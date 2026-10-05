<?php $odemePlaniYonetebilir = yetki_var('odeme_ekle'); ?>
<section class="page-head">
    <div>
        <h1>Mevcut Borclular</h1>
        <p>Paket borcu bulunan ogrenciler ve kalan tahsilat bakiyeleri.</p>
    </div>
    <button class="btn btn-primary" type="button" data-open-dialog="#tahsilat-dialog">Tahsilat Yap</button>
</section>

<section class="debt-calendar-card" data-debt-payment-calendar>
    <div class="debt-calendar-head">
        <div>
            <span class="eyebrow">ÖDEME PLANI</span>
            <h2>Beklenen Tahsilatlar</h2>
            <p>Velilerin bildirdiği tarihlere göre gelecek nakit ve diğer ödemeler.</p>
        </div>
        <div class="debt-calendar-navigation">
            <button type="button" data-debt-calendar-prev aria-label="Önceki ay">‹</button>
            <strong data-debt-calendar-month></strong>
            <button type="button" data-debt-calendar-next aria-label="Sonraki ay">›</button>
        </div>
    </div>
    <div class="debt-calendar-layout">
        <div class="debt-calendar-month">
            <div class="debt-calendar-weekdays" aria-hidden="true">
                <span>Pzt</span><span>Sal</span><span>Çar</span><span>Per</span><span>Cum</span><span>Cmt</span><span>Paz</span>
            </div>
            <div class="debt-calendar-grid" data-debt-calendar-grid></div>
        </div>
        <aside class="debt-calendar-detail" data-debt-calendar-detail>
            <div class="empty-table">Beklenen ödeme tarihi seçilmiş borç bulunmuyor.</div>
        </aside>
    </div>
    <script type="application/json" data-debt-calendar-source><?= json_encode(
        $beklenenOdemeTakvimi ?? [],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?></script>
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
                        <th>Başlangıç</th>
                        <th>Bitiş</th>
                        <th>Paket Tutari</th>
                        <th>Tahsilat</th>
                        <th>Kalan Borc</th>
                        <th>Tahsilat Notu</th>
                        <th>Beklenen Ödeme</th>
                        <th>Islem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($borcluPaketler)) : ?>
                        <tr><td colspan="10">Borclu paket bulunamadi.</td></tr>
                    <?php endif; ?>
                    <?php foreach (($borcluPaketler ?? []) as $borc) : ?>
                        <tr>
                            <td><?= e($borc['ogrenci']) ?></td>
                            <td><?= e($borc['paket_adi']) ?></td>
                            <td><?= e(tarih_goster($borc['baslangic_tarihi'] ?? null)) ?></td>
                            <td><?= e(tarih_goster($borc['tahmini_son_ders_tarihi'] ?? null)) ?></td>
                            <td><?= e(para_goster($borc['net_paket_tutari'])) ?></td>
                            <td><?= e(para_goster($borc['tahsilat'])) ?></td>
                            <td><strong><?= e(para_goster($borc['kalan_borc'])) ?></strong></td>
                            <td class="debt-note-cell">
                                <?php if (!empty($borc['tahsilat_notu'])) : ?>
                                    <span><?= nl2br(e($borc['tahsilat_notu'])) ?></span>
                                <?php else : ?>
                                    <span class="muted">Not girilmedi</span>
                                <?php endif; ?>
                                <?php if ($odemePlaniYonetebilir) : ?>
                                    <button
                                        class="debt-note-edit"
                                        type="button"
                                        data-debt-payment-plan
                                        data-paket-id="<?= e($borc['paket_id']) ?>"
                                        data-package-label="<?= e($borc['ogrenci'] . ' · ' . $borc['paket_adi']) ?>"
                                        data-payment-note="<?= e(rawurlencode((string) ($borc['tahsilat_notu'] ?? ''))) ?>"
                                        data-expected-payment-date="<?= e((string) ($borc['beklenen_odeme_tarihi'] ?? '')) ?>"
                                    ><?= empty($borc['tahsilat_notu']) ? 'Not Ekle' : 'Notu Düzenle' ?></button>
                                <?php endif; ?>
                            </td>
                            <td class="debt-date-cell">
                                <?php
                                $beklenenOdemeTarihi = (string) ($borc['beklenen_odeme_tarihi'] ?? '');
                                $odemeTarihiSinifi = $beklenenOdemeTarihi !== '' && $beklenenOdemeTarihi < date('Y-m-d')
                                    ? 'is-danger'
                                    : ($beklenenOdemeTarihi === date('Y-m-d') ? 'is-warning' : 'is-success');
                                ?>
                                <?php if ($beklenenOdemeTarihi !== '') : ?>
                                    <strong><?= e(tarih_goster($beklenenOdemeTarihi)) ?></strong>
                                    <span class="status-pill <?= e($odemeTarihiSinifi) ?>">
                                        <?= $beklenenOdemeTarihi < date('Y-m-d') ? 'Gecikti' : ($beklenenOdemeTarihi === date('Y-m-d') ? 'Bugün' : 'Planlandı') ?>
                                    </span>
                                <?php else : ?>
                                    <span class="muted">Tarih girilmedi</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="debt-actions">
                                <?php if ($odemePlaniYonetebilir) : ?>
                                    <button
                                        class="btn btn-secondary"
                                        type="button"
                                        data-debt-payment-plan
                                        data-paket-id="<?= e($borc['paket_id']) ?>"
                                        data-package-label="<?= e($borc['ogrenci'] . ' · ' . $borc['paket_adi']) ?>"
                                        data-payment-note="<?= e(rawurlencode((string) ($borc['tahsilat_notu'] ?? ''))) ?>"
                                        data-expected-payment-date="<?= e($beklenenOdemeTarihi) ?>"
                                    >Not / Tarih Düzenle</button>
                                <?php endif; ?>
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

<?php if ($odemePlaniYonetebilir) : ?>
<dialog class="appointment-dialog debt-payment-plan-dialog" data-debt-payment-plan-dialog>
    <form method="dialog" class="appointment-dialog-form" data-ajax-form="paket_odeme_plani_guncelle" data-success-redirect="/panel/odemeler/borclular" data-debt-payment-plan-form>
        <div class="dialog-head">
            <div><h2>Ödeme Planı</h2><p data-debt-payment-plan-title></p></div>
            <button type="button" data-close-dialog>x</button>
        </div>
        <input type="hidden" name="paket_id">
        <div class="dialog-grid">
            <label>
                <span>Beklenen Ödeme Tarihi</span>
                <input type="date" name="beklenen_odeme_tarihi">
                <small>Veli ödemeyi hangi tarihte getireceğini belirttiyse seçin.</small>
            </label>
            <label class="dialog-wide">
                <span>Tahsilat Notu</span>
                <textarea name="tahsilat_notu" rows="6" maxlength="2000" placeholder="Ornek: Ödemeyi cuma günü nakit getirecek."></textarea>
                <small>Tarih veya notu kaldırmak için ilgili alanı boşaltıp kaydedebilirsiniz.</small>
            </label>
        </div>
        <div class="record-actions compact-actions">
            <span data-form-message></span>
            <button class="btn btn-ghost" type="button" data-close-dialog>Vazgec</button>
            <button class="btn btn-primary" type="submit">Ödeme Planını Kaydet</button>
        </div>
    </form>
</dialog>
<?php endif; ?>

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
