<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Model;

final class Kasa extends Model
{
    public static function liste(): array
    {
        $stmt = self::db()->prepare(
            'SELECT k.id, k.ad, k.tur, k.para_birimi, k.acilis_bakiyesi, k.aciklama, k.aktif,
                    COALESCE(giris.tutar, 0) AS tahsilat,
                    COALESCE(manuel.giris, 0) AS manuel_giris,
                    COALESCE(manuel.cikis, 0) AS manuel_cikis,
                    COALESCE(cikis.tutar, 0) AS gider,
                    k.acilis_bakiyesi + COALESCE(giris.tutar, 0) + COALESCE(manuel.giris, 0) - COALESCE(manuel.cikis, 0) - COALESCE(cikis.tutar, 0) AS bakiye
             FROM kasalar k
             LEFT JOIN (
                SELECT kasa_id, SUM(tutar) AS tutar
                FROM odemeler
                WHERE kurum_id = :kurum_id_giris AND iptal = 0 AND kasa_id IS NOT NULL
                GROUP BY kasa_id
             ) giris ON giris.kasa_id = k.id
             LEFT JOIN (
                SELECT kasa_id,
                       SUM(CASE WHEN tur = "giris" THEN tutar ELSE 0 END) AS giris,
                       SUM(CASE WHEN tur = "cikis" THEN tutar ELSE 0 END) AS cikis
                FROM kasa_hareketleri
                WHERE kurum_id = :kurum_id_manuel
                GROUP BY kasa_id
             ) manuel ON manuel.kasa_id = k.id
             LEFT JOIN (
                SELECT kasa_id, SUM(tutar) AS tutar
                FROM giderler
                WHERE kurum_id = :kurum_id_cikis AND durum = "odendi" AND kasa_id IS NOT NULL
                GROUP BY kasa_id
             ) cikis ON cikis.kasa_id = k.id
             WHERE k.kurum_id = :kurum_id
             ORDER BY k.aktif DESC, k.tur ASC, k.ad ASC'
        );
        $kurumId = self::kurumId();
        $stmt->execute([
            'kurum_id' => $kurumId,
            'kurum_id_giris' => $kurumId,
            'kurum_id_manuel' => $kurumId,
            'kurum_id_cikis' => $kurumId,
        ]);
        return $stmt->fetchAll();
    }

    public static function ozet(): array
    {
        $ozet = [];
        foreach (self::liste() as $kasa) {
            $paraBirimi = (string) ($kasa['para_birimi'] ?: 'TRY');
            if (!isset($ozet[$paraBirimi])) {
                $ozet[$paraBirimi] = [
                    'para_birimi' => $paraBirimi,
                    'bakiye' => 0.0,
                    'tahsilat' => 0.0,
                    'gider' => 0.0,
                    'manuel_giris' => 0.0,
                    'manuel_cikis' => 0.0,
                ];
            }
            $ozet[$paraBirimi]['bakiye'] += (float) $kasa['bakiye'];
            $ozet[$paraBirimi]['tahsilat'] += (float) $kasa['tahsilat'];
            $ozet[$paraBirimi]['gider'] += (float) $kasa['gider'];
            $ozet[$paraBirimi]['manuel_giris'] += (float) $kasa['manuel_giris'];
            $ozet[$paraBirimi]['manuel_cikis'] += (float) $kasa['manuel_cikis'];
        }

        return array_values($ozet);
    }

    public static function hareketler(int $limit = 100): array
    {
        $limit = max(1, min(300, $limit));
        $sql = "
            SELECT * FROM (
                SELECT CONCAT('manuel-', kh.id) AS id, kh.tarih, kh.tur, kh.tutar, k.ad AS kasa, k.para_birimi,
                       COALESCE(kh.aciklama, 'Manuel kasa hareketi') AS aciklama,
                       'Manuel' AS kaynak
                FROM kasa_hareketleri kh
                INNER JOIN kasalar k ON k.id = kh.kasa_id
                WHERE kh.kurum_id = :kurum_id_manuel
                UNION ALL
                SELECT CONCAT('odeme-', od.id) AS id, od.tarih, 'giris' AS tur, od.tutar, k.ad AS kasa, k.para_birimi,
                       CONCAT('Tahsilat: ', o.ad, ' ', o.soyad, ' - ', p.paket_adi) AS aciklama,
                       'Tahsilat' AS kaynak
                FROM odemeler od
                INNER JOIN kasalar k ON k.id = od.kasa_id
                INNER JOIN ogrenciler o ON o.id = od.ogrenci_id
                INNER JOIN paketler p ON p.id = od.paket_id
                WHERE od.kurum_id = :kurum_id_odeme AND od.iptal = 0
                UNION ALL
                SELECT CONCAT('gider-', g.id) AS id, COALESCE(g.odeme_tarihi, g.tarih) AS tarih, 'cikis' AS tur, g.tutar, k.ad AS kasa, k.para_birimi,
                       CONCAT('Gider: ', g.tedarikci, CASE WHEN g.kategori IS NULL THEN '' ELSE CONCAT(' - ', g.kategori) END) AS aciklama,
                       'Gider' AS kaynak
                FROM giderler g
                INNER JOIN kasalar k ON k.id = g.kasa_id
                WHERE g.kurum_id = :kurum_id_gider AND g.durum = 'odendi'
            ) hareketler
            ORDER BY tarih DESC, id DESC
            LIMIT " . $limit;

        $stmt = self::db()->prepare($sql);
        $kurumId = self::kurumId();
        $stmt->execute([
            'kurum_id_manuel' => $kurumId,
            'kurum_id_odeme' => $kurumId,
            'kurum_id_gider' => $kurumId,
        ]);
        return $stmt->fetchAll();
    }

    public static function hareketEkle(array $veri): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO kasa_hareketleri
             (kurum_id, kasa_id, tarih, tur, tutar, aciklama, olusturan_kullanici_id, olusturulma_tarihi)
             VALUES
             (:kurum_id, :kasa_id, :tarih, :tur, :tutar, :aciklama, :olusturan_kullanici_id, NOW())'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'kasa_id' => (int) $veri['kasa_id'],
            'tarih' => $veri['tarih'],
            'tur' => $veri['tur'] === 'cikis' ? 'cikis' : 'giris',
            'tutar' => (float) $veri['tutar'],
            'aciklama' => $veri['aciklama'] ?: null,
            'olusturan_kullanici_id' => $veri['olusturan_kullanici_id'] ?: null,
        ]);

        return (int) self::db()->lastInsertId();
    }

    public static function secenekler(): array
    {
        $stmt = self::db()->prepare(
            'SELECT id, ad, tur, para_birimi
             FROM kasalar
             WHERE kurum_id = :kurum_id AND aktif = 1
             ORDER BY tur ASC, ad ASC'
        );
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function ekle(array $veri): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO kasalar
             (kurum_id, ad, tur, para_birimi, acilis_bakiyesi, aciklama, aktif, olusturan_kullanici_id, olusturulma_tarihi)
             VALUES
             (:kurum_id, :ad, :tur, :para_birimi, :acilis_bakiyesi, :aciklama, :aktif, :olusturan_kullanici_id, NOW())'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'ad' => $veri['ad'],
            'tur' => self::tur($veri['tur']),
            'para_birimi' => strtoupper((string) ($veri['para_birimi'] ?: 'TRY')),
            'acilis_bakiyesi' => (float) ($veri['acilis_bakiyesi'] ?? 0),
            'aciklama' => $veri['aciklama'] ?: null,
            'aktif' => (int) ($veri['aktif'] ?? 1),
            'olusturan_kullanici_id' => $veri['olusturan_kullanici_id'] ?: null,
        ]);

        return (int) self::db()->lastInsertId();
    }

    public static function guncelle(int $id, array $veri): bool
    {
        $stmt = self::db()->prepare(
            'UPDATE kasalar
             SET ad = :ad,
                 tur = :tur,
                 para_birimi = :para_birimi,
                 acilis_bakiyesi = :acilis_bakiyesi,
                 aciklama = :aciklama,
                 aktif = :aktif
             WHERE id = :id AND kurum_id = :kurum_id'
        );
        $stmt->execute([
            'id' => $id,
            'kurum_id' => self::kurumId(),
            'ad' => $veri['ad'],
            'tur' => self::tur($veri['tur']),
            'para_birimi' => strtoupper((string) ($veri['para_birimi'] ?: 'TRY')),
            'acilis_bakiyesi' => (float) ($veri['acilis_bakiyesi'] ?? 0),
            'aciklama' => $veri['aciklama'] ?: null,
            'aktif' => (int) ($veri['aktif'] ?? 1),
        ]);

        return $stmt->rowCount() >= 0;
    }

    public static function sil(int $id): bool
    {
        $db = self::db();
        $stmt = $db->prepare('SELECT COUNT(*) FROM odemeler WHERE kasa_id = :id AND kurum_id = :kurum_id');
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $odeme = (int) $stmt->fetchColumn();
        $stmt = $db->prepare('SELECT COUNT(*) FROM giderler WHERE kasa_id = :id AND kurum_id = :kurum_id');
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $gider = (int) $stmt->fetchColumn();
        $stmt = $db->prepare('SELECT COUNT(*) FROM kasa_hareketleri WHERE kasa_id = :id AND kurum_id = :kurum_id');
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $hareket = (int) $stmt->fetchColumn();
        if ($odeme + $gider + $hareket > 0) {
            $stmt = $db->prepare('UPDATE kasalar SET aktif = 0 WHERE id = :id AND kurum_id = :kurum_id');
            $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
            return $stmt->rowCount() > 0;
        }

        $stmt = $db->prepare('DELETE FROM kasalar WHERE id = :id AND kurum_id = :kurum_id');
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        return $stmt->rowCount() > 0;
    }

    private static function tur($tur): string
    {
        $tur = (string) $tur;
        return in_array($tur, ['nakit', 'banka', 'altin', 'diger'], true) ? $tur : 'nakit';
    }
}
