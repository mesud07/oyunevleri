<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\YetkiServisi;

final class YetkiKontrolu
{
    public function kontrol(string $yetki): bool
    {
        return (new YetkiServisi())->izinliMi($yetki);
    }
}
