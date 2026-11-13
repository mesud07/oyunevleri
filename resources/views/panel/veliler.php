<section class="record-titlebar address-book-titlebar">
    <div>
        <h1>Adres Defteri</h1>
        <p>Velilerin iletişim, adres ve bağlı öğrenci bilgilerini tek ekrandan yönetin.</p>
    </div>
    <?php if (yetki_var('veli_ekle')) : ?>
        <button class="btn btn-primary" type="button" data-address-book-new>Yeni Kişi</button>
    <?php endif; ?>
</section>

<section class="address-book-page" data-address-book-page data-can-manage="<?= yetki_var('veli_ekle') ? '1' : '0' ?>" data-can-sms="<?= yetki_var('sms_gonder') ? '1' : '0' ?>">
    <div class="address-book-summary">
        <article><span>Toplam Kişi</span><strong data-address-count>0</strong></article>
        <article><span>Telefonu Kayıtlı</span><strong data-address-phone-count>0</strong></article>
        <article><span>E-postası Kayıtlı</span><strong data-address-email-count>0</strong></article>
        <article><span>Adresi Kayıtlı</span><strong data-address-location-count>0</strong></article>
    </div>

    <article class="panel-card address-book-card">
        <div class="address-book-toolbar">
            <label class="address-book-search"><span>Ara</span><input type="search" placeholder="Ad, telefon, öğrenci veya adres ara" data-address-search></label>
            <label><span>Yakınlık</span><select data-address-relation><option value="">Tüm yakınlıklar</option></select></label>
            <label><span>Bilgi durumu</span><select data-address-completeness><option value="">Tüm kayıtlar</option><option value="complete">İletişim bilgisi tam</option><option value="missing">Eksik bilgili</option></select></label>
            <button class="btn btn-ghost" type="button" data-address-clear>Filtreyi Temizle</button>
        </div>
        <div class="address-book-result-note" data-address-result-note>Kayıtlar yükleniyor...</div>
        <div class="table-wrap address-book-table" data-address-book-table></div>
    </article>
</section>

<?php if (yetki_var('veli_ekle')) : ?>
<dialog class="appointment-dialog address-book-dialog" data-address-book-dialog>
    <form method="dialog" class="appointment-dialog-form" data-address-book-form>
        <div class="dialog-head">
            <div><h2 data-address-dialog-title>Yeni Kişi</h2><p>İletişim ve adres bilgilerini kaydedin.</p></div>
            <button type="button" data-address-dialog-close>x</button>
        </div>
        <input type="hidden" name="id" value="0">
        <div class="dialog-grid">
            <label><span>Ad</span><input name="ad" maxlength="100" required></label>
            <label><span>Soyad</span><input name="soyad" maxlength="100" required></label>
            <label><span>Yakınlık</span><input name="yakinlik" maxlength="50" placeholder="Anne, baba, vasi..."></label>
            <label><span>Telefon</span><input name="telefon" data-phone-mask maxlength="20" required></label>
            <label><span>Yedek Telefon</span><input name="yedek_telefon" data-phone-mask maxlength="20"></label>
            <label><span>E-posta</span><input type="email" name="eposta" maxlength="190"></label>
            <input type="hidden" name="telefon_ulke" value="Türkiye (+90)">
            <label><span>İl</span><input name="il" maxlength="100" value="Antalya"></label>
            <label><span>İlçe</span><input name="ilce" maxlength="100"></label>
            <label class="dialog-wide"><span>Açık Adres</span><textarea name="adres" rows="3"></textarea></label>
            <label class="dialog-wide"><span>İletişim Kaynağı</span><input name="iletisim_referansi" maxlength="190" placeholder="Örn. Instagram, Google, Ayşe Hanım..."></label>
            <label class="dialog-wide"><span>Notlar</span><textarea name="notlar" rows="3"></textarea></label>
        </div>
        <div class="record-actions compact-actions">
            <span data-address-form-message></span>
            <button class="btn btn-ghost" type="button" data-address-dialog-close>Vazgeç</button>
            <button class="btn btn-primary" type="submit">Kaydet</button>
        </div>
    </form>
</dialog>
<?php endif; ?>
