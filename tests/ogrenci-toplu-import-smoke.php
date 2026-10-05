<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\OgrenciTopluImportServisi;

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "[FAIL] {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "[OK] {$message}\n");
};

$root = dirname(__DIR__);
$template = $root . '/public/assets/templates/ogrenci-toplu-import-sablonu.xlsx';
$assert(is_file($template) && filesize($template) > 1000, 'İndirilebilir Excel şablonu mevcut');

$temp = tempnam(sys_get_temp_dir(), 'ogrenci-import-test-');
$assert($temp !== false && copy($template, $temp), 'Test Excel dosyası hazırlandı');

$zip = new ZipArchive();
$assert($zip->open($temp) === true, 'Excel paketi açılabiliyor');
$sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
$assert(is_string($sheet) && str_contains($sheet, '</x:sheetData>'), 'Öğrenciler sayfası okunabiliyor');

$cell = static fn(string $ref, string $value): string => '<x:c r="' . $ref . '" t="inlineStr"><x:is><x:t>'
    . htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</x:t></x:is></x:c>';
$row = '<x:row r="2">'
    . $cell('A2', 'Ada')
    . $cell('B2', 'Yılmaz')
    . $cell('C2', '12345678901')
    . $cell('D2', '15.09.2021')
    . $cell('E2', 'Kız')
    . $cell('F2', '01.10.2026')
    . $cell('G2', 'Ayşe')
    . $cell('H2', 'Yılmaz')
    . $cell('I2', '0532 123 45 67')
    . $cell('K2', 'ayse@example.com')
    . '</x:row>';
$sheet = str_replace('</x:sheetData>', $row . '</x:sheetData>', $sheet);
$assert($zip->addFromString('xl/worksheets/sheet1.xml', $sheet), 'Test öğrencisi Excel dosyasına eklendi');
$zip->close();

$result = (new OgrenciTopluImportServisi())->oku($temp);
@unlink($temp);

$assert(count($result['kayitlar']) === 1, 'Geçerli öğrenci satırı ayrıştırılıyor');
$assert($result['hatalar'] === [], 'Geçerli öğrenci satırında doğrulama hatası yok');
$record = $result['kayitlar'][0];
$assert($record['ogrenci_dogum_tarihi'] === '2021-09-15', 'Doğum tarihi standart biçime çevriliyor');
$assert($record['ogrenci_cinsiyet'] === 'kiz', 'Cinsiyet sistem değerine çevriliyor');
$assert($record['veli_telefon'] === '0(532) 123 45 67', 'Veli telefonu standart biçime çevriliyor');

$routes = file_get_contents($root . '/config/routes.php') ?: '';
$view = file_get_contents($root . '/resources/views/panel/ogrenciler.php') ?: '';
$js = file_get_contents($root . '/public/assets/js/ogrenciler.js') ?: '';
$assert(str_contains($routes, '/panel/ogrenciler/import-sablonu.xlsx') && str_contains($routes, '/panel/ogrenciler/import'), 'İndirme ve import rotaları tanımlı');
$assert(str_contains($view, 'data-student-import-form'), 'Öğrenciler sayfasında toplu aktarım formu var');
$assert(str_contains($js, "new FormData(form)"), 'Excel dosyası multipart istekle gönderiliyor');

fwrite(STDOUT, "Öğrenci toplu import smoke testleri tamamlandı.\n");
