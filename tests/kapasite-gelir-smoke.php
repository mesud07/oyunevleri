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
$model = file_get_contents($root . '/app/Models/Rapor.php') ?: '';
$view = file_get_contents($root . '/resources/views/panel/raporlar.php') ?: '';
$style = file_get_contents($root . '/public/assets/css/panel.css') ?: '';

foreach (['gelmedi', 'mazeretli_gelmedi', 'gec_iptal', 'kurum_iptali', 'ertelendi'] as $durum) {
    $assert(
        str_contains($model, sprintf('"%s"', $durum)),
        sprintf('%s durumundaki ders kapasite ve gelir sorgusunda haric tutuluyor', $durum)
    );
}

$assert(str_contains($model, 'AS kayip_ders_sayisi'), 'Gelmedi ve iptal edilen derslerin sayisi ayri hesaplaniyor');
$assert(str_contains($model, 'AS kayip_bakiye'), 'Gelmedi ve iptal edilen derslerin ders basi eksi bakiyesi hesaplaniyor');
$assert(str_contains($model, "'kayip_bakiye' => \$kayipBakiye"), 'Hesaplanan eksi bakiye rapor verisine aktariliyor');
$assert(str_contains($view, 'Gelmedi / Iptal Eksi Bakiye'), 'Toplam eksi bakiye rapor ekraninda gosteriliyor');
$assert(str_contains($view, '<span>Net Ders Sayisi</span>'), 'Ekranda iptaller dusulmus ders sayisi net olarak adlandiriliyor');
$assert(str_contains($view, 'Gelmedi, mazeretli gelmedi, gec iptal, kurum iptali ve ertelenen dersler hesaplamadan dusulur.'), 'Raporun hesaplama kapsami kullaniciya aciklaniyor');
$assert(str_contains($style, '.capacity-metrics .capacity-loss-metric strong'), 'Eksi bakiye karti finansal kayip olarak vurgulaniyor');

fwrite(STDOUT, "Kapasite ve gelir smoke testleri tamamlandi.\n");
