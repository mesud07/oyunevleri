<?php
$rapor = $rapor ?? [];
$raporOzet = $rapor['ozet'] ?? [];
$borcluPaketSayisi = (int) ($rapor['borclu_paket_sayisi'] ?? 0);
$bugunSonDersler = $rapor['bugun_son_dersler'] ?? [];
$kayitYenilemeTakvimi = $rapor['kayit_yenileme_takvimi'] ?? [];
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
$canAppointments = yetki_var('randevu_listele');
$canEditAppointments = yetki_var('randevu_ekle');
$canChangeAppointmentStatus = yetki_var('randevu_durum_degistir');
$canPayments = yetki_var('odeme_listele');
$canReports = yetki_var('rapor_ozet');
$canSendSms = yetki_var('sms_gonder');
$bugun = new DateTimeImmutable('today');
$ayAdlari = [
    1 => 'Ocak',
    2 => 'Şubat',
    3 => 'Mart',
    4 => 'Nisan',
    5 => 'Mayıs',
    6 => 'Haziran',
    7 => 'Temmuz',
    8 => 'Ağustos',
    9 => 'Eylül',
    10 => 'Ekim',
    11 => 'Kasım',
    12 => 'Aralık',
];
$gunAdlariOzet = [
    1 => 'Pazartesi',
    2 => 'Salı',
    3 => 'Çarşamba',
    4 => 'Perşembe',
    5 => 'Cuma',
    6 => 'Cumartesi',
    7 => 'Pazar',
];
$tarihEtiketi = $bugun->format('j') . ' ' . $ayAdlari[(int) $bugun->format('n')] . ' ' . $bugun->format('Y') . ', ' . $gunAdlariOzet[(int) $bugun->format('N')];
?>

<section class="dashboard-day-summary" aria-labelledby="dashboard-day-title">
    <div class="dashboard-day-heading">
        <div>
            <h1 id="dashboard-day-title">Günün Özeti</h1>
            <p>Bugünkü operasyonel durumunuz</p>
        </div>
        <time class="dashboard-date-pill" datetime="<?= e($bugun->format('Y-m-d')) ?>">
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="5" width="18" height="16" rx="2"></rect>
                <path d="M16 3v4M8 3v4M3 10h18"></path>
            </svg>
            <span><?= e($tarihEtiketi) ?></span>
        </time>
    </div>

    <div class="dashboard-day-cards">
        <?php if ($canAppointments) : ?>
            <article class="dashboard-day-card is-blue">
                <div class="dashboard-day-card-main">
                    <span class="dashboard-day-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="5" width="18" height="16" rx="2"></rect>
                            <path d="M16 3v4M8 3v4M3 10h18"></path>
                        </svg>
                    </span>
                    <div><span>Bugünkü Randevu</span><strong><?= e($ozet['randevu'] ?? 0) ?></strong></div>
                </div>
                <div class="dashboard-day-card-foot">
                    <a href="/panel/randevular">Tüm randevular <span aria-hidden="true">›</span></a>
                    <em>Bugün</em>
                </div>
            </article>
        <?php endif; ?>

        <?php if ($canStudents) : ?>
            <article class="dashboard-day-card is-green">
                <div class="dashboard-day-card-main">
                    <span class="dashboard-day-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M19 8v6M22 11h-6"></path>
                        </svg>
                    </span>
                    <div><span>Aktif Öğrenci</span><strong><?= e($ozet['ogrenci'] ?? 0) ?></strong></div>
                </div>
                <div class="dashboard-day-card-foot">
                    <a href="/panel/ogrenciler">Tüm öğrenciler <span aria-hidden="true">›</span></a>
                    <em>Aktif</em>
                </div>
            </article>
        <?php endif; ?>

        <?php if ($canPayments || $canReports) : ?>
            <article class="dashboard-day-card is-orange">
                <div class="dashboard-day-card-main">
                    <span class="dashboard-day-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 7V5a2 2 0 0 0-2-2H5a3 3 0 0 0 0 6h15v10a2 2 0 0 1-2 2H5a3 3 0 0 1-3-3V6"></path>
                            <path d="M16 13h5"></path>
                        </svg>
                    </span>
                    <div><span>Bekleyen Alacak</span><strong><?= e(para_goster($raporOzet['bekleyen_alacak'] ?? 0)) ?></strong></div>
                </div>
                <div class="dashboard-day-card-foot">
                    <a href="/panel/odemeler/borclular">Tahsilat bekleyenler <span aria-hidden="true">›</span></a>
                    <em><?= e($borcluPaketSayisi) ?> kayıt</em>
                </div>
            </article>

            <article class="dashboard-day-card is-teal">
                <div class="dashboard-day-card-main">
                    <span class="dashboard-day-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 20v-7M9 20V9M14 20V5M19 20V2"></path>
                        </svg>
                    </span>
                    <div><span>Bu Ay Tahsilat</span><strong><?= e(para_goster($raporOzet['bu_ay_tahsilat'] ?? 0)) ?></strong></div>
                </div>
                <div class="dashboard-day-card-foot">
                    <a href="/panel/odemeler/tahsilatlar">Aylık tahsilat <span aria-hidden="true">›</span></a>
                    <em>Bu ay</em>
                </div>
            </article>
        <?php endif; ?>
    </div>
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
