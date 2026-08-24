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
            'baslik' => 'Veliler',
            'aktif' => 'veliler',
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

        $id = Veli::ekle([
            'ad' => trim((string) $data['ad']),
            'soyad' => trim((string) $data['soyad']),
            'telefon' => trim((string) $data['telefon']),
            'eposta' => trim((string) ($data['eposta'] ?? '')),
            'yakinlik' => trim((string) ($data['yakinlik'] ?? '')),
            'adres' => trim((string) ($data['adres'] ?? '')),
            'iletisim_referansi' => trim((string) ($data['iletisim_referansi'] ?? '')),
            'notlar' => trim((string) ($data['notlar'] ?? '')),
        ]);

        Response::json(['basari' => true, 'mesaj' => 'Veli kaydi olusturuldu.', 'veri' => ['id' => $id]], 201);
    }
}
