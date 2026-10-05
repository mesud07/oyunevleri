<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Models\IslemKaydi;
use App\Models\Kullanici;

final class KimlikDogrulamaServisi
{
    public function dogrula(string $eposta, string $sifre, string $kurumKodu = 'TALYA'): ?array
    {
        $eposta = trim($eposta);
        $kurumKodu = strcasecmp($eposta, 'demo') === 0
            ? 'DEMO'
            : strtoupper(trim($kurumKodu) !== '' ? trim($kurumKodu) : 'TALYA');
        $kullanici = Kullanici::epostaIleBul($eposta, $kurumKodu);
        if (!$kullanici || !password_verify($sifre, (string) $kullanici['sifre'])) {
            IslemKaydi::ekle(null, 'giris_basarisiz', 'Basarisiz giris denemesi', [
                'eposta' => $eposta,
                'kurum_kodu' => $kurumKodu,
            ]);
            return null;
        }

        if (password_needs_rehash((string) $kullanici['sifre'], PASSWORD_DEFAULT)) {
            Kullanici::sifreHashGuncelle((int) $kullanici['id'], password_hash($sifre, PASSWORD_DEFAULT));
        }

        return $kullanici;
    }

    public function oturumAc(array $kullanici, bool $beniHatirla = false): void
    {
        Auth::login($kullanici, $beniHatirla);
        IslemKaydi::ekle((int) $kullanici['id'], 'giris_basarili', 'Kullanıcı giriş yaptı.');
    }
}
