<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Session;
use App\Services\SmsOtomasyonCalistirici;

define('BASE_PATH', __DIR__);

$composerAutoload = BASE_PATH . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = BASE_PATH . '/app/' . $relative . '.php';

    if (is_file($file)) {
        require $file;
    }
});

require_once BASE_PATH . '/app/Helpers/genel.php';
require_once BASE_PATH . '/app/Helpers/tarih.php';
require_once BASE_PATH . '/app/Helpers/para.php';
require_once BASE_PATH . '/app/Helpers/metin.php';

Config::load(BASE_PATH . '/.env');
date_default_timezone_set(Config::get('APP_TIMEZONE', 'Europe/Istanbul'));
Session::start();
SmsOtomasyonCalistirici::webIstegindenCalistir();
