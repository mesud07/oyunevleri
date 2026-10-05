<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Model;
final class Odeme extends Model
{
    public static function liste(int $sayfa = 1, int $limit = 20, string $yontem = '', string $siralama = 'tarih_desc', string $ay = ''): array
    {
        $sayfa = max(1, $sayfa);
        $limit = max(10, min(100, $limit));
        $offset = ($sayfa - 1) * $limit;
        $yontem = in_array($yontem, ['nakit', 'kredi_karti', 'havale', 'odeme_baglantisi', 'diger'], true) ? $yontem : '';
        $ay = preg_match('/^20\d{2}-(?:0[1-9]|1[0-2])$/', $ay) ? $ay : '';
        $siralama = in_array($siralama, ['tarih_desc', 'tarih_asc', 'yontem_asc', 'yontem_desc'], true) ? $siralama : 'tarih_desc';
        $where = ['od.kurum_id = :kurum_id'];
        $params = ['kurum_id' => self::kurumId()];
        if ($yontem === 'havale') {
            $where[] = 'od.yontem IN ("havale_eft", "banka_havalesi", "havale")';
        } elseif ($yontem !== '') {
            $where[] = 'od.yontem = :yontem';
            $params['yontem'] = $yontem;
        }
        if ($ay !== '') {
            $ayTarihi = new \DateTimeImmutable($ay . '-01');
            $where[] = 'od.tarih BETWEEN :ay_baslangic AND :ay_bitis';
            $params['ay_baslangic'] = $ayTarihi->format('Y-m-01');
            $params['ay_bitis'] = $ayTarihi->format('Y-m-t');
        }
        $whereSql = implode(' AND ', $where);
        $orderSql = match ($siralama) {
            'tarih_asc' => 'od.tarih ASC, od.id ASC',
            'yontem_asc' => 'od.yontem ASC, od.tarih DESC, od.id DESC',
            'yontem_desc' => 'od.yontem DESC, od.tarih DESC, od.id DESC',
            default => 'od.tarih DESC, od.id DESC',
        };

        $sayStmt = self::db()->prepare('SELECT COUNT(*) FROM odemeler od WHERE ' . $whereSql);
        $sayStmt->execute($params);
        $toplam = (int) $sayStmt->fetchColumn();

        $faturaTablosu = (bool) self::db()->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'faturalar' LIMIT 1")->fetchColumn();
        $faturaAlanlari = $faturaTablosu
            ? ', f.id AS fatura_id, f.fatura_no, f.belge_turu AS fatura_belge_turu, f.yerel_durum AS fatura_durumu'
            : ', NULL AS fatura_id, NULL AS fatura_no, NULL AS fatura_belge_turu, NULL AS fatura_durumu';
        $faturaJoin = $faturaTablosu ? ' LEFT JOIN faturalar f ON f.odeme_id = od.id AND f.kurum_id = od.kurum_id' : '';

        $stmt = self::db()->prepare(
            'SELECT od.id, CONCAT(o.ad, " ", o.soyad) AS ogrenci,
                    COALESCE(
                        NULLIF(TRIM(CONCAT(v.ad, " ", v.soyad)), ""),
                        (SELECT NULLIF(TRIM(CONCAT(v2.ad, " ", v2.soyad)), "")
                         FROM ogrenci_velileri ov2
                         INNER JOIN veliler v2 ON v2.id = ov2.veli_id AND v2.kurum_id = ov2.kurum_id
                         WHERE ov2.ogrenci_id = od.ogrenci_id AND ov2.kurum_id = od.kurum_id
                         ORDER BY ov2.birincil_mi DESC, ov2.id ASC
                         LIMIT 1),
                        "-"
                    ) AS veli,
                    p.paket_adi, od.tarih, od.tutar,
                    od.yontem, od.kasa_id, COALESCE(k.ad, "-") AS kasa, od.makbuz_numarasi,
                    od.aciklama, od.iptal, od.iptal_nedeni' . $faturaAlanlari . '
             FROM odemeler od
             INNER JOIN ogrenciler o ON o.id = od.ogrenci_id AND o.kurum_id = od.kurum_id
             INNER JOIN paketler p ON p.id = od.paket_id AND p.kurum_id = od.kurum_id
             LEFT JOIN veliler v ON v.id = od.veli_id AND v.kurum_id = od.kurum_id
             LEFT JOIN kasalar k ON k.id = od.kasa_id AND k.kurum_id = od.kurum_id
             ' . $faturaJoin . '
             WHERE ' . $whereSql . '
             ORDER BY ' . $orderSql . '
             LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, $key === 'kurum_id' ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return [
            'kayitlar' => $stmt->fetchAll(),
            'sayfalama' => [
                'sayfa' => $sayfa,
                'limit' => $limit,
                'toplam' => $toplam,
                'toplam_sayfa' => max(1, (int) ceil($toplam / $limit)),
                'yontem' => $yontem,
                'siralama' => $siralama,
                'ay' => $ay,
            ],
        ];
    }

    public static function tahsilatOzetleri(): array
    {
        $bugun = new \DateTimeImmutable('today');
        $haftaBaslangic = $bugun->modify('monday this week')->format('Y-m-d');
        $haftaBitis = $bugun->modify('sunday this week')->format('Y-m-d');

        $stmt = self::db()->prepare(
            'SELECT
                COALESCE(SUM(CASE WHEN tarih = :bugun THEN tutar ELSE 0 END), 0) AS bugun,
                COALESCE(SUM(CASE WHEN tarih BETWEEN :hafta_baslangic AND :hafta_bitis THEN tutar ELSE 0 END), 0) AS bu_hafta,
                COALESCE(SUM(tutar), 0) AS toplam
             FROM odemeler
             WHERE kurum_id = :kurum_id AND iptal = 0'
        );
        $stmt->execute([
            'bugun' => $bugun->format('Y-m-d'),
            'hafta_baslangic' => $haftaBaslangic,
            'hafta_bitis' => $haftaBitis,
            'kurum_id' => self::kurumId(),
        ]);
        $row = $stmt->fetch() ?: [];

        return [
            'bugun' => (float) ($row['bugun'] ?? 0),
            'bu_hafta' => (float) ($row['bu_hafta'] ?? 0),
            'toplam' => (float) ($row['toplam'] ?? 0),
        ];
    }

    public static function analizVerileri(string $baslangic, string $bitis, string $yontem = ''): array
    {
        if (!preg_match('/^20\d{2}-\d{2}-\d{2}$/', $baslangic)
            || !preg_match('/^20\d{2}-\d{2}-\d{2}$/', $bitis)
            || $bitis < $baslangic) {
            throw new \InvalidArgumentException('Analiz tarih araligi gecersizdir.');
        }

        $db = self::db();
        $yontem = in_array($yontem, ['nakit', 'kredi_karti', 'havale', 'odeme_baglantisi', 'diger'], true) ? $yontem : '';
        $yontemKosulu = '';
        $params = [
            'kurum_id' => self::kurumId(),
            'baslangic' => $baslangic,
            'bitis' => $bitis,
        ];
        if ($yontem === 'havale') {
            $yontemKosulu = ' AND od.yontem IN ("havale_eft", "banka_havalesi", "havale")';
        } elseif ($yontem !== '') {
            $yontemKosulu = ' AND od.yontem = :yontem';
            $params['yontem'] = $yontem;
        }

        $yontemStmt = $db->prepare(
            'SELECT
                CASE
                    WHEN od.yontem = "nakit" THEN "nakit"
                    WHEN od.yontem = "kredi_karti" THEN "kredi_karti"
                    WHEN od.yontem IN ("havale_eft", "banka_havalesi", "havale") THEN "havale_eft"
                    WHEN od.yontem = "odeme_baglantisi" THEN "odeme_baglantisi"
                    ELSE "diger"
                END AS kategori,
                COUNT(*) AS adet,
                COALESCE(SUM(od.tutar), 0) AS brut,
                COALESCE(SUM(
                    CASE
                        WHEN p.kdv_orani IS NULL OR p.kdv_orani <= 0 THEN 0
                        ELSE od.tutar - (od.tutar / (1 + (p.kdv_orani / 100)))
                    END
                ), 0) AS kdv,
                COALESCE(SUM(CASE WHEN p.kdv_orani IS NULL THEN 1 ELSE 0 END), 0) AS kdv_belirsiz_adet
             FROM odemeler od
             INNER JOIN paketler p ON p.id = od.paket_id AND p.kurum_id = od.kurum_id
             WHERE od.kurum_id = :kurum_id AND od.iptal = 0
               AND od.tarih BETWEEN :baslangic AND :bitis' . $yontemKosulu . '
             GROUP BY kategori'
        );
        $yontemStmt->execute($params);

        return [
            'yontemler' => $yontemStmt->fetchAll(),
        ];
    }

    public static function tarihAraligiTahsilatlari(string $baslangic, string $bitis, string $yontem = ''): array
    {
        if (!preg_match('/^20\d{2}-\d{2}-\d{2}$/', $baslangic)
            || !preg_match('/^20\d{2}-\d{2}-\d{2}$/', $bitis)
            || $bitis < $baslangic) {
            throw new \InvalidArgumentException('Tahsilat tarih araligi gecersizdir.');
        }

        $yontem = in_array($yontem, ['nakit', 'kredi_karti', 'havale', 'odeme_baglantisi', 'diger'], true) ? $yontem : '';
        $yontemKosulu = '';
        $params = [
            'kurum_id' => self::kurumId(),
            'baslangic' => $baslangic,
            'bitis' => $bitis,
        ];
        if ($yontem === 'havale') {
            $yontemKosulu = ' AND od.yontem IN ("havale_eft", "banka_havalesi", "havale")';
        } elseif ($yontem !== '') {
            $yontemKosulu = ' AND od.yontem = :yontem';
            $params['yontem'] = $yontem;
        }

        $stmt = self::db()->prepare(
            'SELECT od.tarih, CONCAT(o.ad, " ", o.soyad) AS ogrenci,
                    COALESCE(
                        NULLIF(TRIM(CONCAT(v.ad, " ", v.soyad)), ""),
                        (SELECT NULLIF(TRIM(CONCAT(v2.ad, " ", v2.soyad)), "")
                         FROM ogrenci_velileri ov2
                         INNER JOIN veliler v2 ON v2.id = ov2.veli_id AND v2.kurum_id = ov2.kurum_id
                         WHERE ov2.ogrenci_id = od.ogrenci_id AND ov2.kurum_id = od.kurum_id
                         ORDER BY ov2.birincil_mi DESC, ov2.id ASC
                         LIMIT 1),
                        "-"
                    ) AS veli,
                    p.paket_adi, od.yontem, od.tutar, od.makbuz_numarasi,
                    COALESCE(k.ad, "-") AS kasa, od.aciklama
             FROM odemeler od
             INNER JOIN ogrenciler o ON o.id = od.ogrenci_id AND o.kurum_id = od.kurum_id
             INNER JOIN paketler p ON p.id = od.paket_id AND p.kurum_id = od.kurum_id
             LEFT JOIN veliler v ON v.id = od.veli_id AND v.kurum_id = od.kurum_id
             LEFT JOIN kasalar k ON k.id = od.kasa_id AND k.kurum_id = od.kurum_id
             WHERE od.kurum_id = :kurum_id AND od.iptal = 0
               AND od.tarih BETWEEN :baslangic AND :bitis' . $yontemKosulu . '
             ORDER BY od.tarih ASC, od.id ASC'
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function borcluPaketler(): array
    {
        Paket::tahsilatNotuKolonunuHazirla();
        $stmt = self::db()->prepare(
            'SELECT p.id AS paket_id, CONCAT(o.ad, " ", o.soyad) AS ogrenci, p.paket_adi,
                    p.baslangic_tarihi, p.tahmini_son_ders_tarihi,
                    p.net_paket_tutari, p.tahsilat_notu, p.beklenen_odeme_tarihi,
                    COALESCE(SUM(CASE WHEN od.iptal = 0 THEN od.tutar ELSE 0 END), 0) AS tahsilat,
                    p.net_paket_tutari - COALESCE(SUM(CASE WHEN od.iptal = 0 THEN od.tutar ELSE 0 END), 0) AS kalan_borc,
                    p.paket_durumu
             FROM paketler p
             INNER JOIN ogrenciler o ON o.id = p.ogrenci_id AND o.kurum_id = p.kurum_id
             LEFT JOIN odemeler od ON od.paket_id = p.id AND od.kurum_id = p.kurum_id
             WHERE p.kurum_id = :kurum_id AND p.paket_durumu = "aktif"
             GROUP BY p.id
             HAVING kalan_borc > 0
             ORDER BY p.beklenen_odeme_tarihi IS NULL ASC,
                      p.beklenen_odeme_tarihi ASC,
                      kalan_borc DESC,
                      p.olusturulma_tarihi DESC
             LIMIT 100'
        );
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function ekle(array $veri): int
    {
        $paket = Paket::idIleBul((int) $veri['paket_id']);
        if (!$paket) {
            throw new \RuntimeException('Paket bulunamadi.');
        }

        $stmt = self::db()->prepare(
            'INSERT INTO odemeler
             (kurum_id, ogrenci_id, veli_id, paket_id, tarih, tutar, yontem, kasa_id, makbuz_numarasi, aciklama, alan_kullanici_id, olusturulma_tarihi)
             VALUES
             (:kurum_id, :ogrenci_id, :veli_id, :paket_id, :tarih, :tutar, :yontem, :kasa_id, :makbuz_numarasi, :aciklama, :alan_kullanici_id, NOW())'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'ogrenci_id' => (int) $paket['ogrenci_id'],
            'veli_id' => $veri['veli_id'] ?: null,
            'paket_id' => $veri['paket_id'],
            'tarih' => $veri['tarih'],
            'tutar' => (float) $veri['tutar'],
            'yontem' => $veri['yontem'],
            'kasa_id' => !empty($veri['kasa_id']) ? (int) $veri['kasa_id'] : null,
            'makbuz_numarasi' => $veri['makbuz_numarasi'] ?: null,
            'aciklama' => $veri['aciklama'] ?: null,
            'alan_kullanici_id' => $veri['alan_kullanici_id'],
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function geriAl(int $id, string $neden = ''): bool
    {
        $stmt = self::db()->prepare(
            'UPDATE odemeler
             SET iptal = 1, iptal_nedeni = :iptal_nedeni
             WHERE id = :id AND kurum_id = :kurum_id AND iptal = 0'
        );
        $stmt->execute([
            'id' => $id,
            'kurum_id' => self::kurumId(),
            'iptal_nedeni' => $neden ?: 'Tahsilat geri alindi.',
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function kasayaAktar(int $id, int $kasaId): bool
    {
        $kontrol = self::db()->prepare('SELECT id FROM odemeler WHERE id = :id AND kurum_id = :kurum_id AND iptal = 0 LIMIT 1');
        $kontrol->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        if (!$kontrol->fetch()) {
            return false;
        }

        $stmt = self::db()->prepare(
            'UPDATE odemeler
             SET kasa_id = :kasa_id
             WHERE id = :id AND kurum_id = :kurum_id AND iptal = 0'
        );
        $stmt->execute([
            'id' => $id,
            'kurum_id' => self::kurumId(),
            'kasa_id' => $kasaId > 0 ? $kasaId : null,
        ]);

        return true;
    }

    public static function sil(int $id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM odemeler WHERE id = :id AND kurum_id = :kurum_id');
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        return $stmt->rowCount() > 0;
    }
}
