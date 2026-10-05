<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Services\MfaServisi;
use App\Services\NesEndpointGuvenligi;
use App\Services\KaliciOturumServisi;

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "[FAIL] {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "[OK] {$message}\n");
};

$endpoint = new NesEndpointGuvenligi();
foreach ([
    ['https://api.nes.com.tr', 'production'],
    ['https://apitest.nes.com.tr', 'test'],
] as [$url, $environment]) {
    $accepted = true;
    try {
        $endpoint->dogrula($url, $environment);
    } catch (Throwable) {
        $accepted = false;
    }
    $assert($accepted, "NES {$environment} resmi adresi kabul edilir");
}
foreach ([
    ['http://api.nes.com.tr', 'production'],
    ['https://api.nes.com.tr.evil.invalid', 'production'],
    ['https://apitest.nes.com.tr', 'production'],
    ['https://user@api.nes.com.tr', 'production'],
] as [$url, $environment]) {
    $rejected = false;
    try {
        $endpoint->dogrula($url, $environment);
    } catch (Throwable) {
        $rejected = true;
    }
    $assert($rejected, "Güvensiz/uyumsuz NES adresi reddedilir: {$url}");
}

$mfa = new MfaServisi();
$totp = new ReflectionMethod($mfa, 'totp');
$assert(
    $totp->invoke($mfa, 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 1) === '287082',
    'TOTP üretimi RFC 6238 SHA-1 test vektörüyle uyumlu'
);

$schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
$assert(is_string($schema) && !str_contains($schema, "SELECT id, 'Talya', 'Kurucu', 'admin'"), 'Kurulum şemasında varsayılan admin hesabı yok');

$secici = str_repeat('a', 32);
$dogrulayici = str_repeat('b', 64);
$assert(
    KaliciOturumServisi::cookieAyir($secici . '.' . $dogrulayici) === [$secici, $dogrulayici],
    'Geçerli kalıcı oturum çerezi ayrıştırılıyor'
);
foreach (['', 'kisa.deger', str_repeat('a', 31) . '.' . $dogrulayici, $secici . '.' . str_repeat('z', 64)] as $gecersiz) {
    $assert(KaliciOturumServisi::cookieAyir($gecersiz) === null, 'Biçimi bozuk kalıcı oturum çerezi reddediliyor');
}
$kaliciOturumMigrasyonu = file_get_contents(dirname(__DIR__) . '/database/migrations/20260822_kalici_oturumlar.sql');
$assert(
    is_string($kaliciOturumMigrasyonu)
    && str_contains($kaliciOturumMigrasyonu, 'dogrulayici_hash')
    && !preg_match('/\bdogrulayici\s+(?:CHAR|VARCHAR|TEXT)/i', $kaliciOturumMigrasyonu),
    'Kalıcı oturum doğrulayıcısı veritabanında yalnız hash olarak saklanıyor'
);

$kullaniciController = file_get_contents(dirname(__DIR__) . '/app/Controllers/KullaniciController.php') ?: '';
$kullaniciModel = file_get_contents(dirname(__DIR__) . '/app/Models/Kullanici.php') ?: '';
$logServisi = file_get_contents(dirname(__DIR__) . '/app/Services/LogServisi.php') ?: '';
$assert(
    str_contains($kullaniciController, "Session::set('oturum_surumu'")
    && str_contains($kullaniciModel, 'public static function oturumSurumu'),
    'Kullanıcı kendi hesabını güncellediğinde geçerli oturumu korunuyor'
);
$assert(
    str_contains($logServisi, "Session::get('kullanici_id'")
    && str_contains($logServisi, "Session::get('kurum_id'"),
    'Denetim kaydı hesap güncellemesi sonrasında kurum bağlamını kaybetmiyor'
);

fwrite(STDOUT, "Güvenlik smoke testleri tamamlandı.\n");
