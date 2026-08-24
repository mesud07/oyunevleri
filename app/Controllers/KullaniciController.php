<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Kullanici;
use App\Services\YetkiServisi;

final class KullaniciController extends Controller
{
    public function sayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }
        if (!(new YetkiServisi())->izinliMi('kullanici_yonet')) {
            http_response_code(403);
            require BASE_PATH . '/resources/views/errors/403.php';
            return;
        }

        $this->view('panel/kullanicilar', [
            'baslik' => 'Kullanicilar',
            'aktif' => 'kullanicilar',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'roller' => Kullanici::roller(),
            'yetkiSecenekleri' => Kullanici::yetkiSecenekleri(),
        ], 'panel');
    }

    public function liste(): void
    {
        Response::json(['basari' => true, 'mesaj' => 'Kullanicilar listelendi.', 'veri' => Kullanici::liste()]);
    }

    public function roller(): void
    {
        Response::json(['basari' => true, 'mesaj' => 'Roller listelendi.', 'veri' => [
            'roller' => Kullanici::rollerDetayli(),
            'yetkiler' => Kullanici::yetkiSecenekleri(),
        ]]);
    }

    public function rolKaydet(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        $hatalar = Validator::gerekli($data, ['ad']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Rol adi zorunludur.', 'hatalar' => $hatalar], 422);
            return;
        }

        $ad = trim((string) $data['ad']);
        $kod = $id > 0 ? '' : preg_replace('/[^a-z0-9_]/', '', strtolower(strtr($ad, [
            'ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u',
            'Ç' => 'c', 'Ğ' => 'g', 'İ' => 'i', 'Ö' => 'o', 'Ş' => 's', 'Ü' => 'u',
            ' ' => '_', '-' => '_',
        ])));
        $kod = trim((string) ($data['kod'] ?? $kod), '_');
        if ($id < 1 && ($kod === '' || Kullanici::rolKoduVarMi($kod))) {
            Response::json(['basari' => false, 'mesaj' => 'Rol kodu bos veya kullaniliyor.', 'hatalar' => ['kod' => 'Rol kodu kullaniliyor.']], 422);
            return;
        }

        $rolId = Kullanici::rolKaydet($id, [
            'ad' => $ad,
            'kod' => $kod,
            'yetkiler' => is_array($data['yetkiler'] ?? null) ? $data['yetkiler'] : [],
        ]);
        Response::json(['basari' => true, 'mesaj' => 'Kullanici tipi kaydedildi.', 'veri' => ['id' => $rolId]]);
    }

    public function kaydet(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        $zorunlu = ['rol_id', 'ad', 'soyad', 'eposta'];
        if ($id < 1) {
            $zorunlu[] = 'sifre';
        }
        $hatalar = Validator::gerekli($data, $zorunlu);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        $eposta = trim((string) $data['eposta']);
        $rolId = (int) $data['rol_id'];
        $sifre = trim((string) ($data['sifre'] ?? ''));
        if (!preg_match('/^[A-Za-z0-9._@-]{3,190}$/', $eposta)) {
            Response::json(['basari' => false, 'mesaj' => 'Gecerli bir kullanici adi veya e-posta yazin.', 'hatalar' => ['eposta' => 'Gecersiz giris bilgisi.']], 422);
            return;
        }
        if (!Kullanici::rolVarMi($rolId)) {
            Response::json(['basari' => false, 'mesaj' => 'Rol bulunamadi.', 'hatalar' => ['rol_id' => 'Rol bulunamadi.']], 422);
            return;
        }
        if (Kullanici::epostaVarMi($eposta, $id)) {
            Response::json(['basari' => false, 'mesaj' => 'Bu kullanici adi zaten kullaniliyor.', 'hatalar' => ['eposta' => 'Kullanici adi kullaniliyor.']], 422);
            return;
        }
        if ($sifre !== '' && strlen($sifre) < 8) {
            Response::json(['basari' => false, 'mesaj' => 'Sifre en az 8 karakter olmalidir.', 'hatalar' => ['sifre' => 'Sifre kisa.']], 422);
            return;
        }

        $veri = [
            'rol_id' => $rolId,
            'ad' => trim((string) $data['ad']),
            'soyad' => trim((string) $data['soyad']),
            'eposta' => $eposta,
            'telefon' => trim((string) ($data['telefon'] ?? '')),
            'sifre' => $sifre,
            'aktif' => (int) ($data['aktif'] ?? 1),
        ];

        if ($id > 0) {
            Kullanici::guncelle($id, $veri);
            Response::json(['basari' => true, 'mesaj' => 'Kullanici guncellendi.', 'veri' => ['id' => $id]]);
            return;
        }

        $yeniId = Kullanici::ekle($veri);
        Response::json(['basari' => true, 'mesaj' => 'Kullanici olusturuldu.', 'veri' => ['id' => $yeniId]], 201);
    }
}
