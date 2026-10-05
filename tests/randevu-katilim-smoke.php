<?php

declare(strict_types=1);

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "[FAIL] {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "[OK] {$message}\n");
};

$model = file_get_contents(dirname(__DIR__) . '/app/Models/Randevu.php') ?: '';
$controller = file_get_contents(dirname(__DIR__) . '/app/Controllers/RandevuKatilimController.php') ?: '';
$view = file_get_contents(dirname(__DIR__) . '/resources/views/randevu-katilim/form.php') ?: '';
$automation = file_get_contents(dirname(__DIR__) . '/app/Services/SmsOtomasyonCalistirici.php') ?: '';
$cron = file_get_contents(dirname(__DIR__) . '/cron/sms-otomasyon.php') ?: '';
$compose = file_get_contents(dirname(__DIR__) . '/compose.production.yaml') ?: '';

$assert(str_contains($model, 'randevu_katilim_tokenlari'), 'Bir randevu icin birden fazla kalici SMS tokeni destekleniyor');
$assert(str_contains($model, 'katilimTokenHashiniKaydet'), 'Eski SMS tokenleri kalici kayda aliniyor');
$assert(str_contains($model, 'INNER JOIN sms_kayitlari sk') && str_contains($model, "'%?t=' . strtolower(\$token)"), 'Onceden gonderilmis eski SMS linkleri de randevuya baglaniyor');
$assert(!str_contains($model, 'SET katilim_token = NULL'), 'Yeni tokenlar onceki katilim linklerini ezmiyor');
$assert(!str_contains($model, 'katilim_token_son_kullanim >= NOW()'), 'Ders sonrasi link randevu detayini gostermeye devam ediyor');
$assert(str_contains($model, '(TIMESTAMP(r.tarih, r.baslangic_saati) > NOW()) AS katilim_acik'), 'Katilim penceresi ders baslangic saatine gore belirleniyor');
$assert(str_contains($model, 'TIMESTAMP(r.tarih, r.baslangic_saati) > NOW()'), 'Ders saati gecince yeni yanit veritabaninda engelleniyor');
$assert(str_contains($view, 'if ($katilimAcik)') && str_contains($view, 'artik degistirilemez'), 'Gecmis randevu salt okunur gosteriliyor');
$assert(str_contains($controller, "empty(\$randevu['katilim_acik'])"), 'Gec kalan veliye acik durum mesaji veriliyor');
$assert(str_contains($automation, 'foreach (Kurum::aktifIdler()'), 'Otomasyon tum aktif kurumlari isliyor');
$assert(str_contains($automation, 'sms_automation_last_run_at'), 'Otomasyon son calisma bilgisini kurum bazinda kaydediyor');
$assert(str_contains($cron, 'tumKurumlarIcinCalistir'), 'Tek komutluk SMS zamanlayicisi hazir');
$assert(str_contains($compose, 'scheduler:') && str_contains($compose, 'cron/sms-otomasyon.php'), 'Uretim konteynerinde otomatik scheduler servisi var');
$assert(str_contains(file_get_contents(dirname(__DIR__) . '/app/Services/SmsServisi.php') ?: '', 'katilimLinkiniSablonGerektiriyorsaEkle'), 'Katilim linki yalnizca kullanan sablonlar icin erkenden uretiliyor');
$assert(str_contains(file_get_contents(dirname(__DIR__) . '/app/Services/SmsServisi.php') ?: '', "Config::get('SMS_PUBLIC_BASE_URL'"), 'SMS linki icin telefondan erisilebilen genel adres destekleniyor');

fwrite(STDOUT, "Randevu katilim ve SMS otomasyonu smoke testleri tamamlandi.\n");
