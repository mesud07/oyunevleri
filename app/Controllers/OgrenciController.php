<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Kasa;
use App\Models\Ogrenci;
use App\Models\OgrenciKaraListe;
use App\Models\OnamFormu;
use App\Models\Veli;

final class OgrenciController extends Controller
{
    private function adSoyadAyir(string $adSoyad): array
    {
        $parcalar = preg_split('/\s+/', trim($adSoyad)) ?: [];
        $parcalar = array_values(array_filter($parcalar));

        if (count($parcalar) <= 1) {
            return [$parcalar[0] ?? '', ''];
        }

        $soyad = array_pop($parcalar);
        return [implode(' ', $parcalar), $soyad];
    }

    private function telefonFormatla(string $telefon): string
    {
        $rakamlar = preg_replace('/\D+/', '', $telefon) ?? '';
        if ($rakamlar === '') {
            return '';
        }

        if (str_starts_with($rakamlar, '0')) {
            $rakamlar = substr($rakamlar, 1);
        }

        $rakamlar = substr($rakamlar, 0, 10);
        if ($rakamlar === '') {
            return '0';
        }

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

    public function sayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $this->view('panel/ogrenciler', [
            'baslik' => 'Ogrenciler',
            'aktif' => 'ogrenciler',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
        ], 'panel');
    }

    public function yeniKayit(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $this->view('panel/ogrenci-yeni', [
            'baslik' => 'Yeni Ogrenci Kaydi',
            'aktif' => 'ogrenciler',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
        ], 'panel');
    }

    public function profil(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $id = (int) ($_GET['id'] ?? 0);
        $profil = Ogrenci::profil($id);
        if (!$profil) {
            Response::redirect('/panel/ogrenciler');
        }

        $this->view('panel/ogrenci-profil', [
            'baslik' => 'Ogrenci Profili',
            'aktif' => 'ogrenciler',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'profil' => $profil,
            'kasalar' => Kasa::secenekler(),
            'onamFormlari' => OnamFormu::ogrenciListesi($id),
        ], 'panel');
    }

    public function temaEtkinlikleri(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $id = (int) ($_GET['id'] ?? 0);
        $profil = Ogrenci::profil($id);
        if (!$profil) {
            Response::redirect('/panel/ogrenciler');
        }

        $this->view('panel/ogrenci-tema-etkinlikleri', [
            'baslik' => 'Ogrenci Tema ve Etkinlikleri',
            'aktif' => 'ogrenciler',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'profil' => $profil,
        ], 'panel');
    }

    public function karaListeSayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $this->view('panel/ogrenci-kara-liste', [
            'baslik' => 'Ogrenci Kara Liste',
            'aktif' => 'ogrenci-kara-liste',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'kayitlar' => OgrenciKaraListe::liste(),
            'kategoriler' => OgrenciKaraListe::KATEGORILER,
            'tablolarHazir' => OgrenciKaraListe::tabloVarMi(),
        ], 'panel');
    }

    public function liste(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $sayfa = max(1, (int) ($data['sayfa'] ?? 1));
        $limit = max(10, min(100, (int) ($data['limit'] ?? 20)));
        Response::json([
            'basari' => true,
            'mesaj' => 'Ogrenciler listelendi.',
            'veri' => Ogrenci::liste(trim((string) ($data['arama'] ?? '')), $sayfa, $limit),
        ]);
    }

    public function telefonKontrol(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $telefon = trim((string) ($data['telefon'] ?? ''));
        Response::json([
            'basari' => true,
            'mesaj' => 'Telefon kontrol edildi.',
            'veri' => Ogrenci::telefonEslesmeleri($telefon),
        ]);
    }

    public function karaListeEkle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $ogrenciId = (int) ($data['ogrenci_id'] ?? 0);
        $kategori = trim((string) ($data['kategori'] ?? ''));
        $sebep = trim((string) ($data['sebep'] ?? ''));

        $hatalar = [];
        if ($ogrenciId < 1 || !Ogrenci::profil($ogrenciId)) {
            $hatalar['ogrenci_id'] = 'Ogrenci bulunamadi.';
        }
        if (!isset(OgrenciKaraListe::KATEGORILER[$kategori])) {
            $hatalar['kategori'] = 'Kategori secilmelidir.';
        }
        if ($sebep === '') {
            $hatalar['sebep'] = 'Sebep yazilmalidir.';
        }
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik veya hatali alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        $id = OgrenciKaraListe::ekle([
            'ogrenci_id' => $ogrenciId,
            'kategori' => $kategori,
            'sebep' => $sebep,
            'olusturan_kullanici_id' => (int) (Auth::user()['id'] ?? 0),
        ]);

        Response::json(['basari' => true, 'mesaj' => 'Ogrenci kara listeye eklendi.', 'veri' => ['id' => $id]], 201);
    }

    public function karaListeKaldir(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        if ($id < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Kara liste kaydi gecersiz.', 'hatalar' => []], 422);
            return;
        }

        if (!OgrenciKaraListe::pasifeAl($id)) {
            Response::json(['basari' => false, 'mesaj' => 'Kara liste kaydi bulunamadi.', 'hatalar' => []], 404);
            return;
        }

        Response::json(['basari' => true, 'mesaj' => 'Kara liste kaydi kaldirildi.', 'veri' => ['id' => $id]]);
    }

    public function sil(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        if ($id < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Ogrenci secimi gecersiz.', 'hatalar' => []], 422);
            return;
        }

        if (!Ogrenci::sil($id)) {
            Response::json(['basari' => false, 'mesaj' => 'Ogrenci bulunamadi.', 'hatalar' => []], 404);
            return;
        }

        Response::json(['basari' => true, 'mesaj' => 'Ogrenci kaydi silindi.', 'veri' => ['id' => $id]]);
    }

    public function ekle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['ad', 'soyad']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        $id = Ogrenci::ekle([
            'ad' => trim((string) $data['ad']),
            'soyad' => trim((string) $data['soyad']),
            'dogum_tarihi' => trim((string) ($data['dogum_tarihi'] ?? '')),
            'cinsiyet' => trim((string) ($data['cinsiyet'] ?? 'belirtilmedi')),
            'kayit_tarihi' => trim((string) ($data['kayit_tarihi'] ?? date('Y-m-d'))),
            'durum' => trim((string) ($data['durum'] ?? 'aktif')),
            'veli_id' => (int) ($data['veli_id'] ?? 0),
            'acil_durum_kisi' => trim((string) ($data['acil_durum_kisi'] ?? '')),
            'acil_durum_telefon' => trim((string) ($data['acil_durum_telefon'] ?? '')),
            'saglik_bilgisi' => trim((string) ($data['saglik_bilgisi'] ?? '')),
            'alerji_bilgisi' => trim((string) ($data['alerji_bilgisi'] ?? '')),
            'ozel_durum_notu' => trim((string) ($data['ozel_durum_notu'] ?? '')),
            'yonetici_notu' => trim((string) ($data['yonetici_notu'] ?? '')),
            'ogretmen_notu' => trim((string) ($data['ogretmen_notu'] ?? '')),
        ]);

        Response::json(['basari' => true, 'mesaj' => 'Ogrenci kaydi olusturuldu.', 'veri' => ['id' => $id]], 201);
    }

    public function veliIleEkle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['ogrenci_ad_soyad', 'veli_ad_soyad', 'veli_telefon']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Yildizli alanlari doldurun.', 'hatalar' => $hatalar], 422);
            return;
        }

        [$ogrenciAd, $ogrenciSoyad] = $this->adSoyadAyir((string) $data['ogrenci_ad_soyad']);
        [$veliAd, $veliSoyad] = $this->adSoyadAyir((string) $data['veli_ad_soyad']);

        if (!$ogrenciAd || !$ogrenciSoyad || !$veliAd || !$veliSoyad) {
            Response::json(['basari' => false, 'mesaj' => 'Ad soyad alanlarinda ad ve soyad birlikte yazilmalidir.', 'hatalar' => []], 422);
            return;
        }

        $telefon = $this->telefonFormatla((string) $data['veli_telefon']);
        $kontrolTelefonlari = [
            'veli_telefon' => $telefon,
            'veli_yedek_telefon' => $this->telefonFormatla((string) ($data['veli_yedek_telefon'] ?? '')),
            'vasi_telefon' => $this->telefonFormatla((string) ($data['vasi_telefon'] ?? '')),
            'acil_durum_telefon' => $this->telefonFormatla((string) ($data['acil_durum_telefon'] ?? '')),
        ];
        $eslesmeler = [];
        foreach ($kontrolTelefonlari as $kontrolTelefon) {
            if ($kontrolTelefon === '') {
                continue;
            }
            foreach (Ogrenci::telefonEslesmeleri($kontrolTelefon) as $eslesme) {
                $eslesmeler[(int) $eslesme['id']] = $eslesme;
            }
        }
        if ($eslesmeler) {
            Response::json([
                'basari' => false,
                'mesaj' => 'Bu telefon numarasina ait ogrenci kaydi zaten var.',
                'hatalar' => ['veli_telefon' => 'Bu numara kayitli. Mevcut ogrenci profilinden devam edin.'],
                'veri' => ['eslesmeler' => array_values($eslesmeler)],
            ], 409);
            return;
        }

        $id = Ogrenci::veliIleEkle([
            'ogrenci' => [
                'ad' => $ogrenciAd,
                'soyad' => $ogrenciSoyad,
                'tc_kimlik_no' => trim((string) ($data['ogrenci_tc_kimlik_no'] ?? '')),
                'dogum_tarihi' => trim((string) ($data['ogrenci_dogum_tarihi'] ?? '')),
                'cinsiyet' => trim((string) ($data['ogrenci_cinsiyet'] ?? 'belirtilmedi')),
                'kayit_tarihi' => date('Y-m-d'),
                'acil_durum_kisi' => trim((string) ($data['acil_durum_kisi'] ?? '')),
                'acil_durum_telefon' => trim((string) ($data['acil_durum_telefon'] ?? '')),
                'saglik_bilgisi' => trim((string) ($data['saglik_bilgisi'] ?? '')),
                'alerji_bilgisi' => trim((string) ($data['alerji_bilgisi'] ?? '')),
                'ozel_durum_notu' => trim((string) ($data['ogrenci_aciklama'] ?? '')),
                'vasi_ad_soyad' => trim((string) ($data['vasi_ad_soyad'] ?? '')),
                'vasi_tc_kimlik_no' => trim((string) ($data['vasi_tc_kimlik_no'] ?? '')),
                'vasi_telefon' => $this->telefonFormatla((string) ($data['vasi_telefon'] ?? '')),
                'yonetici_notu' => '',
                'ogretmen_notu' => '',
            ],
            'veli' => [
                'ad' => $veliAd,
                'soyad' => $veliSoyad,
                'tc_kimlik_no' => trim((string) ($data['veli_tc_kimlik_no'] ?? '')),
                'telefon_ulke' => trim((string) ($data['telefon_ulke'] ?? 'Turkiye')),
                'telefon' => $telefon,
                'yedek_telefon' => $this->telefonFormatla((string) ($data['veli_yedek_telefon'] ?? '')),
                'eposta' => trim((string) ($data['veli_eposta'] ?? '')),
                'yakinlik' => trim((string) ($data['veli_yakinlik'] ?? '')),
                'il' => trim((string) ($data['il'] ?? '')),
                'ilce' => trim((string) ($data['ilce'] ?? '')),
                'adres' => trim((string) ($data['adres'] ?? '')),
                'iletisim_referansi' => trim((string) ($data['veli_iletisim_referansi'] ?? '')),
                'notlar' => trim((string) ($data['veli_aciklama'] ?? '')),
            ],
        ]);

        Response::json(['basari' => true, 'mesaj' => 'Ogrenci ve veli kaydi olusturuldu.', 'veri' => ['id' => $id]], 201);
    }

    public function profilGuncelle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['id', 'ogrenci_ad', 'ogrenci_soyad']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Ogrenci ad soyad zorunludur.', 'hatalar' => $hatalar], 422);
            return;
        }

        $id = (int) $data['id'];
        if ($id < 1 || !Ogrenci::profil($id)) {
            Response::json(['basari' => false, 'mesaj' => 'Ogrenci bulunamadi.', 'hatalar' => []], 404);
            return;
        }

        Ogrenci::profilGuncelle($id, [
            'ogrenci' => [
                'ad' => trim((string) $data['ogrenci_ad']),
                'soyad' => trim((string) $data['ogrenci_soyad']),
                'tc_kimlik_no' => trim((string) ($data['ogrenci_tc_kimlik_no'] ?? '')),
                'dogum_tarihi' => trim((string) ($data['ogrenci_dogum_tarihi'] ?? '')),
                'cinsiyet' => trim((string) ($data['ogrenci_cinsiyet'] ?? 'belirtilmedi')),
                'kayit_tarihi' => trim((string) ($data['ogrenci_kayit_tarihi'] ?? date('Y-m-d'))),
                'durum' => trim((string) ($data['ogrenci_durum'] ?? 'aktif')),
                'acil_durum_kisi' => trim((string) ($data['acil_durum_kisi'] ?? '')),
                'acil_durum_telefon' => $this->telefonFormatla((string) ($data['acil_durum_telefon'] ?? '')),
                'saglik_bilgisi' => trim((string) ($data['saglik_bilgisi'] ?? '')),
                'alerji_bilgisi' => trim((string) ($data['alerji_bilgisi'] ?? '')),
                'ozel_durum_notu' => trim((string) ($data['ozel_durum_notu'] ?? '')),
                'vasi_ad_soyad' => trim((string) ($data['vasi_ad_soyad'] ?? '')),
                'vasi_tc_kimlik_no' => trim((string) ($data['vasi_tc_kimlik_no'] ?? '')),
                'vasi_telefon' => $this->telefonFormatla((string) ($data['vasi_telefon'] ?? '')),
                'yonetici_notu' => trim((string) ($data['yonetici_notu'] ?? '')),
                'ogretmen_notu' => trim((string) ($data['ogretmen_notu'] ?? '')),
            ],
            'veli' => [
                'id' => (int) ($data['veli_id'] ?? 0),
                'ad' => trim((string) ($data['veli_ad'] ?? '')),
                'soyad' => trim((string) ($data['veli_soyad'] ?? '')),
                'tc_kimlik_no' => trim((string) ($data['veli_tc_kimlik_no'] ?? '')),
                'telefon_ulke' => trim((string) ($data['veli_telefon_ulke'] ?? 'Turkiye')),
                'telefon' => $this->telefonFormatla((string) ($data['veli_telefon'] ?? '')),
                'yedek_telefon' => $this->telefonFormatla((string) ($data['veli_yedek_telefon'] ?? '')),
                'eposta' => trim((string) ($data['veli_eposta'] ?? '')),
                'yakinlik' => trim((string) ($data['veli_yakinlik'] ?? '')),
                'il' => trim((string) ($data['veli_il'] ?? '')),
                'ilce' => trim((string) ($data['veli_ilce'] ?? '')),
                'adres' => trim((string) ($data['veli_adres'] ?? '')),
                'iletisim_referansi' => trim((string) ($data['veli_iletisim_referansi'] ?? '')),
                'notlar' => trim((string) ($data['veli_notlar'] ?? '')),
            ],
        ]);

        Response::json(['basari' => true, 'mesaj' => 'Bilgiler guncellendi.', 'veri' => ['id' => $id]]);
    }
}
