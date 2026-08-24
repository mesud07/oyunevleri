<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Bu dosya yalnizca CLI ile calisir.');
}

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Veritabani;

$stmt = Veritabani::baglan()->prepare(
    "UPDATE odeme_sozleri
     SET durum = CASE
       WHEN soz_verilen_tarih = CURDATE() THEN 'bugun_odenecek'
       WHEN soz_verilen_tarih < CURDATE() THEN 'gecikti'
       ELSE durum
     END
     WHERE durum IN ('bekleniyor', 'bugun_odenecek')"
);
$stmt->execute();

echo 'Guncellenen odeme sozu: ' . $stmt->rowCount() . PHP_EOL;
