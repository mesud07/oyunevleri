<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Kullanici;
use App\Models\IslemKaydi;
use App\Services\KaliciOturumServisi;

final class Auth
{
    private static bool $kaliciOturumDenendi = false;

    public static function user(): ?array
    {
        $id = Session::get('kullanici_id');
        if (!$id && !self::$kaliciOturumDenendi) {
            self::$kaliciOturumDenendi = true;
            $kullanici = (new KaliciOturumServisi())->kullaniciyiGetir();
            if ($kullanici) {
                self::oturumBilgileriniYaz($kullanici);
                IslemKaydi::ekle((int) $kullanici['id'], 'kalici_oturum_girisi', 'Güvenilir cihaz anahtarıyla otomatik giriş yapıldı.');
                $id = (int) $kullanici['id'];
            }
        }
        if (!$id) {
            return null;
        }

        $kullanici = Kullanici::idIleBul((int) $id);
        $oturumSurumu = Session::get('oturum_surumu');
        if (!$kullanici || $oturumSurumu === null || (int) $oturumSurumu !== (int) ($kullanici['oturum_surumu'] ?? 0)) {
            self::logout();
            return null;
        }

        return $kullanici;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(array $kullanici, bool $beniHatirla = false): void
    {
        self::oturumBilgileriniYaz($kullanici);
        $kaliciOturum = new KaliciOturumServisi();
        if ($beniHatirla) {
            $kaliciOturum->olustur($kullanici);
        } else {
            $kaliciOturum->iptalEt();
        }
    }

    private static function oturumBilgileriniYaz(array $kullanici): void
    {
        Session::regenerate();
        Session::set('kullanici_id', (int) $kullanici['id']);
        Session::set('kurum_id', (int) $kullanici['kurum_id']);
        Session::set('kurum_kodu', (string) $kullanici['kurum_kodu']);
        Session::set('rol_kodu', (string) $kullanici['rol_kodu']);
        Session::set('oturum_surumu', (int) ($kullanici['oturum_surumu'] ?? 1));
        Kullanici::sonGirisGuncelle((int) $kullanici['id']);
    }

    public static function kurumId(): int
    {
        return max(0, (int) Session::get('kurum_id', 0));
    }

    public static function sistemYoneticisiMi(): bool
    {
        return (int) (self::user()['sistem_yoneticisi'] ?? 0) === 1;
    }

    public static function logout(): void
    {
        (new KaliciOturumServisi())->iptalEt();
        Session::destroy();
    }
}
