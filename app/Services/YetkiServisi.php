<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Veritabani;
use App\Models\Personel;

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

        if (!(new KurumModuluServisi())->yetkiIcinAktifMi($yetki, (int) $kullanici['kurum_id'])) {
            return false;
        }

        if (($kullanici['rol_kodu'] ?? '') === 'kurucu') {
            return true;
        }

        // Paylaşımlı sunucuda SQL migration komutu çalıştırılamadığı için bu modül
        // ilk yetki kontrolünde kendi şemasını ve varsayılan rol izinlerini bir kez hazırlar.
        if (str_starts_with($yetki, 'personel_')) {
            Personel::semayiHazirla();
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

        if ((bool) $stmt->fetchColumn()) {
            return true;
        }

        $ekYetki = Veritabani::baglan()->prepare(
            'SELECT 1
             FROM kullanici_ek_yetkileri
             WHERE kurum_id = ? AND kullanici_id = ?
               AND yetki IN (' . implode(',', array_fill(0, count($yetkiler), '?')) . ')
             LIMIT 1'
        );
        $ekYetki->execute(array_merge([
            (int) $kullanici['kurum_id'],
            (int) $kullanici['id'],
        ], $yetkiler));

        return (bool) $ekYetki->fetchColumn();
    }
}
