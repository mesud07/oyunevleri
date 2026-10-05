<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Session;
use App\Core\Veritabani;
use App\Models\IslemKaydi;
use App\Models\OdemeSozu;
use App\Models\SmsKaydi;

$assert = static function (bool $kosul, string $mesaj): void {
    if (!$kosul) {
        fwrite(STDERR, "[FAIL] {$mesaj}\n");
        exit(1);
    }
    fwrite(STDOUT, "[OK] {$mesaj}\n");
};

$db = Veritabani::baglan();
$eskiKurumId = (int) Session::get('kurum_id', 0);

$kurumSayisi = (int) $db->query('SELECT COUNT(*) FROM kurumlar WHERE aktif = 1')->fetchColumn();
$assert($kurumSayisi >= 2, 'İzolasyon testi için en az iki aktif kurum var');

$kurumTablolari = $db->query(
    'SELECT TABLE_NAME
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = "kurum_id"
     ORDER BY TABLE_NAME'
)->fetchAll(PDO::FETCH_COLUMN);
$assert(count($kurumTablolari) === 59, 'Kurum bilgisi taşıyan 59 tablo tespit edildi');

$varsayilanSayisi = (int) $db->query(
    'SELECT COUNT(*)
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND COLUMN_NAME = "kurum_id"
       AND COLUMN_DEFAULT IS NOT NULL'
)->fetchColumn();
$assert($varsayilanSayisi === 0, 'Hiçbir kurum_id kolonu sessizce kurum 1 varsaymıyor');

$fkTabloSayisi = (int) $db->query(
    'SELECT COUNT(DISTINCT TABLE_NAME)
     FROM information_schema.KEY_COLUMN_USAGE
     WHERE TABLE_SCHEMA = DATABASE()
       AND COLUMN_NAME = "kurum_id"
       AND REFERENCED_TABLE_NAME = "kurumlar"'
)->fetchColumn();
$assert($fkTabloSayisi === count($kurumTablolari), 'Kurumlu tabloların tamamı kurumlar tablosuna bağlı');

foreach ($kurumTablolari as $tablo) {
    $guvenliTablo = str_replace('`', '``', (string) $tablo);
    $yetim = (int) $db->query(
        "SELECT COUNT(*) FROM `{$guvenliTablo}` t LEFT JOIN kurumlar k ON k.id = t.kurum_id WHERE k.id IS NULL"
    )->fetchColumn();
    $assert($yetim === 0, "{$tablo} tablosunda kurumsuz kayıt yok");
}

$smsIndeksi = $db->query(
    'SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS kolonlar, MIN(NON_UNIQUE) AS tekil_degil
     FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "sms_kayitlari"
     GROUP BY INDEX_NAME
     HAVING kolonlar = "kurum_id,mukerrer_anahtari" AND tekil_degil = 0'
)->fetch();
$assert((bool) $smsIndeksi, 'SMS mükerrerlik anahtarı kurum içinde benzersiz');

$kurumlar = $db->query('SELECT id FROM kurumlar WHERE aktif = 1 ORDER BY id DESC LIMIT 2')->fetchAll(PDO::FETCH_COLUMN);
$kurumA = (int) $kurumlar[0];
$kurumB = (int) $kurumlar[1];
$anahtar = 'kurum-smoke-' . bin2hex(random_bytes(8));

$db->beginTransaction();
try {
    Session::set('kurum_id', $kurumA);
    $smsA = SmsKaydi::olustur([
        'telefon' => '905000000001',
        'telefon_orijinal' => '05000000001',
        'mesaj' => 'Kurum izolasyonu testi',
        'mukerrer_anahtari' => $anahtar,
        'durum' => 'bekliyor',
    ]);

    Session::set('kurum_id', $kurumB);
    $smsB = SmsKaydi::olustur([
        'telefon' => '905000000002',
        'telefon_orijinal' => '05000000002',
        'mesaj' => 'Kurum izolasyonu testi',
        'mukerrer_anahtari' => $anahtar,
        'durum' => 'bekliyor',
    ]);
    $assert($smsA !== $smsB, 'Aynı SMS anahtarı iki kurumda bağımsız kayıt oluşturuyor');

    $stmt = $db->prepare(
        'SELECT sk.kurum_id, so.kurum_id AS olay_kurum_id
         FROM sms_kayitlari sk
         INNER JOIN sms_olay_kayitlari so ON so.sms_kaydi_id = sk.id
         WHERE sk.id IN (:sms_a, :sms_b)
         ORDER BY sk.id'
    );
    $stmt->execute(['sms_a' => $smsA, 'sms_b' => $smsB]);
    $smsSatirlari = $stmt->fetchAll();
    $assert(
        count($smsSatirlari) === 2
        && (int) $smsSatirlari[0]['kurum_id'] === (int) $smsSatirlari[0]['olay_kurum_id']
        && (int) $smsSatirlari[1]['kurum_id'] === (int) $smsSatirlari[1]['olay_kurum_id'],
        'SMS olay kayıtları ana SMS kaydıyla aynı kuruma yazılıyor'
    );

    Session::set('kurum_id', $kurumA);
    IslemKaydi::ekle(null, $anahtar, 'Kurum izolasyonu testi', ['kurum_id' => $kurumA]);
    $stmt = $db->prepare('SELECT kurum_id FROM islem_kayitlari WHERE islem = :islem ORDER BY id DESC LIMIT 1');
    $stmt->execute(['islem' => $anahtar]);
    $assert((int) $stmt->fetchColumn() === $kurumA, 'İşlem kaydı doğru kuruma yazılıyor');

    foreach ([$kurumA, $kurumB] as $kurumId) {
        Session::set('kurum_id', $kurumId);
        $sozler = OdemeSozu::liste();
        $yalnizBuKurum = true;
        foreach ($sozler as $satir) {
            $yalnizBuKurum = $yalnizBuKurum && (int) $satir['kurum_id'] === $kurumId;
        }
        $assert($yalnizBuKurum, "Ödeme sözü listesi yalnız kurum {$kurumId} kayıtlarını döndürüyor");
    }
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}

Session::remove('kurum_id');
$baglamReddedildi = false;
try {
    OdemeSozu::liste();
} catch (RuntimeException) {
    $baglamReddedildi = true;
}
$assert($baglamReddedildi, 'Kurum bağlamı olmadan kurumsal veri sorgulanamıyor');

if ($eskiKurumId > 0) {
    Session::set('kurum_id', $eskiKurumId);
} else {
    Session::remove('kurum_id');
}

$schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
$assert(
    is_string($schema)
    && str_contains($schema, 'CREATE TABLE IF NOT EXISTS `kurumlar`')
    && !preg_match('/`kurum_id`[^,\n]*DEFAULT\s+[\'\"]?1/iu', $schema),
    'Temiz kurulum şeması kurum yapısını içeriyor ve kurum 1 varsaymıyor'
);

fwrite(STDOUT, "Kurum izolasyonu smoke testleri tamamlandı.\n");
