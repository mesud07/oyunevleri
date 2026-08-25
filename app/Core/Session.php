<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    private const BENI_HATIRLA_SURESI = 2592000;

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = Config::get('APP_ENV') === 'production'
            && (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $normalLifetime = max(60, (int) Config::get('SESSION_LIFETIME', 7200));
        $maxLifetime = max($normalLifetime, self::BENI_HATIRLA_SURESI);
        $sessionPath = BASE_PATH . '/storage/sessions';
        if (!is_dir($sessionPath)) {
            @mkdir($sessionPath, 0775, true);
        }
        if (is_dir($sessionPath) && is_writable($sessionPath)) {
            session_save_path($sessionPath);
        }
        ini_set('session.gc_maxlifetime', (string) $maxLifetime);
        session_name((string) Config::get('SESSION_NAME', 'talya_kids_session'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        $beniHatirla = !empty($_SESSION['_beni_hatirla']);
        $lifetime = $beniHatirla ? self::BENI_HATIRLA_SURESI : $normalLifetime;
        $now = time();
        if (isset($_SESSION['_son_aktivite']) && ($now - (int) $_SESSION['_son_aktivite']) > $lifetime) {
            self::destroy();
            session_start();
            $beniHatirla = false;
            $lifetime = $normalLifetime;
        }
        $_SESSION['_son_aktivite'] = $now;
        self::refreshCookie($beniHatirla ? $lifetime : 0, $secure);
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

    public static function beniHatirla(bool $aktif): void
    {
        self::set('_beni_hatirla', $aktif);
        self::set('_son_aktivite', time());
        $secure = Config::get('APP_ENV') === 'production'
            && (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        self::refreshCookie($aktif ? self::BENI_HATIRLA_SURESI : 0, $secure);
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
            'expires' => $lifetime > 0 ? time() + $lifetime : 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
