<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require dirname(__DIR__) . '/bootstrap.php';

use App\Services\SmsServisi;

$lockPath = BASE_PATH . '/storage/sms-kuyrugu.lock';
$lock = fopen($lockPath, 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "SMS kuyrugu zaten calisiyor.\n";
    exit(0);
}

$limit = (int) ($argv[1] ?? 50);
$sonuc = (new SmsServisi())->kuyrukIsle($limit);
echo json_encode($sonuc, JSON_UNESCAPED_UNICODE) . PHP_EOL;
