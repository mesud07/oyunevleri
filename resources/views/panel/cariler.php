<section class="page-head">
    <div>
        <h1>Cariler</h1>
        <p>Öğrenci paketinden bağımsız kişi ve firmalara fatura düzenleyin.</p>
    </div>
    <button class="btn btn-primary" type="button" data-open-dialog="#cari-dialog">Yeni Cari</button>
</section>

<section class="panel-card report-panel">
    <form method="get" action="/panel/cariler" class="filter-grid">
        <label><span>Cari Ara</span><input name="q" value="<?= e($arama) ?>" placeholder="Ad, ünvan veya VKN/TCKN"></label>
        <div class="record-actions">
            <button class="btn btn-primary">Ara</button>
            <a class="btn btn-ghost" href="/panel/cariler">Temizle</a>
        </div>
    </form>
</section>

<section class="panel-card report-panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Cari</th><th>VKN/TCKN</th><th>Tür</th><th>Telefon</th><th>Veli Eşleşmesi</th><th>Fatura</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php if (empty($cariler)) : ?><tr><td colspan="7">Cari kaydı bulunamadı.</td></tr><?php endif; ?>
            <?php foreach ($cariler as $cari) : ?>
                <?php $cariAdi = $cari['profil_turu'] === 'kurumsal' ? $cari['unvan'] : trim($cari['ad'] . ' ' . $cari['soyad']); ?>
                <tr>
                    <td><strong><?= e($cariAdi) ?></strong><small class="table-subtext"><?= e($cari['eposta'] ?: $cari['adres']) ?></small></td>
                    <td><?= e(str_repeat('*', max(0, strlen($cari['vkn_tckn']) - 4)) . substr($cari['vkn_tckn'], -4)) ?></td>
                    <td><?= $cari['profil_turu'] === 'kurumsal' ? 'Kurumsal' : 'Bireysel' ?></td>
                    <td><?= e($cari['telefon'] ?: '-') ?></td>
                    <td><?= e($cari['veli_adi'] ?: 'Bağımsız cari') ?></td>
                    <td><?= e($cari['fatura_sayisi']) ?></td>
                    <td><button class="mini-btn" type="button" data-cari-invoice='<?= e(json_encode(['id' => $cari['id'], 'name' => $cariAdi], JSON_UNESCAPED_UNICODE)) ?>'>Fatura Taslağı</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<dialog id="cari-dialog" class="appointment-dialog payment-dialog">
    <form method="dialog" class="appointment-dialog-form" data-cari-form>
        <div class="dialog-head">
            <div><h2>Yeni Cari</h2><p>Cariyi kaydettikten sonra doğrudan fatura adımına geçebilirsiniz.</p></div>
            <button type="button" data-close-dialog>x</button>
        </div>
        <div class="dialog-grid">
            <label class="check-row dialog-wide">
                <span>Hızlı Cari</span>
                <div class="check-list">
                    <label><input type="checkbox" name="muhtelif_musteri" value="1" data-misc-customer checked> Muhtelif Müşteri</label>
                    <small>Genel müşteriler için TCKN ve adres bilgileri otomatik doldurulur.</small>
                </div>
            </label>
            <label class="dialog-wide"><span>Mevcut Veli Ara</span><input data-cari-parent-search placeholder="Ad soyad veya TCKN yazın"></label>
            <div class="dialog-wide cari-parent-results" data-cari-parent-results></div>
            <input type="hidden" name="veli_id">
            <label><span>Cari Türü</span><select name="profil_turu" data-cari-type><option value="bireysel">Bireysel</option><option value="kurumsal">Kurumsal</option></select></label>
            <label><span>VKN / TCKN</span><input name="vkn_tckn" maxlength="11" value="11111111111" required></label>
            <label data-cari-person><span>Ad</span><input name="ad" placeholder="Ad girin"></label>
            <label data-cari-person><span>Soyad</span><input name="soyad" placeholder="Soyad girin"></label>
            <label class="dialog-wide" data-cari-company hidden><span>Firma Ünvanı</span><input name="unvan"></label>
            <label data-cari-company hidden><span>Vergi Dairesi</span><input name="vergi_dairesi"></label>
            <label><span>İl</span><input name="il" value="Antalya" required></label>
            <label><span>İlçe</span><input name="ilce" value="Muratpaşa" required></label>
            <label class="dialog-wide"><span>Adres</span><textarea name="adres" required>Muratpaşa / Antalya</textarea></label>
            <label><span>Telefon</span><input name="telefon"></label>
            <label><span>E-posta</span><input type="email" name="eposta"></label>
            <input type="hidden" name="ulke" value="TÜRKİYE">
            <label class="dialog-wide"><span>Not</span><textarea name="notlar"></textarea></label>
        </div>
        <div class="record-actions">
            <span data-form-message></span>
            <button class="btn btn-ghost" type="button" data-close-dialog>Vazgeç</button>
            <button class="btn btn-primary" type="submit">Kaydet ve Faturaya Geç</button>
        </div>
    </form>
</dialog>

<dialog id="cari-fatura-dialog" class="appointment-dialog payment-dialog">
    <form method="dialog" class="appointment-dialog-form" data-cari-invoice-form>
        <div class="dialog-head">
            <div><h2>Cari Fatura Taslağı</h2><p data-cari-invoice-name></p></div>
            <button type="button" data-close-dialog>x</button>
        </div>
        <input type="hidden" name="cari_id">
        <div class="dialog-grid">
            <label><span>Fatura Tarihi</span><input type="date" name="tarih" value="<?= e(date('Y-m-d')) ?>" required></label>
            <label><span>KDV Dahil Tutar</span><input type="number" name="tutar" min="0.01" step="0.01" required></label>
            <label><span>KDV Oranı (%)</span><input type="number" name="kdv_orani" min="0" max="100" step="0.01" value="10" required></label>
            <label class="dialog-wide"><span>Hizmet / Ürün Açıklaması</span><input name="aciklama" required></label>
            <label class="dialog-wide"><span>Fatura Notu</span><textarea name="not"></textarea></label>
        </div>
        <div class="info-box compact-info">
            <strong>Önce taslak oluşturulur</strong>
            <p>Belgeyi kontrol ettikten sonra Faturalar ekranından ayrıca onaylayarak resmileştirebilirsiniz.</p>
        </div>
        <div class="record-actions">
            <span data-form-message></span>
            <button class="btn btn-ghost" type="button" data-close-dialog>Vazgeç</button>
            <button class="btn btn-primary" type="submit">Taslak Oluştur</button>
        </div>
    </form>
</dialog>
