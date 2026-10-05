<?php

declare(strict_types=1);

use App\Core\Config;

return [
    'base_url' => Config::get('NES_BASE_URL', 'https://apitest.nes.com.tr'),
    'api_key' => Config::get('NES_API_KEY', ''),
    'environment' => Config::get('NES_ENVIRONMENT', 'test'),
    'connect_timeout' => (int) Config::get('NES_CONNECT_TIMEOUT', '10'),
    'timeout' => (int) Config::get('NES_TIMEOUT', '45'),
    'max_response_bytes' => (int) Config::get('NES_MAX_RESPONSE_BYTES', '20971520'),
];
