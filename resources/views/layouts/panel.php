<?php
$asset = static function (string $path): string {
    $fullPath = BASE_PATH . '/public' . $path;
    $version = is_file($fullPath) ? (string) filemtime($fullPath) : (string) time();
    return $path . '?v=' . rawurlencode($version);
};
$kurumLogoYolu = trim((string) ($kullanici['kurum_logo_yolu'] ?? ''));
$kurumLogosuVar = preg_match('#^/uploads/kurum-logolari/[A-Za-z0-9._-]+$#', $kurumLogoYolu) === 1
    && is_file(BASE_PATH . '/public' . $kurumLogoYolu);
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($baslik ?? 'Panel') ?> | Oyun Evleri Yönetim Sistemi</title>
    <link rel="stylesheet" href="<?= e($asset('/assets/css/panel.css')) ?>">
    <link rel="stylesheet" href="<?= e($asset('/assets/css/formlar.css')) ?>">
    <link rel="stylesheet" href="<?= e($asset('/assets/css/tablolar.css')) ?>">
    <link rel="stylesheet" href="<?= e($asset('/assets/css/takvim.css')) ?>">
    <link rel="stylesheet" href="<?= e($asset('/assets/css/mobil.css')) ?>">
    <link rel="stylesheet" href="<?= e($asset('/assets/css/liquid-glass.css')) ?>">
    <link rel="stylesheet" href="<?= e($asset('/assets/css/gece-modu.css')) ?>">
    <link rel="stylesheet" href="<?= e($asset('/assets/css/personel-puantaj.css')) ?>">
    <link rel="stylesheet" href="<?= e($asset('/assets/css/tahsilat-analizi.css')) ?>">
    <?php if (($aktif ?? '') === 'ogrenci-adres-haritasi') : ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
    <?php endif; ?>
    <script nonce="<?= e(\App\Core\SecurityHeaders::nonce()) ?>">
        (() => {
            try {
                const kayitliTema = localStorage.getItem('talyaTema');
                const sistemGeceModu = window.matchMedia?.('(prefers-color-scheme: dark)').matches;
                const tema = kayitliTema || (sistemGeceModu ? 'dark' : 'light');
                document.documentElement.dataset.theme = tema;
                document.documentElement.style.colorScheme = tema;
            } catch (error) {
                document.documentElement.dataset.theme = 'light';
            }
        })();
    </script>
    <script nonce="<?= e(\App\Core\SecurityHeaders::nonce()) ?>">window.talyaCsrfToken = <?= json_encode($csrf ?? '') ?>;</script>
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" id="panel-sidebar" aria-label="Panel menusu">
            <div class="brand">
                <span class="brand-mark<?= $kurumLogosuVar ? ' has-institution-logo' : '' ?>">
                    <?php if ($kurumLogosuVar) : ?>
                        <img src="<?= e($asset($kurumLogoYolu)) ?>" alt="<?= e(($kullanici['kurum_adi'] ?? 'Kurum') . ' logosu') ?>">
                    <?php else : ?>T<?php endif; ?>
                </span>
                <div class="brand-text">
                    <strong title="<?= e($kullanici['kurum_adi'] ?? 'Oyun Evleri') ?>"><?= e($kullanici['kurum_adi'] ?? 'Oyun Evleri') ?></strong>
                    <small>Yönetim Sistemi</small>
                </div>
                <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-label="Menuyu ac veya daralt" aria-expanded="true">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
            <?php
            $odemeAktif = str_starts_with((string) ($aktif ?? ''), 'odemeler');
            $grupAktif = str_starts_with((string) ($aktif ?? ''), 'gruplar');
            $ogrenciAktif = in_array((string) ($aktif ?? ''), ['ogrenciler', 'ogrenci-devamlilik', 'ogrenci-adres-haritasi', 'ogrenci-ozel-notlar', 'ogrenci-kara-liste', 'bekleyen-veliler', 'veli-onamlari'], true);
            $programAktif = $grupAktif || in_array((string) ($aktif ?? ''), ['randevular'], true);
            $finansAktif = $odemeAktif || in_array((string) ($aktif ?? ''), ['paketler', 'cariler', 'faturalar', 'mysoft-ayarlari', 'raporlar', 'gelir-gider'], true);
            $icerikAktif = in_array((string) ($aktif ?? ''), ['haftalik-temalar', 'gunluk-kayitlar'], true);
            $smsAktif = in_array((string) ($aktif ?? ''), ['sms', 'sms-raporlar'], true);
            $sistemAktif = (string) ($aktif ?? '') === 'kurumlar';
            $yonetimAktif = in_array((string) ($aktif ?? ''), ['kullanicilar', 'personel-puantaj'], true);
            $menuIzin = static fn(string $yetki): bool => yetki_var($yetki);
            $kurumModuluServisi = new \App\Services\KurumModuluServisi();
            $sayfaIzin = static fn(string $sayfa): bool => $kurumModuluServisi->sayfaAktifMi($sayfa);
            ?>
            <nav class="menu">
                <a class="<?= aktif_menu($aktif ?? '', 'genel-bakis') ?>" href="/panel" data-short="GB" data-icon="dashboard" data-tooltip="Genel Bakış">Genel Bakis</a>
                <?php if (($menuIzin('ogrenci_listele') && ($sayfaIzin('ogrenciler') || $sayfaIzin('devamlilik_raporu') || $sayfaIzin('adres_haritasi') || $sayfaIzin('ozel_notlar') || $sayfaIzin('tedbir_listesi'))) || ($menuIzin('bekleyen_veli_listele') && $sayfaIzin('bekleyen_veliler')) || ($menuIzin('veli_listele') && $sayfaIzin('veli_onamlari'))) : ?>
                    <div class="menu-group <?= $ogrenciAktif ? 'is-open' : '' ?>">
                        <button class="<?= $ogrenciAktif ? ' is-active' : '' ?>" type="button" data-menu-group-toggle data-short="OI" data-icon="student-operations" data-tooltip="Öğrenci İşlemleri" aria-expanded="<?= $ogrenciAktif ? 'true' : 'false' ?>">Ogrenci Islemleri</button>
                        <div class="submenu" data-menu-title="Öğrenci İşlemleri">
                            <?php if ($menuIzin('ogrenci_listele') && $sayfaIzin('ogrenciler')) : ?><a class="<?= aktif_menu($aktif ?? '', 'ogrenciler') ?>" href="/panel/ogrenciler" data-short="OG" data-icon="students" title="Ogrenciler">Ogrenciler</a><?php endif; ?>
                            <?php if ($menuIzin('ogrenci_listele') && $sayfaIzin('devamlilik_raporu')) : ?><a class="<?= aktif_menu($aktif ?? '', 'ogrenci-devamlilik') ?>" href="/panel/ogrenciler/devamlilik-raporu" data-short="DR" data-icon="attendance" title="Devamlılık Raporu">Devamlılık Raporu</a><?php endif; ?>
                            <?php if ($menuIzin('ogrenci_listele') && $sayfaIzin('adres_haritasi')) : ?><a class="<?= aktif_menu($aktif ?? '', 'ogrenci-adres-haritasi') ?>" href="/panel/ogrenciler/adres-haritasi" data-short="AH" data-icon="address-map" title="Adres Haritası">Adres Haritası</a><?php endif; ?>
                            <?php if ($menuIzin('ogrenci_listele') && $sayfaIzin('ozel_notlar')) : ?><a class="<?= aktif_menu($aktif ?? '', 'ogrenci-ozel-notlar') ?>" href="/panel/ogrenciler/ozel-notlar" data-short="ON" data-icon="private-notes" title="Özel Notlar">Özel Notlar</a><?php endif; ?>
                            <?php if ($menuIzin('ogrenci_listele') && $sayfaIzin('tedbir_listesi')) : ?><a class="<?= aktif_menu($aktif ?? '', 'ogrenci-kara-liste') ?>" href="/panel/ogrenciler/tedbir-listesi" data-short="TL" data-icon="measures" title="Öğrenci Tedbir Listesi">Tedbir Listesi</a><?php endif; ?>
                            <?php if ($menuIzin('bekleyen_veli_listele') && $sayfaIzin('bekleyen_veliler')) : ?><a class="<?= aktif_menu($aktif ?? '', 'bekleyen-veliler') ?>" href="/panel/bekleyen-veliler" data-short="BV" data-icon="waiting-parents" title="Bekleyen Veliler">Bekleyen Veliler</a><?php endif; ?>
                            <?php if ($menuIzin('veli_listele') && $sayfaIzin('veli_onamlari')) : ?><a class="<?= aktif_menu($aktif ?? '', 'veli-onamlari') ?>" href="/panel/veli-onamlari" data-short="VO" data-icon="parent-consents" title="Veli Onamları">Veli Onamları</a><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (($menuIzin('randevu_listele') && $sayfaIzin('randevular')) || ($menuIzin('grup_listele') && $sayfaIzin('haftalik_program'))) : ?>
                    <div class="menu-group <?= $programAktif ? 'is-open' : '' ?>">
                        <button class="<?= $programAktif ? ' is-active' : '' ?>" type="button" data-menu-group-toggle data-short="PR" data-icon="program" data-tooltip="Program" aria-expanded="<?= $programAktif ? 'true' : 'false' ?>">Program</button>
                        <div class="submenu" data-menu-title="Program">
                            <?php if ($menuIzin('randevu_listele') && $sayfaIzin('randevular')) : ?><a class="<?= aktif_menu($aktif ?? '', 'randevular') ?>" href="/panel/randevular" data-short="RN" data-icon="appointments" title="Randevular">Randevular</a><?php endif; ?>
                            <?php if ($menuIzin('grup_listele') && $sayfaIzin('haftalik_program')) : ?><a class="<?= aktif_menu($aktif ?? '', 'gruplar') ?>" href="/panel/gruplar" data-short="GP" data-icon="weekly-program" title="Haftalik Program">Haftalik Program</a><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (($menuIzin('paket_listele') && $sayfaIzin('paketler')) || ($menuIzin('odeme_listele') && ($sayfaIzin('borclu_paketler') || $sayfaIzin('tahsilatlar') || $sayfaIzin('giderler') || $sayfaIzin('kasalar') || $sayfaIzin('cariler') || $sayfaIzin('faturalar'))) || ($menuIzin('fatura_entegrasyon_yonet') && $sayfaIzin('entegrasyon_ayarlari')) || ($menuIzin('rapor_ozet') && ($sayfaIzin('gelir_gider') || $sayfaIzin('raporlar')))) : ?>
                    <div class="menu-group <?= $finansAktif ? 'is-open' : '' ?>">
                        <button class="<?= $finansAktif ? ' is-active' : '' ?>" type="button" data-menu-group-toggle data-short="FN" data-icon="finance" data-tooltip="Finans" aria-expanded="<?= $finansAktif ? 'true' : 'false' ?>">Finans</button>
                        <div class="submenu" data-menu-title="Finans">
                            <?php if ($menuIzin('paket_listele') && $sayfaIzin('paketler')) : ?><a class="<?= aktif_menu($aktif ?? '', 'paketler') ?>" href="/panel/paketler" data-short="PK" data-icon="packages" title="Paketler">Paketler</a><?php endif; ?>
                            <?php if ($menuIzin('odeme_listele') && $sayfaIzin('borclu_paketler')) : ?><a class="<?= aktif_menu($aktif ?? '', 'odemeler-borclular') ?>" href="/panel/odemeler/borclular" data-short="BP" data-icon="overdue-packages" title="Borclu Paketler">Borclu Paketler</a><?php endif; ?>
                            <?php if ($menuIzin('odeme_listele') && $sayfaIzin('tahsilatlar')) : ?><a class="<?= aktif_menu($aktif ?? '', 'odemeler-tahsilatlar') ?>" href="/panel/odemeler/tahsilatlar" data-short="TH" data-icon="collections" title="Tahsilatlar">Tahsilatlar</a><?php endif; ?>
                            <?php if ($menuIzin('odeme_listele') && $sayfaIzin('giderler')) : ?><a class="<?= aktif_menu($aktif ?? '', 'odemeler-giderler') ?>" href="/panel/odemeler/giderler" data-short="GO" data-icon="expenses" title="Yapilacak Odemeler">Giderler</a><?php endif; ?>
                            <?php if ($menuIzin('odeme_listele') && $sayfaIzin('kasalar')) : ?><a class="<?= aktif_menu($aktif ?? '', 'odemeler-kasalar') ?>" href="/panel/odemeler/kasalar" data-short="KS" data-icon="cashboxes" title="Kasalar">Kasalar</a><?php endif; ?>
                            <?php if ($menuIzin('odeme_listele') && $sayfaIzin('cariler')) : ?><a class="<?= aktif_menu($aktif ?? '', 'cariler') ?>" href="/panel/cariler" data-short="CR" data-icon="accounts" title="Cariler">Cariler</a><?php endif; ?>
                            <?php if ($menuIzin('odeme_listele') && $sayfaIzin('faturalar')) : ?><a class="<?= aktif_menu($aktif ?? '', 'faturalar') ?>" href="/panel/faturalar" data-short="FT" data-icon="invoices" title="Faturalar">Faturalar</a><?php endif; ?>
                            <?php if ($menuIzin('fatura_entegrasyon_yonet') && $sayfaIzin('entegrasyon_ayarlari')) : ?><a class="<?= aktif_menu($aktif ?? '', 'mysoft-ayarlari') ?>" href="/panel/faturalar/entegrasyon-ayarlari" data-short="EN" data-icon="integrations" title="Entegrasyon Ayarları">Entegrasyon Ayarları</a><?php endif; ?>
                            <?php if ($menuIzin('rapor_ozet') && $sayfaIzin('gelir_gider')) : ?><a class="<?= aktif_menu($aktif ?? '', 'gelir-gider') ?>" href="/panel/finans/gelir-gider" data-short="GG" data-icon="income-expense" title="Gelir Gider Analizi">Gelir Gider Analizi</a><?php endif; ?>
                            <?php if ($menuIzin('rapor_ozet') && $sayfaIzin('raporlar')) : ?><a class="<?= aktif_menu($aktif ?? '', 'raporlar') ?>" href="/panel/raporlar" data-short="RP" data-icon="reports" title="Raporlar">Raporlar</a><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (($menuIzin('tema_yonet') && $sayfaIzin('haftalik_temalar')) || ($menuIzin('yoklama_listele') && $sayfaIzin('gunluk_kayitlar'))) : ?>
                    <div class="menu-group <?= $icerikAktif ? 'is-open' : '' ?>">
                        <button class="<?= $icerikAktif ? ' is-active' : '' ?>" type="button" data-menu-group-toggle data-short="IC" data-icon="content-tracking" data-tooltip="İçerik ve Takip" aria-expanded="<?= $icerikAktif ? 'true' : 'false' ?>">Icerik ve Takip</button>
                        <div class="submenu" data-menu-title="İçerik ve Takip">
                            <?php if ($menuIzin('tema_yonet') && $sayfaIzin('haftalik_temalar')) : ?><a class="<?= aktif_menu($aktif ?? '', 'haftalik-temalar') ?>" href="/panel/haftalik-temalar" data-short="HT" data-icon="weekly-themes" title="Haftalik Temalar">Haftalik Temalar</a><?php endif; ?>
                            <?php if ($menuIzin('yoklama_listele') && $sayfaIzin('gunluk_kayitlar')) : ?><a class="<?= aktif_menu($aktif ?? '', 'gunluk-kayitlar') ?>" href="/panel/gunluk-kayitlar" data-short="GN" data-icon="daily-records" title="Gunluk Kayitlar">Gunluk Kayitlar</a><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (($menuIzin('sms_goruntule') && $sayfaIzin('sms_yonetimi')) || ($menuIzin('sms_rapor_goruntule') && $sayfaIzin('sms_raporlari'))) : ?>
                    <div class="menu-group <?= $smsAktif ? 'is-open' : '' ?>">
                        <button class="<?= $smsAktif ? ' is-active' : '' ?>" type="button" data-menu-group-toggle data-short="SM" data-icon="sms" data-tooltip="SMS" aria-expanded="<?= $smsAktif ? 'true' : 'false' ?>">SMS</button>
                        <div class="submenu" data-menu-title="SMS">
                            <?php if ($menuIzin('sms_goruntule') && $sayfaIzin('sms_yonetimi')) : ?><a class="<?= aktif_menu($aktif ?? '', 'sms') ?>" href="/panel/sms" data-short="SM" data-icon="sms-management" title="SMS Yonetimi">SMS Yonetimi</a><?php endif; ?>
                            <?php if ($menuIzin('sms_rapor_goruntule') && $sayfaIzin('sms_raporlari')) : ?><a class="<?= aktif_menu($aktif ?? '', 'sms-raporlar') ?>" href="/panel/sms/raporlar" data-short="SR" data-icon="sms-reports" title="SMS Raporlari">SMS Raporlari</a><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (($menuIzin('kullanici_yonet') && $sayfaIzin('kullanicilar')) || ($menuIzin('personel_listele') && $sayfaIzin('personel_puantaj'))) : ?>
                    <div class="menu-group <?= $yonetimAktif ? 'is-open' : '' ?>">
                        <button class="<?= $yonetimAktif ? 'is-active' : '' ?>" type="button" data-menu-group-toggle data-short="YN" data-icon="management" data-tooltip="Yönetim" aria-expanded="<?= $yonetimAktif ? 'true' : 'false' ?>">Yonetim</button>
                        <div class="submenu" data-menu-title="Yönetim">
                            <?php if ($menuIzin('kullanici_yonet') && $sayfaIzin('kullanicilar')) : ?><a class="<?= aktif_menu($aktif ?? '', 'kullanicilar') ?>" href="/panel/kullanicilar" data-short="KY" data-icon="users" title="Kullanicilar">Kullanicilar</a><?php endif; ?>
                            <?php if ($menuIzin('personel_listele') && $sayfaIzin('personel_puantaj')) : ?><a class="<?= aktif_menu($aktif ?? '', 'personel-puantaj') ?>" href="/panel/personeller" data-short="PP" data-icon="personnel" title="Personel ve Puantaj">Personel &amp; Puantaj</a><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($menuIzin('sistem_yonetimi')) : ?>
                    <div class="menu-group <?= $sistemAktif ? 'is-open' : '' ?>">
                        <button class="<?= $sistemAktif ? ' is-active' : '' ?>" type="button" data-menu-group-toggle data-short="SY" data-icon="system" data-tooltip="Sistem Yönetimi" aria-expanded="<?= $sistemAktif ? 'true' : 'false' ?>">Sistem Yonetimi</button>
                        <div class="submenu" data-menu-title="Sistem Yönetimi">
                            <a class="<?= aktif_menu($aktif ?? '', 'kurumlar') ?>" href="/panel/sistem/kurumlar" data-short="KR" data-icon="institutions" title="Kurumlar">Kurumlar</a>
                        </div>
                    </div>
                <?php endif; ?>
            </nav>
        </aside>
        <button class="sidebar-backdrop" type="button" data-sidebar-close aria-label="Menuyu kapat"></button>
        <main class="main">
            <header class="topbar">
                <button class="topbar-menu-button" type="button" data-sidebar-toggle aria-controls="panel-sidebar" aria-label="Menuyu ac" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <div>
                    <p>Merhaba, <?= e(($kullanici['ad'] ?? '') . ' ' . ($kullanici['soyad'] ?? '')) ?></p>
                    <strong><?= e($kullanici['kurum_adi'] ?? 'Oyun Evleri Yönetim Sistemi') ?> / <?= e((int) ($kullanici['sistem_yoneticisi'] ?? 0) === 1 ? 'Sistem Yoneticisi' : ($kullanici['rol_adi'] ?? '')) ?></strong>
                </div>
                <div class="topbar-actions">
                    <a class="btn btn-ghost" href="/panel/guvenlik/mfa">Hesap Güvenliği</a>
                    <button class="theme-toggle" type="button" data-theme-toggle aria-pressed="false">
                        <span class="theme-toggle-icon" aria-hidden="true"></span>
                        <span data-theme-toggle-label>Gece Modu</span>
                    </button>
                    <form method="post" action="/cikis">
                        <input type="hidden" name="csrf" value="<?= e($csrf ?? '') ?>">
                        <button class="btn btn-ghost" type="submit">Cikis</button>
                    </form>
                </div>
            </header>
            <?php
            $sayfaYetkileri = [
                'ogrenciler' => 'ogrenci_listele',
                'ogrenci-devamlilik' => 'ogrenci_listele',
                'ogrenci-adres-haritasi' => 'ogrenci_listele',
                'ogrenci-ozel-notlar' => 'ogrenci_listele',
                'ogrenci-kara-liste' => 'ogrenci_listele',
                'bekleyen-veliler' => 'bekleyen_veli_listele',
                'veli-onamlari' => 'veli_listele',
                'veliler' => 'veli_listele',
                'gruplar' => 'grup_listele',
                'paketler' => 'paket_listele',
                'odemeler-borclular' => 'odeme_listele',
                'odemeler-tahsilat-takibi' => 'odeme_listele',
                'odemeler-tahsilatlar' => 'odeme_listele',
                'odemeler-giderler' => 'odeme_listele',
                'odemeler-kasalar' => 'odeme_listele',
                'cariler' => 'odeme_listele',
                'faturalar' => 'odeme_listele',
                'mysoft-ayarlari' => 'fatura_entegrasyon_yonet',
                'randevular' => 'randevu_listele',
                'haftalik-temalar' => 'tema_yonet',
                'gunluk-kayitlar' => 'yoklama_listele',
                'raporlar' => 'rapor_ozet',
                'gelir-gider' => 'rapor_ozet',
                'sms' => 'sms_goruntule',
                'sms-raporlar' => 'sms_rapor_goruntule',
                'kullanicilar' => 'kullanici_yonet',
                'personel-puantaj' => 'personel_listele',
                'kurumlar' => 'sistem_yonetimi',
            ];
            $sayfaYetki = $sayfaYetkileri[(string) ($aktif ?? '')] ?? null;
            ?>
            <?php if ($sayfaYetki && !$menuIzin($sayfaYetki)) : ?>
                <?php http_response_code(403); require BASE_PATH . '/resources/views/errors/403.php'; ?>
            <?php else : ?>
                <?php require $viewDosyasi; ?>
            <?php endif; ?>
            <?php if ($menuIzin('randevu_ekle') && $sayfaIzin('randevular')) : ?>
                <?php require BASE_PATH . '/resources/views/partials/hizli-randevu-dialog.php'; ?>
            <?php endif; ?>
            <?php if ($menuIzin('sms_gonder') && $sayfaIzin('sms_yonetimi')) : ?>
                <dialog class="appointment-dialog sms-compose-dialog" data-sms-compose-dialog data-clinic-name="Oyun Evleri">
                    <form method="dialog" class="appointment-dialog-form" data-sms-compose-form>
                        <div class="dialog-head">
                            <div>
                                <h2>Mesaj Gonder</h2>
                                <p data-sms-compose-recipient></p>
                            </div>
                            <button type="button" data-sms-compose-close>x</button>
                        </div>
                        <input type="hidden" name="ogrenci_id">
                        <input type="hidden" name="veli_id">
                        <input type="hidden" name="sablon_anahtari">
                        <div class="dialog-grid">
                            <label><span>Telefon</span><input name="telefon" data-phone-mask maxlength="16" required></label>
                            <label>
                                <span>SMS Sablonu</span>
                                <select name="sablon_secimi" data-sms-compose-template required>
                                    <option value="">Sablon yukleniyor...</option>
                                </select>
                            </label>
                            <label class="dialog-wide"><span>Mesaj</span><textarea name="mesaj" rows="7" required></textarea></label>
                        </div>
                        <small class="muted" data-sms-compose-counter></small>
                        <div class="record-actions compact-actions">
                            <span data-sms-compose-message></span>
                            <button class="btn btn-ghost" type="button" data-sms-compose-close>Vazgec</button>
                            <button class="btn btn-primary" type="submit">Gonder</button>
                        </div>
                    </form>
                </dialog>
            <?php endif; ?>
        </main>
    </div>
    <footer class="site-footer" aria-label="Telif bilgisi">Oyun Evleri © 2025 - 2026</footer>
    <script src="<?= e($asset('/assets/js/ajax.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/layout-controls.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/panel.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/ogrenciler.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/ogrenci-profil-sekmeleri.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/onam-formlari.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/ogrenci-devamlilik.js')) ?>"></script>
    <?php if (($aktif ?? '') === 'ogrenci-adres-haritasi') : ?>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
        <?php if (!empty($googleHaritaAnahtari)) : ?>
            <script src="https://maps.googleapis.com/maps/api/js?key=<?= e(rawurlencode($googleHaritaAnahtari)) ?>&language=tr&region=TR"></script>
        <?php endif; ?>
        <script src="<?= e($asset('/assets/js/ogrenci-adres-haritasi.js')) ?>"></script>
    <?php endif; ?>
    <script src="<?= e($asset('/assets/js/ogrenci-finans.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/veliler.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/gruplar.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/randevular.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/odemeler.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/faturalar.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/cariler.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/kasalar.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/sms.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/kullanicilar.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/kurumlar.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/temalar.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/gelir-gider.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/personel-puantaj.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/gunluk-kayitlar.js')) ?>"></script>
</body>
</html>
