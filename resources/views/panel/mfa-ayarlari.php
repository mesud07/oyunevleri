<section class="page-header"><div><h1>Hesap Güvenliği</h1><p>Yönetici ve muhasebe işlemleri için iki aşamalı doğrulama.</p></div></section>
<section class="panel-card form-stack">
    <?php if (!empty($hata)) : ?><div class="alert alert-error"><?= e($hata) ?></div><?php endif; ?>
    <?php if (!empty($recoveryCodes)) : ?>
        <div class="alert"><strong>Kurtarma kodlarını şimdi güvenli bir yere kaydedin. Bir daha gösterilmeyecekler.</strong><pre><?= e(implode("\n", $recoveryCodes)) ?></pre></div>
    <?php elseif ((int) ($kullanici['mfa_enabled'] ?? 0) === 1) : ?>
        <div class="alert alert-success">İki aşamalı doğrulama hesabınızda etkin.</div>
    <?php else : ?>
        <h2>Doğrulama uygulamasını bağlayın</h2>
        <p>Google Authenticator, Microsoft Authenticator veya uyumlu bir uygulamada aşağıdaki anahtarı manuel olarak ekleyin.</p>
        <label><span>Kurulum Anahtarı</span><input type="text" readonly value="<?= e($secret) ?>"></label>
        <details><summary>Teknik kurulum bağlantısı</summary><code><?= e($otpauth) ?></code></details>
        <form method="post" action="/panel/guvenlik/mfa" class="form-stack">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <label><span>Uygulamadaki 6 Haneli Kod</span><input type="text" name="kod" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required></label>
            <button class="btn btn-primary" type="submit">MFA'yı Etkinleştir</button>
        </form>
    <?php endif; ?>
</section>
