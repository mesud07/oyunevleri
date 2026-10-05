<?php
$saatGoster = static function (?string $saat): string {
    return $saat ? substr($saat, 0, 5) : '-';
};
$durumEtiket = [
    'planlandi' => 'Planlandi',
    'geldi' => 'Geldi',
    'gelmedi' => 'Gelmedi',
    'iptal' => 'Iptal',
    'kurum_iptali' => 'Kurum Iptali',
];
$uzunTarihGoster = static function (?string $tarih): string {
    if (!$tarih) {
        return '-';
    }
    $gunler = ['Pazar', 'Pazartesi', 'Sali', 'Carsamba', 'Persembe', 'Cuma', 'Cumartesi'];
    $zaman = strtotime($tarih);
    return $zaman ? date('d.m.Y', $zaman) . ' ' . $gunler[(int) date('w', $zaman)] : $tarih;
};
$haftaAraligiGoster = static function (?string $baslangic, ?string $bitis): string {
    if (!$baslangic || !$bitis) {
        return '-';
    }
    return tarih_goster($baslangic) . ' - ' . tarih_goster($bitis);
};
$ayHesapla = static function (?string $dogumTarihi): ?int {
    if (!$dogumTarihi) {
        return null;
    }
    try {
        $dogum = new DateTimeImmutable($dogumTarihi);
        $bugun = new DateTimeImmutable('today');
        if ($dogum > $bugun) {
            return null;
        }
        $fark = $dogum->diff($bugun);
        return ($fark->y * 12) + $fark->m;
    } catch (Throwable $e) {
        return null;
    }
};
?>
<main class="parent-portal-shell">
    <section class="parent-hero">
        <div>
            <span class="parent-brand">Oyun Evleri</span>
            <h1>Veli Bilgi Ekrani</h1>
            <p>SMS ile doğrulanan telefon numaranıza bağlı öğrenci bilgilerini güvenle görüntüleyin.</p>
        </div>
    </section>

    <section class="panel-card parent-verify-card">
        <?php if (($adim ?? 'telefon') === 'kod') : ?>
            <form method="post" action="/veli-portal" class="parent-phone-form">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="islem" value="kod_dogrula">
                <label>
                    <span>SMS Doğrulama Kodu</span>
                    <input type="text" name="kod" placeholder="6 haneli kod" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus>
                </label>
                <button class="btn btn-primary" type="submit">Doğrula</button>
            </form>
            <form method="post" action="/veli-portal">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="islem" value="kod_gonder">
                <input type="hidden" name="telefon" value="<?= e($telefon) ?>">
                <button class="btn" type="submit">Yeni Kod Gönder</button>
            </form>
        <?php elseif (($adim ?? 'telefon') !== 'dogrulandi') : ?>
            <form method="post" action="/veli-portal" class="parent-phone-form">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="islem" value="kod_gonder">
                <label>
                    <span>Telefon Numarası</span>
                    <input type="tel" name="telefon" value="<?= e($telefon) ?>" placeholder="0(5__) ___ __ __" inputmode="tel" autocomplete="tel" required autofocus>
                </label>
                <button class="btn btn-primary" type="submit">SMS Kodu Gönder</button>
            </form>
        <?php endif; ?>
        <?php if (!empty($mesaj)) : ?>
            <div class="alert alert-success"><?= e($mesaj) ?></div>
        <?php endif; ?>
        <?php if (!empty($testKodu)) : ?>
            <div class="alert">Yalnızca geliştirme ortamı test kodu: <?= e($testKodu) ?></div>
        <?php endif; ?>
        <?php if (!empty($hata)) : ?>
            <div class="alert alert-error"><?= e($hata) ?></div>
        <?php endif; ?>
    </section>

    <?php if (!empty($sonuc['cocuklar'])) : ?>
        <section class="parent-result-list">
            <?php foreach ($sonuc['cocuklar'] as $cocuk) : ?>
                <article class="panel-card parent-child-card">
                    <?php $tabId = 'parent-child-' . (int) ($cocuk['id'] ?? 0); ?>
                    <div class="parent-child-head">
                        <div>
                            <h2><?= e($cocuk['ad_soyad']) ?></h2>
                            <?php $ay = $ayHesapla($cocuk['dogum_tarihi'] ?? null); ?>
                            <p>
                                <?= !empty($cocuk['dogum_tarihi']) ? e('Dogum tarihi: ' . tarih_goster($cocuk['dogum_tarihi'])) : 'Dogum tarihi kayitli degil' ?>
                                <?= $ay !== null ? e(' / ' . $ay . ' aylik') : '' ?>
                            </p>
                        </div>
                        <span class="status-pill"><?= e($cocuk['durum'] ?: 'aktif') ?></span>
                    </div>

                    <div class="parent-child-tabs">
                        <input type="radio" id="<?= e($tabId) ?>-appointments" name="<?= e($tabId) ?>-tab" checked>
                        <input type="radio" id="<?= e($tabId) ?>-activities" name="<?= e($tabId) ?>-tab">
                        <div class="parent-tab-menu">
                            <label for="<?= e($tabId) ?>-appointments">Randevular</label>
                            <label for="<?= e($tabId) ?>-activities">Tema Etkinlikleri</label>
                        </div>

                        <section class="parent-tab-panel parent-appointments-panel">
                            <div class="parent-section-title">
                                <h3>Randevular</h3>
                                <span>Planlanan ve gecmis randevular</span>
                            </div>
                            <div class="table-wrap parent-table-wrap">
                                <table>
                                    <thead><tr><th>Tarih</th><th>Saat</th><th>Hizmet</th><th>Durum</th></tr></thead>
                                    <tbody>
                                        <?php if (empty($cocuk['randevular'])) : ?>
                                            <tr><td colspan="4">Randevu bulunamadi.</td></tr>
                                        <?php endif; ?>
                                        <?php foreach ($cocuk['randevular'] as $randevu) : ?>
                                            <?php $durumSinif = preg_replace('/[^a-z0-9_]+/', '-', strtolower((string) $randevu['durum'])); ?>
                                            <tr>
                                                <td><?= e($uzunTarihGoster($randevu['tarih'])) ?></td>
                                                <td><?= e($saatGoster($randevu['baslangic_saati'])) ?></td>
                                                <td>
                                                    <?= e($randevu['paket_adi']) ?>
                                                    <?php if (!empty($randevu['telafi_hakki_id'])) : ?>
                                                        <small class="appointment-source">Telafi: <?= e(tarih_goster($randevu['telafi_kaynak_tarih'] ?? null)) ?> <?= e($saatGoster($randevu['telafi_kaynak_saat'] ?? null)) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="status-pill parent-status-<?= e($durumSinif) ?>"><?= e($durumEtiket[$randevu['durum']] ?? $randevu['durum']) ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section class="parent-tab-panel parent-activities-panel">
                            <div class="parent-section-title">
                                <h3>Tema Etkinlikleri</h3>
                                <span>Yapilan tema ve etkinlik icerikleri</span>
                            </div>
                            <div class="parent-activity-list">
                                <?php if (empty($cocuk['tema_etkinlikleri'])) : ?>
                                    <div class="parent-empty">Yapilan tema etkinligi bulunamadi.</div>
                                <?php endif; ?>
                                <?php foreach ($cocuk['tema_etkinlikleri'] as $etkinlik) : ?>
                                    <article class="parent-activity-item">
                                        <div>
                                            <time><?= e(tarih_goster($etkinlik['completed_at'] ?? null)) ?></time>
                                            <strong><?= e($etkinlik['theme_title'] ?? '-') ?></strong>
                                            <span><?= e($haftaAraligiGoster($etkinlik['week_start'] ?? null, $etkinlik['week_end'] ?? null)) ?></span>
                                        </div>
                                        <div>
                                            <h4><?= e($etkinlik['activity_title'] ?? '-') ?></h4>
                                            <?php if (!empty($etkinlik['activity_description'])) : ?>
                                                <p><?= nl2br(e($etkinlik['activity_description'])) ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($etkinlik['age_groups'])) : ?>
                                                <small><?= e($etkinlik['age_groups']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
