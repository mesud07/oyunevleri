<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        $key = (string) Config::get('CSRF_TOKEN_NAME', 'talya_csrf');
        if (!Session::get($key)) {
            Session::set($key, bin2hex(random_bytes(32)));
        }
        return (string) Session::get($key);
    }

    public static function dogrula(?string $token): bool
    {
        return is_string($token) && hash_equals(self::token(), $token);
    }
}
