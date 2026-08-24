<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class OgrenciKaraListe extends Model
{
    public const KATEGORILER = [
        'tanisma_dersine_gelmedi' => 'Tanisma dersine gelmedi',
        'haber_vermeden_gelmedi' => 'Haber vermeden gelmedi',
        'sik_iptal' => 'Sik iptal',
        'odeme_sorunu' => 'Odeme sorunu',
        'iletisim_sorunu' => 'Iletisim sorunu',
        'diger' => 'Diger',
    ];

    public static function tabloVarMi(): bool
    {
        $stmt = self::db()->query("SHOW TABLES LIKE 'ogrenci_kara_liste'");
        return (bool) ($stmt && $stmt->fetchColumn());
    }

    public static function liste(array $filtre = []): array
    {
        if (!self::tabloVarMi()) {
            return [];
        }

        $where = ['okl.kurum_id = :kurum_id'];
        $params = ['kurum_id' => self::kurumId()];
        if (!empty($filtre['durum'])) {
            $where[] = 'okl.aktif = :aktif';
            $params['aktif'] = (string) $filtre['durum'] === 'pasif' ? 0 : 1;
        }
        if (!empty($filtre['ogrenci_id'])) {
            $where[] = 'okl.ogrenci_id = :ogrenci_id';
            $params['ogrenci_id'] = (int) $filtre['ogrenci_id'];
        }
        if (!empty($filtre['kategori'])) {
            $where[] = 'okl.kategori = :kategori';
            $params['kategori'] = (string) $filtre['kategori'];
        }

        $sql = 'SELECT okl.*, CONCAT(o.ad, " ", o.soyad) AS ogrenci,
                       CONCAT(k.ad, " ", k.soyad) AS kaydeden
                FROM ogrenci_kara_liste okl
                INNER JOIN ogrenciler o ON o.id = okl.ogrenci_id AND o.kurum_id = okl.kurum_id
                LEFT JOIN kullanicilar k ON k.id = okl.olusturan_kullanici_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY okl.aktif DESC, okl.olusturulma_tarihi DESC, okl.id DESC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function ogrenciKayitlari(int $ogrenciId): array
    {
        return self::liste(['ogrenci_id' => $ogrenciId]);
    }

    public static function aktifKayit(int $ogrenciId): ?array
    {
        if (!self::tabloVarMi()) {
            return null;
        }

        $stmt = self::db()->prepare(
            'SELECT *
             FROM ogrenci_kara_liste
             WHERE kurum_id = :kurum_id AND ogrenci_id = :ogrenci_id AND aktif = 1
             ORDER BY olusturulma_tarihi DESC, id DESC
             LIMIT 1'
        );
        $stmt->execute(['kurum_id' => self::kurumId(), 'ogrenci_id' => $ogrenciId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function ekle(array $veri): int
    {
        self::tabloGerekli();
        $stmt = self::db()->prepare(
            'INSERT INTO ogrenci_kara_liste
             (kurum_id, ogrenci_id, kategori, sebep, aktif, olusturan_kullanici_id, olusturulma_tarihi)
             VALUES (:kurum_id, :ogrenci_id, :kategori, :sebep, 1, :olusturan_kullanici_id, NOW())'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'ogrenci_id' => (int) $veri['ogrenci_id'],
            'kategori' => (string) $veri['kategori'],
            'sebep' => (string) $veri['sebep'],
            'olusturan_kullanici_id' => (int) ($veri['olusturan_kullanici_id'] ?? 0) ?: null,
        ]);

        return (int) self::db()->lastInsertId();
    }

    public static function pasifeAl(int $id): bool
    {
        self::tabloGerekli();
        $stmt = self::db()->prepare(
            'UPDATE ogrenci_kara_liste
             SET aktif = 0, kaldirilma_tarihi = NOW()
             WHERE id = :id AND kurum_id = :kurum_id'
        );
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        return $stmt->rowCount() > 0;
    }

    private static function tabloGerekli(): void
    {
        if (!self::tabloVarMi()) {
            throw new \RuntimeException('ogrenci_kara_liste tablosu bulunamadi. Migration calistirilmadan kara liste kaydi yapilamaz.');
        }
    }
}
