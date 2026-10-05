<section class="page-head">
    <div>
        <h1>Ogrenciler</h1>
        <p>Ogrenci kayitlari ve detaylari.</p>
    </div>
    <?php if (yetki_var('ogrenci_ekle')) : ?>
        <div class="head-actions">
            <a class="btn btn-ghost" href="/panel/ogrenciler/import-sablonu.xlsx">Excel Şablonunu İndir</a>
            <button class="btn btn-sky" type="button" data-open-student-import>Toplu Öğrenci Aktar</button>
            <a class="btn btn-primary" href="/panel/ogrenciler/yeni">Yeni Ogrenci Kaydi</a>
        </div>
    <?php endif; ?>
</section>

<section class="panel-grid single-wide">
    <article class="panel-card">
        <div class="appointment-toolbar">
            <div>
                <h2>Ogrenci Listesi</h2>
                <p>Isim veya telefon numarasina gore arama yapabilirsiniz.</p>
            </div>
            <label class="table-search">
                <span>Arama</span>
                <input type="search" data-student-search placeholder="Isim veya telefon ara">
            </label>
        </div>
        <div id="ogrenci-tablosu" class="table-wrap" data-table="ogrenci_listele" data-can-edit-student="<?= yetki_var('ogrenci_ekle') ? '1' : '0' ?>"></div>
    </article>
</section>

<?php if (yetki_var('ogrenci_ekle')) : ?>
<dialog class="appointment-dialog" id="ogrenci-toplu-import-dialog">
    <form
        class="appointment-dialog-form"
        method="post"
        action="/panel/ogrenciler/import"
        enctype="multipart/form-data"
        data-student-import-form
    >
        <div class="dialog-head">
            <div><h2>Toplu Öğrenci Aktarımı</h2><p>Şablonu doldurun ve .xlsx dosyasını yükleyin.</p></div>
            <button type="button" data-close-dialog aria-label="Kapat">x</button>
        </div>
        <input type="hidden" name="csrf" value="<?= e($csrf ?? '') ?>">
        <div class="import-instructions">
            <strong>Aktarım adımları</strong>
            <ol>
                <li><a href="/panel/ogrenciler/import-sablonu.xlsx">Excel şablonunu indirin.</a></li>
                <li>Öğrenciler sayfasında her öğrenci için bir satır doldurun.</li>
                <li>Başlıkları değiştirmeden dosyayı .xlsx olarak kaydedip yükleyin.</li>
            </ol>
            <p>Yıldızlı sütunlar zorunludur. Aynı veli telefonuyla kardeş öğrenciler eklenebilir. Sistemde bulunan öğrenciler yeniden oluşturulmaz.</p>
        </div>
        <label class="file-upload-field">
            <span>Excel Dosyası</span>
            <input type="file" name="excel_dosyasi" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
            <small>En fazla 5 MB ve 500 öğrenci.</small>
        </label>
        <div class="import-result" data-student-import-result aria-live="polite"></div>
        <div class="record-actions compact-actions">
            <button class="btn btn-ghost" type="button" data-close-dialog>Vazgeç</button>
            <button class="btn btn-primary" type="submit">Öğrencileri Aktar</button>
        </div>
    </form>
</dialog>

<dialog class="appointment-dialog" id="ogrenci-hizli-duzenle-dialog">
    <form
        class="appointment-dialog-form"
        data-ajax-form="ogrenci_hizli_guncelle"
        data-refresh="ogrenci_listele"
        data-target="#ogrenci-tablosu"
        data-student-quick-edit-form
    >
        <div class="dialog-head">
            <div><h2>Hizli Bilgi Duzenleme</h2><p>Listeye donmeden temel ogrenci ve veli bilgilerini guncelleyin.</p></div>
            <button type="button" data-close-dialog aria-label="Kapat">x</button>
        </div>
        <input type="hidden" name="id">
        <input type="hidden" name="veli_id">
        <div class="dialog-grid">
            <label><span>Ogrenci Adi</span><input name="ogrenci_ad" required></label>
            <label><span>Ogrenci Soyadi</span><input name="ogrenci_soyad" required></label>
            <label><span>Dogum Tarihi</span><input type="date" name="ogrenci_dogum_tarihi"></label>
            <label><span>Kayit Tarihi</span><input type="date" name="ogrenci_kayit_tarihi"></label>
            <label><span>Cinsiyet</span><select name="ogrenci_cinsiyet"><option value="belirtilmedi">Belirtilmedi</option><option value="kiz">Kiz</option><option value="erkek">Erkek</option></select></label>
            <label><span>Durum</span><select name="ogrenci_durum"><option value="aktif">Aktif</option><option value="pasif">Pasif</option></select></label>
            <label><span>Ogrencinin Ili</span><input name="ogrenci_il" value="Antalya"></label>
            <label>
                <span>Ogrencinin Ilcesi</span>
                <select name="ogrenci_ilce">
                    <option value="">Seciniz</option>
                    <?php foreach (['Akseki', 'Aksu', 'Alanya', 'Demre', 'Döşemealtı', 'Elmalı', 'Finike', 'Gazipaşa', 'Gündoğmuş', 'İbradı', 'Kaş', 'Kemer', 'Kepez', 'Konyaaltı', 'Korkuteli', 'Kumluca', 'Manavgat', 'Muratpaşa', 'Serik'] as $ilce) : ?>
                        <option value="<?= e($ilce) ?>"><?= e($ilce) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="dialog-wide"><span>Ogrencinin Ev Adresi</span><textarea name="ogrenci_adres" rows="3" maxlength="500"></textarea></label>
            <label><span>Veli Adi</span><input name="veli_ad"></label>
            <label><span>Veli Soyadi</span><input name="veli_soyad"></label>
            <label class="dialog-wide"><span>Veli Telefonu</span><input name="veli_telefon" data-phone-mask maxlength="16" inputmode="tel"></label>
        </div>
        <div class="record-actions compact-actions">
            <span data-form-message aria-live="polite"></span>
            <button class="btn btn-ghost" type="button" data-close-dialog>Vazgec</button>
            <button class="btn btn-primary" type="submit">Bilgileri Kaydet</button>
        </div>
    </form>
</dialog>
<?php endif; ?>
