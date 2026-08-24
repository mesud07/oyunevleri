<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    public static function gerekli(array $data, array $fields): array
    {
        $hatalar = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                $hatalar[$field] = 'Bu alan zorunludur.';
            }
        }
        return $hatalar;
    }
}
