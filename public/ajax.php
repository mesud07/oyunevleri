<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Controllers\AyarlarController;
use App\Controllers\BekleyenVeliController;
use App\Controllers\GiderController;
use App\Controllers\GrupController;
use App\Controllers\GunlukKayitController;
use App\Controllers\HaftalikTemaController;
use App\Controllers\HizmetController;
use App\Controllers\KasaController;
use App\Controllers\KullaniciController;
use App\Controllers\KurumController;
use App\Controllers\OdemeController;
use App\Controllers\OgrenciController;
use App\Controllers\OnamFormuController;
use App\Controllers\PaketController;
use App\Controllers\PaketDisiHakController;
use App\Controllers\RandevuController;
use App\Controllers\RaporController;
use App\Controllers\SmsController;
use App\Controllers\TelafiController;
use App\Controllers\VeliController;
use App\Core\Auth;
use App\Core\Response;
use App\Middleware\CsrfKontrolu;
use App\Middleware\YetkiKontrolu;

function talyaAjaxLogla(string $islem, \Throwable $hata, array $data): string
{
    $hataKodu = 'AJAX-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3));
    $gizliAlanlar = '/sifre|password|token|secret|key|csrf/i';
    $temizData = [];

    foreach ($data as $anahtar => $deger) {
        $temizData[$anahtar] = preg_match($gizliAlanlar, (string) $anahtar) ? '[gizlendi]' : $deger;
    }

    $kullanici = Auth::user();
    $icerik = sprintf(
        "[%s] %s\nislem=%s kullanici=%s uri=%s\nhata=%s\nkonum=%s:%d\ndata=%s\ntrace=%s\n\n",
        date('Y-m-d H:i:s'),
        $hataKodu,
        $islem,
        (string) ($kullanici['id'] ?? '-'),
        (string) ($_SERVER['REQUEST_URI'] ?? '-'),
        $hata->getMessage(),
        $hata->getFile(),
        $hata->getLine(),
        json_encode($temizData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        $hata->getTraceAsString()
    );

    $dizin = BASE_PATH . '/storage/logs';
    if (!is_dir($dizin)) {
        @mkdir($dizin, 0775, true);
    }
    if (is_dir($dizin) && is_writable($dizin)) {
        @file_put_contents($dizin . '/ajax.log', $icerik, FILE_APPEND);
    }
    error_log($icerik);

    return $hataKodu;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    Response::json(['basari' => false, 'mesaj' => 'Yalnizca POST kabul edilir.', 'hatalar' => []], 405);
    exit;
}

if (!Auth::check()) {
    Response::json(['basari' => false, 'mesaj' => 'Oturum gerekli.', 'hatalar' => []], 401);
    exit;
}

$raw = file_get_contents('php://input') ?: '{}';
$data = json_decode($raw, true);
if (!is_array($data)) {
    Response::json(['basari' => false, 'mesaj' => 'Gecersiz JSON.', 'hatalar' => []], 422);
    exit;
}

$GLOBALS['talya_ajax_data'] = $data;

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!(new CsrfKontrolu())->kontrol($token)) {
    Response::json(['basari' => false, 'mesaj' => 'CSRF dogrulamasi basarisiz.', 'hatalar' => []], 419);
    exit;
}

$islemHaritasi = [
    'ogrenci_listele' => ['controller' => OgrenciController::class, 'metot' => 'liste', 'yetki' => 'ogrenci_listele'],
    'ogrenci_telefon_kontrol' => ['controller' => OgrenciController::class, 'metot' => 'telefonKontrol', 'yetki' => 'ogrenci_listele'],
    'ogrenci_kara_liste_ekle' => ['controller' => OgrenciController::class, 'metot' => 'karaListeEkle', 'yetki' => 'ogrenci_ekle'],
    'ogrenci_kara_liste_kaldir' => ['controller' => OgrenciController::class, 'metot' => 'karaListeKaldir', 'yetki' => 'ogrenci_ekle'],
    'ogrenci_ekle' => ['controller' => OgrenciController::class, 'metot' => 'ekle', 'yetki' => 'ogrenci_ekle'],
    'ogrenci_veli_ekle' => ['controller' => OgrenciController::class, 'metot' => 'veliIleEkle', 'yetki' => 'ogrenci_ekle'],
    'ogrenci_profil_guncelle' => ['controller' => OgrenciController::class, 'metot' => 'profilGuncelle', 'yetki' => 'ogrenci_ekle'],
    'ogrenci_sil' => ['controller' => OgrenciController::class, 'metot' => 'sil', 'yetki' => 'ogrenci_ekle'],
    'onam_formu_olustur' => ['controller' => OnamFormuController::class, 'metot' => 'olustur', 'yetki' => 'ogrenci_ekle'],
    'bekleyen_veli_listele' => ['controller' => BekleyenVeliController::class, 'metot' => 'liste', 'yetki' => 'bekleyen_veli_listele'],
    'bekleyen_veli_ekle' => ['controller' => BekleyenVeliController::class, 'metot' => 'ekle', 'yetki' => 'bekleyen_veli_ekle'],
    'bekleyen_veli_durum_guncelle' => ['controller' => BekleyenVeliController::class, 'metot' => 'durumGuncelle', 'yetki' => 'bekleyen_veli_ekle'],
    'bekleyen_veli_ogrenciye_donustur' => ['controller' => BekleyenVeliController::class, 'metot' => 'ogrenciyeDonustur', 'yetki' => 'bekleyen_veli_ekle'],
    'bekleyen_veli_sil' => ['controller' => BekleyenVeliController::class, 'metot' => 'sil', 'yetki' => 'bekleyen_veli_ekle'],
    'veli_listele' => ['controller' => VeliController::class, 'metot' => 'liste', 'yetki' => 'veli_listele'],
    'veli_ekle' => ['controller' => VeliController::class, 'metot' => 'ekle', 'yetki' => 'veli_ekle'],
    'grup_listele' => ['controller' => GrupController::class, 'metot' => 'liste', 'yetki' => 'grup_listele'],
    'grup_ekle' => ['controller' => GrupController::class, 'metot' => 'ekle', 'yetki' => 'grup_ekle'],
    'grup_program_listele' => ['controller' => GrupController::class, 'metot' => 'programListe', 'yetki' => 'grup_listele'],
    'grup_bos_kontenjan_takvimi' => ['controller' => GrupController::class, 'metot' => 'bosKontenjanTakvimi', 'yetki' => 'grup_listele'],
    'grup_randevu_senkronize_et' => ['controller' => GrupController::class, 'metot' => 'randevulariSenkronizeEt', 'yetki' => 'grup_ekle'],
    'grup_program_ekle' => ['controller' => GrupController::class, 'metot' => 'programEkle', 'yetki' => 'grup_ekle'],
    'grup_program_guncelle' => ['controller' => GrupController::class, 'metot' => 'programGuncelle', 'yetki' => 'grup_ekle'],
    'grup_program_sil' => ['controller' => GrupController::class, 'metot' => 'programSil', 'yetki' => 'grup_ekle'],
    'grup_ogrenci_secenekleri' => ['controller' => GrupController::class, 'metot' => 'ogrenciSecenekleri', 'yetki' => 'ogrenci_listele'],
    'grup_ogrenci_listele' => ['controller' => GrupController::class, 'metot' => 'grupOgrencileri', 'yetki' => 'grup_listele'],
    'grup_aylik_takip' => ['controller' => GrupController::class, 'metot' => 'aylikTakip', 'yetki' => 'grup_listele'],
    'grup_ogrenci_ata' => ['controller' => GrupController::class, 'metot' => 'ogrenciAta', 'yetki' => 'grup_ekle'],
    'grup_ogrenci_cikar' => ['controller' => GrupController::class, 'metot' => 'ogrenciCikar', 'yetki' => 'grup_ekle'],
    'hizmet_listele' => ['controller' => HizmetController::class, 'metot' => 'liste', 'yetki' => 'paket_listele'],
    'hizmet_ekle' => ['controller' => HizmetController::class, 'metot' => 'ekle', 'yetki' => 'paket_ekle'],
    'hizmet_guncelle' => ['controller' => HizmetController::class, 'metot' => 'guncelle', 'yetki' => 'paket_ekle'],
    'hizmet_sil' => ['controller' => HizmetController::class, 'metot' => 'sil', 'yetki' => 'paket_ekle'],
    'paket_listele' => ['controller' => PaketController::class, 'metot' => 'liste', 'yetki' => 'paket_listele'],
    'paket_ekle' => ['controller' => PaketController::class, 'metot' => 'ekle', 'yetki' => 'paket_ekle'],
    'hizli_randevu_ekle' => ['controller' => PaketController::class, 'metot' => 'hizliRandevu', 'yetki' => 'randevu_ekle'],
    'paket_sil' => ['controller' => PaketController::class, 'metot' => 'sil', 'yetki' => 'paket_ekle'],
    'odeme_listele' => ['controller' => OdemeController::class, 'metot' => 'liste', 'yetki' => 'odeme_listele'],
    'odeme_ekle' => ['controller' => OdemeController::class, 'metot' => 'ekle', 'yetki' => 'odeme_ekle'],
    'paket_odeme_yapilmadi_kapat' => ['controller' => OdemeController::class, 'metot' => 'odemeYapilmadiKapat', 'yetki' => 'odeme_ekle'],
    'odeme_geri_al' => ['controller' => OdemeController::class, 'metot' => 'geriAl', 'yetki' => 'odeme_ekle'],
    'odeme_kasaya_aktar' => ['controller' => OdemeController::class, 'metot' => 'kasayaAktar', 'yetki' => 'odeme_ekle'],
    'odeme_sil' => ['controller' => OdemeController::class, 'metot' => 'sil', 'yetki' => 'odeme_ekle'],
    'gider_listele' => ['controller' => GiderController::class, 'metot' => 'liste', 'yetki' => 'odeme_listele'],
    'gider_ekle' => ['controller' => GiderController::class, 'metot' => 'ekle', 'yetki' => 'odeme_ekle'],
    'gider_guncelle' => ['controller' => GiderController::class, 'metot' => 'guncelle', 'yetki' => 'odeme_ekle'],
    'gider_odendi' => ['controller' => GiderController::class, 'metot' => 'odendi', 'yetki' => 'odeme_ekle'],
    'gider_sil' => ['controller' => GiderController::class, 'metot' => 'sil', 'yetki' => 'odeme_ekle'],
    'kasa_listele' => ['controller' => KasaController::class, 'metot' => 'liste', 'yetki' => 'odeme_listele'],
    'kasa_ekle' => ['controller' => KasaController::class, 'metot' => 'ekle', 'yetki' => 'odeme_ekle'],
    'kasa_guncelle' => ['controller' => KasaController::class, 'metot' => 'guncelle', 'yetki' => 'odeme_ekle'],
    'kasa_hareket_ekle' => ['controller' => KasaController::class, 'metot' => 'hareketEkle', 'yetki' => 'odeme_ekle'],
    'kasa_sil' => ['controller' => KasaController::class, 'metot' => 'sil', 'yetki' => 'odeme_ekle'],
    'randevu_listele' => ['controller' => RandevuController::class, 'metot' => 'liste', 'yetki' => 'randevu_listele'],
    'randevu_detay' => ['controller' => RandevuController::class, 'metot' => 'detay', 'yetki' => 'randevu_listele'],
    'randevu_takvim' => ['controller' => RandevuController::class, 'metot' => 'takvim', 'yetki' => 'randevu_listele'],
    'randevu_ekle' => ['controller' => RandevuController::class, 'metot' => 'ekle', 'yetki' => 'randevu_ekle'],
    'randevu_guncelle' => ['controller' => RandevuController::class, 'metot' => 'guncelle', 'yetki' => 'randevu_ekle'],
    'randevu_durum_degistir' => ['controller' => RandevuController::class, 'metot' => 'durumDegistir', 'yetki' => 'randevu_durum_degistir'],
    'randevu_toplu_guncelle' => ['controller' => RandevuController::class, 'metot' => 'topluGuncelle', 'yetki' => 'randevu_ekle'],
    'randevu_sil' => ['controller' => RandevuController::class, 'metot' => 'sil', 'yetki' => 'randevu_ekle'],
    'gunluk_not_ekle' => ['controller' => GunlukKayitController::class, 'metot' => 'ekle', 'yetki' => 'randevu_ekle'],
    'haftalik_tema_listele' => ['controller' => HaftalikTemaController::class, 'metot' => 'liste', 'yetki' => 'tema_yonet'],
    'haftalik_tema_detay' => ['controller' => HaftalikTemaController::class, 'metot' => 'detay', 'yetki' => 'tema_yonet'],
    'haftalik_tema_kaydet' => ['controller' => HaftalikTemaController::class, 'metot' => 'kaydet', 'yetki' => 'tema_yonet'],
    'haftalik_tema_sil' => ['controller' => HaftalikTemaController::class, 'metot' => 'sil', 'yetki' => 'tema_yonet'],
    'ogrenci_etkinlik_ekle' => ['controller' => HaftalikTemaController::class, 'metot' => 'ogrenciEtkinlikEkle', 'yetki' => 'tema_yonet'],
    'ogrenci_etkinlik_sil' => ['controller' => HaftalikTemaController::class, 'metot' => 'ogrenciEtkinlikSil', 'yetki' => 'tema_yonet'],
    'paket_disi_hak_listele' => ['controller' => PaketDisiHakController::class, 'metot' => 'liste', 'yetki' => 'paket_listele'],
    'telafi_listele' => ['controller' => TelafiController::class, 'metot' => 'liste', 'yetki' => 'randevu_listele'],
    'telafi_planla' => ['controller' => TelafiController::class, 'metot' => 'planla', 'yetki' => 'randevu_ekle'],
    'rapor_ozet' => ['controller' => RaporController::class, 'metot' => 'ozet', 'yetki' => 'rapor_ozet'],
    'gelir_gider_analizi' => ['controller' => RaporController::class, 'metot' => 'gelirGiderVeri', 'yetki' => 'rapor_ozet'],
    'ayar_listele' => ['controller' => AyarlarController::class, 'metot' => 'liste', 'yetki' => 'rapor_ozet'],
    'sms_tekli_gonder' => ['controller' => SmsController::class, 'metot' => 'tekliGonder', 'yetki' => 'sms_gonder'],
    'sms_ogrenciye_gonder' => ['controller' => SmsController::class, 'metot' => 'ogrenciyeGonder', 'yetki' => 'sms_gonder'],
    'sms_toplu_gonder' => ['controller' => SmsController::class, 'metot' => 'topluGonder', 'yetki' => 'sms_toplu_gonder'],
    'sms_kuyruga_ekle' => ['controller' => SmsController::class, 'metot' => 'kuyrugaEkle', 'yetki' => 'sms_gonder'],
    'sms_kayitlarini_listele' => ['controller' => SmsController::class, 'metot' => 'kayitlariListele', 'yetki' => 'sms_goruntule'],
    'sms_detay_getir' => ['controller' => SmsController::class, 'metot' => 'detayGetir', 'yetki' => 'sms_goruntule'],
    'sms_raporlari_listele' => ['controller' => SmsController::class, 'metot' => 'raporlariListele', 'yetki' => 'sms_rapor_goruntule'],
    'sms_ogrenci_raporlari' => ['controller' => SmsController::class, 'metot' => 'ogrenciRaporlari', 'yetki' => 'sms_rapor_goruntule'],
    'sms_rapor_kontrol_et' => ['controller' => SmsController::class, 'metot' => 'raporKontrolEt', 'yetki' => 'sms_rapor_goruntule'],
    'sms_tekrar_gonder' => ['controller' => SmsController::class, 'metot' => 'tekrarGonder', 'yetki' => 'sms_tekrar_gonder'],
    'sms_iptal_et' => ['controller' => SmsController::class, 'metot' => 'iptalEt', 'yetki' => 'sms_gonder'],
    'sms_sablonlarini_listele' => ['controller' => SmsController::class, 'metot' => 'sablonlariniListele', 'yetki' => 'sms_sablon_yonet'],
    'sms_sablon_secimleri' => ['controller' => SmsController::class, 'metot' => 'sablonSecimleri', 'yetki' => 'sms_gonder'],
    'sms_sablon_kaydet' => ['controller' => SmsController::class, 'metot' => 'sablonKaydet', 'yetki' => 'sms_sablon_yonet'],
    'sms_sablon_durum_degistir' => ['controller' => SmsController::class, 'metot' => 'sablonDurumDegistir', 'yetki' => 'sms_sablon_yonet'],
    'sms_sablon_onayla' => ['controller' => SmsController::class, 'metot' => 'sablonOnayla', 'yetki' => 'sms_sablon_yonet'],
    'sms_sablon_reddet' => ['controller' => SmsController::class, 'metot' => 'sablonReddet', 'yetki' => 'sms_sablon_yonet'],
    'sms_baglanti_ayarlari_kaydet' => ['controller' => SmsController::class, 'metot' => 'baglantiAyarlariKaydet', 'yetki' => 'sms_ayar_yonet'],
    'sms_baglanti_dogrula' => ['controller' => SmsController::class, 'metot' => 'baglantiDogrula', 'yetki' => 'sms_ayar_yonet'],
    'sms_hatirlatma_ayarlari_kaydet' => ['controller' => SmsController::class, 'metot' => 'hatirlatmaAyarlariKaydet', 'yetki' => 'sms_ayar_yonet'],
    'sms_netgsm_basliklari_listele' => ['controller' => SmsController::class, 'metot' => 'netgsmBasliklariListele', 'yetki' => 'sms_ayar_yonet'],
    'sms_test_gonder' => ['controller' => SmsController::class, 'metot' => 'testGonder', 'yetki' => 'sms_gonder'],
    'kullanici_listele' => ['controller' => KullaniciController::class, 'metot' => 'liste', 'yetki' => 'kullanici_yonet'],
    'kullanici_rolleri' => ['controller' => KullaniciController::class, 'metot' => 'roller', 'yetki' => 'kullanici_yonet'],
    'kullanici_kaydet' => ['controller' => KullaniciController::class, 'metot' => 'kaydet', 'yetki' => 'kullanici_yonet'],
    'kullanici_rol_kaydet' => ['controller' => KullaniciController::class, 'metot' => 'rolKaydet', 'yetki' => 'kullanici_yonet'],
    'kurum_listele' => ['controller' => KurumController::class, 'metot' => 'liste', 'yetki' => 'sistem_yonetimi'],
    'kurum_kaydet' => ['controller' => KurumController::class, 'metot' => 'kaydet', 'yetki' => 'sistem_yonetimi'],
];

$islem = (string) ($data['islem'] ?? '');
if (!isset($islemHaritasi[$islem])) {
    Response::json(['basari' => false, 'mesaj' => 'Bilinmeyen islem.', 'hatalar' => []], 404);
    exit;
}

$hedef = $islemHaritasi[$islem];
if (!(new YetkiKontrolu())->kontrol($hedef['yetki'])) {
    Response::json(['basari' => false, 'mesaj' => 'Bu islem icin yetkiniz yok.', 'hatalar' => []], 403);
    exit;
}

ob_start();
try {
    (new $hedef['controller']())->{$hedef['metot']}();
} catch (\Throwable $e) {
    if (ob_get_length() !== false) {
        ob_clean();
    }

    $hataKodu = talyaAjaxLogla($islem, $e, $data);

    Response::json([
        'basari' => false,
        'mesaj' => 'Islem sirasinda beklenmeyen bir hata olustu. Hata kodu: ' . $hataKodu,
        'hata_kodu' => $hataKodu,
        'hatalar' => [],
    ], 500);
} finally {
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
}
