<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require dirname(__DIR__) . '/bootstrap.php';

use App\Services\SmsServisi;

$lockPath = BASE_PATH . '/storage/sms-durum-sorgula.lock';
$lock = fopen($lockPath, 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "SMS durum sorgu zaten calisiyor.\n";
    exit(0);
}

$limit = (int) ($argv[1] ?? 100);
$adet = (new SmsServisi())->durumlariSorgula($limit);
echo "Guncellenen SMS durumu: {$adet}\n";
