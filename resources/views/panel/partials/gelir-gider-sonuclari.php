<?php
$analiz = $analiz ?? [];
$gorunum = (string) ($analiz['gorunum'] ?? 'aylik');
$donemler = $analiz['donemler'] ?? [];
$sonDonem = $analiz['son_donem'] ?? [];
$durum = (string) ($analiz['durum'] ?? 'basabas');
$durumEtiketi = $durum === 'kar' ? 'Kâr' : ($durum === 'zarar' ? 'Zarar' : 'Başabaş');
$netBaslik = $durum === 'zarar' ? 'Net Zarar' : ($durum === 'kar' ? 'Net Kâr' : 'Net Sonuç');
$netTutar = abs((float) ($analiz['net'] ?? 0));
$donemSayisi = max(1, count($donemler));
$grafikMinGenislik = max(720, $donemSayisi * 78);
?>

<section class="report-grid report-summary finance-profit-summary">
    <article class="report-card accent-blue">
        <span>Toplam Gelir</span>
        <strong><?= e(para_goster($analiz['toplam_gelir'] ?? 0)) ?></strong>
        <small>İptal edilmemiş tahsilatlar</small>
    </article>
    <article class="report-card accent-orange">
        <span>Toplam Gider</span>
        <strong><?= e(para_goster($analiz['toplam_gider'] ?? 0)) ?></strong>
        <small>Ödendi durumundaki giderler</small>
    </article>
    <article class="report-card finance-result-card is-<?= e($durum) ?>">
        <span><?= e($netBaslik) ?></span>
        <strong><?= e(para_goster($netTutar)) ?></strong>
        <small><?= e($durumEtiketi) ?> durumu</small>
    </article>
    <article class="report-card accent-purple">
        <span>Kâr Marjı</span>
        <strong><?= $analiz['kar_marji'] !== null ? e(number_format((float) $analiz['kar_marji'], 1, ',', '.')) . '%' : '-' ?></strong>
        <small><?= e((string) ($analiz['karli_donem'] ?? 0)) ?> kârlı, <?= e((string) ($analiz['zarar_donem'] ?? 0)) ?> zararlı dönem</small>
    </article>
</section>

<section class="panel-card finance-chart-card">
    <div class="definition-head">
        <div>
            <h2><?= $gorunum === 'aylik' ? 'Aylık' : 'Haftalık' ?> Gelir–Gider Grafiği</h2>
            <p class="report-helper">Son dönem <?= e($sonDonem['etiket'] ?? '-') ?>: <strong class="finance-net-text is-<?= e($sonDonem['durum'] ?? 'basabas') ?>"><?= e(para_goster(abs((float) ($sonDonem['net'] ?? 0)))) ?> <?= ($sonDonem['durum'] ?? '') === 'zarar' ? 'zarar' : (($sonDonem['durum'] ?? '') === 'kar' ? 'kâr' : 'başabaş') ?></strong></p>
        </div>
        <div class="finance-chart-legend">
            <span><i class="is-income"></i> Gelir</span>
            <span><i class="is-expense"></i> Gider</span>
        </div>
    </div>

    <div class="finance-chart-scroll">
        <div class="finance-chart" role="img" aria-label="Dönemlere göre gelir ve gider sütun grafiği" style="--finance-period-count: <?= e((string) $donemSayisi) ?>; --finance-chart-min-width: <?= e((string) $grafikMinGenislik) ?>px;">
            <?php foreach ($donemler as $donem) : ?>
                <div class="finance-chart-column">
                    <div class="finance-chart-bars">
                        <i class="finance-chart-bar is-income" style="height: <?= e((string) max(0, (float) ($donem['gelir_yuzde'] ?? 0))) ?>%" title="Gelir: <?= e(para_goster($donem['gelir'] ?? 0)) ?>"></i>
                        <i class="finance-chart-bar is-expense" style="height: <?= e((string) max(0, (float) ($donem['gider_yuzde'] ?? 0))) ?>%" title="Gider: <?= e(para_goster($donem['gider'] ?? 0)) ?>"></i>
                    </div>
                    <strong><?= e($donem['etiket']) ?></strong>
                    <small class="is-<?= e($donem['durum']) ?>"><?= e(para_goster(abs((float) $donem['net']))) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="panel-card finance-detail-card">
    <div class="definition-head">
        <div>
            <h2>Dönemsel Kâr/Zarar Tablosu</h2>
            <p class="report-helper">Tutarlar gerçekleşen nakit hareketlerini gösterir; planlanmış ancak ödenmemiş giderler dahil değildir.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="finance-detail-table">
            <thead><tr><th>Dönem</th><th>Tarih Aralığı</th><th>Gelir</th><th>Gider</th><th>Net Sonuç</th><th>Kâr Marjı</th><th>Durum</th></tr></thead>
            <tbody>
                <?php foreach (array_reverse($donemler) as $donem) : ?>
                    <?php $satirDurum = (string) $donem['durum']; ?>
                    <tr>
                        <td><strong><?= e($donem['etiket']) ?></strong></td>
                        <td><?= e(tarih_goster($donem['baslangic'])) ?> - <?= e(tarih_goster($donem['bitis'])) ?></td>
                        <td><?= e(para_goster($donem['gelir'])) ?></td>
                        <td><?= e(para_goster($donem['gider'])) ?></td>
                        <td class="finance-net-text is-<?= e($satirDurum) ?>"><?= $satirDurum === 'zarar' ? '-' : '' ?><?= e(para_goster(abs((float) $donem['net']))) ?></td>
                        <td><?= $donem['kar_marji'] !== null ? e(number_format((float) $donem['kar_marji'], 1, ',', '.')) . '%' : '-' ?></td>
                        <td><span class="finance-status is-<?= e($satirDurum) ?>"><?= $satirDurum === 'kar' ? 'Kâr' : ($satirDurum === 'zarar' ? 'Zarar' : 'Başabaş') ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2">Gösterilen Dönem Toplamı</th>
                    <th><?= e(para_goster($analiz['toplam_gelir'] ?? 0)) ?></th>
                    <th><?= e(para_goster($analiz['toplam_gider'] ?? 0)) ?></th>
                    <th class="finance-net-text is-<?= e($durum) ?>"><?= $durum === 'zarar' ? '-' : '' ?><?= e(para_goster($netTutar)) ?></th>
                    <th><?= $analiz['kar_marji'] !== null ? e(number_format((float) $analiz['kar_marji'], 1, ',', '.')) . '%' : '-' ?></th>
                    <th><span class="finance-status is-<?= e($durum) ?>"><?= e($durumEtiketi) ?></span></th>
                </tr>
            </tfoot>
        </table>
    </div>
</section>
