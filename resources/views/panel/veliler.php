<section class="page-head">
    <div>
        <h1>Veliler</h1>
        <p>Veli iletisim ve iliski kayitlari.</p>
    </div>
</section>

<section class="panel-grid single-wide">
    <article class="panel-card">
        <h2>Yeni Veli</h2>
        <form class="form-grid" data-ajax-form="veli_ekle" data-refresh="veli_listele" data-target="#veli-tablosu">
            <label><span>Ad</span><input name="ad" required></label>
            <label><span>Soyad</span><input name="soyad" required></label>
            <label><span>Telefon</span><input name="telefon" required></label>
            <label><span>E-posta</span><input type="email" name="eposta"></label>
            <label><span>Yakinlik</span><input name="yakinlik" placeholder="Anne, baba..."></label>
            <label><span>Adres</span><input name="adres"></label>
            <label class="full"><span>Bizimle kimin aracılığıyla iletişime geçtiniz?</span><input name="iletisim_referansi" maxlength="190" placeholder="Örn. Ayşe Hanım, Instagram, Google..."></label>
            <label class="full"><span>Notlar</span><textarea name="notlar" rows="3"></textarea></label>
            <div class="form-actions full">
                <button class="btn btn-primary" type="submit">Veli Kaydet</button>
                <span data-form-message></span>
            </div>
        </form>
    </article>

    <article class="panel-card">
        <h2>Veli Listesi</h2>
        <div id="veli-tablosu" class="table-wrap" data-table="veli_listele"></div>
    </article>
</section>
