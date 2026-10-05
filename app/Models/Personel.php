<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

final class Personel extends Model
{
    public const DURUMLAR = [
        'calisti' => ['kod' => 'X', 'ad' => 'Çalıştı'],
        'hafta_tatili' => ['kod' => 'H', 'ad' => 'Hafta tatili'],
        'raporlu' => ['kod' => 'R', 'ad' => 'Raporlu'],
        'izinli' => ['kod' => 'İ', 'ad' => 'İzinli'],
        'resmi_tatil' => ['kod' => 'RT', 'ad' => 'Resmî tatil'],
        'devamsiz' => ['kod' => 'D', 'ad' => 'Devamsız'],
    ];

    private static bool $semaHazir = false;

    public static function semayiHazirla(): void
    {
        if (self::$semaHazir) {
            return;
        }

        $db = self::db();
        $tabloVardi = (bool) $db->query("SHOW TABLES LIKE 'personeller'")->fetchColumn();
        $db->exec(
            'CREATE TABLE IF NOT EXISTS personeller (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                kurum_id BIGINT UNSIGNED NOT NULL,
                kullanici_id BIGINT UNSIGNED NULL,
                tc_kimlik_no VARCHAR(11) NULL,
                ad VARCHAR(120) NOT NULL,
                soyad VARCHAR(120) NOT NULL,
                pozisyon VARCHAR(160) NULL,
                aylik_brut_ucret DECIMAL(12,2) NOT NULL DEFAULT 0,
                sgk_tesvik_turu VARCHAR(30) NOT NULL DEFAULT "diger_2_puan",
                ise_giris_tarihi DATE NULL,
                isten_cikis_tarihi DATE NULL,
                varsayilan_giris_saati TIME NULL,
                varsayilan_cikis_saati TIME NULL,
                aktif TINYINT(1) NOT NULL DEFAULT 1,
                notlar TEXT NULL,
                olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_personel_tc (kurum_id, tc_kimlik_no),
                UNIQUE KEY uq_personel_kullanici (kurum_id, kullanici_id),
                KEY idx_personel_aktif (kurum_id, aktif, ad, soyad),
                CONSTRAINT fk_personel_kurum FOREIGN KEY (kurum_id) REFERENCES kurumlar(id) ON DELETE CASCADE,
                CONSTRAINT fk_personel_kullanici FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        self::kolonEkle($db, 'personeller', 'aylik_brut_ucret', 'aylik_brut_ucret DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER pozisyon');
        self::kolonEkle($db, 'personeller', 'sgk_tesvik_turu', 'sgk_tesvik_turu VARCHAR(30) NOT NULL DEFAULT \'diger_2_puan\' AFTER aylik_brut_ucret');
        $db->exec(
            'CREATE TABLE IF NOT EXISTS personel_puantajlari (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                kurum_id BIGINT UNSIGNED NOT NULL,
                personel_id BIGINT UNSIGNED NOT NULL,
                tarih DATE NOT NULL,
                durum VARCHAR(30) NOT NULL DEFAULT "calisti",
                giris_saati TIME NULL,
                cikis_saati TIME NULL,
                mola_dakika SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                aciklama VARCHAR(500) NULL,
                kaydeden_kullanici_id BIGINT UNSIGNED NULL,
                olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_personel_puantaj (kurum_id, personel_id, tarih),
                KEY idx_puantaj_tarih (kurum_id, tarih, durum),
                CONSTRAINT fk_puantaj_kurum FOREIGN KEY (kurum_id) REFERENCES kurumlar(id) ON DELETE CASCADE,
                CONSTRAINT fk_puantaj_personel FOREIGN KEY (personel_id) REFERENCES personeller(id) ON DELETE CASCADE,
                CONSTRAINT fk_puantaj_kaydeden FOREIGN KEY (kaydeden_kullanici_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        if (!$tabloVardi) {
            $db->exec(
                "INSERT IGNORE INTO rol_yetkileri (rol_id, yetki, olusturulma_tarihi)
                 SELECT id, 'personel_listele', NOW() FROM roller WHERE kod IN ('yonetici', 'muhasebe')"
            );
            $db->exec(
                "INSERT IGNORE INTO rol_yetkileri (rol_id, yetki, olusturulma_tarihi)
                 SELECT id, 'personel_yonet', NOW() FROM roller WHERE kod = 'yonetici'"
            );
        }
        self::$semaHazir = true;
    }

    public static function liste(bool $yalnizAktif = false): array
    {
        self::semayiHazirla();
        $sql = 'SELECT p.*, CONCAT(k.ad, " ", k.soyad) AS kullanici_adi
                FROM personeller p
                LEFT JOIN kullanicilar k ON k.id = p.kullanici_id AND k.kurum_id = p.kurum_id
                WHERE p.kurum_id = :kurum_id';
        if ($yalnizAktif) {
            $sql .= ' AND p.aktif = 1';
        }
        $sql .= ' ORDER BY p.aktif DESC, p.ad, p.soyad';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function kullaniciSecenekleri(): array
    {
        $stmt = self::db()->prepare(
            'SELECT k.id, k.ad, k.soyad, r.ad AS rol_adi
             FROM kullanicilar k
             INNER JOIN roller r ON r.id = k.rol_id
             WHERE k.kurum_id = :kurum_id AND k.aktif = 1
             ORDER BY k.ad, k.soyad'
        );
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function kaydet(int $id, array $veri): int
    {
        self::semayiHazirla();
        $params = [
            'kurum_id' => self::kurumId(),
            'kullanici_id' => ($veri['kullanici_id'] ?? 0) > 0 ? (int) $veri['kullanici_id'] : null,
            'tc_kimlik_no' => ($veri['tc_kimlik_no'] ?? '') !== '' ? (string) $veri['tc_kimlik_no'] : null,
            'ad' => (string) $veri['ad'],
            'soyad' => (string) $veri['soyad'],
            'pozisyon' => ($veri['pozisyon'] ?? '') !== '' ? (string) $veri['pozisyon'] : null,
            'aylik_brut_ucret' => round(max(0, (float) ($veri['aylik_brut_ucret'] ?? 0)), 2),
            'sgk_tesvik_turu' => (string) ($veri['sgk_tesvik_turu'] ?? 'diger_2_puan'),
            'ise_giris_tarihi' => ($veri['ise_giris_tarihi'] ?? '') !== '' ? (string) $veri['ise_giris_tarihi'] : null,
            'isten_cikis_tarihi' => ($veri['isten_cikis_tarihi'] ?? '') !== '' ? (string) $veri['isten_cikis_tarihi'] : null,
            'varsayilan_giris_saati' => ($veri['varsayilan_giris_saati'] ?? '') !== '' ? (string) $veri['varsayilan_giris_saati'] : null,
            'varsayilan_cikis_saati' => ($veri['varsayilan_cikis_saati'] ?? '') !== '' ? (string) $veri['varsayilan_cikis_saati'] : null,
            'aktif' => (int) ($veri['aktif'] ?? 1),
            'notlar' => ($veri['notlar'] ?? '') !== '' ? (string) $veri['notlar'] : null,
        ];

        if ($id > 0) {
            $params['id'] = $id;
            $stmt = self::db()->prepare(
                'UPDATE personeller SET kullanici_id=:kullanici_id, tc_kimlik_no=:tc_kimlik_no,
                    ad=:ad, soyad=:soyad, pozisyon=:pozisyon, aylik_brut_ucret=:aylik_brut_ucret,
                    sgk_tesvik_turu=:sgk_tesvik_turu, ise_giris_tarihi=:ise_giris_tarihi,
                    isten_cikis_tarihi=:isten_cikis_tarihi, varsayilan_giris_saati=:varsayilan_giris_saati,
                    varsayilan_cikis_saati=:varsayilan_cikis_saati, aktif=:aktif, notlar=:notlar
                 WHERE id=:id AND kurum_id=:kurum_id'
            );
            $stmt->execute($params);
            return $id;
        }

        $stmt = self::db()->prepare(
            'INSERT INTO personeller
                (kurum_id,kullanici_id,tc_kimlik_no,ad,soyad,pozisyon,aylik_brut_ucret,sgk_tesvik_turu,ise_giris_tarihi,isten_cikis_tarihi,
                 varsayilan_giris_saati,varsayilan_cikis_saati,aktif,notlar,olusturulma_tarihi)
             VALUES
                (:kurum_id,:kullanici_id,:tc_kimlik_no,:ad,:soyad,:pozisyon,:aylik_brut_ucret,:sgk_tesvik_turu,:ise_giris_tarihi,:isten_cikis_tarihi,
                 :varsayilan_giris_saati,:varsayilan_cikis_saati,:aktif,:notlar,NOW())'
        );
        $stmt->execute($params);
        return (int) self::db()->lastInsertId();
    }

    public static function kullaniciKurumdaMi(int $kullaniciId): bool
    {
        if ($kullaniciId < 1) {
            return true;
        }
        $stmt = self::db()->prepare('SELECT 1 FROM kullanicilar WHERE id=:id AND kurum_id=:kurum_id LIMIT 1');
        $stmt->execute(['id' => $kullaniciId, 'kurum_id' => self::kurumId()]);
        return (bool) $stmt->fetchColumn();
    }

    public static function kurumPersoneliMi(int $personelId): bool
    {
        self::semayiHazirla();
        $stmt = self::db()->prepare('SELECT 1 FROM personeller WHERE id=:id AND kurum_id=:kurum_id LIMIT 1');
        $stmt->execute(['id' => $personelId, 'kurum_id' => self::kurumId()]);
        return (bool) $stmt->fetchColumn();
    }

    public static function kurumPersonelleriMi(array $personelIds): bool
    {
        self::semayiHazirla();
        $personelIds = array_values(array_unique(array_filter(array_map('intval', $personelIds), static fn(int $id): bool => $id > 0)));
        if ($personelIds === []) {
            return false;
        }
        $yerTutucular = implode(',', array_fill(0, count($personelIds), '?'));
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM personeller WHERE kurum_id=? AND id IN (' . $yerTutucular . ')');
        $stmt->execute(array_merge([self::kurumId()], $personelIds));
        return (int) $stmt->fetchColumn() === count($personelIds);
    }

    public static function gunlukListe(string $tarih): array
    {
        self::semayiHazirla();
        $stmt = self::db()->prepare(
            'SELECT p.id AS personel_id, p.tc_kimlik_no, p.ad, p.soyad, p.pozisyon,
                    p.varsayilan_giris_saati, p.varsayilan_cikis_saati,
                    pp.id AS puantaj_id, pp.durum, pp.giris_saati, pp.cikis_saati,
                    pp.mola_dakika, pp.aciklama,
                    CASE WHEN pp.giris_saati IS NOT NULL AND pp.cikis_saati IS NOT NULL
                         THEN GREATEST(0, FLOOR((TIME_TO_SEC(pp.cikis_saati) - TIME_TO_SEC(pp.giris_saati)
                              + IF(pp.cikis_saati < pp.giris_saati, 86400, 0)) / 60) - pp.mola_dakika)
                         ELSE NULL END AS calisma_dakika
             FROM personeller p
             LEFT JOIN personel_puantajlari pp ON pp.personel_id=p.id AND pp.kurum_id=p.kurum_id AND pp.tarih=:tarih
             WHERE p.kurum_id=:kurum_id AND p.aktif=1
             ORDER BY p.ad,p.soyad'
        );
        $stmt->execute(['kurum_id' => self::kurumId(), 'tarih' => $tarih]);
        return $stmt->fetchAll();
    }

    public static function aylikVeri(string $ay): array
    {
        self::semayiHazirla();
        $baslangic = $ay . '-01';
        $bitis = date('Y-m-t', strtotime($baslangic));
        $personeller = self::liste(true);
        $stmt = self::db()->prepare(
            'SELECT personel_id,tarih,durum,giris_saati,cikis_saati,mola_dakika,aciklama
             FROM personel_puantajlari
             WHERE kurum_id=:kurum_id AND tarih BETWEEN :baslangic AND :bitis
             ORDER BY tarih'
        );
        $stmt->execute(['kurum_id' => self::kurumId(), 'baslangic' => $baslangic, 'bitis' => $bitis]);
        $kayitlar = [];
        foreach ($stmt->fetchAll() as $row) {
            $kayitlar[(int) $row['personel_id']][(string) $row['tarih']] = $row;
        }
        return ['ay' => $ay, 'baslangic' => $baslangic, 'bitis' => $bitis, 'personeller' => $personeller, 'kayitlar' => $kayitlar];
    }

    public static function yillikPuantajKayitlari(int $yil): array
    {
        self::semayiHazirla();
        $stmt = self::db()->prepare(
            'SELECT personel_id,tarih,durum,giris_saati,cikis_saati,mola_dakika,aciklama
             FROM personel_puantajlari
             WHERE kurum_id=:kurum_id AND tarih BETWEEN :baslangic AND :bitis
             ORDER BY tarih'
        );
        $stmt->execute(['kurum_id' => self::kurumId(), 'baslangic' => $yil . '-01-01', 'bitis' => $yil . '-12-31']);
        $kayitlar = [];
        foreach ($stmt->fetchAll() as $row) {
            $kayitlar[(int) $row['personel_id']][(string) $row['tarih']] = $row;
        }
        return $kayitlar;
    }

    public static function puantajKaydet(array $veri, ?int $kaydedenId): void
    {
        self::semayiHazirla();
        $stmt = self::db()->prepare(
            'INSERT INTO personel_puantajlari
                (kurum_id,personel_id,tarih,durum,giris_saati,cikis_saati,mola_dakika,aciklama,kaydeden_kullanici_id,olusturulma_tarihi)
             VALUES
                (:kurum_id,:personel_id,:tarih,:durum,:giris_saati,:cikis_saati,:mola_dakika,:aciklama,:kaydeden,NOW())
             ON DUPLICATE KEY UPDATE durum=VALUES(durum),giris_saati=VALUES(giris_saati),
                cikis_saati=VALUES(cikis_saati),mola_dakika=VALUES(mola_dakika),aciklama=VALUES(aciklama),
                kaydeden_kullanici_id=VALUES(kaydeden_kullanici_id)'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'personel_id' => (int) $veri['personel_id'],
            'tarih' => (string) $veri['tarih'],
            'durum' => (string) $veri['durum'],
            'giris_saati' => ($veri['giris_saati'] ?? '') !== '' ? (string) $veri['giris_saati'] : null,
            'cikis_saati' => ($veri['cikis_saati'] ?? '') !== '' ? (string) $veri['cikis_saati'] : null,
            'mola_dakika' => max(0, min(1440, (int) ($veri['mola_dakika'] ?? 0))),
            'aciklama' => ($veri['aciklama'] ?? '') !== '' ? (string) $veri['aciklama'] : null,
            'kaydeden' => $kaydedenId,
        ]);
    }

    public static function puantajTopluKaydet(array $kayitlar, string $durum, ?int $kaydedenId): int
    {
        self::semayiHazirla();
        $db = self::db();
        $stmt = $db->prepare(
            'INSERT INTO personel_puantajlari
                (kurum_id,personel_id,tarih,durum,giris_saati,cikis_saati,mola_dakika,aciklama,kaydeden_kullanici_id,olusturulma_tarihi)
             VALUES
                (:kurum_id,:personel_id,:tarih,:durum,NULL,NULL,0,NULL,:kaydeden,NOW())
             ON DUPLICATE KEY UPDATE durum=VALUES(durum),kaydeden_kullanici_id=VALUES(kaydeden_kullanici_id)'
        );
        $db->beginTransaction();
        try {
            foreach ($kayitlar as $kayit) {
                $stmt->execute([
                    'kurum_id' => self::kurumId(),
                    'personel_id' => (int) $kayit['personel_id'],
                    'tarih' => (string) $kayit['tarih'],
                    'durum' => $durum,
                    'kaydeden' => $kaydedenId,
                ]);
            }
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
        return count($kayitlar);
    }

    public static function hareketKaydet(int $personelId, string $tur, ?int $kaydedenId): void
    {
        self::semayiHazirla();
        $alan = $tur === 'cikis' ? 'cikis_saati' : 'giris_saati';
        $stmt = self::db()->prepare(
            'INSERT INTO personel_puantajlari
                (kurum_id,personel_id,tarih,durum,' . $alan . ',kaydeden_kullanici_id,olusturulma_tarihi)
             VALUES (:kurum_id,:personel_id,CURDATE(),"calisti",CURTIME(),:kaydeden,NOW())
             ON DUPLICATE KEY UPDATE durum="calisti",' . $alan . '=CURTIME(),kaydeden_kullanici_id=VALUES(kaydeden_kullanici_id)'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'personel_id' => $personelId,
            'kaydeden' => $kaydedenId,
        ]);
    }

    private static function kolonEkle(PDO $db, string $tablo, string $kolon, string $tanim): void
    {
        $stmt = $db->query('SHOW COLUMNS FROM `' . $tablo . '` LIKE ' . $db->quote($kolon));
        if ($stmt->fetch()) {
            return;
        }
        try {
            $db->exec('ALTER TABLE `' . $tablo . '` ADD COLUMN ' . $tanim);
        } catch (\Throwable $e) {
            if (stripos($e->getMessage(), 'Duplicate column') === false) {
                throw $e;
            }
        }
    }
}
