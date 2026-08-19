<?php
declare(strict_types=1);

namespace App\Core;

final class SecurityHeaders
{
    public static function apply(): void
    {
        if (headers_sent()) return;
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-site');
        header('X-Permitted-Cross-Domain-Policies: none');
        header('Cache-Control: no-store, private');

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        if ($https && (string)env('APP_ENV', 'production') === 'production') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        $csp = "default-src 'self'; "
            . "img-src 'self' data: https://images.unsplash.com; "
            . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
            . "font-src 'self' https://fonts.gstatic.com; "
            . "script-src 'self'; "
            . "connect-src 'self' https://generativelanguage.googleapis.com; "
            . "object-src 'none'; frame-src 'none'; frame-ancestors 'none'; "
            . "base-uri 'self'; form-action 'self'; upgrade-insecure-requests;";
        header('Content-Security-Policy: ' . $csp);
    }
}
