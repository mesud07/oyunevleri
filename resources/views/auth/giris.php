<main class="auth-card">
    <div class="auth-logo">Oyun Evleri Yönetim Sistemi</div>
    <h1>Yonetim paneline giris</h1>
    <p>Ogrenci, paket, randevu ve tahsilat sureclerini yonetin.</p>

    <?php if (!empty($hata)) : ?>
        <div class="alert alert-error"><?= e($hata) ?></div>
    <?php endif; ?>

    <form method="post" action="/giris" class="form-stack">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <label>
            <span>Kurum Kodu</span>
            <input type="text" name="kurum_kodu" value="TALYA" autocomplete="organization" required>
        </label>
        <label>
            <span>Kullanici Adi</span>
            <input type="text" name="eposta" autocomplete="username" required autofocus>
        </label>
        <label>
            <span>Sifre</span>
            <input type="password" name="sifre" autocomplete="current-password" required>
        </label>
        <button class="btn btn-primary" type="submit">Giris Yap</button>
    </form>

    <a class="auth-public-link" href="/veli-portal">Veli Bilgi Ekrani</a>
</main>
