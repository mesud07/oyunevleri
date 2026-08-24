<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Kullanici;

final class Auth
{
    private static ?array $kullanici = null;
    private static bool $kullaniciYuklendi = false;

    public static function user(): ?array
    {
        if (self::$kullaniciYuklendi) {
            return self::$kullanici;
        }

        self::$kullaniciYuklendi = true;
        $id = Session::get('kullanici_id');
        self::$kullanici = $id ? Kullanici::idIleBul((int) $id) : null;
        return self::$kullanici;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(array $kullanici): void
    {
        Session::regenerate();
        Session::set('kullanici_id', (int) $kullanici['id']);
        Session::set('kurum_id', (int) $kullanici['kurum_id']);
        Session::set('kurum_kodu', (string) $kullanici['kurum_kodu']);
        Session::set('rol_kodu', (string) $kullanici['rol_kodu']);
        self::$kullanici = $kullanici;
        self::$kullaniciYuklendi = true;
        Kullanici::sonGirisGuncelle((int) $kullanici['id']);
    }

    public static function kurumId(): int
    {
        return max(1, (int) (Session::get('kurum_id') ?: 1));
    }

    public static function sistemYoneticisiMi(): bool
    {
        return (int) (self::user()['sistem_yoneticisi'] ?? 0) === 1;
    }

    public static function logout(): void
    {
        self::$kullanici = null;
        self::$kullaniciYuklendi = true;
        Session::destroy();
    }
}
