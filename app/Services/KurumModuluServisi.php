<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Veritabani;

final class KurumModuluServisi
{
    public const TANIMLAR = [
        'ogrenci_islemleri' => ['ad' => 'Öğrenci İşlemleri', 'sayfalar' => [
            'ogrenciler' => 'Öğrenciler', 'devamlilik_raporu' => 'Devamlılık Raporu',
            'adres_haritasi' => 'Adres Haritası', 'ozel_notlar' => 'Özel Notlar',
            'tedbir_listesi' => 'Tedbir Listesi', 'bekleyen_veliler' => 'Bekleyen Veliler',
            'veli_onamlari' => 'Veli Onamları',
        ]],
        'program' => ['ad' => 'Program', 'sayfalar' => [
            'randevular' => 'Randevular', 'haftalik_program' => 'Haftalık Program',
        ]],
        'finans' => ['ad' => 'Finans', 'sayfalar' => [
            'paketler' => 'Paketler', 'borclu_paketler' => 'Borçlu Paketler',
            'tahsilatlar' => 'Tahsilatlar', 'giderler' => 'Giderler', 'kasalar' => 'Kasalar',
            'cariler' => 'Cariler', 'faturalar' => 'Faturalar',
            'entegrasyon_ayarlari' => 'Entegrasyon Ayarları',
            'gelir_gider' => 'Gelir Gider Analizi', 'raporlar' => 'Raporlar',
        ]],
        'icerik_takip' => ['ad' => 'İçerik ve Takip', 'sayfalar' => [
            'haftalik_temalar' => 'Haftalık Temalar', 'gunluk_kayitlar' => 'Günlük Kayıtlar',
        ]],
        'sms' => ['ad' => 'SMS', 'sayfalar' => [
            'sms_yonetimi' => 'SMS Yönetimi', 'sms_raporlari' => 'SMS Raporları',
        ]],
        'yonetim' => ['ad' => 'Yönetim', 'sayfalar' => [
            'kullanicilar' => 'Kullanıcılar', 'personel_puantaj' => 'Personel ve Puantaj',
        ]],
    ];

    private const YETKI_MODULLERI = [
        'ogrenci_listele' => 'ogrenci_islemleri', 'ogrenci_ekle' => 'ogrenci_islemleri',
        'veli_listele' => 'ogrenci_islemleri', 'veli_ekle' => 'ogrenci_islemleri',
        'bekleyen_veli_listele' => 'ogrenci_islemleri', 'bekleyen_veli_ekle' => 'ogrenci_islemleri',
        'grup_listele' => 'program', 'grup_ekle' => 'program',
        'randevu_listele' => 'program', 'randevu_ekle' => 'program', 'randevu_durum_degistir' => 'program',
        'paket_listele' => 'finans', 'paket_ekle' => 'finans', 'odeme_listele' => 'finans',
        'odeme_ekle' => 'finans', 'rapor_ozet' => 'finans', 'fatura_entegrasyon_yonet' => 'finans',
        'tema_yonet' => 'icerik_takip', 'yoklama_listele' => 'icerik_takip',
        'sms_goruntule' => 'sms', 'sms_rapor_goruntule' => 'sms', 'sms_gonder' => 'sms',
        'sms_toplu_gonder' => 'sms', 'sms_tekrar_gonder' => 'sms', 'sms_sablon_yonet' => 'sms',
        'sms_ayar_yonet' => 'sms', 'kullanici_yonet' => 'yonetim',
        'personel_listele' => 'yonetim', 'personel_yonet' => 'yonetim',
    ];

    private const YETKI_SAYFALARI = [
        'ogrenci_listele' => ['ogrenciler', 'devamlilik_raporu', 'adres_haritasi', 'ozel_notlar', 'tedbir_listesi', 'veli_onamlari'],
        'ogrenci_ekle' => ['ogrenciler', 'devamlilik_raporu', 'adres_haritasi', 'ozel_notlar', 'tedbir_listesi', 'veli_onamlari'],
        'veli_listele' => ['ogrenciler'],
        'veli_ekle' => ['ogrenciler'],
        'bekleyen_veli_listele' => ['bekleyen_veliler'],
        'bekleyen_veli_ekle' => ['bekleyen_veliler'],
        'grup_listele' => ['haftalik_program'],
        'grup_ekle' => ['haftalik_program'],
        'randevu_listele' => ['randevular'],
        'randevu_ekle' => ['randevular'],
        'randevu_durum_degistir' => ['randevular'],
        'paket_listele' => ['paketler', 'borclu_paketler'],
        'paket_ekle' => ['paketler'],
        'odeme_listele' => ['tahsilatlar', 'giderler', 'kasalar'],
        'odeme_ekle' => ['tahsilatlar', 'giderler', 'kasalar'],
        'rapor_ozet' => ['gelir_gider', 'raporlar'],
        'fatura_entegrasyon_yonet' => ['entegrasyon_ayarlari'],
        'tema_yonet' => ['haftalik_temalar'],
        'yoklama_listele' => ['gunluk_kayitlar'],
        'sms_goruntule' => ['sms_yonetimi'],
        'sms_rapor_goruntule' => ['sms_raporlari'],
        'sms_gonder' => ['sms_yonetimi'],
        'sms_toplu_gonder' => ['sms_yonetimi'],
        'sms_tekrar_gonder' => ['sms_yonetimi'],
        'sms_sablon_yonet' => ['sms_yonetimi'],
        'sms_ayar_yonet' => ['sms_yonetimi'],
        'kullanici_yonet' => ['kullanicilar'],
        'personel_listele' => ['personel_puantaj'],
        'personel_yonet' => ['personel_puantaj'],
    ];

    private const YOL_SAYFALARI = [
        '/panel/faturalar/entegrasyon-ayarlari' => 'entegrasyon_ayarlari',
        '/panel/ogrenciler/devamlilik-raporu' => 'devamlilik_raporu',
        '/panel/ogrenciler/adres-haritasi' => 'adres_haritasi',
        '/panel/ogrenciler/ozel-notlar' => 'ozel_notlar',
        '/panel/ogrenciler/tedbir-listesi' => 'tedbir_listesi',
        '/panel/ogrenciler/kara-liste' => 'tedbir_listesi',
        '/panel/odemeler/tahsilat-takibi' => 'borclu_paketler',
        '/panel/odemeler/borclular' => 'borclu_paketler',
        '/panel/odemeler/tahsilatlar' => 'tahsilatlar',
        '/panel/odemeler/giderler' => 'giderler', '/panel/odemeler/kasalar' => 'kasalar',
        '/panel/finans/gelir-gider' => 'gelir_gider', '/panel/veli-onamlari' => 'veli_onamlari',
        '/panel/bekleyen-veliler' => 'bekleyen_veliler', '/panel/onam-formlari' => 'ogrenciler',
        '/panel/ogrenciler' => 'ogrenciler', '/panel/veliler' => 'ogrenciler',
        '/panel/randevular' => 'randevular', '/panel/gruplar' => 'haftalik_program',
        '/panel/paketler' => 'paketler', '/panel/odemeler' => 'tahsilatlar',
        '/panel/cariler' => 'cariler', '/panel/faturalar' => 'faturalar',
        '/panel/raporlar' => 'raporlar', '/panel/haftalik-temalar' => 'haftalik_temalar',
        '/panel/gunluk-kayitlar' => 'gunluk_kayitlar', '/panel/sms/raporlar' => 'sms_raporlari',
        '/panel/sms' => 'sms_yonetimi', '/panel/kullanicilar' => 'kullanicilar',
        '/panel/personeller' => 'personel_puantaj',
    ];

    private static array $onbellek = [];
    private static array $sayfaOnbellek = [];

    public static function sayfaTanimlari(): array
    {
        $sayfalar = [];
        foreach (self::TANIMLAR as $modulKodu => $modul) {
            foreach ($modul['sayfalar'] as $sayfaKodu => $sayfaAdi) {
                $sayfalar[$sayfaKodu] = ['ad' => $sayfaAdi, 'modul' => $modulKodu];
            }
        }
        return $sayfalar;
    }

    public static function yetkiModulu(string $yetki): ?string
    {
        return self::YETKI_MODULLERI[$yetki] ?? null;
    }

    public static function yetkiSayfalari(string $yetki): array
    {
        return self::YETKI_SAYFALARI[$yetki] ?? [];
    }

    public function yetkiIcinAktifMi(string $yetki, ?int $kurumId = null): bool
    {
        $modul = self::YETKI_MODULLERI[$yetki] ?? null;
        return $modul === null || $this->aktifMi($modul, $kurumId);
    }

    public function aktifMi(string $modul, ?int $kurumId = null): bool
    {
        if (!isset(self::TANIMLAR[$modul])) {
            return false;
        }
        $kurumId ??= Auth::kurumId();
        return $kurumId > 0 && (bool) ($this->kurumIcin($kurumId)[$modul] ?? true);
    }

    public function sayfaAktifMi(string $sayfa, ?int $kurumId = null): bool
    {
        $tanim = self::sayfaTanimlari()[$sayfa] ?? null;
        if ($tanim === null) {
            return false;
        }
        $kurumId ??= Auth::kurumId();
        return $kurumId > 0
            && $this->aktifMi($tanim['modul'], $kurumId)
            && (bool) ($this->sayfalarKurumIcin($kurumId)[$sayfa] ?? true);
    }

    public function yolIcinAktifMi(string $yol, ?int $kurumId = null): bool
    {
        foreach (self::YOL_SAYFALARI as $kok => $sayfa) {
            if ($yol === $kok || str_starts_with($yol, $kok . '/')) {
                return $this->sayfaAktifMi($sayfa, $kurumId);
            }
        }
        return true;
    }

    public function islemIcinAktifMi(string $islem, ?int $kurumId = null): bool
    {
        $sayfa = match (true) {
            str_starts_with($islem, 'ogrenci_kara_liste_') => 'tedbir_listesi',
            str_starts_with($islem, 'ogrenci_adres_') => 'adres_haritasi',
            str_starts_with($islem, 'ogrenci_ozel_not_') => 'ozel_notlar',
            str_starts_with($islem, 'bekleyen_veli_') => 'bekleyen_veliler',
            str_starts_with($islem, 'veli_'), str_starts_with($islem, 'ogrenci_'), $islem === 'onam_formu_olustur' => 'ogrenciler',
            str_starts_with($islem, 'grup_') => 'haftalik_program',
            str_starts_with($islem, 'randevu_'), str_starts_with($islem, 'telafi_'), $islem === 'hizli_randevu_ekle' => 'randevular',
            in_array($islem, ['paket_tahsilat_notu_guncelle', 'paket_odeme_plani_guncelle', 'paket_odeme_yapilmadi_kapat'], true) => 'borclu_paketler',
            str_starts_with($islem, 'hizmet_'), str_starts_with($islem, 'paket_disi_hak_'), str_starts_with($islem, 'paket_') => 'paketler',
            str_starts_with($islem, 'odeme_') => 'tahsilatlar', str_starts_with($islem, 'gider_') => 'giderler',
            str_starts_with($islem, 'kasa_') => 'kasalar',
            $islem === 'cari_fatura_olustur', str_starts_with($islem, 'fatura_') => 'faturalar',
            str_starts_with($islem, 'nes_') => 'entegrasyon_ayarlari', str_starts_with($islem, 'cari_') => 'cariler',
            $islem === 'gelir_gider_analizi', $islem === 'ayar_listele' => 'gelir_gider',
            $islem === 'rapor_ozet' => 'raporlar', str_starts_with($islem, 'haftalik_tema_') => 'haftalik_temalar',
            str_starts_with($islem, 'gunluk_not_') => 'gunluk_kayitlar',
            in_array($islem, ['sms_raporlari_listele', 'sms_ogrenci_raporlari', 'sms_rapor_kontrol_et'], true) => 'sms_raporlari',
            str_starts_with($islem, 'sms_') => 'sms_yonetimi', str_starts_with($islem, 'kullanici_') => 'kullanicilar',
            str_starts_with($islem, 'personel_') => 'personel_puantaj', default => null,
        };
        return $sayfa === null || $this->sayfaAktifMi($sayfa, $kurumId);
    }

    public function kurumIcin(int $kurumId): array
    {
        if (isset(self::$onbellek[$kurumId])) {
            return self::$onbellek[$kurumId];
        }
        $sonuc = array_fill_keys(array_keys(self::TANIMLAR), true);
        $stmt = Veritabani::baglan()->prepare('SELECT modul, aktif FROM kurum_modulleri WHERE kurum_id = :kurum_id');
        $stmt->execute(['kurum_id' => $kurumId]);
        foreach ($stmt->fetchAll() as $satir) {
            $modul = (string) ($satir['modul'] ?? '');
            if (isset(self::TANIMLAR[$modul])) {
                $sonuc[$modul] = (int) $satir['aktif'] === 1;
            }
        }
        return self::$onbellek[$kurumId] = $sonuc;
    }

    public function sayfalarKurumIcin(int $kurumId): array
    {
        if (isset(self::$sayfaOnbellek[$kurumId])) {
            return self::$sayfaOnbellek[$kurumId];
        }
        $tanimlar = self::sayfaTanimlari();
        $sonuc = array_fill_keys(array_keys($tanimlar), true);
        $stmt = Veritabani::baglan()->prepare('SELECT sayfa, aktif FROM kurum_modul_sayfalari WHERE kurum_id = :kurum_id');
        $stmt->execute(['kurum_id' => $kurumId]);
        foreach ($stmt->fetchAll() as $satir) {
            $sayfa = (string) ($satir['sayfa'] ?? '');
            if (isset($tanimlar[$sayfa])) {
                $sonuc[$sayfa] = (int) $satir['aktif'] === 1;
            }
        }
        return self::$sayfaOnbellek[$kurumId] = $sonuc;
    }

    public function kaydet(int $kurumId, array $aktifModuller): void
    {
        $secili = array_fill_keys(array_map('strval', $aktifModuller), true);
        $stmt = Veritabani::baglan()->prepare(
            'INSERT INTO kurum_modulleri (kurum_id, modul, aktif, olusturulma_tarihi) VALUES (:kurum_id, :modul, :aktif, NOW())
             ON DUPLICATE KEY UPDATE aktif = VALUES(aktif), guncellenme_tarihi = NOW()'
        );
        foreach (array_keys(self::TANIMLAR) as $modul) {
            $stmt->execute(['kurum_id' => $kurumId, 'modul' => $modul, 'aktif' => isset($secili[$modul]) ? 1 : 0]);
        }
        unset(self::$onbellek[$kurumId]);
    }

    public function sayfalariKaydet(int $kurumId, array $aktifSayfalar): void
    {
        $secili = array_fill_keys(array_map('strval', $aktifSayfalar), true);
        $stmt = Veritabani::baglan()->prepare(
            'INSERT INTO kurum_modul_sayfalari (kurum_id, sayfa, aktif, olusturulma_tarihi) VALUES (:kurum_id, :sayfa, :aktif, NOW())
             ON DUPLICATE KEY UPDATE aktif = VALUES(aktif), guncellenme_tarihi = NOW()'
        );
        foreach (array_keys(self::sayfaTanimlari()) as $sayfa) {
            $stmt->execute(['kurum_id' => $kurumId, 'sayfa' => $sayfa, 'aktif' => isset($secili[$sayfa]) ? 1 : 0]);
        }
        unset(self::$sayfaOnbellek[$kurumId]);
    }
}
