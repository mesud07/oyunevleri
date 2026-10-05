<?php

declare(strict_types=1);

// PHP'nin yerlesik sunucusunda mevcut statik dosyalari uygulama router'ina
// gondermeyip dogrudan sunucunun servis etmesini sagla.
if (PHP_SAPI === 'cli-server') {
    $publicRoot = realpath(__DIR__);
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $requestedFile = is_string($requestPath) && $publicRoot !== false
        ? realpath($publicRoot . DIRECTORY_SEPARATOR . ltrim(rawurldecode($requestPath), '/'))
        : false;

    if (
        $requestedFile !== false
        && str_starts_with($requestedFile, $publicRoot . DIRECTORY_SEPARATOR)
        && is_file($requestedFile)
    ) {
        return false;
    }
}

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Config;
use App\Core\Router;

try {
    $router = new Router();
    $routes = require BASE_PATH . '/config/routes.php';
    $routes($router);
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
} catch (Throwable $e) {
    $referans = bin2hex(random_bytes(4));
    error_log(sprintf(
        'Beklenmeyen uygulama hatasi [%s] %s: %s (%s:%d)',
        $referans,
        get_class($e),
        preg_replace('/[\r\n]+/', ' ', mb_substr($e->getMessage(), 0, 500)),
        basename($e->getFile()),
        $e->getLine()
    ));
    http_response_code(500);
    if (Config::bool('APP_DEBUG', false)) {
        echo '<pre>' . e($e->getMessage()) . "\n" . e($e->getTraceAsString()) . '</pre>';
        exit;
    }
    $hataReferansi = $referans;
    require BASE_PATH . '/resources/views/errors/500.php';
}
