<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class OgrenciAdresHaritasi extends Model
{
    public static function liste(): array
    {
        self::mevcutKonumlariOnbellegeAktar();
        self::onbellektekiKonumlariUygula();

        $stmt = self::db()->prepare(
            'SELECT o.id AS ogrenci_id, CONCAT(o.ad, " ", o.soyad) AS ogrenci,
                    o.il, o.ilce, o.adres, o.adres_enlem, o.adres_boylam,
                    o.adres_konum_dogrulandi, o.adres_konum_guncellenme_tarihi,
                    o.durum AS ogrenci_durumu,
                    EXISTS(
                        SELECT 1
                        FROM randevular r
                        WHERE r.kurum_id = o.kurum_id
                          AND r.ogrenci_id = o.id
                          AND r.durum = "planlandi"
                          AND TIMESTAMP(r.tarih, r.baslangic_saati) >= NOW()
                    ) AS aktif_randevusu_var,
                    COALESCE(CONCAT(v.ad, " ", v.soyad), "-") AS veli,
                    COALESCE(v.telefon, "") AS veli_telefon
             FROM ogrenciler o
             LEFT JOIN ogrenci_velileri ov
                    ON ov.ogrenci_id = o.id AND ov.kurum_id = o.kurum_id AND ov.birincil_mi = 1
             LEFT JOIN veliler v ON v.id = ov.veli_id AND v.kurum_id = o.kurum_id
             WHERE o.kurum_id = :kurum_id
               AND (COALESCE(o.il, "") <> "" OR COALESCE(o.ilce, "") <> "" OR COALESCE(o.adres, "") <> "")
             ORDER BY o.ad ASC, o.soyad ASC'
        );
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function ozet(array $kayitlar): array
    {
        $ilceler = [];
        $dogrulanan = 0;
        foreach ($kayitlar as $kayit) {
            $dogrulandi = !empty($kayit['adres_konum_dogrulandi'])
                && $kayit['adres_enlem'] !== null && $kayit['adres_boylam'] !== null;
            $dogrulanan += $dogrulandi ? 1 : 0;
            $ilce = trim((string) ($kayit['ilce'] ?? '')) ?: 'İlçe belirtilmemiş';
            $ilceler[$ilce] = ($ilceler[$ilce] ?? 0) + 1;
        }
        arsort($ilceler);

        return [
            'adresli' => count($kayitlar),
            'dogrulanan' => $dogrulanan,
            'bekleyen' => count($kayitlar) - $dogrulanan,
            'ilceler' => array_slice($ilceler, 0, 8, true),
        ];
    }

    public static function konumGuncelle(int $ogrenciId, float $enlem, float $boylam, string $beklenenAdresAnahtari = ''): bool
    {
        if (!self::turkiyeSinirlariIcindeMi($enlem, $boylam)) {
            throw new \InvalidArgumentException('Konum Türkiye sınırları dışında görünüyor.');
        }

        $db = self::db();
        $db->beginTransaction();
        try {
            $adresStmt = $db->prepare(
                'SELECT il, ilce, adres FROM ogrenciler
                 WHERE id = :id AND kurum_id = :kurum_id
                 FOR UPDATE'
            );
            $adresStmt->execute(['id' => $ogrenciId, 'kurum_id' => self::kurumId()]);
            $adres = $adresStmt->fetch();
            if (!$adres || (!trim((string) $adres['il']) && !trim((string) $adres['ilce']) && !trim((string) $adres['adres']))) {
                $db->rollBack();
                return false;
            }

            $adresAnahtari = self::adresAnahtari($adres);
            if ($beklenenAdresAnahtari !== '' && !hash_equals($adresAnahtari, $beklenenAdresAnahtari)) {
                throw new \InvalidArgumentException('Adres bu işlem sırasında değişti. Konum yeniden bulunmalıdır.');
            }

            $enlemDegeri = number_format($enlem, 7, '.', '');
            $boylamDegeri = number_format($boylam, 7, '.', '');
            $onbellekStmt = $db->prepare(
                'INSERT INTO adres_konum_onbellegi
                    (kurum_id, adres_anahtari, il, ilce, adres, enlem, boylam, saglayici, son_kullanilma_tarihi)
                 VALUES
                    (:kurum_id, :adres_anahtari, :il, :ilce, :adres, :enlem, :boylam, "google", NOW())
                 ON DUPLICATE KEY UPDATE
                    il = VALUES(il), ilce = VALUES(ilce), adres = VALUES(adres),
                    enlem = VALUES(enlem), boylam = VALUES(boylam),
                    saglayici = VALUES(saglayici), son_kullanilma_tarihi = NOW()'
            );
            $onbellekStmt->execute([
                'kurum_id' => self::kurumId(),
                'adres_anahtari' => $adresAnahtari,
                'il' => trim((string) $adres['il']) ?: null,
                'ilce' => trim((string) $adres['ilce']) ?: null,
                'adres' => trim((string) $adres['adres']) ?: null,
                'enlem' => $enlemDegeri,
                'boylam' => $boylamDegeri,
            ]);

            $stmt = $db->prepare(
                'UPDATE ogrenciler
                 SET adres_enlem = :enlem,
                     adres_boylam = :boylam,
                     adres_konum_dogrulandi = 1,
                     adres_konum_guncellenme_tarihi = NOW()
                 WHERE id = :id AND kurum_id = :kurum_id'
            );
            $stmt->execute([
                'id' => $ogrenciId,
                'kurum_id' => self::kurumId(),
                'enlem' => $enlemDegeri,
                'boylam' => $boylamDegeri,
            ]);
            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function adresAnahtari(array $adres): string
    {
        $parcalar = array_map(static function ($deger): string {
            $deger = str_replace(['I', 'İ'], ['ı', 'i'], trim((string) $deger));
            $deger = mb_strtolower($deger, 'UTF-8');
            return preg_replace('/\s+/u', ' ', $deger) ?? $deger;
        }, [$adres['adres'] ?? '', $adres['ilce'] ?? '', $adres['il'] ?? '']);

        return hash('sha256', implode('|', $parcalar));
    }

    private static function onbellektekiKonumlariUygula(): void
    {
        $db = self::db();
        $stmt = $db->prepare(
            'SELECT id, il, ilce, adres FROM ogrenciler
             WHERE kurum_id = :kurum_id
               AND adres_konum_dogrulandi = 0
               AND COALESCE(adres, "") <> ""'
        );
        $stmt->execute(self::kurumParam());
        $ogrenciler = $stmt->fetchAll();
        if (!$ogrenciler) {
            return;
        }

        $ogrencilerByAnahtar = [];
        foreach ($ogrenciler as $ogrenci) {
            $ogrencilerByAnahtar[self::adresAnahtari($ogrenci)][] = (int) $ogrenci['id'];
        }
        $anahtarlar = array_keys($ogrencilerByAnahtar);
        $yerTutucular = implode(',', array_fill(0, count($anahtarlar), '?'));
        $cacheStmt = $db->prepare(
            "SELECT adres_anahtari, enlem, boylam FROM adres_konum_onbellegi
             WHERE kurum_id = ? AND adres_anahtari IN ({$yerTutucular})"
        );
        $cacheStmt->execute([self::kurumId(), ...$anahtarlar]);
        $guncelleStmt = $db->prepare(
            'UPDATE ogrenciler
             SET adres_enlem = :enlem, adres_boylam = :boylam,
                 adres_konum_dogrulandi = 1, adres_konum_guncellenme_tarihi = NOW()
             WHERE id = :id AND kurum_id = :kurum_id AND adres_konum_dogrulandi = 0'
        );
        foreach ($cacheStmt->fetchAll() as $cache) {
            foreach ($ogrencilerByAnahtar[$cache['adres_anahtari']] ?? [] as $ogrenciId) {
                $guncelleStmt->execute([
                    'id' => $ogrenciId,
                    'kurum_id' => self::kurumId(),
                    'enlem' => $cache['enlem'],
                    'boylam' => $cache['boylam'],
                ]);
            }
        }
    }

    private static function mevcutKonumlariOnbellegeAktar(): void
    {
        $db = self::db();
        $stmt = $db->prepare(
            'SELECT o.il, o.ilce, o.adres, o.adres_enlem, o.adres_boylam
             FROM ogrenciler o
             WHERE o.kurum_id = :kurum_id
               AND o.adres_konum_dogrulandi = 1
               AND o.adres_enlem IS NOT NULL
               AND o.adres_boylam IS NOT NULL
               AND COALESCE(o.adres, "") <> ""'
        );
        $stmt->execute(self::kurumParam());
        $ekleStmt = $db->prepare(
            'INSERT IGNORE INTO adres_konum_onbellegi
                (kurum_id, adres_anahtari, il, ilce, adres, enlem, boylam, saglayici, son_kullanilma_tarihi)
             VALUES
                (:kurum_id, :adres_anahtari, :il, :ilce, :adres, :enlem, :boylam, "google", NOW())'
        );
        foreach ($stmt->fetchAll() as $adres) {
            $ekleStmt->execute([
                'kurum_id' => self::kurumId(),
                'adres_anahtari' => self::adresAnahtari($adres),
                'il' => trim((string) $adres['il']) ?: null,
                'ilce' => trim((string) $adres['ilce']) ?: null,
                'adres' => trim((string) $adres['adres']) ?: null,
                'enlem' => $adres['adres_enlem'],
                'boylam' => $adres['adres_boylam'],
            ]);
        }
    }

    public static function turkiyeSinirlariIcindeMi(float $enlem, float $boylam): bool
    {
        return $enlem >= 35 && $enlem <= 43 && $boylam >= 25 && $boylam <= 45;
    }
}
