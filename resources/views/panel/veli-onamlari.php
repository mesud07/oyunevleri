<?php
$kayitlar = $kayitlar ?? [];
$eslesen = count(array_filter($kayitlar, static fn(array $kayit): bool => !empty($kayit['veli_eslesti'])));
?>
<section class="page-head">
    <div>
        <h1>Veli Onam Formları</h1>
        <p>QR bağlantısından doldurulan oyun grubu katılımcı bilgi ve veli onam kayıtları.</p>
    </div>
</section>

<section class="consent-admin-grid">
    <article class="panel-card consent-share-card">
        <div>
            <div class="info-box compact-info"><strong>Form dijitale aktarıldı</strong><p>Paylaştığınız katılımcı bilgi alanları, sağlık soruları, görsel izinleri ve program onamı bu bağlantıda yer alıyor.</p></div>
            <span class="consent-kicker">ORTAK FORM BAĞLANTISI</span>
            <h2><?= e($ayar['baslik'] ?? 'Veli Onam Formu') ?></h2>
            <p>Bu QR kod tüm yeni veliler için kullanılabilir. Bağlantı kuruma özeldir.</p>
            <label><span>Paylaşılabilir bağlantı</span><input value="<?= e($link) ?>" readonly onclick="this.select()"></label>
            <div class="record-actions">
                <a class="btn btn-primary" href="<?= e($link) ?>" target="_blank" rel="noopener">Formu Aç</a>
                <a class="btn btn-ghost" href="/panel/veli-onamlari/qr.svg?indir=1">QR Kodu İndir</a>
                <?php if (!empty($ayarDuzenleyebilir)) : ?>
                    <button class="btn btn-ghost" type="button" data-open-dialog="#veli-onam-ayari-dialog">Onam Metnini Düzenle</button>
                <?php endif; ?>
            </div>
        </div>
        <a class="consent-qr" href="<?= e($link) ?>" target="_blank" rel="noopener" aria-label="Veli onam formunu aç">
            <img src="/panel/veli-onamlari/qr.svg" alt="Veli onam formu QR kodu">
        </a>
    </article>

    <div class="consent-admin-stats">
        <article><span>Toplam Form</span><strong><?= e((string) count($kayitlar)) ?></strong></article>
        <article><span>Veliyle Eşleşen</span><strong><?= e((string) $eslesen) ?></strong></article>
        <article><span>Yeni Veli</span><strong><?= e((string) (count($kayitlar) - $eslesen)) ?></strong></article>
    </div>
</section>

<?php if (!empty($ayarDuzenleyebilir)) : ?>
    <dialog id="veli-onam-ayari-dialog" class="appointment-dialog consent-settings-dialog">
        <form
            method="dialog"
            class="appointment-dialog-form record-form consent-settings-form"
            data-ajax-form="veli_onam_ayari_kaydet"
            data-success-redirect="/panel/veli-onamlari"
        >
            <div class="dialog-head">
                <div>
                    <h2>Program Kuralları ve Katılım Onamı</h2>
                    <p>Form sürümü <?= e((string) ($ayar['form_surumu'] ?? 1)) ?></p>
                </div>
                <button type="button" data-close-dialog aria-label="Pencereyi kapat">×</button>
            </div>
            <div class="info-box compact-info">
                <strong><?= e($kullanici['kurum_adi'] ?? 'Kurumunuz') ?> için geçerlidir</strong>
                <p>Değişiklik yeni doldurulan formlara uygulanır; daha önce verilmiş onamların kayıtlı metni değişmez.</p>
            </div>
            <label>
                <span>Program Kuralları ve Katılım Onamı Metni</span>
                <textarea name="onam_metni" rows="16" minlength="20" maxlength="30000" required><?= e($ayar['onam_metni'] ?? '') ?></textarea>
            </label>
            <div class="record-actions compact-actions">
                <span data-form-message aria-live="polite"></span>
                <button class="btn btn-ghost" type="button" data-close-dialog>Vazgeç</button>
                <button class="btn btn-primary" type="submit">Onam Metnini Kaydet</button>
            </div>
        </form>
    </dialog>
<?php endif; ?>

<section class="panel-card consent-records-card">
    <div class="section-title"><div><h2>Dijital Onam Kayıtları</h2><p class="muted">Telefon numarası ve katılımcı adıyla mevcut veli ve öğrenci kayıtları otomatik eşleştirilir; randevu kaydı aranmaz.</p></div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Katılımcı</th><th>Veli</th><th>Telefon</th><th>Eşleşme</th><th>Onay Tarihi</th><th>İşlemler</th></tr></thead>
            <tbody>
                <?php if (!$kayitlar) : ?><tr><td colspan="6">Henüz dijital onam formu doldurulmadı.</td></tr><?php endif; ?>
                <?php foreach ($kayitlar as $kayit) : ?>
                    <tr>
                        <td>
                            <?php if (!empty($kayit['ogrenci_id'])) : ?><a class="table-link" href="/panel/ogrenciler/profil?id=<?= e($kayit['ogrenci_id']) ?>"><?= e($kayit['ogrenci_ad_soyad']) ?></a><?php else : ?><?= e($kayit['ogrenci_ad_soyad']) ?><?php endif; ?>
                        </td>
                        <td><?= e($kayit['veli_ad_soyad']) ?></td>
                        <td><?= e($kayit['veli_telefon']) ?></td>
                        <td>
                            <?php
                            $eslesmeEtiketi = !empty($kayit['ogrenci_eslesti']) && !empty($kayit['veli_eslesti'])
                                ? 'Öğrenci ve veli'
                                : (!empty($kayit['ogrenci_eslesti']) ? 'Mevcut öğrenci' : (!empty($kayit['veli_eslesti']) ? 'Mevcut veli' : 'Yeni kayıt'));
                            ?>
                            <span class="status-pill<?= empty($kayit['ogrenci_eslesti']) && empty($kayit['veli_eslesti']) ? ' is-danger' : '' ?>"><?= e($eslesmeEtiketi) ?></span>
                        </td>
                        <td><?= e(date('d.m.Y H:i', strtotime((string) $kayit['onay_tarihi']))) ?></td>
                        <td>
                            <div class="record-actions compact-actions">
                                <?php if (isset($menuIzin) && $menuIzin('randevu_ekle')) : ?>
                                    <button
                                        class="btn btn-primary"
                                        type="button"
                                        data-open-dialog="#hizli-randevu-dialog"
                                        data-quick-appointment-prefill
                                        data-onam-id="<?= e($kayit['id']) ?>"
                                        data-ogrenci-id="<?= e($kayit['ogrenci_id'] ?? '') ?>"
                                        data-ogrenci-ad-soyad="<?= e($kayit['ogrenci_ad_soyad']) ?>"
                                        data-dogum-tarihi="<?= e($kayit['ogrenci_dogum_tarihi'] ?? '') ?>"
                                        data-veli-ad-soyad="<?= e($kayit['veli_ad_soyad']) ?>"
                                        data-veli-telefon="<?= e($kayit['veli_telefon']) ?>"
                                    >Hızlı Randevu</button>
                                <?php endif; ?>
                                <a class="btn btn-ghost" href="/panel/veli-onamlari/pdf?id=<?= e($kayit['id']) ?>" target="_blank">PDF Aç</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
