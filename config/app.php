<?php

declare(strict_types=1);

use App\Core\Config;

return [
    'env' => Config::get('APP_ENV', 'local'),
    'debug' => Config::bool('APP_DEBUG', false),
    'url' => Config::get('APP_URL', ''),
    'timezone' => Config::get('APP_TIMEZONE', 'Europe/Istanbul'),
];
