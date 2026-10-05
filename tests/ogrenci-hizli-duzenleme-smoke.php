<?php

declare(strict_types=1);

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "[FAIL] {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "[OK] {$message}\n");
};

$model = file_get_contents(dirname(__DIR__) . '/app/Models/Ogrenci.php') ?: '';
$controller = file_get_contents(dirname(__DIR__) . '/app/Controllers/OgrenciController.php') ?: '';
$ajax = file_get_contents(dirname(__DIR__) . '/public/ajax.php') ?: '';
$view = file_get_contents(dirname(__DIR__) . '/resources/views/panel/ogrenciler.php') ?: '';
$js = file_get_contents(dirname(__DIR__) . '/public/assets/js/panel.js') ?: '';

$assert(str_contains($view, 'data-student-quick-edit-form'), 'Ogrenci listesinde hizli duzenleme penceresi var');
$assert(str_contains($view, "yetki_var('ogrenci_ekle')"), 'Hizli duzenleme arayuzu ogrenci duzenleme yetkisine bagli');
$assert(str_contains($js, 'data-quick-edit-student'), 'Her ogrenci satirinda hizli duzenleme islemi uretiliyor');
$assert(str_contains($js, 'birincil_veli_telefon'), 'Birincil veli bilgileri hizli forma aktariliyor');
$assert(str_contains($view, 'name="ogrenci_adres"'), 'Ogrenci adresi hizli duzenleme formunda yer aliyor');
$assert(str_contains($view, '<select name="ogrenci_ilce">'), 'Ogrenci ilcesi hizli duzenleme formunda dropdown olarak sunuluyor');
$assert(str_contains($js, "ogrenci_adres: row.adres || ''"), 'Mevcut ogrenci adresi hizli forma aktariliyor');
$assert(str_contains($ajax, "'ogrenci_hizli_guncelle'"), 'Hizli guncelleme API islemi kayitli');
$assert(str_contains($controller, 'public function hizliGuncelle'), 'Hizli guncelleme denetleyicisi tanimli');
$assert(str_contains($model, 'public static function hizliGuncelle'), 'Kismi ogrenci guncelleme modeli tanimli');
$assert(str_contains($model, 'adres_konum_dogrulandi = :adres_konum_dogrulandi'), 'Adres degisince eski harita dogrulamasi guvenle yonetiliyor');
$assert(!str_contains(substr($model, strpos($model, 'public static function hizliGuncelle') ?: 0, 2500), 'saglik_bilgisi'), 'Hizli guncelleme gorunmeyen profil alanlarini degistirmiyor');

fwrite(STDOUT, "Ogrenci hizli duzenleme smoke testleri tamamlandi.\n");
