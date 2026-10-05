<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Bu dosya yalnizca CLI ile calisir.');
}

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Session;
use App\Core\Veritabani;
use App\Models\Kurum;

$stmt = Veritabani::baglan()->prepare(
    "UPDATE odeme_sozleri
     SET durum = CASE
       WHEN soz_verilen_tarih = CURDATE() THEN 'bugun_odenecek'
       WHEN soz_verilen_tarih < CURDATE() THEN 'gecikti'
       ELSE durum
     END
     WHERE kurum_id = :kurum_id
       AND durum IN ('bekleniyor', 'bugun_odenecek')"
);
$toplam = 0;
foreach (Kurum::aktifIdler() as $kurumId) {
    Session::set('kurum_id', $kurumId);
    $stmt->execute(['kurum_id' => $kurumId]);
    $toplam += $stmt->rowCount();
}

echo 'Guncellenen odeme sozu: ' . $toplam . PHP_EOL;
