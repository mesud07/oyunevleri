<section class="page-head">
    <div>
        <h1>Yapilacak Odemeler</h1>
        <p>Ileri tarihli giderlerinizi borclandirin ve odeme planinizi takip edin.</p>
    </div>
    <button class="btn btn-primary" type="button" data-open-dialog="#gider-dialog">Gider Ekle</button>
</section>

<section class="panel-grid single-wide">
    <article class="panel-card">
        <div class="definition-head">
            <div>
                <h2>Yapilacak Odemeler</h2>
                <p class="section-helper">Gecikmis, bugun, 7 gun ve 30 gun icindeki planlanmis giderler.</p>
            </div>
            <button class="btn btn-sky" type="button" data-open-dialog="#gider-dialog">Gider Ekle</button>
        </div>
        <div class="expense-summary" data-expense-summary></div>
        <div class="expense-filter-bar" data-expense-date-filter data-default-period="bu_ay">
            <div class="expense-filter-presets">
                <button class="btn btn-ghost is-active" type="button" data-expense-period="bu_ay">Bu Ay</button>
                <button class="btn btn-ghost" type="button" data-expense-period="sonraki_ay">Sonraki Ay</button>
            </div>
            <label>
                <span>Baslangic</span>
                <input type="date" data-expense-start>
            </label>
            <label>
                <span>Bitis</span>
                <input type="date" data-expense-end>
            </label>
            <button class="btn btn-sky" type="button" data-expense-apply>Uygula</button>
        </div>
        <div class="expense-insights" data-expense-insights></div>
        <div class="expense-toolbar">
            <label class="expense-search">
                <span>Gider Ara</span>
                <input type="search" data-expense-search placeholder="Tedarikci, kategori, aciklama ara">
            </label>
        </div>
        <div id="gider-tablosu" class="table-wrap" data-expense-table></div>
    </article>
</section>

<dialog id="gider-dialog" class="appointment-dialog payment-dialog">
    <form method="dialog" class="appointment-dialog-form" data-ajax-form="gider_ekle" data-success-redirect="/panel/odemeler/giderler">
        <div class="dialog-head">
            <h2>Gider Ekle</h2>
            <button type="button" data-close-dialog>x</button>
        </div>
        <div class="info-box compact-info">
            <strong>Bilgilendirme</strong>
            <p>Ileri tarihli giderleri planlandi olarak ekleyin. Odeme gerceklestiginde listeden odendi olarak isaretleyebilirsiniz.</p>
        </div>
        <div class="dialog-grid">
            <label><span>Tarih</span><input type="date" name="tarih" value="<?= e(date('Y-m-d')) ?>" required></label>
            <label><span>Tedarikci</span><input name="tedarikci" placeholder="Personel adi, tedarikci, kira..." required></label>
            <label>
                <span>Kategori</span>
                <select name="kategori" data-expense-category-select>
                    <option value="">Kategori secin</option>
                    <?php foreach (($giderKategorileri ?? []) as $kategori) : ?>
                        <option value="<?= e($kategori) ?>"><?= e($kategori) ?></option>
                    <?php endforeach; ?>
                    <option value="__new">+ Yeni kategori ekle</option>
                </select>
            </label>
            <label data-expense-category-new-wrap hidden>
                <span>Yeni Kategori</span>
                <input name="yeni_kategori" placeholder="Kategori adini yazin">
            </label>
            <label><span>Tutar</span><input type="number" step="0.01" name="tutar" required></label>
            <label>
                <span>Odeme Turu</span>
                <select name="odeme_turu" required>
                    <option value="nakit">Nakit</option>
                    <option value="kredi_karti">Kredi Karti</option>
                    <option value="banka_havalesi">Banka Havalesi</option>
                    <option value="otomatik_odeme">Otomatik Odeme</option>
                    <option value="diger">Diger</option>
                </select>
            </label>
            <label>
                <span>Tekrarlama</span>
                <select name="tekrar_turu" data-expense-repeat-select>
                    <option value="tek">Tek seferlik</option>
                    <option value="aylik">Aylik</option>
                </select>
            </label>
            <label>
                <span>Kasa</span>
                <select name="kasa_id">
                    <option value="">Kasa secin</option>
                    <?php foreach (($kasalar ?? []) as $kasa) : ?>
                        <option value="<?= e($kasa['id']) ?>"><?= e($kasa['ad']) ?> - <?= e($kasa['para_birimi']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label data-expense-repeat-count-wrap hidden>
                <span>Planlanacak Ay</span>
                <input type="number" name="tekrar_adet" min="1" max="60" value="12">
            </label>
            <label class="dialog-wide"><span>Aciklama</span><textarea name="aciklama" rows="4"></textarea></label>
        </div>
        <div class="record-actions compact-actions">
            <span data-form-message></span>
            <button class="btn btn-ghost" type="button" data-close-dialog>Vazgec</button>
            <button class="btn btn-primary" type="submit">Gideri Kaydet</button>
        </div>
    </form>
</dialog>

<dialog id="gider-duzenle-dialog" class="appointment-dialog payment-dialog">
    <form method="dialog" class="appointment-dialog-form" data-expense-edit-form>
        <div class="dialog-head">
            <h2>Gider Duzenle</h2>
            <button type="button" data-close-dialog>x</button>
        </div>
        <input type="hidden" name="id">
        <div class="dialog-grid">
            <label><span>Tarih</span><input type="date" name="tarih" required></label>
            <label><span>Tedarikci</span><input name="tedarikci" required></label>
            <label>
                <span>Kategori</span>
                <select name="kategori" data-expense-category-select>
                    <option value="">Kategori secin</option>
                    <?php foreach (($giderKategorileri ?? []) as $kategori) : ?>
                        <option value="<?= e($kategori) ?>"><?= e($kategori) ?></option>
                    <?php endforeach; ?>
                    <option value="__new">+ Yeni kategori ekle</option>
                </select>
            </label>
            <label data-expense-category-new-wrap hidden>
                <span>Yeni Kategori</span>
                <input name="yeni_kategori" placeholder="Kategori adini yazin">
            </label>
            <label><span>Tutar</span><input type="number" step="0.01" name="tutar" required></label>
            <label>
                <span>Odeme Turu</span>
                <select name="odeme_turu" required>
                    <option value="nakit">Nakit</option>
                    <option value="kredi_karti">Kredi Karti</option>
                    <option value="banka_havalesi">Banka Havalesi</option>
                    <option value="otomatik_odeme">Otomatik Odeme</option>
                    <option value="diger">Diger</option>
                </select>
            </label>
            <label>
                <span>Kasa</span>
                <select name="kasa_id">
                    <option value="">Kasa secin</option>
                    <?php foreach (($kasalar ?? []) as $kasa) : ?>
                        <option value="<?= e($kasa['id']) ?>"><?= e($kasa['ad']) ?> - <?= e($kasa['para_birimi']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="dialog-wide"><span>Aciklama</span><textarea name="aciklama" rows="4"></textarea></label>
        </div>
        <div class="record-actions compact-actions">
            <span data-form-message></span>
            <button class="btn btn-ghost" type="button" data-close-dialog>Vazgec</button>
            <button class="btn btn-primary" type="submit">Gideri Guncelle</button>
        </div>
    </form>
</dialog>
