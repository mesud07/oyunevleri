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
$migration = file_get_contents($root . '/database/migrations/20260925_veli_onam_formlari.sql') ?: '';
$contentMigration = file_get_contents($root . '/database/migrations/20260925_veli_onam_form_icerigi.sql') ?: '';
$routes = file_get_contents($root . '/config/routes.php') ?: '';
$controller = file_get_contents($root . '/app/Controllers/VeliOnamController.php') ?: '';
$ajax = file_get_contents($root . '/public/ajax.php') ?: '';
$packageController = file_get_contents($root . '/app/Controllers/PaketController.php') ?: '';
$model = file_get_contents($root . '/app/Models/VeliOnam.php') ?: '';
$panel = file_get_contents($root . '/resources/views/panel/veli-onamlari.php') ?: '';
$quickDialog = file_get_contents($root . '/resources/views/partials/hizli-randevu-dialog.php') ?: '';
$panelJs = file_get_contents($root . '/public/assets/js/panel.js') ?: '';
$studentModel = file_get_contents($root . '/app/Models/Ogrenci.php') ?: '';
$studentProfile = file_get_contents($root . '/resources/views/panel/ogrenci-profil.php') ?: '';
$public = file_get_contents($root . '/resources/views/veli-onam/form.php') ?: '';
$pdf = file_get_contents($root . '/resources/views/pdf/veli-onam.php') ?: '';
$composer = json_decode(file_get_contents($root . '/composer.json') ?: '{}', true);

$assert(str_contains($migration, 'CREATE TABLE IF NOT EXISTS onam_form_ayarlari'), 'Kuruma özel ortak onam bağlantısı tablosu var');
$assert(str_contains($migration, 'CREATE TABLE IF NOT EXISTS veli_onam_kayitlari'), 'Dijital veli onam kayıt tablosu var');
$assert(str_contains($routes, "'/oyun-grubu-onam'"), 'Ortak veli formu rotası tanımlı');
$assert(str_contains($routes, "'/panel/veli-onamlari'"), 'Onam yönetim ekranı rotası tanımlı');
$assert(str_contains($controller, 'HizSinirlayici'), 'Herkese açık form hız sınırıyla korunuyor');
$assert(str_contains($controller, 'Csrf::dogrula'), 'Form gönderiminde CSRF doğrulaması yapılıyor');
$assert(str_contains($model, 'telefonNormalize') && str_contains($model, 'veliIdBul'), 'Telefon üzerinden mevcut veli eşleştiriliyor');
$assert(str_contains($model, 'ogrenciIdBul'), 'Katılımcı mevcut öğrenci kaydıyla eşleştiriliyor');
$assert(str_contains($model, 'eslesmeleriGuncelle') && str_contains($model, 'acil_durum_telefon'), 'Sonradan eklenen öğrenci randevusu olmasa da onam kaydıyla yeniden eşleştiriliyor');
$assert(str_contains($migration, 'onam_metni LONGTEXT NOT NULL'), 'Gönderim anındaki onam metni kayıtta saklanıyor');
$assert(str_contains($model, 'onamMetniGuncelle') && str_contains($model, 'form_surumu = form_surumu + 1'), 'Kuruma özel onam metni güncellenirken form sürümü artırılıyor');
$assert(str_contains($panel, 'data-open-dialog="#veli-onam-ayari-dialog"') && str_contains($panel, '>Onam Metnini Düzenle<'), 'Onam metni düzenleme düğmesi QR indirme işleminin yanında bulunuyor');
$assert(str_contains($panel, 'id="veli-onam-ayari-dialog"') && str_contains($panel, 'data-ajax-form="veli_onam_ayari_kaydet"') && str_contains($panel, 'name="onam_metni"'), 'Kurucu onam metnini modal pencereden düzenleyebiliyor');
$assert(str_contains($controller, "'kurucu'") && str_contains($controller, 'ayarDuzenleyebilir'), 'Onam metni düzenleme işlemi kurucu rolüyle sınırlandırılıyor');
$assert(str_contains($ajax, "'veli_onam_ayari_kaydet'"), 'Onam metni kaydetme işlemi AJAX bağlantısına sahip');
$assert(str_contains($public, 'dijital_onay'), 'Veli açık dijital onay vermeden form gönderilemiyor');
$assert(str_contains($public, 'uzman_destegi') && str_contains($public, 'oyun_grubu_deneyimi'), 'Uzman desteği ve oyun grubu deneyimi forma aktarıldı');
$assert(str_contains($public, 'name="il"') && str_contains($public, 'name="ilce"') && str_contains($public, 'Muratpaşa'), 'İl ve Antalya ilçeleri ayrı seçim alanları olarak sunuluyor');
$assert(str_contains($controller, 'ANTALYA_ILCELERI') && str_contains($controller, "'ilce' =>"), 'İlçe seçimi sunucu tarafında doğrulanıp kaydediliyor');
$assert(str_contains($pdf, "\$alan('İl',") && str_contains($pdf, "\$alan('İlçe',") && str_contains($pdf, "\$alan('Açık adres',"), 'İl, ilçe ve açık adres PDF çıktısında ayrı gösteriliyor');
$assert(str_contains($public, 'tani_durumu') && str_contains($public, 'ilac_durumu'), 'Sağlık soruları forma aktarıldı');
$assert(str_contains($public, 'gorsel_kayit_izni') && str_contains($public, 'sosyal_medya_izni'), 'Görsel izinleri birbirinden bağımsız kaydediliyor');
$assert(str_contains($contentMigration, 'PROGRAM KURALLARI') && str_contains($contentMigration, 'PROGRAM KATILIM ONAMI'), 'Basılı formdaki program metinleri dijital onama aktarıldı');
$assert(str_contains($panel, 'qr.svg') && str_contains($panel, 'PDF Aç'), 'Panelde QR paylaşımı ve PDF çıktısı bulunuyor');
$assert(str_contains($panel, 'data-quick-appointment-prefill') && str_contains($quickDialog, 'name="veli_onam_id"'), 'Onam kaydı hızlı randevu formuna aktarılabiliyor');
$assert(str_contains($packageController, "\$data['ogrenci_id']") && str_contains($packageController, 'Ogrenci::raporBilgisi'), 'Hızlı randevu eşleşmiş mevcut öğrenciyi kullanıyor');
$assert(str_contains($panelJs, 'ogrenci_ad_soyad: opener.dataset.ogrenciAdSoyad'), 'Hızlı randevu alanları onam bilgileriyle dolduruluyor');
$assert(str_contains($model, 'ogrenciBilgileriniGuncelle') && str_contains($model, 'form_verisi_json'), 'Onam bilgileri eşleşen öğrenci ve veli kaydına aktarılıyor');
$assert(str_contains($studentModel, "'veli_onam' => \$veliOnam"), 'Son onam kaydı öğrenci profiline yükleniyor');
$assert(str_contains($studentProfile, 'Onam Formu Bilgileri') && str_contains($studentProfile, "Onam PDF'ini Aç"), 'Onam formunun tüm bilgileri öğrenci profilinde gösteriliyor');
$assert(str_contains($pdf, 'Dijital veli onayı alınmıştır'), 'PDF üzerinde dijital onay kaydı gösteriliyor');
$assert(str_contains($pdf, 'oyun-evleri-logo.png') && str_contains($pdf, 'meb_logo_pdf.jpg'), 'PDF, Oyun Evleri ve MEB logolarının sunucu uyumlu sürümlerini kullanıyor');
$assert(str_contains($pdf, "\$kayit['kurum_adi']"), 'PDF üzerinde kayda ait kurumun adı gösteriliyor');
$assert(str_contains($pdf, 'Dijital olarak imzalanmıştır'), 'PDF alt bölümünde dijital imza ifadesi bulunuyor');
$assert(str_contains($pdf, "' yaş ' . \$yasFarki->m . ' ay'"), 'PDF yaşı tamamlanan yıl ve kalan ay olarak gösteriyor');
$assert(str_contains($pdf, 'class="info-grid"') && str_contains($pdf, '<td colspan="2">'), 'PDF bilgileri iki sütunlu kompakt düzende gösteriyor');
$assert(str_contains($pdf, 'page-break-before: always'), 'Onam metni ikinci sayfada kontrollü başlatılıyor');
$assert(isset($composer['require']['endroid/qr-code']), 'QR kodu yerel kütüphaneyle üretiliyor');

fwrite(STDOUT, "Veli onam formu smoke testleri tamamlandı.\n");
