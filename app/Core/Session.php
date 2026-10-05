<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = Config::bool('SESSION_COOKIE_SECURE', Config::get('APP_ENV') === 'production')
            && (Request::isSecure() || Config::get('APP_ENV') === 'production');
        $idleTimeout = max(300, (int) Config::get('SESSION_IDLE_TIMEOUT', 1800));
        $absoluteTimeout = max($idleTimeout, (int) Config::get('SESSION_ABSOLUTE_LIFETIME', 43200));
        $cookieLifetime = max(0, (int) Config::get('SESSION_COOKIE_LIFETIME', 0));
        $gcLifetime = $absoluteTimeout;
        $sessionPath = BASE_PATH . '/storage/sessions';
        if (!is_dir($sessionPath)) {
            @mkdir($sessionPath, 0770, true);
        }
        if (is_dir($sessionPath) && is_writable($sessionPath)) {
            session_save_path($sessionPath);
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.gc_maxlifetime', (string) $gcLifetime);
        session_name((string) Config::get('SESSION_NAME', 'talya_kids_session'));
        session_set_cookie_params([
            'lifetime' => $cookieLifetime,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        $now = time();
        $idleExpired = isset($_SESSION['_son_aktivite']) && ($now - (int) $_SESSION['_son_aktivite']) > $idleTimeout;
        $absoluteExpired = isset($_SESSION['_olusturulma']) && ($now - (int) $_SESSION['_olusturulma']) > $absoluteTimeout;
        if ($idleExpired || $absoluteExpired) {
            self::resetSession();
        }
        if (!isset($_SESSION['_olusturulma'])) {
            $_SESSION['_olusturulma'] = $now;
        }
        if (!isset($_SESSION['_son_yenileme']) || ($now - (int) $_SESSION['_son_yenileme']) > 900) {
            self::regenerate();
            $_SESSION['_son_yenileme'] = $now;
        }
        $_SESSION['_son_aktivite'] = $now;
        self::refreshCookie($cookieLifetime, $secure);
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    private static function resetSession(): void
    {
        self::destroy();
        session_start();
    }

    private static function refreshCookie(int $lifetime, bool $secure): void
    {
        if ($lifetime === 0 || !ini_get('session.use_cookies') || session_id() === '') {
            return;
        }

        setcookie(session_name(), session_id(), [
            'expires' => $lifetime > 0 ? time() + $lifetime : 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
