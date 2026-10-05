<?php

declare(strict_types=1);

namespace App\Core;

final class SecurityHeaders
{
    private static ?string $nonce = null;

    public static function nonce(): string
    {
        return self::$nonce ??= base64_encode(random_bytes(18));
    }

    public static function apply(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        header("Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none'; form-action 'self'; img-src 'self' data: https://tile.openstreetmap.org https://unpkg.com https://maps.googleapis.com https://maps.gstatic.com; font-src 'self' data: https://fonts.gstatic.com; connect-src 'self' https://maps.googleapis.com https://maps.gstatic.com; script-src 'self' https://unpkg.com https://maps.googleapis.com https://maps.gstatic.com 'nonce-" . self::nonce() . "'; style-src 'self' https://unpkg.com https://fonts.googleapis.com 'unsafe-inline'");

        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        if (str_starts_with($path, '/panel') || str_starts_with($path, '/veli-portal') || str_starts_with($path, '/oyun-grubu-onam')) {
            header('Cache-Control: private, no-store, max-age=0');
            header('Pragma: no-cache');
        }

        if (Request::isSecure() && Config::get('APP_ENV') === 'production') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
