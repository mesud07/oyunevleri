<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Veritabani;

$db = Veritabani::baglan();
$migration = file_get_contents(BASE_PATH . '/database/migrations/20261005_randevu_katilim_tokenlari_suresiz.sql');
if (!is_string($migration) || trim($migration) === '') {
    throw new RuntimeException('Randevu katilim token migrasyonu okunamadi.');
}
$db->exec($migration);

$kayitlar = $db->query(
    'SELECT sk.kurum_id, sk.randevu_id, sk.mesaj
     FROM sms_kayitlari sk
     INNER JOIN randevular r
             ON r.id = sk.randevu_id AND r.kurum_id = sk.kurum_id
     WHERE sk.randevu_id IS NOT NULL
       AND sk.mesaj LIKE "%/randevu-katilim?t=%"'
)->fetchAll();

$ekle = $db->prepare(
    'INSERT IGNORE INTO randevu_katilim_tokenlari
     (kurum_id, randevu_id, token_hash, olusturulma_tarihi)
     VALUES (:kurum_id, :randevu_id, :token_hash, NOW())'
);
$eklenen = 0;
foreach ($kayitlar as $kayit) {
    if (!preg_match_all('~/randevu-katilim\?t=([a-f0-9]{48,80})~i', (string) $kayit['mesaj'], $eslesmeler)) {
        continue;
    }
    foreach (array_unique(array_map('strtolower', $eslesmeler[1])) as $token) {
        $ekle->execute([
            'kurum_id' => (int) $kayit['kurum_id'],
            'randevu_id' => (int) $kayit['randevu_id'],
            'token_hash' => hash('sha256', $token),
        ]);
        $eklenen += $ekle->rowCount();
    }
}

echo json_encode([
    'basarili' => true,
    'incelenen_sms' => count($kayitlar),
    'kurtarilan_token' => $eklenen,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
