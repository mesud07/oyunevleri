<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Kullanici;

final class Auth
{
    public static function user(): ?array
    {
        $id = Session::get('kullanici_id');
        return $id ? Kullanici::idIleBul((int) $id) : null;
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
        Session::destroy();
    }
}
