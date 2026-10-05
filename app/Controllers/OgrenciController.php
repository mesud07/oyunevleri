<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Kasa;
use App\Models\CocukIsletmesi;
use App\Models\Ogrenci;
use App\Models\OgrenciDevamlilikRaporu;
use App\Models\OgrenciAdresHaritasi;
use App\Models\OgrenciKaraListe;
use App\Models\Veli;
use App\Services\OgrenciTopluImportServisi;

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

    public function topluImportSablonu(): void
    {
        $dosya = BASE_PATH . '/public/assets/templates/ogrenci-toplu-import-sablonu.xlsx';
        if (!is_file($dosya) || !is_readable($dosya)) {
            http_response_code(404);
            echo 'Excel şablonu bulunamadı.';
            return;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="ogrenci-toplu-import-sablonu.xlsx"');
        header('Content-Length: ' . filesize($dosya));
        header('X-Content-Type-Options: nosniff');
        readfile($dosya);
    }

    public function topluImport(): void
    {
        if (!Csrf::dogrula((string) ($_POST['csrf'] ?? ''))) {
            Response::json(['basari' => false, 'mesaj' => 'Güvenlik doğrulaması başarısız.', 'hatalar' => []], 419);
            return;
        }

        $dosya = $_FILES['excel_dosyasi'] ?? null;
        if (!is_array($dosya) || (int) ($dosya['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Response::json(['basari' => false, 'mesaj' => 'Aktarılacak Excel dosyasını seçin.', 'hatalar' => []], 422);
            return;
        }
        if ((int) ($dosya['size'] ?? 0) < 1 || (int) $dosya['size'] > 5 * 1024 * 1024) {
            Response::json(['basari' => false, 'mesaj' => 'Excel dosyası en fazla 5 MB olabilir.', 'hatalar' => []], 422);
            return;
        }

        $geciciYol = (string) ($dosya['tmp_name'] ?? '');
        $dosyaAdi = (string) ($dosya['name'] ?? '');
        $imza = is_file($geciciYol) ? file_get_contents($geciciYol, false, null, 0, 4) : false;
        if (strtolower(pathinfo($dosyaAdi, PATHINFO_EXTENSION)) !== 'xlsx' || $imza !== "PK\x03\x04") {
            Response::json(['basari' => false, 'mesaj' => 'Yalnızca .xlsx biçimindeki Excel dosyaları kabul edilir.', 'hatalar' => []], 422);
            return;
        }

        try {
            $sonuc = (new OgrenciTopluImportServisi())->oku($geciciYol);
        } catch (\InvalidArgumentException $e) {
            Response::json(['basari' => false, 'mesaj' => $e->getMessage(), 'hatalar' => []], 422);
            return;
        } catch (\Throwable $e) {
            error_log('Öğrenci toplu import dosyası okunamadı: ' . $e->getMessage());
            Response::json(['basari' => false, 'mesaj' => 'Excel dosyası işlenirken beklenmeyen bir hata oluştu.', 'hatalar' => []], 500);
            return;
        }

        $eklenen = 0;
        $atlanmis = 0;
        $hatalar = $sonuc['hatalar'];
        foreach ($sonuc['kayitlar'] as $kayit) {
            if (Ogrenci::topluAktarimEslesmesiVarMi(
                $kayit['ogrenci_tc_kimlik_no'],
                $kayit['ogrenci_adi'],
                $kayit['ogrenci_soyadi'],
                $kayit['ogrenci_dogum_tarihi'],
                $kayit['veli_telefon']
            )) {
                $atlanmis++;
                $hatalar[] = ['satir' => $kayit['satir'], 'mesaj' => 'Öğrenci sistemde zaten kayıtlı olduğu için atlandı.'];
                continue;
            }

            try {
                Ogrenci::veliIleEkle([
                    'ogrenci' => [
                        'ad' => $kayit['ogrenci_adi'],
                        'soyad' => $kayit['ogrenci_soyadi'],
                        'tc_kimlik_no' => $kayit['ogrenci_tc_kimlik_no'],
                        'dogum_tarihi' => $kayit['ogrenci_dogum_tarihi'],
                        'cinsiyet' => $kayit['ogrenci_cinsiyet'],
                        'kayit_tarihi' => $kayit['ogrenci_kayit_tarihi'],
                        'acil_durum_kisi' => $kayit['acil_durum_kisi'],
                        'acil_durum_telefon' => $kayit['acil_durum_telefon'],
                        'saglik_bilgisi' => $kayit['saglik_bilgisi'],
                        'alerji_bilgisi' => $kayit['alerji_bilgisi'],
                        'ozel_durum_notu' => $kayit['ogrenci_notu'],
                        'vasi_ad_soyad' => '',
                        'vasi_tc_kimlik_no' => '',
                        'vasi_telefon' => '',
                        'yonetici_notu' => '',
                        'ogretmen_notu' => '',
                        'il' => $kayit['il'],
                        'ilce' => $kayit['ilce'],
                        'adres' => $kayit['adres'],
                    ],
                    'veli' => [
                        'ad' => $kayit['veli_adi'],
                        'soyad' => $kayit['veli_soyadi'],
                        'tc_kimlik_no' => '',
                        'telefon_ulke' => 'Turkiye',
                        'telefon' => $kayit['veli_telefon'],
                        'yedek_telefon' => $kayit['veli_yedek_telefon'],
                        'eposta' => $kayit['veli_eposta'],
                        'yakinlik' => $kayit['veli_yakinlik'],
                        'il' => $kayit['il'],
                        'ilce' => $kayit['ilce'],
                        'adres' => $kayit['adres'],
                        'notlar' => '',
                    ],
                ]);
                $eklenen++;
            } catch (\Throwable $e) {
                $hatalar[] = ['satir' => $kayit['satir'], 'mesaj' => 'Kayıt veritabanına eklenemedi.'];
            }
        }

        $hataSayisi = count($hatalar) - $atlanmis;
        $mesaj = $eklenen . ' öğrenci eklendi.';
        if ($atlanmis > 0) {
            $mesaj .= ' ' . $atlanmis . ' mevcut kayıt atlandı.';
        }
        if ($hataSayisi > 0) {
            $mesaj .= ' ' . $hataSayisi . ' satırda hata bulundu.';
        }

        Response::json([
            'basari' => $eklenen > 0,
            'mesaj' => $mesaj,
            'veri' => [
                'eklenen' => $eklenen,
                'atlanan' => $atlanmis,
                'hata_sayisi' => $hataSayisi,
                'hatalar' => array_slice($hatalar, 0, 100),
            ],
            'hatalar' => [],
        ], $eklenen > 0 ? 200 : 422);
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

    public function devamlilikRaporuSayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $arama = trim((string) ($_GET['arama'] ?? ''));
        $sekme = (string) ($_GET['sekme'] ?? 'aktif');
        if (!in_array($sekme, ['aktif', 'pasif'], true)) {
            $sekme = 'aktif';
        }
        $testDurumu = (string) ($_GET['test_durumu'] ?? 'tumu');
        if (!in_array($testDurumu, ['tumu', 'uygulandi', 'uygulanmadi', 'bir_ayi_gecen_uygulanmadi'], true)) {
            $testDurumu = 'tumu';
        }

        $tumKayitlar = OgrenciDevamlilikRaporu::liste($arama);
        $sekmeSayilari = ['aktif' => 0, 'pasif' => 0];
        foreach ($tumKayitlar as $kayit) {
            $sekmeSayilari[!empty($kayit['pasif_ogrenci']) ? 'pasif' : 'aktif']++;
        }
        $bugun = new \DateTimeImmutable('today');
        $kayitlar = array_values(array_filter($tumKayitlar, static function (array $kayit) use ($sekme, $testDurumu): bool {
            $pasif = !empty($kayit['pasif_ogrenci']);
            if (($sekme === 'pasif') !== $pasif) {
                return false;
            }
            if ($testDurumu === 'uygulandi') {
                return !empty($kayit['gelisim_testi_uygulandi']);
            }
            if ($testDurumu === 'uygulanmadi') {
                return empty($kayit['gelisim_testi_uygulandi']);
            }
            if ($testDurumu === 'bir_ayi_gecen_uygulanmadi') {
                return empty($kayit['gelisim_testi_uygulandi'])
                    && !empty($kayit['gelisim_testine_uygun']);
            }
            return true;
        }));
        if ($testDurumu === 'bir_ayi_gecen_uygulanmadi') {
            foreach ($kayitlar as &$kayit) {
                $ilkPaket = new \DateTimeImmutable((string) $kayit['ilk_paket_baslangic_tarihi']);
                $birAyEsigi = $ilkPaket->modify('+1 month');
                $kayit['bir_ay_sinirini_gecen_gun'] = max(0, (int) $birAyEsigi->diff($bugun)->format('%a'));
            }
            unset($kayit);
            usort($kayitlar, static fn(array $a, array $b): int =>
                ((int) ($b['kayitli_gun_sayisi'] ?? 0) <=> (int) ($a['kayitli_gun_sayisi'] ?? 0))
                ?: strcmp((string) ($a['ad'] ?? ''), (string) ($b['ad'] ?? ''))
            );
        }
        $this->view('panel/ogrenci-devamlilik-raporu', [
            'baslik' => 'Öğrenci Devamlılık Raporu',
            'aktif' => 'ogrenci-devamlilik',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'arama' => $arama,
            'sekme' => $sekme,
            'testDurumu' => $testDurumu,
            'sekmeSayilari' => $sekmeSayilari,
            'kayitlar' => $kayitlar,
            'ozet' => OgrenciDevamlilikRaporu::ozet($kayitlar),
            'gelisimTestiTablosuHazir' => OgrenciDevamlilikRaporu::gelisimTestiTablosuVarMi(),
        ], 'panel');
    }

    public function adresHaritasiSayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $kayitlar = OgrenciAdresHaritasi::liste();
        $this->view('panel/ogrenci-adres-haritasi', [
            'baslik' => 'Veli Adres Yoğunluk Haritası',
            'aktif' => 'ogrenci-adres-haritasi',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'kayitlar' => $kayitlar,
            'ozet' => OgrenciAdresHaritasi::ozet($kayitlar),
            'cocukIsletmeleri' => CocukIsletmesi::liste(),
            'cocukIsletmeleriSonAktarim' => CocukIsletmesi::sonAktarim(),
            'haritaKaroAdresi' => (string) Config::get('OSM_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
            'googleHaritaAnahtari' => trim((string) Config::get('GOOGLE_MAPS_API_KEY', '')),
        ], 'panel');
    }

    public function ozelNotlarSayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $arama = trim((string) ($_GET['arama'] ?? ''));
        $durum = (string) ($_GET['durum'] ?? 'aktif');
        if (!in_array($durum, ['aktif', 'pasif', 'tumu'], true)) {
            $durum = 'aktif';
        }

        $this->view('panel/ogrenci-ozel-notlar', [
            'baslik' => 'Öğrenci Özel Notları',
            'aktif' => 'ogrenci-ozel-notlar',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'arama' => $arama,
            'durum' => $durum,
            'kayitlar' => Ogrenci::ozelNotListesi($arama, $durum),
        ], 'panel');
    }

    public function adresKonumuGuncelle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $ogrenciId = (int) ($data['ogrenci_id'] ?? 0);
        $enlem = filter_var($data['enlem'] ?? null, FILTER_VALIDATE_FLOAT);
        $boylam = filter_var($data['boylam'] ?? null, FILTER_VALIDATE_FLOAT);
        $adresAnahtari = trim((string) ($data['adres_anahtari'] ?? ''));
        if ($ogrenciId < 1 || $enlem === false || $boylam === false) {
            Response::json(['basari' => false, 'mesaj' => 'Öğrenci ve harita konumu seçilmelidir.', 'hatalar' => []], 422);
            return;
        }

        try {
            if (!OgrenciAdresHaritasi::konumGuncelle($ogrenciId, (float) $enlem, (float) $boylam, $adresAnahtari)) {
                Response::json(['basari' => false, 'mesaj' => 'Adresli öğrenci kaydı bulunamadı.', 'hatalar' => []], 404);
                return;
            }
        } catch (\InvalidArgumentException $e) {
            Response::json(['basari' => false, 'mesaj' => $e->getMessage(), 'hatalar' => []], 422);
            return;
        }

        Response::json(['basari' => true, 'mesaj' => 'Adres konumu doğrulandı.', 'veri' => ['ogrenci_id' => $ogrenciId]]);
    }

    public function gelisimTestiGuncelle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $ogrenciId = (int) ($data['ogrenci_id'] ?? 0);
        $uygulandi = filter_var($data['uygulandi'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $uygulamaTarihi = trim((string) ($data['uygulama_tarihi'] ?? ''));

        $hatalar = [];
        if ($ogrenciId < 1) {
            $hatalar['ogrenci_id'] = 'Öğrenci seçimi geçersiz.';
        }
        if ($uygulamaTarihi !== '') {
            $tarih = \DateTimeImmutable::createFromFormat('!Y-m-d', $uygulamaTarihi);
            if (!$tarih || $tarih->format('Y-m-d') !== $uygulamaTarihi) {
                $hatalar['uygulama_tarihi'] = 'Uygulama tarihi geçersiz.';
            }
        }
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik veya hatalı alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        $guncellendi = OgrenciDevamlilikRaporu::gelisimTestiGuncelle(
            $ogrenciId,
            $uygulandi,
            $uygulamaTarihi !== '' ? $uygulamaTarihi : null,
            (int) (Auth::user()['id'] ?? 0)
        );
        if (!$guncellendi) {
            Response::json(['basari' => false, 'mesaj' => 'Randevusu bulunan öğrenci kaydı bulunamadı.', 'hatalar' => []], 404);
            return;
        }

        Response::json([
            'basari' => true,
            'mesaj' => 'Gelişim testi bilgisi güncellendi.',
            'veri' => [
                'ogrenci_id' => $ogrenciId,
                'uygulandi' => $uygulandi,
                'uygulama_tarihi' => $uygulamaTarihi ?: ($uygulandi ? date('Y-m-d') : null),
            ],
        ]);
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
            'il' => trim((string) ($data['il'] ?? '')) ?: 'Antalya',
            'ilce' => trim((string) ($data['ilce'] ?? '')),
            'adres' => trim((string) ($data['adres'] ?? '')),
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
                'il' => trim((string) ($data['il'] ?? '')) ?: 'Antalya',
                'ilce' => trim((string) ($data['ilce'] ?? '')),
                'adres' => trim((string) ($data['adres'] ?? '')),
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
                'il' => trim((string) ($data['ogrenci_il'] ?? '')) ?: 'Antalya',
                'ilce' => trim((string) ($data['ogrenci_ilce'] ?? '')),
                'adres' => trim((string) ($data['ogrenci_adres'] ?? '')),
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
                'il' => trim((string) ($data['veli_il'] ?? '')) ?: 'Antalya',
                'ilce' => trim((string) ($data['veli_ilce'] ?? '')),
                'adres' => trim((string) ($data['veli_adres'] ?? '')),
                'notlar' => trim((string) ($data['veli_notlar'] ?? '')),
            ],
        ]);

        Response::json(['basari' => true, 'mesaj' => 'Bilgiler guncellendi.', 'veri' => ['id' => $id]]);
    }

    public function hizliGuncelle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['id', 'ogrenci_ad', 'ogrenci_soyad']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Ogrenci ad soyad zorunludur.', 'hatalar' => $hatalar], 422);
            return;
        }

        $id = (int) $data['id'];
        if ($id < 1 || !Ogrenci::raporBilgisi($id)) {
            Response::json(['basari' => false, 'mesaj' => 'Ogrenci bulunamadi.', 'hatalar' => []], 404);
            return;
        }

        $cinsiyet = (string) ($data['ogrenci_cinsiyet'] ?? 'belirtilmedi');
        $durum = (string) ($data['ogrenci_durum'] ?? 'aktif');
        if (!in_array($cinsiyet, ['belirtilmedi', 'kiz', 'erkek'], true)) {
            $cinsiyet = 'belirtilmedi';
        }
        if (!in_array($durum, ['aktif', 'pasif'], true)) {
            $durum = 'aktif';
        }

        $dogumTarihi = trim((string) ($data['ogrenci_dogum_tarihi'] ?? ''));
        $kayitTarihi = trim((string) ($data['ogrenci_kayit_tarihi'] ?? '')) ?: date('Y-m-d');
        $gecerliTarih = static function (string $tarih): bool {
            $nesne = \DateTimeImmutable::createFromFormat('!Y-m-d', $tarih);
            return $nesne !== false && $nesne->format('Y-m-d') === $tarih;
        };
        if (($dogumTarihi !== '' && !$gecerliTarih($dogumTarihi)) || !$gecerliTarih($kayitTarihi)) {
            Response::json(['basari' => false, 'mesaj' => 'Tarih bilgisi gecersiz.', 'hatalar' => []], 422);
            return;
        }
        $adres = trim((string) ($data['ogrenci_adres'] ?? ''));
        if (mb_strlen($adres) > 500) {
            Response::json(['basari' => false, 'mesaj' => 'Adres en fazla 500 karakter olabilir.', 'hatalar' => []], 422);
            return;
        }

        Ogrenci::hizliGuncelle($id, [
            'ad' => trim((string) $data['ogrenci_ad']),
            'soyad' => trim((string) $data['ogrenci_soyad']),
            'dogum_tarihi' => $dogumTarihi,
            'cinsiyet' => $cinsiyet,
            'kayit_tarihi' => $kayitTarihi,
            'durum' => $durum,
            'il' => trim((string) ($data['ogrenci_il'] ?? '')) ?: 'Antalya',
            'ilce' => trim((string) ($data['ogrenci_ilce'] ?? '')),
            'adres' => $adres,
        ], [
            'id' => (int) ($data['veli_id'] ?? 0),
            'ad' => trim((string) ($data['veli_ad'] ?? '')),
            'soyad' => trim((string) ($data['veli_soyad'] ?? '')),
            'telefon_ulke' => 'Turkiye',
            'telefon' => $this->telefonFormatla((string) ($data['veli_telefon'] ?? '')),
        ]);

        Response::json(['basari' => true, 'mesaj' => 'Bilgiler hizlica guncellendi.', 'veri' => ['id' => $id]]);
    }

    public function ozelNotGuncelle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        $not = trim((string) ($data['profil_ozel_notu'] ?? ''));

        if ($id < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Ogrenci secilmelidir.', 'hatalar' => []], 422);
            return;
        }
        if (mb_strlen($not) > 500) {
            Response::json(['basari' => false, 'mesaj' => 'Ozel not en fazla 500 karakter olabilir.', 'hatalar' => []], 422);
            return;
        }
        if (!Ogrenci::profil($id)) {
            Response::json(['basari' => false, 'mesaj' => 'Ogrenci bulunamadi.', 'hatalar' => []], 404);
            return;
        }

        Ogrenci::ozelNotGuncelle($id, $not);
        Response::json([
            'basari' => true,
            'mesaj' => $not === '' ? 'Ozel not kaldirildi.' : 'Ozel not kaydedildi.',
            'veri' => ['id' => $id],
        ]);
    }
}
