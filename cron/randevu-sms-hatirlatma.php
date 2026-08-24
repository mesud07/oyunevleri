<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require dirname(__DIR__) . '/bootstrap.php';

use App\Services\SmsServisi;

$lockPath = BASE_PATH . '/storage/randevu-sms-hatirlatma.lock';
$lock = fopen($lockPath, 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "Randevu SMS hatirlatma zaten calisiyor.\n";
    exit(0);
}

$servis = new SmsServisi();
$adet = $servis->randevuHatirlatmalariOlustur();
$dogumGunuAdet = $servis->dogumGunuMesajlariOlustur();
$limit = max(1, (int) ($argv[1] ?? 100));
$sonuc = $servis->kuyrukIsle($limit);

echo "Kuyruga eklenen randevu hatirlatmasi: {$adet}\n";
echo "Kuyruga eklenen dogum gunu mesaji: {$dogumGunuAdet}\n";
echo 'SMS kuyruk sonucu: ' . json_encode($sonuc, JSON_UNESCAPED_UNICODE) . PHP_EOL;
