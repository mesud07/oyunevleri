<section class="page-head">
    <div>
        <h1>Tahsilatlar</h1>
        <p>Tamamlanan tahsilat kayıtları, ödeme dağılımı ve KDV özeti.</p>
    </div>
    <button class="btn btn-primary" type="button" data-open-dialog="#tahsilat-dialog">Yeni Tahsilat</button>
</section>

<?php
$analiz = $tahsilatAnalizi ?? [];
$grafikDilimleri = [];
$grafikBaslangic = 0.0;
foreach (($analiz['yontemler'] ?? []) as $yontem) {
    $yuzde = max(0.0, (float) ($yontem['yuzde'] ?? 0));
    if ($yuzde <= 0) {
        continue;
    }
    $grafikBitis = min(100.0, $grafikBaslangic + $yuzde);
    $grafikDilimleri[] = sprintf('%s %.2f%% %.2f%%', (string) ($yontem['renk'] ?? '#9aa9bb'), $grafikBaslangic, $grafikBitis);
    $grafikBaslangic = $grafikBitis;
}
$grafikArkaPlan = $grafikDilimleri
    ? 'conic-gradient(' . implode(', ', $grafikDilimleri) . ')'
    : 'conic-gradient(#dbe5ef 0 100%)';
?>

<section class="collection-analysis" aria-labelledby="tahsilat-analizi-baslik">
    <article class="panel-card collection-analysis-card">
        <div class="collection-analysis-head">
            <div>
                <span class="collection-analysis-kicker">Finansal görünüm</span>
                <h2 id="tahsilat-analizi-baslik"><?= e($analiz['ay_etiketi'] ?? '') ?> Tahsilat Analizi</h2>
                <p>Seçili dönemde alınan tahsilatların ödeme türüne göre dağılımı ve KDV özeti.</p>
            </div>
            <form class="collection-month-filter" method="get" action="/panel/odemeler/tahsilatlar">
                <label for="tahsilat-analiz-ayi">Analiz Dönemi</label>
                <div>
                    <input id="tahsilat-analiz-ayi" type="month" name="ay" value="<?= e($analizAy ?? date('Y-m')) ?>" required>
                    <select name="donem" aria-label="Analiz süre aralığı">
                        <option value="ay" <?= ($analizDonem ?? 'ay') === 'ay' ? 'selected' : '' ?>>Aylık</option>
                        <option value="3ay" <?= ($analizDonem ?? '') === '3ay' ? 'selected' : '' ?>>3 Aylık</option>
                        <option value="6ay" <?= ($analizDonem ?? '') === '6ay' ? 'selected' : '' ?>>6 Aylık</option>
                        <option value="bu_yil" <?= ($analizDonem ?? '') === 'bu_yil' ? 'selected' : '' ?>>Bu Sene</option>
                        <option value="1yil" <?= ($analizDonem ?? '') === '1yil' ? 'selected' : '' ?>>1 Yıl</option>
                    </select>
                    <button class="btn btn-sky" type="submit">Göster</button>
                    <a class="btn btn-ghost" href="/panel/odemeler/tahsilatlar.xlsx?ay=<?= e(rawurlencode($analizAy ?? date('Y-m'))) ?>&amp;donem=<?= e(rawurlencode($analizDonem ?? 'ay')) ?>">Excel İndir</a>
                </div>
            </form>
        </div>

        <form class="collection-vat-settings" data-vat-method-form>
            <div>
                <strong>KDV Hesabına Dahil Edilecek Ödeme Yöntemleri</strong>
                <small>Toplam tahsilat değişmez; yalnızca hesaplanan KDV ve KDV hariç gelir yeniden hesaplanır.</small>
            </div>
            <div class="collection-vat-options">
                <?php foreach (['nakit' => 'Nakit', 'kredi_karti' => 'Kredi Kartı', 'havale_eft' => 'Havale / EFT', 'odeme_baglantisi' => 'Ödeme Bağlantısı', 'diger' => 'Diğer'] as $kod => $etiket) : ?>
                    <label><input type="checkbox" name="yontemler[]" value="<?= e($kod) ?>" <?= in_array($kod, $kdvYontemleri ?? [], true) ? 'checked' : '' ?>> <span><?= e($etiket) ?></span></label>
                <?php endforeach; ?>
            </div>
            <div class="collection-vat-actions">
                <span data-vat-method-message aria-live="polite"></span>
                <button class="btn btn-sky" type="submit">KDV Ayarını Kaydet</button>
            </div>
        </form>

        <div class="collection-kpi-grid">
            <article>
                <span>Dönem Tahsilatı</span>
                <strong><?= e(para_goster($analiz['toplam_tahsilat'] ?? 0)) ?></strong>
                <small><?= e((string) ($analiz['tahsilat_adedi'] ?? 0)) ?> aktif tahsilat</small>
            </article>
            <article>
                <span>Tahsilat Başına Ortalama</span>
                <strong><?= e(para_goster($analiz['ortalama_tahsilat'] ?? 0)) ?></strong>
                <small>Seçili dönemdeki işlem ortalaması</small>
            </article>
            <article>
                <span>Hesaplanan KDV</span>
                <strong><?= e(para_goster($analiz['kdv'] ?? 0)) ?></strong>
                <small>Seçili ödeme yöntemleri ve paket KDV oranlarına göre</small>
            </article>
            <article>
                <span>KDV Hariç Gelir</span>
                <strong><?= e(para_goster($analiz['kdv_haric_gelir'] ?? 0)) ?></strong>
                <small>Dönem tahsilatı eksi hesaplanan KDV</small>
            </article>
        </div>

        <div class="collection-analysis-body">
            <section class="collection-method-chart" aria-labelledby="odeme-dagilimi-baslik">
                <div>
                    <h3 id="odeme-dagilimi-baslik">Ödeme Yöntemi Dağılımı</h3>
                    <p>Her ödeme türünün seçili dönemdeki tutarı, işlem adedi ve toplam içindeki payı.</p>
                </div>
                <div class="collection-chart-layout">
                    <div class="collection-donut" role="img" aria-label="Ödeme yöntemlerine göre tahsilat dağılımı" style="--collection-chart: <?= e($grafikArkaPlan) ?>">
                        <div>
                            <strong><?= e(para_goster($analiz['toplam_tahsilat'] ?? 0)) ?></strong>
                            <span>Toplam</span>
                        </div>
                    </div>
                    <div class="collection-chart-legend">
                        <?php foreach (($analiz['yontemler'] ?? []) as $yontem) : ?>
                            <div class="collection-legend-row">
                                <i style="--legend-color: <?= e($yontem['renk'] ?? '#9aa9bb') ?>"></i>
                                <span>
                                    <strong><?= e($yontem['ad'] ?? '') ?></strong>
                                    <small><?= e((string) ($yontem['adet'] ?? 0)) ?> tahsilat · %<?= e(number_format((float) ($yontem['yuzde'] ?? 0), 1, ',', '.')) ?></small>
                                </span>
                                <b><?= e(para_goster($yontem['tutar'] ?? 0)) ?></b>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

        </div>

        <?php if ((int) ($analiz['kdv_belirsiz_adet'] ?? 0) > 0) : ?>
            <p class="collection-analysis-warning"><strong>Dikkat:</strong> <?= e((string) $analiz['kdv_belirsiz_adet']) ?> tahsilatın paketinde KDV oranı olmadığı için bu kayıtların KDV tutarı sıfır kabul edildi.</p>
        <?php endif; ?>
        <p class="collection-analysis-note">KDV özeti yönetim amaçlı yaklaşık hesaplamadır; resmî beyan veya mali müşavir hesabı yerine geçmez.</p>
    </article>
</section>

<section class="panel-grid single-wide" id="tahsilat-listesi">
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
        <div class="filter-grid payment-list-filters">
            <label><span>Tahsilat Ayı</span><input type="month" value="<?= e(date('Y-m')) ?>" data-payment-month-filter></label>
            <label><span>Ödeme Yöntemi</span><select data-payment-method-filter><option value="">Tüm yöntemler</option><option value="havale">Havale / EFT</option><option value="nakit">Nakit</option><option value="kredi_karti">Kredi Kartı</option><option value="odeme_baglantisi">Ödeme Bağlantısı</option><option value="diger">Diğer</option></select></label>
            <label><span>Sıralama</span><select data-payment-sort><option value="tarih_desc">Ödeme tarihi — yeniden eskiye</option><option value="tarih_asc">Ödeme tarihi — eskiden yeniye</option><option value="yontem_asc">Ödeme yöntemi — A’dan Z’ye</option><option value="yontem_desc">Ödeme yöntemi — Z’den A’ya</option></select></label>
            <div class="record-actions">
                <a class="btn btn-sky" href="#" data-payment-filter-export>Filtreleneni Excel'e Aktar</a>
                <button class="btn btn-ghost" type="button" data-payment-filter-clear>Filtreyi Temizle</button>
            </div>
        </div>
        <div
            id="odeme-tablosu"
            class="table-wrap fast-table-wrap"
            data-payment-table
            data-institution-name="<?= e($kullanici['kurum_adi'] ?? 'Kurum') ?>"
            data-institution-logo="<?= e($kurumLogosuVar ? $kurumLogoYolu : '') ?>"
        ></div>
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

<dialog id="fatura-kes-dialog" class="appointment-dialog payment-dialog">
    <form method="dialog" class="appointment-dialog-form" data-invoice-create-form>
        <div class="dialog-head"><div><h2>Fatura Taslağı Oluştur</h2><p data-invoice-payment-info></p></div><button type="button" data-close-dialog>x</button></div>
        <input type="hidden" name="odeme_id">
        <div class="dialog-grid">
            <label><span>Fatura Profili</span><select name="profil_turu" data-invoice-profile-type required><option value="bireysel">Bireysel</option><option value="kurumsal">Kurumsal</option></select></label>
            <label><span>VKN / TCKN</span><input name="vkn_tckn" maxlength="11" inputmode="numeric" required></label>
            <label data-individual-field><span>Ad</span><input name="ad"></label><label data-individual-field><span>Soyad</span><input name="soyad"></label>
            <label class="dialog-wide" data-corporate-field hidden><span>Firma Ünvanı</span><input name="unvan"></label><label data-corporate-field hidden><span>Vergi Dairesi</span><input name="vergi_dairesi"></label>
            <label><span>İl</span><input name="il" required></label><label><span>İlçe</span><input name="ilce" required></label><label class="dialog-wide"><span>Adres</span><textarea name="adres" rows="3" required></textarea></label>
            <label><span>E-posta</span><input type="email" name="eposta"></label><label><span>Telefon</span><input name="telefon"></label><input type="hidden" name="ulke" value="TÜRKİYE">
            <label><span>KDV Oranı (%)</span><input type="number" name="kdv_orani" min="0" max="100" step="0.01" required placeholder="Hizmete uygulanan oran"></label>
        </div>
        <div class="info-box compact-info"><strong>Önce taslak oluşturulur</strong><p>VKN/TCKN sorgusuna göre e-Fatura veya e-Arşiv taslağı hazırlanır. Taslak PDF’yi kontrol ettikten sonra ayrı bir onayla belgeyi resmileştirebilirsiniz.</p></div>
        <div class="record-actions compact-actions"><span data-form-message></span><button class="btn btn-ghost" type="button" data-close-dialog>Vazgeç</button><button class="btn btn-primary" type="submit">Taslak Oluştur</button></div>
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
