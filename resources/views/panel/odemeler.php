<section class="page-head">
    <div>
        <h1>Tahsilatlar</h1>
        <p>Paket odemeleri, makbuz ve tahsilat kayitlari.</p>
    </div>
    <button class="btn btn-primary" type="button" data-open-dialog="#tahsilat-dialog">Tahsilat Yap</button>
</section>

<section class="panel-grid single-wide">
    <article class="panel-card">
        <div class="definition-head">
            <h2>Mevcut Borclular</h2>
            <span class="status-pill"><?= e(count($borcluPaketler ?? [])) ?> kayit</span>
        </div>
        <div class="table-wrap fast-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Ogrenci</th>
                        <th>Paket</th>
                        <th>Paket Tutari</th>
                        <th>Tahsilat</th>
                        <th>Kalan Borc</th>
                        <th>Islem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($borcluPaketler)) : ?>
                        <tr><td colspan="6">Borclu paket bulunamadi.</td></tr>
                    <?php endif; ?>
                    <?php foreach (($borcluPaketler ?? []) as $borc) : ?>
                        <tr>
                            <td><?= e($borc['ogrenci']) ?></td>
                            <td><?= e($borc['paket_adi']) ?></td>
                            <td><?= e(para_goster($borc['net_paket_tutari'])) ?></td>
                            <td><?= e(para_goster($borc['tahsilat'])) ?></td>
                            <td><strong><?= e(para_goster($borc['kalan_borc'])) ?></strong></td>
                            <td>
                                <button
                                    class="btn btn-ghost"
                                    type="button"
                                    data-payment-from-debt
                                    data-paket-id="<?= e($borc['paket_id']) ?>"
                                    data-tutar="<?= e($borc['kalan_borc']) ?>"
                                >Tahsilat Yap</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="panel-card">
        <div class="definition-head">
            <h2>Tahsilat Listesi</h2>
            <button class="btn btn-sky" type="button" data-open-dialog="#tahsilat-dialog">Yeni Tahsilat</button>
        </div>
        <div id="odeme-tablosu" class="table-wrap fast-table-wrap" data-payment-table></div>
    </article>
</section>

<section class="panel-grid single-wide">
    <article class="panel-card">
        <div class="definition-head">
            <div>
                <h2>Yapilacak Odemeler</h2>
                <p class="section-helper">Ileri tarihli giderlerinizi buradan borclandirin ve odeme planinizi takip edin.</p>
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
        <div id="gider-tablosu" class="table-wrap fast-table-wrap" data-expense-table></div>
    </article>
</section>

<dialog id="tahsilat-dialog" class="appointment-dialog payment-dialog">
    <form method="dialog" class="appointment-dialog-form" data-ajax-form="odeme_ekle" data-success-redirect="/panel/odemeler">
        <div class="dialog-head">
            <h2>Tahsilat Yap</h2>
            <button type="button" data-close-dialog>x</button>
        </div>
        <div class="info-box compact-info">
            <strong>Bilgilendirme</strong>
            <p>Paket secildiginde tahsilat ilgili ogrencinin cari hesabina islenir. Kismi odemeler ayni paket uzerinde birden fazla kayit olarak takip edilir.</p>
        </div>
        <div class="dialog-grid">
            <label>
                <span>Paket</span>
                <select name="paket_id" required>
                    <option value="">Seciniz</option>
                    <?php foreach (($paketler ?? []) as $paket) : ?>
                        <option value="<?= e($paket['id']) ?>"><?= e($paket['etiket']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="check-row dialog-wide">
                <span>Paket Tutari</span>
                <div class="check-list">
                    <label><input type="checkbox" name="paket_tutari_guncelle" value="1" data-package-price-toggle> Bu tahsilatla paketin toplam odenecek tutarini guncelle</label>
                </div>
            </label>
            <div class="dialog-wide package-price-panel" data-package-price-panel hidden>
                <label>
                    <span>Yeni Toplam Odenecek Tutar</span>
                    <input type="number" step="0.01" min="0" name="yeni_paket_tutari" placeholder="Orn. 5000">
                </label>
                <p class="muted-note">Bu alan sadece secili paketin borc hesabinda kullanilan toplam tutarini degistirir. Randevular ve paket kaydi aynen kalir.</p>
            </div>
            <label><span>Tarih</span><input type="date" name="tarih" value="<?= e(date('Y-m-d')) ?>" required></label>
            <label><span>Tutar</span><input type="number" step="0.01" name="tutar" required></label>
            <label>
                <span>Yontem</span>
                <select name="yontem" required>
                    <option value="nakit">Nakit</option>
                    <option value="kredi_karti">Kredi Karti</option>
                    <option value="havale_eft">Havale/EFT</option>
                    <option value="odeme_baglantisi">Odeme Baglantisi</option>
                    <option value="diger">Diger</option>
                </select>
            </label>
            <label><span>Makbuz No</span><input name="makbuz_numarasi"></label>
            <label>
                <span>Kasa</span>
                <select name="kasa_id">
                    <option value="">Kasa secin</option>
                    <?php foreach (($kasalar ?? []) as $kasa) : ?>
                        <option value="<?= e($kasa['id']) ?>"><?= e($kasa['ad']) ?> - <?= e($kasa['para_birimi']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="check-row dialog-wide">
                <span>SMS</span>
                <div class="check-list">
                    <label><input type="checkbox" name="odeme_sms_gonder" value="1"> Odeme alindi SMS'i gonder</label>
                </div>
            </label>
            <label class="dialog-wide"><span>Aciklama</span><textarea name="aciklama" rows="4"></textarea></label>
        </div>
        <div class="record-actions compact-actions">
            <span data-form-message></span>
            <button class="btn btn-ghost" type="button" data-close-dialog>Vazgec</button>
            <button class="btn btn-primary" type="submit">Tahsilati Kaydet</button>
        </div>
    </form>
</dialog>

<dialog id="tahsilat-kasa-dialog" class="appointment-dialog payment-dialog">
    <form method="dialog" class="appointment-dialog-form" data-payment-cashbox-form>
        <div class="dialog-head">
            <h2>Tahsilati Kasaya Aktar</h2>
            <button type="button" data-close-dialog>x</button>
        </div>
        <input type="hidden" name="id">
        <div class="dialog-grid">
            <label class="dialog-wide">
                <span>Tahsilat</span>
                <input name="odeme_bilgi" readonly>
            </label>
            <label class="dialog-wide">
                <span>Kasa</span>
                <select name="kasa_id" required>
                    <option value="">Kasa secin</option>
                    <?php foreach (($kasalar ?? []) as $kasa) : ?>
                        <option value="<?= e($kasa['id']) ?>"><?= e($kasa['ad']) ?> - <?= e($kasa['para_birimi']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="record-actions compact-actions">
            <span data-form-message></span>
            <button class="btn btn-ghost" type="button" data-close-dialog>Vazgec</button>
            <button class="btn btn-primary" type="submit">Kasaya Aktar</button>
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
                    <option value="Maas">Maas</option>
                    <option value="SGK / Personel">SGK / Personel</option>
                    <option value="Market">Market</option>
                    <option value="Yemek">Yemek</option>
                    <option value="Temizlik">Temizlik</option>
                    <option value="Kira / Aidat">Kira / Aidat</option>
                    <option value="Kirtasiye">Kirtasiye</option>
                    <option value="Akaryakit">Akaryakit</option>
                    <option value="Abonelik">Abonelik</option>
                    <option value="Bakim / Onarim">Bakim / Onarim</option>
                    <option value="Egitim Materyali">Egitim Materyali</option>
                    <option value="Diger">Diger</option>
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

<dialog id="gider-dialog" class="appointment-dialog payment-dialog">
    <form method="dialog" class="appointment-dialog-form" data-ajax-form="gider_ekle" data-success-redirect="/panel/odemeler">
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
                    <option value="Maas">Maas</option>
                    <option value="SGK / Personel">SGK / Personel</option>
                    <option value="Market">Market</option>
                    <option value="Yemek">Yemek</option>
                    <option value="Temizlik">Temizlik</option>
                    <option value="Kira / Aidat">Kira / Aidat</option>
                    <option value="Kirtasiye">Kirtasiye</option>
                    <option value="Akaryakit">Akaryakit</option>
                    <option value="Abonelik">Abonelik</option>
                    <option value="Bakim / Onarim">Bakim / Onarim</option>
                    <option value="Egitim Materyali">Egitim Materyali</option>
                    <option value="Diger">Diger</option>
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
