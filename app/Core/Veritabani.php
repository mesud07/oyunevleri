<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Veritabani
{
    private static ?PDO $baglanti = null;

    public static function baglan(): PDO
    {
        if (self::$baglanti instanceof PDO) {
            return self::$baglanti;
        }

        $host = Config::get('DB_HOST', 'db');
        $port = Config::get('DB_PORT', '3306');
        $db = Config::get('DB_DATABASE', 'talya_db');
        $user = Config::get('DB_USERNAME', 'talya_user');
        $pass = Config::get('DB_PASSWORD', 'talya_pass');
        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

        self::$baglanti = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$baglanti;
    }
}
