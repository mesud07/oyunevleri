<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Session;
use App\Models\Kurum;
use App\Services\SmsServisi;

$lockPath = BASE_PATH . '/storage/odeme-sozu-sms-hatirlatma.lock';
$lock = fopen($lockPath, 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "Odeme sozu SMS hatirlatma zaten calisiyor.\n";
    exit(0);
}

$toplam = 0;
$hatalar = [];
foreach (Kurum::aktifIdler() as $kurumId) {
    Session::set('kurum_id', $kurumId);
    try {
        $toplam += (new SmsServisi())->odemeSozuHatirlatmalariOlustur();
    } catch (Throwable $e) {
        $hatalar[] = ['kurum_id' => $kurumId, 'mesaj' => $e->getMessage()];
    }
}
echo json_encode(['kuyruga_eklenen' => $toplam, 'hatalar' => $hatalar], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
