<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Model;
final class Odeme extends Model
{
    public static function liste(int $sayfa = 1, int $limit = 20): array
    {
        $sayfa = max(1, $sayfa);
        $limit = max(10, min(100, $limit));
        $offset = ($sayfa - 1) * $limit;

        $sayStmt = self::db()->prepare('SELECT COUNT(*) FROM odemeler WHERE kurum_id = :kurum_id');
        $sayStmt->execute(self::kurumParam());
        $toplam = (int) $sayStmt->fetchColumn();

        $stmt = self::db()->prepare(
            'SELECT od.id, CONCAT(o.ad, " ", o.soyad) AS ogrenci, p.paket_adi, od.tarih, od.tutar,
                    od.yontem, od.kasa_id, COALESCE(k.ad, "-") AS kasa, od.makbuz_numarasi,
                    od.aciklama, od.iptal, od.iptal_nedeni
             FROM odemeler od
             INNER JOIN ogrenciler o ON o.id = od.ogrenci_id AND o.kurum_id = od.kurum_id
             INNER JOIN paketler p ON p.id = od.paket_id AND p.kurum_id = od.kurum_id
             LEFT JOIN kasalar k ON k.id = od.kasa_id AND k.kurum_id = od.kurum_id
             WHERE od.kurum_id = :kurum_id
             ORDER BY od.tarih DESC, od.id DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('kurum_id', self::kurumId(), \PDO::PARAM_INT);
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

    public static function borcluPaketler(): array
    {
        $stmt = self::db()->prepare(
            'SELECT p.id AS paket_id, CONCAT(o.ad, " ", o.soyad) AS ogrenci, p.paket_adi,
                    p.net_paket_tutari,
                    COALESCE(SUM(CASE WHEN od.iptal = 0 THEN od.tutar ELSE 0 END), 0) AS tahsilat,
                    p.net_paket_tutari - COALESCE(SUM(CASE WHEN od.iptal = 0 THEN od.tutar ELSE 0 END), 0) AS kalan_borc,
                    p.paket_durumu
             FROM paketler p
             INNER JOIN ogrenciler o ON o.id = p.ogrenci_id AND o.kurum_id = p.kurum_id
             LEFT JOIN odemeler od ON od.paket_id = p.id AND od.kurum_id = p.kurum_id
             WHERE p.kurum_id = :kurum_id AND p.paket_durumu = "aktif"
             GROUP BY p.id
             HAVING kalan_borc > 0
             ORDER BY kalan_borc DESC, p.olusturulma_tarihi DESC
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
