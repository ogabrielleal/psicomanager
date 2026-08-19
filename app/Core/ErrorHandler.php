<?php
declare(strict_types=1);

namespace App\Core;

use ErrorException;
use Throwable;

final class ErrorHandler
{
    private static bool $handling = false;

    public static function register(): void
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) return false;
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        set_exception_handler(static fn(Throwable $e) => self::handle($e));
        register_shutdown_function(static function (): void {
            $last = error_get_last();
            if (!$last || !in_array($last['type'], [E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR], true)) return;
            self::handle(new ErrorException((string)$last['message'], 0, (int)$last['type'], (string)$last['file'], (int)$last['line']));
        });
    }

    public static function handle(Throwable $e): void
    {
        if (self::$handling) return;
        self::$handling = true;

        $payload = [
            'ts' => gmdate('c'),
            'request_id' => RequestContext::id(),
            'method' => RequestContext::method(),
            'route' => RequestContext::route(),
            'tenant_id' => (int)($_SESSION['auth_tenant_id'] ?? $_SESSION['portal_tenant_id'] ?? 0) ?: null,
            'user_id' => (int)($_SESSION['auth_user_id'] ?? 0) ?: null,
            'portal_patient_id' => (int)($_SESSION['portal_patient_id'] ?? 0) ?: null,
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace_hash' => hash('sha256', $e->getTraceAsString()),
        ];
        self::write($payload);

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, '[PsicoManager][' . RequestContext::id() . '] ' . $e->getMessage() . PHP_EOL);
            self::$handling = false;
            return;
        }

        if (!headers_sent()) http_response_code(500);
        $debug = filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL);
        $error = $debug ? $e : null;
        $requestId = RequestContext::id();
        $view = APP_ROOT . '/app/views/500.php';
        if (is_file($view)) require $view;
        else echo 'Erro interno. Referência: ' . htmlspecialchars($requestId, ENT_QUOTES, 'UTF-8');
        self::$handling = false;
    }

    public static function client(array $event): void
    {
        self::write([
            'ts' => gmdate('c'),
            'request_id' => RequestContext::id(),
            'kind' => 'client_error',
            'route' => RequestContext::route(),
            'tenant_id' => (int)($_SESSION['auth_tenant_id'] ?? $_SESSION['portal_tenant_id'] ?? 0) ?: null,
            'user_id' => (int)($_SESSION['auth_user_id'] ?? 0) ?: null,
            'message' => mb_substr((string)($event['message'] ?? 'Client error'), 0, 500),
            'source' => mb_substr((string)($event['source'] ?? ''), 0, 500),
            'line' => (int)($event['line'] ?? 0),
            'column' => (int)($event['column'] ?? 0),
        ], 'client');
    }

    private static function write(array $payload, string $channel = 'app'): void
    {
        $dir = APP_ROOT . '/storage/logs';
        if (!is_dir($dir)) @mkdir($dir, 0750, true);
        $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        @file_put_contents($dir . '/' . $channel . '-' . gmdate('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
        error_log('[PsicoManager][' . RequestContext::id() . '] ' . ($payload['message'] ?? $payload['kind'] ?? 'event'));
    }
}
