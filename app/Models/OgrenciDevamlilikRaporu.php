<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Services\DevamlilikRaporuServisi;

final class OgrenciDevamlilikRaporu extends Model
{
    private static ?bool $gelisimTestiTablosuVar = null;

    public static function gelisimTestiTablosuVarMi(): bool
    {
        if (self::$gelisimTestiTablosuVar !== null) {
            return self::$gelisimTestiTablosuVar;
        }

        $stmt = self::db()->query("SHOW TABLES LIKE 'ogrenci_gelisim_testleri'");
        self::$gelisimTestiTablosuVar = (bool) ($stmt && $stmt->fetchColumn());

        return self::$gelisimTestiTablosuVar;
    }

    public static function liste(string $arama = ''): array
    {
        $params = ['kurum_id' => self::kurumId()];
        $where = '';
        $arama = trim($arama);
        if ($arama !== '') {
            $where = 'WHERE CONCAT(rapor.ad, " ", rapor.soyad) LIKE :arama';
            $params['arama'] = '%' . $arama . '%';
        }

        $testSelect = '0 AS gelisim_testi_uygulandi, NULL AS gelisim_testi_tarihi,
                       NULL AS gelisim_testi_notu, NULL AS gelisim_testi_guncellenme_tarihi,
                       NULL AS gelisim_testi_kaydeden';
        $testJoin = '';
        if (self::gelisimTestiTablosuVarMi()) {
            $testSelect = 'COALESCE(ogt.uygulandi, 0) AS gelisim_testi_uygulandi,
                           ogt.uygulama_tarihi AS gelisim_testi_tarihi,
                           ogt.notlar AS gelisim_testi_notu,
                           ogt.guncellenme_tarihi AS gelisim_testi_guncellenme_tarihi,
                           COALESCE(CONCAT(k.ad, " ", k.soyad), "-") AS gelisim_testi_kaydeden';
            $testJoin = 'LEFT JOIN ogrenci_gelisim_testleri ogt
                           ON ogt.ogrenci_id = rapor.ogrenci_id AND ogt.kurum_id = rapor.kurum_id
                         LEFT JOIN kullanicilar k ON k.id = ogt.guncelleyen_kullanici_id AND k.kurum_id = ogt.kurum_id';
        }

        $sql = 'SELECT rapor.*,
                       CASE
                           WHEN rapor.ilk_paket_baslangic_tarihi IS NULL THEN NULL
                           ELSE GREATEST(0, TIMESTAMPDIFF(MONTH, rapor.ilk_paket_baslangic_tarihi, CURDATE()))
                       END AS kayitli_ay_sayisi,
                       CASE
                           WHEN rapor.ilk_paket_baslangic_tarihi IS NULL THEN NULL
                           ELSE GREATEST(0, DATEDIFF(CURDATE(), rapor.ilk_paket_baslangic_tarihi))
                       END AS kayitli_gun_sayisi,
                       ' . $testSelect . '
                FROM (
                    SELECT o.kurum_id, o.id AS ogrenci_id, o.ad, o.soyad, o.durum AS ogrenci_durumu,
                           (SELECT MIN(p.baslangic_tarihi)
                            FROM paketler p
                            WHERE p.ogrenci_id = o.id AND p.kurum_id = o.kurum_id) AS ilk_paket_baslangic_tarihi,
                           (SELECT MAX(COALESCE(p.tahmini_son_ders_tarihi, p.baslangic_tarihi))
                            FROM paketler p
                            WHERE p.ogrenci_id = o.id AND p.kurum_id = o.kurum_id) AS son_paket_tarihi,
                           COUNT(r.id) AS toplam_randevu,
                           SUM(CASE WHEN r.durum IN ("geldi", "tamamlandi") THEN 1 ELSE 0 END) AS katildigi_ders,
                           SUM(CASE WHEN r.durum IN ("gelmedi", "mazeretli_gelmedi", "gec_iptal") THEN 1 ELSE 0 END) AS katilmadigi_ders,
                           SUM(CASE WHEN r.durum = "planlandi"
                                        AND TIMESTAMP(r.tarih, r.baslangic_saati) >= NOW()
                                    THEN 1 ELSE 0 END) AS gelecek_randevu,
                           MAX(CASE WHEN r.durum IN ("geldi", "tamamlandi") THEN r.tarih END) AS son_katilim_tarihi,
                           MAX(r.tarih) AS son_randevu_tarihi
                    FROM ogrenciler o
                    INNER JOIN randevular r ON r.ogrenci_id = o.id AND r.kurum_id = o.kurum_id
                    WHERE o.kurum_id = :kurum_id
                    GROUP BY o.kurum_id, o.id, o.ad, o.soyad, o.durum
                ) rapor
                ' . $testJoin . '
                ' . $where . '
                ORDER BY katildigi_ders DESC, rapor.ad ASC, rapor.soyad ASC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        $kayitlar = $stmt->fetchAll();

        $ogrenciIds = [];
        foreach ($kayitlar as &$kayit) {
            $kayit['gelisim_testine_uygun'] = DevamlilikRaporuServisi::gelisimTestineUygunMu($kayit['ilk_paket_baslangic_tarihi'] ?? null);
            $kayit['gelisim_testine_kalan_gun'] = DevamlilikRaporuServisi::gelisimTestineKalanGun($kayit['ilk_paket_baslangic_tarihi'] ?? null);
            $kayit['pasif_ogrenci'] = DevamlilikRaporuServisi::pasifOgrenciMi($kayit['son_paket_tarihi'] ?? null);
            $kayit['notlar'] = [];
            $ogrenciIds[] = (int) $kayit['ogrenci_id'];
        }
        unset($kayit);

        if ($ogrenciIds) {
            $yerTutucular = implode(',', array_fill(0, count($ogrenciIds), '?'));
            $notStmt = self::db()->prepare(
                'SELECT gn.ogrenci_id, gn.tarih, gn.kategori, gn.not_metni, gn.olusturulma_tarihi,
                        COALESCE(CONCAT(k.ad, " ", k.soyad), "-") AS kaydeden
                 FROM gunluk_notlar gn
                 LEFT JOIN kullanicilar k ON k.id = gn.olusturan_kullanici_id AND k.kurum_id = gn.kurum_id
                 WHERE gn.kurum_id = ? AND gn.ogrenci_id IN (' . $yerTutucular . ')
                 ORDER BY gn.tarih DESC, gn.olusturulma_tarihi DESC, gn.id DESC'
            );
            $notStmt->execute(array_merge([self::kurumId()], $ogrenciIds));
            $notlar = [];
            foreach ($notStmt->fetchAll() as $not) {
                $notlar[(int) $not['ogrenci_id']][] = $not;
            }
            foreach ($kayitlar as &$kayit) {
                $kayit['notlar'] = $notlar[(int) $kayit['ogrenci_id']] ?? [];
            }
            unset($kayit);
        }

        return $kayitlar;
    }

    public static function ozet(array $kayitlar): array
    {
        $ozet = [
            'ogrenci_sayisi' => count($kayitlar),
            'katilim_sayisi' => 0,
            'uygun_ogrenci_sayisi' => 0,
            'test_uygulanan_sayisi' => 0,
        ];

        foreach ($kayitlar as $kayit) {
            $ozet['katilim_sayisi'] += (int) ($kayit['katildigi_ders'] ?? 0);
            $ozet['uygun_ogrenci_sayisi'] += !empty($kayit['gelisim_testine_uygun']) ? 1 : 0;
            $ozet['test_uygulanan_sayisi'] += !empty($kayit['gelisim_testi_uygulandi']) ? 1 : 0;
        }

        return $ozet;
    }

    public static function gelisimTestiGuncelle(int $ogrenciId, bool $uygulandi, ?string $uygulamaTarihi, int $kullaniciId): bool
    {
        if (!self::gelisimTestiTablosuVarMi()) {
            throw new \RuntimeException('Gelişim testi tablosu bulunamadı. İlgili migration çalıştırılmalıdır.');
        }

        $ogrenciStmt = self::db()->prepare(
            'SELECT 1
             FROM ogrenciler o
             WHERE o.id = :ogrenci_id AND o.kurum_id = :kurum_id
               AND EXISTS (
                   SELECT 1 FROM randevular r
                   WHERE r.ogrenci_id = o.id AND r.kurum_id = o.kurum_id
               )'
        );
        $ogrenciStmt->execute(['ogrenci_id' => $ogrenciId, 'kurum_id' => self::kurumId()]);
        if (!$ogrenciStmt->fetchColumn()) {
            return false;
        }

        $stmt = self::db()->prepare(
            'INSERT INTO ogrenci_gelisim_testleri
                (kurum_id, ogrenci_id, uygulandi, uygulama_tarihi, notlar, guncelleyen_kullanici_id, olusturulma_tarihi, guncellenme_tarihi)
             VALUES
                (:kurum_id, :ogrenci_id, :uygulandi, :uygulama_tarihi, NULL, :kullanici_id, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                uygulandi = VALUES(uygulandi),
                uygulama_tarihi = VALUES(uygulama_tarihi),
                guncelleyen_kullanici_id = VALUES(guncelleyen_kullanici_id),
                guncellenme_tarihi = NOW()'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'ogrenci_id' => $ogrenciId,
            'uygulandi' => $uygulandi ? 1 : 0,
            'uygulama_tarihi' => $uygulamaTarihi ?: ($uygulandi ? date('Y-m-d') : null),
            'kullanici_id' => $kullaniciId > 0 ? $kullaniciId : null,
        ]);

        return true;
    }
}
