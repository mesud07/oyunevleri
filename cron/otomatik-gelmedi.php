<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Bu dosya yalnizca CLI ile calisir.');
}

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Session;
use App\Core\Veritabani;
use App\Models\Ayar;
use App\Models\Kurum;

$db = Veritabani::baglan();
$stmt = $db->prepare(
    "UPDATE randevular
     SET durum = 'gelmedi', otomatik_gelmedi_islendi = 1
     WHERE kurum_id = :kurum_id
       AND durum = 'planlandi'
       AND otomatik_gelmedi_islendi = 0
       AND TIMESTAMP(tarih, bitis_saati) < (NOW() - INTERVAL :bekleme MINUTE)"
);
$toplam = 0;
foreach (Kurum::aktifIdler() as $kurumId) {
    Session::set('kurum_id', $kurumId);
    $bekleme = max(1, (int) Ayar::deger('otomatik_gelmedi_bekleme_dakika', '60'));
    $stmt->bindValue('kurum_id', $kurumId, \PDO::PARAM_INT);
    $stmt->bindValue('bekleme', $bekleme, \PDO::PARAM_INT);
    $stmt->execute();
    $toplam += $stmt->rowCount();
}

echo 'Otomatik gelmedi islenen randevu: ' . $toplam . PHP_EOL;
