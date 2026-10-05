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
$migration = file_get_contents($root . '/database/migrations/20260924_bekleyen_veli_gruplari_ve_durumlari.sql') ?: '';
$waitingModel = file_get_contents($root . '/app/Models/BekleyenVeli.php') ?: '';
$groupModel = file_get_contents($root . '/app/Models/Grup.php') ?: '';
$ajax = file_get_contents($root . '/public/ajax.php') ?: '';
$waitingView = file_get_contents($root . '/resources/views/panel/bekleyen-veliler.php') ?: '';
$waitingController = file_get_contents($root . '/app/Controllers/BekleyenVeliController.php') ?: '';
$groupView = file_get_contents($root . '/resources/views/panel/gruplar.php') ?: '';
$panelJs = file_get_contents($root . '/public/assets/js/panel.js') ?: '';
$groupJs = file_get_contents($root . '/public/assets/js/gruplar.js') ?: '';

$assert(str_contains($migration, 'CREATE TABLE IF NOT EXISTS bekleyen_veli_gruplari'), 'Veli ve grup arasında kalıcı bağlantı tablosu var');
$assert(str_contains($migration, "'bilgi_verildi','ulasilamadi','katilmadi'"), 'Yeni takip durumları veritabanı enumuna eklendi');
$assert(str_contains($waitingModel, 'public static function gruplariGuncelle'), 'Beklenen gruplar güncellenebiliyor');
$assert(str_contains($waitingModel, 'public static function grubaGoreListe'), 'Grup bazında bekleyen veliler listelenebiliyor');
$assert(str_contains($waitingModel, 'self::tarihceEkle'), 'Durum ve grup değişiklikleri görüşme tarihçesine yazılıyor');
$assert(str_contains($groupModel, 'bekleyen_veli_sayisi'), 'Haftalık program satırında bekleyen veli sayısı hesaplanıyor');
$assert(str_contains($ajax, "'grup_bekleyen_veli_listele'"), 'Haftalık program hızlı liste uç noktası kayıtlı');
$assert(str_contains($ajax, "'bekleyen_veli_gruplari_guncelle'"), 'Veli-grup eşleştirme uç noktası kayıtlı');
$assert(str_contains($ajax, "'bekleyen_veli_guncelle'"), 'Bekleyen veli düzenleme uç noktası kayıtlı');
$assert(str_contains($waitingView, 'Beklediği Gruplar'), 'Bekleyen veli detayında grup seçimi bulunuyor');
$assert(str_contains($waitingView, 'name="grup_ids[]"'), 'Yeni veli eklenirken birden fazla grup seçilebiliyor');
$assert(str_contains($waitingView, 'data-waiting-parent-edit-dialog'), 'Bekleyen veli öğrenci bilgileri düzenleme penceresi bulunuyor');
$assert(!str_contains($waitingView, 'name="beklenen_gun"'), 'Eski beklenen gün alanı ekleme formundan kaldırıldı');
$assert(str_contains($waitingController, 'Grup::secenekler()'), 'Ekleme ekranına aktif grup seçenekleri aktarılıyor');
$assert(str_contains($groupModel, 'dp.baslangic_saati') && str_contains($groupModel, 'dp.bitis_saati'), 'Grup seçeneklerine ders saatleri ekleniyor');
$assert(str_contains($groupModel, 'CASE WHEN dp.gun IS NULL THEN 8 ELSE dp.gun END ASC'), 'Grup seçenekleri haftanın günlerine göre sıralanıyor');
$assert(str_contains($panelJs, 'waiting-parent-group-schedule'), 'Görüşme detayında grup gün ve saatleri gösteriliyor');
$assert(str_contains($waitingController, 'BekleyenVeli::gruplariGuncelle($id, $grupIdleri'), 'İlk kayıtta seçilen gruplar veliye bağlanıyor');
$assert(str_contains($groupView, 'data-can-manage-waiting'), 'Hızlı durum işlemleri yetkiye göre gösteriliyor');
$assert(str_contains($panelJs, 'bekleyen_veli_gruplari_guncelle'), 'Grup seçimi arayüzden kaydediliyor');
$assert(!str_contains($panelJs, '<th>Bekledigi Gun</th>'), 'Beklediği gün sütunu listeden kaldırıldı');
$assert(str_contains($panelJs, 'waiting-parent-row-actions'), 'Liste işlemleri kompakt ikon grubunda gösteriliyor');
$assert(!str_contains($panelJs, 'data-save-waiting-parent-status'), 'Ayrı durum kaydet butonu kaldırıldı');
$assert(str_contains($panelJs, 'data-saved-status'), 'Durum değişikliğinde hata olursa eski değer korunuyor');
$assert(str_contains($panelJs, "event.target.closest('[data-waiting-parent-status]')"), 'Durum seçimi değiştiğinde otomatik kayıt başlıyor');
$assert(str_contains($waitingModel, 'listeye_ekleneli_gun') && str_contains($waitingModel, 'DATEDIFF(CURDATE()'), 'Listeye eklenme süresi gün olarak hesaplanıyor');
$assert(str_contains($waitingView, 'data-waiting-parent-view="converted"'), 'Aktif kayda dönüşenler ayrı görünümden açılabiliyor');
$assert(str_contains($panelJs, "row.durum === 'kayda_donustu'") && str_contains($panelJs, 'waitingParentDaysLabel'), 'Liste görünümü duruma göre ayrılıyor ve geçen gün gösteriliyor');
$assert(str_contains($panelJs, 'data-waiting-parent-sort') && str_contains($panelJs, 'waitingParentSortValue'), 'Bekleyen veli sütun başlıkları tıklanarak sıralanabiliyor');
$assert(str_contains($waitingModel, 'public static function guncelle') && str_contains($panelJs, 'data-edit-waiting-parent'), 'Bekleyen veli satırları mevcut bilgilerle düzenlenebiliyor');
$assert(str_contains($groupJs, 'grup_bekleyen_veli_listele'), 'Haftalık programdan bekleyen veliler yükleniyor');
$assert(str_contains($groupJs, 'data-group-waiting-status="bilgi_verildi"'), 'Bilgi Verildi hızlı işlemi bulunuyor');
$assert(str_contains($groupJs, 'data-group-waiting-status="ulasilamadi"'), 'Ulaşılamadı hızlı işlemi bulunuyor');
$assert(str_contains($groupJs, 'data-group-waiting-status="katilmadi"'), 'Katılmadı hızlı işlemi bulunuyor');

fwrite(STDOUT, "Bekleyen veli grup eşleştirme smoke testleri tamamlandı.\n");
