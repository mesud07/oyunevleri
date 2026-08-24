<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Hizmet;

final class HizmetController extends Controller
{
    public function sayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $this->view('panel/hizmetler', [
            'baslik' => 'Hizmetler',
            'aktif' => 'hizmetler',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
        ], 'panel');
    }

    public function liste(): void
    {
        Response::json(['basari' => true, 'mesaj' => 'Hizmetler listelendi.', 'veri' => Hizmet::liste()]);
    }

    public function ekle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['hizmet_adi', 'ucret']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        $haftalik = (int) ($data['haftalik_katilim_sayisi'] ?? 1);
        $id = Hizmet::ekle([
            'hizmet_adi' => trim((string) $data['hizmet_adi']),
            'ucret' => (float) $data['ucret'],
            'haftalik_katilim_sayisi' => $haftalik,
            'toplam_normal_hak' => (int) ($data['toplam_normal_hak'] ?? ($haftalik === 1 ? 4 : 8)),
            'toplam_telafi_hak' => (int) ($data['toplam_telafi_hak'] ?? ($haftalik === 1 ? 1 : 2)),
        ]);

        Response::json(['basari' => true, 'mesaj' => 'Tanim kaydi olusturuldu.', 'veri' => ['id' => $id]], 201);
    }

    public function guncelle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['id', 'hizmet_adi', 'ucret']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        $id = (int) $data['id'];
        if ($id < 1 || !Hizmet::idIleBul($id)) {
            Response::json(['basari' => false, 'mesaj' => 'Hizmet bulunamadi.', 'hatalar' => []], 404);
            return;
        }

        $haftalik = max(1, (int) ($data['haftalik_katilim_sayisi'] ?? 1));
        Hizmet::guncelle($id, [
            'hizmet_adi' => trim((string) $data['hizmet_adi']),
            'ucret' => (float) $data['ucret'],
            'haftalik_katilim_sayisi' => $haftalik,
            'toplam_normal_hak' => max(1, (int) ($data['toplam_normal_hak'] ?? ($haftalik === 1 ? 4 : 8))),
            'toplam_telafi_hak' => max(0, (int) ($data['toplam_telafi_hak'] ?? ($haftalik === 1 ? 1 : 2))),
            'aktif' => (int) ($data['aktif'] ?? 1),
        ]);

        Response::json(['basari' => true, 'mesaj' => 'Hizmet guncellendi.', 'veri' => ['id' => $id]]);
    }

    public function sil(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        if ($id < 1 || !Hizmet::idIleBul($id)) {
            Response::json(['basari' => false, 'mesaj' => 'Hizmet bulunamadi.', 'hatalar' => []], 404);
            return;
        }

        $basarili = Hizmet::sil($id);
        Response::json([
            'basari' => $basarili,
            'mesaj' => $basarili ? 'Hizmet tanimi silindi.' : 'Hizmet silinemedi.',
            'veri' => ['id' => $id],
        ], $basarili ? 200 : 409);
    }
}
