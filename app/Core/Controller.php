<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'ana'): void
    {
        extract($data, EXTR_SKIP);
        $viewDosyasi = BASE_PATH . '/resources/views/' . $view . '.php';
        $layoutDosyasi = BASE_PATH . '/resources/views/layouts/' . $layout . '.php';
        require $layoutDosyasi;
    }
}
