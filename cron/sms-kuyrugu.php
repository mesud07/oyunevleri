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

$lockPath = BASE_PATH . '/storage/sms-kuyrugu.lock';
$lock = fopen($lockPath, 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "SMS kuyrugu zaten calisiyor.\n";
    exit(0);
}

$limit = max(1, (int) ($argv[1] ?? 50));
$sonuclar = [];
foreach (Kurum::aktifIdler() as $kurumId) {
    Session::set('kurum_id', $kurumId);
    try {
        $sonuclar[] = ['kurum_id' => $kurumId, 'sonuc' => (new SmsServisi())->kuyrukIsle($limit)];
    } catch (Throwable $e) {
        $sonuclar[] = ['kurum_id' => $kurumId, 'hata' => $e->getMessage()];
    }
}
echo json_encode($sonuclar, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
