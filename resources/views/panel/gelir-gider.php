<?php
$analiz = $analiz ?? [];
$gorunum = (string) ($analiz['gorunum'] ?? 'aylik');
?>

<section class="page-head finance-analysis-head">
    <div>
        <h1>Gelir Gider Analizi</h1>
        <p>Gerçekleşen tahsilatlar ve ödenmiş giderler üzerinden kâr/zarar takibi.</p>
    </div>
    <button class="btn btn-ghost" type="button" onclick="window.print()">Yazdır</button>
</section>

<section class="panel-card finance-analysis-filter" data-finance-analysis-filter>
    <div class="finance-view-switch" aria-label="Rapor görünümü">
        <button class="btn <?= $gorunum === 'aylik' ? 'btn-primary' : 'btn-ghost' ?>" type="button" data-finance-view="aylik">Aylık</button>
        <button class="btn <?= $gorunum === 'haftalik' ? 'btn-primary' : 'btn-ghost' ?>" type="button" data-finance-view="haftalik">Haftalık</button>
    </div>
    <form method="get" action="/panel/finans/gelir-gider" data-finance-filter-form>
        <input type="hidden" name="gorunum" value="<?= e($gorunum) ?>" data-finance-view-input>
        <label>
            <span>Başlangıç</span>
            <input type="date" name="baslangic" value="<?= e($analiz['baslangic_tarihi'] ?? date('Y-m-d')) ?>">
        </label>
        <label>
            <span>Bitiş</span>
            <input type="date" name="bitis" value="<?= e($analiz['bitis_tarihi'] ?? date('Y-m-d')) ?>">
        </label>
        <button class="btn btn-primary" type="submit">Uygula</button>
    </form>
    <p data-finance-filter-message><?= e(tarih_goster($analiz['baslangic_tarihi'] ?? '')) ?> - <?= e(tarih_goster($analiz['bitis_tarihi'] ?? '')) ?> aralığı gösteriliyor.</p>
</section>

<div class="finance-analysis-results" data-finance-analysis-results>
    <?php require BASE_PATH . '/resources/views/panel/partials/gelir-gider-sonuclari.php'; ?>
</div>
