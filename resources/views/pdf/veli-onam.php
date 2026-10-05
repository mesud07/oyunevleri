<?php
$ek = json_decode((string) ($kayit['form_verisi_json'] ?? '{}'), true);
$ek = is_array($ek) ? $ek : [];
$cevap = static fn(string $deger): string => match ($deger) {
    'evet', 'var' => 'Evet / Var',
    'hayir', 'yok' => 'Hayır / Yok',
    default => '-',
};
$izin = static fn(bool $izin): string => $izin ? 'İzin verildi' : 'İzin verilmedi';
$yas = '-';
if (!empty($kayit['ogrenci_dogum_tarihi'])) {
    try {
        $dogum = new DateTimeImmutable((string) $kayit['ogrenci_dogum_tarihi']);
        $yasFarki = $dogum->diff(new DateTimeImmutable((string) $kayit['onay_tarihi']));
        $yas = $yasFarki->y . ' yaş ' . $yasFarki->m . ' ay';
    } catch (Throwable $e) {
        $yas = '-';
    }
}
$resimData = static function (string $yol, string $mime): string {
    $icerik = is_file($yol) ? file_get_contents($yol) : false;
    return is_string($icerik) ? 'data:' . $mime . ';base64,' . base64_encode($icerik) : '';
};
$kurumLogo = $resimData(BASE_PATH . '/public/assets/images/oyun-evleri-logo.png', 'image/png');
$mebLogo = $resimData(BASE_PATH . '/public/assets/images/meb_logo_pdf.jpg', 'image/jpeg');
$alan = static function (string $etiket, mixed $deger, int $sinir = 220): string {
    $metin = trim(preg_replace('/\s+/u', ' ', (string) $deger) ?? (string) $deger);
    if (mb_strlen($metin) > $sinir) {
        $metin = rtrim(mb_substr($metin, 0, $sinir - 31)) . '... (devamı dijital kayıtta)';
    }
    return '<span class="field-label">' . e($etiket) . '</span><span class="field-value">' . e($metin !== '' ? $metin : '-') . '</span>';
};
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px 30px 34px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #243247; font-family: "DejaVu Sans", sans-serif; font-size: 8.4pt; line-height: 1.28; }
        .page-footer { position: fixed; right: 0; bottom: -22px; left: 0; border-top: 1px solid #cfe1ec; color: #287da7; font-size: 8pt; font-weight: bold; padding-top: 6px; text-align: center; }
        .brand-header { width: 100%; border-collapse: collapse; border-bottom: 3px solid #38aaf5; margin-bottom: 9px; table-layout: fixed; }
        .brand-header td { padding: 0 7px 7px; text-align: center; vertical-align: middle; }
        .brand-side { width: 24%; }
        .brand-center { width: 52%; }
        .talya-logo { display: block; height: 62px; margin: 0 auto 2px; width: 62px; }
        .meb-logo { display: block; height: 64px; margin: 0 auto; width: auto; }
        .institution-name { color: #253a4b; font-size: 6.8pt; font-weight: bold; line-height: 1.15; }
        h1 { color: #1e3448; font-size: 14.5pt; line-height: 1.18; margin: 0 0 4px; text-align: center; }
        .form-version { color: #718092; font-size: 7.3pt; margin: 0; text-align: center; }
        h2 { background: #eaf7fd; border-left: 4px solid #38aaf5; color: #247da9; font-size: 10pt; margin: 9px 0 5px; padding: 5px 8px; page-break-after: avoid; }
        .info-grid { border-collapse: separate; border-spacing: 5px 4px; margin: 0 -5px; table-layout: fixed; width: calc(100% + 10px); }
        .info-grid td { background: #f8fbfd; border: 1px solid #dce9f1; border-radius: 5px; min-height: 30px; padding: 5px 7px; vertical-align: top; width: 50%; }
        .field-label { color: #63758a; display: block; font-size: 7pt; font-weight: bold; margin-bottom: 2px; }
        .field-value { color: #1f2f42; display: block; font-size: 8.2pt; overflow-wrap: anywhere; }
        .permission-cell { background: #f1faf7 !important; border-color: #cbe7dc !important; }
        .permission-cell .field-value { color: #14765f; font-weight: bold; }
        .record-note { color: #748396; font-size: 6.8pt; margin: 4px 0 0; text-align: right; }
        .consent-page { page-break-before: always; }
        .compact-header { width: 100%; border-bottom: 2px solid #38aaf5; margin-bottom: 8px; padding-bottom: 6px; }
        .compact-header td { vertical-align: middle; }
        .compact-header img { height: 34px; width: auto; }
        .compact-header .right { color: #617187; font-size: 7.5pt; text-align: right; }
        .consent { background: #f8fbfd; border-left: 4px solid #38aaf5; font-size: 8.1pt; line-height: 1.32; margin-top: 5px; padding: 10px 12px; white-space: pre-line; }
        .approval { background: #f1faf7; border: 1px solid #bfe1d4; margin-top: 10px; padding: 9px 11px; page-break-inside: avoid; }
        .approval strong { color: #14765f; }
        .signature { border: 2px solid #38aaf5; color: #1d6f94; font-size: 11pt; font-weight: bold; margin-top: 12px; padding: 9px; text-align: center; }
        .audit { color: #6d7c8e; font-size: 7.4pt; line-height: 1.42; margin-top: 8px; text-align: center; }
    </style>
</head>
<body>
    <div class="page-footer">Dijital olarak imzalanmıştır</div>

    <table class="brand-header">
        <tr>
            <td class="brand-side">
                <?php if ($kurumLogo !== '') : ?><img class="talya-logo" src="<?= e($kurumLogo) ?>" alt="Oyun Evleri"><?php endif; ?>
                <div class="institution-name"><?= e($kayit['kurum_adi'] ?? 'Oyun Evleri') ?></div>
            </td>
            <td class="brand-center">
                <h1><?= e($kayit['baslik'] ?? 'Oyun Grubu Katılımcı Bilgi ve Veli Onam Formu') ?></h1>
                <p class="form-version">Form sürümü: <?= e((string) ($kayit['form_surumu'] ?? 1)) ?></p>
            </td>
            <td class="brand-side"><?php if ($mebLogo !== '') : ?><img class="meb-logo" src="<?= e($mebLogo) ?>" alt="Millî Eğitim Bakanlığı"><?php endif; ?></td>
        </tr>
    </table>

    <h2>Katılımcı ve Veli Bilgileri</h2>
    <table class="info-grid">
        <tr><td><?= $alan('Katılımcı', $kayit['ogrenci_ad_soyad'] ?? '-') ?></td><td><?= $alan('Doğum tarihi', !empty($kayit['ogrenci_dogum_tarihi']) ? tarih_goster($kayit['ogrenci_dogum_tarihi']) : '-') ?></td></tr>
        <tr><td><?= $alan('Yaşı', $yas) ?></td><td><?= $alan('Onay veren veli', $kayit['veli_ad_soyad'] ?? '-') ?></td></tr>
        <tr><td><?= $alan('Anne adı soyadı', $ek['anne_ad_soyad'] ?? '-') ?></td><td><?= $alan('Baba adı soyadı', $ek['baba_ad_soyad'] ?? '-') ?></td></tr>
        <tr><td><?= $alan('Cep telefonu', $kayit['veli_telefon'] ?? '-') ?></td><td><?= $alan('E-posta', $kayit['veli_eposta'] ?? '-') ?></td></tr>
        <tr><td><?= $alan('İl', ($ek['il'] ?? '') ?: 'Antalya') ?></td><td><?= $alan('İlçe', $ek['ilce'] ?? '-') ?></td></tr>
        <tr><td colspan="2"><?= $alan('Açık adres', $ek['ev_adresi'] ?? '-', 300) ?></td></tr>
    </table>

    <h2>Geçmiş Bilgileri</h2>
    <table class="info-grid"><tr>
        <td><?= $alan('Uzman desteği', $cevap((string) ($ek['uzman_destegi'] ?? '')) . (!empty($ek['uzman_aciklama']) ? ' - ' . $ek['uzman_aciklama'] : '')) ?></td>
        <td><?= $alan('Oyun grubu deneyimi', $cevap((string) ($ek['oyun_grubu_deneyimi'] ?? '')) . (!empty($ek['oyun_grubu_aciklama']) ? ' - ' . $ek['oyun_grubu_aciklama'] : '')) ?></td>
    </tr></table>

    <h2>Sağlık Bilgileri</h2>
    <table class="info-grid">
        <tr>
            <td><?= $alan('Tanı / gelişimsel farklılık', $cevap((string) ($ek['tani_durumu'] ?? '')) . (!empty($ek['tani_aciklama']) ? ' - ' . $ek['tani_aciklama'] : '')) ?></td>
            <td><?= $alan('Düzenli kullanılan ilaç', $cevap((string) ($ek['ilac_durumu'] ?? '')) . (!empty($ek['ilac_aciklama']) ? ' - ' . $ek['ilac_aciklama'] : '')) ?></td>
        </tr>
        <tr><td><?= $alan('Alerji durumu', $ek['alerji_durumu'] ?? '-') ?></td><td><?= $alan('Özel sağlık durumu', $ek['ozel_saglik_durumu'] ?? '-') ?></td></tr>
    </table>

    <h2>Görsel Kayıt İzinleri</h2>
    <table class="info-grid"><tr>
        <td class="permission-cell"><?= $alan('Fotoğraf / video çekimi', $izin(!empty($ek['gorsel_kayit_izni']))) ?></td>
        <td class="permission-cell"><?= $alan('Sosyal medya / paylaşım', $izin(!empty($ek['sosyal_medya_izni']))) ?></td>
    </tr></table>
    <p class="record-note">Uzun açıklamalar iki sayfalık çıktı için kısaltılabilir; tam içerik dijital kayıtta saklanır.</p>

    <section class="consent-page">
        <table class="compact-header"><tr>
            <td><?php if ($kurumLogo !== '') : ?><img src="<?= e($kurumLogo) ?>" alt="Oyun Evleri"><?php endif; ?></td>
            <td class="right"><?= e($kayit['kurum_adi'] ?? 'Oyun Evleri') ?><br><?= e($kayit['ogrenci_ad_soyad'] ?? '-') ?></td>
            <td class="right"><?php if ($mebLogo !== '') : ?><img src="<?= e($mebLogo) ?>" alt="MEB"><?php endif; ?></td>
        </tr></table>
        <h2>Program Kuralları ve Katılım Onamı</h2>
        <div class="consent"><?= e($kayit['onam_metni'] ?? '') ?></div>
        <div class="approval"><strong>Program kuralları kabul edilmiştir.<br>Dijital veli onayı alınmıştır.</strong><br>Veli, bilgilendirme ve katılım onamını okuduğunu, çocuğunun oyun grubu çalışmalarına katılmasını kabul ettiğini ve verdiği bilgilerin doğru olduğunu beyan etmiştir.</div>
        <div class="signature">Dijital olarak imzalanmıştır</div>
        <div class="audit">Onay tarihi: <?= e(date('d.m.Y H:i:s', strtotime((string) $kayit['onay_tarihi']))) ?><br>Kayıt no: <?= e((string) $kayit['id']) ?> · Veli eşleşmesi: <?= !empty($kayit['veli_id']) ? 'Mevcut veli kaydıyla eşleşti' : 'Yeni veli kaydı' ?></div>
    </section>
</body>
</html>
