<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class IslemKaydi extends Model
{
    public static function ekle(?int $kullaniciId, string $islem, string $aciklama, array $veri = []): void
    {
        $stmt = self::db()->prepare(
            'INSERT INTO islem_kayitlari (kullanici_id, islem, aciklama, veri, ip_adresi, olusturulma_tarihi)
             VALUES (:kullanici_id, :islem, :aciklama, :veri, :ip_adresi, NOW())'
        );
        $stmt->execute([
            'kullanici_id' => $kullaniciId,
            'islem' => $islem,
            'aciklama' => $aciklama,
            'veri' => json_encode($veri, JSON_UNESCAPED_UNICODE),
            'ip_adresi' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
