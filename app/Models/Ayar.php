<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

final class Ayar extends Model
{
    private static bool $tabloHazir = false;

    public static function deger(string $anahtar, ?string $varsayilan = null): ?string
    {
        self::tabloHazirla();
        $stmt = self::db()->prepare('SELECT deger FROM ayarlar WHERE kurum_id = :kurum_id AND anahtar = :anahtar LIMIT 1');
        $stmt->execute(['kurum_id' => self::kurumId(), 'anahtar' => $anahtar]);
        $deger = $stmt->fetchColumn();

        return $deger === false ? $varsayilan : (string) $deger;
    }

    public static function coklu(array $varsayilanlar): array
    {
        self::tabloHazirla();
        if ($varsayilanlar === []) {
            return [];
        }

        $anahtarlar = array_keys($varsayilanlar);
        $yerler = implode(',', array_fill(0, count($anahtarlar), '?'));
        $stmt = self::db()->prepare("SELECT anahtar, deger FROM ayarlar WHERE kurum_id = ? AND anahtar IN ($yerler)");
        $stmt->execute(array_merge([self::kurumId()], $anahtarlar));

        $sonuc = $varsayilanlar;
        foreach ($stmt->fetchAll(PDO::FETCH_KEY_PAIR) as $anahtar => $deger) {
            $sonuc[$anahtar] = (string) $deger;
        }

        return $sonuc;
    }

    public static function kaydet(string $anahtar, string $deger, ?string $aciklama = null): void
    {
        self::tabloHazirla();
        $stmt = self::db()->prepare(
            'INSERT INTO ayarlar (kurum_id, anahtar, deger, aciklama)
             VALUES (:kurum_id, :anahtar, :deger, :aciklama)
             ON DUPLICATE KEY UPDATE deger = VALUES(deger), aciklama = COALESCE(VALUES(aciklama), aciklama)'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'anahtar' => $anahtar,
            'deger' => $deger,
            'aciklama' => $aciklama,
        ]);
    }

    public static function kaydetCoklu(array $veriler, array $aciklamalar = []): void
    {
        foreach ($veriler as $anahtar => $deger) {
            self::kaydet((string) $anahtar, (string) $deger, $aciklamalar[$anahtar] ?? null);
        }
    }

    private static function tabloHazirla(): void
    {
        if (self::$tabloHazir) {
            return;
        }

        self::db()->exec(
            'CREATE TABLE IF NOT EXISTS ayarlar (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                kurum_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
                anahtar VARCHAR(120) NOT NULL,
                deger TEXT NULL,
                aciklama TEXT NULL,
                guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_ayarlar_kurum_anahtar (kurum_id, anahtar),
                KEY idx_ayarlar_kurum (kurum_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        self::kurumKolonuHazirla();
        self::$tabloHazir = true;
    }

    private static function kurumKolonuHazirla(): void
    {
        $db = self::db();

        $kolon = $db->query("SHOW COLUMNS FROM ayarlar LIKE 'kurum_id'");
        if (!$kolon || !$kolon->fetch()) {
            $db->exec('ALTER TABLE ayarlar ADD COLUMN kurum_id BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id');
        }

        $indeks = $db->query("SHOW INDEX FROM ayarlar WHERE Key_name = 'uq_ayarlar_kurum_anahtar'");
        if (!$indeks || !$indeks->fetch()) {
            try {
                $db->exec('ALTER TABLE ayarlar DROP INDEX anahtar');
            } catch (\Throwable $e) {
            }
            $db->exec('ALTER TABLE ayarlar ADD UNIQUE KEY uq_ayarlar_kurum_anahtar (kurum_id, anahtar)');
        }

        $kurumIndeks = $db->query("SHOW INDEX FROM ayarlar WHERE Key_name = 'idx_ayarlar_kurum'");
        if (!$kurumIndeks || !$kurumIndeks->fetch()) {
            $db->exec('ALTER TABLE ayarlar ADD INDEX idx_ayarlar_kurum (kurum_id)');
        }
    }
}
