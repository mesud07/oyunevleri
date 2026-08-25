<main class="login-page">
    <section class="login-showcase" aria-label="Oyun Evleri tanitim alani">
        <a class="login-brand" href="https://oyunevleri.com" aria-label="oyunevleri.com">
            <svg class="login-brand-symbol" aria-hidden="true" viewBox="0 0 72 62" fill="none">
                <path d="M8 30 36 8l28 22M12 28v27M60 28v27" stroke="#1599ef" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"></path>
                <path d="m36 19 2.5 5 5.5.8-4 3.9 1 5.5-5-2.6-5 2.6 1-5.5-4-3.9 5.5-.8Z" fill="#ffb020"></path>
                <circle cx="25" cy="41" r="5" fill="#3c8df5"></circle><path d="M18 54c.8-7 3-10 7-10s6.2 3 7 10" fill="#3c8df5"></path>
                <circle cx="47" cy="41" r="5" fill="#59bd72"></circle><path d="M40 54c.8-7 3-10 7-10s6.2 3 7 10" fill="#59bd72"></path>
            </svg>
            <span><strong>oyunevleri</strong><em>.com</em></span>
        </a>

        <div class="login-showcase-copy">
            <h1>Oyun Evleri<br>Yönetimi Çok Kolay!</h1>
            <p>Öğrenci, paket, randevu ve tahsilat süreçlerinizi tek bir yerden yönetin.</p>
        </div>

        <figure class="login-illustration">
            <img src="<?= e($asset('/assets/images/login-playhouse.webp')) ?>" alt="Oyun evi ve oyun parkı illüstrasyonu">
        </figure>

        <div class="login-feature-grid" aria-label="Sistem ozellikleri">
            <article><span class="login-feature-icon is-blue"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 11h18M8 15h3M13 15h3"></path></svg></span><strong>Randevu</strong><p>Kolay randevu oluşturun ve takip edin.</p></article>
            <article><span class="login-feature-icon is-green"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="9" cy="7" r="4"></circle><path d="M2 21v-2a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v2M17 11a4 4 0 0 0 0-8M22 21v-2a4 4 0 0 0-3-3.87"></path></svg></span><strong>Öğrenci Takibi</strong><p>Öğrenci bilgileri ve gelişimi elinizin altında.</p></article>
            <article><span class="login-feature-icon is-orange"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 2v10h10A10 10 0 1 1 12 2Z"></path><path d="M16 2.8A10 10 0 0 1 21.2 8H16Z"></path></svg></span><strong>Finans Yönetimi</strong><p>Tahsilatlarınızı ve ödemelerinizi yönetin.</p></article>
            <article><span class="login-feature-icon is-purple"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3v-7a4 4 0 0 1-1-2.6V7a4 4 0 0 1 4-4h11a4 4 0 0 1 4 4Z"></path><path d="M7 10h.01M12 10h.01M17 10h.01"></path></svg></span><strong>SMS &amp; Bildirim</strong><p>Velilerinize hızlıca SMS gönderin.</p></article>
        </div>

        <p class="login-trust-note"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 4 7v5c0 5 3.4 8 8 9 4.6-1 8-4 8-9V7Z"></path><path d="m9 12 2 2 4-4"></path></svg>Güvenli, hızlı ve kullanıcı dostu yönetim sistemi</p>
    </section>

    <section class="login-form-column">
        <div class="auth-card login-card">
            <div class="auth-logo">Oyun Evleri Yönetim Sistemi</div>
            <h2>Yönetim paneline giriş</h2>
            <p>Öğrenci, paket, randevu ve tahsilat süreçlerini yönetin.</p>

            <?php if (!empty($hata)) : ?><div class="alert alert-error"><?= e($hata) ?></div><?php endif; ?>

            <form method="post" action="/giris" class="form-stack login-form">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <label><span>Kurum Kodu</span><span class="login-input-wrap"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 21V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v17M2 21h20M8 7h2M12 7h1M8 11h2M12 11h1M8 15h2M12 15h1M18 9h2v12"></path></svg><input type="text" name="kurum_kodu" value="<?= e($kurumKodu ?? '') ?>" placeholder="Kurum kodunuzu girin" autocomplete="organization" required></span></label>
                <label><span>Kullanıcı Adı</span><span class="login-input-wrap"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="7" r="4"></circle><path d="M4 21v-2a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v2"></path></svg><input type="text" name="eposta" value="<?= e($eposta ?? '') ?>" placeholder="Kullanıcı adınızı girin" autocomplete="username" required autofocus></span></label>
                <label><span>Şifre</span><span class="login-input-wrap has-action"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"></path></svg><input type="password" name="sifre" placeholder="Şifrenizi girin" autocomplete="current-password" required data-password-input><button type="button" class="login-password-toggle" data-password-toggle aria-label="Şifreyi göster"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg></button></span></label>
                <label class="auth-remember"><input type="checkbox" name="beni_hatirla" value="1"<?= !empty($beniHatirla) ? ' checked' : '' ?>><span>Beni hatırla</span><small>30 gün boyunca açık tut</small></label>
                <button class="btn btn-primary login-submit" type="submit"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"></path></svg>Giriş Yap</button>
            </form>
        </div>
        <p class="login-copyright">© <?= e(date('Y')) ?> OyunEvleri.com — Tüm hakları saklıdır.</p>
    </section>
</main>

<script>
document.querySelector('[data-password-toggle]')?.addEventListener('click', function () {
    const input = document.querySelector('[data-password-input]');
    if (!input) return;
    const visible = input.type === 'text';
    input.type = visible ? 'password' : 'text';
    this.setAttribute('aria-label', visible ? 'Şifreyi göster' : 'Şifreyi gizle');
    this.classList.toggle('is-visible', !visible);
});
</script>
