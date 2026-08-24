<?php
$ogrenci = $profil['ogrenci'] ?? [];
$temaSecenekleri = $profil['tema_secenekleri'] ?? [];
$etkinlikGecmisi = $profil['etkinlik_gecmisi'] ?? [];
$adSoyad = trim(($ogrenci['ad'] ?? '') . ' ' . ($ogrenci['soyad'] ?? ''));
$haftaAraligiGoster = static function (?string $baslangic, ?string $bitis): string {
    if (!$baslangic || !$bitis) {
        return '-';
    }
    return tarih_goster($baslangic) . ' - ' . tarih_goster($bitis);
};
?>

<section class="page-head">
    <div>
        <h1><?= e($adSoyad) ?></h1>
        <p>Tema ve Etkinlik Gecmisi</p>
    </div>
    <a class="btn btn-ghost" href="/panel/ogrenciler/profil?id=<?= e($ogrenci['id'] ?? '') ?>">Profile Don</a>
</section>

<section
    class="panel-card report-panel"
    data-student-theme-records
    data-student-id="<?= e($ogrenci['id'] ?? '') ?>"
    data-themes='<?= e(json_encode($temaSecenekleri, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
>
    <div class="appointment-toolbar">
        <div>
            <h2>Tema ve Etkinlikler</h2>
            <p>Ogrencinin yaptigi tema etkinliklerini kaydedin ve takip edin.</p>
        </div>
    </div>

    <?php if (yetki_var('tema_yonet')) : ?>
        <form class="theme-record-form" data-student-theme-form>
            <div class="dialog-grid">
                <label>
                    <span>Tema</span>
                    <select name="theme_id" data-student-theme-select required>
                        <option value="">Tema seciniz</option>
                        <?php foreach ($temaSecenekleri as $tema) : ?>
                            <option value="<?= e($tema['id']) ?>"><?= e($tema['title']) ?> / <?= e($haftaAraligiGoster($tema['week_start'] ?? null, $tema['week_end'] ?? null)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Etkinlik</span>
                    <select name="activity_id" data-student-activity-select required disabled>
                        <option value="">Once tema seciniz</option>
                    </select>
                </label>
                <label>
                    <span>Tamamlanma Tarihi</span>
                    <input type="date" name="completed_at" value="<?= e(date('Y-m-d')) ?>" required>
                </label>
                <div class="theme-record-submit">
                    <button class="btn btn-primary" type="submit">Yapti Olarak Ekle</button>
                </div>
            </div>
            <p class="form-message" data-student-theme-message></p>
        </form>
    <?php endif; ?>

    <div class="table-wrap theme-history-table">
        <table>
            <thead>
                <tr>
                    <th>Hafta</th>
                    <th>Tema</th>
                    <th>Etkinlik</th>
                    <th>Yas Gruplari</th>
                    <th>Tamamlanma Tarihi</th>
                    <th>Sil</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$etkinlikGecmisi) : ?>
                    <tr><td colspan="6">Etkinlik gecmisi bulunamadi.</td></tr>
                <?php endif; ?>
                <?php foreach ($etkinlikGecmisi as $kayit) : ?>
                    <tr>
                        <td><?= e($haftaAraligiGoster($kayit['week_start'] ?? null, $kayit['week_end'] ?? null)) ?></td>
                        <td><?= e($kayit['theme_title'] ?? '-') ?></td>
                        <td><?= e($kayit['activity_title'] ?? '-') ?></td>
                        <td><?= e($kayit['age_groups'] ?? '-') ?></td>
                        <td><?= e(tarih_goster($kayit['completed_at'] ?? null)) ?></td>
                        <td>
                            <?php if (yetki_var('tema_yonet')) : ?>
                                <button class="btn btn-danger" type="button" data-student-theme-delete="<?= e($kayit['id']) ?>">Sil</button>
                            <?php else : ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
