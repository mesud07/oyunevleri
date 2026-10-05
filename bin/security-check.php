<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Config;
use App\Core\Veritabani;
use App\Services\NesEndpointGuvenligi;

$fail = [];
$warn = [];
$ok = [];
$check = static function (bool $condition, string $message) use (&$fail, &$ok): void {
    if ($condition) {
        $ok[] = $message;
        return;
    }
    $fail[] = $message;
};

$production = Config::get('APP_ENV') === 'production';
$check($production, 'APP_ENV production olmalı.');
$check(!Config::bool('APP_DEBUG', true), 'APP_DEBUG false olmalı.');
$check(str_starts_with((string) Config::get('APP_URL', ''), 'https://'), 'APP_URL HTTPS kullanmalı.');
$check(strlen((string) Config::get('APP_KEY', '')) >= 32, 'APP_KEY en az 32 rastgele karakter olmalı.');
$dbPassword = (string) Config::get('DB_PASSWORD', '');
$check(strlen($dbPassword) >= 16 && !in_array($dbPassword, ['talya_pass', 'root_pass', 'password'], true), 'DB_PASSWORD en az 16 karakter ve benzersiz olmalı.');
$check((int) Config::get('SESSION_IDLE_TIMEOUT', 1800) <= 1800, 'Oturum hareketsizlik süresi en fazla 30 dakika olmalı.');
$check((int) Config::get('SESSION_ABSOLUTE_LIFETIME', 43200) <= 43200, 'Mutlak oturum süresi en fazla 12 saat olmalı.');
$check((int) Config::get('SESSION_COOKIE_LIFETIME', 0) === 0, 'Oturum çerezi tarayıcı kapanınca silinmeli.');
$check(Config::bool('SESSION_COOKIE_SECURE', true), 'Canlı oturum çerezi yalnız HTTPS üzerinden gönderilmeli.');
$rememberDays = (int) Config::get('REMEMBER_LOGIN_DAYS', 30);
$check($rememberDays >= 1 && $rememberDays <= 30, 'Güvenilir cihaz oturumu en fazla 30 gün geçerli olmalı.');
$check(!Config::bool('SMS_WEB_AUTOMATION_ENABLED', true), 'SMS web otomasyonu kapalı olmalı; cron kullanılmalı.');
$check(!Config::bool('ALLOW_NES_TEST_TOOLS', false), 'NES test araçları canlıda kapalı olmalı.');
$check(Config::bool('MFA_ENFORCE', false), 'MFA_ENFORCE etkin olmalı.');
$check(Config::bool('KEY_ROTATION_CONFIRMED', false), 'Daha önce paylaşılan NES anahtarlarının yenilendiği doğrulanmalı.');
$check(strlen((string) Config::get('BACKUP_ENCRYPTION_KEY', '')) >= 24, 'Şifreli yedekleme anahtarı tanımlanmalı.');
$backupDir = trim((string) Config::get('BACKUP_DIR', ''));
$check($backupDir !== '' && str_starts_with($backupDir, '/') && !str_starts_with($backupDir . '/', BASE_PATH . '/'), 'BACKUP_DIR uygulama dizini dışında mutlak bir yol olmalı.');
$check(Config::bool('BACKUP_RESTORE_TESTED', false), 'Şifreli yedeğin izole ortamda geri dönüş provası doğrulanmalı.');
$check(Config::bool('STORAGE_ENCRYPTION_CONFIRMED', false), 'Fatura arşivini taşıyan disk/birim için beklemede şifreleme doğrulanmalı.');
$trustedProxies = preg_split('/\s*,\s*/', trim((string) Config::get('TRUSTED_PROXIES', '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
$check(array_intersect($trustedProxies, ['*', '0.0.0.0/0', '::/0']) === [], 'TRUSTED_PROXIES genel ağları kabul etmemeli.');

$envPath = BASE_PATH . '/.env';
if (is_file($envPath)) {
    $mode = fileperms($envPath) & 0777;
    $check(($mode & 0007) === 0, '.env diğer kullanıcılara kapalı olmalı (öneri: 0640 veya 0600).');
}
foreach (['storage/sessions', 'storage/logs', 'storage/cache', 'storage/faturalar'] as $relative) {
    $path = BASE_PATH . '/' . $relative;
    if (is_dir($path)) {
        $mode = fileperms($path) & 0777;
        $check(($mode & 0007) === 0, $relative . ' dünya erişimine kapalı olmalı.');
    }
}

try {
    $db = Veritabani::baglan();
    foreach (['hiz_sinirlari', 'veli_portal_dogrulamalari', 'kalici_oturumlar'] as $table) {
        $stmt = $db->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table');
        $stmt->execute(['table' => $table]);
        $check((bool) $stmt->fetchColumn(), $table . ' güvenlik tablosu bulunmalı.');
    }
    foreach (['mfa_enabled', 'mfa_secret_sifreli', 'mfa_kurtarma_kodlari_sifreli', 'oturum_surumu'] as $column) {
        $stmt = $db->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "kullanicilar" AND COLUMN_NAME = :column');
        $stmt->execute(['column' => $column]);
        $check((bool) $stmt->fetchColumn(), 'kullanicilar.' . $column . ' kolonu bulunmalı.');
    }
    $missingMfa = (int) $db->query(
        'SELECT COUNT(*) FROM kullanicilar k INNER JOIN roller r ON r.id=k.rol_id
         WHERE k.aktif=1 AND (k.sistem_yoneticisi=1 OR r.kod IN ("kurucu","muhasebe")) AND k.mfa_enabled<>1'
    )->fetchColumn();
    $check($missingMfa === 0, 'Tüm aktif yönetici, kurucu ve muhasebe hesaplarında MFA etkin olmalı.');

    $bilinenKurulumHashi = '$2y$12$4.pd6ePXI8Hrtvl352ddqej3YMl2Qk3FzLqbPoyPGv1cFGhNcbKke';
    $varsayilanHesap = $db->prepare('SELECT COUNT(*) FROM kullanicilar WHERE eposta = "admin" AND sifre = :hash');
    $varsayilanHesap->execute(['hash' => $bilinenKurulumHashi]);
    $check((int) $varsayilanHesap->fetchColumn() === 0, 'Bilinen kurulum admin hesabı/parolası bulunmamalı.');

    $integrations = $db->query('SELECT kurum_id, base_url, ortam FROM kurum_entegrasyonlari WHERE provider="nes" AND aktif=1')->fetchAll();
    if ($integrations) {
        foreach ($integrations as $integration) {
            $kurumId = (int) $integration['kurum_id'];
            $check((string) $integration['ortam'] === 'production', "Kurum {$kurumId} için aktif NES entegrasyonu canlı ortamda olmalı.");
            try {
                NesEndpointGuvenligi::dogrula((string) $integration['base_url'], (string) $integration['ortam']);
                $ok[] = "Kurum {$kurumId} NES adresi resmi allowlist ile uyumlu.";
            } catch (Throwable $e) {
                $fail[] = "Kurum {$kurumId} NES adresi güvenlik kontrolünü geçemedi.";
            }
        }
    } else {
        $warn[] = 'Aktif NES entegrasyonu bulunamadı.';
    }
} catch (Throwable $e) {
    $fail[] = 'Veritabanı güvenlik kontrolü tamamlanamadı: ' . get_class($e);
}

foreach ($ok as $message) {
    echo "[OK]   {$message}\n";
}
foreach ($warn as $message) {
    echo "[UYARI] {$message}\n";
}
foreach ($fail as $message) {
    echo "[HATA] {$message}\n";
}
echo sprintf("\nSonuç: %d başarılı, %d uyarı, %d hata.\n", count($ok), count($warn), count($fail));
exit($fail === [] ? 0 : 1);
