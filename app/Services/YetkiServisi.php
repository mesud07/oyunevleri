<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Veritabani;

final class YetkiServisi
{
    private static array $rolYetkileri = [];

    public function izinliMi(string $yetki): bool
    {
        $kullanici = Auth::user();
        if (!$kullanici) {
            return false;
        }

        if ($yetki === 'sistem_yonetimi') {
            return (int) ($kullanici['sistem_yoneticisi'] ?? 0) === 1;
        }

        if (($kullanici['rol_kodu'] ?? '') === 'kurucu') {
            return true;
        }

        $rolKodu = (string) ($kullanici['rol_kodu'] ?? '');
        if ($rolKodu === '') {
            return false;
        }

        if (!array_key_exists($rolKodu, self::$rolYetkileri)) {
            $stmt = Veritabani::baglan()->prepare(
                'SELECT ry.yetki
             FROM rol_yetkileri ry
             INNER JOIN roller r ON r.id = ry.rol_id
             WHERE r.kod = ?'
            );
            $stmt->execute([$rolKodu]);
            self::$rolYetkileri[$rolKodu] = array_fill_keys(array_map('strval', $stmt->fetchAll(\PDO::FETCH_COLUMN)), true);
        }

        $rolYetkileri = self::$rolYetkileri[$rolKodu];
        return isset($rolYetkileri[$yetki])
            || ($yetki === 'randevu_durum_degistir' && isset($rolYetkileri['randevu_ekle']));
    }
}
