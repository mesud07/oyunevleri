<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public static function clientIp(): string
    {
        $remote = self::normalizeIp((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($remote === null) {
            return '0.0.0.0';
        }

        if (!self::trustedProxy($remote)) {
            return $remote;
        }

        $forwarded = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
        foreach (array_map('trim', explode(',', $forwarded)) as $candidate) {
            $ip = self::normalizeIp($candidate);
            if ($ip !== null && !self::trustedProxy($ip)) {
                return $ip;
            }
        }

        return $remote;
    }

    public static function isSecure(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        $remote = self::normalizeIp((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($remote === null || !self::trustedProxy($remote)) {
            return false;
        }

        return strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))) === 'https';
    }

    private static function trustedProxy(string $ip): bool
    {
        $configured = array_filter(array_map('trim', explode(',', (string) Config::get('TRUSTED_PROXIES', '127.0.0.1,::1'))));
        return in_array($ip, $configured, true);
    }

    private static function normalizeIp(string $ip): ?string
    {
        $ip = trim($ip);
        return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : null;
    }
}
