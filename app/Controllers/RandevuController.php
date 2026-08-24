<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Grup;
use App\Models\GunlukKayit;
use App\Models\Ogrenci;
use App\Models\Paket;
use App\Models\Randevu;
use App\Services\SmsServisi;

final class RandevuController extends Controller
{
    public function sayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $this->view('panel/randevular', [
            'baslik' => 'Randevular',
            'aktif' => 'randevular',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
        ], 'panel');
    }

    public function yeni(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $this->view('panel/randevu-yeni', [
            'baslik' => 'Randevu Olustur',
            'aktif' => 'randevular',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'paketler' => Paket::secenekler(),
            'ogrenciler' => Ogrenci::secenekler(),
            'gruplar' => Grup::liste(),
        ], 'panel');
    }

    public function liste(): void
    {
        Response::json([
            'basari' => true,
            'mesaj' => 'Randevular listelendi.',
            'veri' => [
                'ozet' => Randevu::ozet(),
                'kayitlar' => Randevu::liste(),
            ],
        ]);
    }

    public function detay(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $randevu = Randevu::idIleBul((int) ($data['id'] ?? 0));
        if (!$randevu) {
            Response::json(['basari' => false, 'mesaj' => 'Randevu bulunamadi.', 'hatalar' => []], 404);
            return;
        }

        $randevu['gunluk_notlar'] = GunlukKayit::randevuNotlari((int) $randevu['id']);

        Response::json(['basari' => true, 'mesaj' => 'Randevu getirildi.', 'veri' => $randevu]);
    }

    public function takvim(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $baslangic = trim((string) ($data['baslangic'] ?? ''));
        $bitis = trim((string) ($data['bitis'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $baslangic) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $bitis)) {
            Response::json(['basari' => true, 'mesaj' => 'Takvim verisi getirildi.', 'veri' => Randevu::takvimAralik($baslangic, $bitis)]);
            return;
        }

        $ay = trim((string) ($data['ay'] ?? date('Y-m')));
        if (!preg_match('/^\d{4}-\d{2}$/', $ay)) {
            $ay = date('Y-m');
        }

        Response::json(['basari' => true, 'mesaj' => 'Takvim verisi getirildi.', 'veri' => Randevu::takvim($ay)]);
    }

    public function ekle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['tarih', 'baslangic_saati', 'sure_dakika', 'tur', 'hak_kaynagi', 'durum']);
        $ogrenciIdleri = $this->ogrenciIdleri($data);
        $paketId = (int) ($data['paket_id'] ?? 0);
        $paket = null;

        if ($paketId > 0) {
            $paket = Paket::idIleBul($paketId);
            if (!$paket) {
                $hatalar['paket_id'] = 'Gecerli bir paket secilmelidir.';
            }
        } elseif ($ogrenciIdleri === []) {
            $hatalar['ogrenci_id'] = 'En az bir ogrenci veya paket secilmelidir.';
        }

        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }
        if (!Randevu::durumGecerliMi((string) $data['durum'])) {
            Response::json(['basari' => false, 'mesaj' => 'Gecersiz randevu durumu.', 'hatalar' => []], 422);
            return;
        }

        $kullanici = Auth::user();
        $kullaniciId = (int) ($kullanici['id'] ?? 0);
        if ($kullaniciId < 1) {
            Response::json([
                'basari' => false,
                'mesaj' => 'Oturumunuz gecersiz. Lutfen tekrar giris yapin.',
                'hatalar' => [],
            ], 401);
            return;
        }

        if ($paket) {
            $ogrenciIdleri = [(int) $paket['ogrenci_id']];
        }

        $olusanIdler = [];
        foreach ($ogrenciIdleri as $ogrenciId) {
            $id = Randevu::ekle([
                'ogrenci_id' => $ogrenciId,
                'paket_id' => $paketId,
                'grup_id' => (int) ($data['grup_id'] ?? 0),
                'ogretmen_id' => (int) ($data['ogretmen_id'] ?? 0),
                'tarih' => trim((string) $data['tarih']),
                'baslangic_saati' => trim((string) $data['baslangic_saati']),
                'sure_dakika' => (int) $data['sure_dakika'],
                'tur' => trim((string) $data['tur']),
                'hak_kaynagi' => trim((string) $data['hak_kaynagi']),
                'durum' => trim((string) $data['durum']),
                'aciklama' => trim((string) ($data['aciklama'] ?? '')),
                'olusturan_kullanici_id' => $kullaniciId,
            ]);
            $olusanIdler[] = $id;
        }

        if ((string) ($data['randevu_sms_gonder'] ?? '') === '1') {
            try {
                $smsServisi = new SmsServisi();
                $smsServisi->randevularOlusturuldu($olusanIdler);
                $smsServisi->kuyrukIsle(50);
            } catch (\Throwable $e) {
                error_log('Randevu SMS kuyrugu olusturulamadi veya islenemedi: ' . $e->getMessage());
            }
        }

        $adet = count($olusanIdler);
        Response::json([
            'basari' => true,
            'mesaj' => $adet > 1 ? $adet . ' randevu olusturuldu.' : 'Randevu olusturuldu.',
            'veri' => ['ids' => $olusanIdler, 'adet' => $adet],
        ], 201);
    }

    public function guncelle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['id', 'tarih', 'baslangic_saati', 'sure_dakika', 'tur', 'hak_kaynagi', 'durum']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }
        if (!Randevu::durumGecerliMi((string) $data['durum'])) {
            Response::json(['basari' => false, 'mesaj' => 'Gecersiz randevu durumu.', 'hatalar' => []], 422);
            return;
        }

        $randevuId = (int) $data['id'];
        $eskiRandevu = Randevu::idIleBul($randevuId) ?: [];
        $basarili = Randevu::guncelle($randevuId, [
            'tarih' => trim((string) $data['tarih']),
            'baslangic_saati' => trim((string) $data['baslangic_saati']),
            'sure_dakika' => (int) $data['sure_dakika'],
            'tur' => trim((string) $data['tur']),
            'hak_kaynagi' => trim((string) $data['hak_kaynagi']),
            'durum' => trim((string) $data['durum']),
            'aciklama' => trim((string) ($data['aciklama'] ?? '')),
            'isleyen_kullanici_id' => (int) (Auth::user()['id'] ?? 0),
        ]);

        if ($basarili && (string) ($data['randevu_sms_gonder'] ?? '') === '1') {
            try {
                $smsServisi = new SmsServisi();
                $smsServisi->randevuGuncellendi($randevuId, $eskiRandevu);
                $smsServisi->kuyrukIsle(20);
            } catch (\Throwable $e) {
                error_log('Randevu guncelleme SMS kuyrugu olusturulamadi veya islenemedi: ' . $e->getMessage());
            }
        }

        Response::json([
            'basari' => $basarili,
            'mesaj' => $basarili ? 'Randevu guncellendi.' : 'Randevu bulunamadi.',
            'veri' => ['id' => $randevuId],
        ], $basarili ? 200 : 404);
    }

    public function durumDegistir(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $ids = $this->idler($data);
        $durum = trim((string) ($data['durum'] ?? ''));
        if ($ids === [] || !Randevu::durumGecerliMi($durum)) {
            Response::json(['basari' => false, 'mesaj' => 'Randevu ve durum secilmelidir.', 'hatalar' => []], 422);
            return;
        }

        $adet = Randevu::durumDegistir($ids, $durum, (int) (Auth::user()['id'] ?? 0));
        Response::json(['basari' => true, 'mesaj' => $adet . ' randevunun durumu guncellendi.', 'veri' => ['adet' => $adet]]);
    }

    public function topluGuncelle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $ids = $this->idler($data);
        if ($ids === []) {
            Response::json(['basari' => false, 'mesaj' => 'En az bir randevu secilmelidir.', 'hatalar' => []], 422);
            return;
        }

        $adet = Randevu::topluGuncelle($ids, [
            'tarih' => trim((string) ($data['tarih'] ?? '')),
            'baslangic_saati' => trim((string) ($data['baslangic_saati'] ?? '')),
            'sure_dakika' => (int) ($data['sure_dakika'] ?? 45),
            'durum' => trim((string) ($data['durum'] ?? '')),
            'aciklama' => trim((string) ($data['aciklama'] ?? '')),
        ]);

        Response::json(['basari' => true, 'mesaj' => $adet . ' randevu toplu guncellendi.', 'veri' => ['adet' => $adet]]);
    }

    public function sil(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $ids = $this->idler($data);
        if ($ids === []) {
            Response::json(['basari' => false, 'mesaj' => 'Silinecek randevu secilmelidir.', 'hatalar' => []], 422);
            return;
        }

        try {
            $adet = Randevu::sil($ids);
            Response::json(['basari' => true, 'mesaj' => $adet . ' randevu silindi.', 'veri' => ['adet' => $adet]]);
        } catch (\Throwable $e) {
            Response::json(['basari' => false, 'mesaj' => 'Yoklama veya bagli kaydi olan randevular silinemez.', 'hatalar' => []], 409);
        }
    }

    private function idler(array $data): array
    {
        $ids = $data['ids'] ?? ($data['id'] ?? []);
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        return array_values(array_filter(array_map('intval', $ids)));
    }

    private function ogrenciIdleri(array $data): array
    {
        $ids = $data['ogrenci_ids'] ?? [];
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        if (!empty($data['ogrenci_id'])) {
            $ids[] = $data['ogrenci_id'];
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }
}
