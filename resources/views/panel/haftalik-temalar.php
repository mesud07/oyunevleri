<?php
$yasGruplari = $yasGruplari ?? [];
$gruplar = $gruplar ?? [];
$tablolarHazir = (bool) ($tablolarHazir ?? false);
?>

<section class="page-head">
    <div>
        <h1>Haftalik Temalar</h1>
        <p>Haftalara gore tema ve etkinlikleri yonetin.</p>
    </div>
    <button class="btn btn-primary" type="button" data-theme-new>Yeni Tema</button>
</section>

<section
    class="panel-card report-panel"
    data-theme-page
    data-age-groups='<?= e(json_encode($yasGruplari, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
    data-groups='<?= e(json_encode($gruplar, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
>
    <?php if (!$tablolarHazir) : ?>
        <div class="info-box compact-info">
            <strong>Migration gerekli</strong>
            <p>Haftalik tema tablolari henuz veritabaninda yok. Migration calistirildikten sonra kayit eklenebilir.</p>
        </div>
    <?php endif; ?>

    <div class="table-wrap fast-table-wrap" data-theme-table></div>
    <p class="form-message" data-theme-message></p>

    <dialog class="appointment-dialog theme-dialog" data-theme-dialog>
        <form method="dialog" class="appointment-dialog-form theme-form" data-theme-form>
            <div class="dialog-head">
                <h2 data-theme-form-title>Yeni Tema</h2>
                <button type="button" data-theme-dialog-close>x</button>
            </div>
            <input type="hidden" name="id">
            <div class="dialog-grid">
                <label class="dialog-wide"><span>Tema Adi</span><input name="title" required></label>
                <label><span>Baslangic</span><input type="date" name="week_start" required></label>
                <label><span>Bitis</span><input type="date" name="week_end" required></label>
                <label class="dialog-wide"><span>Aciklama</span><textarea name="description" rows="3"></textarea></label>
            </div>

            <section class="theme-form-section">
                <div class="appointment-toolbar compact-toolbar">
                    <div>
                        <h3>Yas Gruplari</h3>
                    </div>
                    <div class="appointment-toolbar-actions">
                        <button class="btn btn-ghost" type="button" data-theme-age-all>Tumunu Sec</button>
                        <button class="btn btn-ghost" type="button" data-theme-age-clear>Secimi Temizle</button>
                    </div>
                </div>
                <div class="check-list theme-age-list">
                    <?php foreach ($yasGruplari as $yasGrubu) : ?>
                        <label>
                            <input type="checkbox" name="age_group_ids[]" value="<?= e($yasGrubu['id']) ?>">
                            <?= e($yasGrubu['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="theme-form-section" data-theme-activities-section hidden>
                <div class="appointment-toolbar compact-toolbar">
                    <div>
                        <h3>Etkinlikler</h3>
                    </div>
                    <button class="btn btn-ghost" type="button" data-theme-activity-add>Etkinlik Ekle</button>
                </div>
                <div class="theme-activity-list" data-theme-activity-list></div>
            </section>

            <div class="record-actions compact-actions">
                <span data-theme-form-message></span>
                <button class="btn btn-ghost" type="button" data-theme-dialog-close>Vazgec</button>
                <button class="btn btn-primary" type="submit">Kaydet</button>
            </div>
        </form>
    </dialog>
</section>
