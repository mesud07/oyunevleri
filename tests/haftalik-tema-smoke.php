<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Models\HaftalikTema;

$kontroller = [
    [HaftalikTema::temaDurumu('geldi') === 'islendi', 'Geldi durumundaki randevu işlendi sayılır'],
    [HaftalikTema::temaDurumu('tamamlandi') === 'islendi', 'Tamamlanan randevu işlendi sayılır'],
    [HaftalikTema::temaDurumu('gelmedi') === 'islenmedi', 'Gelmeyen öğrenci temayı işlemedi sayılır'],
    [HaftalikTema::temaDurumu('mazeretli_gelmedi') === 'islenmedi', 'Mazeretli gelmeme işlenmedi sayılır'],
    [HaftalikTema::temaDurumu('planlandi') === 'bekliyor', 'Planlanan randevu bekliyor sayılır'],
];

$view = (string) file_get_contents(dirname(__DIR__) . '/resources/views/panel/haftalik-temalar.php');
$routes = (string) file_get_contents(dirname(__DIR__) . '/config/routes.php');
$ajax = (string) file_get_contents(dirname(__DIR__) . '/public/ajax.php');
$kontroller[] = [!str_contains($view, 'data-theme-activity-add'), 'Tema formunda etkinlik ekleme kaldırıldı'];
$kontroller[] = [!str_contains($view, 'grup-secimleri'), 'Tema formunda grup seçimi kaldırıldı'];
$kontroller[] = [!str_contains($routes, '/panel/haftalik-temalar/grup-secimleri'), 'Grup seçimi sayfa rotası kaldırıldı'];
$kontroller[] = [!str_contains($routes, '/panel/etkinlik-sablonlari'), 'Etkinlik şablonu sayfa rotası kaldırıldı'];
$kontroller[] = [!str_contains($ajax, "'ogrenci_etkinlik_ekle'"), 'Elle etkinlik ekleme API işlemi kaldırıldı'];
$kontroller[] = [str_contains($view, 'Öğrencilerin İşlediği Temalar'), 'Randevu katılımına dayalı tema takip tablosu eklendi'];

foreach ($kontroller as [$basarili, $aciklama]) {
    if (!$basarili) {
        fwrite(STDERR, '[HATA] ' . $aciklama . PHP_EOL);
        exit(1);
    }
    echo '[OK] ' . $aciklama . PHP_EOL;
}

echo "Haftalık tema smoke testleri tamamlandı.\n";
