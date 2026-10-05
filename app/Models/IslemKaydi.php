<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Request;
use App\Core\Session;

final class IslemKaydi extends Model
{
    public static function ekle(?int $kullaniciId, string $islem, string $aciklama, array $veri = []): void
    {
        $kurumId = self::islemKurumu($kullaniciId, $veri);
        $stmt = self::db()->prepare(
            'INSERT INTO islem_kayitlari (kurum_id, kullanici_id, islem, aciklama, veri, ip_adresi, olusturulma_tarihi)
             VALUES (:kurum_id, :kullanici_id, :islem, :aciklama, :veri, :ip_adresi, NOW())'
        );
        $stmt->execute([
            'kurum_id' => $kurumId,
            'kullanici_id' => $kullaniciId,
            'islem' => $islem,
            'aciklama' => $aciklama,
            'veri' => json_encode($veri, JSON_UNESCAPED_UNICODE),
            'ip_adresi' => Request::clientIp(),
        ]);
    }

    private static function islemKurumu(?int $kullaniciId, array $veri): int
    {
        $kurumId = max(0, (int) ($veri['kurum_id'] ?? 0));
        if ($kurumId > 0) {
            return $kurumId;
        }

        if (!empty($veri['kurum_kodu'])) {
            $stmt = self::db()->prepare('SELECT id FROM kurumlar WHERE kod = :kod AND aktif = 1 LIMIT 1');
            $stmt->execute(['kod' => strtoupper(trim((string) $veri['kurum_kodu']))]);
            $kurumId = (int) $stmt->fetchColumn();
        } elseif (($kullaniciId ?? 0) > 0) {
            $stmt = self::db()->prepare('SELECT kurum_id FROM kullanicilar WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $kullaniciId]);
            $kurumId = (int) $stmt->fetchColumn();
        } else {
            $kurumId = max(0, (int) Session::get('kurum_id', 0));
        }

        if ($kurumId < 1) {
            throw new \RuntimeException('İşlem kaydı için kurum belirlenemedi.');
        }

        return $kurumId;
    }
}
