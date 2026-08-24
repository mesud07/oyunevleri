<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Bu dosya yalnizca CLI ile calisir.');
}

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Veritabani;

$db = Veritabani::baglan();
$bekleme = (int) ($db->query("SELECT deger FROM ayarlar WHERE anahtar = 'otomatik_gelmedi_bekleme_dakika'")->fetchColumn() ?: 60);

$stmt = $db->prepare(
    "UPDATE randevular
     SET durum = 'gelmedi', otomatik_gelmedi_islendi = 1
     WHERE durum = 'planlandi'
       AND otomatik_gelmedi_islendi = 0
       AND TIMESTAMP(tarih, bitis_saati) < (NOW() - INTERVAL :bekleme MINUTE)"
);
$stmt->bindValue('bekleme', $bekleme, \PDO::PARAM_INT);
$stmt->execute();

echo 'Otomatik gelmedi islenen randevu: ' . $stmt->rowCount() . PHP_EOL;
