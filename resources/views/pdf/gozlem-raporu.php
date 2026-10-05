<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 52px 48px 48px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #263548; font-family: "DejaVu Sans", sans-serif; font-size: 10.5pt; line-height: 1.55; }
        .top-line { height: 7px; margin: -52px -48px 30px; background: #38aaf5; }
        .header { display: table; width: 100%; margin-bottom: 24px; }
        .brand, .report-meta { display: table-cell; vertical-align: middle; }
        .brand { width: 55%; }
        .mark { display: inline-block; width: 38px; height: 38px; margin-right: 9px; border-radius: 11px; background: #38aaf5; color: #fff; font-size: 21px; font-weight: bold; line-height: 38px; text-align: center; vertical-align: middle; }
        .brand-copy { display: inline-block; vertical-align: middle; }
        .brand-copy strong { display: block; color: #1688d6; font-size: 15pt; }
        .brand-copy span { display: block; color: #718096; font-size: 8.5pt; letter-spacing: .5px; }
        .report-meta { color: #607085; font-size: 8.5pt; text-align: right; }
        .hero { margin-bottom: 23px; padding: 22px 24px; border: 1px solid #dcebf5; border-radius: 16px; background: #f5fbff; }
        h1 { margin: 0 0 12px; color: #213044; font-size: 19pt; line-height: 1.25; }
        .meta-pill { display: inline-block; margin: 3px 7px 0 0; padding: 5px 9px; border-radius: 12px; background: #fff; color: #52677f; font-size: 8.5pt; }
        .section { page-break-inside: avoid; margin: 0 0 17px; padding: 0 0 16px 17px; border-bottom: 1px solid #e5eef5; border-left: 4px solid #8ed7c6; }
        .section:nth-child(odd) { border-left-color: #7ec8f5; }
        .section h2 { margin: 0 0 7px; color: #247da9; font-size: 12.5pt; }
        .section p { margin: 0 0 8px; white-space: pre-line; }
        .closing { margin-top: 24px; padding: 16px 18px; border-radius: 13px; background: #fff7ec; color: #725b39; font-size: 9pt; }
        .signature { margin-top: 22px; color: #1688d6; font-weight: bold; font-size: 11pt; text-align: right; }
    </style>
</head>
<body>
    <div class="top-line"></div>
    <div class="header">
        <div class="brand">
            <span class="mark">T</span>
            <span class="brand-copy"><strong>Oyun Evleri</strong><span>GELİŞİM VE OYUN EVİ</span></span>
        </div>
        <div class="report-meta">Oluşturma tarihi<br><strong><?= e(date('d.m.Y')) ?></strong></div>
    </div>

    <div class="hero">
        <h1><?= e((string) ($rapor['ogrenci_adi'] ?? 'Öğrenci')) ?> - Oyun Grubu Gözlem Raporu</h1>
        <span class="meta-pill"><strong>Gözlem Dönemi:</strong> <?= e(tarih_goster($rapor['baslangic'] ?? '')) ?> - <?= e(tarih_goster($rapor['bitis'] ?? '')) ?></span>
        <span class="meta-pill"><strong>Yaş Grubu:</strong> <?= e((string) ($rapor['yas_grubu'] ?? '-')) ?></span>
        <span class="meta-pill"><strong>Gözlem:</strong> <?= e((string) ($rapor['not_sayisi'] ?? 0)) ?> günlük not</span>
    </div>

    <?php foreach (($rapor['bolum_basliklari'] ?? []) as $anahtar => $baslik) : ?>
        <section class="section">
            <h2><?= e($baslik) ?></h2>
            <?php foreach (preg_split('/\n\s*\n/u', trim((string) ($rapor['bolumler'][$anahtar] ?? ''))) ?: [] as $paragraf) : ?>
                <p><?= nl2br(e(trim($paragraf))) ?></p>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>

    <div class="closing">Bu rapor, belirtilen dönemde kaydedilen sınıf içi gözlemlerin eğitsel bir özetidir. Tanı veya klinik değerlendirme niteliğinde değildir.</div>
    <div class="signature">Oyun Evleri</div>
</body>
</html>
