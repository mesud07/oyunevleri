<?php

declare(strict_types=1);

$kok = dirname(__DIR__);
$model = file_get_contents($kok . '/app/Models/Randevu.php');
$ajax = file_get_contents($kok . '/public/ajax.php');
$migration = file_get_contents($kok . '/database/migrations/20260822_randevu_performans_indeksleri.sql');

$kontroller = [
    'takvim tarih indeksi' => str_contains($migration, 'idx_randevular_kurum_tarih_saat'),
    'durum ozeti indeksi' => str_contains($migration, 'idx_randevular_kurum_durum'),
    'yenileme kontrol indeksi' => str_contains($migration, 'idx_randevular_kurum_ogrenci_tarih_durum'),
    'tek seferlik indeks isaretcisi' => str_contains($model, 'randevu-performans-index-v1.ready'),
    'indeks hatasinda bekleme' => str_contains($model, 'randevu-performans-index-v1.failed'),
    'indeks dostu durum filtresi' => str_contains($model, 'AND r.durum IN ("geldi", "tamamlandi")'),
    'randevu liste oturum kilidi birakma' => str_contains($ajax, "['randevu_listele', 'randevu_takvim']"),
    'php oturum kilidi kapatma' => str_contains($ajax, 'session_write_close();'),
];

$basarisiz = array_keys(array_filter($kontroller, static fn(bool $sonuc): bool => !$sonuc));
if ($basarisiz !== []) {
    fwrite(STDERR, 'Basarisiz kontroller: ' . implode(', ', $basarisiz) . PHP_EOL);
    exit(1);
}

echo "Randevu performans kontrolleri basarili.\n";
