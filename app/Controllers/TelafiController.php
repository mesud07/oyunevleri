<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;
use App\Core\Response;
use App\Core\Validator;
use App\Models\TelafiHakki;
use App\Services\SmsServisi;

final class TelafiController
{
    public function liste(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        Response::json([
            'basari' => true,
            'mesaj' => 'Telafi listesi hazir.',
            'veri' => TelafiHakki::bekleyenler((int) ($data['ogrenci_id'] ?? 0)),
        ]);
    }

    public function planla(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['id', 'tarih', 'baslangic_saati']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        $kullaniciId = (int) (Auth::user()['id'] ?? 0);
        $randevuId = TelafiHakki::planla((int) $data['id'], [
            'tarih' => trim((string) $data['tarih']),
            'baslangic_saati' => trim((string) $data['baslangic_saati']),
            'sure_dakika' => (int) ($data['sure_dakika'] ?? 45),
            'grup_id' => (int) ($data['grup_id'] ?? 0),
            'ogretmen_id' => (int) ($data['ogretmen_id'] ?? 0),
            'aciklama' => trim((string) ($data['aciklama'] ?? '')),
            'olusturan_kullanici_id' => $kullaniciId,
        ]);

        if ($randevuId < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Planlanacak telafi hakki bulunamadi.', 'hatalar' => []], 404);
            return;
        }

        $smsAdet = 0;
        try {
            $smsServisi = new SmsServisi();
            $smsAdet = $smsServisi->telafiDersiOlusturuldu($randevuId, $kullaniciId);
            if ($smsAdet > 0) {
                $smsServisi->kuyrukIsle(20);
            }
        } catch (\Throwable $e) {
            error_log('Telafi dersi SMS kuyrugu olusturulamadi veya islenemedi: ' . $e->getMessage());
        }

        Response::json([
            'basari' => true,
            'mesaj' => 'Telafi dersi randevu olarak olusturuldu.',
            'veri' => ['id' => $randevuId, 'sms_adet' => $smsAdet],
        ], 201);
    }
}
