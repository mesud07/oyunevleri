<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Services\BordroHesaplamaServisi;

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "[FAIL] {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "[OK] {$message}\n");
};
$yaklasik = static fn (float $actual, float $expected): bool => abs($actual - $expected) < 0.011;

$servis = new BordroHesaplamaServisi();
$personel = [[
    'id' => 1,
    'ad' => 'Asgari',
    'soyad' => 'Ücretli',
    'aylik_brut_ucret' => 33030,
    'sgk_tesvik_turu' => 'diger_2_puan',
    'ise_giris_tarihi' => '2026-01-01',
    'isten_cikis_tarihi' => null,
]];

$ocak = $servis->hesapla('2026-01', $personel, []);
$satir = $ocak['satirlar'][0] ?? [];
$assert(!empty($ocak['destekleniyor']), '2026 bordro oranları tanımlı');
$assert(($satir['sgk_gunu'] ?? 0) === 30, 'Tam ay için 30 SGK günü hesaplanıyor');
$assert($yaklasik((float) ($satir['net_odeme'] ?? 0), 28075.50), '2026 net asgari ücret resmî tutarla eşleşiyor');
$assert($yaklasik((float) ($satir['sgk_isveren'] ?? 0), 6523.43), 'İki puan indirimli işveren SGK payı doğru');
$assert($yaklasik((float) ($satir['isveren_maliyeti'] ?? 0), 40214.03), '2026 asgari ücret işveren maliyeti resmî tutarla eşleşiyor');

$eksikGunKayitlari = [
    1 => [
        '2026-01-05' => ['durum' => 'devamsiz'],
        '2026-01-06' => ['durum' => 'raporlu'],
        '2026-01-07' => ['durum' => 'izinli'],
    ],
];
$eksik = $servis->hesapla('2026-01', $personel, $eksikGunKayitlari);
$eksikSatir = $eksik['satirlar'][0] ?? [];
$assert(($eksikSatir['sgk_gunu'] ?? 0) === 28, 'Rapor ve devamsızlık düşülür, ücretli izin SGK gününde kalır');
$assert($yaklasik((float) ($eksikSatir['brut_hakedis'] ?? 0), 30828.00), 'Eksik gün brüt hakedişe 30 gün esasıyla yansır');

$tesviksizPersonel = $personel;
$tesviksizPersonel[0]['sgk_tesvik_turu'] = 'tesviksiz';
$tesviksiz = $servis->hesapla('2026-01', $tesviksizPersonel, []);
$assert(
    (float) ($tesviksiz['satirlar'][0]['isveren_maliyeti'] ?? 0) > (float) ($satir['isveren_maliyeti'] ?? 0),
    'Teşviksiz işveren maliyeti indirimli maliyetten yüksek'
);

$ustUcretli = $personel;
$ustUcretli[0]['aylik_brut_ucret'] = 50000;
$agustos = $servis->hesapla('2026-08', $ustUcretli, []);
$agustosSatir = $agustos['satirlar'][0] ?? [];
$assert((float) ($agustosSatir['gelir_vergisi'] ?? 0) > 0, 'Asgari ücret üzerindeki maaşta gelir vergisi hesaplanıyor');
$assert(
    (float) ($agustosSatir['kumulatif_vergi_matrahi'] ?? 0) > (float) ($agustosSatir['gelir_vergisi_matrahi'] ?? 0),
    'Gelir vergisi seçilen aya kadar kümülatif matrahla hesaplanıyor'
);

$desteklenmeyen = $servis->hesapla('2027-01', $personel, []);
$assert(empty($desteklenmeyen['destekleniyor']), 'Oranları tanımlanmamış yıl sessizce tahmin edilmiyor');

fwrite(STDOUT, "Bordro smoke testleri tamamlandı.\n");
