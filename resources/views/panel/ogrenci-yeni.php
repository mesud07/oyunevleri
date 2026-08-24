<section class="record-titlebar">
    <h1>Yeni Ogrenci Kaydi</h1>
    <div class="breadcrumb">Ogrenci Islemleri <span>›</span> Yeni Ogrenci Kaydi</div>
</section>

<form class="record-form" data-ajax-form="ogrenci_veli_ekle" data-success-redirect="/panel/ogrenciler" data-student-create-form>
    <div class="record-grid">
        <article class="record-card">
            <h2>Ogrenci Bilgileri</h2>
            <div class="record-fields">
                <label>
                    <span>Ad Soyad *</span>
                    <input name="ogrenci_ad_soyad" placeholder="Lutfen ogrenci ad ve soyadini giriniz. (Zorunlu)" required>
                </label>
                <label>
                    <span>TC Kimlik No</span>
                    <input name="ogrenci_tc_kimlik_no" inputmode="numeric" placeholder="Lutfen TC kimlik no giriniz. (Istege bagli)">
                </label>
                <label>
                    <span>Cinsiyet</span>
                    <select name="ogrenci_cinsiyet">
                        <option value="belirtilmedi">Seciniz</option>
                        <option value="kiz">Kiz</option>
                        <option value="erkek">Erkek</option>
                    </select>
                </label>
                <label>
                    <span>Dogum Tarihi</span>
                    <input type="date" name="ogrenci_dogum_tarihi" placeholder="Lutfen dogum tarihi seciniz. (Istege bagli)">
                </label>
                <label>
                    <span>Saglik Bilgisi</span>
                    <input name="saglik_bilgisi" placeholder="Lutfen saglik bilgisi giriniz. (Istege bagli)">
                </label>
                <label>
                    <span>Alerji Bilgisi</span>
                    <input name="alerji_bilgisi" placeholder="Lutfen alerji bilgisi giriniz. (Istege bagli)">
                </label>
                <label class="textarea-row">
                    <span>Aciklama</span>
                    <textarea name="ogrenci_aciklama" maxlength="250" rows="5" placeholder="Ogrenciye ait aciklama yazabilirsiniz. (Istege bagli)"></textarea>
                    <small>Aciklama en fazla 250 karakter olabilir.</small>
                </label>
            </div>
            <p class="required-note">* ile isaretli alanlarin doldurulmasi zorunludur.</p>
        </article>

        <article class="record-card">
            <h2>Veli ve Iletisim Bilgileri</h2>
            <div class="record-fields">
                <label>
                    <span>Veli Ad Soyad *</span>
                    <input name="veli_ad_soyad" placeholder="Lutfen veli ad ve soyadini giriniz. (Zorunlu)" required>
                </label>
                <label>
                    <span>Veli TC Kimlik No</span>
                    <input name="veli_tc_kimlik_no" inputmode="numeric" placeholder="Lutfen veli TC kimlik no giriniz. (Istege bagli)">
                </label>
                <label>
                    <span>Yakinlik</span>
                    <select name="veli_yakinlik">
                        <option value="">Seciniz</option>
                        <option value="Anne">Anne</option>
                        <option value="Baba">Baba</option>
                        <option value="Vasi">Vasi</option>
                        <option value="Diger">Diger</option>
                    </select>
                </label>
                <label>
                    <span>Telefon Ulke</span>
                    <input name="telefon_ulke" value="Turkiye">
                </label>
                <label>
                    <span>Telefon No *</span>
                    <input name="veli_telefon" inputmode="tel" maxlength="16" data-phone-mask placeholder="0(537) 495 83 06" required>
                </label>
                <label>
                    <span>Yedek Telefon No</span>
                    <input name="veli_yedek_telefon" inputmode="tel" maxlength="16" data-phone-mask placeholder="0(537) 495 83 06">
                </label>
                <label>
                    <span>E-Posta Adresi</span>
                    <input type="email" name="veli_eposta" placeholder="Lutfen e-posta adresi giriniz. (Istege bagli)">
                </label>
                <label>
                    <span>Bizimle kimin aracılığıyla iletişime geçtiniz?</span>
                    <input name="veli_iletisim_referansi" maxlength="190" placeholder="Örn. Ayşe Hanım, Instagram, Google...">
                </label>
                <label>
                    <span>Il</span>
                    <select name="il" data-city-select>
                        <option value="">Seciniz</option>
                        <option value="Antalya">Antalya</option>
                    </select>
                </label>
                <label>
                    <span>Ilce</span>
                    <select name="ilce" data-district-select disabled>
                        <option value="">Once il seciniz.</option>
                    </select>
                </label>
                <label class="textarea-row">
                    <span>Adres</span>
                    <textarea name="adres" maxlength="250" rows="4" placeholder="Lutfen adres bilgisi giriniz. (Istege bagli)"></textarea>
                    <small>Adres en fazla 250 karakter olabilir.</small>
                </label>
            </div>
            <p class="required-note">* ile isaretli alanlarin doldurulmasi zorunludur.</p>
        </article>

        <article class="record-card">
            <h2>Vasi Bilgileri</h2>
            <div class="record-fields">
                <label>
                    <span>Vasi Ad Soyad</span>
                    <input name="vasi_ad_soyad" placeholder="Lutfen vasi ad ve soyad giriniz.">
                </label>
                <label>
                    <span>Vasi TC Kimlik No</span>
                    <input name="vasi_tc_kimlik_no" inputmode="numeric" placeholder="Lutfen vasi TC kimlik no giriniz.">
                </label>
                <label>
                    <span>Vasi Telefon No</span>
                    <input name="vasi_telefon" inputmode="tel" maxlength="16" data-phone-mask placeholder="0(537) 495 83 06">
                </label>
            </div>
        </article>
    </div>

    <div class="record-actions">
        <span data-form-message></span>
        <a class="btn btn-ghost" href="/panel/ogrenciler">Vazgec</a>
        <button class="btn btn-primary" type="submit">Ogrenciyi Kaydet</button>
    </div>
</form>

<dialog class="appointment-dialog duplicate-student-dialog" data-duplicate-student-dialog>
    <div class="appointment-dialog-form">
        <div class="dialog-head">
            <div>
                <h2>Bu ogrencimiz kayitli</h2>
                <p>Ayni telefon numarasiyla eslesen mevcut kayit bulundu.</p>
            </div>
            <button type="button" data-duplicate-student-close>x</button>
        </div>
        <div class="duplicate-student-list" data-duplicate-student-list></div>
        <div class="record-actions compact-actions">
            <span>Yeni kayit olusturulamaz. Mevcut ogrenci profilinden devam edin.</span>
            <button class="btn btn-ghost" type="button" data-duplicate-student-close>Kapat</button>
        </div>
    </div>
</dialog>
