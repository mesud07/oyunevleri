<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Hizmet extends Model
{
    public static function liste(): array
    {
        $stmt = self::db()->prepare(
            'SELECT id, hizmet_adi, ucret, haftalik_katilim_sayisi, toplam_normal_hak, toplam_telafi_hak, aktif
             FROM hizmetler
             WHERE kurum_id = :kurum_id
             ORDER BY aktif DESC, id DESC'
        );
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function aktifListe(): array
    {
        $stmt = self::db()->prepare(
            'SELECT id, hizmet_adi, ucret, haftalik_katilim_sayisi, toplam_normal_hak, toplam_telafi_hak
             FROM hizmetler
             WHERE kurum_id = :kurum_id AND aktif = 1
             ORDER BY hizmet_adi'
        );
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function idIleBul(int $id): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT id, hizmet_adi, ucret, haftalik_katilim_sayisi, toplam_normal_hak, toplam_telafi_hak, aktif
             FROM hizmetler
             WHERE id = :id AND kurum_id = :kurum_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $hizmet = $stmt->fetch();
        return $hizmet ?: null;
    }

    public static function tanismaDersi(): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT id, hizmet_adi, ucret, haftalik_katilim_sayisi, toplam_normal_hak, toplam_telafi_hak, aktif
             FROM hizmetler
             WHERE kurum_id = :kurum_id
               AND aktif = 1
               AND (LOWER(hizmet_adi) LIKE "%tanisma%" OR LOWER(hizmet_adi) LIKE "%tanışma%")
             ORDER BY toplam_normal_hak ASC, id DESC
             LIMIT 1'
        );
        $stmt->execute(self::kurumParam());
        $hizmet = $stmt->fetch();
        return $hizmet ?: null;
    }

    public static function ekle(array $veri): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO hizmetler (kurum_id, hizmet_adi, ucret, haftalik_katilim_sayisi, toplam_normal_hak, toplam_telafi_hak, aktif, olusturulma_tarihi)
             VALUES (:kurum_id, :hizmet_adi, :ucret, :haftalik_katilim_sayisi, :toplam_normal_hak, :toplam_telafi_hak, 1, NOW())'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'hizmet_adi' => $veri['hizmet_adi'],
            'ucret' => (float) $veri['ucret'],
            'haftalik_katilim_sayisi' => (int) $veri['haftalik_katilim_sayisi'],
            'toplam_normal_hak' => (int) $veri['toplam_normal_hak'],
            'toplam_telafi_hak' => (int) $veri['toplam_telafi_hak'],
        ]);

        return (int) self::db()->lastInsertId();
    }

    public static function guncelle(int $id, array $veri): bool
    {
        $stmt = self::db()->prepare(
            'UPDATE hizmetler
             SET hizmet_adi = :hizmet_adi,
                 ucret = :ucret,
                 haftalik_katilim_sayisi = :haftalik_katilim_sayisi,
                 toplam_normal_hak = :toplam_normal_hak,
                 toplam_telafi_hak = :toplam_telafi_hak,
                 aktif = :aktif
             WHERE id = :id AND kurum_id = :kurum_id'
        );

        $stmt->execute([
            'id' => $id,
            'kurum_id' => self::kurumId(),
            'hizmet_adi' => $veri['hizmet_adi'],
            'ucret' => (float) $veri['ucret'],
            'haftalik_katilim_sayisi' => (int) $veri['haftalik_katilim_sayisi'],
            'toplam_normal_hak' => (int) $veri['toplam_normal_hak'],
            'toplam_telafi_hak' => (int) $veri['toplam_telafi_hak'],
            'aktif' => (int) $veri['aktif'],
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function sil(int $id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM hizmetler WHERE id = :id AND kurum_id = :kurum_id');
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        return $stmt->rowCount() > 0;
    }
}
