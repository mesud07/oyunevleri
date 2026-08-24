<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Config;
use App\Core\Router;

try {
    $router = new Router();
    $routes = require BASE_PATH . '/config/routes.php';
    $routes($router);
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
} catch (Throwable $e) {
    http_response_code(500);
    if (Config::bool('APP_DEBUG', false)) {
        echo '<pre>' . e($e->getMessage()) . "\n" . e($e->getTraceAsString()) . '</pre>';
        exit;
    }
    require BASE_PATH . '/resources/views/errors/500.php';
}
