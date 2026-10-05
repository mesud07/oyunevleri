<?php
$kayitlar = $kayitlar ?? [];
$ozet = $ozet ?? [];
$cocukIsletmeleri = $cocukIsletmeleri ?? [];
$cocukIsletmeleriSonAktarim = $cocukIsletmeleriSonAktarim ?? null;
$haritaNoktalari = array_values(array_map(static fn(array $row): array => [
    'ogrenci_id' => (int) $row['ogrenci_id'],
    'ogrenci' => (string) $row['ogrenci'],
    'veli' => (string) $row['veli'],
    'telefon' => (string) $row['veli_telefon'],
    'il' => (string) ($row['il'] ?? ''),
    'ilce' => (string) ($row['ilce'] ?? ''),
    'adres' => (string) ($row['adres'] ?? ''),
    'adres_anahtari' => \App\Models\OgrenciAdresHaritasi::adresAnahtari($row),
    'enlem' => $row['adres_enlem'] !== null ? (float) $row['adres_enlem'] : null,
    'boylam' => $row['adres_boylam'] !== null ? (float) $row['adres_boylam'] : null,
    'dogrulandi' => !empty($row['adres_konum_dogrulandi']),
    'aktif_randevusu_var' => !empty($row['aktif_randevusu_var']),
], $kayitlar));
$isletmeNoktalari = array_values(array_map(static fn(array $row): array => [
    'id' => (int) $row['id'],
    'ad' => (string) $row['ad'],
    'kategori' => (string) $row['kategori'],
    'adres' => (string) ($row['adres'] ?? ''),
    'ilce' => (string) ($row['ilce'] ?? ''),
    'telefon' => (string) ($row['telefon'] ?? ''),
    'web_sitesi' => (string) ($row['web_sitesi'] ?? ''),
    'kaynak' => (string) ($row['kaynak'] ?? 'openstreetmap'),
    'enlem' => (float) $row['enlem'],
    'boylam' => (float) $row['boylam'],
], $cocukIsletmeleri));
?>

<section class="page-head">
    <div>
        <h1>Veli Adres Yoğunluk Haritası</h1>
        <p>Öğrencilerin ev adreslerini ve velilerin hangi bölgelerden geldiğini harita üzerinde inceleyin.</p>
    </div>
    <a class="btn btn-primary" href="/panel/ogrenciler/yeni">Yeni Adresli Öğrenci</a>
</section>

<section class="report-grid report-summary">
    <article class="report-card accent-blue"><span>Adresli öğrenci</span><strong><?= e((string) ($ozet['adresli'] ?? 0)) ?></strong></article>
    <article class="report-card accent-green"><span>Haritada doğrulandı</span><strong><?= e((string) ($ozet['dogrulanan'] ?? 0)) ?></strong></article>
    <article class="report-card accent-purple"><span>Konum bekliyor</span><strong><?= e((string) ($ozet['bekleyen'] ?? 0)) ?></strong></article>
    <article class="report-card accent-teal"><span>En yoğun ilçe</span><strong><?= e((string) (array_key_first($ozet['ilceler'] ?? []) ?? '-')) ?></strong></article>
</section>

<?php if (empty($googleHaritaAnahtari)) : ?>
    <div class="info-box compact-info map-provider-warning">
        <strong>Sokak seviyesinde otomatik konum için Google Maps anahtarı gerekli</strong>
        <p><code>GOOGLE_MAPS_API_KEY</code> tanımlandığında açık adresler otomatik olarak sokak/cadde seviyesinde çözümlenir. Şu anda yalnız ilçe bazlı yaklaşık görünüm kullanılabilir.</p>
    </div>
<?php endif; ?>

<section class="address-map-layout" data-address-map-root data-google-enabled="<?= !empty($googleHaritaAnahtari) ? '1' : '0' ?>" data-tile-url="<?= e($haritaKaroAdresi ?? 'https://tile.openstreetmap.org/{z}/{x}/{y}.png') ?>" data-points="<?= e(json_encode($haritaNoktalari, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>" data-businesses="<?= e(json_encode($isletmeNoktalari, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
    <article class="panel-card address-map-card">
        <div class="definition-head">
            <div>
                <h2>Yoğunluk Haritası</h2>
                <p>Numaralı işaretler o bölgede kaç kişi bulunduğunu gösterir. Haritayı yakınlaştırdıkça kümeler ayrılır ve her öğrenci kendi adres konumunda görünür. Küme işaretine tıklayarak doğrudan yakınlaşabilirsiniz.</p>
            </div>
            <div class="map-legend"><span><i class="is-cluster">3</i> Kişi kümesi</span><span><i class="is-exact"></i> Doğrulanmış adres</span><span><i class="is-approximate"></i> İlçe bazlı</span><span><i class="is-business"></i> Çocuk işletmesi</span></div>
        </div>
        <div class="address-map-controls" aria-label="Harita görünüm filtreleri">
            <label><input type="checkbox" data-active-students-only checked> Yalnız aktif öğrenciler <small>(gelecek randevusu var)</small></label>
            <label><input type="checkbox" data-heatmap-toggle checked> Isı dağılımını göster</label>
            <span data-visible-student-count></span>
        </div>
        <div class="address-map" data-address-map aria-label="Veli adres yoğunluk haritası"></div>
        <?php if (!empty($googleHaritaAnahtari)) : ?><p class="map-geocode-status" data-geocode-status>Adresler hazırlanıyor...</p><?php endif; ?>
        <p class="map-privacy-note"><?= !empty($googleHaritaAnahtari) ? 'Koordinatı kayıtlı adresler tekrar Google adres çözümleme servisine gönderilmez.' : 'İl ve ilçe kaydı bulunan öğrenciler yoğunluk haritasına dahil edilir. Adres metinleri harita sağlayıcısına gönderilmez.' ?></p>
        <p class="map-privacy-note">İşletme verisi yerel veritabanından gelir. Kaynaklar: <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">© OpenStreetMap katkıda bulunanlar, ODbL</a> ve Google Places. Google Places kayıtları 30 günlük yerel önbellekten gösterilir.</p>
    </article>

    <aside class="panel-card district-density-card">
        <h2>İlçe Yoğunluğu</h2>
        <p>Girilen adres bilgilerine göre.</p>
        <ol>
            <?php foreach (($ozet['ilceler'] ?? []) as $ilce => $adet) : ?>
                <li><span><?= e($ilce) ?></span><strong><?= e((string) $adet) ?></strong></li>
            <?php endforeach; ?>
            <?php if (empty($ozet['ilceler'])) : ?><li>Henüz adres girilmedi.</li><?php endif; ?>
        </ol>
        <div class="business-filters" data-business-filters>
            <h3>Çocuk İşletmeleri</h3>
            <label><input type="checkbox" value="okul" checked> Okullar</label>
            <label><input type="checkbox" value="anaokulu" checked> Anaokulları</label>
            <label><input type="checkbox" value="kres" checked> Kreşler</label>
            <label><input type="checkbox" value="oyun_evi" checked> Oyun evleri</label>
            <label><input type="checkbox" value="cocuk_parki" checked> Oyun alanları ve çocuk parkları</label>
            <p><strong><?= e((string) count($cocukIsletmeleri)) ?></strong> kayıt yerel veritabanında.</p>
            <?php if ($cocukIsletmeleriSonAktarim) : ?>
                <small>Son aktarım: <?= e(date('d.m.Y H:i', strtotime((string) $cocukIsletmeleriSonAktarim['aktarim_tarihi']))) ?></small>
            <?php endif; ?>
        </div>
    </aside>
</section>

<section class="panel-card report-panel">
    <div class="definition-head">
        <div>
            <h2>Adres ve Konum Doğrulama</h2>
            <p>Doğru yoğunluk için adresi Google Maps'te kontrol edin, ardından haritada gerçek bina konumuna tıklayın.</p>
        </div>
        <span class="payment-total-pill is-total" data-address-list-count><?= e((string) count($kayitlar)) ?> adres</span>
    </div>
    <div class="table-wrap">
        <table class="address-verification-table">
            <thead><tr><th>Öğrenci / Veli</th><th>Adres</th><th>Konum</th><th>İşlem</th></tr></thead>
            <tbody>
                <?php if (!$kayitlar) : ?><tr><td colspan="4">Henüz öğrenci adresi girilmedi.</td></tr><?php endif; ?>
                <tr data-address-filter-empty hidden><td colspan="4">Seçilen filtreye uygun öğrenci adresi bulunamadı.</td></tr>
                <?php foreach ($kayitlar as $row) : ?>
                    <?php $aramaAdresi = implode(', ', array_filter([$row['adres'] ?? '', $row['ilce'] ?? '', $row['il'] ?? '', 'Türkiye'])); ?>
                    <?php $konumEtiketi = !empty($row['adres_konum_dogrulandi']) ? 'Doğrulandı' : (!empty($row['ilce']) ? 'İlçe bazlı yaklaşık' : 'Bekliyor'); ?>
                    <tr data-address-student-row data-has-active-appointment="<?= !empty($row['aktif_randevusu_var']) ? '1' : '0' ?>">
                        <td><a class="table-link" href="/panel/ogrenciler/profil?id=<?= e($row['ogrenci_id']) ?>"><?= e($row['ogrenci']) ?></a><small class="table-subtext"><?= e($row['veli']) ?> · <?= e($row['veli_telefon']) ?></small></td>
                        <td><?= e($row['adres'] ?? '-') ?><small class="table-subtext"><?= e(implode(' / ', array_filter([$row['ilce'] ?? '', $row['il'] ?? '']))) ?></small></td>
                        <td><span class="status-pill <?= !empty($row['adres_konum_dogrulandi']) ? 'attendance-tested' : 'attendance-progress' ?>"><?= e($konumEtiketi) ?></span></td>
                        <td class="address-actions">
                            <a class="btn btn-ghost" href="https://www.google.com/maps/search/?api=1&query=<?= e(rawurlencode($aramaAdresi)) ?>" target="_blank" rel="noopener noreferrer">Google'da Kontrol Et</a>
                            <?php if (yetki_var('ogrenci_ekle') && empty($googleHaritaAnahtari)) : ?>
                                <button class="btn btn-primary" type="button" data-location-edit="<?= e($row['ogrenci_id']) ?>">Haritada İşaretle</button>
                            <?php elseif (!empty($googleHaritaAnahtari)) : ?>
                                <span class="status-pill attendance-tested">Otomatik konum</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if (yetki_var('ogrenci_ekle')) : ?>
<dialog class="appointment-dialog location-picker-dialog" data-location-dialog>
    <form method="dialog" class="appointment-dialog-form" data-location-form>
        <div class="dialog-head"><div><h2>Adres Konumunu Doğrula</h2><p data-location-student></p></div><button type="button" data-location-close>x</button></div>
        <input type="hidden" name="ogrenci_id"><input type="hidden" name="enlem"><input type="hidden" name="boylam">
        <div class="location-address" data-location-address></div>
        <div class="location-picker-map" data-location-picker-map></div>
        <p class="muted">Haritada binanın bulunduğu noktaya tıklayın. İşaretçiyi sürükleyerek ince ayar yapabilirsiniz.</p>
        <div class="record-actions compact-actions"><span data-location-message></span><button class="btn btn-ghost" type="button" data-location-close>Vazgeç</button><button class="btn btn-primary" type="submit">Konumu Kaydet</button></div>
    </form>
</dialog>
<?php endif; ?>
