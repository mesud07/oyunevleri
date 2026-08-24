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

        $secure = Config::get('APP_ENV') === 'production'
            && (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $lifetime = (int) Config::get('SESSION_LIFETIME', 2592000);
        $sessionPath = BASE_PATH . '/storage/sessions';
        if (!is_dir($sessionPath)) {
            @mkdir($sessionPath, 0775, true);
        }
        if (is_dir($sessionPath) && is_writable($sessionPath)) {
            session_save_path($sessionPath);
        }
        ini_set('session.gc_maxlifetime', (string) $lifetime);
        session_name((string) Config::get('SESSION_NAME', 'talya_kids_session'));
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        $now = time();
        if (isset($_SESSION['_son_aktivite']) && ($now - (int) $_SESSION['_son_aktivite']) > $lifetime) {
            self::destroy();
            session_start();
        }
        $_SESSION['_son_aktivite'] = $now;
        self::refreshCookie($lifetime, $secure);
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

    private static function refreshCookie(int $lifetime, bool $secure): void
    {
        if (!ini_get('session.use_cookies') || session_id() === '') {
            return;
        }

        setcookie(session_name(), session_id(), [
            'expires' => time() + $lifetime,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
