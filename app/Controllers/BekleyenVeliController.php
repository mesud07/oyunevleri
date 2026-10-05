<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Validator;
use App\Models\BekleyenVeli;
use App\Models\Grup;

final class BekleyenVeliController extends Controller
{
    public function sayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $gruplar = array_values(array_filter(
            Grup::secenekler(),
            static fn(array $grup): bool => (int) ($grup['aktif'] ?? 0) === 1
        ));

        $this->view('panel/bekleyen-veliler', [
            'baslik' => 'Bekleyen Veliler',
            'aktif' => 'bekleyen-veliler',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'gruplar' => $gruplar,
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

        $grupIdleri = $data['grup_ids'] ?? [];
        if (is_array($grupIdleri) && $grupIdleri !== []) {
            BekleyenVeli::gruplariGuncelle($id, $grupIdleri, (int) (Auth::user()['id'] ?? 0));
        }

        Response::json(['basari' => true, 'mesaj' => 'Bekleyen veli kaydi olusturuldu.', 'veri' => ['id' => $id]], 201);
    }

    public function ogrencidenEkle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $ogrenciId = (int) ($data['ogrenci_id'] ?? 0);
        if ($ogrenciId < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Bekleme listesine eklenecek öğrenci seçilmelidir.', 'hatalar' => []], 422);
            return;
        }

        try {
            $sonuc = BekleyenVeli::ogrencidenEkle($ogrenciId, (int) (Auth::user()['id'] ?? 0));
        } catch (\DomainException $e) {
            Response::json(['basari' => false, 'mesaj' => $e->getMessage(), 'hatalar' => []], 422);
            return;
        }

        Response::json([
            'basari' => true,
            'mesaj' => $sonuc['yeni']
                ? 'Ayrılmış öğrenci bekleyen veli listesine eklendi.'
                : 'Bu öğrenci zaten bekleyen veli listesinde.',
            'veri' => $sonuc,
        ], $sonuc['yeni'] ? 201 : 200);
    }

    public function guncelle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        $hatalar = Validator::gerekli($data, ['ogrenci_ad_soyad', 'veli_ad_soyad', 'veli_telefon']);
        if ($id < 1) $hatalar['id'] = 'Düzenlenecek kayıt seçilmelidir.';

        $dogumTarihi = trim((string) ($data['ogrenci_dogum_tarihi'] ?? ''));
        if ($dogumTarihi !== '') {
            $tarih = \DateTimeImmutable::createFromFormat('!Y-m-d', $dogumTarihi);
            if (!$tarih || $tarih->format('Y-m-d') !== $dogumTarihi || $tarih > new \DateTimeImmutable('today')) {
                $hatalar['ogrenci_dogum_tarihi'] = 'Doğum tarihi geçersiz.';
            }
        }
        $zamanTercihi = trim((string) ($data['zaman_tercihi'] ?? 'farketmez'));
        if (!in_array($zamanTercihi, ['hafta_ici', 'hafta_sonu', 'farketmez'], true)) {
            $hatalar['zaman_tercihi'] = 'Zaman tercihi geçersiz.';
        }
        $notlar = trim((string) ($data['notlar'] ?? ''));
        if (mb_strlen($notlar) > 500) $hatalar['notlar'] = 'Not en fazla 500 karakter olabilir.';
        $eposta = trim((string) ($data['veli_eposta'] ?? ''));
        if ($eposta !== '' && !filter_var($eposta, FILTER_VALIDATE_EMAIL)) $hatalar['veli_eposta'] = 'E-posta adresi geçersiz.';

        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Bilgileri kontrol edin.', 'hatalar' => $hatalar], 422);
            return;
        }

        $kayit = BekleyenVeli::bul($id);
        if (!$kayit || in_array((string) $kayit['durum'], ['kayda_donustu', 'kayit_oldu', 'iptal', 'vazgecti', 'ulasilamadi', 'katilmadi', 'yas_uygun_degil', 'saatler_uymadi', 'diger'], true)) {
            Response::json(['basari' => false, 'mesaj' => 'Bu kayıt artık bekleme listesinde düzenlenemez.', 'hatalar' => []], 409);
            return;
        }

        $basarili = BekleyenVeli::guncelle($id, [
            'ogrenci_ad_soyad' => trim((string) $data['ogrenci_ad_soyad']),
            'ogrenci_dogum_tarihi' => $dogumTarihi,
            'veli_ad_soyad' => trim((string) $data['veli_ad_soyad']),
            'veli_telefon' => $this->telefonFormatla((string) $data['veli_telefon']),
            'veli_eposta' => $eposta,
            'ay_grubu' => trim((string) ($data['ay_grubu'] ?? '')),
            'zaman_tercihi' => $zamanTercihi,
            'notlar' => $notlar,
        ], (int) (Auth::user()['id'] ?? 0));
        if (!$basarili) {
            Response::json(['basari' => false, 'mesaj' => 'Bekleyen veli kaydı güncellenemedi.', 'hatalar' => []], 404);
            return;
        }

        $grupIdleri = $data['grup_ids'] ?? [];
        BekleyenVeli::gruplariGuncelle($id, is_array($grupIdleri) ? $grupIdleri : [], (int) (Auth::user()['id'] ?? 0));
        Response::json(['basari' => true, 'mesaj' => 'Bekleyen veli ve öğrenci bilgileri güncellendi.', 'veri' => ['id' => $id]]);
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

        if (!BekleyenVeli::durumGuncelle($id, $durum, (int) (Auth::user()['id'] ?? 0))) {
            Response::json(['basari' => false, 'mesaj' => 'Bekleyen veli kaydi bulunamadi veya durum gecersiz.', 'hatalar' => []], 404);
            return;
        }

        Response::json(['basari' => true, 'mesaj' => 'Bekleyen veli durumu guncellendi.', 'veri' => ['id' => $id]]);
    }

    public function gorusmeler(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        $veli = $id > 0 ? BekleyenVeli::bul($id) : null;
        if (!$veli) {
            Response::json(['basari' => false, 'mesaj' => 'Bekleyen veli kaydı bulunamadı.', 'hatalar' => []], 404);
            return;
        }
        Response::json([
            'basari' => true,
            'mesaj' => 'Görüşme tarihçesi listelendi.',
            'veri' => [
                'veli' => $veli,
                'gorusmeler' => BekleyenVeli::gorusmeler($id),
                'gruplar' => BekleyenVeli::grupSecenekleri($id),
            ],
        ]);
    }

    public function gorusmeEkle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['bekleyen_veli_id'] ?? 0);
        $ozet = trim((string) ($data['ozet'] ?? ''));
        $kanal = trim((string) ($data['kanal'] ?? 'telefon'));
        $sonuc = trim((string) ($data['sonuc'] ?? 'bilgi_verildi'));
        $gorusmeTarihi = trim((string) ($data['gorusme_tarihi'] ?? ''));
        $takipTarihi = trim((string) ($data['next_follow_up_at'] ?? ($data['sonraki_takip_tarihi'] ?? '')));
        if ($takipTarihi !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $takipTarihi)) $takipTarihi .= 'T09:00';
        $aksiyon = trim((string) ($data['next_action_type'] ?? ''));
        $aksiyonNotu = trim((string) ($data['next_action_note'] ?? ''));
        $hatalar = [];
        if ($id < 1) $hatalar['bekleyen_veli_id'] = 'Veli seçilmelidir.';
        if ($ozet === '') $hatalar['ozet'] = 'Görüşme özeti zorunludur.';
        if (mb_strlen($ozet) > 2000) $hatalar['ozet'] = 'Görüşme özeti en fazla 2000 karakter olabilir.';
        if (!in_array($kanal, ['telefon', 'whatsapp', 'yuz_yuze', 'sms', 'diger'], true)) $hatalar['kanal'] = 'Görüşme kanalı geçersiz.';
        if (!in_array($sonuc, ['goruldu', 'bilgi_verildi', 'veli_donecek', 'tekrar_aranacak', 'kayit_istiyor', 'uygun_grup_yok', 'randevu_planlandi', 'kararsiz', 'ulasilamadi', 'katilmadi', 'olumsuz', 'diger'], true)) $hatalar['sonuc'] = 'Görüşme sonucu geçersiz.';
        $tarih = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $gorusmeTarihi);
        if (!$tarih || $tarih->format('Y-m-d\TH:i') !== $gorusmeTarihi) $hatalar['gorusme_tarihi'] = 'Görüşme tarihi geçersiz.';
        $takip = null;
        if ($takipTarihi !== '') {
            $takip = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $takipTarihi);
            if (!$takip || $takip->format('Y-m-d\TH:i') !== $takipTarihi) $hatalar['next_follow_up_at'] = 'Takip tarihi geçersiz.';
            if (!in_array($aksiyon, BekleyenVeli::aksiyonTipleri(), true)) $hatalar['next_action_type'] = 'Sonraki aksiyon seçilmelidir.';
        }
        if (mb_strlen($aksiyonNotu) > 500) $hatalar['next_action_note'] = 'Aksiyon notu en fazla 500 karakter olabilir.';
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Görüşme bilgilerini kontrol edin.', 'hatalar' => $hatalar], 422);
            return;
        }
        $gorusmeId = BekleyenVeli::gorusmeEkle($id, [
            'gorusme_tarihi' => $tarih->format('Y-m-d H:i:s'),
            'kanal' => $kanal,
            'ozet' => $ozet,
            'sonuc' => $sonuc,
            'sonraki_takip_tarihi' => $takip?->format('Y-m-d') ?? '',
            'next_follow_up_at' => $takip?->format('Y-m-d H:i:s') ?? '',
            'next_action_type' => $takip ? $aksiyon : '',
            'next_action_note' => $aksiyonNotu,
            'olusturan_kullanici_id' => (int) (Auth::user()['id'] ?? 0),
        ]);
        if ($gorusmeId < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Bekleyen veli kaydı bulunamadı.', 'hatalar' => []], 404);
            return;
        }
        Response::json(['basari' => true, 'mesaj' => 'Görüşme tarihçeye eklendi.', 'veri' => ['id' => $gorusmeId]]);
    }

    public function takipGuncelle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        $takipMetni = trim((string) ($data['next_follow_up_at'] ?? ''));
        $aksiyon = trim((string) ($data['next_action_type'] ?? ''));
        $not = trim((string) ($data['next_action_note'] ?? ''));
        $takip = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $takipMetni);
        $hatalar = [];
        if ($id < 1) $hatalar['id'] = 'Bekleyen veli seçilmelidir.';
        if (!$takip || $takip->format('Y-m-d\TH:i') !== $takipMetni) $hatalar['next_follow_up_at'] = 'Takip tarihi geçersiz.';
        if (!in_array($aksiyon, BekleyenVeli::aksiyonTipleri(), true)) $hatalar['next_action_type'] = 'Aksiyon tipi geçersiz.';
        if (mb_strlen($not) > 500) $hatalar['next_action_note'] = 'Aksiyon notu en fazla 500 karakter olabilir.';
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Takip bilgilerini kontrol edin.', 'hatalar' => $hatalar], 422);
            return;
        }
        if (!BekleyenVeli::takipGuncelle($id, [
            'next_follow_up_at' => $takip->format('Y-m-d H:i:s'),
            'next_action_type' => $aksiyon,
            'next_action_note' => $not,
        ], (int) (Auth::user()['id'] ?? 0))) {
            Response::json(['basari' => false, 'mesaj' => 'Takip bilgisi kaydedilemedi.', 'hatalar' => []], 404);
            return;
        }
        Response::json(['basari' => true, 'mesaj' => 'Sonraki aksiyon planlandı.', 'veri' => ['id' => $id]]);
    }

    public function gruplariGuncelle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['bekleyen_veli_id'] ?? 0);
        $grupIdleri = $data['grup_ids'] ?? [];
        if (!is_array($grupIdleri)) $grupIdleri = [];
        if ($id < 1 || !BekleyenVeli::gruplariGuncelle($id, $grupIdleri, (int) (Auth::user()['id'] ?? 0))) {
            Response::json(['basari' => false, 'mesaj' => 'Bekleyen veli kaydı bulunamadı.', 'hatalar' => []], 404);
            return;
        }
        Response::json(['basari' => true, 'mesaj' => 'Beklenen gruplar güncellendi.', 'veri' => ['id' => $id]]);
    }

    public function ogrenciyeDonustur(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        if ($id < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Aktarilacak kayit secilmelidir.', 'hatalar' => []], 422);
            return;
        }

        $ogrenciId = BekleyenVeli::ogrenciyeDonustur($id, (int) (Auth::user()['id'] ?? 0));
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
