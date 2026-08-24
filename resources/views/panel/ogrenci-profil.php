<?php
$ogrenci = $profil['ogrenci'] ?? [];
$veliler = $profil['veliler'] ?? [];
$birincilVeli = $veliler[0] ?? [];
$paketler = $profil['paketler'] ?? [];
$odemeOzeti = $profil['odeme_ozeti'] ?? [];
$randevular = $profil['randevular'] ?? [];
$gunlukNotlar = $profil['gunluk_notlar'] ?? [];
$karaListeKayitlari = $profil['kara_liste_kayitlari'] ?? [];
$karaListeAktif = $profil['kara_liste_aktif'] ?? null;
$temaSecenekleri = $profil['tema_secenekleri'] ?? [];
$etkinlikGecmisi = $profil['etkinlik_gecmisi'] ?? [];
$telafiler = $profil['telafiler'] ?? [];
$kasalar = $kasalar ?? [];
$onamFormlari = $onamFormlari ?? [];
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
$veliAdSoyad = trim(($birincilVeli['ad'] ?? '') . ' ' . ($birincilVeli['soyad'] ?? ''));
$onamVeliAdSoyad = trim((string) ($ogrenci['vasi_ad_soyad'] ?? '')) ?: $veliAdSoyad;
$onamVeliTc = trim((string) ($ogrenci['vasi_tc_kimlik_no'] ?? '')) ?: (string) ($birincilVeli['tc_kimlik_no'] ?? '');
$onamTelefon = (string) ($birincilVeli['telefon'] ?? $ogrenci['acil_durum_telefon'] ?? $ogrenci['vasi_telefon'] ?? '');
$personelAdSoyad = trim((string) (($kullanici['ad'] ?? '') . ' ' . ($kullanici['soyad'] ?? '')));
$personelUnvan = (string) ($kullanici['rol_adi'] ?? 'Kurum Personeli');
$tahsilatPaketleri = array_values(array_filter(
    $odemeOzeti,
    static fn(array $odeme): bool => (float) ($odeme['kalan_borc'] ?? 0) > 0
));
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
            <button class="btn btn-primary" type="button" data-open-consent-form>Onam Formu +</button>
            <button class="btn btn-danger" type="button" data-open-dialog="#kara-liste-dialog">Kara Listeye Ekle</button>
            <button class="btn btn-ghost" type="button" data-open-profile-edit>Bilgileri Duzenle</button>
        <?php endif; ?>
        <?php if ($canCreateAppointment) : ?>
            <button class="btn btn-ghost" type="button" data-open-dialog="#hizli-randevu-dialog">Hizli Randevu Olustur</button>
            <a class="btn btn-primary" href="<?= e($randevuOlusturUrl) ?>">Randevu Olustur</a>
        <?php endif; ?>
    </div>
</section>

<?php if ($canEditStudent) : ?>
<dialog class="appointment-dialog consent-dialog" data-consent-dialog>
    <form class="appointment-dialog-form consent-dialog-form" data-consent-form>
        <div class="dialog-head consent-dialog-head">
            <div>
                <small data-consent-step-label>1 / 3 · Form Seçimi</small>
                <h2>Onam Formu Oluştur</h2>
            </div>
            <button type="button" data-consent-close aria-label="Kapat">×</button>
        </div>

        <div class="consent-dialog-body">
            <section class="consent-step" data-consent-step="selection">
                <div class="consent-info-box">
                    <strong>Form Seçimi</strong>
                    <span>Oluşturmak istediğiniz formu seçin. Öğrenci ve veli bilgileri sonraki adımda otomatik doldurulacaktır.</span>
                </div>
                <label class="consent-template-card">
                    <input type="radio" name="sablon_kodu" value="gorsel_icerik_kullanim">
                    <span class="consent-template-icon">▣</span>
                    <span>
                        <strong>Görsel İçerik Kullanım Onam Formu</strong>
                        <small>Fotoğraf ve video içeriklerinin sosyal medya, web sitesi ve tanıtım çalışmalarında kullanımı için onam formu</small>
                    </span>
                    <b>›</b>
                </label>
            </section>

            <section class="consent-step" data-consent-step="details" hidden>
                <div class="consent-info-box is-compact">
                    <strong>Oluşturulacak Form</strong>
                    <span>Görsel İçerik Kullanım Onam Formu · Fiziksel belge</span>
                </div>
                <input type="hidden" name="ogrenci_id" value="<?= e($ogrenci['id'] ?? '') ?>">
                <input type="hidden" name="veli_id" value="<?= e($birincilVeli['id'] ?? '') ?>">

                <section class="consent-form-section">
                    <h3>Belge Türü</h3>
                    <div class="consent-document-type">
                        <div><span>▣</span><strong>Fiziksel Belge</strong><small>Kağıt üzerinde imzalanacak</small></div>
                    </div>
                </section>

                <section class="consent-form-section">
                    <h3>Formu Hazırlayan Personel</h3>
                    <div class="dialog-grid">
                        <label><span>Unvan *</span><input name="personel_unvan" value="<?= e($personelUnvan) ?>" required></label>
                        <label><span>Adı Soyadı *</span><input name="personel_ad_soyad" value="<?= e($personelAdSoyad) ?>" required></label>
                    </div>
                </section>

                <section class="consent-form-section">
                    <h3>Öğrenci Bilgileri</h3>
                    <div class="dialog-grid">
                        <label><span>Öğrenci Adı Soyadı *</span><input name="ogrenci_ad_soyad" value="<?= e($adSoyad) ?>" required></label>
                        <label><span>T.C. Kimlik Numarası</span><input name="ogrenci_tc_kimlik_no" maxlength="20" value="<?= e($ogrenci['tc_kimlik_no'] ?? '') ?>"></label>
                        <label><span>Doğum Tarihi</span><input type="date" name="ogrenci_dogum_tarihi" value="<?= e($ogrenci['dogum_tarihi'] ?? '') ?>"></label>
                        <label><span>Telefon Numarası</span><input name="ogrenci_telefon" data-phone-mask maxlength="16" value="<?= e($onamTelefon) ?>"></label>
                    </div>
                </section>

                <section class="consent-form-section">
                    <h3>Veli / Yasal Temsilci Bilgileri</h3>
                    <div class="dialog-grid">
                        <label><span>Adı Soyadı *</span><input name="veli_ad_soyad" value="<?= e($onamVeliAdSoyad) ?>" required></label>
                        <label><span>T.C. Kimlik Numarası</span><input name="veli_tc_kimlik_no" maxlength="20" value="<?= e($onamVeliTc) ?>"></label>
                        <label><span>Yakınlık Derecesi</span><input name="veli_yakinlik" value="<?= e($birincilVeli['yakinlik'] ?? '') ?>" placeholder="Anne, baba, vasi..."></label>
                        <label><span>Form Tarihi *</span><input type="date" name="form_tarihi" value="<?= e(date('Y-m-d')) ?>" required></label>
                    </div>
                </section>

                <label class="consent-confirm-row">
                    <input type="checkbox" name="bilgiler_dogrulandi" value="1" required>
                    <span>Öğrenci ve veli bilgilerinin doğruluğunu kontrol ettim; formun fiziksel imza için oluşturulmasını onaylıyorum.</span>
                </label>
            </section>

            <section class="consent-step" data-consent-step="preview" hidden>
                <div class="consent-preview-paper">
                    <header>
                        <div class="consent-preview-brand"><span>O</span> Oyun Evleri</div>
                        <small>GÖRSEL İÇERİK KULLANIM ONAM FORMU</small>
                    </header>
                    <div class="consent-preview-grid">
                        <section>
                            <h3>Öğrenci Bilgileri</h3>
                            <p><b>Adı Soyadı:</b> <span data-consent-preview-value="ogrenci_ad_soyad"></span></p>
                            <p><b>T.C. Kimlik No:</b> <span data-consent-preview-value="ogrenci_tc_kimlik_no"></span></p>
                            <p><b>Doğum Tarihi:</b> <span data-consent-preview-value="ogrenci_dogum_tarihi"></span></p>
                            <p><b>Form Tarihi:</b> <span data-consent-preview-value="form_tarihi"></span></p>
                        </section>
                        <section>
                            <h3>Kullanım Amaçları</h3>
                            <p>Sosyal medya paylaşımları, kurumsal web sitesi, tanıtım materyalleri ile eğitim ve etkinlik arşivi.</p>
                        </section>
                    </div>
                    <section>
                        <h3>Görsel İçerik Kullanım Açık Rıza Beyanı</h3>
                        <p>Yukarıda yer alan bilgilendirme metnini okuduğumu, anladığımı ve öğrencime ait görsel içeriklerin belirtilen amaçlarla kullanılmasına açık rıza verdiğimi beyan ederim.</p>
                    </section>
                    <div class="consent-preview-signatures">
                        <p><b>Veli/Yasal Temsilci:</b> <span data-consent-preview-value="veli_ad_soyad"></span></p>
                        <p><b>Yakınlık:</b> <span data-consent-preview-value="veli_yakinlik"></span></p>
                        <p><b>Personel:</b> <span data-consent-preview-value="personel_unvan"></span> <span data-consent-preview-value="personel_ad_soyad"></span></p>
                    </div>
                </div>
            </section>
        </div>

        <div class="consent-dialog-actions" data-consent-actions="selection">
            <span></span>
            <button class="btn btn-ghost" type="button" data-consent-close>Kapat</button>
            <button class="btn btn-primary" type="button" data-consent-next disabled>Devam Et</button>
        </div>
        <div class="consent-dialog-actions" data-consent-actions="details" hidden>
            <span data-consent-message></span>
            <button class="btn btn-ghost" type="button" data-consent-back>Geri</button>
            <button class="btn btn-sky" type="button" data-consent-preview>Formu Önizle</button>
            <button class="btn btn-primary" type="submit">Formu Oluştur</button>
        </div>
        <div class="consent-dialog-actions" data-consent-actions="preview" hidden>
            <span data-consent-message></span>
            <button class="btn btn-ghost" type="button" data-consent-edit>Düzenlemeye Dön</button>
            <button class="btn btn-primary" type="submit">Formu Oluştur ve PDF'yi Aç</button>
        </div>
    </form>
</dialog>
<?php endif; ?>

<nav class="student-tabs">
    <a href="#paketler" data-profile-anchor>Paketler</a>
    <a class="is-active" href="#randevular" data-profile-anchor>Randevular</a>
    <a href="/panel/ogrenciler/tema-etkinlikleri?id=<?= e($ogrenci['id'] ?? '') ?>">Tema ve Etkinlikler</a>
    <button type="button" data-profile-section-tab="onam-formlari" aria-selected="false">Onam Formları</button>
    <button type="button" data-profile-section-tab="kara-liste" aria-selected="false">Kara Liste Kayıtları</button>
    <button type="button" data-profile-section-tab="gunluk-notlar" aria-selected="false">Günlük Not Akışı</button>
</nav>

<div class="student-tab-content" data-profile-section-content hidden></div>

<section class="panel-card report-panel consent-list-panel" id="onam-formlari" data-profile-section-panel="onam-formlari" hidden>
    <div class="appointment-toolbar">
        <div>
            <h2>Onam Formları</h2>
            <p>Öğrenci adına oluşturulan fiziksel imza belgeleri.</p>
        </div>
        <?php if ($canEditStudent) : ?>
            <button class="btn btn-primary" type="button" data-open-consent-form>Onam Formu +</button>
        <?php endif; ?>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Form Adı</th><th>Belge Türü</th><th>Form Tarihi</th><th>Hazırlayan</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
                <?php if (!$onamFormlari) : ?><tr><td colspan="6">Henüz onam formu oluşturulmadı.</td></tr><?php endif; ?>
                <?php foreach ($onamFormlari as $onamFormu) : ?>
                    <tr>
                        <td><?= e($onamFormu['form_adi']) ?></td>
                        <td>Fiziksel Belge</td>
                        <td><?= e(tarih_goster($onamFormu['form_tarihi'])) ?></td>
                        <td><?= e($onamFormu['personel_ad_soyad'] ?: trim((string) $onamFormu['olusturan'])) ?></td>
                        <td><span class="status-pill">Oluşturuldu</span></td>
                        <td><div class="row-actions">
                            <a class="btn btn-ghost" target="_blank" rel="noopener" href="/panel/onam-formlari/pdf?id=<?= e($onamFormu['id']) ?>">Görüntüle</a>
                            <a class="btn btn-primary" href="/panel/onam-formlari/pdf?id=<?= e($onamFormu['id']) ?>&amp;indir=1">PDF İndir</a>
                        </div></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

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
                    <label><span>Il</span><input name="veli_il" value="<?= e($birincilVeli['il'] ?? '') ?>"></label>
                    <label><span>Ilce</span><input name="veli_ilce" value="<?= e($birincilVeli['ilce'] ?? '') ?>"></label>
                    <label class="dialog-wide"><span>Adres</span><textarea name="veli_adres" rows="3"><?= e($birincilVeli['adres'] ?? '') ?></textarea></label>
                    <label class="dialog-wide"><span>Bizimle kimin aracılığıyla iletişime geçtiniz?</span><input name="veli_iletisim_referansi" maxlength="190" value="<?= e($birincilVeli['iletisim_referansi'] ?? '') ?>" placeholder="Örn. Ayşe Hanım, Instagram, Google..."></label>
                    <label class="dialog-wide"><span>Veli Notu</span><textarea name="veli_notlar" rows="3"><?= e($birincilVeli['notlar'] ?? '') ?></textarea></label>
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
                    <label class="dialog-wide"><span>Aciklama</span><textarea name="ozel_durum_notu" rows="3"><?= e($ogrenci['ozel_durum_notu'] ?? '') ?></textarea></label>
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
                    </div>
                    <div class="payment-mini-actions">
                        <b><?= $kalanBorc > 0 ? e('Kalan: ' . para_goster($kalanBorc)) : 'Odendi' ?></b>
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

<section class="panel-card report-panel blacklist-panel" id="kara-liste" data-profile-section-panel="kara-liste" hidden>
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

<section class="panel-card report-panel" id="gunluk-notlar" data-profile-section-panel="gunluk-notlar" hidden>
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
