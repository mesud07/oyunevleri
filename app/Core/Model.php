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
        $kurumId = max(0, (int) Session::get('kurum_id', 0));
        if ($kurumId < 1) {
            throw new \RuntimeException('Bu işlem için geçerli kurum bağlamı zorunludur.');
        }

        return $kurumId;
    }

    protected static function kurumParam(): array
    {
        return ['kurum_id' => self::kurumId()];
    }
}
