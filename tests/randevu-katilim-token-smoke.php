<?php

declare(strict_types=1);

$assert = static function (bool $kosul, string $mesaj): void {
    if (!$kosul) {
        fwrite(STDERR, "[FAIL] {$mesaj}\n");
        exit(1);
    }
    fwrite(STDOUT, "[OK] {$mesaj}\n");
};

$kok = dirname(__DIR__);
$model = file_get_contents($kok . '/app/Models/Randevu.php') ?: '';
$controller = file_get_contents($kok . '/app/Controllers/RandevuKatilimController.php') ?: '';
$migration = file_get_contents($kok . '/database/migrations/20261005_randevu_katilim_tokenlari_suresiz.sql') ?: '';
$kurtarma = file_get_contents($kok . '/bin/randevu-katilim-token-migrate.php') ?: '';

$assert(
    str_contains($model, 'INSERT INTO randevu_katilim_tokenlari')
    && !str_contains($model, 'SET katilim_token = NULL'),
    'Her yeni SMS tokeni onceki linkleri ezmeden ayri kaydediliyor'
);
$assert(
    str_contains($model, 'INNER JOIN randevu_katilim_tokenlari kt')
    && str_contains($model, 'kt.token_hash = :token_hash'),
    'Kalici token tablosundaki tum eski linkler randevuya ulasabiliyor'
);
$assert(
    !str_contains($model, 'kt.iptal_tarihi IS NULL')
    && !str_contains($model, 'katilim_token_son_kullanim >= NOW()'),
    'Tokenlar icin sure veya iptal tarihi nedeniyle gecersizlesme yok'
);
$assert(
    str_contains($model, 'katilimTokenHashiniKaydet')
    && str_contains($model, 'INNER JOIN sms_kayitlari sk'),
    'Eski SMS linkleri ilk kullanimda kalici token tablosuna aliniyor'
);
$assert(
    !str_contains($controller, 'randevu-katilim:get:')
    && str_contains($controller, 'randevu-katilim:post:'),
    'Link goruntuleme hiz sinirina takilmiyor, yanit gonderimi korunuyor'
);
$assert(
    str_contains($migration, 'UNIQUE KEY uq_randevu_katilim_tokenlari_hash')
    && str_contains($migration, 'ON DELETE CASCADE'),
    'Kalici token tablosunda token tekilligi ve randevu yasam dongusu korunuyor'
);
$assert(
    str_contains($kurtarma, 'sms_kayitlari')
    && str_contains($kurtarma, "hash('sha256', \$token)"),
    'Daha once gonderilmis SMS linkleri icin kurtarma adimi mevcut'
);

fwrite(STDOUT, "Suresiz randevu katilim token smoke testleri tamamlandi.\n");
