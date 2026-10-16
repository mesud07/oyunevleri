<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require dirname(__DIR__) . '/bootstrap.php';

use App\Services\SmsOtomasyonCalistirici;
use App\Services\DemoVerisiYenileyici;

$lockPath = BASE_PATH . '/storage/sms-otomasyon.lock';
$lock = fopen($lockPath, 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "SMS otomasyonu zaten calisiyor.\n";
    exit(0);
}

$limit = max(1, (int) ($argv[1] ?? 100));
$demoSonCalismaDosyasi = BASE_PATH . '/storage/demo-verisi-yenile.last';
$demoSonCalismaGunu = is_file($demoSonCalismaDosyasi) ? trim((string) file_get_contents($demoSonCalismaDosyasi)) : '';
if ($demoSonCalismaGunu !== date('Y-m-d')) {
    try {
        DemoVerisiYenileyici::yenile();
        file_put_contents($demoSonCalismaDosyasi, date('Y-m-d'));
    } catch (Throwable $e) {
        error_log('[demo-verisi-yenile] ' . $e->getMessage());
    }
}
$sonuclar = SmsOtomasyonCalistirici::tumKurumlarIcinCalistir($limit, true, 'cron');
echo json_encode($sonuclar, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
