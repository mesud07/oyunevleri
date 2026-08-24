<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    private static array $values = [];

    public static function load(string $envPath): void
    {
        if (is_file($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$key, $value] = explode('=', $line, 2);
                self::$values[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
            }
        }

        foreach ($_ENV as $key => $value) {
            self::$values[$key] = (string) $value;
        }

        foreach ($_SERVER as $key => $value) {
            if (is_string($value) && preg_match('/^[A-Z0-9_]+$/', $key)) {
                self::$values[$key] = $value;
            }
        }
    }

    public static function get(string $key, $default = null)
    {
        return self::$values[$key] ?? getenv($key) ?: $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default ? 'true' : 'false');
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
