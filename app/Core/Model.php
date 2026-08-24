<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Model
{
    protected static function db(): PDO
    {
        return Veritabani::baglan();
    }

    protected static function kurumId(): int
    {
        return max(1, (int) (Session::get('kurum_id') ?: 1));
    }

    protected static function kurumParam(): array
    {
        return ['kurum_id' => self::kurumId()];
    }
}
