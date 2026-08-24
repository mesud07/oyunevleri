<?php
$rapor = $rapor ?? [];
$raporOzet = $rapor['ozet'] ?? [];
$yaklasanTahsilatlar = $rapor['yaklasan_tahsilatlar'] ?? [];
$gecikmisTahsilatlar = $rapor['gecikmis_tahsilatlar'] ?? [];
$borcluPaketler = $rapor['borclu_paketler'] ?? [];
$bugunSonDersler = $rapor['bugun_son_dersler'] ?? [];
$grupKontenjanlari = $rapor['grup_kontenjanlari'] ?? [];
$kayitYenilemeleri = $rapor['kayit_yenilemeleri'] ?? [];
$kayitYenilemeTakvimi = $rapor['kayit_yenileme_takvimi'] ?? [];
$dogumGunleri = $dogumGunleri ?? [];
$veliPortalAnahtari = $veliPortalAnahtari ?? '';
$veliPortalYolu = $veliPortalAnahtari !== '' ? '/veli-portal?k=' . rawurlencode($veliPortalAnahtari) : '';
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
$canStudents = yetki_var('ogrenci_listele');
$canGroups = yetki_var('grup_listele');
$canAppointments = yetki_var('randevu_listele');
$canCreateAppointments = yetki_var('randevu_ekle');
$canEditAppointments = yetki_var('randevu_ekle');
$canChangeAppointmentStatus = yetki_var('randevu_durum_degistir');
$canPayments = yetki_var('odeme_listele');
$canReports = yetki_var('rapor_ozet');
$canSendSms = yetki_var('sms_gonder');
?>

<section class="page-head">
    <div>
        <h1>Genel Bakis</h1>
        <p>Oyun Evleri operasyonunun günlük durumunu hızlı takip edin.</p>
    </div>
    <?php if ($canCreateAppointments) : ?>
        <div class="appointment-toolbar-actions">
            <button class="btn btn-ghost" type="button" data-open-dialog="#hizli-randevu-dialog">Hizli Randevu Olustur</button>
            <a class="btn btn-ghost" href="/panel/randevular/yeni">Toplu Randevu</a>
            <a class="btn btn-primary" href="/panel/paketler/tanimla">Randevu Olustur</a>
        </div>
    <?php endif; ?>
</section>

<?php if ($veliPortalYolu !== '') : ?>
<section class="dashboard-portal-info" data-parent-portal-info data-parent-portal-path="<?= e($veliPortalYolu) ?>">
    <div class="dashboard-portal-info-copy">
        <span>Veli Bilgi Ekranı</span>
        <strong>Velilerinizle paylaşacağınız kuruma özel bağlantı</strong>
        <p>Bu bağlantıyı velilere göndererek telefon numaralarıyla öğrenci bilgilerine güvenli biçimde ulaşmalarını sağlayabilirsiniz.</p>
    </div>
    <div class="dashboard-portal-link-row">
        <input type="text" readonly value="<?= e($veliPortalYolu) ?>" data-parent-portal-link aria-label="Veli bilgi ekranı bağlantısı">
        <button class="btn btn-sky" type="button" data-parent-portal-copy>Bağlantıyı Kopyala</button>
        <a class="btn btn-ghost" href="<?= e($veliPortalYolu) ?>" target="_blank" rel="noopener">Ekranı Aç</a>
    </div>
    <small class="dashboard-portal-message" data-parent-portal-message aria-live="polite"></small>
</section>
<?php endif; ?>

<section class="stats-grid dashboard-summary-grid">
    <?php if ($canStudents) : ?><article class="stat-card"><span>Aktif Ogrenci</span><strong><?= e($ozet['ogrenci'] ?? 0) ?></strong></article><?php endif; ?>
    <?php if ($canGroups) : ?><article class="stat-card"><span>Aktif Grup</span><strong><?= e($ozet['grup'] ?? 0) ?></strong></article><?php endif; ?>
    <?php if ($canAppointments) : ?><article class="stat-card"><span>Bugunku Randevu</span><strong><?= e($ozet['randevu'] ?? 0) ?></strong></article><?php endif; ?>
    <?php if ($canStudents) : ?>
        <article class="stat-card birthday-stat-card">
            <span>Bu Hafta Dogum Gunu</span>
            <strong><?= e($ozet['dogum_gunu'] ?? 0) ?></strong>
            <?php if ($dogumGunleri) : ?>
                <ul>
                    <?php foreach (array_slice($dogumGunleri, 0, 4) as $dogumGunu) : ?>
                        <li>
                            <a href="/panel/ogrenciler/profil?id=<?= e($dogumGunu['id']) ?>"><?= e($dogumGunu['ad_soyad']) ?></a>
                            <small><?= e($dogumGunu['dogum_gunu']) ?> / <?= e($dogumGunu['yas']) ?> yas</small>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if (count($dogumGunleri) > 4) : ?>
                    <em>+<?= e(count($dogumGunleri) - 4) ?> ogrenci daha</em>
                <?php endif; ?>
            <?php else : ?>
                <small>Bu hafta dogum gunu bulunmuyor.</small>
            <?php endif; ?>
        </article>
    <?php endif; ?>
</section>

<?php if ($canPayments || $canReports) : ?>
<section class="report-grid report-summary dashboard-finance-summary">
    <?php if ($canPayments || $canReports) : ?>
    <article class="report-card accent-blue">
        <span>Bu Ay Tahsilat</span>
        <strong><?= e(para_goster($raporOzet['bu_ay_tahsilat'] ?? 0)) ?></strong>
    </article>
    <article class="report-card accent-red">
        <span>Bekleyen Alacak</span>
        <strong><?= e(para_goster($raporOzet['bekleyen_alacak'] ?? 0)) ?></strong>
    </article>
    <article class="report-card accent-dark">
        <span>Yapilacak Odemeler</span>
        <strong><?= e(para_goster($raporOzet['yapilacak_odeme_30_gun'] ?? 0)) ?></strong>
    </article>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($canAppointments) : ?>
<section class="report-grid dashboard-last-lessons">
    <article class="panel-card report-panel">
        <h2>Bugun Son Dersi Olanlar</h2>
        <p class="report-helper">Bugun paketi biten ogrenciler ve olasi yenileme bilgileri.</p>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Ogrenci</th><th>Hizmet</th><th>Saat</th><th>Kalan Ders</th><th>Telafi</th><th>Yenileme</th><?php if ($canSendSms) : ?><th>Islem</th><?php endif; ?></tr></thead>
                <tbody>
                    <?php if (!$bugunSonDersler) : ?><tr><td colspan="<?= $canSendSms ? '7' : '6' ?>">Bugun son dersi olan ogrenci bulunamadi.</td></tr><?php endif; ?>
                    <?php foreach ($bugunSonDersler as $row) : ?>
                        <tr>
                            <td><?= e($row['ogrenci']) ?></td>
                            <td><?= e($row['paket_adi']) ?></td>
                            <td><?= e($saatGoster($row['son_ders_saati'] ?? null)) ?></td>
                            <td><?= e($row['kalan_normal_hak']) ?></td>
                            <td><?= e($row['kalan_telafi_hak']) ?></td>
                            <td><?= e(((int) ($row['kalan_normal_hak'] ?? 0) > 1) ? para_goster($row['yenileme_ucreti']) : 'Tek ders') ?></td>
                            <?php if ($canSendSms) : ?>
                                <td>
                                    <button
                                        class="btn btn-ghost"
                                        type="button"
                                        data-open-sms-compose
                                        data-student-id="<?= e($row['ogrenci_id'] ?? '') ?>"
                                        data-student-name="<?= e($row['ogrenci'] ?? '') ?>"
                                        data-parent-id="<?= e($row['veli_id'] ?? '') ?>"
                                        data-parent-name="<?= e($row['veli_adi'] ?? '') ?>"
                                        data-phone="<?= e($row['telefon'] ?? '') ?>"
                                        data-package-name="<?= e($row['paket_adi'] ?? '') ?>"
                                        data-time="<?= e($saatGoster($row['son_ders_saati'] ?? null)) ?>"
                                        data-remaining="<?= e($row['kalan_normal_hak'] ?? '') ?>"
                                        data-makeup="<?= e($row['kalan_telafi_hak'] ?? '') ?>"
                                        data-renewal="<?= e(((int) ($row['kalan_normal_hak'] ?? 0) > 1) ? para_goster($row['yenileme_ucreti']) : 'Tek ders') ?>"
                                    >Mesaj Gonder</button>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
<?php endif; ?>

<?php if ($canPayments || $canReports) : ?>
<section class="panel-card report-panel renewal-calendar-panel">
    <div class="appointment-toolbar">
        <div>
            <h2>Gelecek Yenileme Takvimi</h2>
            <p>Son ders bitim tarihine gore gun gun beklenen kayit yenileme bakiyesi.</p>
        </div>
        <div class="appointment-toolbar-actions renewal-range-actions">
            <button class="btn btn-ghost is-active" type="button" data-renewal-range="7">7 Gun</button>
            <button class="btn btn-ghost" type="button" data-renewal-range="15">15 Gun</button>
            <button class="btn btn-ghost" type="button" data-renewal-range="30">30 Gun</button>
        </div>
    </div>
    <div class="renewal-custom-range">
        <label><span>Baslangic</span><input type="date" data-renewal-start value="<?= e(date('Y-m-d')) ?>"></label>
        <label><span>Bitis</span><input type="date" data-renewal-end value="<?= e(date('Y-m-d', strtotime('+30 days'))) ?>"></label>
        <button class="btn btn-primary" type="button" data-renewal-custom-apply>Uygula</button>
    </div>
    <div
        class="renewal-calendar"
        data-renewal-calendar
        data-today="<?= e(date('Y-m-d')) ?>"
        data-renewals="<?= e(json_encode($kayitYenilemeTakvimi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
    ></div>
</section>
<?php endif; ?>

<?php if ($canAppointments) : ?>
<section
    class="appointment-page dashboard-appointments"
    data-randevu-page
    data-can-edit-appointments="<?= $canEditAppointments ? '1' : '0' ?>"
    data-can-change-appointment-status="<?= $canChangeAppointmentStatus ? '1' : '0' ?>"
>
    <div class="appointment-stats">
        <article class="appointment-stat">
            <span>Planlanan</span>
            <strong data-randevu-stat="planlandi">0</strong>
            <a href="/panel/randevular">Goruntule</a>
        </article>
        <article class="appointment-stat success">
            <span>Gelen</span>
            <strong data-randevu-stat="geldi">0</strong>
            <a href="/panel/randevular">Goruntule</a>
        </article>
        <article class="appointment-stat danger">
            <span>Gelmeyen</span>
            <strong data-randevu-stat="gelmedi">0</strong>
            <a href="/panel/randevular">Goruntule</a>
        </article>
        <article class="appointment-shortcuts">
            <strong>Kisayollar</strong>
            <div>
                <a class="btn btn-primary" href="/panel/ogrenciler">Ogrenciler</a>
                <a class="btn btn-primary" href="/panel/paketler/tanimla">Yeni Randevu</a>
            </div>
        </article>
    </div>

    <article class="panel-card appointment-calendar-card">
        <div class="appointment-toolbar">
            <div>
                <h2>Randevu Takvimi</h2>
                <p data-calendar-title></p>
            </div>
            <div class="appointment-toolbar-actions">
                <div class="calendar-view-switch" aria-label="Takvim gorunumu">
                    <button class="btn btn-ghost is-active" type="button" data-calendar-view="month">Ay</button>
                    <button class="btn btn-ghost" type="button" data-calendar-view="week">Hafta</button>
                    <button class="btn btn-ghost" type="button" data-calendar-view="day">Gun</button>
                </div>
                <button class="btn btn-ghost" type="button" data-calendar-prev>&lt;</button>
                <button class="btn btn-ghost" type="button" data-calendar-today>Bugun</button>
                <button class="btn btn-ghost" type="button" data-calendar-next>&gt;</button>
                <button class="btn btn-ghost" type="button" data-calendar-print>Yazdir</button>
            </div>
        </div>
        <div class="appointment-calendar" data-randevu-calendar></div>
        <p class="form-message" data-randevu-message></p>
    </article>
</section>
<?php endif; ?>
