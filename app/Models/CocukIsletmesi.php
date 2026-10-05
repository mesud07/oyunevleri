<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class CocukIsletmesi extends Model
{
    public static function liste(): array
    {
        return self::db()->query(
            'SELECT id, ad, kategori, adres, ilce, telefon, web_sitesi, enlem, boylam, kaynak
             FROM cocuk_isletmeleri
             WHERE aktif = 1
               AND (kaynak <> "google_places" OR onbellek_son_tarihi >= NOW())
             ORDER BY kategori ASC, ad ASC'
        )->fetchAll();
    }

    public static function sonAktarim(): ?array
    {
        $kayit = self::db()->query(
            'SELECT kayit_sayisi, aktarim_tarihi
             FROM cocuk_isletme_aktarimlari
             ORDER BY id DESC LIMIT 1'
        )->fetch();
        return $kayit ?: null;
    }
}
