<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Services\TahsilatAnalizServisi;
use App\Services\TahsilatExcelServisi;

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "[FAIL] {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "[OK] {$message}\n");
};

$root = dirname(__DIR__);
$routes = file_get_contents($root . '/config/routes.php') ?: '';
$controller = file_get_contents($root . '/app/Controllers/OdemeController.php') ?: '';
$model = file_get_contents($root . '/app/Models/Odeme.php') ?: '';
$view = file_get_contents($root . '/resources/views/panel/tahsilatlar.php') ?: '';
$legacyView = file_get_contents($root . '/resources/views/panel/odemeler.php') ?: '';
$javascript = file_get_contents($root . '/public/assets/js/odemeler.js') ?: '';
$assert(str_contains($routes, "'/panel/odemeler/tahsilatlar.xlsx'"), 'Aylık tahsilat Excel indirme rotası tanımlı');
$assert(str_contains($controller, 'TahsilatExcelServisi') && str_contains($controller, 'tahsilatlarExcel'), 'Excel indirme işlemi denetleyiciye bağlı');
$assert(str_contains($model, 'public static function tarihAraligiTahsilatlari'), 'Seçilen tarih aralığının tahsilat detayları Excel için hazırlanıyor');
$assert(str_contains($view, 'Excel İndir') && str_contains($view, 'tahsilatlar.xlsx?ay='), 'Seçilen aya bağlı Excel indirme düğmesi ekranda');
$assert(str_contains($view, 'data-payment-month-filter') && str_contains($view, 'data-payment-filter-export'), 'Tahsilat listesinde ay filtresi ve filtrelenmiş Excel düğmesi bulunuyor');
$assert(str_contains($javascript, "params.set('yontem', methodFilter.value)") && str_contains($javascript, "ay: monthFilter?.value"), 'Liste ve Excel aynı ay ve ödeme yöntemi filtrelerini kullanıyor');
$assert(str_contains($model, 'od.tarih BETWEEN :ay_baslangic AND :ay_bitis'), 'Tahsilat listesi seçilen ayla sınırlandırılıyor');
$assert(str_contains($controller, "Odeme::tarihAraligiTahsilatlari(\$aralik['baslangic'], \$aralik['bitis'], \$yontem)"), 'Excel çıktısı seçilen ödeme yöntemiyle sınırlandırılıyor');
$assert(str_contains($model, 'ORDER BY ov2.birincil_mi DESC') && str_contains($javascript, '<th>Veli</th>'), 'Tahsilat listesi kayıtlı veya birincil veli adını gösteriyor');
$assert(str_contains($javascript, 'institutionLogoUrl') && str_contains($javascript, 'institutionName'), 'Tahsilat makbuzu kurum logosu ve adini kullaniyor');
$assert(!str_contains($javascript, '<div class="brand">Oyun Evleri'), 'Tahsilat makbuzunda platform markasi bulunmuyor');
$assert(str_contains($view, 'data-institution-logo') && str_contains($legacyView, 'data-institution-logo'), 'Kurum logosu tum tahsilat listelerinden makbuza aktariliyor');
$assert(!str_contains($javascript, '>Kasaya Aktar</button>'), 'Tahsilat listesindeki kasaya aktar butonu gizleniyor');
$assert(!str_contains($view, 'payment-tracking-grid') && !str_contains($view, 'Yaklasan Tahsilatlar') && !str_contains($view, 'Gecikmis Tahsilatlar'), 'Yaklasan ve gecikmis tahsilat bolumleri gizleniyor');

$servis = new TahsilatAnalizServisi();
$analiz = $servis->olustur('2026-08', [
    'yontemler' => [
        ['kategori' => 'nakit', 'adet' => 2, 'brut' => 2200, 'kdv' => 200, 'kdv_belirsiz_adet' => 0],
        ['kategori' => 'havale_eft', 'adet' => 1, 'brut' => 1100, 'kdv' => 100, 'kdv_belirsiz_adet' => 0],
        ['kategori' => 'kredi_karti', 'adet' => 1, 'brut' => 550, 'kdv' => 50, 'kdv_belirsiz_adet' => 0],
        ['kategori' => 'odeme_baglantisi', 'adet' => 1, 'brut' => 550, 'kdv' => 50, 'kdv_belirsiz_adet' => 0],
    ],
    'donem_gelirleri' => [
        ['donem' => 3, 'adet' => 4, 'brut' => 4400, 'kdv' => 400],
    ],
    'donem_giderleri' => [
        ['donem' => 3, 'gider' => 1000],
    ],
]);

$assert(($analiz['ay_etiketi'] ?? '') === 'Ağustos 2026', 'Seçilen ay Türkçe etiketleniyor');
$assert((float) ($analiz['toplam_tahsilat'] ?? 0) === 4400.0, 'Aylık tahsilat toplamı hesaplanıyor');
$assert((float) ($analiz['ortalama_tahsilat'] ?? 0) === 880.0, 'Tahsilat başına ortalama hesaplanıyor');
$assert((float) ($analiz['kdv'] ?? 0) === 200.0 && (float) ($analiz['kdv_haric_gelir'] ?? 0) === 4200.0, 'Varsayılan KDV hesabı nakit tahsilatı dışarıda bırakıyor');
$assert(!in_array('nakit', $analiz['kdv_yontemleri'] ?? [], true), 'Nakit varsayılan olarak KDV hesabında pasif');
$assert(in_array('kredi_karti', $analiz['kdv_yontemleri'] ?? [], true) && in_array('havale_eft', $analiz['kdv_yontemleri'] ?? [], true), 'Kart ve havale varsayılan olarak KDV hesabında aktif');

$yalnizBankaKdv = $servis->olustur('2026-08', [
    'yontemler' => [
        ['kategori' => 'nakit', 'adet' => 2, 'brut' => 2200, 'kdv' => 200, 'kdv_belirsiz_adet' => 0],
        ['kategori' => 'havale_eft', 'adet' => 1, 'brut' => 1100, 'kdv' => 100, 'kdv_belirsiz_adet' => 0],
        ['kategori' => 'kredi_karti', 'adet' => 1, 'brut' => 550, 'kdv' => 50, 'kdv_belirsiz_adet' => 0],
        ['kategori' => 'odeme_baglantisi', 'adet' => 1, 'brut' => 550, 'kdv' => 50, 'kdv_belirsiz_adet' => 0],
    ],
], ['havale_eft', 'kredi_karti']);
$assert((float) ($yalnizBankaKdv['kdv'] ?? 0) === 150.0, 'Checkbox seçimi yalnız havale ve kart KDV tutarını hesaba katıyor');

$yontemler = array_column($analiz['yontemler'] ?? [], null, 'kod');
$assert((float) ($yontemler['nakit']['yuzde'] ?? 0) === 50.0, 'Nakit dağılım yüzdesi hesaplanıyor');
$assert((float) ($yontemler['havale_eft']['yuzde'] ?? 0) === 25.0, 'Havale dağılım yüzdesi hesaplanıyor');
$assert((float) ($yontemler['kredi_karti']['yuzde'] ?? 0) === 12.5, 'Kredi kartı ayrı hesaplanıyor');
$assert((float) ($yontemler['odeme_baglantisi']['yuzde'] ?? 0) === 12.5, 'Ödeme bağlantısı ayrı hesaplanıyor');

$ucAy = TahsilatAnalizServisi::donemAraligi('2026-09', '3ay');
$altiAy = TahsilatAnalizServisi::donemAraligi('2026-09', '6ay');
$buYil = TahsilatAnalizServisi::donemAraligi('2026-09', 'bu_yil');
$birYil = TahsilatAnalizServisi::donemAraligi('2026-09', '1yil');
$assert($ucAy['baslangic'] === '2026-07-01' && $ucAy['bitis'] === '2026-09-30', 'Üç aylık analiz aralığı doğru hesaplanıyor');
$assert($altiAy['baslangic'] === '2026-04-01' && $altiAy['bitis'] === '2026-09-30', 'Altı aylık analiz aralığı doğru hesaplanıyor');
$assert($buYil['baslangic'] === '2026-01-01' && $buYil['bitis'] === '2026-09-30', 'Bu sene analiz aralığı doğru hesaplanıyor');
$assert($birYil['baslangic'] === '2025-10-01' && $birYil['bitis'] === '2026-09-30', 'Bir yıllık analiz aralığı doğru hesaplanıyor');

$excel = (new TahsilatExcelServisi())->olustur($analiz, [[
    'tarih' => '2026-08-15',
    'ogrenci' => 'Ada Deveci',
    'veli' => 'Ayşe Deveci',
    'paket_adi' => 'Oyun Grubu',
    'yontem' => 'kredi_karti',
    'tutar' => 550,
    'makbuz_numarasi' => 'M-100',
    'kasa' => 'Ana Kasa',
    'aciklama' => 'Ağustos tahsilatı',
]], 'Talya Kids');
$assert(str_starts_with((string) ($excel['content'] ?? ''), 'PK'), 'Tahsilat raporu geçerli bir XLSX paketi olarak üretiliyor');
$assert(($excel['name'] ?? '') === 'tahsilatlar-202608.xlsx', 'Excel dosya adı seçilen ayı içeriyor');

$geciciExcel = tempnam(sys_get_temp_dir(), 'tahsilat-test-');
$assert($geciciExcel !== false, 'Excel doğrulaması için geçici dosya oluşturuluyor');
file_put_contents($geciciExcel, $excel['content']);
$zip = new ZipArchive();
$assert($zip->open($geciciExcel) === true, 'Üretilen Excel dosyası açılabiliyor');
$ozetXml = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
$detayXml = (string) $zip->getFromName('xl/worksheets/sheet2.xml');
$stilXml = (string) $zip->getFromName('xl/styles.xml');
$zip->close();
@unlink($geciciExcel);
$assert(simplexml_load_string($ozetXml) !== false && simplexml_load_string($detayXml) !== false && simplexml_load_string($stilXml) !== false, 'Excel çalışma sayfaları ve stilleri geçerli XML içeriyor');
$assert(str_contains($ozetXml, 'Kredi Kartı') && str_contains($ozetXml, 'Ödeme Bağlantısı'), 'Excel özetinde ödeme türleri ayrı gösteriliyor');
$assert(str_contains($detayXml, 'Ada Deveci') && str_contains($detayXml, 'Ağustos tahsilatı'), 'Excel detay sekmesi aylık tahsilat kayıtlarını içeriyor');
$assert(str_contains($view, 'data-vat-method-form') && str_contains($view, 'KDV Ayarını Kaydet'), 'Tahsilat ekranında KDV ödeme yöntemi checkbox ayarı bulunuyor');
$assert(str_contains($view, '>3 Aylık<') && str_contains($view, '>6 Aylık<') && str_contains($view, '>Bu Sene<') && str_contains($view, '>1 Yıl<'), 'Analiz ekranında geniş dönem seçenekleri bulunuyor');
$assert(str_contains($view, '&amp;donem=') && str_contains($controller, 'tarihAraligiTahsilatlari'), 'Excel raporu seçili analiz aralığını kullanıyor');
$assert(!str_contains($view, 'Üç Aylık Gelir Vergisi Tahmini') && !str_contains($view, 'collection-quarter-tax'), 'Üç aylık gelir vergisi tahmini tahsilat ekranından kaldırıldı');
$assert(str_contains($controller, 'tahsilat_kdv_yontemleri') && str_contains($controller, 'kdvAyarlariKaydet'), 'KDV ödeme yöntemi ayarı kurum bazında saklanıyor');
$assert(str_contains($routes . file_get_contents($root . '/public/ajax.php'), 'odeme_kdv_ayarlari_kaydet'), 'KDV ayarı kayıt işlemi API bağlantısına sahip');

fwrite(STDOUT, "Tahsilat analizi smoke testleri tamamlandı.\n");
