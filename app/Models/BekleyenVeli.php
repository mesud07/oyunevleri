<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class BekleyenVeli extends Model
{
    public static function liste(): array
    {
        self::ensureSchema();

        $stmt = self::db()->prepare(
            'SELECT id, ogrenci_id, ogrenci_ad_soyad, ogrenci_dogum_tarihi, veli_ad_soyad, veli_telefon, veli_eposta,
                    beklenen_gun, ay_grubu, iletisim_referansi, zaman_tercihi, durum, notlar, olusturulma_tarihi
             FROM bekleyen_veliler
             WHERE kurum_id = :kurum_id
             ORDER BY FIELD(durum, "bekliyor", "iletisime_gecildi", "kayda_donustu", "iptal"),
                      olusturulma_tarihi DESC,
                      id DESC
             LIMIT 300'
        );
        $stmt->execute(self::kurumParam());

        return self::ayYaslariniEkle($stmt->fetchAll());
    }

    public static function ekle(array $veri): int
    {
        self::ensureSchema();

        $stmt = self::db()->prepare(
            'INSERT INTO bekleyen_veliler
                (kurum_id, ogrenci_ad_soyad, ogrenci_dogum_tarihi, veli_ad_soyad, veli_telefon, veli_eposta,
                 beklenen_gun, ay_grubu, iletisim_referansi, zaman_tercihi, durum, notlar, olusturan_kullanici_id, olusturulma_tarihi)
             VALUES
                (:kurum_id, :ogrenci_ad_soyad, :ogrenci_dogum_tarihi, :veli_ad_soyad, :veli_telefon, :veli_eposta,
                 :beklenen_gun, :ay_grubu, :iletisim_referansi, :zaman_tercihi, "bekliyor", :notlar, :olusturan_kullanici_id, NOW())'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'ogrenci_ad_soyad' => $veri['ogrenci_ad_soyad'],
            'ogrenci_dogum_tarihi' => $veri['ogrenci_dogum_tarihi'] ?: null,
            'veli_ad_soyad' => $veri['veli_ad_soyad'],
            'veli_telefon' => $veri['veli_telefon'],
            'veli_eposta' => $veri['veli_eposta'] ?: null,
            'beklenen_gun' => $veri['beklenen_gun'] ?: null,
            'ay_grubu' => $veri['ay_grubu'] ?: null,
            'iletisim_referansi' => $veri['iletisim_referansi'] ?: null,
            'zaman_tercihi' => $veri['zaman_tercihi'] ?: 'farketmez',
            'notlar' => $veri['notlar'] ?: null,
            'olusturan_kullanici_id' => $veri['olusturan_kullanici_id'] ?: null,
        ]);

        return (int) self::db()->lastInsertId();
    }

    public static function durumGuncelle(int $id, string $durum): bool
    {
        self::ensureSchema();

        if (!in_array($durum, self::durumlar(), true)) {
            return false;
        }

        $stmt = self::db()->prepare(
            'UPDATE bekleyen_veliler
             SET durum = :durum, guncellenme_tarihi = NOW()
             WHERE id = :id AND kurum_id = :kurum_id'
        );
        $stmt->execute(['id' => $id, 'durum' => $durum, 'kurum_id' => self::kurumId()]);

        return $stmt->rowCount() > 0;
    }

    public static function sil(int $id): bool
    {
        self::ensureSchema();

        $stmt = self::db()->prepare('DELETE FROM bekleyen_veliler WHERE id = :id AND kurum_id = :kurum_id');
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);

        return $stmt->rowCount() > 0;
    }

    public static function bul(int $id): ?array
    {
        self::ensureSchema();

        $stmt = self::db()->prepare(
            'SELECT id, ogrenci_id, ogrenci_ad_soyad, ogrenci_dogum_tarihi, veli_ad_soyad, veli_telefon, veli_eposta,
                    beklenen_gun, ay_grubu, iletisim_referansi, zaman_tercihi, durum, notlar, olusturulma_tarihi
             FROM bekleyen_veliler
             WHERE id = :id
               AND kurum_id = :kurum_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $kayit = $stmt->fetch();

        return $kayit ? self::ayYasiniEkle($kayit) : null;
    }

    public static function ogrenciyeDonustur(int $id): int
    {
        $kayit = self::bul($id);
        if (!$kayit) {
            return 0;
        }

        $mevcutOgrenciId = (int) ($kayit['ogrenci_id'] ?? 0);
        if ($mevcutOgrenciId > 0) {
            self::donusumBaginiGuncelle($id, $mevcutOgrenciId);
            return $mevcutOgrenciId;
        }

        [$ogrenciAd, $ogrenciSoyad] = self::adSoyadAyir((string) $kayit['ogrenci_ad_soyad']);
        [$veliAd, $veliSoyad] = self::adSoyadAyir((string) $kayit['veli_ad_soyad']);

        if ($ogrenciAd === '' || $veliAd === '') {
            return 0;
        }

        $notlar = array_filter([
            'Bekleyen veli listesinden aktif ogrenciye aktarildi.',
            $kayit['beklenen_gun'] ? 'Bekledigi gun: ' . $kayit['beklenen_gun'] : '',
            $kayit['ay_grubu'] ? 'Ay grubu: ' . $kayit['ay_grubu'] : '',
            $kayit['zaman_tercihi'] ? 'Zaman tercihi: ' . $kayit['zaman_tercihi'] : '',
            $kayit['notlar'] ? 'Not: ' . $kayit['notlar'] : '',
        ]);

        $ogrenciId = Ogrenci::veliIleEkle([
            'ogrenci' => [
                'ad' => $ogrenciAd,
                'soyad' => $ogrenciSoyad ?: '-',
                'tc_kimlik_no' => '',
                'dogum_tarihi' => (string) ($kayit['ogrenci_dogum_tarihi'] ?? ''),
                'cinsiyet' => 'belirtilmedi',
                'kayit_tarihi' => date('Y-m-d'),
                'acil_durum_kisi' => '',
                'acil_durum_telefon' => '',
                'saglik_bilgisi' => '',
                'alerji_bilgisi' => '',
                'ozel_durum_notu' => implode("\n", $notlar),
                'vasi_ad_soyad' => '',
                'vasi_tc_kimlik_no' => '',
                'vasi_telefon' => '',
                'yonetici_notu' => '',
                'ogretmen_notu' => '',
            ],
            'veli' => [
                'ad' => $veliAd,
                'soyad' => $veliSoyad ?: '-',
                'tc_kimlik_no' => '',
                'telefon_ulke' => 'Turkiye',
                'telefon' => (string) $kayit['veli_telefon'],
                'yedek_telefon' => '',
                'eposta' => (string) ($kayit['veli_eposta'] ?? ''),
                'yakinlik' => '',
                'il' => '',
                'ilce' => '',
                'adres' => '',
                'iletisim_referansi' => (string) ($kayit['iletisim_referansi'] ?? ''),
                'notlar' => (string) ($kayit['notlar'] ?? ''),
            ],
        ]);

        self::donusumBaginiGuncelle($id, $ogrenciId);

        return $ogrenciId;
    }

    public static function durumlar(): array
    {
        return ['bekliyor', 'iletisime_gecildi', 'kayda_donustu', 'iptal'];
    }

    private static function donusumBaginiGuncelle(int $id, int $ogrenciId): void
    {
        $stmt = self::db()->prepare(
            'UPDATE bekleyen_veliler
             SET ogrenci_id = :ogrenci_id, durum = "kayda_donustu", guncellenme_tarihi = NOW()
             WHERE id = :id AND kurum_id = :kurum_id'
        );
        $stmt->execute(['id' => $id, 'ogrenci_id' => $ogrenciId, 'kurum_id' => self::kurumId()]);
    }

    private static function adSoyadAyir(string $adSoyad): array
    {
        $parcalar = preg_split('/\s+/', trim($adSoyad)) ?: [];
        $parcalar = array_values(array_filter($parcalar));

        if (count($parcalar) <= 1) {
            return [$parcalar[0] ?? '', ''];
        }

        $soyad = array_pop($parcalar);
        return [implode(' ', $parcalar), $soyad];
    }

    private static function ayYaslariniEkle(array $kayitlar): array
    {
        foreach ($kayitlar as $index => $kayit) {
            $kayitlar[$index] = self::ayYasiniEkle($kayit);
        }

        return $kayitlar;
    }

    private static function ayYasiniEkle(array $kayit): array
    {
        $kayit['ogrenci_ay_yasi'] = self::ayYasi($kayit['ogrenci_dogum_tarihi'] ?? null);

        return $kayit;
    }

    private static function ayYasi(?string $dogumTarihi): ?int
    {
        if (!$dogumTarihi) {
            return null;
        }

        try {
            $dogum = new \DateTimeImmutable($dogumTarihi);
            $bugun = new \DateTimeImmutable(date('Y-m-d'));
        } catch (\Throwable $e) {
            return null;
        }

        if ($dogum > $bugun) {
            return null;
        }

        $ay = (((int) $bugun->format('Y') - (int) $dogum->format('Y')) * 12)
            + ((int) $bugun->format('m') - (int) $dogum->format('m'));

        if ((int) $bugun->format('d') < (int) $dogum->format('d')) {
            $ay--;
        }

        return max(0, $ay);
    }

    private static function ensureSchema(): void
    {
        $db = self::db();
        $db->exec(
            'CREATE TABLE IF NOT EXISTS bekleyen_veliler (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              kurum_id INT UNSIGNED NOT NULL DEFAULT 1,
              ogrenci_id BIGINT UNSIGNED NULL,
              ogrenci_ad_soyad VARCHAR(160) NOT NULL,
              ogrenci_dogum_tarihi DATE NULL,
              veli_ad_soyad VARCHAR(160) NOT NULL,
              veli_telefon VARCHAR(32) NOT NULL,
              veli_eposta VARCHAR(160) NULL,
              beklenen_gun VARCHAR(20) NULL,
              ay_grubu VARCHAR(80) NULL,
              iletisim_referansi VARCHAR(190) NULL,
              zaman_tercihi ENUM("hafta_ici","hafta_sonu","farketmez") NOT NULL DEFAULT "farketmez",
              durum ENUM("bekliyor","iletisime_gecildi","kayda_donustu","iptal") NOT NULL DEFAULT "bekliyor",
              notlar TEXT NULL,
              olusturan_kullanici_id BIGINT UNSIGNED NULL,
              olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              guncellenme_tarihi DATETIME NULL,
              INDEX idx_bekleyen_veliler_ogrenci (ogrenci_id),
              INDEX idx_bekleyen_veliler_durum (durum),
              INDEX idx_bekleyen_veliler_telefon (veli_telefon),
              INDEX idx_bekleyen_veliler_gun (beklenen_gun)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        if (!self::kolonVarMi('ogrenci_id')) {
            $db->exec('ALTER TABLE bekleyen_veliler ADD COLUMN ogrenci_id BIGINT UNSIGNED NULL AFTER id');
        }
        if (!self::kolonVarMi('kurum_id')) {
            $db->exec('ALTER TABLE bekleyen_veliler ADD COLUMN kurum_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id');
        }
        if (!self::kolonVarMi('iletisim_referansi')) {
            $db->exec('ALTER TABLE bekleyen_veliler ADD COLUMN iletisim_referansi VARCHAR(190) NULL AFTER ay_grubu');
        }
    }

    private static function kolonVarMi(string $kolon): bool
    {
        $stmt = self::db()->prepare(
            'SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = "bekleyen_veliler"
               AND COLUMN_NAME = :kolon'
        );
        $stmt->execute(['kolon' => $kolon]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
