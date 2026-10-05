<main class="auth-card">
    <div class="auth-logo">Oyun Evleri</div>
    <h1>İki aşamalı doğrulama</h1>
    <p>Doğrulama uygulamanızdaki 6 haneli kodu veya tek kullanımlık kurtarma kodunuzu girin.</p>
    <?php if (!empty($hata)) : ?><div class="alert alert-error"><?= e($hata) ?></div><?php endif; ?>
    <form method="post" action="/mfa" class="form-stack">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <label><span>Doğrulama Kodu</span><input type="text" name="kod" autocomplete="one-time-code" required autofocus maxlength="16"></label>
        <button class="btn btn-primary" type="submit">Doğrula</button>
    </form>
</main>
