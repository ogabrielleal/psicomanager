<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/app/helpers.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = substr($class, strlen($prefix));
    $path = APP_ROOT . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) require_once $path;
});

\App\Core\Env::load(APP_ROOT . '/.env');
date_default_timezone_set((string) env('APP_TIMEZONE', 'America/Bahia'));
\App\Core\RequestContext::boot();
\App\Core\ErrorHandler::register();

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('psicomanager_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => filter_var(env('COOKIE_SECURE', $isHttps ? 'true' : 'false'), FILTER_VALIDATE_BOOL),
    'httponly' => true,
    'samesite' => 'Strict',
]);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

\App\Core\SecurityHeaders::apply();

if (is_installed()) {
    \App\Core\EnvValidator::validate();
    \App\Core\Database::boot();
}
