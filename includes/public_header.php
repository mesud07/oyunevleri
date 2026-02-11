<?php
$public_nav_active = $public_nav_active ?? '';
$public_login_next = $public_login_next ?? '';
$veli_giris = !empty($_SESSION['veli_giris']) && !empty($_SESSION['veli']);
$veli_ad = $veli_giris ? ($_SESSION['veli']['ad_soyad'] ?? 'Profil') : '';
$next_query = $public_login_next !== '' ? '?next=' . urlencode($public_login_next) : '';
$nav_items = [
    ['key' => 'home', 'label' => 'Anasayfa', 'href' => 'index.php'],
    ['key' => 'storage', 'label' => 'Kurumlar', 'href' => 'search.php'],
];
?>
<header class="public-header">
    <div class="public-topbar">
        <div class="container public-topbar-inner">
            <div class="public-topbar-left">
                <span class="topbar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16v16H4z"></path>
                        <path d="M22 6l-10 7L2 6"></path>
                    </svg>
                </span>
                <a href="mailto:iletisim@oyunevleri.com">iletisim@oyunevleri.com</a>
            </div>
            <div class="public-topbar-right">
                <span>Takip Edin:</span>
                <a href="#" class="topbar-social" aria-label="Instagram">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                        <path d="M16 11.37a4 4 0 1 1-7.87 1.26A4 4 0 0 1 16 11.37z"></path>
                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                    </svg>
                </a>
            </div>
        </div>
    </div>
    <div class="container public-nav">
        <a class="public-logo" href="index.php">
            <span class="public-logo-mark" aria-hidden="true"></span>
            <span>Oyunevleri<span class="public-logo-accent">.com</span></span>
        </a>
        <button class="public-menu-toggle" type="button" aria-expanded="false" aria-controls="publicMenu" data-public-menu-toggle>
            <span></span>
            <span></span>
            <span></span>
        </button>
        <nav class="public-links" id="publicMenu" data-public-menu>
            <?php foreach ($nav_items as $item) {
                $active = $public_nav_active === $item['key'] ? 'active' : '';
                ?>
                <a class="<?php echo $active; ?>" href="<?php echo $item['href']; ?>">
                    <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php } ?>
        </nav>
        <div class="public-auth">
            <?php if ($veli_giris) { ?>
                <div class="profile-wrap" data-profile-wrap>
                    <button class="profile-toggle" type="button" data-profile-toggle>
                        <span class="profile-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        </span>
                        <span><?php echo htmlspecialchars($veli_ad, ENT_QUOTES, 'UTF-8'); ?></span>
                    </button>
                    <div class="profile-menu" data-profile-menu>
                        <a href="profilim.php">Profilim</a>
                        <a href="grup_bilgilerim.php">Grup Bilgilerim</a>
                        <a href="index.php#grup-takvimim">Grup Takvimim</a>
                        <a href="logout.php">Çıkış Yap</a>
                    </div>
                </div>
            <?php } else { ?>
                <a class="public-btn ghost" href="login.php<?php echo $next_query; ?>">Giriş Yap</a>
                <a class="public-btn primary" href="register.php<?php echo $next_query; ?>">Kayıt Ol</a>
    <?php } ?>
        </div>
    </div>
</header>
<script>
    (function () {
        var menuToggle = document.querySelector('[data-public-menu-toggle]');
        var menu = document.querySelector('[data-public-menu]');
        if (menuToggle && menu) {
            menuToggle.addEventListener('click', function () {
                var open = menu.classList.toggle('is-open');
                menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            document.addEventListener('click', function (e) {
                if (!menu.contains(e.target) && !menuToggle.contains(e.target)) {
                    menu.classList.remove('is-open');
                    menuToggle.setAttribute('aria-expanded', 'false');
                }
            });
        }
        var wraps = document.querySelectorAll('[data-profile-wrap]');
        if (!wraps.length) { return; }
        wraps.forEach(function (wrap) {
            var toggle = wrap.querySelector('[data-profile-toggle]');
            var menu = wrap.querySelector('[data-profile-menu]');
            if (!toggle || !menu) { return; }
            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                menu.classList.toggle('is-open');
            });
            document.addEventListener('click', function (e) {
                if (!wrap.contains(e.target)) {
                    menu.classList.remove('is-open');
                }
            });
        });
    })();
</script>
