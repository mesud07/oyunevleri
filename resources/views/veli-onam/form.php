<?php
$degerler = $degerler ?? [];
$ek = $degerler['ek_bilgiler'] ?? [];
$secili = static fn(string $alan, string $deger): string => (($ek[$alan] ?? '') === $deger) ? 'checked' : '';
$antalyaIlceleri = [
    'Akseki', 'Aksu', 'Alanya', 'Demre', 'Döşemealtı', 'Elmalı', 'Finike',
    'Gazipaşa', 'Gündoğmuş', 'İbradı', 'Kaş', 'Kemer', 'Kepez', 'Konyaaltı',
    'Korkuteli', 'Kumluca', 'Manavgat', 'Muratpaşa', 'Serik',
];
?>
<main class="consent-public-shell">
    <header class="consent-public-hero">
        <span class="parent-brand"><?= e($ayar['kurum_adi'] ?? 'Oyun Evleri') ?></span>
        <h1><?= e($ayar['baslik'] ?? 'Oyun Grubu Katılımcı Bilgi ve Veli Onam Formu') ?></h1>
        <?php if (!empty($ayar['aciklama'])) : ?><p><?= e($ayar['aciklama']) ?></p><?php endif; ?>
    </header>

    <?php if ($basarili) : ?>
        <section class="panel-card consent-success-card">
            <span aria-hidden="true">✓</span><h2>Formunuz alındı</h2>
            <p>Bilgileriniz ve izin tercihleriniz güvenli biçimde kaydedildi. Telefon numaranız sistemde kayıtlıysa mevcut veli hesabınızla otomatik eşleştirildi.</p>
        </section>
    <?php elseif (!$ayar) : ?>
        <section class="panel-card consent-success-card is-error"><h2>Form bulunamadı</h2><p>QR kodunu yeniden okutun veya Oyun Evleri ile iletişime geçin.</p></section>
    <?php else : ?>
        <form class="panel-card consent-public-form" method="post" action="/oyun-grubu-onam">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="token" value="<?= e($ayar['public_token']) ?>">
            <?php if ($hata) : ?><div class="alert alert-error" role="alert"><?= e($hata) ?></div><?php endif; ?>

            <section>
                <div class="consent-section-title"><span>1</span><div><h2>Çocuğa Ait Bilgiler</h2><p>Oyun grubuna katılacak çocuğun bilgileri</p></div></div>
                <div class="consent-form-grid">
                    <label><span>Adı Soyadı *</span><input name="ogrenci_ad_soyad" maxlength="190" value="<?= e($degerler['ogrenci_ad_soyad'] ?? '') ?>" required></label>
                    <label><span>Doğum Tarihi</span><input type="date" name="ogrenci_dogum_tarihi" max="<?= e(date('Y-m-d')) ?>" value="<?= e($degerler['ogrenci_dogum_tarihi'] ?? '') ?>"></label>
                </div>
            </section>

            <section>
                <div class="consent-section-title"><span>2</span><div><h2>Veli Bilgileri</h2><p>Cep telefonu mevcut veli kaydıyla eşleştirme için kullanılır.</p></div></div>
                <div class="consent-form-grid">
                    <label><span>Onay Veren Veli Ad Soyad *</span><input name="veli_ad_soyad" maxlength="190" autocomplete="name" value="<?= e($degerler['veli_ad_soyad'] ?? '') ?>" required></label>
                    <label><span>Cep Telefonu *</span><input type="tel" name="veli_telefon" maxlength="20" inputmode="tel" autocomplete="tel" placeholder="0(5__) ___ __ __" value="<?= e($degerler['veli_telefon'] ?? '') ?>" required></label>
                    <label><span>Anne Adı Soyadı</span><input name="anne_ad_soyad" maxlength="190" value="<?= e($ek['anne_ad_soyad'] ?? '') ?>"></label>
                    <label><span>Baba Adı Soyadı</span><input name="baba_ad_soyad" maxlength="190" value="<?= e($ek['baba_ad_soyad'] ?? '') ?>"></label>
                    <label class="full"><span>E-posta</span><input type="email" name="veli_eposta" maxlength="190" autocomplete="email" value="<?= e($degerler['veli_eposta'] ?? '') ?>"></label>
                    <label><span>İl</span><select name="il"><option value="Antalya" selected>Antalya</option></select></label>
                    <label><span>İlçe</span><select name="ilce"><option value="">İlçe seçin</option><?php foreach ($antalyaIlceleri as $ilce) : ?><option value="<?= e($ilce) ?>" <?= (($ek['ilce'] ?? '') === $ilce) ? 'selected' : '' ?>><?= e($ilce) ?></option><?php endforeach; ?></select></label>
                    <label class="full"><span>Açık Adres</span><textarea name="ev_adresi" rows="3" maxlength="1000" placeholder="Mahalle, cadde/sokak, bina ve daire bilgisi"><?= e($ek['ev_adresi'] ?? '') ?></textarea></label>
                </div>
                <div class="consent-question">
                    <fieldset><legend>Daha önce herhangi bir uzman desteği aldı mı? *</legend><label><input type="radio" name="uzman_destegi" value="hayir" <?= $secili('uzman_destegi', 'hayir') ?> required> Hayır</label><label><input type="radio" name="uzman_destegi" value="evet" <?= $secili('uzman_destegi', 'evet') ?>> Evet</label></fieldset>
                    <label><span>Evet ise açıklama</span><input name="uzman_aciklama" maxlength="500" value="<?= e($ek['uzman_aciklama'] ?? '') ?>"></label>
                </div>
                <div class="consent-question">
                    <fieldset><legend>Daha önce oyun grubu deneyimi var mı? *</legend><label><input type="radio" name="oyun_grubu_deneyimi" value="hayir" <?= $secili('oyun_grubu_deneyimi', 'hayir') ?> required> Hayır</label><label><input type="radio" name="oyun_grubu_deneyimi" value="evet" <?= $secili('oyun_grubu_deneyimi', 'evet') ?>> Evet</label></fieldset>
                    <label><span>Açıklama</span><input name="oyun_grubu_aciklama" maxlength="500" value="<?= e($ek['oyun_grubu_aciklama'] ?? '') ?>"></label>
                </div>
            </section>

            <section>
                <div class="consent-section-title"><span>3</span><div><h2>Sağlık Bilgileri</h2><p>Çocuğun güvenliği için güncel bilgileri paylaşınız.</p></div></div>
                <div class="consent-question">
                    <fieldset><legend>Herhangi bir tanı / gelişimsel farklılık var mı? *</legend><label><input type="radio" name="tani_durumu" value="yok" <?= $secili('tani_durumu', 'yok') ?> required> Yok</label><label><input type="radio" name="tani_durumu" value="var" <?= $secili('tani_durumu', 'var') ?>> Var</label></fieldset>
                    <label><span>Var ise açıklama</span><input name="tani_aciklama" maxlength="500" value="<?= e($ek['tani_aciklama'] ?? '') ?>"></label>
                </div>
                <div class="consent-question">
                    <fieldset><legend>Düzenli kullanılan ilaç var mı? *</legend><label><input type="radio" name="ilac_durumu" value="yok" <?= $secili('ilac_durumu', 'yok') ?> required> Yok</label><label><input type="radio" name="ilac_durumu" value="var" <?= $secili('ilac_durumu', 'var') ?>> Var</label></fieldset>
                    <label><span>Var ise açıklama</span><input name="ilac_aciklama" maxlength="500" value="<?= e($ek['ilac_aciklama'] ?? '') ?>"></label>
                </div>
                <div class="consent-form-grid">
                    <label class="full"><span>Alerji Durumu (gıda, ilaç, temas vb.)</span><textarea name="alerji_durumu" rows="3" maxlength="1000"><?= e($ek['alerji_durumu'] ?? '') ?></textarea></label>
                    <label class="full"><span>Özel Sağlık Durumu (astım, epilepsi, diyabet vb.)</span><textarea name="ozel_saglik_durumu" rows="3" maxlength="1000"><?= e($ek['ozel_saglik_durumu'] ?? '') ?></textarea></label>
                </div>
            </section>

            <section>
                <div class="consent-section-title"><span>4</span><div><h2>Görsel Kayıt İzni</h2><p>Her izni birbirinden bağımsız olarak verebilirsiniz.</p></div></div>
                <label class="consent-option"><input type="checkbox" name="gorsel_kayit_izni" value="1" <?= !empty($ek['gorsel_kayit_izni']) ? 'checked' : '' ?>><span>Etkinlik sırasında çocuğumun fotoğraf/video çekilmesine izin veriyorum.</span></label>
                <label class="consent-option"><input type="checkbox" name="sosyal_medya_izni" value="1" <?= !empty($ek['sosyal_medya_izni']) ? 'checked' : '' ?>><span>Çocuğuma ait fotoğraf/video içeriklerinin sosyal medya ve paylaşımlarda kullanılmasına izin veriyorum.</span></label>
                <p class="consent-permission-note">İşaretlenmeyen seçenek için izin verilmemiş kabul edilir.</p>
            </section>

            <section>
                <div class="consent-section-title"><span>5</span><div><h2>Program Kuralları ve Katılım Onamı</h2><p>Gönderim anındaki metin form kaydıyla birlikte saklanır.</p></div></div>
                <div class="consent-text"><?= nl2br(e($ayar['onam_metni'] ?? '')) ?></div>
                <label class="consent-approval"><input type="checkbox" name="program_kurallari_kabul" value="1" <?= !empty($ek['program_kurallari_kabul']) ? 'checked' : '' ?> required><span>Program kurallarını ve devam/telafi koşullarını okudum, kabul ediyorum.</span></label>
                <label class="consent-approval"><input type="checkbox" name="dijital_onay" value="1" required><span>Yukarıdaki bilgilendirme ve katılım onamını okudum. Çocuğumun oyun grubu çalışmalarına katılmasını kabul ediyor, verdiğim bilgilerin doğru olduğunu beyan ediyor ve veli olarak dijital onay veriyorum.</span></label>
            </section>

            <button class="btn btn-primary consent-submit" type="submit">Formu Onayla ve Gönder</button>
            <small class="consent-privacy">Onay tarihi, form sürümü ve güvenlik doğrulama bilgileri kayıt altına alınır.</small>
        </form>
    <?php endif; ?>
</main>
