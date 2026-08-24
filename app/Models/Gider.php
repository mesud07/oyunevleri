<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Model;

final class Gider extends Model
{
    public static function kategoriler(): array
    {
        $varsayilanlar = [
            'Maas',
            'SGK / Personel',
            'Market',
            'Yemek',
            'Temizlik',
            'Kira / Aidat',
            'Kirtasiye',
            'Akaryakit',
            'Abonelik',
            'Bakim / Onarim',
            'Egitim Materyali',
            'Diger',
        ];

        $stmt = self::db()->prepare(
            'SELECT DISTINCT kategori
             FROM giderler
             WHERE kurum_id = :kurum_id AND kategori IS NOT NULL AND kategori <> ""
             ORDER BY kategori ASC'
        );
        $stmt->execute(self::kurumParam());
        $kayitliKategoriler = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        return array_values(array_unique(array_merge($varsayilanlar, $kayitliKategoriler ?: [])));
    }

    public static function liste(array $filtre = []): array
    {
        $stmt = self::db()->prepare(
            'SELECT g.id, g.tarih, g.tedarikci, g.kategori, g.aciklama, g.tutar, g.odeme_turu, g.kasa_id,
                    COALESCE(k.ad, "-") AS kasa, g.durum, g.odeme_tarihi
             FROM giderler g
             LEFT JOIN kasalar k ON k.id = g.kasa_id AND k.kurum_id = g.kurum_id
             WHERE g.kurum_id = :kurum_id AND g.tarih BETWEEN :baslangic_tarihi AND :bitis_tarihi
             ORDER BY g.tarih ASC, g.id ASC
             LIMIT 1000'
        );
        $stmt->execute(self::filtreParametreleri($filtre));
        return $stmt->fetchAll();
    }

    public static function ozet(array $filtre = []): array
    {
        $db = self::db();
        $params = self::filtreParametreleri($filtre);

        $aralikStmt = $db->prepare(
            'SELECT
                COALESCE(SUM(tutar), 0) AS toplam,
                COALESCE(SUM(CASE WHEN durum = "planlandi" THEN tutar ELSE 0 END), 0) AS planli,
                COALESCE(SUM(CASE WHEN durum = "odendi" THEN tutar ELSE 0 END), 0) AS odendi,
                COUNT(*) AS kayit_sayisi
             FROM giderler
             WHERE kurum_id = :kurum_id AND tarih BETWEEN :baslangic_tarihi AND :bitis_tarihi'
        );
        $aralikStmt->execute($params);
        $aralik = $aralikStmt->fetch() ?: [];

        return [
            'gecikmis' => self::planliToplam('tarih < CURDATE()'),
            'bugun' => self::planliToplam('tarih = CURDATE()'),
            'yedi_gun' => self::planliToplam('tarih BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)'),
            'otuz_gun' => self::planliToplam('tarih BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)'),
            'aralik_toplam' => (float) ($aralik['toplam'] ?? 0),
            'aralik_planli' => (float) ($aralik['planli'] ?? 0),
            'aralik_odendi' => (float) ($aralik['odendi'] ?? 0),
            'aralik_kayit_sayisi' => (int) ($aralik['kayit_sayisi'] ?? 0),
        ];
    }

    public static function ekle(array $veri): int
    {
        return self::ekleTekrarli($veri, 1)[0] ?? 0;
    }

    public static function ekleTekrarli(array $veri, int $adet): array
    {
        $stmt = self::db()->prepare(
            'INSERT INTO giderler
             (kurum_id, tarih, tedarikci, kategori, aciklama, tutar, odeme_turu, kasa_id, durum, olusturan_kullanici_id, olusturulma_tarihi)
             VALUES
             (:kurum_id, :tarih, :tedarikci, :kategori, :aciklama, :tutar, :odeme_turu, :kasa_id, "planlandi", :olusturan_kullanici_id, NOW())'
        );

        $db = self::db();
        $ids = [];
        $adet = max(1, min(60, $adet));

        try {
            $db->beginTransaction();
            for ($i = 0; $i < $adet; $i++) {
                $stmt->execute([
                    'kurum_id' => self::kurumId(),
                    'tarih' => self::ayEkle((string) $veri['tarih'], $i),
                    'tedarikci' => $veri['tedarikci'],
                    'kategori' => $veri['kategori'] ?: null,
                    'aciklama' => $veri['aciklama'] ?: null,
                    'tutar' => (float) $veri['tutar'],
                    'odeme_turu' => $veri['odeme_turu'],
                    'kasa_id' => !empty($veri['kasa_id']) ? (int) $veri['kasa_id'] : null,
                    'olusturan_kullanici_id' => (int) $veri['olusturan_kullanici_id'],
                ]);
                $ids[] = (int) $db->lastInsertId();
            }
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        return $ids;
    }

    public static function odendi(int $id): bool
    {
        $stmt = self::db()->prepare(
            'UPDATE giderler
             SET durum = "odendi", odeme_tarihi = CURDATE()
             WHERE id = :id AND kurum_id = :kurum_id AND durum = "planlandi"'
        );
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        return $stmt->rowCount() > 0;
    }

    public static function guncelle(int $id, array $veri): bool
    {
        $mevcut = self::idIleBul($id);
        if (!$mevcut) {
            return false;
        }

        $stmt = self::db()->prepare(
            'UPDATE giderler
             SET tarih = :tarih,
                 tedarikci = :tedarikci,
                 kategori = :kategori,
                 aciklama = :aciklama,
                 tutar = :tutar,
                 odeme_turu = :odeme_turu,
                 kasa_id = :kasa_id
             WHERE id = :id AND kurum_id = :kurum_id'
        );

        $stmt->execute([
            'id' => $id,
            'kurum_id' => self::kurumId(),
            'tarih' => $veri['tarih'],
            'tedarikci' => $veri['tedarikci'],
            'kategori' => $veri['kategori'] ?: null,
            'aciklama' => $veri['aciklama'] ?: null,
            'tutar' => (float) $veri['tutar'],
            'odeme_turu' => $veri['odeme_turu'],
            'kasa_id' => !empty($veri['kasa_id']) ? (int) $veri['kasa_id'] : null,
        ]);

        return true;
    }

    public static function idIleBul(int $id): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT id, tarih, tedarikci, kategori, aciklama, tutar, odeme_turu, kasa_id, durum, odeme_tarihi
             FROM giderler
             WHERE id = :id AND kurum_id = :kurum_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $gider = $stmt->fetch();
        return $gider ?: null;
    }

    public static function sil(int $id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM giderler WHERE id = :id AND kurum_id = :kurum_id');
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        return $stmt->rowCount() > 0;
    }

    private static function ayEkle(string $tarih, int $ay): string
    {
        $parcalar = explode('-', $tarih);
        if (count($parcalar) !== 3) {
            return $tarih;
        }

        $yil = (int) $parcalar[0];
        $ayNo = (int) $parcalar[1];
        $gun = (int) $parcalar[2];
        $hedefAyIndeksi = $ayNo + $ay - 1;
        $hedefYil = $yil + intdiv($hedefAyIndeksi, 12);
        $hedefAy = ($hedefAyIndeksi % 12) + 1;
        $sonGun = (int) (new \DateTimeImmutable(sprintf('%04d-%02d-01', $hedefYil, $hedefAy)))->format('t');

        return sprintf('%04d-%02d-%02d', $hedefYil, $hedefAy, min($gun, $sonGun));
    }

    private static function filtreParametreleri(array $filtre): array
    {
        $bugun = new \DateTimeImmutable('today');
        $baslangic = self::gecerliTarih((string) ($filtre['baslangic_tarihi'] ?? '')) ?? $bugun->modify('first day of this month')->format('Y-m-d');
        $bitis = self::gecerliTarih((string) ($filtre['bitis_tarihi'] ?? '')) ?? $bugun->modify('last day of this month')->format('Y-m-d');

        if ($bitis < $baslangic) {
            [$baslangic, $bitis] = [$bitis, $baslangic];
        }

        return [
            'kurum_id' => self::kurumId(),
            'baslangic_tarihi' => $baslangic,
            'bitis_tarihi' => $bitis,
        ];
    }

    private static function planliToplam(string $kosul): float
    {
        $stmt = self::db()->prepare('SELECT COALESCE(SUM(tutar), 0) FROM giderler WHERE kurum_id = :kurum_id AND durum = "planlandi" AND ' . $kosul);
        $stmt->execute(self::kurumParam());
        return (float) $stmt->fetchColumn();
    }

    private static function gecerliTarih(string $tarih): ?string
    {
        $deger = \DateTimeImmutable::createFromFormat('!Y-m-d', trim($tarih));
        return $deger instanceof \DateTimeImmutable ? $deger->format('Y-m-d') : null;
    }
}
