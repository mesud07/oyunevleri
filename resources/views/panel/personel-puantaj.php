<?php
$personeller = $personeller ?? [];
$kullaniciSecenekleri = $kullaniciSecenekleri ?? [];
$gunluk = $gunluk ?? [];
$aylik = $aylik ?? ['ay' => date('Y-m'), 'personeller' => [], 'kayitlar' => []];
$durumlar = $durumlar ?? [];
$bordro = $bordro ?? ['destekleniyor' => false, 'satirlar' => [], 'toplamlar' => []];
$sgkTesvikler = $sgkTesvikler ?? [];
$ay = $ay ?? date('Y-m');
$tarih = $tarih ?? date('Y-m-d');
$yonetebilir = yetki_var('personel_yonet');
$bugun = date('Y-m-d');
$gunSayisi = (int) date('t', strtotime($ay . '-01'));
$oncekiAy = date('Y-m', strtotime($ay . '-01 -1 month'));
$sonrakiAy = date('Y-m', strtotime($ay . '-01 +1 month'));
$gunAdlari = [1 => 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
$ayAdlari = [1 => 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
$ayBasligi = $ayAdlari[(int) substr($ay, 5, 2)] . ' ' . substr($ay, 0, 4);
$saat = static fn($deger): string => $deger ? substr((string) $deger, 0, 5) : '-';
$gunlukOzet = ['giris' => 0, 'tamamlanan' => 0, 'eksik' => 0];
foreach ($gunluk as $satir) {
    if (!empty($satir['giris_saati'])) {
        $gunlukOzet['giris']++;
    }
    if (!empty($satir['giris_saati']) && !empty($satir['cikis_saati'])) {
        $gunlukOzet['tamamlanan']++;
    }
    if (empty($satir['giris_saati']) || empty($satir['cikis_saati'])) {
        $gunlukOzet['eksik']++;
    }
}
$kodSinifi = [
    'calisti' => 'is-worked', 'hafta_tatili' => 'is-weekend', 'raporlu' => 'is-report',
    'izinli' => 'is-leave', 'resmi_tatil' => 'is-holiday', 'devamsiz' => 'is-absent',
];
?>

<section class="page-head attendance-page-head">
    <div>
        <h1>Personel ve Puantaj</h1>
        <p>Günlük giriş–çıkış hareketlerini kaydedin, aylık puantajı yönetin ve Excel raporu alın.</p>
    </div>
    <div class="head-actions">
        <a class="btn btn-ghost" href="/panel/personeller/puantaj.xlsx?ay=<?= e($ay) ?>">Excel İndir</a>
        <?php if ($yonetebilir) : ?><button class="btn btn-primary" type="button" data-personel-yeni>Personel Ekle</button><?php endif; ?>
    </div>
</section>

<section class="report-grid report-summary attendance-summary">
    <article class="report-card accent-blue"><span>Aktif Personel</span><strong><?= count($gunluk) ?></strong></article>
    <article class="report-card accent-green"><span>Giriş Yapan</span><strong><?= $gunlukOzet['giris'] ?></strong></article>
    <article class="report-card accent-teal"><span>Giriş–Çıkış Tamam</span><strong><?= $gunlukOzet['tamamlanan'] ?></strong></article>
    <article class="report-card accent-purple"><span>Eksik Hareket</span><strong><?= $gunlukOzet['eksik'] ?></strong></article>
</section>

<section class="panel-card report-panel attendance-daily" data-personel-page>
    <div class="definition-head attendance-section-head">
        <div>
            <h2>Günlük Giriş–Çıkış</h2>
            <p>Personelin geliş, çıkış, mola ve günlük çalışma bilgisini takip edin.</p>
        </div>
        <form class="attendance-filter" method="get">
            <input type="hidden" name="ay" value="<?= e($ay) ?>">
            <label><span>Tarih</span><input type="date" name="tarih" value="<?= e($tarih) ?>"></label>
            <button class="btn btn-primary" type="submit">Göster</button>
        </form>
    </div>
    <div class="table-wrap attendance-daily-table">
        <table>
            <thead><tr><th>Personel</th><th>Pozisyon</th><th>Planlanan</th><th>Giriş</th><th>Çıkış</th><th>Durum</th><th>Net Çalışma</th><?php if ($yonetebilir) : ?><th>İşlem</th><?php endif; ?></tr></thead>
            <tbody>
            <?php if (empty($gunluk)) : ?><tr><td colspan="8" class="empty-table">Aktif personel kaydı bulunamadı.</td></tr><?php endif; ?>
            <?php foreach ($gunluk as $satir) :
                $durum = (string) ($satir['durum'] ?? '');
                $dakika = $satir['calisma_dakika'] ?? null;
                $net = $dakika === null ? '-' : sprintf('%d sa %02d dk', intdiv((int) $dakika, 60), (int) $dakika % 60);
                $duzenleVerisi = [
                    'personel_id' => (int) $satir['personel_id'], 'ad_soyad' => trim((string) $satir['ad'] . ' ' . (string) $satir['soyad']),
                    'tarih' => $tarih, 'durum' => $durum !== '' ? $durum : 'calisti',
                    'giris_saati' => $satir['giris_saati'] ? substr((string) $satir['giris_saati'], 0, 5) : '',
                    'cikis_saati' => $satir['cikis_saati'] ? substr((string) $satir['cikis_saati'], 0, 5) : '',
                    'mola_dakika' => (int) ($satir['mola_dakika'] ?? 0), 'aciklama' => (string) ($satir['aciklama'] ?? ''),
                ];
            ?>
                <tr>
                    <td><strong><?= e(trim((string) $satir['ad'] . ' ' . (string) $satir['soyad'])) ?></strong><small><?= e((string) ($satir['tc_kimlik_no'] ?? '')) ?></small></td>
                    <td><?= e((string) ($satir['pozisyon'] ?: '-')) ?></td>
                    <td><?= e($saat($satir['varsayilan_giris_saati'])) ?> – <?= e($saat($satir['varsayilan_cikis_saati'])) ?></td>
                    <td><?= e($saat($satir['giris_saati'])) ?></td><td><?= e($saat($satir['cikis_saati'])) ?></td>
                    <td><?php if ($durum !== '') : ?><span class="attendance-status <?= e($kodSinifi[$durum] ?? '') ?>"><?= e($durumlar[$durum]['ad'] ?? $durum) ?></span><?php else : ?><span class="attendance-status is-empty">Kayıt yok</span><?php endif; ?></td>
                    <td><?= e($net) ?></td>
                    <?php if ($yonetebilir) : ?><td><div class="attendance-row-actions">
                        <?php if ($tarih === $bugun && empty($satir['giris_saati'])) : ?><button class="mini-btn" type="button" data-hareket="giris" data-personel-id="<?= (int) $satir['personel_id'] ?>">Giriş</button><?php endif; ?>
                        <?php if ($tarih === $bugun && !empty($satir['giris_saati']) && empty($satir['cikis_saati'])) : ?><button class="mini-btn" type="button" data-hareket="cikis" data-personel-id="<?= (int) $satir['personel_id'] ?>">Çıkış</button><?php endif; ?>
                        <button class="mini-btn" type="button" data-puantaj-duzenle='<?= e(json_encode($duzenleVerisi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'>Düzenle</button>
                    </div></td><?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel-card report-panel payroll-summary">
    <div class="definition-head attendance-section-head">
        <div>
            <h2><?= e($ayBasligi) ?> Maaş ve SGK Tahmini</h2>
            <p>Brüt maaş ve puantaja göre net ödeme, SGK yükü ve işveren maliyeti tahmini.</p>
        </div>
        <span class="attendance-status is-empty">30 gün esası</span>
    </div>
    <?php if (empty($bordro['destekleniyor'])) : ?>
        <div class="info-note warning-note">Seçilen yıl için bordro oranları tanımlı değil. Hesaplama yapabilmek için yıllık resmî oranların sisteme eklenmesi gerekir.</div>
    <?php else : ?>
        <div class="report-grid report-summary payroll-totals">
            <article class="report-card accent-blue"><span>Brüt Hakediş</span><strong><?= e(para_goster($bordro['toplamlar']['brut_hakedis'] ?? 0)) ?></strong></article>
            <article class="report-card accent-green"><span>Net Ödenecek</span><strong><?= e(para_goster($bordro['toplamlar']['net_odeme'] ?? 0)) ?></strong></article>
            <article class="report-card accent-purple"><span>Toplam SGK</span><strong><?= e(para_goster($bordro['toplamlar']['sgk_toplam'] ?? 0)) ?></strong></article>
            <article class="report-card accent-teal"><span>İşveren Maliyeti</span><strong><?= e(para_goster($bordro['toplamlar']['isveren_maliyeti'] ?? 0)) ?></strong></article>
        </div>
        <div class="table-wrap payroll-table">
            <table>
                <thead><tr><th>Personel</th><th>Aylık Brüt</th><th>SGK Gün</th><th>Brüt Hakediş</th><th>Çalışan SGK</th><th>Vergiler</th><th>Net Ödeme</th><th>İşveren SGK</th><th>Toplam SGK</th><th>İşveren Maliyeti</th></tr></thead>
                <tbody>
                <?php if (empty($bordro['satirlar'])) : ?><tr><td colspan="10" class="empty-table">Bordro hesabına dahil aktif personel bulunamadı.</td></tr><?php endif; ?>
                <?php foreach (($bordro['satirlar'] ?? []) as $bordroSatiri) : ?>
                    <tr>
                        <td><strong><?= e((string) $bordroSatiri['ad_soyad']) ?></strong><small><?= e((string) $bordroSatiri['sgk_tesvik_adi']) ?></small></td>
                        <?php if ((float) ($bordroSatiri['aylik_brut_ucret'] ?? 0) <= 0) : ?>
                            <td colspan="9"><button class="mini-btn" type="button" data-personel-duzenle="<?= (int) $bordroSatiri['personel_id'] ?>">Brüt maaş tanımlayın</button></td>
                        <?php else : ?>
                            <td><?= e(para_goster($bordroSatiri['aylik_brut_ucret'])) ?></td>
                            <td><strong><?= (int) $bordroSatiri['sgk_gunu'] ?></strong></td>
                            <td><?= e(para_goster($bordroSatiri['brut_hakedis'])) ?></td>
                            <td><?= e(para_goster((float) $bordroSatiri['sgk_calisan'] + (float) $bordroSatiri['issizlik_calisan'])) ?><small>SGK + işsizlik</small></td>
                            <td><?= e(para_goster($bordroSatiri['vergi_toplam'])) ?><small>Gelir + damga</small></td>
                            <td><strong><?= e(para_goster($bordroSatiri['net_odeme'])) ?></strong></td>
                            <td><?= e(para_goster((float) $bordroSatiri['sgk_isveren'] + (float) $bordroSatiri['issizlik_isveren'])) ?><small>%<?= e(number_format((float) $bordroSatiri['sgk_isveren_orani'], 2, ',', '.')) ?> + %2</small></td>
                            <td><?= e(para_goster($bordroSatiri['sgk_toplam'])) ?></td>
                            <td><strong><?= e(para_goster($bordroSatiri['isveren_maliyeti'])) ?></strong></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="payroll-disclaimer">2026 resmî oranları kullanılır. Raporlu ve devamsız günler ücret/SGK gününden düşülür; diğer durumlar ücretlidir. Gelir vergisi, mevcut brüt ücretin yıl boyunca geçerli olduğu varsayımıyla kümülatif tahmin edilir. Bu ekran resmî bordro yerine geçmez.</p>
    <?php endif; ?>
</section>

<section class="panel-card report-panel attendance-monthly">
    <div class="definition-head attendance-section-head">
        <div><h2><?= e($ayBasligi) ?> Puantaj Tablosu</h2><p>Fareyi basılı tutup sürükleyerek birden fazla günü seçin; ayrıntı için hücreye çift tıklayın.</p></div>
        <div class="attendance-month-nav">
            <a class="btn btn-ghost" href="?ay=<?= e($oncekiAy) ?>&tarih=<?= e($tarih) ?>" aria-label="Önceki ay">‹</a>
            <form method="get"><input type="hidden" name="tarih" value="<?= e($tarih) ?>"><input type="month" name="ay" value="<?= e($ay) ?>"><button class="btn btn-primary" type="submit">Uygula</button></form>
            <a class="btn btn-ghost" href="?ay=<?= e($sonrakiAy) ?>&tarih=<?= e($tarih) ?>" aria-label="Sonraki ay">›</a>
        </div>
    </div>
    <div class="attendance-legend" aria-label="Puantaj kodları">
        <?php foreach ($durumlar as $anahtar => $bilgi) : ?>
            <?php if ($yonetebilir) : ?>
                <button type="button" class="<?= e($kodSinifi[$anahtar] ?? '') ?>" data-puantaj-toplu-durum="<?= e($anahtar) ?>" data-puantaj-durum-adi="<?= e($bilgi['ad']) ?>" disabled><b><?= e($bilgi['kod']) ?></b> <?= e($bilgi['ad']) ?></button>
            <?php else : ?>
                <span class="<?= e($kodSinifi[$anahtar] ?? '') ?>"><b><?= e($bilgi['kod']) ?></b> <?= e($bilgi['ad']) ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php if ($yonetebilir) : ?>
        <div class="attendance-bulk-toolbar" data-puantaj-bulk-toolbar aria-live="polite">
            <div><strong data-puantaj-secim-sayisi>0 hücre seçildi</strong><span>Birden fazla hücre seçtiğinizde fareyi bırakınca toplu düzenleme penceresi açılır.</span></div>
            <button class="btn btn-ghost" type="button" data-puantaj-secimi-temizle disabled>Seçimi Temizle</button>
        </div>
    <?php endif; ?>
    <div class="table-wrap attendance-grid-wrap">
        <table class="attendance-grid">
            <thead><tr><th class="is-sticky-id">T.C.K.N.</th><th class="is-sticky-name">Adı Soyadı</th>
                <?php for ($gun = 1; $gun <= $gunSayisi; $gun++) : $gunTarih = sprintf('%s-%02d', $ay, $gun); $haftaSonu = (int) date('N', strtotime($gunTarih)) >= 6; ?>
                    <th class="day-head <?= $haftaSonu ? 'is-weekend' : '' ?>"><span><?= $gun ?></span><small><?= e($gunAdlari[(int) date('N', strtotime($gunTarih))]) ?></small></th>
                <?php endfor; ?>
                <?php foreach ($durumlar as $bilgi) : ?><th class="summary-head"><span><?= e($bilgi['kod']) ?></span></th><?php endforeach; ?><th class="summary-head"><span>Top.</span></th>
            </tr></thead>
            <tbody>
            <?php if (empty($aylik['personeller'])) : ?><tr><td colspan="40" class="empty-table">Puantaj oluşturmak için önce personel ekleyin.</td></tr><?php endif; ?>
            <?php foreach (($aylik['personeller'] ?? []) as $personelSatir => $personel) :
                $personelId = (int) $personel['id']; $sayaclar = array_fill_keys(array_keys($durumlar), 0); $doluGun = 0;
            ?><tr><td class="is-sticky-id"><?= e((string) ($personel['tc_kimlik_no'] ?: '-')) ?></td><td class="is-sticky-name"><button type="button" class="personel-name-button" data-personel-duzenle="<?= $personelId ?>"><?= e(trim((string) $personel['ad'] . ' ' . (string) $personel['soyad'])) ?></button></td>
                <?php for ($gun = 1; $gun <= $gunSayisi; $gun++) :
                    $gunTarih = sprintf('%s-%02d', $ay, $gun); $haftaSonu = (int) date('N', strtotime($gunTarih)) >= 6;
                    $kayit = $aylik['kayitlar'][$personelId][$gunTarih] ?? null;
                    $durum = $kayit ? (string) $kayit['durum'] : ($haftaSonu ? 'hafta_tatili' : '');
                    if ($durum !== '') { $sayaclar[$durum] = ($sayaclar[$durum] ?? 0) + 1; $doluGun++; }
                    $cellData = ['personel_id' => $personelId, 'ad_soyad' => trim((string) $personel['ad'] . ' ' . (string) $personel['soyad']), 'tarih' => $gunTarih,
                        'durum' => $durum !== '' ? $durum : 'calisti', 'giris_saati' => $kayit && $kayit['giris_saati'] ? substr((string) $kayit['giris_saati'], 0, 5) : '',
                        'cikis_saati' => $kayit && $kayit['cikis_saati'] ? substr((string) $kayit['cikis_saati'], 0, 5) : '', 'mola_dakika' => (int) ($kayit['mola_dakika'] ?? 0), 'aciklama' => (string) ($kayit['aciklama'] ?? '')];
                ?><td class="attendance-day <?= e($kodSinifi[$durum] ?? '') ?> <?= $haftaSonu ? 'is-weekend-column' : '' ?>"><?php if ($yonetebilir) : ?><button type="button" data-puantaj-cell data-grid-row="<?= (int) $personelSatir ?>" data-grid-col="<?= (int) ($gun - 1) ?>" data-puantaj-kayit='<?= e(json_encode($cellData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>' title="Seçmek için tıklayın, ayrıntı için çift tıklayın: <?= e($gunTarih) ?>" aria-selected="false"><?= e($durum !== '' ? ($durumlar[$durum]['kod'] ?? '') : '') ?></button><?php else : ?><?= e($durum !== '' ? ($durumlar[$durum]['kod'] ?? '') : '') ?><?php endif; ?></td><?php endfor; ?>
                <?php foreach ($durumlar as $anahtar => $bilgi) : ?><td class="attendance-total"><?= (int) ($sayaclar[$anahtar] ?? 0) ?></td><?php endforeach; ?><td class="attendance-total is-grand"><?= $doluGun ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($yonetebilir) : ?>
<dialog class="appointment-dialog" data-personel-dialog><form method="dialog" class="appointment-dialog-form" data-personel-form>
    <div class="dialog-head"><div><h2 data-personel-dialog-title>Personel Ekle</h2><p>Kimlik, görev ve standart çalışma saatleri</p></div><button type="button" data-personel-kapat>×</button></div>
    <input type="hidden" name="id"><div class="dialog-grid">
        <label><span>Ad *</span><input name="ad" maxlength="120" required></label><label><span>Soyad *</span><input name="soyad" maxlength="120" required></label>
        <label><span>T.C. Kimlik No</span><input name="tc_kimlik_no" inputmode="numeric" maxlength="11"></label><label><span>Pozisyon</span><input name="pozisyon" maxlength="160"></label>
        <label><span>Aylık Brüt Ücret</span><input type="number" name="aylik_brut_ucret" min="0" max="99999999.99" step="0.01" value="0"></label>
        <label><span>SGK Teşvik Türü</span><select name="sgk_tesvik_turu"><?php foreach ($sgkTesvikler as $tesvikKodu => $tesvik) : ?><option value="<?= e($tesvikKodu) ?>"><?= e((string) $tesvik['ad']) ?></option><?php endforeach; ?></select></label>
        <label><span>İşe Giriş Tarihi</span><input type="date" name="ise_giris_tarihi"></label><label><span>İşten Çıkış Tarihi</span><input type="date" name="isten_cikis_tarihi"></label>
        <label><span>Standart Giriş</span><input type="time" name="varsayilan_giris_saati" value="09:00"></label><label><span>Standart Çıkış</span><input type="time" name="varsayilan_cikis_saati" value="18:00"></label>
        <label><span>Sistem Kullanıcısı (isteğe bağlı)</span><select name="kullanici_id"><option value="0">Bağlama</option><?php foreach ($kullaniciSecenekleri as $secenek) : ?><option value="<?= (int) $secenek['id'] ?>"><?= e(trim((string) $secenek['ad'] . ' ' . (string) $secenek['soyad']) . ' / ' . (string) $secenek['rol_adi']) ?></option><?php endforeach; ?></select></label>
        <label><span>Durum</span><select name="aktif"><option value="1">Aktif</option><option value="0">Pasif</option></select></label>
        <label class="full-row"><span>Not</span><textarea name="notlar" rows="3" maxlength="2000"></textarea></label>
    </div><div class="record-actions compact-actions"><span data-personel-mesaj></span><button class="btn btn-ghost" type="button" data-personel-kapat>Vazgeç</button><button class="btn btn-primary" type="submit">Kaydet</button></div>
</form></dialog>

<dialog class="appointment-dialog" data-puantaj-dialog><form method="dialog" class="appointment-dialog-form" data-puantaj-form>
    <div class="dialog-head"><div><h2>Puantaj Kaydı</h2><p data-puantaj-kisi></p></div><button type="button" data-puantaj-kapat>×</button></div>
    <input type="hidden" name="personel_id"><input type="hidden" name="tarih"><div class="dialog-grid">
        <label><span>Durum</span><select name="durum"><?php foreach ($durumlar as $anahtar => $bilgi) : ?><option value="<?= e($anahtar) ?>"><?= e($bilgi['kod'] . ' — ' . $bilgi['ad']) ?></option><?php endforeach; ?></select></label>
        <label><span>Mola (dakika)</span><input type="number" min="0" max="1440" name="mola_dakika" value="0"></label>
        <label><span>Giriş Saati</span><input type="time" name="giris_saati"></label><label><span>Çıkış Saati</span><input type="time" name="cikis_saati"></label>
        <label class="full-row"><span>Açıklama</span><textarea name="aciklama" rows="3" maxlength="500"></textarea></label>
    </div><div class="record-actions compact-actions"><span data-puantaj-mesaj></span><button class="btn btn-ghost" type="button" data-puantaj-kapat>Vazgeç</button><button class="btn btn-primary" type="submit">Kaydet</button></div>
</form></dialog>

<dialog class="appointment-dialog attendance-bulk-dialog" data-puantaj-toplu-dialog><form method="dialog" class="appointment-dialog-form" data-puantaj-toplu-form>
    <div class="dialog-head"><div><h2>Toplu Puantaj Düzenle</h2><p><strong data-puantaj-toplu-sayi>0 hücre</strong> için aynı durum uygulanacak.</p></div><button type="button" data-puantaj-toplu-kapat>×</button></div>
    <div class="dialog-grid">
        <label class="full-row"><span>Uygulanacak Durum *</span><select name="durum" required><option value="">Durum seçin</option><?php foreach ($durumlar as $anahtar => $bilgi) : ?><option value="<?= e($anahtar) ?>"><?= e($bilgi['kod'] . ' — ' . $bilgi['ad']) ?></option><?php endforeach; ?></select></label>
    </div>
    <div class="attendance-bulk-dialog-note">Yalnız durum alanı toplu olarak değiştirilir. Giriş, çıkış ve açıklama bilgilerini hücreye çift tıklayarak ayrı ayrı düzenleyebilirsiniz.</div>
    <div class="record-actions compact-actions"><span data-puantaj-toplu-mesaj></span><button class="btn btn-ghost" type="button" data-puantaj-toplu-kapat>Vazgeç</button><button class="btn btn-primary" type="submit">Seçilenlere Uygula</button></div>
</form></dialog>
<script nonce="<?= e(\App\Core\SecurityHeaders::nonce()) ?>" type="application/json" data-personel-map><?= json_encode(array_column($personeller, null, 'id'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php endif; ?>
