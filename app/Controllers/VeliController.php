<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Veli;

final class VeliController extends Controller
{
    public function sayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $this->view('panel/veliler', [
            'baslik' => 'Adres Defteri',
            'aktif' => 'adres-defteri',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
        ], 'panel');
    }

    public function liste(): void
    {
        Response::json(['basari' => true, 'mesaj' => 'Veliler listelendi.', 'veri' => Veli::liste()]);
    }

    public function ekle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['ad', 'soyad', 'telefon']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        $id = Veli::ekle($this->formVerisi($data));

        Response::json(['basari' => true, 'mesaj' => 'Adres defteri kaydı oluşturuldu.', 'veri' => ['id' => $id]], 201);
    }

    public function guncelle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['id', 'ad', 'soyad', 'telefon']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        try {
            $guncellendi = Veli::guncelle((int) $data['id'], $this->formVerisi($data));
        } catch (\RuntimeException $e) {
            Response::json(['basari' => false, 'mesaj' => $e->getMessage(), 'hatalar' => []], 422);
            return;
        }
        if (!$guncellendi) {
            Response::json(['basari' => false, 'mesaj' => 'Güncellenecek kişi bulunamadı.', 'hatalar' => []], 404);
            return;
        }

        Response::json(['basari' => true, 'mesaj' => 'Adres defteri kaydı güncellendi.', 'veri' => ['id' => (int) $data['id']]]);
    }

    private function formVerisi(array $data): array
    {
        return [
            'ad' => trim((string) $data['ad']),
            'soyad' => trim((string) $data['soyad']),
            'telefon' => trim((string) $data['telefon']),
            'telefon_ulke' => trim((string) ($data['telefon_ulke'] ?? 'Türkiye (+90)')),
            'yedek_telefon' => trim((string) ($data['yedek_telefon'] ?? '')),
            'eposta' => trim((string) ($data['eposta'] ?? '')),
            'yakinlik' => trim((string) ($data['yakinlik'] ?? '')),
            'il' => trim((string) ($data['il'] ?? '')),
            'ilce' => trim((string) ($data['ilce'] ?? '')),
            'adres' => trim((string) ($data['adres'] ?? '')),
            'iletisim_referansi' => trim((string) ($data['iletisim_referansi'] ?? '')),
            'notlar' => trim((string) ($data['notlar'] ?? '')),
        ];
    }
}
