<?php
$ogrenci = $profil['ogrenci'] ?? [];
$veliler = $profil['veliler'] ?? [];
$birincilVeli = $veliler[0] ?? [];
$paketler = $profil['paketler'] ?? [];
$odemeOzeti = $profil['odeme_ozeti'] ?? [];
$odemeler = $profil['odemeler'] ?? [];
$faturalar = $profil['faturalar'] ?? [];
$faturaEntegrasyonuHazir = (bool) ($profil['fatura_entegrasyonu_hazir'] ?? false);
$randevular = $profil['randevular'] ?? [];
$gunlukNotlar = $profil['gunluk_notlar'] ?? [];
$karaListeKayitlari = $profil['kara_liste_kayitlari'] ?? [];
$karaListeAktif = $profil['kara_liste_aktif'] ?? null;
$telafiler = $profil['telafiler'] ?? [];
$veliOnam = $profil['veli_onam'] ?? null;
$veliOnamBilgileri = is_array($veliOnam['form_verisi'] ?? null) ? $veliOnam['form_verisi'] : [];
$kasalar = $kasalar ?? [];
$canEditStudent = yetki_var('ogrenci_ekle');
$canCreateAppointment = yetki_var('randevu_ekle');
$canChangeAppointmentStatus = yetki_var('randevu_durum_degistir');
$canEditAppointment = yetki_var('randevu_ekle');
$canViewFinance = yetki_var('odeme_listele');
$canManagePayments = yetki_var('odeme_ekle');
$canManagePackages = yetki_var('paket_ekle');
$canViewSmsReports = yetki_var('sms_rapor_goruntule');
$canSendSms = yetki_var('sms_gonder');
$karaListeKategorileri = \App\Models\OgrenciKaraListe::KATEGORILER;
$karaListeKategoriEtiketi = static fn(string $kategori): string => $karaListeKategorileri[$kategori] ?? $kategori;
$adSoyad = trim(($ogrenci['ad'] ?? '') . ' ' . ($ogrenci['soyad'] ?? ''));
$tahsilatPaketleri = array_values(array_filter(
    $odemeOzeti,
    static fn(array $odeme): bool => (float) ($odeme['kalan_borc'] ?? 0) > 0
));
$faturasizOdemeler = array_values(array_filter(
    $faturaEntegrasyonuHazir ? $odemeler : [],
    static fn(array $odeme): bool => (int) ($odeme['iptal'] ?? 0) === 0 && empty($odeme['fatura_id'])
));
$toplamPaketTutari = array_sum(array_map(static fn(array $satir): float => (float) ($satir['net_paket_tutari'] ?? 0), $odemeOzeti));
$toplamTahsilat = array_sum(array_map(static fn(array $satir): float => (float) ($satir['tahsilat'] ?? 0), $odemeOzeti));
$toplamKalanBorc = array_sum(array_map(static fn(array $satir): float => max(0, (float) ($satir['kalan_borc'] ?? 0)), $odemeOzeti));
$finansBakiyesi = $toplamTahsilat - $toplamPaketTutari;
$odemeYontemleri = [
    'nakit' => 'Nakit',
    'kredi_karti' => 'Kredi Kartı',
    'havale_eft' => 'Havale / EFT',
    'banka_havalesi' => 'Banka Havalesi',
    'odeme_baglantisi' => 'Ödeme Bağlantısı',
    'diger' => 'Diğer',
];
$randevuOlusturUrl = '/panel/paketler/tanimla?ogrenci_id=' . urlencode((string) ($ogrenci['id'] ?? ''));
$yasMetni = '-';
if (!empty($ogrenci['dogum_tarihi'])) {
    $dogum = new DateTimeImmutable((string) $ogrenci['dogum_tarihi']);
    $bugun = new DateTimeImmutable(date('Y-m-d'));
    $ay = (($bugun->format('Y') - $dogum->format('Y')) * 12) + ((int) $bugun->format('m') - (int) $dogum->format('m'));
    if ((int) $bugun->format('d') < (int) $dogum->format('d')) {
        $ay--;
    }
    $yasMetni = max(0, $ay) . ' ay';
}
$saatGoster = static fn(?string $saat): string => $saat ? substr($saat, 0, 5) : '-';
$uzunTarihGoster = static function (?string $tarih): string {
    if (!$tarih) {
        return '-';
    }
    $zaman = strtotime($tarih);
    if (!$zaman) {
        return $tarih;
    }
    $aylar = [
        1 => 'Ocak',
        2 => 'Subat',
        3 => 'Mart',
        4 => 'Nisan',
        5 => 'Mayis',
        6 => 'Haziran',
        7 => 'Temmuz',
        8 => 'Agustos',
        9 => 'Eylul',
        10 => 'Ekim',
        11 => 'Kasim',
        12 => 'Aralik',
    ];
    $gunler = [
        1 => 'Pazartesi',
        2 => 'Sali',
        3 => 'Carsamba',
        4 => 'Persembe',
        5 => 'Cuma',
        6 => 'Cumartesi',
        7 => 'Pazar',
    ];

    return date('d', $zaman) . ' ' . $aylar[(int) date('n', $zaman)] . ' ' . date('Y', $zaman) . ' ' . $gunler[(int) date('N', $zaman)];
};
$haftaAraligiGoster = static function (?string $baslangic, ?string $bitis): string {
    if (!$baslangic || !$bitis) {
        return '-';
    }
    return tarih_goster($baslangic) . ' - ' . tarih_goster($bitis);
};
$durumEtiket = [
    'planlandi' => 'Planlandi',
    'geldi' => 'Geldi',
    'gelmedi' => 'Gelmedi',
    'mazeretli_gelmedi' => 'Mazeretli Gelmedi',
    'gec_iptal' => 'Gec Iptal',
    'kurum_iptali' => 'Kurum Iptali',
    'ertelendi' => 'Ertelendi',
    'tamamlandi' => 'Tamamlandi',
];
$durumSinif = static function (array $randevu): string {
    if (!empty($randevu['telafi_hakki_id'])) {
        return 'is-makeup';
    }
    $durum = $randevu['durum'] ?? '';
    if ($durum === 'geldi' || $durum === 'tamamlandi') {
        return 'is-success';
    }
    if ($durum === 'gelmedi' || $durum === 'gec_iptal') {
        return 'is-danger';
    }
    if ($durum === 'ertelendi' || $durum === 'mazeretli_gelmedi') {
        return 'is-dark';
    }
    return 'is-planned';
};
$durumIkonu = static function (array $randevu): string {
    $durum = $randevu['durum'] ?? '';
    if ($durum === 'geldi' || $durum === 'tamamlandi') {
        return '<span class="appointment-date-icon is-success">✓</span>';
    }
    if ($durum === 'gelmedi' || $durum === 'gec_iptal') {
        return '<span class="appointment-date-icon is-danger">×</span>';
    }
    return '';
};
?>

<section class="student-special-note<?= !empty($ogrenci['profil_ozel_notu']) ? ' has-note' : '' ?>" aria-label="Öğrenci özel notu">
    <span class="student-special-note-label">Özel Not</span>
    <?php if ($canEditStudent) : ?>
        <form
            class="student-special-note-form"
            data-ajax-form="ogrenci_ozel_not_guncelle"
            data-success-redirect="/panel/ogrenciler/profil?id=<?= e($ogrenci['id'] ?? '') ?>"
        >
            <input type="hidden" name="id" value="<?= e($ogrenci['id'] ?? '') ?>">
            <input
                type="text"
                name="profil_ozel_notu"
                maxlength="500"
                value="<?= e($ogrenci['profil_ozel_notu'] ?? '') ?>"
                placeholder="Örn. Haftada 2'ye çıkmak istiyor"
                aria-label="Öğrenci özel notu"
            >
            <span class="student-special-note-message" data-form-message aria-live="polite"></span>
            <button type="submit">Kaydet</button>
        </form>
    <?php else : ?>
        <p><?= e($ogrenci['profil_ozel_notu'] ?: 'Bu öğrenci için özel not bulunmuyor.') ?></p>
    <?php endif; ?>
</section>

<section class="student-profile-hero">
    <div class="student-avatar"><?= e(substr((string) ($ogrenci['ad'] ?? 'O'), 0, 1)) ?></div>
    <div>
        <h1><?= e($adSoyad) ?></h1>
        <p>Ogrenci Profili</p>
    <div class="student-profile-meta">
        <span><?= e($yasMetni) ?></span>
        <span><?= e($ogrenci['cinsiyet'] ?? 'belirtilmedi') ?></span>
        <span><?= e($ogrenci['durum'] ?? '-') ?></span>
        <?php if ($karaListeAktif) : ?><span class="is-danger">Kara listede</span><?php endif; ?>
    </div>
    </div>
    <div class="appointment-toolbar-actions">
        <?php if ($canManagePayments && $tahsilatPaketleri) : ?>
            <button class="btn btn-primary" type="button" data-open-dialog="#tahsilat-dialog">Tahsilat Yap</button>
        <?php endif; ?>
        <?php if ($canSendSms) : ?>
            <button
                class="btn btn-ghost"
                type="button"
                data-open-sms-compose
                data-student-id="<?= e($ogrenci['id'] ?? '') ?>"
                data-student-name="<?= e($adSoyad) ?>"
                data-parent-id="<?= e($birincilVeli['id'] ?? '') ?>"
                data-parent-name="<?= e(trim(($birincilVeli['ad'] ?? '') . ' ' . ($birincilVeli['soyad'] ?? ''))) ?>"
                data-phone="<?= e($birincilVeli['telefon'] ?? $ogrenci['acil_durum_telefon'] ?? '') ?>"
            >Mesaj Gonder</button>
        <?php endif; ?>
        <?php if ($canViewSmsReports) : ?>
            <button class="btn btn-ghost" type="button" data-open-profile-sms-reports data-student-id="<?= e($ogrenci['id'] ?? '') ?>">SMS Raporlari</button>
        <?php endif; ?>
        <?php if ($canEditStudent) : ?>
            <button class="btn btn-danger" type="button" data-open-dialog="#kara-liste-dialog">Kara Listeye Ekle</button>
            <button class="btn btn-ghost" type="button" data-open-profile-edit>Bilgileri Duzenle</button>
        <?php endif; ?>
        <?php if ($canCreateAppointment) : ?>
            <button class="btn btn-ghost" type="button" data-open-dialog="#hizli-randevu-dialog">Hizli Randevu Olustur</button>
            <a class="btn btn-primary" href="<?= e($randevuOlusturUrl) ?>">Randevu Olustur</a>
        <?php endif; ?>
    </div>
</section>

<?php if ($veliOnam) : ?>
<section class="panel-card student-consent-card" aria-label="Veli onam formu bilgileri">
    <div class="definition-head">
        <div>
            <h2>Onam Formu Bilgileri</h2>
            <p><?= e(date('d.m.Y H:i', strtotime((string) $veliOnam['onay_tarihi']))) ?> tarihinde veli tarafından dolduruldu.</p>
        </div>
        <a class="btn btn-ghost" href="/panel/veli-onamlari/pdf?id=<?= e($veliOnam['id']) ?>" target="_blank">Onam PDF'ini Aç</a>
    </div>
    <div class="student-consent-facts">
        <div><span>Anne</span><strong><?= e($veliOnamBilgileri['anne_ad_soyad'] ?? '-') ?></strong></div>
        <div><span>Baba</span><strong><?= e($veliOnamBilgileri['baba_ad_soyad'] ?? '-') ?></strong></div>
        <div><span>Ev adresi</span><strong><?= e(implode(', ', array_filter([$veliOnamBilgileri['ev_adresi'] ?? '', $veliOnamBilgileri['ilce'] ?? '', $veliOnamBilgileri['il'] ?? ''])) ?: '-') ?></strong></div>
        <div><span>Uzman desteği</span><strong><?= ($veliOnamBilgileri['uzman_destegi'] ?? '') === 'evet' ? 'Var' : 'Yok' ?><?= !empty($veliOnamBilgileri['uzman_aciklama']) ? ' — ' . e($veliOnamBilgileri['uzman_aciklama']) : '' ?></strong></div>
        <div><span>Oyun grubu deneyimi</span><strong><?= ($veliOnamBilgileri['oyun_grubu_deneyimi'] ?? '') === 'evet' ? 'Var' : 'Yok' ?><?= !empty($veliOnamBilgileri['oyun_grubu_aciklama']) ? ' — ' . e($veliOnamBilgileri['oyun_grubu_aciklama']) : '' ?></strong></div>
        <div><span>Tanı / gelişimsel farklılık</span><strong><?= ($veliOnamBilgileri['tani_durumu'] ?? '') === 'var' ? 'Var' : 'Yok' ?><?= !empty($veliOnamBilgileri['tani_aciklama']) ? ' — ' . e($veliOnamBilgileri['tani_aciklama']) : '' ?></strong></div>
        <div><span>Düzenli ilaç</span><strong><?= ($veliOnamBilgileri['ilac_durumu'] ?? '') === 'var' ? 'Var' : 'Yok' ?><?= !empty($veliOnamBilgileri['ilac_aciklama']) ? ' — ' . e($veliOnamBilgileri['ilac_aciklama']) : '' ?></strong></div>
        <div><span>Alerji</span><strong><?= e($veliOnamBilgileri['alerji_durumu'] ?? '-') ?: '-' ?></strong></div>
        <div><span>Özel sağlık durumu</span><strong><?= e($veliOnamBilgileri['ozel_saglik_durumu'] ?? '-') ?: '-' ?></strong></div>
        <div><span>Görsel kayıt izni</span><strong><?= !empty($veliOnamBilgileri['gorsel_kayit_izni']) ? 'Onaylandı' : 'Onaylanmadı' ?></strong></div>
        <div><span>Sosyal medya izni</span><strong><?= !empty($veliOnamBilgileri['sosyal_medya_izni']) ? 'Onaylandı' : 'Onaylanmadı' ?></strong></div>
        <div><span>Program kuralları</span><strong><?= !empty($veliOnamBilgileri['program_kurallari_kabul']) ? 'Kabul edildi' : 'Kabul edilmedi' ?></strong></div>
    </div>
</section>
<?php endif; ?>

<nav class="student-tabs">
    <a href="#paketler" data-student-tab="profil">Paketler</a>
    <a class="is-active" href="#randevular" data-student-tab="profil">Randevular</a>
    <?php if ($canViewFinance) : ?><a href="#finans" data-student-tab="finans">Finans</a><?php endif; ?>
    <a href="#kara-liste" data-student-tab="profil">Kara Liste</a>
    <a href="#gunluk-notlar" data-student-tab="profil">Gunluk Notlar</a>
</nav>

<?php if ($canEditStudent) : ?>
<dialog class="appointment-dialog" id="kara-liste-dialog">
    <form method="dialog" class="appointment-dialog-form" data-blacklist-form>
        <div class="dialog-head">
            <h2>Kara Liste Kaydi</h2>
            <button type="button" data-close-dialog>x</button>
        </div>
        <input type="hidden" name="ogrenci_id" value="<?= e($ogrenci['id'] ?? '') ?>">
        <div class="dialog-grid">
            <label>
                <span>Kategori</span>
                <select name="kategori" required>
                    <option value="">Kategori seciniz</option>
                    <?php foreach ($karaListeKategorileri as $kod => $etiket) : ?>
                        <option value="<?= e($kod) ?>"><?= e($etiket) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="dialog-wide">
                <span>Sebep</span>
                <textarea name="sebep" rows="5" placeholder="Kara listeye alma sebebini yazin." required></textarea>
            </label>
        </div>
        <div class="record-actions compact-actions">
            <span data-blacklist-message></span>
            <button class="btn btn-ghost" type="button" data-close-dialog>Vazgec</button>
            <button class="btn btn-primary" type="submit">Kaydet</button>
        </div>
    </form>
</dialog>
<?php endif; ?>

<?php if ($canViewSmsReports) : ?>
<dialog class="appointment-dialog appointment-detail-dialog profile-sms-report-dialog" data-profile-sms-dialog>
    <div class="appointment-dialog-form">
        <div class="dialog-head">
            <h2>SMS Raporlari</h2>
            <button type="button" data-profile-sms-close>x</button>
        </div>
        <p class="muted">Bu ogrenciye ait gonderilmis, kuyrukta bekleyen ve basarisiz SMS kayitlari.</p>
        <div class="table-wrap definition-table profile-sms-report-table" data-profile-sms-table>
            <div class="empty-state">SMS raporlari yukleniyor...</div>
        </div>
        <div class="record-actions compact-actions">
            <button class="btn btn-ghost" type="button" data-profile-sms-close>Kapat</button>
        </div>
    </div>
</dialog>
<?php endif; ?>

<?php if ($canManagePayments && $tahsilatPaketleri) : ?>
<dialog id="tahsilat-dialog" class="appointment-dialog payment-dialog">
    <form method="dialog" class="appointment-dialog-form" data-ajax-form="odeme_ekle" data-success-redirect="/panel/ogrenciler/profil?id=<?= e($ogrenci['id'] ?? '') ?>">
        <div class="dialog-head">
            <h2>Tahsilat Yap</h2>
            <button type="button" data-close-dialog>x</button>
        </div>
        <input type="hidden" name="veli_id" value="<?= e($birincilVeli['id'] ?? '') ?>">
        <div class="dialog-grid">
            <label>
                <span>Paket</span>
                <select name="paket_id" required>
                    <option value="">Seciniz</option>
                    <?php foreach ($tahsilatPaketleri as $odeme) : ?>
                        <option value="<?= e($odeme['paket_id'] ?? '') ?>"><?= e($odeme['paket_adi'] ?? 'Paket') ?> - Kalan <?= e(para_goster(max(0, (float) ($odeme['kalan_borc'] ?? 0)))) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span>Tarih</span><input type="date" name="tarih" value="<?= e(date('Y-m-d')) ?>" required></label>
            <label><span>Tutar</span><input type="number" step="0.01" min="0.01" name="tutar" required></label>
            <label>
                <span>Yontem</span>
                <select name="yontem" required>
                    <option value="nakit">Nakit</option>
                    <option value="kredi_karti">Kredi Karti</option>
                    <option value="havale_eft">Havale/EFT</option>
                    <option value="odeme_baglantisi">Odeme Baglantisi</option>
                    <option value="diger">Diger</option>
                </select>
            </label>
            <label><span>Makbuz No</span><input name="makbuz_numarasi"></label>
            <label>
                <span>Kasa</span>
                <select name="kasa_id">
                    <option value="">Kasa secin</option>
                    <?php foreach ($kasalar as $kasa) : ?>
                        <option value="<?= e($kasa['id']) ?>"><?= e($kasa['ad']) ?> - <?= e($kasa['para_birimi']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="check-row dialog-wide">
                <span>SMS</span>
                <div class="check-list">
                    <label><input type="checkbox" name="odeme_sms_gonder" value="1"> Odeme alindi SMS'i gonder</label>
                </div>
            </label>
            <label class="dialog-wide"><span>Aciklama</span><textarea name="aciklama" rows="3"></textarea></label>
        </div>
        <div class="record-actions compact-actions">
            <span data-form-message></span>
            <button class="btn btn-ghost" type="button" data-close-dialog>Vazgec</button>
            <button class="btn btn-primary" type="submit">Tahsilati Kaydet</button>
        </div>
    </form>
</dialog>
<?php endif; ?>

<?php if ($canEditStudent) : ?>
<dialog class="appointment-dialog appointment-detail-dialog" data-profile-edit-dialog>
    <form class="appointment-dialog-form profile-edit-form" data-profile-edit-form>
        <div class="dialog-head">
            <h2>Bilgileri Duzenle</h2>
            <button type="button" data-profile-edit-close>x</button>
        </div>
        <input type="hidden" name="id" value="<?= e($ogrenci['id'] ?? '') ?>">
        <input type="hidden" name="veli_id" value="<?= e($birincilVeli['id'] ?? '') ?>">

        <div class="profile-edit-sections">
            <section>
                <h3>Kisisel Bilgiler</h3>
                <div class="dialog-grid">
                    <label><span>Ad</span><input name="ogrenci_ad" value="<?= e($ogrenci['ad'] ?? '') ?>" required></label>
                    <label><span>Soyad</span><input name="ogrenci_soyad" value="<?= e($ogrenci['soyad'] ?? '') ?>" required></label>
                    <label><span>TC Kimlik No</span><input name="ogrenci_tc_kimlik_no" value="<?= e($ogrenci['tc_kimlik_no'] ?? '') ?>"></label>
                    <label><span>Dogum Tarihi</span><input type="date" name="ogrenci_dogum_tarihi" value="<?= e($ogrenci['dogum_tarihi'] ?? '') ?>"></label>
                    <label>
                        <span>Cinsiyet</span>
                        <select name="ogrenci_cinsiyet">
                            <option value="belirtilmedi" <?= ($ogrenci['cinsiyet'] ?? '') === 'belirtilmedi' ? 'selected' : '' ?>>Belirtilmedi</option>
                            <option value="kiz" <?= ($ogrenci['cinsiyet'] ?? '') === 'kiz' ? 'selected' : '' ?>>Kiz</option>
                            <option value="erkek" <?= ($ogrenci['cinsiyet'] ?? '') === 'erkek' ? 'selected' : '' ?>>Erkek</option>
                        </select>
                    </label>
                    <label><span>Kayit Tarihi</span><input type="date" name="ogrenci_kayit_tarihi" value="<?= e($ogrenci['kayit_tarihi'] ?? date('Y-m-d')) ?>"></label>
                    <label>
                        <span>Durum</span>
                        <select name="ogrenci_durum">
                            <option value="aktif" <?= ($ogrenci['durum'] ?? '') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="pasif" <?= ($ogrenci['durum'] ?? '') === 'pasif' ? 'selected' : '' ?>>Pasif</option>
                        </select>
                    </label>
                    <label><span>Acil Durum Kisi</span><input name="acil_durum_kisi" value="<?= e($ogrenci['acil_durum_kisi'] ?? '') ?>"></label>
                    <label><span>Acil Durum Telefon</span><input name="acil_durum_telefon" data-phone-mask maxlength="16" value="<?= e($ogrenci['acil_durum_telefon'] ?? '') ?>"></label>
                </div>
            </section>

            <section>
                <h3>Veli Bilgileri</h3>
                <div class="dialog-grid">
                    <label><span>Veli Ad</span><input name="veli_ad" value="<?= e($birincilVeli['ad'] ?? '') ?>"></label>
                    <label><span>Veli Soyad</span><input name="veli_soyad" value="<?= e($birincilVeli['soyad'] ?? '') ?>"></label>
                    <label><span>Veli TC Kimlik No</span><input name="veli_tc_kimlik_no" value="<?= e($birincilVeli['tc_kimlik_no'] ?? '') ?>"></label>
                    <label><span>Telefon Ulke</span><input name="veli_telefon_ulke" value="<?= e($birincilVeli['telefon_ulke'] ?? 'Turkiye') ?>"></label>
                    <label><span>Telefon</span><input name="veli_telefon" data-phone-mask maxlength="16" value="<?= e($birincilVeli['telefon'] ?? '') ?>"></label>
                    <label><span>Yedek Telefon</span><input name="veli_yedek_telefon" data-phone-mask maxlength="16" value="<?= e($birincilVeli['yedek_telefon'] ?? '') ?>"></label>
                    <label><span>E-Posta</span><input type="email" name="veli_eposta" value="<?= e($birincilVeli['eposta'] ?? '') ?>"></label>
                    <label><span>Yakinlik</span><input name="veli_yakinlik" value="<?= e($birincilVeli['yakinlik'] ?? '') ?>"></label>
                    <label><span>İl</span><select name="veli_il"><option value="Antalya" selected>Antalya</option></select></label>
                    <label>
                        <span>İlçe</span>
                        <select name="veli_ilce">
                            <option value="">Seçiniz</option>
                            <?php foreach (['Muratpaşa', 'Kepez', 'Konyaaltı', 'Döşemealtı', 'Aksu'] as $ilceSecenegi) : ?>
                                <option value="<?= e($ilceSecenegi) ?>" <?= ($birincilVeli['ilce'] ?? '') === $ilceSecenegi ? 'selected' : '' ?>><?= e($ilceSecenegi) ?></option>
                            <?php endforeach; ?>
                            <?php if (!empty($birincilVeli['ilce']) && !in_array($birincilVeli['ilce'], ['Muratpaşa', 'Kepez', 'Konyaaltı', 'Döşemealtı', 'Aksu'], true)) : ?>
                                <option value="<?= e($birincilVeli['ilce']) ?>" selected><?= e($birincilVeli['ilce']) ?> (mevcut)</option>
                            <?php endif; ?>
                        </select>
                    </label>
                    <label class="dialog-wide"><span>Adres</span><textarea name="veli_adres" rows="3"><?= e($birincilVeli['adres'] ?? '') ?></textarea></label>
                    <label class="dialog-wide"><span>Veli Notu</span><textarea name="veli_notlar" rows="3"><?= e($birincilVeli['notlar'] ?? '') ?></textarea></label>
                </div>
            </section>

            <section>
                <h3>Öğrencinin Ev Adresi</h3>
                <div class="dialog-grid">
                    <label>
                        <span>İl</span>
                        <select name="ogrenci_il">
                            <option value="Antalya" selected>Antalya</option>
                        </select>
                    </label>
                    <label>
                        <span>İlçe</span>
                        <select name="ogrenci_ilce">
                            <option value="">Seçiniz</option>
                            <?php foreach (['Muratpaşa', 'Kepez', 'Konyaaltı', 'Döşemealtı', 'Aksu'] as $ilceSecenegi) : ?>
                                <option value="<?= e($ilceSecenegi) ?>" <?= ($ogrenci['ilce'] ?? '') === $ilceSecenegi ? 'selected' : '' ?>><?= e($ilceSecenegi) ?></option>
                            <?php endforeach; ?>
                            <?php if (!empty($ogrenci['ilce']) && !in_array($ogrenci['ilce'], ['Muratpaşa', 'Kepez', 'Konyaaltı', 'Döşemealtı', 'Aksu'], true)) : ?>
                                <option value="<?= e($ogrenci['ilce']) ?>" selected><?= e($ogrenci['ilce']) ?> (mevcut)</option>
                            <?php endif; ?>
                        </select>
                    </label>
                    <label class="dialog-wide"><span>Açık Adres</span><textarea name="ogrenci_adres" rows="3" maxlength="500"><?= e($ogrenci['adres'] ?? '') ?></textarea></label>
                    <div class="dialog-wide info-box compact-info">
                        <strong>Harita konumu</strong>
                        <p><?= !empty($ogrenci['adres_konum_dogrulandi']) ? 'Konum doğrulandı. Adres değişirse harita konumu yeniden doğrulanmalıdır.' : 'Adres kaydedildikten sonra Adres Haritası ekranından konumu doğrulayın.' ?></p>
                    </div>
                </div>
                <div class="profile-address-actions">
                    <span data-profile-address-save-message></span>
                    <button class="btn btn-primary" type="submit">Adresi Kaydet</button>
                </div>
            </section>

            <section>
                <h3>Vasi ve Notlar</h3>
                <div class="dialog-grid">
                    <label><span>Vasi Ad Soyad</span><input name="vasi_ad_soyad" value="<?= e($ogrenci['vasi_ad_soyad'] ?? '') ?>"></label>
                    <label><span>Vasi TC Kimlik No</span><input name="vasi_tc_kimlik_no" value="<?= e($ogrenci['vasi_tc_kimlik_no'] ?? '') ?>"></label>
                    <label><span>Vasi Telefon</span><input name="vasi_telefon" data-phone-mask maxlength="16" value="<?= e($ogrenci['vasi_telefon'] ?? '') ?>"></label>
                    <label class="dialog-wide"><span>Saglik Bilgisi</span><textarea name="saglik_bilgisi" rows="3"><?= e($ogrenci['saglik_bilgisi'] ?? '') ?></textarea></label>
                    <label class="dialog-wide"><span>Alerji Bilgisi</span><textarea name="alerji_bilgisi" rows="3"><?= e($ogrenci['alerji_bilgisi'] ?? '') ?></textarea></label>
                    <label class="dialog-wide"><span>Açıklama</span><textarea name="ozel_durum_notu" rows="3"><?= e($ogrenci['ozel_durum_notu'] ?? '') ?></textarea></label>
                    <label class="dialog-wide"><span>Yonetici Notu</span><textarea name="yonetici_notu" rows="3"><?= e($ogrenci['yonetici_notu'] ?? '') ?></textarea></label>
                    <label class="dialog-wide"><span>Ogretmen Notu</span><textarea name="ogretmen_notu" rows="3"><?= e($ogrenci['ogretmen_notu'] ?? '') ?></textarea></label>
                </div>
            </section>
        </div>

        <div class="record-actions compact-actions">
            <span data-profile-edit-message></span>
            <button class="btn btn-ghost" type="button" data-profile-edit-close>Vazgec</button>
            <button class="btn btn-primary" type="submit">Kaydet</button>
        </div>
    </form>
</dialog>
<?php endif; ?>

<?php if ($canEditAppointment) : ?>
<dialog class="appointment-dialog" data-profile-appointment-edit-dialog>
    <form method="dialog" class="appointment-dialog-form" data-profile-appointment-edit-form>
        <div class="dialog-head">
            <h2>Randevu Guncelle</h2>
            <button type="button" data-profile-appointment-edit-close>x</button>
        </div>
        <input type="hidden" name="id">
        <div class="dialog-grid">
            <label><span>Tarih</span><input type="date" name="tarih" required></label>
            <label><span>Saat</span><input type="time" name="baslangic_saati" required></label>
            <label><span>Sure</span><input type="number" name="sure_dakika" min="15" step="15" value="45" required></label>
            <label>
                <span>Durum</span>
                <select name="durum" required>
                    <?php foreach ($durumEtiket as $durum => $etiket) : ?>
                        <option value="<?= e($durum) ?>"><?= e($etiket) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span>Randevu Tanimi</span><input name="tur" required></label>
            <label><span>Hak Kaynagi</span><input name="hak_kaynagi" required></label>
            <label class="dialog-wide"><span>Not</span><textarea name="aciklama" rows="4"></textarea></label>
            <label class="check-row dialog-wide">
                <span>SMS</span>
                <div class="check-list">
                    <label><input type="checkbox" name="randevu_sms_gonder" value="1"> Guncelleme SMS'i gonder</label>
                </div>
            </label>
        </div>
        <div class="record-actions compact-actions">
            <span data-profile-appointment-edit-message></span>
            <button class="btn btn-ghost" type="button" data-profile-appointment-edit-close>Vazgec</button>
            <button class="btn btn-primary" type="submit">Guncelle</button>
        </div>
    </form>
</dialog>
<?php endif; ?>

<?php require __DIR__ . '/partials/ogrenci-finans.php'; ?>

<div data-student-tab-panel="profil">
<section class="profile-grid">
    <article class="panel-card report-panel" id="paketler">
        <h2>Paketler</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Paket</th><th>Baslangic</th><th>Son Ders</th><th>Kalan Ders</th><th>Telafi</th><th>Durum</th><th>Islem</th></tr></thead>
                <tbody>
                    <?php if (!$paketler) : ?><tr><td colspan="7">Paket kaydi bulunamadi.</td></tr><?php endif; ?>
                    <?php foreach ($paketler as $paket) : ?>
                        <tr>
                            <td><?= e($paket['paket_adi']) ?></td>
                            <td><?= e(tarih_goster($paket['baslangic_tarihi'])) ?></td>
                            <td><?= e(tarih_goster($paket['tahmini_son_ders_tarihi'])) ?></td>
                            <td><?= e($paket['kalan_normal_hak']) ?></td>
                            <td><?= e($paket['kalan_telafi_hak']) ?></td>
                            <td><span class="status-pill"><?= e($paket['paket_durumu']) ?></span></td>
                            <td>
                                <?php if ($canManagePackages) : ?>
                                    <button class="btn btn-danger" type="button" data-profile-package-delete="<?= e($paket['id']) ?>">Sil</button>
                                <?php else : ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="form-message" data-profile-package-message></p>
    </article>

    <?php if ($canViewFinance) : ?>
    <article class="panel-card report-panel">
        <h2>Odeme Durumu</h2>
        <div class="payment-mini-list">
            <?php if (!$odemeOzeti) : ?>
                <div class="payment-mini-row">
                    <strong>Odeme kaydi bulunamadi.</strong>
                    <span>Ogrenciye paket tanimlandiginda odeme durumu burada gorunur.</span>
                </div>
            <?php endif; ?>
            <?php foreach ($odemeOzeti as $odeme) : ?>
                <?php
                $kalanBorc = (float) ($odeme['kalan_borc'] ?? 0);
                $tahsilat = (float) ($odeme['tahsilat'] ?? 0);
                ?>
                <div class="payment-mini-row <?= $kalanBorc > 0 ? 'has-debt' : 'is-paid' ?>">
                    <div>
                        <strong><?= e($odeme['paket_adi']) ?></strong>
                        <span>
                            <?= $tahsilat > 0
                                ? 'Odeme: ' . e($odeme['odeme_tarihleri'] ?: '-') . ' / ' . e(para_goster($tahsilat))
                                : 'Odeme yapilmadi' ?>
                        </span>
                        <?php if (!empty($odeme['tahsilat_notu'])) : ?>
                            <span class="payment-note"><strong>Tahsilat notu:</strong> <?= nl2br(e($odeme['tahsilat_notu'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="payment-mini-actions">
                        <b><?= $kalanBorc > 0 ? e('Kalan: ' . para_goster($kalanBorc)) : 'Odendi' ?></b>
                        <?php if ($canManagePayments) : ?>
                            <button
                                class="btn btn-ghost"
                                type="button"
                                data-profile-payment-note
                                data-paket-id="<?= e($odeme['paket_id'] ?? 0) ?>"
                                data-payment-note="<?= e(rawurlencode((string) ($odeme['tahsilat_notu'] ?? ''))) ?>"
                            ><?= empty($odeme['tahsilat_notu']) ? 'Not Ekle' : 'Notu Duzenle' ?></button>
                        <?php endif; ?>
                        <?php if ($canManagePayments && $kalanBorc > 0) : ?>
                            <button
                                class="btn btn-primary"
                                type="button"
                                data-payment-from-debt
                                data-paket-id="<?= e($odeme['paket_id'] ?? 0) ?>"
                                data-tutar="<?= e(number_format($kalanBorc, 2, '.', '')) ?>"
                            >Tahsilat Yap</button>
                            <button
                                class="btn btn-danger"
                                type="button"
                                data-profile-package-unpaid-close="<?= e($odeme['paket_id'] ?? 0) ?>"
                            >Odeme Yapilmadi Kapat</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
    <?php endif; ?>
</section>

<?php if ($canManagePayments && $odemeOzeti) : ?>
<dialog class="appointment-dialog payment-dialog" data-profile-payment-note-dialog>
    <form
        method="dialog"
        class="appointment-dialog-form"
        data-ajax-form="paket_tahsilat_notu_guncelle"
        data-success-redirect="/panel/ogrenciler/profil?id=<?= e($ogrenci['id'] ?? '') ?>"
        data-profile-payment-note-form
    >
        <div class="dialog-head">
            <h2>Tahsilat Notu</h2>
            <button type="button" data-profile-payment-note-close>x</button>
        </div>
        <input type="hidden" name="paket_id">
        <div class="dialog-grid">
            <label class="dialog-wide">
                <span>Not</span>
                <textarea name="tahsilat_notu" rows="5" maxlength="2000" placeholder="Ornek: Onumuzdeki hafta nakit olarak getirecek."></textarea>
                <small>Notu silmek icin alani bosaltip kaydedebilirsiniz.</small>
            </label>
        </div>
        <div class="record-actions compact-actions">
            <span data-form-message></span>
            <button class="btn btn-ghost" type="button" data-profile-payment-note-close>Vazgec</button>
            <button class="btn btn-primary" type="submit">Notu Kaydet</button>
        </div>
    </form>
</dialog>
<?php endif; ?>

<?php if ($canCreateAppointment) : ?>
<section class="panel-card report-panel" data-profile-makeup>
    <div class="appointment-toolbar">
        <div>
            <h2>Bekleyen Telafi Haklari</h2>
            <p>Gelmedi olarak isaretlenen ve telafi hakkindan kullanilan dersleri yeni tarihe planlayin.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Kaynak Ders</th><th>Paket</th><th>Son Kullanim</th><th>Yeni Tarih</th><th>Saat</th><th>Sure</th><th>Islem</th></tr></thead>
            <tbody>
                <?php if (!$telafiler) : ?><tr><td colspan="7">Bekleyen telafi hakki bulunamadi.</td></tr><?php endif; ?>
                <?php foreach ($telafiler as $telafi) : ?>
                    <tr data-makeup-row="<?= e($telafi['id']) ?>">
                        <td><?= e(tarih_goster($telafi['kaynak_tarih'] ?? null)) ?> <?= e($saatGoster($telafi['kaynak_saat'] ?? null)) ?></td>
                        <td><?= e($telafi['paket_adi']) ?></td>
                        <td><?= e(tarih_goster($telafi['son_kullanim_tarihi'] ?? null)) ?></td>
                        <td><input type="date" data-makeup-date value="<?= e(date('Y-m-d')) ?>"></td>
                        <td><input type="time" data-makeup-time value="<?= e($saatGoster($telafi['kaynak_saat'] ?? '15:00')) ?>"></td>
                        <td><input type="number" data-makeup-duration min="15" step="15" value="45"></td>
                        <td><button class="btn btn-primary" type="button" data-makeup-plan="<?= e($telafi['id']) ?>">Planla</button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="form-message" data-profile-makeup-message></p>
</section>
<?php endif; ?>

<section class="panel-card report-panel blacklist-panel" id="kara-liste">
    <div class="appointment-toolbar">
        <div>
            <h2>Kara Liste Kayitlari</h2>
            <p>Bu ogrenci icin sebep ve kategori bazli takip kayitlari.</p>
        </div>
        <div class="appointment-toolbar-actions">
            <a class="btn btn-ghost" href="/panel/ogrenciler/kara-liste">Tum Kara Liste</a>
            <?php if ($canEditStudent) : ?>
                <button class="btn btn-danger" type="button" data-open-dialog="#kara-liste-dialog">Kara Listeye Ekle</button>
            <?php endif; ?>
        </div>
    </div>
    <div class="blacklist-timeline">
        <?php if (!$karaListeKayitlari) : ?>
            <div class="empty-state">Bu ogrenci icin kara liste kaydi yok.</div>
        <?php endif; ?>
        <?php foreach ($karaListeKayitlari as $kayit) : ?>
            <article class="blacklist-item <?= (int) ($kayit['aktif'] ?? 0) === 1 ? 'is-active' : '' ?>">
                <header>
                    <strong><?= e($karaListeKategoriEtiketi((string) ($kayit['kategori'] ?? ''))) ?></strong>
                    <span><?= (int) ($kayit['aktif'] ?? 0) === 1 ? 'Aktif' : 'Kaldirildi' ?></span>
                </header>
                <p><?= nl2br(e($kayit['sebep'] ?? '')) ?></p>
                <footer>
                    <span><?= e(tarih_goster($kayit['olusturulma_tarihi'] ?? null)) ?></span>
                    <?php if ((int) ($kayit['aktif'] ?? 0) === 1 && $canEditStudent) : ?>
                        <button class="btn btn-danger" type="button" data-blacklist-remove="<?= e($kayit['id']) ?>">Kaldir</button>
                    <?php endif; ?>
                </footer>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel-card report-panel" id="gunluk-notlar">
    <div class="appointment-toolbar">
        <div>
            <h2>Gunluk Not Akisi</h2>
            <p>Randevular uzerinden eklenen davranis, gozlem ve takip notlari.</p>
        </div>
        <div class="appointment-toolbar-actions">
            <a class="btn btn-ghost" href="/panel/gunluk-kayitlar?baslangic=<?= e(date('Y-m-d', strtotime('-30 days'))) ?>&bitis=<?= e(date('Y-m-d')) ?>">Tum Gunluk Notlar</a>
        </div>
    </div>
    <div class="student-note-timeline">
        <?php if (!$gunlukNotlar) : ?>
            <div class="empty-state">Bu ogrenci icin henuz gunluk not girilmemis.</div>
        <?php endif; ?>
        <?php foreach ($gunlukNotlar as $not) : ?>
            <article class="student-note-item">
                <time><?= e($uzunTarihGoster($not['tarih'] ?? null)) ?> <?= e($saatGoster($not['baslangic_saati'] ?? null)) ?></time>
                <div>
                    <header>
                        <strong><?= e($not['kategori'] ?? 'Genel') ?></strong>
                        <span><?= e($not['kaydeden'] ?? '-') ?></span>
                    </header>
                    <p><?= nl2br(e($not['not_metni'] ?? '')) ?></p>
                    <small><?= e($not['randevu_tanimi'] ?? '-') ?><?= !empty($not['grup']) ? ' / ' . e($not['grup']) : '' ?></small>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel-card report-panel" id="randevular" data-profile-appointments>
    <div class="appointment-toolbar">
        <div>
            <h2>Randevular</h2>
            <p>Ogrencinin gecmis ve planli randevulari.</p>
        </div>
        <div class="appointment-toolbar-actions">
            <?php if ($canEditAppointment) : ?>
                <button class="btn btn-danger" type="button" data-profile-appointment-delete-selected>Secilenleri Sil</button>
            <?php endif; ?>
            <?php if ($canCreateAppointment) : ?>
                <button class="btn btn-ghost" type="button" data-open-dialog="#hizli-randevu-dialog">Hizli Randevu Olustur</button>
                <a class="btn btn-sky" href="<?= e($randevuOlusturUrl) ?>">Randevu Olustur</a>
            <?php endif; ?>
        </div>
    </div>
    <p class="form-message" data-profile-appointment-message></p>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th></th><th>Tarih</th><th>Saat</th><th>Grup</th><th>Randevu Tanimi</th><th>Durum</th><th>Islem</th></tr></thead>
            <tbody>
                <?php if (!$randevular) : ?><tr><td colspan="8">Randevu bulunamadi.</td></tr><?php endif; ?>
                <?php foreach ($randevular as $index => $randevu) : ?>
                    <tr>
                        <td><?= e($index + 1) ?></td>
                        <td><input type="checkbox" data-profile-appointment-check value="<?= e($randevu['id']) ?>"></td>
                        <td><?= e($uzunTarihGoster($randevu['tarih'])) ?> <?= $durumIkonu($randevu) ?></td>
                        <td><?= e($saatGoster($randevu['baslangic_saati'])) ?></td>
                        <td><?= e($randevu['grup']) ?></td>
                        <td>
                            <?= e($randevu['paket_adi']) ?>
                            <?php if (!empty($randevu['telafi_hakki_id'])) : ?>
                                <small class="appointment-source">Telafi: <?= e(tarih_goster($randevu['telafi_kaynak_tarih'] ?? null)) ?> <?= e($saatGoster($randevu['telafi_kaynak_saat'] ?? null)) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <select data-profile-appointment-status="<?= e($randevu['id']) ?>">
                                <?php foreach ($durumEtiket as $durum => $etiket) : ?>
                                    <option value="<?= e($durum) ?>" <?= $randevu['durum'] === $durum ? 'selected' : '' ?>><?= e($etiket) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($randevu['telafi_hakki_id'])) : ?>
                                <span class="status-pill <?= e($durumSinif($randevu)) ?>">Telafi</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="row-actions">
                                <a class="btn btn-ghost" href="/panel/randevular">Takvim</a>
                                <?php if ($canEditAppointment) : ?>
                                    <button class="btn btn-ghost" type="button" data-profile-appointment-edit="<?= e($randevu['id']) ?>">Duzenle</button>
                                <?php endif; ?>
                                <?php if ($canChangeAppointmentStatus) : ?>
                                    <button class="btn btn-ghost" type="button" data-profile-appointment-status-save="<?= e($randevu['id']) ?>">Durum Kaydet</button>
                                <?php endif; ?>
                                <?php if ($canEditAppointment) : ?>
                                    <button class="btn btn-danger" type="button" data-profile-appointment-delete="<?= e($randevu['id']) ?>">Sil</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
</div>
