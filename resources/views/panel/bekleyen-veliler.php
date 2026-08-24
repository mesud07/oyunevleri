<section class="page-head">
    <div>
        <h1>Bekleyen Veliler</h1>
        <p>Grup kontenjani bekleyen aday ogrenci ve veli talepleri.</p>
    </div>
    <button class="btn btn-primary" type="button" data-open-dialog="#bekleyen-veli-dialog">Bekleyen Veli Ekle</button>
</section>

<section class="panel-grid single-wide">
    <article class="panel-card">
        <div class="appointment-toolbar">
            <div>
                <h2>Bekleyen Veli Listesi</h2>
                <p>Isim, telefon, gun veya ay grubuna gore arama yapabilirsiniz.</p>
            </div>
            <label class="table-search">
                <span>Arama</span>
                <input type="search" data-waiting-parent-search placeholder="Isim, telefon, gun ara">
            </label>
        </div>
        <div id="bekleyen-veli-tablosu" class="table-wrap" data-table="bekleyen_veli_listele"></div>
    </article>
</section>

<dialog class="appointment-dialog dialog-wide" id="bekleyen-veli-dialog">
    <div class="dialog-head">
        <h2>Bekleyen Veli Ekle</h2>
        <button type="button" class="dialog-close" data-close-dialog aria-label="Kapat">x</button>
    </div>
    <form class="appointment-dialog-form form-grid" data-ajax-form="bekleyen_veli_ekle" data-refresh="bekleyen_veli_listele" data-target="#bekleyen-veli-tablosu">
        <label>
            <span>Ogrenci Ad Soyad *</span>
            <input name="ogrenci_ad_soyad" placeholder="Ogrenci ad soyad" required>
        </label>
        <label>
            <span>Dogum Tarihi</span>
            <input type="date" name="ogrenci_dogum_tarihi">
        </label>
        <label>
            <span>Veli Ad Soyad *</span>
            <input name="veli_ad_soyad" placeholder="Veli ad soyad" required>
        </label>
        <label>
            <span>Telefon *</span>
            <input name="veli_telefon" inputmode="tel" maxlength="16" data-phone-mask placeholder="0(537) 495 83 06" required>
        </label>
        <label>
            <span>E-posta</span>
            <input type="email" name="veli_eposta">
        </label>
        <label>
            <span>Bekledigi Gun</span>
            <select name="beklenen_gun">
                <option value="">Seciniz</option>
                <option value="Pazartesi">Pazartesi</option>
                <option value="Sali">Sali</option>
                <option value="Carsamba">Carsamba</option>
                <option value="Persembe">Persembe</option>
                <option value="Cuma">Cuma</option>
                <option value="Cumartesi">Cumartesi</option>
                <option value="Pazar">Pazar</option>
            </select>
        </label>
        <label>
            <span>Ay Grubu</span>
            <input name="ay_grubu" placeholder="25-36 Ay, Ilk Adim Grubu...">
        </label>
        <label>
            <span>Bizimle kimin aracılığıyla iletişime geçtiniz?</span>
            <input name="iletisim_referansi" maxlength="190" placeholder="Örn. Ayşe Hanım, Instagram, Google...">
        </label>
        <label>
            <span>Zaman Tercihi</span>
            <select name="zaman_tercihi">
                <option value="farketmez">Fark etmez</option>
                <option value="hafta_ici">Hafta ici</option>
                <option value="hafta_sonu">Hafta sonu</option>
            </select>
        </label>
        <label class="full">
            <span>Not</span>
            <textarea name="notlar" rows="3" maxlength="500" placeholder="Bekledigi saat, uygun olmadigi gunler veya gorusme notu"></textarea>
        </label>
        <div class="form-actions full">
            <button class="btn btn-secondary" type="button" data-close-dialog>Vazgec</button>
            <button class="btn btn-primary" type="submit">Listeye Ekle</button>
            <span data-form-message></span>
        </div>
    </form>
</dialog>
