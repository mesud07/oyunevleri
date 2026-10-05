<?php
$yasGruplari = $yasGruplari ?? [];
$tablolarHazir = (bool) ($tablolarHazir ?? false);
$baslangic = $baslangic ?? date('Y-m-d', strtotime('-30 days'));
$bitis = $bitis ?? date('Y-m-d');
$katilim = $katilim ?? 'tumu';
$temaTakibi = $temaTakibi ?? [];
$takipOzeti = $takipOzeti ?? [];
$durumEtiketleri = [
    'planlandi' => 'Planlandı',
    'geldi' => 'Geldi',
    'gelmedi' => 'Gelmedi',
    'mazeretli_gelmedi' => 'Mazeretli Gelmedi',
    'gec_iptal' => 'Geç İptal',
    'kurum_iptali' => 'Kurum İptali',
    'ertelendi' => 'Ertelendi',
    'tamamlandi' => 'Tamamlandı',
];
$durumSinifi = static function (string $durum): string {
    if (in_array($durum, ['geldi', 'tamamlandi'], true)) {
        return 'is-success';
    }
    if (in_array($durum, ['gelmedi', 'mazeretli_gelmedi', 'gec_iptal', 'kurum_iptali'], true)) {
        return 'is-danger';
    }
    return 'is-planned';
};
$temaDurumu = [
    'islendi' => ['İşlendi', 'is-success'],
    'islenmedi' => ['İşlenmedi', 'is-danger'],
    'bekliyor' => ['Bekliyor', 'is-planned'],
];
?>

<section class="page-head">
    <div>
        <h1>Haftalık Temalar</h1>
        <p>Temaları tarih ve yaş grubuyla tanımlayın; öğrencilerin işlediği temaları randevu katılımından takip edin.</p>
    </div>
    <div class="head-actions">
        <button class="btn btn-primary" type="button" data-theme-new>Yeni Tema</button>
    </div>
</section>

<section
    class="panel-card report-panel"
    data-theme-page
    data-age-groups='<?= e(json_encode($yasGruplari, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
>
    <?php if (!$tablolarHazir) : ?>
        <div class="info-box compact-info">
            <strong>Migration gerekli</strong>
            <p>Haftalık tema tabloları henüz veritabanında yok. Migration çalıştırıldıktan sonra kayıt eklenebilir.</p>
        </div>
    <?php endif; ?>

    <div class="definition-head">
        <div>
            <h2>Tema Takvimi</h2>
            <p>Tema adı, uygulanacağı tarih aralığı ve yaş grubu yeterlidir.</p>
        </div>
    </div>
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
                <label class="dialog-wide"><span>Tema Adı</span><input name="title" required></label>
                <label><span>Başlangıç</span><input type="date" name="week_start" required></label>
                <label><span>Bitiş</span><input type="date" name="week_end" required></label>
                <label class="dialog-wide"><span>Açıklama</span><textarea name="description" rows="3"></textarea></label>
            </div>

            <section class="theme-form-section">
                <div class="appointment-toolbar compact-toolbar">
                    <div><h3>Yaş Grupları</h3></div>
                    <div class="appointment-toolbar-actions">
                        <button class="btn btn-ghost" type="button" data-theme-age-all>Tümünü Seç</button>
                        <button class="btn btn-ghost" type="button" data-theme-age-clear>Seçimi Temizle</button>
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

            <div class="record-actions compact-actions">
                <span data-theme-form-message></span>
                <button class="btn btn-ghost" type="button" data-theme-dialog-close>Vazgeç</button>
                <button class="btn btn-primary" type="submit">Kaydet</button>
            </div>
        </form>
    </dialog>
</section>

<section class="panel-card report-panel theme-attendance-panel">
    <div class="definition-head">
        <div>
            <h2>Öğrencilerin İşlediği Temalar</h2>
            <p>Randevu tarihi tema aralığına ve öğrencinin yaş grubuna uyuyorsa, katılım durumuna göre otomatik hesaplanır.</p>
        </div>
    </div>

    <form class="theme-attendance-filter" method="get">
        <label><span>Başlangıç</span><input type="date" name="baslangic" value="<?= e($baslangic) ?>" required></label>
        <label><span>Bitiş</span><input type="date" name="bitis" value="<?= e($bitis) ?>" required></label>
        <label>
            <span>Katılım</span>
            <select name="katilim">
                <option value="tumu" <?= $katilim === 'tumu' ? 'selected' : '' ?>>Tümü</option>
                <option value="geldi" <?= $katilim === 'geldi' ? 'selected' : '' ?>>Geldi / Tamamlandı</option>
                <option value="gelmedi" <?= $katilim === 'gelmedi' ? 'selected' : '' ?>>Gelmedi</option>
                <option value="bekliyor" <?= $katilim === 'bekliyor' ? 'selected' : '' ?>>Planlandı / Bekliyor</option>
                <option value="iptal" <?= $katilim === 'iptal' ? 'selected' : '' ?>>Kurum İptali</option>
            </select>
        </label>
        <button class="btn btn-primary" type="submit">Listele</button>
    </form>

    <div class="theme-attendance-summary">
        <article><span>Öğrenci</span><strong><?= e((string) ($takipOzeti['ogrenci'] ?? 0)) ?></strong></article>
        <article><span>İşlendi</span><strong><?= e((string) ($takipOzeti['islendi'] ?? 0)) ?></strong></article>
        <article><span>İşlenmedi</span><strong><?= e((string) ($takipOzeti['islenmedi'] ?? 0)) ?></strong></article>
        <article><span>Bekliyor</span><strong><?= e((string) ($takipOzeti['bekliyor'] ?? 0)) ?></strong></article>
    </div>

    <div class="table-wrap theme-attendance-table">
        <table>
            <thead>
                <tr>
                    <th>Öğrenci</th>
                    <th>Tema</th>
                    <th>Tema Dönemi</th>
                    <th>Randevu</th>
                    <th>Katılım</th>
                    <th>Sonuç</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$temaTakibi) : ?>
                    <tr><td colspan="6" class="empty-table">Seçilen aralıkta tema ile eşleşen öğrenci randevusu bulunamadı.</td></tr>
                <?php endif; ?>
                <?php foreach ($temaTakibi as $kayit) : ?>
                    <?php $sonuc = $temaDurumu[$kayit['tema_durumu'] ?? 'bekliyor'] ?? $temaDurumu['bekliyor']; ?>
                    <tr>
                        <td><a class="theme-student-link" href="/panel/ogrenciler/profil?id=<?= e((string) ($kayit['ogrenci_id'] ?? '')) ?>"><?= e($kayit['ogrenci'] ?? '-') ?></a></td>
                        <td><strong><?= e($kayit['tema'] ?? '-') ?></strong><small><?= e($kayit['age_groups'] ?? 'Tüm yaş grupları') ?></small></td>
                        <td><?= e(tarih_goster($kayit['week_start'] ?? null)) ?> - <?= e(tarih_goster($kayit['week_end'] ?? null)) ?></td>
                        <td>
                            <strong><?= e(tarih_goster($kayit['tarih'] ?? null)) ?>, <?= e(substr((string) ($kayit['baslangic_saati'] ?? ''), 0, 5)) ?></strong>
                            <small><?= e($kayit['grup'] ?? '-') ?></small>
                        </td>
                        <td><span class="status-pill <?= e($durumSinifi((string) ($kayit['durum'] ?? ''))) ?>"><?= e($durumEtiketleri[$kayit['durum'] ?? ''] ?? ($kayit['durum'] ?? '-')) ?></span></td>
                        <td><span class="status-pill <?= e($sonuc[1]) ?>"><?= e($sonuc[0]) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
