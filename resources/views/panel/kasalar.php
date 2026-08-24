<section class="page-head">
    <div>
        <h1>Kasalar</h1>
        <p>Nakit kasa, banka hesabi ve altin hesaplarinizi tanimlayin.</p>
    </div>
    <div class="appointment-toolbar-actions">
        <button class="btn btn-ghost" type="button" data-open-dialog="#kasa-hareket-dialog">Para Giris/Cikis</button>
        <button class="btn btn-primary" type="button" data-open-dialog="#kasa-dialog">Kasa Ekle</button>
    </div>
</section>

<section class="panel-grid single-wide" data-kasa-page>
    <article class="panel-card">
        <div class="expense-summary" data-cashbox-summary></div>
        <div class="definition-head">
            <div>
                <h2>Kasa Listesi</h2>
                <p class="section-helper">Bakiye, tahsilat ve odemesi yapilmis giderlere gore hesaplanir.</p>
            </div>
            <button class="btn btn-sky" type="button" data-open-dialog="#kasa-dialog">Yeni Kasa</button>
        </div>
        <div id="kasa-tablosu" class="table-wrap" data-cashbox-table></div>
    </article>

    <article class="panel-card">
        <div class="definition-head">
            <div>
                <h2>Kasa Hareket Gecmisi</h2>
                <p class="section-helper">Manuel giris/cikislar, kasaya aktarilan tahsilatlar ve odemesi yapilmis giderler.</p>
            </div>
            <button class="btn btn-sky" type="button" data-open-dialog="#kasa-hareket-dialog">Hareket Ekle</button>
        </div>
        <div id="kasa-hareket-tablosu" class="table-wrap" data-cashbox-history></div>
    </article>

    <dialog id="kasa-dialog" class="appointment-dialog payment-dialog">
        <form method="dialog" class="appointment-dialog-form" data-cashbox-form>
            <div class="dialog-head">
                <h2 data-cashbox-form-title>Kasa Ekle</h2>
                <button type="button" data-close-dialog>x</button>
            </div>
            <input type="hidden" name="id">
            <div class="dialog-grid">
                <label><span>Kasa Adi</span><input name="ad" placeholder="Nakit Kasa, Garanti Bankasi, Gram Altin..." required></label>
                <label>
                    <span>Tur</span>
                    <select name="tur" required>
                        <option value="nakit">Nakit</option>
                        <option value="banka">Banka Hesabi</option>
                        <option value="altin">Altin</option>
                        <option value="diger">Diger</option>
                    </select>
                </label>
                <label><span>Para Birimi</span><input name="para_birimi" value="TRY" maxlength="10" required></label>
                <label><span>Acilis Bakiyesi</span><input type="number" step="0.01" name="acilis_bakiyesi" value="0"></label>
                <label>
                    <span>Durum</span>
                    <select name="aktif">
                        <option value="1">Aktif</option>
                        <option value="0">Pasif</option>
                    </select>
                </label>
                <label class="dialog-wide"><span>Aciklama</span><textarea name="aciklama" rows="4"></textarea></label>
            </div>
            <div class="record-actions compact-actions">
                <span data-form-message></span>
                <button class="btn btn-ghost" type="button" data-close-dialog>Vazgec</button>
                <button class="btn btn-primary" type="submit">Kasayi Kaydet</button>
            </div>
        </form>
    </dialog>

    <dialog id="kasa-hareket-dialog" class="appointment-dialog payment-dialog">
        <form method="dialog" class="appointment-dialog-form" data-cashbox-movement-form>
            <div class="dialog-head">
                <h2>Para Giris/Cikis</h2>
                <button type="button" data-close-dialog>x</button>
            </div>
            <div class="dialog-grid">
                <label>
                    <span>Kasa</span>
                    <select name="kasa_id" required data-cashbox-select></select>
                </label>
                <label><span>Tarih</span><input type="date" name="tarih" value="<?= e(date('Y-m-d')) ?>" required></label>
                <label>
                    <span>Islem</span>
                    <select name="tur" required>
                        <option value="giris">Para Girisi</option>
                        <option value="cikis">Para Cikisi</option>
                    </select>
                </label>
                <label><span>Tutar</span><input type="number" step="0.01" name="tutar" required></label>
                <label class="dialog-wide"><span>Aciklama</span><textarea name="aciklama" rows="4"></textarea></label>
            </div>
            <div class="record-actions compact-actions">
                <span data-form-message></span>
                <button class="btn btn-ghost" type="button" data-close-dialog>Vazgec</button>
                <button class="btn btn-primary" type="submit">Hareketi Kaydet</button>
            </div>
        </form>
    </dialog>
</section>
