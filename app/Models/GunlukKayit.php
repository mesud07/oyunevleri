<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

final class GunlukKayit extends Model
{
    public static function ekle(array $veri): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO gunluk_notlar
             (kurum_id, ogrenci_id, randevu_id, tarih, kategori, not_metni, olusturan_kullanici_id, olusturulma_tarihi)
             VALUES
             (:kurum_id, :ogrenci_id, :randevu_id, :tarih, :kategori, :not_metni, :olusturan_kullanici_id, NOW())'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'ogrenci_id' => (int) $veri['ogrenci_id'],
            'randevu_id' => !empty($veri['randevu_id']) ? (int) $veri['randevu_id'] : null,
            'tarih' => $veri['tarih'] ?: date('Y-m-d'),
            'kategori' => $veri['kategori'] ?: 'Genel',
            'not_metni' => $veri['not_metni'],
            'olusturan_kullanici_id' => !empty($veri['olusturan_kullanici_id']) ? (int) $veri['olusturan_kullanici_id'] : null,
        ]);

        return (int) self::db()->lastInsertId();
    }

    public static function liste(string $baslangic, string $bitis, array $filtre = []): array
    {
        $where = ['gn.kurum_id = :kurum_id', 'gn.tarih BETWEEN :baslangic AND :bitis'];
        $params = [
            'kurum_id' => self::kurumId(),
            'baslangic' => $baslangic,
            'bitis' => $bitis,
        ];

        if (!empty($filtre['ogrenci_id'])) {
            $where[] = 'gn.ogrenci_id = :ogrenci_id';
            $params['ogrenci_id'] = (int) $filtre['ogrenci_id'];
        }

        if (!empty($filtre['randevu_id'])) {
            $where[] = 'gn.randevu_id = :randevu_id';
            $params['randevu_id'] = (int) $filtre['randevu_id'];
        }

        $sql = 'SELECT gn.*,
                       CONCAT(o.ad, " ", o.soyad) AS ogrenci,
                       r.baslangic_saati,
                       r.bitis_saati,
                       COALESCE(g.ad, "Cocuk Etkinlik ve Oyun Evi") AS grup,
                       COALESCE(p.paket_adi, r.tur, "-") AS randevu_tanimi,
                       COALESCE(CONCAT(k.ad, " ", k.soyad), "-") AS kaydeden
                FROM gunluk_notlar gn
                INNER JOIN ogrenciler o ON o.id = gn.ogrenci_id AND o.kurum_id = gn.kurum_id
                LEFT JOIN randevular r ON r.id = gn.randevu_id AND r.kurum_id = gn.kurum_id
                LEFT JOIN gruplar g ON g.id = r.grup_id AND g.kurum_id = gn.kurum_id
                LEFT JOIN paketler p ON p.id = r.paket_id AND p.kurum_id = gn.kurum_id
                LEFT JOIN kullanicilar k ON k.id = gn.olusturan_kullanici_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY gn.tarih DESC, COALESCE(r.baslangic_saati, TIME(gn.olusturulma_tarihi)) DESC, gn.id DESC
                LIMIT 500';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function ogrenciAkisi(int $ogrenciId, int $limit = 100): array
    {
        $stmt = self::db()->prepare(
            'SELECT gn.*,
                    r.baslangic_saati,
                    r.bitis_saati,
                    COALESCE(g.ad, "Cocuk Etkinlik ve Oyun Evi") AS grup,
                    COALESCE(p.paket_adi, r.tur, "-") AS randevu_tanimi,
                    COALESCE(CONCAT(k.ad, " ", k.soyad), "-") AS kaydeden
             FROM gunluk_notlar gn
             LEFT JOIN randevular r ON r.id = gn.randevu_id AND r.kurum_id = gn.kurum_id
             LEFT JOIN gruplar g ON g.id = r.grup_id AND g.kurum_id = gn.kurum_id
             LEFT JOIN paketler p ON p.id = r.paket_id AND p.kurum_id = gn.kurum_id
             LEFT JOIN kullanicilar k ON k.id = gn.olusturan_kullanici_id
             WHERE gn.kurum_id = :kurum_id
               AND gn.ogrenci_id = :ogrenci_id
             ORDER BY gn.tarih DESC, COALESCE(r.baslangic_saati, TIME(gn.olusturulma_tarihi)) DESC, gn.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue('kurum_id', self::kurumId(), PDO::PARAM_INT);
        $stmt->bindValue('ogrenci_id', $ogrenciId, PDO::PARAM_INT);
        $stmt->bindValue('limit', max(10, min(300, $limit)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function randevuNotlari(int $randevuId): array
    {
        $stmt = self::db()->prepare(
            'SELECT gn.*, COALESCE(CONCAT(k.ad, " ", k.soyad), "-") AS kaydeden
             FROM gunluk_notlar gn
             LEFT JOIN kullanicilar k ON k.id = gn.olusturan_kullanici_id
             WHERE gn.kurum_id = :kurum_id
               AND gn.randevu_id = :randevu_id
             ORDER BY gn.olusturulma_tarihi DESC, gn.id DESC'
        );
        $stmt->execute(['kurum_id' => self::kurumId(), 'randevu_id' => $randevuId]);

        return $stmt->fetchAll();
    }

    public static function ozet(array $kayitlar): array
    {
        $ogrenciler = [];
        $kategoriler = [];

        foreach ($kayitlar as $row) {
            $ogrenciId = (int) ($row['ogrenci_id'] ?? 0);
            if ($ogrenciId > 0) {
                $ogrenciler[$ogrenciId] = true;
            }
            $kategori = (string) ($row['kategori'] ?? 'Genel');
            $kategoriler[$kategori] = ($kategoriler[$kategori] ?? 0) + 1;
        }

        return [
            'not_sayisi' => count($kayitlar),
            'ogrenci_sayisi' => count($ogrenciler),
            'kategori_sayisi' => count($kategoriler),
            'kategoriler' => $kategoriler,
        ];
    }

}
