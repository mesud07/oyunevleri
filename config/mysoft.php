<?php

declare(strict_types=1);

use App\Core\Config;

return [
    'base_url' => rtrim((string) Config::get('MYSOFT_BASE_URL', 'https://edocumentapi.mytest.tr'), '/'),
    'client_id' => (string) Config::get('MYSOFT_CLIENT_ID', ''),
    'client_secret' => (string) Config::get('MYSOFT_CLIENT_SECRET', ''),
    'tenant_identifier' => (string) Config::get('MYSOFT_TENANT_IDENTIFIER', ''),
    'environment' => (string) Config::get('MYSOFT_ENVIRONMENT', 'test'),
    'auto_invoice' => Config::bool('MYSOFT_AUTO_INVOICE', false),
    'connect_timeout' => (int) Config::get('MYSOFT_CONNECT_TIMEOUT', '10'),
    'timeout' => (int) Config::get('MYSOFT_TIMEOUT', '45'),
    'token_refresh_margin' => (int) Config::get('MYSOFT_TOKEN_REFRESH_MARGIN', '30'),
];
