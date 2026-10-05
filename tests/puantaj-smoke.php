<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Services\BordroHesaplamaServisi;
use App\Services\PuantajExcelServisi;

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "[FAIL] {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "[OK] {$message}\n");
};

$aylik = [
    'ay' => '2026-08',
    'personeller' => [
        ['id' => 1, 'tc_kimlik_no' => '12345678901', 'ad' => 'Ayşe', 'soyad' => 'Yılmaz', 'aylik_brut_ucret' => 33030, 'sgk_tesvik_turu' => 'diger_2_puan'],
        ['id' => 2, 'tc_kimlik_no' => '10987654321', 'ad' => 'Mehmet', 'soyad' => 'Kaya', 'aylik_brut_ucret' => 50000, 'sgk_tesvik_turu' => 'tesviksiz'],
    ],
    'kayitlar' => [
        1 => [
            '2026-08-03' => ['durum' => 'calisti', 'giris_saati' => '09:02:00', 'cikis_saati' => '18:04:00', 'mola_dakika' => 60, 'aciklama' => 'Normal gün'],
            '2026-08-04' => ['durum' => 'izinli', 'giris_saati' => null, 'cikis_saati' => null, 'mola_dakika' => 0, 'aciklama' => 'Yıllık izin'],
        ],
        2 => [
            '2026-08-03' => ['durum' => 'raporlu', 'giris_saati' => null, 'cikis_saati' => null, 'mola_dakika' => 0, 'aciklama' => 'Raporlu'],
        ],
    ],
];
$aylik['bordro'] = (new BordroHesaplamaServisi())->hesapla('2026-08', $aylik['personeller'], $aylik['kayitlar']);

$rapor = (new PuantajExcelServisi())->olustur($aylik, 'Talya Kids Test');
$assert(($rapor['name'] ?? '') === 'personel-puantaj-202608.xlsx', 'Excel dosya adı ay bilgisini içeriyor');
$assert(str_starts_with((string) ($rapor['content'] ?? ''), 'PK'), 'XLSX geçerli ZIP imzasıyla oluşturuluyor');

$gecici = tempnam(sys_get_temp_dir(), 'puantaj-test-');
$assert($gecici !== false, 'Geçici doğrulama dosyası oluşturuluyor');
file_put_contents($gecici, $rapor['content']);
$zip = new ZipArchive();
$assert($zip->open($gecici) === true, 'XLSX paketi tekrar açılabiliyor');

$beklenenler = ['[Content_Types].xml', 'xl/workbook.xml', 'xl/styles.xml', 'xl/worksheets/sheet1.xml', 'xl/worksheets/sheet2.xml', 'xl/worksheets/sheet3.xml'];
foreach ($beklenenler as $dosya) {
    $assert($zip->locateName($dosya) !== false, "XLSX bileşeni mevcut: {$dosya}");
}

$workbook = (string) $zip->getFromName('xl/workbook.xml');
$puantaj = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
$hareket = (string) $zip->getFromName('xl/worksheets/sheet2.xml');
$bordro = (string) $zip->getFromName('xl/worksheets/sheet3.xml');
$assert(str_contains($workbook, 'name="Puantaj"') && str_contains($workbook, 'name="Giriş Çıkış"') && str_contains($workbook, 'name="Maaş ve SGK"'), 'Üç rapor sekmesi tanımlı');
$assert(str_contains($puantaj, 'COUNTIF(') && str_contains($puantaj, 'COUNTA('), 'Aylık özet formülleri üretildi');
$assert(str_contains($puantaj, 'Ayşe') && str_contains($puantaj, '>X<') && str_contains($puantaj, '>İ<'), 'Personel ve günlük kodlar aylık tabloda yer alıyor');
$assert(str_contains($hareket, 'Yıllık izin') && str_contains($hareket, '09:02'), 'Giriş-çıkış ayrıntıları ikinci sekmede yer alıyor');
$assert(str_contains($bordro, 'Net Ödeme') && str_contains($bordro, 'Ayşe Yılmaz'), 'Maaş ve SGK tahmini üçüncü sekmede yer alıyor');

foreach ([$workbook, $puantaj, $hareket, $bordro, (string) $zip->getFromName('xl/styles.xml')] as $xml) {
    $belge = new DOMDocument();
    $assert(@$belge->loadXML($xml), 'OpenXML bileşeni geçerli XML');
}

$zip->close();
@unlink($gecici);

$gorunum = file_get_contents(dirname(__DIR__) . '/resources/views/panel/personel-puantaj.php') ?: '';
$javascript = file_get_contents(dirname(__DIR__) . '/public/assets/js/personel-puantaj.js') ?: '';
$controller = file_get_contents(dirname(__DIR__) . '/app/Controllers/PersonelController.php') ?: '';
$ajax = file_get_contents(dirname(__DIR__) . '/public/ajax.php') ?: '';
$assert(str_contains($gorunum, 'data-puantaj-cell') && str_contains($gorunum, 'data-puantaj-toplu-durum'), 'Puantaj tablosunda çoklu seçim ve durum araçları mevcut');
$assert(str_contains($javascript, "addEventListener('pointerover'") && str_contains($javascript, 'dikdortgenSec'), 'Fareyle sürükleyerek dikdörtgen hücre seçimi tanımlı');
$assert(str_contains($gorunum, 'data-puantaj-toplu-dialog') && str_contains($javascript, 'window.requestAnimationFrame(topluPuantajDialoginiAc)'), 'Fare bırakıldığında çoklu düzenleme penceresi otomatik açılıyor');
$assert(str_contains($javascript, "talyaAjax('personel_puantaj_toplu_kaydet'"), 'Seçilen hücreler toplu kayıt API’sine gönderiliyor');
$assert(str_contains($controller, 'function puantajTopluKaydet') && str_contains($ajax, "'personel_puantaj_toplu_kaydet'"), 'Toplu puantaj API rotası ve denetleyicisi tanımlı');
$assert(str_contains($gorunum, 'Maaş ve SGK Tahmini') && str_contains($gorunum, 'name="aylik_brut_ucret"'), 'Brüt maaş ve SGK özeti personel puantaj ekranına bağlı');
$assert(str_contains($controller, 'BordroHesaplamaServisi') && str_contains($controller, "'sgk_tesvik_turu'"), 'Bordro servisi ve SGK teşvik seçimi denetleyiciye bağlı');
fwrite(STDOUT, "Puantaj Excel smoke testleri tamamlandı.\n");
