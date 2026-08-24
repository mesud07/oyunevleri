<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Models\IslemKaydi;

final class LogServisi
{
    public function yaz(string $islem, string $aciklama, array $veri = []): void
    {
        $kullanici = Auth::user();
        IslemKaydi::ekle($kullanici ? (int) $kullanici['id'] : null, $islem, $aciklama, $veri);
    }
}
