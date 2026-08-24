<?php
$rapor = $rapor ?? [];
$ozet = $rapor['ozet'] ?? [];
$aylikTahsilat = $rapor['aylik_tahsilat'] ?? [];
$randevuDurumlari = $rapor['randevu_durumlari'] ?? [];
$paketPerformansi = $rapor['paket_performansi'] ?? [];
$kapasiteGelir = $rapor['kapasite_gelir'] ?? [];
$ortalamaDagilimi = $kapasiteGelir['ortalama_dagilimi'] ?? [];
$ortalamaDetay = $kapasiteGelir['ortalama_detay'] ?? [];
$maxOrtalamaDagilim = max(array_map(fn($row) => (int) ($row['ogrenci_sayisi'] ?? 0), $ortalamaDagilimi ?: [['ogrenci_sayisi' => 0]]));
$maxTahsilat = max(array_map(fn($row) => (float) ($row['tahsilat'] ?? 0), $aylikTahsilat ?: [['tahsilat' => 0]]));
$maxPaketCiro = max(array_map(fn($row) => (float) ($row['ciro'] ?? 0), $paketPerformansi ?: [['ciro' => 0]]));
$gunler = [1 => 'Pazartesi', 2 => 'Sali', 3 => 'Carsamba', 4 => 'Persembe', 5 => 'Cuma', 6 => 'Cumartesi', 7 => 'Pazar'];
$kapasiteHaftaBaslangici = (string) ($kapasiteGelir['hafta_baslangici'] ?? date('Y-m-d', strtotime('monday this week')));
$kapasiteHaftaBitisi = (string) ($kapasiteGelir['hafta_bitisi'] ?? date('Y-m-d', strtotime('sunday this week')));
$oncekiKapasiteHaftasi = date('Y-m-d', strtotime($kapasiteHaftaBaslangici . ' -7 days'));
$sonrakiKapasiteHaftasi = date('Y-m-d', strtotime($kapasiteHaftaBaslangici . ' +7 days'));
$raporTarihi = date('Y-m-d');
$yaklasanTahsilatBitisi = date('Y-m-d', strtotime($raporTarihi . ' +7 days'));
$yenilemeTarihleri = array_values(array_filter(array_column($rapor['kayit_yenileme_takvimi'] ?? [], 'tarih')));
sort($yenilemeTarihleri);
$yenilemeBaslangici = $yenilemeTarihleri[0] ?? null;
$yenilemeBitisi = $yenilemeTarihleri ? $yenilemeTarihleri[array_key_last($yenilemeTarihleri)] : null;
?>

<section class="page-head">
    <div>
        <h1>Raporlar</h1>
        <p>Finans, paket, randevu ve operasyon ozetleri.</p>
    </div>
    <button class="btn btn-ghost" type="button" onclick="window.print()">Yazdir</button>
</section>

<section class="report-grid report-summary report-summary-six">
    <article class="report-card accent-blue">
        <span>Bu Ay Tahsilat</span>
        <strong><?= e(para_goster($ozet['bu_ay_tahsilat'] ?? 0)) ?></strong>
    </article>
    <article class="report-card accent-red">
        <span>Bekleyen Alacak</span>
        <strong><?= e(para_goster($ozet['bekleyen_alacak'] ?? 0)) ?></strong>
    </article>
    <article class="report-card accent-dark">
        <span>Bu Ay Gelmeyen</span>
        <strong><?= e($ozet['gelmeyen_randevu'] ?? 0) ?></strong>
    </article>
    <article class="report-card accent-purple">
        <span>Yaklasan Tahsilatlar</span>
        <strong><?= e(para_goster($ozet['yaklasan_tahsilat_beklentisi'] ?? 0)) ?></strong>
        <small class="report-card-period"><?= e(tarih_goster($raporTarihi)) ?> - <?= e(tarih_goster($yaklasanTahsilatBitisi)) ?></small>
    </article>
    <article class="report-card accent-orange">
        <span>Gecikmis Tahsilatlar</span>
        <strong><?= e(para_goster($ozet['gecikmis_tahsilat_beklentisi'] ?? 0)) ?></strong>
    </article>
    <article class="report-card accent-teal">
        <span>Gelecek Yenileme Bakiyesi</span>
        <strong><?= e(para_goster($ozet['kayit_yenileme_bakiyesi'] ?? 0)) ?></strong>
        <small class="report-card-period">
            <?= $yenilemeBaslangici && $yenilemeBitisi
                ? e(tarih_goster($yenilemeBaslangici) . ' - ' . tarih_goster($yenilemeBitisi))
                : 'Tarih aralığında kayıt yok' ?>
        </small>
    </article>
</section>

<section class="panel-card report-panel capacity-report" data-capacity-report data-average-income="<?= e((string) ($kapasiteGelir['ortalama_ders_geliri'] ?? 0)) ?>" data-max-capacity="<?= e((string) ($kapasiteGelir['maksimum_ders_sayisi'] ?? 0)) ?>">
    <div class="report-section-head">
        <div>
            <h2>Grup Kapasite ve Gelir Raporu</h2>
            <p class="report-helper"><?= e(tarih_goster($kapasiteHaftaBaslangici)) ?> - <?= e(tarih_goster($kapasiteHaftaBitisi)) ?> haftasindaki randevular esas alinir. Grup geliri, dahil olan her ogrencinin paket tutari normal ders sayisina bolunerek hesaplanir.</p>
        </div>
        <form class="capacity-week-filter" method="get" action="/panel/raporlar">
            <a class="btn btn-ghost" href="/panel/raporlar?hafta=<?= e($oncekiKapasiteHaftasi) ?>">Onceki Hafta</a>
            <label><span>Hafta</span><input type="date" name="hafta" value="<?= e($kapasiteHaftaBaslangici) ?>"></label>
            <button class="btn btn-primary" type="submit">Uygula</button>
            <a class="btn btn-ghost" href="/panel/raporlar?hafta=<?= e($sonrakiKapasiteHaftasi) ?>">Sonraki Hafta</a>
        </form>
    </div>
    <div class="capacity-metrics">
        <article>
            <span>Maksimum Ders Sayisi</span>
            <strong><?= e($kapasiteGelir['maksimum_ders_sayisi'] ?? 0) ?></strong>
        </article>
        <article>
            <span>Planlanan Ders Sayisi</span>
            <strong><?= e($kapasiteGelir['mevcut_ders_sayisi'] ?? 0) ?></strong>
        </article>
        <article>
            <span>Doluluk Orani</span>
            <strong><?= e(number_format((float) ($kapasiteGelir['doluluk_orani'] ?? 0), 1, ',', '.')) ?>%</strong>
        </article>
        <article>
            <span>Ortalama Ders Geliri</span>
            <strong><?= e(para_goster($kapasiteGelir['ortalama_ders_geliri'] ?? 0)) ?></strong>
        </article>
        <article>
            <span>Haftalik Tahmini Gelir</span>
            <strong><?= e(para_goster($kapasiteGelir['mevcut_gelir'] ?? 0)) ?></strong>
        </article>
        <article>
            <span>Maksimum Gelir Potansiyeli</span>
            <strong><?= e(para_goster($kapasiteGelir['maksimum_gelir'] ?? 0)) ?></strong>
        </article>
    </div>
    <div class="income-breakdown">
        <div class="income-breakdown-head">
            <div>
                <h3>Ortalama Gelir Dagilimi</h3>
                <p class="report-helper">Ortalama, aktif son paketlerden tanisma/tek ders haric tutularak hesaplanir.</p>
            </div>
            <div class="income-formula">
                <span>Formul</span>
                <strong><?= e(para_goster($ortalamaDetay['dahil_toplam'] ?? 0)) ?> / <?= e($ortalamaDetay['dahil_ogrenci'] ?? 0) ?> ogrenci</strong>
                <em>= <?= e(para_goster($kapasiteGelir['ortalama_ogrenci_geliri'] ?? 0)) ?></em>
            </div>
        </div>
        <div class="income-breakdown-summary">
            <article>
                <span>Ortalamaya Dahil</span>
                <strong><?= e($ortalamaDetay['dahil_ogrenci'] ?? 0) ?> ogrenci</strong>
                <em><?= e(para_goster($ortalamaDetay['dahil_toplam'] ?? 0)) ?></em>
            </article>
            <article>
                <span>Haric Tutulan</span>
                <strong><?= e($ortalamaDetay['haric_ogrenci'] ?? 0) ?> ogrenci</strong>
                <em>Tanisma / tek ders: <?= e(para_goster($ortalamaDetay['haric_toplam'] ?? 0)) ?></em>
            </article>
        </div>
        <div class="income-bars">
            <?php if (!$ortalamaDagilimi) : ?>
                <div class="empty-table">Ortalama gelir dagilimi icin aktif paket bulunamadi.</div>
            <?php endif; ?>
            <?php foreach ($ortalamaDagilimi as $row) : ?>
                <?php
                    $adet = (int) ($row['ogrenci_sayisi'] ?? 0);
                    $width = $maxOrtalamaDagilim > 0 ? max(8, ($adet / $maxOrtalamaDagilim) * 100) : 0;
                    $dahil = (int) ($row['ortalamaya_dahil'] ?? 0) === 1;
                ?>
                <div class="income-bar-row <?= $dahil ? 'is-included' : 'is-excluded' ?>">
                    <div class="income-bar-label">
                        <strong><?= e($row['kategori'] ?? '-') ?></strong>
                        <span><?= e($adet) ?> ogrenci</span>
                    </div>
                    <div class="income-bar-track"><i style="width: <?= e((string) $width) ?>%"></i></div>
                    <div class="income-bar-values">
                        <span>Toplam: <?= e(para_goster($row['toplam_gelir'] ?? 0)) ?></span>
                        <span>Ortalama: <?= e(para_goster($row['ortalama_gelir'] ?? 0)) ?></span>
                        <em><?= $dahil ? 'Ortalamaya dahil' : 'Ortalamaya dahil degil' ?></em>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="capacity-split">
        <div class="capacity-calculator">
            <h3>Gelir Senaryosu</h3>
            <label>
                <span>Ders sayisi</span>
                <input type="number" min="0" step="1" value="<?= e((string) ($kapasiteGelir['mevcut_kayit'] ?? 0)) ?>" data-capacity-student-input>
            </label>
            <div class="capacity-result">
                <span>Tahmini gelir</span>
                <strong data-capacity-income-result><?= e(para_goster($kapasiteGelir['mevcut_gelir'] ?? 0)) ?></strong>
            </div>
            <small>Hesaplama secili haftadaki ders basi ortalama gelirle yapilir.</small>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Ders Sayisi</th><th>Doluluk</th><th>Tahmini Gelir</th></tr></thead>
                <tbody>
                    <?php foreach (($kapasiteGelir['senaryolar'] ?? []) as $row) : ?>
                        <tr>
                            <td><?= e($row['ogrenci_sayisi']) ?></td>
                            <td><?= e(number_format((float) $row['doluluk_orani'], 1, ',', '.')) ?>%</td>
                            <td><strong><?= e(para_goster($row['tahmini_gelir'])) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Gun</th><th>Saat</th><th>Grup</th><th>Yas</th><th>Ogrenci</th><th>Kontenjan</th><th>Doluluk</th><th>Tahmini Gelir</th><th>Maks. Gelir</th></tr></thead>
            <tbody>
                <?php if (empty($kapasiteGelir['programlar'])) : ?><tr><td colspan="9">Grup programi bulunamadi.</td></tr><?php endif; ?>
                <?php foreach (($kapasiteGelir['programlar'] ?? []) as $row) : ?>
                    <tr>
                        <td><?= e($gunler[(int) ($row['gun'] ?? 0)] ?? '-') ?></td>
                        <td><?= e(substr((string) ($row['baslangic_saati'] ?? ''), 0, 5)) ?> - <?= e(substr((string) ($row['bitis_saati'] ?? ''), 0, 5)) ?></td>
                        <td><?= e($row['program_adi'] ?? '-') ?></td>
                        <td><?= e($row['yas_araligi'] ?? '-') ?></td>
                        <td><?= e($row['ogrenci_sayisi'] ?? 0) ?></td>
                        <td><?= e($row['kontenjan'] ?? 0) ?></td>
                        <td><?= e(number_format((float) ($row['doluluk_orani'] ?? 0), 1, ',', '.')) ?>%</td>
                        <td title="<?= e((string) ($row['gelire_dahil_ogrenci'] ?? 0)) ?> ogrencinin aktif paketi dahil edildi"><?= e(para_goster($row['tahmini_gelir'] ?? 0)) ?></td>
                        <td title="Kontenjan x <?= e(para_goster($row['ortalama_ders_geliri'] ?? 0)) ?> ders basi ortalama"><strong><?= e(para_goster($row['maksimum_gelir'] ?? 0)) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="report-grid">
    <article class="panel-card report-panel">
        <h2>Aylik Tahsilat</h2>
        <div class="report-bars">
            <?php if (!$aylikTahsilat) : ?>
                <div class="empty-table">Tahsilat kaydi bulunamadi.</div>
            <?php endif; ?>
            <?php foreach ($aylikTahsilat as $row) : ?>
                <?php $width = $maxTahsilat > 0 ? max(6, ((float) $row['tahsilat'] / $maxTahsilat) * 100) : 0; ?>
                <div class="report-bar-row">
                    <span><?= e($row['ay']) ?></span>
                    <div><i style="width: <?= e((string) $width) ?>%"></i></div>
                    <strong><?= e(para_goster($row['tahsilat'])) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="panel-card report-panel">
        <h2>Randevu Durumlari</h2>
        <div class="report-status-list">
            <?php if (!$randevuDurumlari) : ?>
                <div class="empty-table">Bu ay randevu kaydi bulunamadi.</div>
            <?php endif; ?>
            <?php foreach ($randevuDurumlari as $row) : ?>
                <div class="report-status-item">
                    <span><?= e($row['durum']) ?></span>
                    <strong><?= e($row['adet']) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<section class="panel-card report-panel">
    <h2>Paket Performansi</h2>
    <div class="report-bars">
        <?php if (!$paketPerformansi) : ?>
            <div class="empty-table">Paket kaydi bulunamadi.</div>
        <?php endif; ?>
        <?php foreach ($paketPerformansi as $row) : ?>
            <?php $width = $maxPaketCiro > 0 ? max(6, ((float) $row['ciro'] / $maxPaketCiro) * 100) : 0; ?>
            <div class="report-bar-row wide">
                <span><?= e($row['paket_adi']) ?> (<?= e($row['paket_sayisi']) ?>)</span>
                <div><i style="width: <?= e((string) $width) ?>%"></i></div>
                <strong><?= e(para_goster($row['ciro'])) ?></strong>
                <em>Kalan hak: <?= e($row['kalan_hak']) ?></em>
            </div>
        <?php endforeach; ?>
    </div>
</section>
