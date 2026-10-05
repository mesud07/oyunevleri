<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "[FAIL] {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "[OK] {$message}\n");
};

$migration = file_get_contents(dirname(__DIR__) . '/database/migrations/20260924_bekleyen_veli_gorusmeleri.sql') ?: '';
$model = file_get_contents(dirname(__DIR__) . '/app/Models/BekleyenVeli.php') ?: '';
$controller = file_get_contents(dirname(__DIR__) . '/app/Controllers/BekleyenVeliController.php') ?: '';
$view = file_get_contents(dirname(__DIR__) . '/resources/views/panel/bekleyen-veliler.php') ?: '';
$js = file_get_contents(dirname(__DIR__) . '/public/assets/js/panel.js') ?: '';

$assert(str_contains($migration, 'bekleyen_veli_gorusmeleri'), 'Görüşme tarihçesi tablosu migration içinde');
$assert(str_contains($migration, 'sonraki_takip_tarihi'), 'Sonraki takip tarihi saklanıyor');
$assert(str_contains($migration, 'ON DELETE CASCADE'), 'Bekleyen veli silinirse bağlı görüşmeler temizleniyor');
$assert(str_contains($model, 'public static function gorusmeEkle'), 'Bir veliye birden fazla görüşme ekleme modeli var');
$assert(str_contains($model, 'ORDER BY bg.gorusme_tarihi DESC'), 'Görüşmeler yeniden eskiye sıralanıyor');
$assert(str_contains($controller, 'Görüşme özeti zorunludur.'), 'Boş görüşme özeti reddediliyor');
$assert(str_contains($view, 'Veli Görüşme Tarihçesi'), 'Görüşme tarihçesi penceresi eklendi');
$assert(str_contains($view, 'Görüşme Kanalı'), 'Görüşme kanalı alanı eklendi');
$assert(str_contains($js, 'bekleyen_veli_gorusme_ekle'), 'Görüşme kayıt işlemi arayüze bağlı');
$assert(str_contains($js, 'data-waiting-parent-history'), 'Liste satırından görüşme tarihçesi açılıyor');

fwrite(STDOUT, "Bekleyen veli görüşme tarihçesi smoke testleri tamamlandı.\n");
