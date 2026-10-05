<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Veritabani;

$db = Veritabani::baglan();

$db->exec(file_get_contents(BASE_PATH . '/database/migrations/20260820_guvenlik_hiz_sinirlari.sql'));
$db->exec(file_get_contents(BASE_PATH . '/database/migrations/20260820_veli_portal_otp.sql'));
$db->exec(file_get_contents(BASE_PATH . '/database/migrations/20260822_kalici_oturumlar.sql'));

$columnExists = static function (string $table, string $column) use ($db): bool {
    $stmt = $db->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column');
    $stmt->execute(['table' => $table, 'column' => $column]);
    return (bool) $stmt->fetchColumn();
};
$indexExists = static function (string $table, string $index) use ($db): bool {
    $stmt = $db->prepare('SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index');
    $stmt->execute(['table' => $table, 'index' => $index]);
    return (bool) $stmt->fetchColumn();
};

$columns = [
    ['randevular', 'katilim_token_hash', 'CHAR(64) NULL AFTER katilim_token'],
    ['randevular', 'katilim_token_son_kullanim', 'DATETIME NULL AFTER katilim_token_hash'],
    ['randevular', 'katilim_token_iptal_tarihi', 'DATETIME NULL AFTER katilim_token_son_kullanim'],
    ['kullanicilar', 'mfa_enabled', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER sistem_yoneticisi'],
    ['kullanicilar', 'mfa_secret_sifreli', 'TEXT NULL AFTER mfa_enabled'],
    ['kullanicilar', 'mfa_kurtarma_kodlari_sifreli', 'TEXT NULL AFTER mfa_secret_sifreli'],
    ['kullanicilar', 'oturum_surumu', 'INT UNSIGNED NOT NULL DEFAULT 1 AFTER mfa_kurtarma_kodlari_sifreli'],
];
foreach ($columns as [$table, $column, $definition]) {
    if (!$columnExists($table, $column)) {
        $db->exec(sprintf('ALTER TABLE `%s` ADD COLUMN `%s` %s', $table, $column, $definition));
    }
}
if (!$indexExists('randevular', 'uq_randevu_katilim_token_hash')) {
    $db->exec('ALTER TABLE randevular ADD UNIQUE KEY uq_randevu_katilim_token_hash (katilim_token_hash)');
}
$db->exec(
    "UPDATE randevular
     SET katilim_token_hash = SHA2(katilim_token, 256),
         katilim_token_son_kullanim = DATE_ADD(TIMESTAMP(tarih, baslangic_saati), INTERVAL 1 DAY),
         katilim_token = NULL
     WHERE katilim_token IS NOT NULL AND katilim_token <> ''"
);

echo "Güvenlik migrasyonları tamamlandı.\n";
