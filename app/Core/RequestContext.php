<?php
declare(strict_types=1);

namespace App\Core;

final class RequestContext
{
    private static string $requestId = '';

    public static function boot(): void
    {
        $incoming = preg_replace('/[^A-Za-z0-9._-]/', '', (string)($_SERVER['HTTP_X_REQUEST_ID'] ?? ''));
        self::$requestId = $incoming !== '' ? substr($incoming, 0, 80) : bin2hex(random_bytes(16));
        if (!headers_sent()) {
            header('X-Request-ID: ' . self::$requestId);
        }
    }

    public static function id(): string
    {
        return self::$requestId !== '' ? self::$requestId : 'unknown';
    }

    public static function route(): string
    {
        return substr((string)($_SERVER['SCRIPT_NAME'] ?? ''), 0, 255);
    }

    public static function method(): string
    {
        return substr((string)($_SERVER['REQUEST_METHOD'] ?? 'CLI'), 0, 12);
    }
}
