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
$routes = file_get_contents($root . '/config/routes.php') ?: '';
$ajax = file_get_contents($root . '/public/ajax.php') ?: '';
$model = file_get_contents($root . '/app/Models/Veli.php') ?: '';
$controller = file_get_contents($root . '/app/Controllers/VeliController.php') ?: '';
$modules = file_get_contents($root . '/app/Services/KurumModuluServisi.php') ?: '';
$layout = file_get_contents($root . '/resources/views/layouts/panel.php') ?: '';
$view = file_get_contents($root . '/resources/views/panel/veliler.php') ?: '';
$javascript = file_get_contents($root . '/public/assets/js/veliler.js') ?: '';

$assert(str_contains($routes, "'/panel/adres-defteri'"), 'Adres Defteri panel rotası tanımlı');
$assert(str_contains($layout, 'href="/panel/adres-defteri"') && str_contains($layout, '>Adres Defteri</a>'), 'Adres Defteri öğrenci işlemleri menüsüne bağlı');
$assert(str_contains($modules, "'adres_defteri' => 'Adres Defteri'") && str_contains($modules, "'/panel/adres-defteri' => 'adres_defteri'"), 'Adres Defteri kurum bazında açılıp kapatılabiliyor');
$assert(str_contains($modules, "['veli_listele', 'veli_ekle']") && str_contains($modules, "sayfaAktifMi('ogrenciler'") && str_contains($modules, "sayfaAktifMi('adres_defteri'"), 'Mevcut öğrenci ekranlarının veli işlemleri Adres Defteri ayarından etkilenmiyor');
$assert(str_contains($model, 'WHERE v.kurum_id = :kurum_id') && str_contains($model, 'WHERE id = :id AND kurum_id = :kurum_id'), 'Adres Defteri listeleme ve güncelleme işlemleri kurumla sınırlandırılıyor');
$assert(str_contains($model, 'GROUP_CONCAT') && str_contains($model, 'ogrenci_velileri'), 'Adres Defteri kişileri bağlı öğrencileriyle birlikte listeliyor');
$assert(str_contains($ajax, "'veli_guncelle'") && str_contains($controller, 'public function guncelle'), 'Kişi güncelleme API işlemi bağlı');
$assert(str_contains($view, 'data-address-search') && str_contains($view, 'data-address-relation') && str_contains($view, 'data-address-completeness'), 'Adres Defteri arama ve filtre alanlarını içeriyor');
$assert(str_contains($javascript, "talyaAjax('veli_listele')") && str_contains($javascript, "'veli_guncelle' : 'veli_ekle'"), 'Adres Defteri istemci kodu listeleme, ekleme ve düzenlemeyi destekliyor');
$assert(str_contains($javascript, 'https://wa.me/') && str_contains($javascript, 'https://www.google.com/maps/search/'), 'Telefon kayıtları WhatsApp ve harita hızlı işlemlerine bağlı');

fwrite(STDOUT, "Adres Defteri smoke testleri tamamlandı.\n");
