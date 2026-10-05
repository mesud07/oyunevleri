<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Session;
use App\Core\SecurityHeaders;
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
$debug = Config::bool('APP_DEBUG', false);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('display_startup_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
$phpLogDirectory = BASE_PATH . '/storage/logs';
if (is_dir($phpLogDirectory) && is_writable($phpLogDirectory)) {
    $phpLogFile = $phpLogDirectory . '/php-error.log';
    if (is_file($phpLogFile) && !is_writable($phpLogFile)) {
        $phpLogFile = $phpLogDirectory . '/php-web-error.log';
    }
    if (!is_file($phpLogFile) || is_writable($phpLogFile)) {
        ini_set('error_log', $phpLogFile);
    }
}
date_default_timezone_set(Config::get('APP_TIMEZONE', 'Europe/Istanbul'));
SecurityHeaders::apply();
Session::start();
if (PHP_SAPI === 'cli' && (int) Session::get('kurum_id', 0) < 1) {
    $cliKurumId = (int) Config::get('CLI_KURUM_ID', 0);
    if ($cliKurumId > 0) {
        Session::set('kurum_id', $cliKurumId);
    }
}
SmsOtomasyonCalistirici::webIstegindenCalistir();
