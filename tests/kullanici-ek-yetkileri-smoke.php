<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Veritabani;

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "[FAIL] {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "[OK] {$message}\n");
};

$root = dirname(__DIR__);
$migration = file_get_contents($root . '/database/migrations/20260929_kullanici_ek_yetkileri.sql') ?: '';
$schema = file_get_contents($root . '/database/schema.sql') ?: '';
$service = file_get_contents($root . '/app/Services/YetkiServisi.php') ?: '';
$model = file_get_contents($root . '/app/Models/Kullanici.php') ?: '';
$controller = file_get_contents($root . '/app/Controllers/KullaniciController.php') ?: '';
$view = file_get_contents($root . '/resources/views/panel/kullanicilar.php') ?: '';
$studentView = file_get_contents($root . '/resources/views/panel/ogrenciler.php') ?: '';
$javascript = file_get_contents($root . '/public/assets/js/kullanicilar.js') ?: '';
$moduleService = file_get_contents($root . '/app/Services/KurumModuluServisi.php') ?: '';
$routes = file_get_contents($root . '/config/routes.php') ?: '';

$assert(str_contains($migration, 'CREATE TABLE IF NOT EXISTS kullanici_ek_yetkileri'), 'Kullanıcıya özel ek yetki tablosu migration içinde');
$assert(str_contains($migration, "r.kod = 'ogretmen'") && str_contains($migration, "ry.yetki = 'ogrenci_ekle'"), 'Öğretmenin varsayılan öğrenci ekleme yetkisi kaldırılıyor');
$assert(str_contains($schema, 'CREATE TABLE IF NOT EXISTS `kullanici_ek_yetkileri`'), 'Temiz kurulum şeması ek yetkileri içeriyor');
$assert(str_contains($service, 'FROM kullanici_ek_yetkileri') && str_contains($service, '(int) $kullanici[\'id\']'), 'Merkezi yetki servisi kullanıcıya özel ek yetkiyi denetliyor');
$assert(str_contains($model, 'ekYetkileriKaydet') && str_contains($model, 'keyt.kurum_id = k.kurum_id'), 'Ek yetkiler kullanıcı ve kurum bazında saklanıyor');
$assert(str_contains($controller, 'Kullanici::ekYetkileriKaydet'), 'Kullanıcı kaydında ek yetkiler güncelleniyor');
$assert(str_contains($view, 'name="ek_yetkiler[]"') && str_contains($view, 'Kullanıcıya Özel Ek Yetkiler'), 'Kullanıcı düzenleme penceresinde ek yetki seçimi var');
$assert(str_contains($javascript, 'values.ek_yetkiler') && str_contains($javascript, 'row?.ek_yetkiler'), 'Ek yetkiler arayüzde yüklenip kaydediliyor');
$assert(str_contains($model, 'AS rol_yetkiler') && str_contains($javascript, 'syncUserPermissions'), 'Kullanıcı tipinden gelen mevcut yetkiler işaretli gösteriliyor');
$assert(str_contains($view, 'data-user-permission-module') && str_contains($moduleService, 'yetkiModulu'), 'Yetkiler kurum modüllerine göre gruplanıyor');
$assert(str_contains($moduleService, "'fatura_entegrasyon_yonet' => ['entegrasyon_ayarlari']"), 'Fatura entegrasyon yetkisi yalnız entegrasyon sayfasına bağlı');
$assert(str_contains($controller, 'sayfalarKurumIcin') && str_contains($controller, 'yetkiSayfalari'), 'Kapalı kurum sayfalarının yetkileri kullanıcı ekranından filtreleniyor');
$assert(str_contains($javascript, 'box.disabled = inherited') && str_contains($javascript, 'Kullanıcı tipinden geliyor'), 'Rol yetkileri sabit, ek yetkiler düzenlenebilir gösteriliyor');
$assert(str_contains($studentView, "if (yetki_var('ogrenci_ekle'))") && str_contains($studentView, 'Yeni Ogrenci Kaydi'), 'Yeni öğrenci düğmesi öğrenci ekleme yetkisine bağlı');
$assert(str_contains($routes, "'/panel/ogrenciler/yeni', [OgrenciController::class, 'yeniKayit'], 'ogrenci_ekle'"), 'Yeni öğrenci sayfasının doğrudan erişimi de yetkiyle korunuyor');

$db = Veritabani::baglan();
$tableExists = (int) $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kullanici_ek_yetkileri'")->fetchColumn();
$assert($tableExists === 1, 'Ek yetki tablosu veritabanında hazır');
$teacherCanCreate = (int) $db->query(
    "SELECT COUNT(*) FROM rol_yetkileri ry INNER JOIN roller r ON r.id = ry.rol_id WHERE r.kod = 'ogretmen' AND ry.yetki = 'ogrenci_ekle'"
)->fetchColumn();
$assert($teacherCanCreate === 0, 'Öğretmen rolü varsayılan olarak yeni öğrenci ekleyemiyor');

fwrite(STDOUT, "Kullanıcı ek yetkileri smoke testleri tamamlandı.\n");
