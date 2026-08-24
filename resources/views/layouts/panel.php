<?php
$asset = static function (string $path): string {
    $fullPath = BASE_PATH . '/public' . $path;
    $version = is_file($fullPath) ? (string) filemtime($fullPath) : (string) time();
    return $path . '?v=' . rawurlencode($version);
};
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($baslik ?? 'Panel') ?> | Oyun Evleri Yönetim Sistemi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@200;300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e($asset('/assets/css/panel.css')) ?>">
    <link rel="stylesheet" href="<?= e($asset('/assets/css/formlar.css')) ?>">
    <link rel="stylesheet" href="<?= e($asset('/assets/css/tablolar.css')) ?>">
    <link rel="stylesheet" href="<?= e($asset('/assets/css/takvim.css')) ?>">
    <link rel="stylesheet" href="<?= e($asset('/assets/css/mobil.css')) ?>">
    <link rel="stylesheet" href="<?= e($asset('/assets/css/liquid-glass.css')) ?>">
    <script>window.talyaCsrfToken = <?= json_encode($csrf ?? '') ?>;</script>
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" id="panel-sidebar" aria-label="Panel menusu">
            <div class="brand">
                <span class="brand-mark">T</span>
                <div class="brand-text">
                    <strong>Oyun Evleri</strong>
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
            $ogrenciAktif = in_array((string) ($aktif ?? ''), ['ogrenciler', 'ogrenci-kara-liste', 'bekleyen-veliler'], true);
            $programAktif = $grupAktif || in_array((string) ($aktif ?? ''), ['randevular'], true);
            $finansAktif = $odemeAktif || in_array((string) ($aktif ?? ''), ['paketler', 'raporlar', 'gelir-gider'], true);
            $icerikAktif = in_array((string) ($aktif ?? ''), ['haftalik-temalar', 'gunluk-kayitlar'], true);
            $smsAktif = in_array((string) ($aktif ?? ''), ['sms', 'sms-raporlar'], true);
            $sistemAktif = (string) ($aktif ?? '') === 'kurumlar';
            $menuIzin = static fn(string $yetki): bool => yetki_var($yetki);
            ?>
            <nav class="menu">
                <a class="<?= aktif_menu($aktif ?? '', 'genel-bakis') ?>" href="/panel" data-short="GB" title="Genel Bakis">Genel Bakis</a>
                <?php if ($menuIzin('ogrenci_listele') || $menuIzin('bekleyen_veli_listele')) : ?>
                    <div class="menu-group <?= $ogrenciAktif ? 'is-open' : '' ?>">
                        <button class="<?= $ogrenciAktif ? ' is-active' : '' ?>" type="button" data-menu-group-toggle data-short="OI" title="Ogrenci Islemleri" aria-expanded="<?= $ogrenciAktif ? 'true' : 'false' ?>">Ogrenci Islemleri</button>
                        <div class="submenu">
                            <?php if ($menuIzin('ogrenci_listele')) : ?><a class="<?= aktif_menu($aktif ?? '', 'ogrenciler') ?>" href="/panel/ogrenciler" data-short="OG" title="Ogrenciler">Ogrenciler</a><?php endif; ?>
                            <?php if ($menuIzin('ogrenci_listele')) : ?><a class="<?= aktif_menu($aktif ?? '', 'ogrenci-kara-liste') ?>" href="/panel/ogrenciler/kara-liste" data-short="KL" title="Ogrenci Kara Liste">Kara Liste</a><?php endif; ?>
                            <?php if ($menuIzin('bekleyen_veli_listele')) : ?><a class="<?= aktif_menu($aktif ?? '', 'bekleyen-veliler') ?>" href="/panel/bekleyen-veliler" data-short="BV" title="Bekleyen Veliler">Bekleyen Veliler</a><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($menuIzin('randevu_listele') || $menuIzin('grup_listele')) : ?>
                    <div class="menu-group <?= $programAktif ? 'is-open' : '' ?>">
                        <button class="<?= $programAktif ? ' is-active' : '' ?>" type="button" data-menu-group-toggle data-short="PR" title="Program" aria-expanded="<?= $programAktif ? 'true' : 'false' ?>">Program</button>
                        <div class="submenu">
                            <?php if ($menuIzin('randevu_listele')) : ?><a class="<?= aktif_menu($aktif ?? '', 'randevular') ?>" href="/panel/randevular" data-short="RN" title="Randevular">Randevular</a><?php endif; ?>
                            <?php if ($menuIzin('grup_listele')) : ?><a class="<?= aktif_menu($aktif ?? '', 'gruplar') ?>" href="/panel/gruplar" data-short="GP" title="Haftalik Program">Haftalik Program</a><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($menuIzin('paket_listele') || $menuIzin('odeme_listele') || $menuIzin('rapor_ozet')) : ?>
                    <div class="menu-group <?= $finansAktif ? 'is-open' : '' ?>">
                        <button class="<?= $finansAktif ? ' is-active' : '' ?>" type="button" data-menu-group-toggle data-short="FN" title="Finans" aria-expanded="<?= $finansAktif ? 'true' : 'false' ?>">Finans</button>
                        <div class="submenu">
                            <?php if ($menuIzin('paket_listele')) : ?><a class="<?= aktif_menu($aktif ?? '', 'paketler') ?>" href="/panel/paketler" data-short="PK" title="Paketler">Paketler</a><?php endif; ?>
                            <?php if ($menuIzin('odeme_listele')) : ?><a class="<?= aktif_menu($aktif ?? '', 'odemeler-borclular') ?>" href="/panel/odemeler/borclular" data-short="BP" title="Borclu Paketler">Borclu Paketler</a><?php endif; ?>
                            <?php if ($menuIzin('odeme_listele')) : ?><a class="<?= aktif_menu($aktif ?? '', 'odemeler-tahsilatlar') ?>" href="/panel/odemeler/tahsilatlar" data-short="TH" title="Tahsilatlar">Tahsilatlar</a><?php endif; ?>
                            <?php if ($menuIzin('odeme_listele')) : ?><a class="<?= aktif_menu($aktif ?? '', 'odemeler-giderler') ?>" href="/panel/odemeler/giderler" data-short="GO" title="Yapilacak Odemeler">Giderler</a><?php endif; ?>
                            <?php if ($menuIzin('odeme_listele')) : ?><a class="<?= aktif_menu($aktif ?? '', 'odemeler-kasalar') ?>" href="/panel/odemeler/kasalar" data-short="KS" title="Kasalar">Kasalar</a><?php endif; ?>
                            <?php if ($menuIzin('rapor_ozet')) : ?><a class="<?= aktif_menu($aktif ?? '', 'gelir-gider') ?>" href="/panel/finans/gelir-gider" data-short="GG" title="Gelir Gider Analizi">Gelir Gider Analizi</a><?php endif; ?>
                            <?php if ($menuIzin('rapor_ozet')) : ?><a class="<?= aktif_menu($aktif ?? '', 'raporlar') ?>" href="/panel/raporlar" data-short="RP" title="Raporlar">Raporlar</a><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($menuIzin('tema_yonet') || $menuIzin('rapor_ozet')) : ?>
                    <div class="menu-group <?= $icerikAktif ? 'is-open' : '' ?>">
                        <button class="<?= $icerikAktif ? ' is-active' : '' ?>" type="button" data-menu-group-toggle data-short="IC" title="Icerik ve Takip" aria-expanded="<?= $icerikAktif ? 'true' : 'false' ?>">Icerik ve Takip</button>
                        <div class="submenu">
                            <?php if ($menuIzin('tema_yonet')) : ?><a class="<?= aktif_menu($aktif ?? '', 'haftalik-temalar') ?>" href="/panel/haftalik-temalar" data-short="HT" title="Haftalik Temalar">Haftalik Temalar</a><?php endif; ?>
                            <?php if ($menuIzin('rapor_ozet')) : ?><a class="<?= aktif_menu($aktif ?? '', 'gunluk-kayitlar') ?>" href="/panel/gunluk-kayitlar" data-short="GN" title="Gunluk Kayitlar">Gunluk Kayitlar</a><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($menuIzin('sms_goruntule') || $menuIzin('sms_rapor_goruntule')) : ?>
                    <div class="menu-group <?= $smsAktif ? 'is-open' : '' ?>">
                        <button class="<?= $smsAktif ? ' is-active' : '' ?>" type="button" data-menu-group-toggle data-short="SM" title="SMS" aria-expanded="<?= $smsAktif ? 'true' : 'false' ?>">SMS</button>
                        <div class="submenu">
                            <?php if ($menuIzin('sms_goruntule')) : ?><a class="<?= aktif_menu($aktif ?? '', 'sms') ?>" href="/panel/sms" data-short="SM" title="SMS Yonetimi">SMS Yonetimi</a><?php endif; ?>
                            <?php if ($menuIzin('sms_rapor_goruntule')) : ?><a class="<?= aktif_menu($aktif ?? '', 'sms-raporlar') ?>" href="/panel/sms/raporlar" data-short="SR" title="SMS Raporlari">SMS Raporlari</a><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($menuIzin('kullanici_yonet')) : ?>
                    <div class="menu-group <?= (string) ($aktif ?? '') === 'kullanicilar' ? 'is-open' : '' ?>">
                        <button class="<?= aktif_menu($aktif ?? '', 'kullanicilar') ?>" type="button" data-menu-group-toggle data-short="YN" title="Yonetim" aria-expanded="<?= (string) ($aktif ?? '') === 'kullanicilar' ? 'true' : 'false' ?>">Yonetim</button>
                        <div class="submenu">
                            <a class="<?= aktif_menu($aktif ?? '', 'kullanicilar') ?>" href="/panel/kullanicilar" data-short="KY" title="Kullanicilar">Kullanicilar</a>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($menuIzin('sistem_yonetimi')) : ?>
                    <div class="menu-group <?= $sistemAktif ? 'is-open' : '' ?>">
                        <button class="<?= $sistemAktif ? ' is-active' : '' ?>" type="button" data-menu-group-toggle data-short="SY" title="Sistem Yonetimi" aria-expanded="<?= $sistemAktif ? 'true' : 'false' ?>">Sistem Yonetimi</button>
                        <div class="submenu">
                            <a class="<?= aktif_menu($aktif ?? '', 'kurumlar') ?>" href="/panel/sistem/kurumlar" data-short="KR" title="Kurumlar">Kurumlar</a>
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
                <form method="post" action="/cikis">
                    <input type="hidden" name="csrf" value="<?= e($csrf ?? '') ?>">
                    <button class="btn btn-ghost" type="submit">Cikis</button>
                </form>
            </header>
            <?php
            $sayfaYetkileri = [
                'ogrenciler' => 'ogrenci_listele',
                'ogrenci-kara-liste' => 'ogrenci_listele',
                'bekleyen-veliler' => 'bekleyen_veli_listele',
                'veliler' => 'veli_listele',
                'gruplar' => 'grup_listele',
                'paketler' => 'paket_listele',
                'odemeler-borclular' => 'odeme_listele',
                'odemeler-tahsilat-takibi' => 'odeme_listele',
                'odemeler-tahsilatlar' => 'odeme_listele',
                'odemeler-giderler' => 'odeme_listele',
                'odemeler-kasalar' => 'odeme_listele',
                'randevular' => 'randevu_listele',
                'haftalik-temalar' => 'tema_yonet',
                'gunluk-kayitlar' => 'rapor_ozet',
                'raporlar' => 'rapor_ozet',
                'gelir-gider' => 'rapor_ozet',
                'sms' => 'sms_goruntule',
                'sms-raporlar' => 'sms_rapor_goruntule',
                'kullanicilar' => 'kullanici_yonet',
                'kurumlar' => 'sistem_yonetimi',
            ];
            $sayfaYetki = $sayfaYetkileri[(string) ($aktif ?? '')] ?? null;
            ?>
            <?php if ($sayfaYetki && !$menuIzin($sayfaYetki)) : ?>
                <?php http_response_code(403); require BASE_PATH . '/resources/views/errors/403.php'; ?>
            <?php else : ?>
                <?php require $viewDosyasi; ?>
            <?php endif; ?>
            <?php if ($menuIzin('randevu_ekle')) : ?>
                <?php require BASE_PATH . '/resources/views/partials/hizli-randevu-dialog.php'; ?>
            <?php endif; ?>
            <?php if ($menuIzin('sms_gonder')) : ?>
                <dialog class="appointment-dialog sms-compose-dialog" data-sms-compose-dialog data-clinic-name="Oyun Evleri Yönetim Sistemi">
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
    <script src="<?= e($asset('/assets/js/ajax.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/panel.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/ogrenciler.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/ogrenci-profil-sekmeleri.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/onam-formlari.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/veliler.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/gruplar.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/randevular.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/odemeler.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/kasalar.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/sms.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/kullanicilar.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/kurumlar.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/temalar.js')) ?>"></script>
    <script src="<?= e($asset('/assets/js/gelir-gider.js')) ?>"></script>
</body>
</html>
