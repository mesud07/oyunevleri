<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Services\DevamlilikRaporuServisi;

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "[FAIL] {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "[OK] {$message}\n");
};

$bugun = new DateTimeImmutable('2026-09-24');
$assert(!DevamlilikRaporuServisi::gelisimTestineUygunMu(null, $bugun), 'İlk paket tarihi olmayan öğrenci uygun gösterilmiyor');
$assert(!DevamlilikRaporuServisi::gelisimTestineUygunMu('2026-08-25', $bugun), 'Bir takvim ayını doldurmayan öğrenci uygun gösterilmiyor');
$assert(DevamlilikRaporuServisi::gelisimTestineKalanGun('2026-08-25', $bugun) === 1, 'Bir ay eşiğine kalan gün hesaplanıyor');
$assert(DevamlilikRaporuServisi::gelisimTestineUygunMu('2026-08-24', $bugun), 'Bir takvim ayını tamamlayan öğrenci teste uygun oluyor');
$assert(DevamlilikRaporuServisi::gelisimTestineUygunMu('2026-07-01', $bugun), 'Bir aydan uzun süredir kayıtlı öğrenci uygun kalıyor');
$assert(!DevamlilikRaporuServisi::pasifOgrenciMi('2026-09-17', $bugun), 'Son paketin üzerinden tam bir hafta geçen öğrenci henüz pasif değil');
$assert(DevamlilikRaporuServisi::pasifOgrenciMi('2026-09-16', $bugun), 'Son paketin üzerinden bir haftadan fazla geçen öğrenci pasif');
$assert(!DevamlilikRaporuServisi::pasifOgrenciMi('2026-09-25', $bugun), 'Gelecek paket tarihi öğrenciyi pasif yapmıyor');

$model = file_get_contents(dirname(__DIR__) . '/app/Models/OgrenciDevamlilikRaporu.php') ?: '';
$view = file_get_contents(dirname(__DIR__) . '/resources/views/panel/ogrenci-devamlilik-raporu.php') ?: '';
$js = file_get_contents(dirname(__DIR__) . '/public/assets/js/ogrenci-devamlilik.js') ?: '';
$migration = file_get_contents(dirname(__DIR__) . '/database/migrations/20260924_ogrenci_devamlilik_ve_gelisim_testi.sql') ?: '';
$assert(str_contains($model, 'IN ("geldi", "tamamlandi")'), 'Katılım hesabı mevcut randevu durumlarıyla uyumlu');
$assert(str_contains($model, 'FROM gunluk_notlar'), 'Öğrenci notları rapor detayına bağlanıyor');
$assert(str_contains($model, 'MIN(p.baslangic_tarihi)'), 'Kayıt başlangıcı öğrencinin ilk paketinden alınıyor');
$assert(str_contains($model, 'TIMESTAMPDIFF(MONTH'), 'Kayıt süresi ilk paket ile bugün arasında ay olarak hesaplanıyor');
$assert(str_contains($model, 'DATEDIFF(CURDATE(), rapor.ilk_paket_baslangic_tarihi)'), 'Kayıt süresi gün bazında hesaplanıyor');
$assert(str_contains($model, 'MAX(COALESCE(p.tahmini_son_ders_tarihi, p.baslangic_tarihi))'), 'Pasiflik için öğrencinin son paket tarihi bulunuyor');
$assert(str_contains($view, 'Gelişim Testi uygulamaya Uygun'), 'Bir aylık süre uygunluk etiketi raporda yer alıyor');
$assert(str_contains($view, 'Pasif Öğrenciler'), 'Pasif öğrenciler ayrı sekmede gösteriliyor');
$assert(str_contains($view, 'Uygulanmadı'), 'Gelişim testi uygulanma filtresi raporda yer alıyor');
$assert(!str_contains($view, 'Test notu (isteğe bağlı)'), 'Gelişim testi not alanı kaldırıldı');
$assert(!str_contains($view, 'type="submit">Kaydet</button>'), 'Gelişim testi için ayrı Kaydet düğmesi kaldırıldı');
$assert(str_contains($js, "input[name=\"uygulama_tarihi\"]") && str_contains($js, 'saveDevelopmentTest(form)'), 'Test durumu ve tarih değişiklikleri otomatik kaydediliyor');
$assert(str_contains($view, '1 ayı geçen, uygulanmamış'), 'Bir ayı geçen ve test uygulanmamış öğrenci filtresi var');
$assert(str_contains($view, 'attendance-print-sheet'), 'Filtrelenmiş liste için sade yazdırma tablosu var');
$assert(str_contains($js, 'window.print()'), 'Yazdır düğmesi tarayıcı yazdırma görünümünü açıyor');
$assert(str_contains($migration, 'UNIQUE KEY uq_ogrenci_gelisim_testi (kurum_id, ogrenci_id)'), 'Gelişim testi kaydı öğrenci ve kurum bazında tekilleştiriliyor');

fwrite(STDOUT, "Devamlılık raporu smoke testleri tamamlandı.\n");
