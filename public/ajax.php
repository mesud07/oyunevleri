<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Controllers\AyarlarController;
use App\Controllers\BekleyenVeliController;
use App\Controllers\GiderController;
use App\Controllers\FaturaController;
use App\Controllers\CariController;
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
use App\Controllers\PersonelController;
use App\Controllers\RandevuController;
use App\Controllers\RaporController;
use App\Controllers\SmsController;
use App\Controllers\TelafiController;
use App\Controllers\VeliController;
use App\Controllers\VeliOnamController;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Response;
use App\Middleware\CsrfKontrolu;
use App\Middleware\YetkiKontrolu;
use App\Services\MfaServisi;
use App\Services\KurumModuluServisi;

function talyaAjaxLogla(string $islem, \Throwable $hata, array $data): string
{
    $hataKodu = 'AJAX-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3));
    $alanlar = array_values(array_filter(array_map('strval', array_keys($data)), static fn(string $alan): bool => !preg_match('/sifre|password|token|secret|key|csrf/i', $alan)));

    $kullanici = Auth::user();
    $icerik = sprintf(
        "[%s] %s\nislem=%s kullanici=%s uri=%s\nhata_tipi=%s\nkonum=%s:%d\nalanlar=%s%s\n\n",
        date('Y-m-d H:i:s'),
        $hataKodu,
        $islem,
        (string) ($kullanici['id'] ?? '-'),
        (string) ($_SERVER['REQUEST_URI'] ?? '-'),
        get_class($hata),
        $hata->getFile(),
        $hata->getLine(),
        json_encode($alanlar, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        Config::bool('APP_DEBUG', false) ? "\ntrace=" . $hata->getTraceAsString() : ''
    );

    $dizin = BASE_PATH . '/storage/logs';
    if (!is_dir($dizin)) {
        @mkdir($dizin, 0770, true);
    }
    if (is_dir($dizin) && is_writable($dizin)) {
        $log = $dizin . '/ajax.log';
        if (is_file($log) && filesize($log) > 5 * 1024 * 1024) {
            @rename($log, $dizin . '/ajax.log.1');
        }
        @file_put_contents($log, $icerik, FILE_APPEND | LOCK_EX);
        @chmod($log, 0640);
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
    'ogrenci_gelisim_testi_guncelle' => ['controller' => OgrenciController::class, 'metot' => 'gelisimTestiGuncelle', 'yetki' => 'ogrenci_ekle'],
    'ogrenci_adres_konumu_guncelle' => ['controller' => OgrenciController::class, 'metot' => 'adresKonumuGuncelle', 'yetki' => 'ogrenci_ekle'],
    'ogrenci_ekle' => ['controller' => OgrenciController::class, 'metot' => 'ekle', 'yetki' => 'ogrenci_ekle'],
    'ogrenci_veli_ekle' => ['controller' => OgrenciController::class, 'metot' => 'veliIleEkle', 'yetki' => 'ogrenci_ekle'],
    'ogrenci_profil_guncelle' => ['controller' => OgrenciController::class, 'metot' => 'profilGuncelle', 'yetki' => 'ogrenci_ekle'],
    'ogrenci_hizli_guncelle' => ['controller' => OgrenciController::class, 'metot' => 'hizliGuncelle', 'yetki' => 'ogrenci_ekle'],
    'ogrenci_ozel_not_guncelle' => ['controller' => OgrenciController::class, 'metot' => 'ozelNotGuncelle', 'yetki' => 'ogrenci_ekle'],
    'ogrenci_sil' => ['controller' => OgrenciController::class, 'metot' => 'sil', 'yetki' => 'ogrenci_ekle'],
    'onam_formu_olustur' => ['controller' => OnamFormuController::class, 'metot' => 'olustur', 'yetki' => 'ogrenci_ekle'],
    'bekleyen_veli_listele' => ['controller' => BekleyenVeliController::class, 'metot' => 'liste', 'yetki' => 'bekleyen_veli_listele'],
    'bekleyen_veli_ekle' => ['controller' => BekleyenVeliController::class, 'metot' => 'ekle', 'yetki' => 'bekleyen_veli_ekle'],
    'bekleyen_veli_guncelle' => ['controller' => BekleyenVeliController::class, 'metot' => 'guncelle', 'yetki' => 'bekleyen_veli_ekle'],
    'bekleyen_veli_durum_guncelle' => ['controller' => BekleyenVeliController::class, 'metot' => 'durumGuncelle', 'yetki' => 'bekleyen_veli_ekle'],
    'bekleyen_veli_gorusmeleri' => ['controller' => BekleyenVeliController::class, 'metot' => 'gorusmeler', 'yetki' => 'bekleyen_veli_listele'],
    'bekleyen_veli_gorusme_ekle' => ['controller' => BekleyenVeliController::class, 'metot' => 'gorusmeEkle', 'yetki' => 'bekleyen_veli_ekle'],
    'bekleyen_veli_gruplari_guncelle' => ['controller' => BekleyenVeliController::class, 'metot' => 'gruplariGuncelle', 'yetki' => 'bekleyen_veli_ekle'],
    'bekleyen_veli_ogrenciye_donustur' => ['controller' => BekleyenVeliController::class, 'metot' => 'ogrenciyeDonustur', 'yetki' => 'bekleyen_veli_ekle'],
    'bekleyen_veli_sil' => ['controller' => BekleyenVeliController::class, 'metot' => 'sil', 'yetki' => 'bekleyen_veli_ekle'],
    'veli_listele' => ['controller' => VeliController::class, 'metot' => 'liste', 'yetki' => 'veli_listele'],
    'veli_ekle' => ['controller' => VeliController::class, 'metot' => 'ekle', 'yetki' => 'veli_ekle'],
    'veli_onam_ayari_kaydet' => ['controller' => VeliOnamController::class, 'metot' => 'ayarKaydet', 'yetki' => 'veli_listele'],
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
    'grup_bekleyen_veli_listele' => ['controller' => GrupController::class, 'metot' => 'bekleyenVeliler', 'yetki' => 'grup_listele'],
    'grup_aylik_takip' => ['controller' => GrupController::class, 'metot' => 'aylikTakip', 'yetki' => 'grup_listele'],
    'grup_ogrenci_ata' => ['controller' => GrupController::class, 'metot' => 'ogrenciAta', 'yetki' => 'grup_ekle'],
    'grup_ogrenci_cikar' => ['controller' => GrupController::class, 'metot' => 'ogrenciCikar', 'yetki' => 'grup_ekle'],
    'hizmet_listele' => ['controller' => HizmetController::class, 'metot' => 'liste', 'yetki' => 'paket_listele'],
    'hizmet_ekle' => ['controller' => HizmetController::class, 'metot' => 'ekle', 'yetki' => 'paket_ekle'],
    'hizmet_guncelle' => ['controller' => HizmetController::class, 'metot' => 'guncelle', 'yetki' => 'paket_ekle'],
    'hizmet_sil' => ['controller' => HizmetController::class, 'metot' => 'sil', 'yetki' => 'paket_ekle'],
    'paket_listele' => ['controller' => PaketController::class, 'metot' => 'liste', 'yetki' => 'paket_listele'],
    'paket_ekle' => ['controller' => PaketController::class, 'metot' => 'ekle', 'yetki' => 'paket_ekle'],
    'paket_tahsilat_notu_guncelle' => ['controller' => PaketController::class, 'metot' => 'tahsilatNotuGuncelle', 'yetki' => 'odeme_ekle'],
    'paket_odeme_plani_guncelle' => ['controller' => PaketController::class, 'metot' => 'odemePlaniGuncelle', 'yetki' => 'odeme_ekle'],
    'hizli_randevu_ekle' => ['controller' => PaketController::class, 'metot' => 'hizliRandevu', 'yetki' => 'randevu_ekle'],
    'paket_sil' => ['controller' => PaketController::class, 'metot' => 'sil', 'yetki' => 'paket_ekle'],
    'odeme_listele' => ['controller' => OdemeController::class, 'metot' => 'liste', 'yetki' => 'odeme_listele'],
    'odeme_kdv_ayarlari_kaydet' => ['controller' => OdemeController::class, 'metot' => 'kdvAyarlariKaydet', 'yetki' => 'odeme_ekle'],
    'odeme_ekle' => ['controller' => OdemeController::class, 'metot' => 'ekle', 'yetki' => 'odeme_ekle'],
    'paket_odeme_yapilmadi_kapat' => ['controller' => OdemeController::class, 'metot' => 'odemeYapilmadiKapat', 'yetki' => 'odeme_ekle'],
    'odeme_geri_al' => ['controller' => OdemeController::class, 'metot' => 'geriAl', 'yetki' => 'odeme_ekle'],
    'odeme_kasaya_aktar' => ['controller' => OdemeController::class, 'metot' => 'kasayaAktar', 'yetki' => 'odeme_ekle'],
    'odeme_sil' => ['controller' => OdemeController::class, 'metot' => 'sil', 'yetki' => 'odeme_ekle'],
    'cari_veli_ara' => ['controller' => CariController::class, 'metot' => 'veliAra', 'yetki' => 'odeme_listele'],
    'cari_kaydet' => ['controller' => CariController::class, 'metot' => 'kaydet', 'yetki' => 'odeme_ekle'],
    'cari_fatura_olustur' => ['controller' => CariController::class, 'metot' => 'faturaOlustur', 'yetki' => 'odeme_ekle'],
    'fatura_odeme_hazirlik' => ['controller' => FaturaController::class, 'metot' => 'odemeHazirlik', 'yetki' => 'odeme_ekle'],
    'fatura_olustur' => ['controller' => FaturaController::class, 'metot' => 'olustur', 'yetki' => 'odeme_ekle'],
    'fatura_onayla' => ['controller' => FaturaController::class, 'metot' => 'onayla', 'yetki' => 'odeme_ekle'],
    'fatura_toplu_onayla' => ['controller' => FaturaController::class, 'metot' => 'topluOnayla', 'yetki' => 'odeme_ekle'],
    'fatura_arsivle' => ['controller' => FaturaController::class, 'metot' => 'arsivle', 'yetki' => 'odeme_ekle'],
    'fatura_durum_guncelle' => ['controller' => FaturaController::class, 'metot' => 'durumGuncelle', 'yetki' => 'odeme_ekle'],
    'fatura_iptal' => ['controller' => FaturaController::class, 'metot' => 'iptal', 'yetki' => 'odeme_ekle'],
    'fatura_toplu_iptal' => ['controller' => FaturaController::class, 'metot' => 'topluIptal', 'yetki' => 'odeme_ekle'],
    'nes_ayar_kaydet' => ['controller' => FaturaController::class, 'metot' => 'nesAyarKaydet', 'yetki' => 'fatura_entegrasyon_yonet'],
    'nes_baglanti_test' => ['controller' => FaturaController::class, 'metot' => 'nesBaglantiTest', 'yetki' => 'fatura_entegrasyon_yonet'],
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
    'gunluk_not_raporu_olustur' => ['controller' => GunlukKayitController::class, 'metot' => 'raporOlustur', 'yetki' => 'randevu_ekle'],
    'haftalik_tema_listele' => ['controller' => HaftalikTemaController::class, 'metot' => 'liste', 'yetki' => 'tema_yonet'],
    'haftalik_tema_detay' => ['controller' => HaftalikTemaController::class, 'metot' => 'detay', 'yetki' => 'tema_yonet'],
    'haftalik_tema_kaydet' => ['controller' => HaftalikTemaController::class, 'metot' => 'kaydet', 'yetki' => 'tema_yonet'],
    'haftalik_tema_sil' => ['controller' => HaftalikTemaController::class, 'metot' => 'sil', 'yetki' => 'tema_yonet'],
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
    'personel_listele' => ['controller' => PersonelController::class, 'metot' => 'liste', 'yetki' => 'personel_listele'],
    'personel_kaydet' => ['controller' => PersonelController::class, 'metot' => 'kaydet', 'yetki' => 'personel_yonet'],
    'personel_puantaj_kaydet' => ['controller' => PersonelController::class, 'metot' => 'puantajKaydet', 'yetki' => 'personel_yonet'],
    'personel_puantaj_toplu_kaydet' => ['controller' => PersonelController::class, 'metot' => 'puantajTopluKaydet', 'yetki' => 'personel_yonet'],
    'personel_hareket_kaydet' => ['controller' => PersonelController::class, 'metot' => 'hareketKaydet', 'yetki' => 'personel_yonet'],
    'kurum_listele' => ['controller' => KurumController::class, 'metot' => 'liste', 'yetki' => 'sistem_yonetimi'],
    'kurum_kaydet' => ['controller' => KurumController::class, 'metot' => 'kaydet', 'yetki' => 'sistem_yonetimi'],
];

$islem = (string) ($data['islem'] ?? '');
if (!isset($islemHaritasi[$islem])) {
    Response::json(['basari' => false, 'mesaj' => 'Bilinmeyen islem.', 'hatalar' => []], 404);
    exit;
}

$hedef = $islemHaritasi[$islem];
if (!(new KurumModuluServisi())->islemIcinAktifMi($islem, Auth::kurumId())) {
    Response::json(['basari' => false, 'mesaj' => 'Bu sayfa kurum için kapalı.', 'hatalar' => []], 403);
    exit;
}
if (!(new YetkiKontrolu())->kontrol($hedef['yetki'])) {
    Response::json(['basari' => false, 'mesaj' => 'Bu islem icin yetkiniz yok.', 'hatalar' => []], 403);
    exit;
}

$yuksekRiskliIslemler = [
    'fatura_onayla', 'fatura_toplu_onayla', 'fatura_iptal', 'fatura_toplu_iptal',
    'nes_ayar_kaydet', 'kullanici_kaydet', 'kullanici_rol_kaydet', 'personel_kaydet', 'kurum_kaydet',
    'ogrenci_sil', 'paket_sil', 'odeme_geri_al', 'odeme_sil',
    'gider_sil', 'kasa_sil', 'randevu_sil', 'sms_toplu_gonder',
    'sms_baglanti_ayarlari_kaydet', 'sms_hatirlatma_ayarlari_kaydet',
];
if (in_array($islem, $yuksekRiskliIslemler, true)) {
    $kullanici = Auth::user();
    if (!$kullanici || !(new MfaServisi())->yuksekRiskDogrulandiMi($kullanici)) {
        Response::json(['basari' => false, 'mesaj' => 'Bu işlem için yakın zamanda MFA doğrulaması yapmanız gerekir. Lütfen çıkış yapıp yeniden giriş yapın.', 'mfa_gerekli' => true, 'hatalar' => []], 403);
        exit;
    }
}

// Randevu sayfasi liste ve takvim verisini ayni anda ister. PHP'nin dosya tabanli
// oturum kilidi acik kalirsa bu iki salt-okunur istek sunucuda gereksiz yere
// siraya girer. Kimlik, CSRF ve yetki kontrolleri tamamlandiktan sonra kilidi
// birakmak isteklerin gercekten paralel calismasini saglar.
if (
    in_array($islem, ['randevu_listele', 'randevu_takvim'], true)
    && session_status() === PHP_SESSION_ACTIVE
) {
    session_write_close();
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
