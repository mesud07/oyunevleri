<section class="page-head">
    <div>
        <h1>Tahsilatlar</h1>
        <p>Yaklasan ve gecikmis odemeler ile tamamlanan tahsilat kayitlari.</p>
    </div>
    <button class="btn btn-primary" type="button" data-open-dialog="#tahsilat-dialog">Yeni Tahsilat</button>
</section>

<section class="report-grid payment-tracking-grid">
    <article class="panel-card report-panel">
        <h2>Yaklasan Tahsilatlar</h2>
        <p class="report-helper">Ilk ders tarihi onumuzdeki 7 gun icinde olan ve paketinde kalan borcu bulunan ogrenciler.</p>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Odeme Vadesi</th><th>Ogrenci</th><th>Son Paket</th><th>Kayit Yenileme</th><th>Kalan Ders</th><th>Telafi</th><th>Paket Tutari</th><th>Odenen</th><th>Beklenen</th></tr></thead>
                <tbody>
                    <?php if (empty($yaklasanTahsilatlar)) : ?><tr><td colspan="9">Yaklasan tahsilat bulunamadi.</td></tr><?php endif; ?>
                    <?php foreach (($yaklasanTahsilatlar ?? []) as $row) : ?>
                        <tr>
                            <td><?= e(tarih_goster($row['odeme_vade_tarihi'])) ?></td>
                            <td><?= e($row['ogrenci']) ?></td>
                            <td><?= e($row['paket_adi']) ?></td>
                            <td><?= e(tarih_goster($row['kayit_yenileme_tarihi'])) ?></td>
                            <td><?= e($row['kalan_ders']) ?></td>
                            <td><?= e($row['kalan_telafi']) ?></td>
                            <td><?= e(para_goster($row['paket_tutari'])) ?></td>
                            <td><?= e(para_goster($row['mevcut_tahsilat'])) ?></td>
                            <td><strong><?= e(para_goster($row['beklenen_tahsilat'])) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="panel-card report-panel">
        <h2>Gecikmis Tahsilatlar</h2>
        <p class="report-helper">Ilk ders tarihi gecmis ve paketinde kalan borcu bulunan ogrenciler.</p>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Odeme Vadesi</th><th>Ogrenci</th><th>Son Paket</th><th>Kayit Yenileme</th><th>Kalan Ders</th><th>Telafi</th><th>Paket Tutari</th><th>Odenen</th><th>Beklenen</th></tr></thead>
                <tbody>
                    <?php if (empty($gecikmisTahsilatlar)) : ?><tr><td colspan="9">Gecikmis tahsilat bulunamadi.</td></tr><?php endif; ?>
                    <?php foreach (($gecikmisTahsilatlar ?? []) as $row) : ?>
                        <tr>
                            <td><?= e(tarih_goster($row['odeme_vade_tarihi'])) ?></td>
                            <td><?= e($row['ogrenci']) ?></td>
                            <td><?= e($row['paket_adi']) ?></td>
                            <td><?= e(tarih_goster($row['kayit_yenileme_tarihi'])) ?></td>
                            <td><?= e($row['kalan_ders']) ?></td>
                            <td><?= e($row['kalan_telafi']) ?></td>
                            <td><?= e(para_goster($row['paket_tutari'])) ?></td>
                            <td><?= e(para_goster($row['mevcut_tahsilat'])) ?></td>
                            <td><strong><?= e(para_goster($row['beklenen_tahsilat'])) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="panel-grid single-wide">
    <article class="panel-card">
        <div class="definition-head">
            <h2>Tahsilat Listesi</h2>
            <div class="head-actions payment-total-pills">
                <span class="payment-total-pill is-day">Bugun Alinan <?= e(para_goster($tahsilatOzetleri['bugun'] ?? 0)) ?></span>
                <span class="payment-total-pill is-week">Bu Hafta <?= e(para_goster($tahsilatOzetleri['bu_hafta'] ?? 0)) ?></span>
                <span class="payment-total-pill is-total">Toplam <?= e(para_goster($tahsilatOzetleri['toplam'] ?? 0)) ?></span>
                <button class="btn btn-sky" type="button" data-open-dialog="#tahsilat-dialog">Yeni Tahsilat</button>
            </div>
        </div>
        <div id="odeme-tablosu" class="table-wrap fast-table-wrap" data-payment-table></div>
    </article>
</section>

<dialog id="tahsilat-dialog" class="appointment-dialog payment-dialog">
    <form method="dialog" class="appointment-dialog-form" data-ajax-form="odeme_ekle" data-success-redirect="/panel/odemeler/tahsilatlar">
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
