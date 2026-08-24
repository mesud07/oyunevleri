<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Models\IslemKaydi;
use App\Models\Kullanici;

final class KimlikDogrulamaServisi
{
    public function giris(string $eposta, string $sifre, string $kurumKodu = 'TALYA'): bool
    {
        $kurumKodu = strtoupper(trim($kurumKodu) !== '' ? trim($kurumKodu) : 'TALYA');
        $kullanici = Kullanici::epostaIleBul($eposta, $kurumKodu);
        if (!$kullanici || !password_verify($sifre, (string) $kullanici['sifre'])) {
            IslemKaydi::ekle(null, 'giris_basarisiz', 'Basarisiz giris denemesi', [
                'eposta' => $eposta,
                'kurum_kodu' => $kurumKodu,
            ]);
            return false;
        }

        Auth::login($kullanici);
        IslemKaydi::ekle((int) $kullanici['id'], 'giris_basarili', 'Kullanici giris yapti');
        return true;
    }
}
