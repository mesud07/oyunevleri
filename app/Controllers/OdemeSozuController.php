<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Validator;
use App\Models\OdemeSozu;
use App\Models\Paket;

final class OdemeSozuController extends Controller
{
    public function sayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $this->view('panel/odeme-sozleri', [
            'baslik' => 'Odeme Sozleri',
            'aktif' => 'odeme-sozleri',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'paketler' => Paket::secenekler(),
        ], 'panel');
    }

    public function liste(): void
    {
        Response::json(['basari' => true, 'mesaj' => 'Odeme sozleri listelendi.', 'veri' => OdemeSozu::liste()]);
    }

    public function ekle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['paket_id', 'soz_verilen_tutar', 'soz_verilen_tarih']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        try {
            $kullanici = Auth::user();
            $id = OdemeSozu::ekle([
                'paket_id' => (int) $data['paket_id'],
                'veli_id' => (int) ($data['veli_id'] ?? 0),
                'soz_verilen_tutar' => (float) $data['soz_verilen_tutar'],
                'soz_verilen_tarih' => trim((string) $data['soz_verilen_tarih']),
                'hatirlatma_tarihi' => trim((string) ($data['hatirlatma_tarihi'] ?? '')),
                'aciklama' => trim((string) ($data['aciklama'] ?? '')),
                'olusturan_kullanici_id' => (int) ($kullanici['id'] ?? 0),
            ]);
        } catch (\Throwable $e) {
            Response::json(['basari' => false, 'mesaj' => $e->getMessage(), 'hatalar' => []], 422);
            return;
        }

        Response::json(['basari' => true, 'mesaj' => 'Odeme sozu olusturuldu.', 'veri' => ['id' => $id]], 201);
    }
}
