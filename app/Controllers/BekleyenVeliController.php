<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Validator;
use App\Models\BekleyenVeli;

final class BekleyenVeliController extends Controller
{
    public function sayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $this->view('panel/bekleyen-veliler', [
            'baslik' => 'Bekleyen Veliler',
            'aktif' => 'bekleyen-veliler',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
        ], 'panel');
    }

    public function liste(): void
    {
        Response::json(['basari' => true, 'mesaj' => 'Bekleyen veliler listelendi.', 'veri' => BekleyenVeli::liste()]);
    }

    public function ekle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['ogrenci_ad_soyad', 'veli_ad_soyad', 'veli_telefon']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Yildizli alanlari doldurun.', 'hatalar' => $hatalar], 422);
            return;
        }

        $zamanTercihi = trim((string) ($data['zaman_tercihi'] ?? 'farketmez'));
        if (!in_array($zamanTercihi, ['hafta_ici', 'hafta_sonu', 'farketmez'], true)) {
            $zamanTercihi = 'farketmez';
        }

        $id = BekleyenVeli::ekle([
            'ogrenci_ad_soyad' => trim((string) $data['ogrenci_ad_soyad']),
            'ogrenci_dogum_tarihi' => trim((string) ($data['ogrenci_dogum_tarihi'] ?? '')),
            'veli_ad_soyad' => trim((string) $data['veli_ad_soyad']),
            'veli_telefon' => $this->telefonFormatla((string) $data['veli_telefon']),
            'veli_eposta' => trim((string) ($data['veli_eposta'] ?? '')),
            'beklenen_gun' => trim((string) ($data['beklenen_gun'] ?? '')),
            'ay_grubu' => trim((string) ($data['ay_grubu'] ?? '')),
            'iletisim_referansi' => trim((string) ($data['iletisim_referansi'] ?? '')),
            'zaman_tercihi' => $zamanTercihi,
            'notlar' => trim((string) ($data['notlar'] ?? '')),
            'olusturan_kullanici_id' => (int) (Auth::user()['id'] ?? 0),
        ]);

        Response::json(['basari' => true, 'mesaj' => 'Bekleyen veli kaydi olusturuldu.', 'veri' => ['id' => $id]], 201);
    }

    public function durumGuncelle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        $durum = trim((string) ($data['durum'] ?? ''));
        if ($id < 1 || $durum === '') {
            Response::json(['basari' => false, 'mesaj' => 'Kayit ve durum secilmelidir.', 'hatalar' => []], 422);
            return;
        }

        if (!BekleyenVeli::durumGuncelle($id, $durum)) {
            Response::json(['basari' => false, 'mesaj' => 'Bekleyen veli kaydi bulunamadi veya durum gecersiz.', 'hatalar' => []], 404);
            return;
        }

        Response::json(['basari' => true, 'mesaj' => 'Bekleyen veli durumu guncellendi.', 'veri' => ['id' => $id]]);
    }

    public function ogrenciyeDonustur(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        if ($id < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Aktarilacak kayit secilmelidir.', 'hatalar' => []], 422);
            return;
        }

        $ogrenciId = BekleyenVeli::ogrenciyeDonustur($id);
        if ($ogrenciId < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Bekleyen veli kaydi aktif ogrenciye aktarilamadi.', 'hatalar' => []], 422);
            return;
        }

        Response::json([
            'basari' => true,
            'mesaj' => 'Bekleyen veli aktif ogrenciye aktarildi.',
            'veri' => ['id' => $id, 'ogrenci_id' => $ogrenciId],
        ]);
    }

    public function sil(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        if ($id < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Silinecek kayit secilmelidir.', 'hatalar' => []], 422);
            return;
        }

        if (!BekleyenVeli::sil($id)) {
            Response::json(['basari' => false, 'mesaj' => 'Bekleyen veli kaydi bulunamadi.', 'hatalar' => []], 404);
            return;
        }

        Response::json(['basari' => true, 'mesaj' => 'Bekleyen veli kaydi silindi.', 'veri' => ['id' => $id]]);
    }

    private function telefonFormatla(string $telefon): string
    {
        $rakamlar = preg_replace('/\D+/', '', $telefon) ?? '';
        if ($rakamlar === '') {
            return '';
        }

        if (str_starts_with($rakamlar, '90')) {
            $rakamlar = substr($rakamlar, 2);
        }
        if (str_starts_with($rakamlar, '0')) {
            $rakamlar = substr($rakamlar, 1);
        }

        $rakamlar = substr($rakamlar, 0, 10);
        $formatli = '0(' . substr($rakamlar, 0, 3);
        if (strlen($rakamlar) >= 3) {
            $formatli .= ')';
        }
        if (strlen($rakamlar) > 3) {
            $formatli .= ' ' . substr($rakamlar, 3, 3);
        }
        if (strlen($rakamlar) > 6) {
            $formatli .= ' ' . substr($rakamlar, 6, 2);
        }
        if (strlen($rakamlar) > 8) {
            $formatli .= ' ' . substr($rakamlar, 8, 2);
        }

        return $formatli;
    }
}
