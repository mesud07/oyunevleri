<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Hizmet;
use App\Models\Ogrenci;
use App\Models\Paket;
use App\Services\SmsServisi;

final class PaketController extends Controller
{
    public function sayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $this->view('panel/paketler', [
            'baslik' => 'Ogrenciye Paket ve Randevu Tanimla',
            'aktif' => 'paketler',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'ogrenciler' => Ogrenci::secenekler(),
            'secili_ogrenci_id' => max(0, (int) ($_GET['ogrenci_id'] ?? 0)),
            'hizmetler' => Hizmet::aktifListe(),
        ], 'panel');
    }

    public function listeSayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $this->view('panel/paket-listesi', [
            'baslik' => 'Paketler',
            'aktif' => 'paketler',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
        ], 'panel');
    }

    public function liste(): void
    {
        $tanimlar = array_map(static fn(array $hizmet): array => [
            'id' => $hizmet['id'],
            'paket_adi' => $hizmet['hizmet_adi'],
            'ucret' => $hizmet['ucret'],
            'haftalik_katilim_sayisi' => $hizmet['haftalik_katilim_sayisi'],
            'toplam_normal_hak' => $hizmet['toplam_normal_hak'],
            'toplam_telafi_hak' => $hizmet['toplam_telafi_hak'],
            'aktif' => $hizmet['aktif'],
        ], Hizmet::liste());

        Response::json(['basari' => true, 'mesaj' => 'Paket tanimlari listelendi.', 'veri' => $tanimlar]);
    }

    public function ekle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['ogrenci_id', 'hizmet_id', 'baslangic_tarihi']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        $paketTanimi = Hizmet::idIleBul((int) $data['hizmet_id']);
        if (!$paketTanimi || (int) $paketTanimi['aktif'] !== 1) {
            Response::json(['basari' => false, 'mesaj' => 'Gecerli bir paket tanimi secmelisiniz.', 'hatalar' => ['hizmet_id' => 'Gecersiz paket tanimi.']], 422);
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

        $baslangicTarihi = $this->tarihDegeri((string) $data['baslangic_tarihi']);
        if ($baslangicTarihi === null) {
            Response::json([
                'basari' => false,
                'mesaj' => 'Gecerli bir baslangic tarihi secmelisiniz.',
                'hatalar' => ['baslangic_tarihi' => 'Gecersiz tarih.'],
            ], 422);
            return;
        }

        $programGunleri = $this->programGunleriniOku($data);
        $normalHak = (int) $paketTanimi['toplam_normal_hak'];
        $tanismaIlkDersSayilsin = (string) ($data['tanisma_dersi_ilk_ders_sayilsin'] ?? '') === '1';
        $olusturulacakRandevuSayisi = max(0, $normalHak - ($tanismaIlkDersSayilsin ? 1 : 0));
        if ($olusturulacakRandevuSayisi > 0 && $programGunleri === []) {
            Response::json([
                'basari' => false,
                'mesaj' => 'Randevu olusturmak icin en az bir gun secmelisiniz.',
                'hatalar' => ['program_gunleri' => 'En az bir gun secilmelidir.'],
            ], 422);
            return;
        }

        $id = Paket::ekle([
            'ogrenci_id' => (int) $data['ogrenci_id'],
            'paket_adi' => (string) $paketTanimi['hizmet_adi'],
            'haftalik_katilim_sayisi' => (int) $paketTanimi['haftalik_katilim_sayisi'],
            'toplam_normal_hak' => $normalHak,
            'toplam_telafi_hak' => (int) $paketTanimi['toplam_telafi_hak'],
            'baslangic_tarihi' => $baslangicTarihi,
            'liste_fiyati' => (float) $paketTanimi['ucret'],
            'indirim_turu' => trim((string) ($data['indirim_turu'] ?? '')),
            'indirim_tutari' => (float) ($data['indirim_tutari'] ?? 0),
            'indirim_aciklama' => trim((string) ($data['indirim_aciklama'] ?? '')),
            'yonetici_notu' => trim((string) ($data['yonetici_notu'] ?? '')),
            'tanisma_dersi_ilk_ders_sayilsin' => $tanismaIlkDersSayilsin,
            'olusturan_kullanici_id' => $kullaniciId,
            'program_gunleri' => $programGunleri,
            'program_saatleri' => $data,
        ]);

        if ((string) ($data['randevu_sms_gonder'] ?? '') === '1') {
            try {
                $smsServisi = new SmsServisi();
                $smsServisi->paketRandevulariOlusturuldu($id);
                $smsServisi->kuyrukIsle(100);
            } catch (\Throwable $e) {
                error_log('Paket randevu SMS kuyrugu olusturulamadi veya islenemedi: ' . $e->getMessage());
            }
        }

        Response::json(['basari' => true, 'mesaj' => 'Paket kaydi olusturuldu.', 'veri' => ['id' => $id]], 201);
    }

    public function hizliRandevu(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, [
            'ogrenci_ad_soyad',
            'dogum_tarihi',
            'veli_ad_soyad',
            'veli_telefon',
            'hizmet_id',
            'randevu_tarihi',
            'randevu_gunu',
            'randevu_saati',
        ]);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        $hizmet = Hizmet::idIleBul((int) $data['hizmet_id']);
        if (!$hizmet || (int) $hizmet['aktif'] !== 1) {
            Response::json([
                'basari' => false,
                'mesaj' => 'Gecerli bir paket secilmelidir.',
                'hatalar' => ['hizmet_id' => 'Gecersiz paket.'],
            ], 422);
            return;
        }

        [$ogrenciAd, $ogrenciSoyad] = $this->adSoyadAyir((string) $data['ogrenci_ad_soyad']);
        [$veliAd, $veliSoyad] = $this->adSoyadAyir((string) $data['veli_ad_soyad']);
        if ($ogrenciAd === '' || $ogrenciSoyad === '' || $veliAd === '' || $veliSoyad === '') {
            Response::json(['basari' => false, 'mesaj' => 'Ad soyad alanlarini ad ve soyad olacak sekilde girin.', 'hatalar' => []], 422);
            return;
        }

        $randevuTarihi = trim((string) $data['randevu_tarihi']);
        $randevuSaati = $this->saatDegeri((string) $data['randevu_saati']);
        if (!$this->tarihGecerliMi($randevuTarihi) || !$randevuSaati) {
            Response::json(['basari' => false, 'mesaj' => 'Gecerli randevu tarihi ve saati secilmelidir.', 'hatalar' => []], 422);
            return;
        }
        $randevuGunu = (int) $data['randevu_gunu'];
        if ($randevuGunu < 1 || $randevuGunu > 7) {
            Response::json(['basari' => false, 'mesaj' => 'Gecerli bir randevu gunu secilmelidir.', 'hatalar' => ['randevu_gunu' => 'Gecersiz gun.']], 422);
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

        $ogrenciId = Ogrenci::veliIleEkle([
            'ogrenci' => [
                'ad' => $ogrenciAd,
                'soyad' => $ogrenciSoyad,
                'tc_kimlik_no' => '',
                'dogum_tarihi' => trim((string) $data['dogum_tarihi']),
                'cinsiyet' => 'belirtilmedi',
                'kayit_tarihi' => date('Y-m-d'),
                'acil_durum_kisi' => $veliAd . ' ' . $veliSoyad,
                'acil_durum_telefon' => trim((string) $data['veli_telefon']),
                'saglik_bilgisi' => '',
                'alerji_bilgisi' => '',
                'ozel_durum_notu' => '',
                'vasi_ad_soyad' => '',
                'vasi_tc_kimlik_no' => '',
                'vasi_telefon' => '',
                'yonetici_notu' => 'Hizli randevu modalindan olusturuldu.',
                'ogretmen_notu' => '',
            ],
            'veli' => [
                'ad' => $veliAd,
                'soyad' => $veliSoyad,
                'tc_kimlik_no' => '',
                'telefon_ulke' => 'Turkiye',
                'telefon' => trim((string) $data['veli_telefon']),
                'yedek_telefon' => '',
                'eposta' => '',
                'yakinlik' => 'Veli',
                'il' => '',
                'ilce' => '',
                'adres' => '',
                'notlar' => 'Hizli randevu modalindan olusturuldu.',
            ],
        ]);

        $paketId = Paket::ekle([
            'ogrenci_id' => $ogrenciId,
            'paket_adi' => (string) $hizmet['hizmet_adi'],
            'haftalik_katilim_sayisi' => max(1, (int) $hizmet['haftalik_katilim_sayisi']),
            'toplam_normal_hak' => max(1, (int) $hizmet['toplam_normal_hak']),
            'toplam_telafi_hak' => max(0, (int) $hizmet['toplam_telafi_hak']),
            'baslangic_tarihi' => $randevuTarihi,
            'liste_fiyati' => (float) $hizmet['ucret'],
            'indirim_turu' => '',
            'indirim_tutari' => 0,
            'indirim_aciklama' => '',
            'yonetici_notu' => 'Hizli randevu modalindan olusturuldu.',
            'olusturan_kullanici_id' => $kullaniciId,
            'program_gunleri' => [$randevuGunu],
            'program_saatleri' => ['program_saat_' . $randevuGunu => $randevuSaati],
        ]);

        if ((string) ($data['randevu_sms_gonder'] ?? '') === '1') {
            try {
                $smsServisi = new SmsServisi();
                $smsServisi->paketRandevulariOlusturuldu($paketId);
                $smsServisi->kuyrukIsle(20);
            } catch (\Throwable $e) {
                error_log('Hizli randevu SMS kuyrugu olusturulamadi veya islenemedi: ' . $e->getMessage());
            }
        }

        Response::json([
            'basari' => true,
            'mesaj' => 'Hizli randevu olusturuldu.',
            'veri' => ['ogrenci_id' => $ogrenciId, 'paket_id' => $paketId],
        ], 201);
    }

    public function sil(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        if ($id < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Silinecek paket secilmelidir.', 'hatalar' => []], 422);
            return;
        }

        try {
            $basarili = Paket::sil($id);
            Response::json([
                'basari' => $basarili,
                'mesaj' => $basarili ? 'Paket ve bagli randevular silindi.' : 'Paket bulunamadi.',
                'veri' => ['id' => $id],
            ], $basarili ? 200 : 404);
        } catch (\Throwable $e) {
            Response::json(['basari' => false, 'mesaj' => 'Paket silinemedi. Bagli kayitlar kontrol edilmeli.', 'hatalar' => []], 409);
        }
    }

    private function adSoyadAyir(string $adSoyad): array
    {
        $parcalar = preg_split('/\s+/', trim($adSoyad)) ?: [];
        $parcalar = array_values(array_filter($parcalar, static fn(string $parca): bool => $parca !== ''));
        if (count($parcalar) < 2) {
            return [$parcalar[0] ?? '', ''];
        }

        $soyad = array_pop($parcalar);
        return [implode(' ', $parcalar), $soyad];
    }

    private function programGunleriniOku(array $data): array
    {
        $gunler = $data['program_gunleri'] ?? ($data['program_gunleri[]'] ?? []);
        if (!is_array($gunler)) {
            $gunler = [$gunler];
        }

        $gunler = array_values(array_unique(array_filter(array_map(
            static fn($gun): int => (int) $gun,
            $gunler
        ), static fn(int $gun): bool => $gun >= 1 && $gun <= 7)));
        sort($gunler);

        return $gunler;
    }

    private function tarihDegeri(string $tarih): ?string
    {
        $tarih = trim($tarih);
        if ($tarih === '') {
            return null;
        }

        foreach (['Y-m-d', 'd.m.Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $tarih);
            $hatalar = \DateTimeImmutable::getLastErrors();
            $hataYok = $hatalar === false || ((int) $hatalar['warning_count'] === 0 && (int) $hatalar['error_count'] === 0);
            if ($date instanceof \DateTimeImmutable && $hataYok && $date->format($format) === $tarih) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function tarihGecerliMi(string $tarih): bool
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $tarih);
        return $date instanceof \DateTimeImmutable && $date->format('Y-m-d') === $tarih;
    }

    private function saatDegeri(string $saat): string
    {
        $saat = substr(trim($saat), 0, 5);
        return preg_match('/^\d{2}:\d{2}$/', $saat) ? $saat : '';
    }
}
