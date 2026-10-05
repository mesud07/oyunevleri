<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\YetkiServisi;
use App\Services\MfaServisi;
use App\Services\KurumModuluServisi;

final class Router
{
    private array $routes = [];

    public function get(string $path, array $handler, ?string $permission = null): void
    {
        $this->routes['GET'][$path] = ['handler' => $handler, 'permission' => $permission];
    }

    public function post(string $path, array $handler, ?string $permission = null): void
    {
        $this->routes['POST'][$path] = ['handler' => $handler, 'permission' => $permission];
    }

    public function dispatch(string $method, string $uri): void
    {
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';
        $route = $this->routes[$method][$path] ?? null;

        if (!$route) {
            http_response_code(404);
            require BASE_PATH . '/resources/views/errors/404.php';
            return;
        }

        if (str_starts_with($path, '/panel')) {
            if (!Auth::check()) {
                Response::redirect('/giris');
            }
            $user = Auth::user();
            if ($path !== '/panel/guvenlik/mfa' && $user && (new MfaServisi())->kayitGerekliMi($user)) {
                Response::redirect('/panel/guvenlik/mfa');
            }
            if (!(new KurumModuluServisi())->yolIcinAktifMi($path, Auth::kurumId())) {
                http_response_code(403);
                require BASE_PATH . '/resources/views/errors/403.php';
                return;
            }
            $permission = $route['permission'] ?? null;
            if (is_string($permission) && $permission !== '' && !(new YetkiServisi())->izinliMi($permission)) {
                http_response_code(403);
                require BASE_PATH . '/resources/views/errors/403.php';
                return;
            }
        }

        $handler = $route['handler'];
        [$class, $methodName] = $handler;
        (new $class())->{$methodName}();
    }
}
