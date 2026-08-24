<?php
$asset = static function (string $path): string {
    $fullPath = BASE_PATH . '/public' . $path;
    $version = is_file($fullPath) ? (string) filemtime($fullPath) : (string) time();
    return $path . '?v=' . rawurlencode($version);
};
$menuIkonu = static function (string $ikon): string {
    $yollar = match ($ikon) {
        'genel-bakis' => '<rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect>',
        'ogrenciler' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
        'program' => '<rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 11h18"></path><path d="m9 16 2 2 4-4"></path>',
        'finans' => '<rect x="2" y="6" width="20" height="14" rx="2"></rect><path d="M16 10h6v6h-6a3 3 0 0 1 0-6Z"></path><circle cx="17" cy="13" r=".5" fill="currentColor" stroke="none"></circle><path d="M6 6V4h12v2"></path>',
        'sms' => '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3v-7a4 4 0 0 1-1-2.6V7a4 4 0 0 1 4-4h11a4 4 0 0 1 4 4Z"></path><path d="M7 8h10M7 12h7"></path>',
        'yonetim' => '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21H9.6v-.1A1.7 1.7 0 0 0 8 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 3.6 15a1.7 1.7 0 0 0-1.5-1H2v-4h.1A1.7 1.7 0 0 0 3.6 9a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 8 4.6a1.7 1.7 0 0 0 1-1.5V3h4v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9a1.7 1.7 0 0 0 1.5 1h.1v4h-.1a1.7 1.7 0 0 0-1.5 1Z"></path>',
        'sistem' => '<path d="M12 3 4 7v5c0 5 3.4 8 8 9 4.6-1 8-4 8-9V7Z"></path><path d="M9 12l2 2 4-4"></path>',
        default => '',
    };

    return '<svg class="menu-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $yollar . '</svg>';
};
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($baslik ?? 'Panel') ?> | Oyun Evleri Yönetim Sistemi</title>
    <script src="<?= e($asset('/assets/js/theme-init.js')) ?>"></script>
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
            $ogrenciAktif = in_array((string) ($aktif ?? ''), ['ogrenciler', 'ogrenci-kara-liste', 'bekleyen-veliler', 'gunluk-kayitlar'], true);
            $programAktif = $grupAktif || in_array((string) ($aktif ?? ''), ['randevular'], true);
            $finansAktif = $odemeAktif || in_array((string) ($aktif ?? ''), ['paketler', 'raporlar', 'gelir-gider'], true);
            $smsAktif = in_array((string) ($aktif ?? ''), ['sms', 'sms-raporlar'], true);
            $sistemAktif = (string) ($aktif ?? '') === 'kurumlar';
            $menuIzin = static fn(string $yetki): bool => yetki_var($yetki);
            ?>
            <nav class="menu">
                <a class="<?= aktif_menu($aktif ?? '', 'genel-bakis') ?>" href="/panel" data-short="GB" title="Genel Bakis"><?= $menuIkonu('genel-bakis') ?><span class="menu-label">Genel Bakis</span></a>
                <?php if ($menuIzin('ogrenci_listele') || $menuIzin('bekleyen_veli_listele') || $menuIzin('yoklama_listele')) : ?>
                    <div class="menu-group <?= $ogrenciAktif ? 'is-open' : '' ?>">
                        <button class="<?= $ogrenciAktif ? ' is-active' : '' ?>" type="button" data-menu-group-toggle data-short="OI" title="Ogrenci Islemleri" aria-expanded="<?= $ogrenciAktif ? 'true' : 'false' ?>"><?= $menuIkonu('ogrenciler') ?><span class="menu-label">Ogrenci Islemleri</span></button>
                        <div class="submenu">
                            <?php if ($menuIzin('ogrenci_listele')) : ?><a class="<?= aktif_menu($aktif ?? '', 'ogrenciler') ?>" href="/panel/ogrenciler" data-short="OG" title="Ogrenciler">Ogrenciler</a><?php endif; ?>
                            <?php if ($menuIzin('ogrenci_listele')) : ?><a class="<?= aktif_menu($aktif ?? '', 'ogrenci-kara-liste') ?>" href="/panel/ogrenciler/tedbir-listesi" data-short="TL" title="Öğrenci Tedbir Listesi">Tedbir Listesi</a><?php endif; ?>
                            <?php if ($menuIzin('bekleyen_veli_listele')) : ?><a class="<?= aktif_menu($aktif ?? '', 'bekleyen-veliler') ?>" href="/panel/bekleyen-veliler" data-short="BV" title="Bekleyen Veliler">Bekleyen Veliler</a><?php endif; ?>
                            <?php if ($menuIzin('yoklama_listele')) : ?><a class="<?= aktif_menu($aktif ?? '', 'gunluk-kayitlar') ?>" href="/panel/gunluk-kayitlar" data-short="GK" title="Günlük Kayıtlar">Günlük Kayıtlar</a><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($menuIzin('randevu_listele') || $menuIzin('grup_listele')) : ?>
                    <div class="menu-group <?= $programAktif ? 'is-open' : '' ?>">
                        <button class="<?= $programAktif ? ' is-active' : '' ?>" type="button" data-menu-group-toggle data-short="PR" title="Program" aria-expanded="<?= $programAktif ? 'true' : 'false' ?>"><?= $menuIkonu('program') ?><span class="menu-label">Program</span></button>
                        <div class="submenu">
                            <?php if ($menuIzin('randevu_listele')) : ?><a class="<?= aktif_menu($aktif ?? '', 'randevular') ?>" href="/panel/randevular" data-short="RN" title="Randevular">Randevular</a><?php endif; ?>
                            <?php if ($menuIzin('grup_listele')) : ?><a class="<?= aktif_menu($aktif ?? '', 'gruplar') ?>" href="/panel/gruplar" data-short="GP" title="Haftalik Program">Haftalik Program</a><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($menuIzin('paket_listele') || $menuIzin('odeme_listele') || $menuIzin('rapor_ozet')) : ?>
                    <div class="menu-group <?= $finansAktif ? 'is-open' : '' ?>">
                        <button class="<?= $finansAktif ? ' is-active' : '' ?>" type="button" data-menu-group-toggle data-short="FN" title="Finans" aria-expanded="<?= $finansAktif ? 'true' : 'false' ?>"><?= $menuIkonu('finans') ?><span class="menu-label">Finans</span></button>
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
                <?php if ($menuIzin('sms_goruntule') || $menuIzin('sms_rapor_goruntule')) : ?>
                    <div class="menu-group <?= $smsAktif ? 'is-open' : '' ?>">
                        <button class="<?= $smsAktif ? ' is-active' : '' ?>" type="button" data-menu-group-toggle data-short="SM" title="SMS" aria-expanded="<?= $smsAktif ? 'true' : 'false' ?>"><?= $menuIkonu('sms') ?><span class="menu-label">SMS</span></button>
                        <div class="submenu">
                            <?php if ($menuIzin('sms_goruntule')) : ?><a class="<?= aktif_menu($aktif ?? '', 'sms') ?>" href="/panel/sms" data-short="SM" title="SMS Yonetimi">SMS Yonetimi</a><?php endif; ?>
                            <?php if ($menuIzin('sms_rapor_goruntule')) : ?><a class="<?= aktif_menu($aktif ?? '', 'sms-raporlar') ?>" href="/panel/sms/raporlar" data-short="SR" title="SMS Raporlari">SMS Raporlari</a><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($menuIzin('kullanici_yonet')) : ?>
                    <div class="menu-group <?= (string) ($aktif ?? '') === 'kullanicilar' ? 'is-open' : '' ?>">
                        <button class="<?= aktif_menu($aktif ?? '', 'kullanicilar') ?>" type="button" data-menu-group-toggle data-short="YN" title="Yonetim" aria-expanded="<?= (string) ($aktif ?? '') === 'kullanicilar' ? 'true' : 'false' ?>"><?= $menuIkonu('yonetim') ?><span class="menu-label">Yonetim</span></button>
                        <div class="submenu">
                            <a class="<?= aktif_menu($aktif ?? '', 'kullanicilar') ?>" href="/panel/kullanicilar" data-short="KY" title="Kullanicilar">Kullanicilar</a>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($menuIzin('sistem_yonetimi')) : ?>
                    <div class="menu-group <?= $sistemAktif ? 'is-open' : '' ?>">
                        <button class="<?= $sistemAktif ? ' is-active' : '' ?>" type="button" data-menu-group-toggle data-short="SY" title="Sistem Yonetimi" aria-expanded="<?= $sistemAktif ? 'true' : 'false' ?>"><?= $menuIkonu('sistem') ?><span class="menu-label">Sistem Yonetimi</span></button>
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
                <div class="topbar-actions">
                    <button class="btn btn-ghost theme-toggle" type="button" data-theme-toggle aria-pressed="false" aria-label="Koyu modu ac">
                        <svg class="theme-toggle-moon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"></path>
                        </svg>
                        <svg class="theme-toggle-sun" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="4"></circle>
                            <path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"></path>
                        </svg>
                        <span data-theme-label>Koyu Mod</span>
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
                'gunluk-kayitlar' => 'yoklama_listele',
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
