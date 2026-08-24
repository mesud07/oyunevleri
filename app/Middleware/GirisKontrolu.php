<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;

final class GirisKontrolu
{
    public function kontrol(): bool
    {
        return Auth::check();
    }
}
