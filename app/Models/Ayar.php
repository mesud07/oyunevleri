<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

final class Ayar extends Model
{
    public static function deger(string $anahtar, ?string $varsayilan = null): ?string
    {
        $stmt = self::db()->prepare('SELECT deger FROM ayarlar WHERE kurum_id = :kurum_id AND anahtar = :anahtar LIMIT 1');
        $stmt->execute(['kurum_id' => self::kurumId(), 'anahtar' => $anahtar]);
        $deger = $stmt->fetchColumn();

        return $deger === false ? $varsayilan : (string) $deger;
    }

    public static function coklu(array $varsayilanlar): array
    {
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

}
