<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Kasa;

final class KasaController extends Controller
{
    public function sayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $this->view('panel/kasalar', [
            'baslik' => 'Kasalar',
            'aktif' => 'odemeler-kasalar',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
        ], 'panel');
    }

    public function liste(): void
    {
        Response::json([
            'basari' => true,
            'mesaj' => 'Kasalar listelendi.',
            'veri' => [
                'ozet' => Kasa::ozet(),
                'kasalar' => Kasa::liste(),
                'hareketler' => Kasa::hareketler(),
            ],
        ]);
    }

    public function ekle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['ad', 'tur', 'para_birimi']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        $id = Kasa::ekle($this->veri($data) + ['olusturan_kullanici_id' => (int) (Auth::user()['id'] ?? 0)]);
        Response::json(['basari' => true, 'mesaj' => 'Kasa olusturuldu.', 'veri' => ['id' => $id]], 201);
    }

    public function guncelle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['id', 'ad', 'tur', 'para_birimi']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        $basarili = Kasa::guncelle((int) $data['id'], $this->veri($data));
        Response::json([
            'basari' => $basarili,
            'mesaj' => $basarili ? 'Kasa guncellendi.' : 'Kasa bulunamadi.',
            'veri' => ['id' => (int) $data['id']],
        ], $basarili ? 200 : 404);
    }

    public function sil(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        if ($id < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Silinecek kasa secilmelidir.', 'hatalar' => []], 422);
            return;
        }

        $basarili = Kasa::sil($id);
        Response::json([
            'basari' => $basarili,
            'mesaj' => $basarili ? 'Kasa silindi veya pasife alindi.' : 'Kasa bulunamadi.',
            'veri' => ['id' => $id],
        ], $basarili ? 200 : 404);
    }

    public function hareketEkle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['kasa_id', 'tarih', 'tur', 'tutar']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        $id = Kasa::hareketEkle([
            'kasa_id' => (int) $data['kasa_id'],
            'tarih' => trim((string) $data['tarih']),
            'tur' => trim((string) $data['tur']),
            'tutar' => (float) $data['tutar'],
            'aciklama' => trim((string) ($data['aciklama'] ?? '')),
            'olusturan_kullanici_id' => (int) (Auth::user()['id'] ?? 0),
        ]);

        Response::json(['basari' => true, 'mesaj' => 'Kasa hareketi eklendi.', 'veri' => ['id' => $id]], 201);
    }

    private function veri(array $data): array
    {
        return [
            'ad' => trim((string) $data['ad']),
            'tur' => trim((string) $data['tur']),
            'para_birimi' => trim((string) ($data['para_birimi'] ?? 'TRY')),
            'acilis_bakiyesi' => (float) ($data['acilis_bakiyesi'] ?? 0),
            'aciklama' => trim((string) ($data['aciklama'] ?? '')),
            'aktif' => (int) ($data['aktif'] ?? 1),
        ];
    }
}
