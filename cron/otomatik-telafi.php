<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Bu dosya yalnizca CLI ile calisir.');
}

require dirname(__DIR__) . '/bootstrap.php';

echo 'Otomatik telafi denetimi calisti.' . PHP_EOL;
