<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Veritabani;

final class YetkiServisi
{
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

        $yetkiler = [$yetki];
        if ($yetki === 'randevu_durum_degistir') {
            $yetkiler[] = 'randevu_ekle';
        }

        $stmt = Veritabani::baglan()->prepare(
            'SELECT 1
             FROM rol_yetkileri ry
             INNER JOIN roller r ON r.id = ry.rol_id
             WHERE r.kod = ? AND ry.yetki IN (' . implode(',', array_fill(0, count($yetkiler), '?')) . ')
             LIMIT 1'
        );
        $stmt->execute(array_merge([(string) $kullanici['rol_kodu']], $yetkiler));

        return (bool) $stmt->fetchColumn();
    }
}
