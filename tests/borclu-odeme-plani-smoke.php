<?php

declare(strict_types=1);

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "[FAIL] {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "[OK] {$message}\n");
};

$root = dirname(__DIR__);
$migration = file_get_contents($root . '/database/migrations/20260924_paket_beklenen_odeme_tarihi.sql') ?: '';
$packageModel = file_get_contents($root . '/app/Models/Paket.php') ?: '';
$paymentModel = file_get_contents($root . '/app/Models/Odeme.php') ?: '';
$controller = file_get_contents($root . '/app/Controllers/PaketController.php') ?: '';
$paymentController = file_get_contents($root . '/app/Controllers/OdemeController.php') ?: '';
$view = file_get_contents($root . '/resources/views/panel/odeme-borclular.php') ?: '';
$js = file_get_contents($root . '/public/assets/js/panel.js') ?: '';
$ajax = file_get_contents($root . '/public/ajax.php') ?: '';

$assert(str_contains($migration, 'beklenen_odeme_tarihi DATE NULL'), 'Beklenen ödeme tarihi veritabanında tarih olarak saklanıyor');
$assert(str_contains($packageModel, 'public static function odemePlaniGuncelle'), 'Tahsilat notu ve beklenen tarih birlikte güncellenebiliyor');
$assert(str_contains($paymentModel, 'p.beklenen_odeme_tarihi'), 'Borçlular sorgusu beklenen ödeme tarihini getiriyor');
$assert(str_contains($paymentModel, 'p.tahsilat_notu'), 'Borçlular sorgusu tahsilat notunu getiriyor');
$assert(str_contains($paymentModel, 'p.baslangic_tarihi') && str_contains($paymentModel, 'p.tahmini_son_ders_tarihi'), 'Borçlular sorgusu paket başlangıç ve bitiş tarihlerini getiriyor');
$assert(str_contains($controller, "createFromFormat('!Y-m-d'"), 'Geçersiz beklenen ödeme tarihi reddediliyor');
$assert(str_contains($ajax, "'paket_odeme_plani_guncelle'"), 'Ödeme planı kayıt uç noktası tanımlı');
$assert(str_contains($view, 'data-debt-payment-plan'), 'Borçlu satırından ödeme planı açılabiliyor');
$assert(str_contains($view, 'data-expected-payment-date'), 'Mevcut beklenen tarih düzenleme penceresine taşınıyor');
$assert(str_contains($view, 'Tahsilat Notu'), 'Tahsilat notu borçlular tablosunda gösteriliyor');
$assert(str_contains($view, '<th>Başlangıç</th>') && str_contains($view, '<th>Bitiş</th>'), 'Paket tarihleri borçlular tablosunda paket sütununun yanında gösteriliyor');
$assert(str_contains($view, 'Notu Düzenle'), 'Tahsilat notu borçlular tablosundan doğrudan düzenlenebiliyor');
$assert(str_contains($js, 'data-debt-payment-plan-dialog'), 'Ödeme planı penceresi arayüz koduna bağlı');
$assert(str_contains($paymentController, "'beklenenOdemeTakvimi'"), 'Gelecek tarihli borçlar takvim verisine hazırlanıyor');
$assert(str_contains($view, 'data-debt-payment-calendar'), 'Beklenen tahsilat takvimi borçlular sayfasında gösteriliyor');
$assert(str_contains($js, 'data-debt-calendar-date'), 'Takvim günleri tıklanarak ödeme ayrıntıları açılabiliyor');
$assert(str_contains($js, 'cursor = new Date(selected.getFullYear(), selected.getMonth(), 1)'), 'Komşu aya ait güne tıklanınca takvim doğru aya geçiyor');

fwrite(STDOUT, "Borçlu ödeme planı smoke testleri tamamlandı.\n");
