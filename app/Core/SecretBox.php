<?php

declare(strict_types=1);

namespace App\Core;

final class SecretBox
{
    public static function encrypt(string $plainText): string
    {
        if ($plainText === '') {
            return '';
        }
        $key = self::key();
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plainText, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            throw new \RuntimeException('Gizli bilgi sifrelenemedi.');
        }
        return 'v1:' . base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt(string $encoded): string
    {
        if ($encoded === '') {
            return '';
        }
        if (!str_starts_with($encoded, 'v1:')) {
            throw new \RuntimeException('Gizli bilgi formati gecersiz.');
        }
        $raw = base64_decode(substr($encoded, 3), true);
        if ($raw === false || strlen($raw) < 29) {
            throw new \RuntimeException('Gizli bilgi okunamadi.');
        }
        $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
        if ($plain === false) {
            throw new \RuntimeException('Gizli bilgi cozulemedi. APP_KEY degismis olabilir.');
        }
        return $plain;
    }

    private static function key(): string
    {
        $appKey = trim((string) Config::get('APP_KEY', ''));
        if ($appKey === '') {
            throw new \RuntimeException('Gizli entegrasyon bilgilerini saklamak icin APP_KEY tanimlanmalidir.');
        }
        return hash('sha256', $appKey, true);
    }
}
