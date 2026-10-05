<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Veritabani;

$read = static function (string $prompt): string {
    $value = readline($prompt);
    return trim($value === false ? '' : $value);
};
$readSecret = static function (string $prompt): string {
    fwrite(STDOUT, $prompt);
    $stty = stripos(PHP_OS_FAMILY, 'Windows') === false && function_exists('shell_exec');
    if ($stty) {
        shell_exec('stty -echo');
    }
    $value = fgets(STDIN);
    if ($stty) {
        shell_exec('stty echo');
        fwrite(STDOUT, PHP_EOL);
    }
    return rtrim((string) $value, "\r\n");
};

$kurumKodu = strtoupper($read('Kurum kodu: '));
$ad = $read('Ad: ');
$soyad = $read('Soyad: ');
$eposta = mb_strtolower($read('E-posta / kullanıcı adı: '));
$sifre = $readSecret('Parola (12-128 karakter): ');
$sifreTekrar = $readSecret('Parola tekrar: ');

if ($kurumKodu === '' || $ad === '' || $soyad === '' || !filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Kurum, ad, soyad ve geçerli e-posta zorunludur.\n");
    exit(1);
}
if (strlen($sifre) < 12 || strlen($sifre) > 128 || !hash_equals($sifre, $sifreTekrar)) {
    fwrite(STDERR, "Parola 12-128 karakter olmalı ve iki giriş eşleşmelidir.\n");
    exit(1);
}

$db = Veritabani::baglan();
$kurum = $db->prepare('SELECT id FROM kurumlar WHERE kod = :kod AND aktif = 1 LIMIT 1');
$kurum->execute(['kod' => $kurumKodu]);
$kurumId = (int) ($kurum->fetchColumn() ?: 0);
$rolId = (int) ($db->query('SELECT id FROM roller WHERE kod = "kurucu" LIMIT 1')->fetchColumn() ?: 0);
if ($kurumId < 1 || $rolId < 1) {
    fwrite(STDERR, "Aktif kurum veya kurucu rolü bulunamadı.\n");
    exit(1);
}

$var = $db->prepare('SELECT 1 FROM kullanicilar WHERE kurum_id = :kurum_id AND eposta = :eposta LIMIT 1');
$var->execute(['kurum_id' => $kurumId, 'eposta' => $eposta]);
if ($var->fetchColumn()) {
    fwrite(STDERR, "Bu kurumda aynı e-posta zaten kayıtlı.\n");
    exit(1);
}

$stmt = $db->prepare(
    'INSERT INTO kullanicilar
     (kurum_id, rol_id, ad, soyad, eposta, sifre, aktif, sistem_yoneticisi, oturum_surumu, olusturulma_tarihi)
     VALUES (:kurum_id, :rol_id, :ad, :soyad, :eposta, :sifre, 1, 0, 1, NOW())'
);
$stmt->execute([
    'kurum_id' => $kurumId,
    'rol_id' => $rolId,
    'ad' => $ad,
    'soyad' => $soyad,
    'eposta' => $eposta,
    'sifre' => password_hash($sifre, PASSWORD_DEFAULT),
]);

fwrite(STDOUT, "Kurucu kullanıcı oluşturuldu. İlk girişte MFA'yı etkinleştirin.\n");
