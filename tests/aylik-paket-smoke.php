<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Models\Paket;

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "[FAIL] {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "[OK] {$message}\n");
};

$assert(Paket::aylikTakvimDersSayisi('2026-09-01', [3, 5]) === 9, 'Eylül 2026 içindeki tüm Çarşamba ve Cuma günleri 9 ders sayılıyor');
$assert(Paket::aylikTakvimDersSayisi('2026-02-15', [3, 5]) === 8, 'Şubat 2026 içindeki tüm Çarşamba ve Cuma günleri 8 ders sayılıyor');
$assert(Paket::aylikTakvimDersSayisi('2026-09-27', [3]) === 5, 'Başlangıç günü ayın ortasında olsa da takvim ayının tamamı hesaplanıyor');
$assert(Paket::telafiHakSayisiniBelirle(['toplam_telafi_hak' => 0], 2) === 0, 'Açıkça tanımlanan sıfır telafi hakkı korunuyor');
$assert(Paket::telafiHakSayisiniBelirle([], 1) === 1, 'Telafi değeri gönderilmezse haftalık tek ders varsayılanı uygulanıyor');
$assert(Paket::telafiHakSayisiniBelirle([], 2) === 2, 'Telafi değeri gönderilmezse haftalık iki ders varsayılanı uygulanıyor');

$root = dirname(__DIR__);
$controller = file_get_contents($root . '/app/Controllers/PaketController.php') ?: '';
$view = file_get_contents($root . '/resources/views/panel/paketler.php') ?: '';
$js = file_get_contents($root . '/public/assets/js/panel.js') ?: '';
$migration = file_get_contents($root . '/database/migrations/20260927_hizmet_aylik_takvim.sql') ?: '';
$assert(str_contains($controller, "=== 'aylik_takvim'") && str_contains($controller, 'aylikTakvimDersSayisi'), 'Aylık paket hakkı sunucuda seçilen ay ve günlerden hesaplanıyor');
$assert(str_contains($view, 'data-calendar-mode') && str_contains($view, 'data-monthly-calendar-note'), 'Paket tanımlama ekranı aylık takvim davranışını açıklıyor');
$paketListesi = file_get_contents($root . '/resources/views/panel/paket-listesi.php') ?: '';
$panelJs = file_get_contents($root . '/public/assets/js/panel.js') ?: '';
$assert(str_contains($paketListesi, 'data-service-definition-form') && str_contains($paketListesi, 'data-service-fixed-right-field'), 'Aylık takvim seçiminde sabit hak alanları koşullu gösteriliyor');
$assert(str_contains($panelJs, 'updateServiceRightsVisibility') && str_contains($panelJs, "=== 'aylik_takvim'"), 'Aylık takvim seçildiğinde haftalık katılım ve telafi alanları gizleniyor');
$assert(str_contains($paketListesi, 'data-table="hizmet_listele"') && str_contains($paketListesi, 'data-can-manage-packages'), 'Paket tanımları düzenleme ve silme destekli tabloyla yükleniyor');
$assert(str_contains($panelJs, 'data-edit-service') && str_contains($panelJs, 'data-delete-service'), 'Paket tablosunda düzenleme ve silme işlemleri bulunuyor');
$assert(str_contains($js, 'updateMonthlyPackageRights'), 'Normal hak sayısı ekranda ay ve gün seçimine göre güncelleniyor');
$assert(str_contains($migration, 'hak_hesaplama_turu') && str_contains($migration, 'aylik_takvim'), 'Hizmet tanımında aylık takvim seçeneği kalıcı olarak saklanıyor');

fwrite(STDOUT, "Aylık paket smoke testleri tamamlandı.\n");
