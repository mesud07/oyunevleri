<?php
$kayitlar = $kayitlar ?? [];
$ozet = $ozet ?? [];
$baslangic = $baslangic ?? date('Y-m-d');
$bitis = $bitis ?? $baslangic;
$bugun = date('Y-m-d');
$dun = date('Y-m-d', strtotime('-1 day'));
$haftaBaslangic = date('Y-m-d', strtotime('monday this week'));
$haftaBitis = date('Y-m-d', strtotime('sunday this week'));
$tarihSaatYaz = static function ($tarih): string {
    if (!$tarih) {
        return '-';
    }

    $ts = strtotime((string) $tarih);
    return $ts ? date('d.m.Y H:i', $ts) : (string) $tarih;
};
$saatYaz = static fn($saat): string => $saat ? substr((string) $saat, 0, 5) : '-';
?>
<section class="page-head">
    <div>
        <h1>Gunluk Notlar</h1>
        <p>Randevular uzerinden girilen davranis, gozlem ve gunluk takip notlari.</p>
    </div>
    <form class="head-actions daily-record-filter" method="get">
        <a class="btn btn-ghost" href="/panel/gunluk-kayitlar?baslangic=<?= e($bugun) ?>&bitis=<?= e($bugun) ?>">Bugun</a>
        <a class="btn btn-ghost" href="/panel/gunluk-kayitlar?baslangic=<?= e($dun) ?>&bitis=<?= e($dun) ?>">Dun</a>
        <a class="btn btn-ghost" href="/panel/gunluk-kayitlar?baslangic=<?= e($haftaBaslangic) ?>&bitis=<?= e($haftaBitis) ?>">Bu Hafta</a>
        <label>
            Baslangic
            <input type="date" name="baslangic" value="<?= e($baslangic) ?>">
        </label>
        <label>
            Bitis
            <input type="date" name="bitis" value="<?= e($bitis) ?>">
        </label>
        <button class="btn btn-primary" type="submit">Uygula</button>
    </form>
</section>

<section class="report-grid report-summary daily-record-summary">
    <article class="report-card accent-blue"><span>Not</span><strong><?= e((string) ($ozet['not_sayisi'] ?? 0)) ?></strong></article>
    <article class="report-card accent-green"><span>Ogrenci</span><strong><?= e((string) ($ozet['ogrenci_sayisi'] ?? 0)) ?></strong></article>
    <article class="report-card accent-purple"><span>Kategori</span><strong><?= e((string) ($ozet['kategori_sayisi'] ?? 0)) ?></strong></article>
    <article class="report-card accent-teal">
        <span>Tarih</span>
        <strong><?= e(tarih_goster($baslangic)) ?><?= $bitis !== $baslangic ? ' - ' . e(tarih_goster($bitis)) : '' ?></strong>
    </article>
</section>

<section class="panel-card report-panel">
    <div class="definition-head">
        <div>
            <h2>Not Akisi</h2>
            <p>Secilen tarih araliginda eklenen tum gunluk notlar.</p>
        </div>
        <span class="payment-total-pill is-total"><?= e((string) count($kayitlar)) ?> not</span>
    </div>

    <div class="daily-note-list">
        <?php if (empty($kayitlar)) : ?>
            <div class="empty-state">Secilen tarih araliginda gunluk not bulunamadi.</div>
        <?php endif; ?>
        <?php foreach ($kayitlar as $row) : ?>
            <article class="daily-note-card">
                <div class="daily-note-card-head">
                    <div>
                        <strong><?= e(tarih_goster($row['tarih'] ?? null)) ?> <?= e($saatYaz($row['baslangic_saati'] ?? null)) ?></strong>
                        <a href="/panel/ogrenciler/profil?id=<?= e((string) ($row['ogrenci_id'] ?? '')) ?>"><?= e($row['ogrenci'] ?? '-') ?></a>
                    </div>
                    <span><?= e($row['kategori'] ?? 'Genel') ?></span>
                </div>
                <p><?= nl2br(e($row['not_metni'] ?? '')) ?></p>
                <footer>
                    <span><?= e($row['randevu_tanimi'] ?? '-') ?><?= !empty($row['grup']) ? ' / ' . e($row['grup']) : '' ?></span>
                    <span><?= e($row['kaydeden'] ?? '-') ?> - <?= e($tarihSaatYaz($row['olusturulma_tarihi'] ?? null)) ?></span>
                </footer>
            </article>
        <?php endforeach; ?>
    </div>
</section>
