<?php
$kayitlar = $kayitlar ?? [];
$ozet = $ozet ?? [];
$arama = $arama ?? '';
$sekme = $sekme ?? 'aktif';
$testDurumu = $testDurumu ?? 'tumu';
$birAyiGecenTestBekleyenListesi = $testDurumu === 'bir_ayi_gecen_uygulanmadi';
$sekmeSayilari = $sekmeSayilari ?? ['aktif' => 0, 'pasif' => 0];
$gelisimTestiTablosuHazir = (bool) ($gelisimTestiTablosuHazir ?? false);
$duzenleyebilir = yetki_var('ogrenci_ekle');
$tarihSaatYaz = static function ($deger): string {
    if (!$deger) {
        return '-';
    }
    $zaman = strtotime((string) $deger);
    return $zaman ? date('d.m.Y H:i', $zaman) : (string) $deger;
};
?>

<section class="page-head">
    <div>
        <h1>Devamlılık Raporu</h1>
        <p>Randevusu bulunan öğrencilerin ders katılımı, gelişim testi uygunluğu ve öğrenci notları.</p>
    </div>
    <form class="head-actions" method="get" action="/panel/ogrenciler/devamlilik-raporu">
        <input type="hidden" name="sekme" value="<?= e($sekme) ?>">
        <label>
            Öğrenci ara
            <input type="search" name="arama" value="<?= e($arama) ?>" placeholder="Ad veya soyad">
        </label>
        <label>
            Gelişim testi
            <select name="test_durumu">
                <option value="tumu" <?= $testDurumu === 'tumu' ? 'selected' : '' ?>>Tümü</option>
                <option value="uygulandi" <?= $testDurumu === 'uygulandi' ? 'selected' : '' ?>>Uygulandı</option>
                <option value="uygulanmadi" <?= $testDurumu === 'uygulanmadi' ? 'selected' : '' ?>>Uygulanmadı</option>
                <option value="bir_ayi_gecen_uygulanmadi" <?= $birAyiGecenTestBekleyenListesi ? 'selected' : '' ?>>1 ayı geçen, uygulanmamış</option>
            </select>
        </label>
        <button class="btn btn-primary" type="submit">Uygula</button>
        <?php if ($birAyiGecenTestBekleyenListesi) : ?><button class="btn btn-secondary" type="button" data-attendance-print>Yazdır</button><?php endif; ?>
        <?php if ($arama !== '' || $testDurumu !== 'tumu') : ?><a class="btn btn-ghost" href="/panel/ogrenciler/devamlilik-raporu?sekme=<?= e($sekme) ?>">Temizle</a><?php endif; ?>
    </form>
</section>

<?php if (!$gelisimTestiTablosuHazir) : ?>
    <div class="info-box compact-info">
        <strong>Migration gerekli</strong>
        <p>Gelişim testi bilgisi, <code>20260924_ogrenci_devamlilik_ve_gelisim_testi.sql</code> migration'ı çalıştırıldıktan sonra kaydedilebilir.</p>
    </div>
<?php endif; ?>

<nav class="attendance-tabs" aria-label="Öğrenci durumu">
    <a class="<?= $sekme === 'aktif' ? 'is-active' : '' ?>" href="/panel/ogrenciler/devamlilik-raporu?<?= e(http_build_query(['sekme' => 'aktif', 'arama' => $arama, 'test_durumu' => $testDurumu])) ?>">
        Güncel Öğrenciler <span><?= e((string) ($sekmeSayilari['aktif'] ?? 0)) ?></span>
    </a>
    <a class="<?= $sekme === 'pasif' ? 'is-active' : '' ?>" href="/panel/ogrenciler/devamlilik-raporu?<?= e(http_build_query(['sekme' => 'pasif', 'arama' => $arama, 'test_durumu' => $testDurumu])) ?>">
        Pasif Öğrenciler <span><?= e((string) ($sekmeSayilari['pasif'] ?? 0)) ?></span>
    </a>
</nav>

<section class="report-grid report-summary">
    <article class="report-card accent-blue"><span>Randevulu öğrenci</span><strong><?= e((string) ($ozet['ogrenci_sayisi'] ?? 0)) ?></strong></article>
    <article class="report-card accent-green"><span>Toplam katılım</span><strong><?= e((string) ($ozet['katilim_sayisi'] ?? 0)) ?></strong></article>
    <article class="report-card accent-purple"><span>Teste uygun</span><strong><?= e((string) ($ozet['uygun_ogrenci_sayisi'] ?? 0)) ?></strong></article>
    <article class="report-card accent-teal"><span>Test uygulandı</span><strong data-development-test-total><?= e((string) ($ozet['test_uygulanan_sayisi'] ?? 0)) ?></strong></article>
</section>

<section class="panel-card report-panel attendance-report <?= $birAyiGecenTestBekleyenListesi ? 'attendance-print-report' : '' ?>">
    <div class="definition-head">
        <div>
            <h2><?= $birAyiGecenTestBekleyenListesi ? '1 ayı geçen, gelişim testi uygulanmamış öğrenciler' : ($sekme === 'pasif' ? 'Pasif öğrenciler' : 'Öğrenci devam durumu') ?></h2>
            <p><?= $birAyiGecenTestBekleyenListesi ? 'İlk paket başlangıcından itibaren bir takvim ayını tamamlayan öğrenciler, en uzun süredir kayıtlı olandan başlayarak sıralanır.' : ($sekme === 'pasif' ? 'Son paket bitiş tarihinin üzerinden 7 günden fazla geçen öğrenciler.' : 'İlk paket başlangıcından itibaren <strong>bir takvim ayını tamamlayan</strong> öğrenciler gelişim testi için otomatik olarak uygun gösterilir.') ?></p>
        </div>
        <span class="payment-total-pill is-total"><?= e((string) count($kayitlar)) ?> öğrenci</span>
    </div>

    <div class="table-wrap attendance-screen-table">
        <table>
            <thead>
                <tr>
                    <th>Öğrenci</th>
                    <th>Kayıt süresi</th>
                    <th>Katıldığı ders</th>
                    <th>Randevu özeti</th>
                    <th>Uygunluk</th>
                    <th>Gelişim testi</th>
                    <th>Detay / Notlar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$kayitlar) : ?>
                    <tr><td colspan="7">Randevusu bulunan öğrenci kaydı bulunamadı.</td></tr>
                <?php endif; ?>
                <?php foreach ($kayitlar as $kayit) : ?>
                    <?php
                    $uygun = !empty($kayit['gelisim_testine_uygun']);
                    $uygulandi = !empty($kayit['gelisim_testi_uygulandi']);
                    $notlar = $kayit['notlar'] ?? [];
                    ?>
                    <tr data-attendance-row>
                        <td>
                            <a class="table-link" href="/panel/ogrenciler/profil?id=<?= e((string) $kayit['ogrenci_id']) ?>">
                                <?= e(($kayit['ad'] ?? '') . ' ' . ($kayit['soyad'] ?? '')) ?>
                            </a>
                            <small class="attendance-subtext"><?= !empty($kayit['pasif_ogrenci']) ? 'Son paket: ' . e(tarih_goster($kayit['son_paket_tarihi'] ?? null)) : (($kayit['ogrenci_durumu'] ?? '') === 'aktif' ? 'Aktif öğrenci' : 'Öğrenci kaydı pasif') ?></small>
                        </td>
                        <td>
                            <?php if ($kayit['kayitli_ay_sayisi'] !== null) : ?>
                                <strong class="attendance-duration"><?= e((string) $kayit['kayitli_ay_sayisi']) ?> ay</strong>
                                <small class="attendance-subtext">İlk paket: <?= e(tarih_goster($kayit['ilk_paket_baslangic_tarihi'] ?? null)) ?></small>
                            <?php else : ?>
                                <span>-</span>
                                <small class="attendance-subtext">Paket kaydı yok</small>
                            <?php endif; ?>
                        </td>
                        <td><strong class="attendance-count"><?= e((string) $kayit['katildigi_ders']) ?></strong></td>
                        <td>
                            <span><?= e((string) $kayit['toplam_randevu']) ?> toplam</span>
                            <small class="attendance-subtext"><?= e((string) $kayit['gelecek_randevu']) ?> gelecek · <?= e((string) $kayit['katilmadigi_ders']) ?> katılmadı</small>
                        </td>
                        <td>
                            <?php if ($uygun) : ?>
                                <span class="status-pill attendance-eligible">Gelişim Testi uygulamaya Uygun</span>
                            <?php elseif (($kayit['gelisim_testine_kalan_gun'] ?? null) !== null) : ?>
                                <span class="status-pill attendance-progress"><?= e((string) $kayit['gelisim_testine_kalan_gun']) ?> gün kaldı</span>
                            <?php else : ?>
                                <span class="status-pill attendance-progress">Paket başlangıcı yok</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($duzenleyebilir && $gelisimTestiTablosuHazir) : ?>
                                <form class="development-test-form" data-development-test-form data-current-applied="<?= $uygulandi ? '1' : '0' ?>" data-current-date="<?= e((string) ($kayit['gelisim_testi_tarihi'] ?? '')) ?>">
                                    <input type="hidden" name="ogrenci_id" value="<?= e((string) $kayit['ogrenci_id']) ?>">
                                    <label class="development-test-toggle">
                                        <input type="checkbox" name="uygulandi" value="1" <?= $uygulandi ? 'checked' : '' ?>>
                                        <span>Uygulandı</span>
                                    </label>
                                    <label class="development-test-date">
                                        <span><?= $uygulandi ? 'Uygulama tarihi' : 'Planlanan tarih' ?></span>
                                        <input type="date" name="uygulama_tarihi" value="<?= e((string) ($kayit['gelisim_testi_tarihi'] ?? '')) ?>" aria-label="Gelişim testi tarihi">
                                    </label>
                                    <small data-development-test-message><?= $uygulandi ? 'Uygulandı · Otomatik kaydedilir' : (!empty($kayit['gelisim_testi_tarihi']) ? 'Test tarihi planlandı · Otomatik kaydedilir' : 'Değişiklikler otomatik kaydedilir') ?></small>
                                </form>
                            <?php else : ?>
                                <span class="status-pill <?= $uygulandi ? 'attendance-tested' : 'attendance-progress' ?>"><?= $uygulandi ? 'Uygulandı' : 'Uygulanmadı' ?></span>
                                <?php if (!empty($kayit['gelisim_testi_tarihi'])) : ?><small class="attendance-subtext"><?= $uygulandi ? 'Uygulama' : 'Planlanan' ?>: <?= e(tarih_goster($kayit['gelisim_testi_tarihi'])) ?></small><?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <details class="attendance-details">
                                <summary><?= count($notlar) ?> öğrenci notu</summary>
                                <div class="attendance-detail-content">
                                    <dl>
                                        <div><dt>Son katılım</dt><dd><?= e(tarih_goster($kayit['son_katilim_tarihi'] ?? null)) ?></dd></div>
                                        <div><dt>Son randevu</dt><dd><?= e(tarih_goster($kayit['son_randevu_tarihi'] ?? null)) ?></dd></div>
                                        <div><dt>Test kaydı</dt><dd><?= e($tarihSaatYaz($kayit['gelisim_testi_guncellenme_tarihi'] ?? null)) ?></dd></div>
                                    </dl>
                                    <?php if (!$notlar) : ?>
                                        <p class="muted">Bu öğrenci için eklenmiş not bulunmuyor.</p>
                                    <?php endif; ?>
                                    <?php foreach ($notlar as $not) : ?>
                                        <article class="attendance-note">
                                            <header><strong><?= e($not['kategori'] ?? 'Genel') ?></strong><span><?= e(tarih_goster($not['tarih'] ?? null)) ?></span></header>
                                            <p><?= nl2br(e($not['not_metni'] ?? '')) ?></p>
                                            <footer><?= e($not['kaydeden'] ?? '-') ?> · <?= e($tarihSaatYaz($not['olusturulma_tarihi'] ?? null)) ?></footer>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($birAyiGecenTestBekleyenListesi) : ?>
        <div class="attendance-print-sheet print-only">
            <header>
                <div>
                    <h1>Gelişim Testi Bekleyen Öğrenciler</h1>
                    <p>İlk paket başlangıcının üzerinden 1 aydan fazla geçen ve gelişim testi uygulanmamış öğrenciler</p>
                </div>
                <div><strong><?= e((string) count($kayitlar)) ?> öğrenci</strong><span><?= e(date('d.m.Y')) ?></span></div>
            </header>
            <table>
                <thead><tr><th>No</th><th>Öğrenci</th><th>İlk Paket</th><th>Kayıt Süresi</th><th>1 Ay Eşiği</th><th>Katılım</th><th>Uygunluk</th></tr></thead>
                <tbody>
                    <?php if (!$kayitlar) : ?><tr><td colspan="7">Kriterlere uygun öğrenci bulunamadı.</td></tr><?php endif; ?>
                    <?php foreach ($kayitlar as $sira => $kayit) : ?>
                        <tr>
                            <td><?= e((string) ($sira + 1)) ?></td>
                            <td><strong><?= e(($kayit['ad'] ?? '') . ' ' . ($kayit['soyad'] ?? '')) ?></strong></td>
                            <td><?= e(tarih_goster($kayit['ilk_paket_baslangic_tarihi'] ?? null)) ?></td>
                            <td><?= e((string) ($kayit['kayitli_gun_sayisi'] ?? 0)) ?> gün / <?= e((string) ($kayit['kayitli_ay_sayisi'] ?? 0)) ?> ay</td>
                            <td><?= e((string) ($kayit['bir_ay_sinirini_gecen_gun'] ?? 0)) ?> gün geçti</td>
                            <td><?= e((string) ($kayit['katildigi_ders'] ?? 0)) ?> ders</td>
                            <td><?= !empty($kayit['gelisim_testine_uygun']) ? 'Teste uygun' : (($kayit['gelisim_testine_kalan_gun'] ?? null) !== null ? e((string) $kayit['gelisim_testine_kalan_gun']) . ' gün kaldı' : 'Paket başlangıcı yok') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <footer>Oyun Evleri · Gelişim Testi Takip Listesi</footer>
        </div>
    <?php endif; ?>
</section>
