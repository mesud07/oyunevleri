<?php

declare(strict_types=1);

use App\Core\Config;

return [
    'host' => Config::get('DB_HOST', 'db'),
    'port' => Config::get('DB_PORT', '3306'),
    'database' => Config::get('DB_DATABASE', 'talya_db'),
    'username' => Config::get('DB_USERNAME', 'talya_user'),
];
