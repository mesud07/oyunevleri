<?php
$kayitlar = $kayitlar ?? [];
$ozet = $ozet ?? [];
$baslangic = $baslangic ?? date('Y-m-d');
$bitis = $bitis ?? $baslangic;
$bugun = date('Y-m-d');
$dun = date('Y-m-d', strtotime('-1 day'));
$haftaBaslangic = date('Y-m-d', strtotime('monday this week'));
$haftaBitis = date('Y-m-d', strtotime('sunday this week'));
$ogrenciler = $ogrenciler ?? [];
$yapayZekaHazir = (bool) ($yapayZekaHazir ?? false);
$tarihSaatYaz = static function ($tarih): string {
    if (!$tarih) {
        return '-';
    }

    $ts = strtotime((string) $tarih);
    return $ts ? date('d.m.Y H:i', $ts) : (string) $tarih;
};
$saatYaz = static fn($saat): string => $saat ? substr((string) $saat, 0, 5) : '-';
?>
<section class="page-head">
    <div>
        <h1>Gunluk Notlar</h1>
        <p>Randevular uzerinden girilen davranis, gozlem ve gunluk takip notlari.</p>
    </div>
    <form class="head-actions daily-record-filter" method="get">
        <?php if (yetki_var('randevu_ekle')) : ?>
            <button class="btn btn-primary ai-report-open" type="button" data-ai-report-open>Yapay Zeka ile Rapor Hazirla</button>
        <?php endif; ?>
        <a class="btn btn-ghost" href="/panel/gunluk-kayitlar?baslangic=<?= e($bugun) ?>&bitis=<?= e($bugun) ?>">Bugun</a>
        <a class="btn btn-ghost" href="/panel/gunluk-kayitlar?baslangic=<?= e($dun) ?>&bitis=<?= e($dun) ?>">Dun</a>
        <a class="btn btn-ghost" href="/panel/gunluk-kayitlar?baslangic=<?= e($haftaBaslangic) ?>&bitis=<?= e($haftaBitis) ?>">Bu Hafta</a>
        <label>
            Baslangic
            <input type="date" name="baslangic" value="<?= e($baslangic) ?>">
        </label>
        <label>
            Bitis
            <input type="date" name="bitis" value="<?= e($bitis) ?>">
        </label>
        <button class="btn btn-primary" type="submit">Uygula</button>
    </form>
</section>

<?php if (yetki_var('randevu_ekle')) : ?>
<dialog class="appointment-dialog ai-report-dialog" data-ai-report-dialog>
    <form method="dialog" class="appointment-dialog-form" data-ai-report-form>
        <div class="dialog-head">
            <div>
                <span class="ai-report-kicker">OGRETMEN ASISTANI</span>
                <h2>Ogrenci Gozlem Raporu</h2>
                <p>Gunluk notlari bir araya getirir, kontrol edilebilir bir rapor taslagi hazirlar.</p>
            </div>
            <button type="button" data-ai-report-close aria-label="Pencereyi kapat">x</button>
        </div>

        <div class="ai-report-setup" data-ai-report-setup>
            <label>
                <span>Ogrenci</span>
                <select name="ogrenci_id" required>
                    <option value="">Ogrenci secin</option>
                    <?php foreach ($ogrenciler as $ogrenci) : ?>
                        <option value="<?= e((string) ($ogrenci['id'] ?? '')) ?>"><?= e($ogrenci['ad_soyad'] ?? '-') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <fieldset>
                <legend>Gozlem donemi</legend>
                <label class="ai-period-option">
                    <input type="radio" name="donem" value="1" checked>
                    <span><strong>Son 1 Aylik</strong><small>Kisa donem gelisim ozeti</small></span>
                </label>
                <label class="ai-period-option">
                    <input type="radio" name="donem" value="3">
                    <span><strong>Son 3 Aylik</strong><small>Daha genis gelisim ve uyum ozeti</small></span>
                </label>
            </fieldset>
            <div class="ai-privacy-note">
                <strong>Kontrol sizde</strong>
                <span>Secili ogrencinin adi ile notlardaki telefon, e-posta ve kimlik numarasi gibi tanimlayici bilgiler maskelenerek yapay zeka hizmetine gonderilir. Taslagi PDF yapmadan once mutlaka okuyup duzenleyin.</span>
            </div>
            <?php if (!$yapayZekaHazir) : ?>
                <div class="ai-config-warning">Yapay zeka baglantisi henuz yapilandirilmamis. Sunucuda OPENAI_API_KEY tanimlanmalidir.</div>
            <?php endif; ?>
        </div>

        <div class="ai-report-editor" data-ai-report-editor hidden>
            <div class="ai-report-editor-head">
                <div><strong data-ai-report-student></strong><span data-ai-report-period></span></div>
                <span data-ai-report-note-count></span>
            </div>
            <?php
            $raporAlanlari = [
                'genel_gozlem' => 'Genel Gozlem',
                'etkinliklere_katilim' => 'Etkinliklere Katilimi',
                'yonerge_ve_grup_uyumu' => 'Yonerge ve Grup Uyumu',
                'cocuk_gelisimci_gorusu' => 'Cocuk Gelisimci Gorusu',
                'psikolojik_danisman_gorusu' => 'Psikolojik Danisman Gorusu',
                'genel_degerlendirme' => 'Genel Degerlendirme',
            ];
            ?>
            <?php foreach ($raporAlanlari as $anahtar => $etiket) : ?>
                <label><span><?= e($etiket) ?></span><textarea rows="5" data-report-section="<?= e($anahtar) ?>" maxlength="6000"></textarea></label>
            <?php endforeach; ?>
        </div>

        <div class="record-actions ai-report-actions">
            <span data-ai-report-message></span>
            <button class="btn btn-ghost" type="button" data-ai-report-back hidden>Secimi Degistir</button>
            <button class="btn btn-ghost" type="button" data-ai-report-close>Vazgec</button>
            <button class="btn btn-primary" type="submit" data-ai-report-generate <?= !$yapayZekaHazir ? 'disabled' : '' ?>>Taslak Olustur</button>
            <button class="btn btn-primary" type="button" data-ai-report-pdf hidden>PDF Indir</button>
        </div>
    </form>
</dialog>
<form method="post" action="/panel/gunluk-kayitlar/rapor.pdf" target="_blank" data-ai-report-pdf-form hidden>
    <input type="hidden" name="csrf" value="<?= e($csrf ?? '') ?>">
    <input type="hidden" name="rapor" value="">
</form>
<?php endif; ?>

<section class="report-grid report-summary daily-record-summary">
    <article class="report-card accent-blue"><span>Not</span><strong><?= e((string) ($ozet['not_sayisi'] ?? 0)) ?></strong></article>
    <article class="report-card accent-green"><span>Ogrenci</span><strong><?= e((string) ($ozet['ogrenci_sayisi'] ?? 0)) ?></strong></article>
    <article class="report-card accent-purple"><span>Kategori</span><strong><?= e((string) ($ozet['kategori_sayisi'] ?? 0)) ?></strong></article>
    <article class="report-card accent-teal">
        <span>Tarih</span>
        <strong><?= e(tarih_goster($baslangic)) ?><?= $bitis !== $baslangic ? ' - ' . e(tarih_goster($bitis)) : '' ?></strong>
    </article>
</section>

<section class="panel-card report-panel">
    <div class="definition-head">
        <div>
            <h2>Not Akisi</h2>
            <p>Secilen tarih araliginda eklenen tum gunluk notlar.</p>
        </div>
        <span class="payment-total-pill is-total"><?= e((string) count($kayitlar)) ?> not</span>
    </div>

    <div class="daily-note-list">
        <?php if (empty($kayitlar)) : ?>
            <div class="empty-state">Secilen tarih araliginda gunluk not bulunamadi.</div>
        <?php endif; ?>
        <?php foreach ($kayitlar as $row) : ?>
            <article class="daily-note-card">
                <div class="daily-note-card-head">
                    <div>
                        <strong><?= e(tarih_goster($row['tarih'] ?? null)) ?> <?= e($saatYaz($row['baslangic_saati'] ?? null)) ?></strong>
                        <a href="/panel/ogrenciler/profil?id=<?= e((string) ($row['ogrenci_id'] ?? '')) ?>"><?= e($row['ogrenci'] ?? '-') ?></a>
                    </div>
                    <span><?= e($row['kategori'] ?? 'Genel') ?></span>
                </div>
                <p><?= nl2br(e($row['not_metni'] ?? '')) ?></p>
                <footer>
                    <span><?= e($row['randevu_tanimi'] ?? '-') ?><?= !empty($row['grup']) ? ' / ' . e($row['grup']) : '' ?></span>
                    <span><?= e($row['kaydeden'] ?? '-') ?> - <?= e($tarihSaatYaz($row['olusturulma_tarihi'] ?? null)) ?></span>
                </footer>
            </article>
        <?php endforeach; ?>
    </div>
</section>
