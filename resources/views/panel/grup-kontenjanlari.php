<?php
$grupKontenjanlari = $grupKontenjanlari ?? [];
$gunAdlari = [
    1 => 'Pazartesi',
    2 => 'Sali',
    3 => 'Carsamba',
    4 => 'Persembe',
    5 => 'Cuma',
    6 => 'Cumartesi',
    7 => 'Pazar',
];
$saatGoster = static fn(?string $saat): string => $saat ? substr($saat, 0, 5) : '-';
?>

<section class="page-head">
    <div>
        <h1>Grup Kontenjanlari</h1>
        <p>Aktif gruplarin doluluk ve en erken musaitlik durumunu takip edin.</p>
    </div>
    <a class="btn btn-ghost" href="/panel/gruplar">Haftalik Program</a>
</section>

<section class="panel-card report-panel">
    <div class="appointment-toolbar">
        <div>
            <h2>Kontenjan Durumu</h2>
            <p class="report-helper">Dolu, sinirli ve musait gruplar.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Gun</th><th>Saat</th><th>Grup</th><th>Yas</th><th>Kontenjan</th><th>Durum</th><th>En Erken</th></tr></thead>
            <tbody>
                <?php if (!$grupKontenjanlari) : ?><tr><td colspan="7">Grup kontenjani bulunamadi.</td></tr><?php endif; ?>
                <?php foreach ($grupKontenjanlari as $row) : ?>
                    <tr>
                        <td><?= e($gunAdlari[(int) ($row['gun'] ?? 0)] ?? '-') ?></td>
                        <td><?= e($saatGoster($row['baslangic_saati'] ?? null)) ?></td>
                        <td><?= e($row['program_adi']) ?></td>
                        <td><?= e($row['yas_araligi'] ?: '-') ?></td>
                        <td><?= e((int) $row['ogrenci_sayisi']) ?> / <?= e((int) $row['kontenjan']) ?></td>
                        <td><span class="status-pill capacity-<?= e($row['kontenjan_durumu']) ?>"><?= e($row['kontenjan_durumu'] === 'dolu' ? 'Dolu' : ($row['kontenjan_durumu'] === 'sinirli' ? 'Sinirli' : 'Musait')) ?></span></td>
                        <td><?= e(($row['kontenjan_durumu'] === 'dolu' && !empty($row['en_erken_musait_tarih'])) ? tarih_goster($row['en_erken_musait_tarih']) : 'Bugun') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel-card report-panel group-fit-panel" data-group-fit-panel data-groups="<?= e(json_encode($grupKontenjanlari, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
    <div class="appointment-toolbar">
        <div>
            <h2>Yasa Gore Uygun Gruplar</h2>
            <p>Dogum tarihini girerek ogrencinin ay yasini ve uygun grup kontenjanlarini gorun.</p>
        </div>
        <label class="group-birthdate-field">
            <span>Dogum Tarihi</span>
            <input type="date" data-group-birthdate>
        </label>
    </div>
    <div class="group-fit-summary" data-group-age-summary>Dogum tarihi giriniz.</div>
    <div class="table-wrap" data-group-fit-results></div>
    <dialog class="appointment-dialog group-fit-dialog" data-group-fit-dialog>
        <div class="appointment-dialog-form">
            <div class="dialog-head">
                <h2 data-group-fit-dialog-title>Grup Ogrencileri</h2>
                <button type="button" data-group-fit-dialog-close>x</button>
            </div>
            <div data-group-fit-dialog-content></div>
            <div class="record-actions compact-actions">
                <button class="btn btn-ghost" type="button" data-group-fit-dialog-close>Kapat</button>
            </div>
        </div>
    </dialog>
</section>
