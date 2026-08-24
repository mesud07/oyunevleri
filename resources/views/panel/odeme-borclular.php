<section class="page-head">
    <div>
        <h1>Mevcut Borclular</h1>
        <p>Paket borcu bulunan ogrenciler ve kalan tahsilat bakiyeleri.</p>
    </div>
    <button class="btn btn-primary" type="button" data-open-dialog="#tahsilat-dialog">Tahsilat Yap</button>
</section>

<section class="panel-grid single-wide">
    <article class="panel-card">
        <div class="definition-head">
            <h2>Mevcut Borclular</h2>
            <div class="head-actions">
                <span class="status-pill"><?= e(count($borcluPaketler ?? [])) ?> kayit</span>
                <span class="status-pill is-danger">Toplam <?= e(para_goster($toplamKalanBorc ?? 0)) ?></span>
            </div>
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
                                <div class="debt-actions">
                                <button
                                    class="btn btn-ghost"
                                    type="button"
                                    data-payment-from-debt
                                    data-paket-id="<?= e($borc['paket_id']) ?>"
                                    data-tutar="<?= e($borc['kalan_borc']) ?>"
                                >Tahsilat Yap</button>
                                    <button
                                        class="btn btn-danger"
                                        type="button"
                                        data-debt-unpaid-close
                                        data-paket-id="<?= e($borc['paket_id']) ?>"
                                    >Odeme Yapilmadi Kapat</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<dialog id="tahsilat-dialog" class="appointment-dialog payment-dialog">
    <form method="dialog" class="appointment-dialog-form" data-ajax-form="odeme_ekle" data-success-redirect="/panel/odemeler/borclular">
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
