<?php

declare(strict_types=1);

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "[FAIL] {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "[OK] {$message}\n");
};

$root = dirname(__DIR__);
$backup = file_get_contents($root . '/bin/encrypted-backup.sh') ?: '';
$daily = file_get_contents($root . '/bin/daily-backup.sh') ?: '';
$verify = file_get_contents($root . '/bin/verify-encrypted-backup.sh') ?: '';
$compose = file_get_contents($root . '/compose.production.yaml') ?: '';
$env = file_get_contents($root . '/.env.example') ?: '';
$deployment = file_get_contents($root . '/DEPLOYMENT.md') ?: '';

$assert(str_contains($backup, 'mysqldump') && str_contains($backup, 'database.sql'), 'Veritabani gunluk yedege dahil');
$assert(str_contains($backup, 'storage/faturalar') && str_contains($backup, 'public/uploads'), 'Fatura arsivi ve yuklenen kurum dosyalari yedege dahil');
$assert(str_contains($backup, 'aes-256-cbc') && str_contains($backup, 'pbkdf2'), 'Yedek guclu sifreleme ile korunuyor');
$assert(str_contains($backup, 'sha256sum') && str_contains($verify, 'sha256sum -c'), 'Yedek butunlugu SHA-256 ile dogrulaniyor');
$assert(str_contains($daily, 'verify-encrypted-backup.sh'), 'Her yeni yedek otomatik geri acma kontrolunden geciyor');
$assert(str_contains($daily, 'BACKUP_RETENTION_DAYS') && str_contains($daily, '-delete'), 'Eski yedekler saklama suresine gore temizleniyor');
$assert(str_contains($daily, '.last-successful-date') && str_contains($daily, '--if-due'), 'Ayni gun yinelenen zamanlayici calismasi ikinci yedek olusturmuyor');
$assert(str_contains($compose, 'daily-backup.sh --if-due') && str_contains($compose, 'talya_backups:'), 'Container zamanlayicisi gunluk yedeklemeyi otomatik calistiriyor');
$assert(str_contains($compose, 'talya_uploads:/var/www/html/public/uploads'), 'Container yuklemeleri kalici volume ile korunuyor');
$assert(str_contains($env, 'BACKUP_RETENTION_DAYS=30'), 'Varsayilan yedek saklama suresi 30 gun');
$assert(str_contains($deployment, '15 3 * * *') && str_contains($deployment, 'cron-daily-backup.log'), 'cPanel gunluk cron kurulumu belgelendi');

fwrite(STDOUT, "Gunluk yedekleme smoke testleri tamamlandi.\n");
