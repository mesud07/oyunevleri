<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Validator;
use App\Models\GunlukKayit;
use App\Models\Randevu;

final class GunlukKayitController extends Controller
{
    public function sayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $bugun = date('Y-m-d');
        $baslangic = $this->tarihAl($_GET['baslangic'] ?? $bugun, $bugun);
        $bitis = $this->tarihAl($_GET['bitis'] ?? $baslangic, $baslangic);

        if ($bitis < $baslangic) {
            $gecici = $baslangic;
            $baslangic = $bitis;
            $bitis = $gecici;
        }

        $kayitlar = GunlukKayit::liste($baslangic, $bitis);

        $this->view('panel/gunluk-kayitlar', [
            'baslik' => 'Gunluk Notlar',
            'aktif' => 'gunluk-kayitlar',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'baslangic' => $baslangic,
            'bitis' => $bitis,
            'kayitlar' => $kayitlar,
            'ozet' => GunlukKayit::ozet($kayitlar),
        ], 'panel');
    }

    public function ekle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['randevu_id', 'not_metni']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Randevu ve not zorunludur.', 'hatalar' => $hatalar], 422);
            return;
        }

        $randevu = Randevu::idIleBul((int) $data['randevu_id']);
        if (!$randevu) {
            Response::json(['basari' => false, 'mesaj' => 'Randevu bulunamadi.', 'hatalar' => []], 404);
            return;
        }

        $notMetni = trim((string) $data['not_metni']);
        if ($notMetni === '') {
            Response::json(['basari' => false, 'mesaj' => 'Not metni bos olamaz.', 'hatalar' => ['not_metni' => 'Not yazin.']], 422);
            return;
        }

        $tarih = $this->tarihAl($data['tarih'] ?? ($randevu['tarih'] ?? date('Y-m-d')), (string) ($randevu['tarih'] ?? date('Y-m-d')));
        $id = GunlukKayit::ekle([
            'ogrenci_id' => (int) $randevu['ogrenci_id'],
            'randevu_id' => (int) $randevu['id'],
            'tarih' => $tarih,
            'kategori' => trim((string) ($data['kategori'] ?? 'Genel')) ?: 'Genel',
            'not_metni' => $notMetni,
            'olusturan_kullanici_id' => (int) (Auth::user()['id'] ?? 0),
        ]);

        Response::json(['basari' => true, 'mesaj' => 'Gunluk not kaydedildi.', 'veri' => ['id' => $id]], 201);
    }

    private function tarihAl($deger, string $varsayilan): string
    {
        $deger = (string) $deger;

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $deger) === 1 ? $deger : $varsayilan;
    }
}
