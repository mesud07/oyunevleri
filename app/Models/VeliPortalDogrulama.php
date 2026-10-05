<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Config;
use App\Core\Model;
use DateTimeImmutable;

final class VeliPortalDogrulama extends Model
{
    public static function olustur(int $kurumId, string $telefon, string $kod): int
    {
        self::temizle();
        $stmt = self::db()->prepare(
            'INSERT INTO veli_portal_dogrulamalari
             (kurum_id, telefon_hash, kod_hash, deneme_sayisi, son_kullanim_tarihi, olusturulma_tarihi)
             VALUES (:kurum_id, :telefon_hash, :kod_hash, 0, :son_kullanim_tarihi, NOW())'
        );
        $stmt->execute([
            'kurum_id' => $kurumId,
            'telefon_hash' => self::telefonHash($telefon),
            'kod_hash' => password_hash($kod, PASSWORD_DEFAULT),
            'son_kullanim_tarihi' => (new DateTimeImmutable('+5 minutes'))->format('Y-m-d H:i:s'),
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function dogrula(int $id, int $kurumId, string $telefon, string $kod): bool
    {
        $db = self::db();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT * FROM veli_portal_dogrulamalari WHERE id = :id AND kurum_id = :kurum_id FOR UPDATE');
            $stmt->execute(['id' => $id, 'kurum_id' => $kurumId]);
            $row = $stmt->fetch();
            $gecerli = $row
                && hash_equals((string) $row['telefon_hash'], self::telefonHash($telefon))
                && empty($row['dogrulanma_tarihi'])
                && (int) $row['deneme_sayisi'] < 5
                && strtotime((string) $row['son_kullanim_tarihi']) >= time()
                && preg_match('/^\d{6}$/', $kod)
                && password_verify($kod, (string) $row['kod_hash']);

            if ($row) {
                $update = $db->prepare(
                    'UPDATE veli_portal_dogrulamalari
                     SET deneme_sayisi = deneme_sayisi + 1,
                         dogrulanma_tarihi = CASE WHEN :gecerli = 1 THEN NOW() ELSE dogrulanma_tarihi END
                     WHERE id = :id AND kurum_id = :kurum_id'
                );
                $update->execute(['id' => $id, 'kurum_id' => $kurumId, 'gecerli' => $gecerli ? 1 : 0]);
            }
            $db->commit();
            return (bool) $gecerli;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    private static function telefonHash(string $telefon): string
    {
        return hash_hmac('sha256', $telefon, (string) Config::get('APP_KEY', 'talya-veli-local-key'));
    }

    private static function temizle(): void
    {
        self::db()->exec('DELETE FROM veli_portal_dogrulamalari WHERE olusturulma_tarihi < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    }
}
