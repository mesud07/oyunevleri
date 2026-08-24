<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;

final class CsrfKontrolu
{
    public function kontrol(?string $token): bool
    {
        return Csrf::dogrula($token);
    }
}
