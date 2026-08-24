<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require dirname(__DIR__) . '/bootstrap.php';

use App\Services\SmsServisi;

$lockPath = BASE_PATH . '/storage/odeme-sozu-sms-hatirlatma.lock';
$lock = fopen($lockPath, 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "Odeme sozu SMS hatirlatma zaten calisiyor.\n";
    exit(0);
}

$adet = (new SmsServisi())->odemeSozuHatirlatmalariOlustur();
echo "Kuyruga eklenen odeme sozu hatirlatmasi: {$adet}\n";
