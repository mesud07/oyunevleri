<?php

declare(strict_types=1);

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "[FAIL] {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "[OK] {$message}\n");
};

$rapor = file_get_contents(dirname(__DIR__) . '/app/Models/Rapor.php') ?: '';
$paket = file_get_contents(dirname(__DIR__) . '/app/Models/Paket.php') ?: '';
$randevu = file_get_contents(dirname(__DIR__) . '/app/Models/Randevu.php') ?: '';
$js = file_get_contents(dirname(__DIR__) . '/public/assets/js/panel.js') ?: '';

$hatirlatmaBaslangici = strpos($randevu, 'private static function yenilemeHatirlatmaTakvimi');
$hatirlatmaBitisi = strpos($randevu, '/**', $hatirlatmaBaslangici ?: 0);
$hatirlatmaSorgusu = $hatirlatmaBaslangici !== false
    ? substr($randevu, $hatirlatmaBaslangici, $hatirlatmaBitisi !== false ? $hatirlatmaBitisi - $hatirlatmaBaslangici : null)
    : '';

$assert(str_contains($rapor, 'COALESCE(sr.son_ders_tarihi, p.tahmini_son_ders_tarihi) AS tarih'), 'Yenileme takvimi randevulardaki gerçek son ders tarihini kullanıyor');
$assert(str_contains($rapor, 'MAX(tarih) AS son_ders_tarihi'), 'Telafi dahil en son randevu tarihi hesaplanıyor');
$assert(str_contains($rapor, 'durum NOT IN ("kurum_iptali", "ertelendi")'), 'İptal ve ertelenmiş randevular son ders hesabına katılmıyor');
$assert(str_contains($paket, 'durum NOT IN ("kurum_iptali", "ertelendi")'), 'Paket bitiş önbelleği yalnız geçerli randevularla güncelleniyor');
$assert(str_contains($randevu, 'durum NOT IN ("kurum_iptali", "ertelendi")'), 'Randevu değişikliklerinde paket bitiş önbelleği doğru hesaplanıyor');
$assert(!str_contains($hatirlatmaSorgusu, 'r.telafi_hakki_id IS NULL'), 'Telafi dersleri sonraki haftanın gri hatırlatmasına dahil ediliyor');
$assert(str_contains($js, 'Telafi dahil son ders tarihi'), 'Takvimde telafi nedeniyle kayan tarih açıklanıyor');
$assert(!str_contains($js, "odeme_bekliyor: 'Odeme alinmadi'") && !str_contains($js, "|| 'Odeme alinmadi'"), 'Gelecek yenileme takviminde Odeme alinmadi etiketi gosterilmiyor');

fwrite(STDOUT, "Yenileme takvimi smoke testleri tamamlandı.\n");
