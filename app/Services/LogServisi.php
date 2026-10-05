<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Session;
use App\Models\IslemKaydi;

final class LogServisi
{
    public function yaz(string $islem, string $aciklama, array $veri = []): void
    {
        // Hesap guncellemesi oturum surumunu degistirebilir. Aktor ve kurum
        // bilgisini Auth kontrolunden once alarak basarili islemin log yuzunden
        // AJAX hatasina donusmesini engelle.
        $oturumKullaniciId = max(0, (int) Session::get('kullanici_id', 0));
        $oturumKurumId = max(0, (int) Session::get('kurum_id', 0));
        $kullanici = Auth::user();
        $kullaniciId = $kullanici ? (int) $kullanici['id'] : ($oturumKullaniciId ?: null);
        if (!isset($veri['kurum_id']) && $oturumKurumId > 0) {
            $veri['kurum_id'] = $oturumKurumId;
        }
        IslemKaydi::ekle($kullaniciId, $islem, $aciklama, $veri);
    }
}
