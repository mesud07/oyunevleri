<?php

declare(strict_types=1);

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "[FAIL] {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "[OK] {$message}\n");
};

$dashboard = file_get_contents(dirname(__DIR__) . '/resources/views/panel/genel-bakis.php') ?: '';
$smsView = file_get_contents(dirname(__DIR__) . '/resources/views/panel/sms-yonetimi.php') ?: '';
$smsJs = file_get_contents(dirname(__DIR__) . '/public/assets/js/sms.js') ?: '';
$smsController = file_get_contents(dirname(__DIR__) . '/app/Controllers/SmsController.php') ?: '';
$smsService = file_get_contents(dirname(__DIR__) . '/app/Services/SmsServisi.php') ?: '';
$cron = file_get_contents(dirname(__DIR__) . '/cron/randevu-sms-hatirlatma.php') ?: '';
$managerPermissionMigration = file_get_contents(dirname(__DIR__) . '/database/migrations/20260928_yonetici_sms_yetkileri.sql') ?: '';
$defaultTemplateMigration = file_get_contents(dirname(__DIR__) . '/database/migrations/20260928_kurum_varsayilan_sms_sablonlari.sql') ?: '';
$smsModel = file_get_contents(dirname(__DIR__) . '/app/Models/SmsKaydi.php') ?: '';
$studentModel = file_get_contents(dirname(__DIR__) . '/app/Models/Ogrenci.php') ?: '';
$institutionModel = file_get_contents(dirname(__DIR__) . '/app/Models/Kurum.php') ?: '';

$assert(str_contains($dashboard, 'data-open-dialog="#haftalik-dogum-gunleri-dialog"'), 'Fazladan dogum gunleri tiklanabilir pencereyi aciyor');
$assert(str_contains($dashboard, 'birthday-dialog-list'), 'Dogum gunu penceresinde haftanin tum ogrencileri listeleniyor');
$assert(str_contains($smsView, 'name="birthday_message_enabled"'), 'Dogum gunu otomatik SMS ayari yonetim ekraninda');
$assert(str_contains($smsView, 'name="birthday_message_time"'), 'Dogum gunu SMS saati yonetim ekraninda');
$assert(str_contains($smsJs, 'values.birthday_message_enabled'), 'Dogum gunu aktiflik secimi kayit istegine ekleniyor');
$assert(str_contains($smsController, "'sms_birthday_message_enabled'"), 'Dogum gunu SMS ayari kurum bazinda kaydediliyor');
$assert(str_contains($smsService, 'public function dogumGunuMesajlariOlustur'), 'Dogum gunu mesajlari otomatik kuyruga aliniyor');
$assert(str_contains($smsService, "'mukerrer_anahtari' => 'dogum_gunu:'"), 'Ayni dogum gunu mesaji ikinci kez olusturulmuyor');
$assert(str_contains($cron, '$servis->dogumGunuMesajlariOlustur()'), 'Zamanlayici dogum gunu mesajlarini calistiriyor');
$assert(str_contains($managerPermissionMigration, "r.kod = 'yonetici'") && str_contains($managerPermissionMigration, "'sms_ayar_yonet'"), 'Kurum yoneticisi NetGSM ve SMS ayarlarini yonetebiliyor');
$assert(str_contains($managerPermissionMigration, "'sms_sablon_yonet'") && str_contains($managerPermissionMigration, "'sms_rapor_goruntule'"), 'Kurum yoneticisi SMS sablon ve raporlarini yonetebiliyor');
$assert(str_contains($smsView, 'Kayıtlı şifreyi korumak için boş bırakın'), 'Kayitli NetGSM sifresi ekrana geri yazilmiyor');
$assert(str_contains($smsController, 'if ($password === \'\')'), 'Bos sifre gonderilince mevcut NetGSM sifresi korunuyor');
$assert(str_contains($smsController, "['netgsm']['password'] = ''"), 'NetGSM sifresi kayit yanitinda istemciye donmuyor');
$assert(str_contains($defaultTemplateMigration, "'randevu_olusturuldu'") && str_contains($defaultTemplateMigration, '{randevu_listesi}'), 'Randevu olusturma sablonu toplu ders listesini iceriyor');
$assert(str_contains($defaultTemplateMigration, "'randevu_hatirlatma'") && str_contains($defaultTemplateMigration, '{katilim_linki}'), 'Randevu hatirlatma sablonu katilim linkini iceriyor');
$assert(str_contains($defaultTemplateMigration, "'geciken_odeme'") && str_contains($defaultTemplateMigration, '{kalan_borc}'), 'Geciken odeme sablonu kalan borcu iceriyor');
$assert(str_contains($defaultTemplateMigration, "'odeme_alindi'") && str_contains($defaultTemplateMigration, '{odeme_tutari}'), 'Odeme bilgilendirme sablonu tutari iceriyor');
$assert(str_contains($smsModel, 'VARSAYILAN_SABLONLAR') && str_contains($institutionModel, 'varsayilanSablonlariEkle'), 'Yeni kurumlar standart SMS sablonlariyla olusturuluyor');
$assert(str_contains($defaultTemplateMigration, "'kurum_adi'") && str_contains($defaultTemplateMigration, 'FROM kurumlar'), 'SMS kurum adi her kurum icin ayri tanimlaniyor');
$assert(str_contains($smsView, 'name="ogrenci_idleri[]"') && str_contains($smsView, 'data-sms-student-search'), 'Toplu SMS alaninda mevcut ogrenciler aranip secilebiliyor');
$assert(str_contains($smsJs, 'data-sms-students-select-visible') && str_contains($smsJs, 'updateStudentCount'), 'Toplu SMS ogrenci secim kontrolleri calisiyor');
$assert(str_contains($studentModel, 'public static function smsAliciSecenekleri') && str_contains($studentModel, 'o.kurum_id = ?'), 'SMS ogrenci alicilari kurum bazinda getiriliyor');
$assert(str_contains($smsController, 'Ogrenci::smsAliciSecenekleri($ogrenciIdleri)') && str_contains($smsController, 'manuelTopluAlicilar'), 'Secili ogrencilerin veli telefonlari sunucuda dogrulaniyor');
$assert(str_contains($smsService, 'public function manuelTopluAlicilar'), 'Ogrenci bilgili toplu SMS alicilari kuyruga aktariliyor');
$assert(str_contains($smsJs, 'SMS servisini pasife almak üzeresiniz') && str_contains($smsJs, 'window.confirm(warning)'), 'SMS servisi aktif veya pasif yapilirken etkisi onaylatiliyor');

fwrite(STDOUT, "Dogum gunu liste ve SMS smoke testleri tamamlandi.\n");
