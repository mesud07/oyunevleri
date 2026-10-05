<?php if ($canViewFinance) : ?>
<section class="student-finance" id="finans" data-student-tab-panel="finans" hidden>
    <div class="finance-heading">
        <div>
            <span class="finance-eyebrow">Öğrenci finansal profili</span>
            <h2><?= e($adSoyad) ?></h2>
            <p>Paket borçları, tahsilatlar ve faturalar tek ekranda.</p>
        </div>
        <span class="status-pill <?= $toplamKalanBorc > 0 ? 'is-danger' : 'is-success' ?>">
            <?= $toplamKalanBorc > 0 ? 'Ödeme bekleniyor' : 'Borç bulunmuyor' ?>
        </span>
    </div>

    <div class="finance-overview-grid">
        <article class="panel-card finance-summary-card">
            <div class="finance-card-title"><h3>Borç Özeti</h3><span>Güncel</span></div>
            <div class="finance-metric"><span>Toplam paket tutarı<small>Öğrenciye tanımlanan paketlerin toplamı.</small></span><strong><?= e(para_goster($toplamPaketTutari)) ?></strong></div>
            <div class="finance-metric"><span>Alınan tahsilat<small>İptal edilmemiş tüm tahsilatlar.</small></span><strong class="is-positive"><?= e(para_goster($toplamTahsilat)) ?></strong></div>
            <div class="finance-metric"><span>Kalan borç<small>Henüz tahsil edilmemiş paket tutarı.</small></span><strong class="<?= $toplamKalanBorc > 0 ? 'is-negative' : 'is-positive' ?>"><?= e(para_goster($toplamKalanBorc)) ?></strong></div>
            <div class="finance-balance-note">
                <span>Finansal bakiye</span>
                <strong><?= e(para_goster(abs($finansBakiyesi))) ?> <?= $finansBakiyesi > 0 ? 'alacaklı' : ($finansBakiyesi < 0 ? 'borçlu' : 'dengede') ?></strong>
            </div>
        </article>

        <article class="panel-card finance-actions-card">
            <div class="finance-card-title"><h3>İşlemler</h3><span>Hızlı erişim</span></div>
            <p class="finance-help">Tutarlar paket ve tahsilat kayıtlarından anlık hesaplanır.</p>
            <div class="finance-action-grid">
                <button class="btn btn-ghost" type="button" data-finance-profile-back>Profile Git</button>
                <button class="btn btn-ghost" type="button" data-finance-refresh>Yeniden Hesapla</button>
                <?php if ($canManagePayments && $tahsilatPaketleri) : ?><button class="btn btn-primary" type="button" data-open-dialog="#tahsilat-dialog">Tahsilat Yap</button><?php endif; ?>
                <?php if ($canManagePackages) : ?><a class="btn btn-ghost" href="<?= e($randevuOlusturUrl) ?>">Paket Ekle</a><?php endif; ?>
                <?php if ($canSendSms) : ?>
                    <button class="btn btn-ghost" type="button" data-open-sms-compose data-student-id="<?= e($ogrenci['id'] ?? '') ?>" data-student-name="<?= e($adSoyad) ?>" data-parent-id="<?= e($birincilVeli['id'] ?? '') ?>" data-parent-name="<?= e(trim(($birincilVeli['ad'] ?? '') . ' ' . ($birincilVeli['soyad'] ?? ''))) ?>" data-phone="<?= e($birincilVeli['telefon'] ?? $ogrenci['acil_durum_telefon'] ?? '') ?>">Mesaj Gönder</button>
                <?php endif; ?>
                <?php if ($canManagePayments && $faturasizOdemeler) : ?><button class="btn btn-primary" type="button" data-profile-invoice-start>Fatura Taslağı Oluştur</button><?php endif; ?>
            </div>
        </article>
    </div>

    <article class="panel-card report-panel finance-table-card">
        <div class="appointment-toolbar"><div><h2>Alınan Tahsilatlar</h2><p>Öğrenciden alınan ödemeler ve bunlara bağlı fatura durumu.</p></div></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Tarih</th><th>Paket</th><th>Tutar</th><th>Ödeme Yöntemi</th><th>Kasa</th><th>Açıklama</th><th>Fatura</th><th>İşlem</th></tr></thead>
                <tbody>
                    <?php if (!$odemeler) : ?><tr><td colspan="9">Tahsilat kaydı bulunamadı.</td></tr><?php endif; ?>
                    <?php foreach ($odemeler as $index => $odeme) : ?>
                        <?php $faturaDurumu = $odeme['fatura_provider_durum'] ?: ($odeme['fatura_mysoft_durum'] ?: $odeme['fatura_yerel_durum']); ?>
                        <tr class="<?= (int) ($odeme['iptal'] ?? 0) === 1 ? 'is-muted-row' : '' ?>">
                            <td><?= e($index + 1) ?></td>
                            <td><?= e(tarih_goster($odeme['tarih'] ?? null)) ?></td>
                            <td><?= e($odeme['paket_adi'] ?? '-') ?></td>
                            <td><strong><?= e(para_goster($odeme['tutar'] ?? 0)) ?></strong></td>
                            <td><?= e($odemeYontemleri[$odeme['yontem'] ?? ''] ?? ($odeme['yontem'] ?? '-')) ?></td>
                            <td><?= e($odeme['kasa'] ?? '-') ?></td>
                            <td><?= e($odeme['aciklama'] ?: '-') ?></td>
                            <td>
                                <?php if (!empty($odeme['fatura_id'])) : ?>
                                    <a class="status-pill" href="/panel/faturalar/detay?id=<?= e($odeme['fatura_id']) ?>"><?= e(fatura_durum_goster($faturaDurumu)) ?></a>
                                <?php else : ?><span class="muted">Oluşturulmadı</span><?php endif; ?>
                            </td>
                            <td>
                                <?php if ($faturaEntegrasyonuHazir && (int) ($odeme['iptal'] ?? 0) === 0 && empty($odeme['fatura_id']) && $canManagePayments) : ?>
                                    <button class="mini-btn" type="button" data-profile-payment-invoice="<?= e($odeme['id']) ?>">Taslak Oluştur</button>
                                <?php elseif ((int) ($odeme['iptal'] ?? 0) === 1) : ?><span class="status-pill is-danger">Geri alındı</span><?php else : ?>-<?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="panel-card report-panel finance-table-card">
        <div class="appointment-toolbar"><div><h2>Verilen Hizmetler ve Paketler</h2><p>Paket bedeli, tahsil edilen tutar ve kalan borç karşılaştırması.</p></div></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Başlangıç</th><th>Tahmini Bitiş</th><th>Paket</th><th>Paket Tutarı</th><th>Tahsilat</th><th>Kalan</th><th>Durum</th></tr></thead>
                <tbody>
                    <?php if (!$odemeOzeti) : ?><tr><td colspan="8">Paket kaydı bulunamadı.</td></tr><?php endif; ?>
                    <?php foreach ($odemeOzeti as $index => $paketOzeti) : ?>
                        <tr>
                            <td><?= e($index + 1) ?></td><td><?= e(tarih_goster($paketOzeti['baslangic_tarihi'] ?? null)) ?></td><td><?= e(tarih_goster($paketOzeti['tahmini_son_ders_tarihi'] ?? null)) ?></td>
                            <td><?= e($paketOzeti['paket_adi'] ?? '-') ?></td><td><?= e(para_goster($paketOzeti['net_paket_tutari'] ?? 0)) ?></td><td><?= e(para_goster($paketOzeti['tahsilat'] ?? 0)) ?></td><td><strong><?= e(para_goster(max(0, (float) ($paketOzeti['kalan_borc'] ?? 0)))) ?></strong></td><td><span class="status-pill"><?= e($paketOzeti['paket_durumu'] ?? '-') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="panel-card report-panel finance-table-card">
        <div class="appointment-toolbar"><div><h2>Düzenlenen Faturalar</h2><p>Bu öğrenci adına oluşturulan e-Fatura ve e-Arşiv belgeleri.</p></div><a class="btn btn-ghost" href="/panel/faturalar">Tüm Faturalar</a></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Tarih</th><th>Fatura No</th><th>Belge Türü</th><th>Alıcı</th><th>Durum</th><th>Toplam</th><th>İşlem</th></tr></thead>
                <tbody>
                    <?php if (!$faturalar) : ?><tr><td colspan="8">Bu öğrenci için düzenlenmiş fatura bulunamadı.</td></tr><?php endif; ?>
                    <?php foreach ($faturalar as $index => $fatura) : ?>
                        <?php $durum = $fatura['provider_durum'] ?: ($fatura['mysoft_durum'] ?: $fatura['yerel_durum']); ?>
                        <tr><td><?= e($index + 1) ?></td><td><?= e(tarih_goster($fatura['fatura_tarihi'] ?? null)) ?></td><td><?= e($fatura['fatura_no'] ?: 'Numara bekleniyor') ?><small class="finance-cell-detail"><?= e($fatura['ettn'] ?? '') ?></small></td><td><?= ($fatura['belge_turu'] ?? '') === 'EFATURA' ? 'e-Fatura' : 'e-Arşiv' ?></td><td><?= e($fatura['alici_adi'] ?? '-') ?></td><td><span class="status-pill"><?= e(fatura_durum_goster($durum)) ?></span></td><td><strong><?= e(para_goster($fatura['genel_toplam'] ?? 0)) ?></strong></td><td><a class="mini-btn" href="/panel/faturalar/detay?id=<?= e($fatura['id']) ?>">Görüntüle</a></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<?php if ($canManagePayments && $faturasizOdemeler) : ?>
<dialog id="profil-fatura-kes-dialog" class="appointment-dialog payment-dialog">
    <form method="dialog" class="appointment-dialog-form" data-profile-invoice-form>
        <div class="dialog-head"><div><h2>Fatura Taslağı Oluştur</h2><p data-profile-invoice-info>Tahsilatı seçerek taslak bilgilerini hazırlayın.</p></div><button type="button" data-profile-invoice-close>x</button></div>
        <input type="hidden" name="odeme_id">
        <div class="dialog-grid">
            <label class="dialog-wide"><span>Faturalanacak Tahsilat</span><select data-profile-invoice-payment required><option value="">Tahsilat seçin</option><?php foreach ($faturasizOdemeler as $odeme) : ?><option value="<?= e($odeme['id']) ?>"><?= e(tarih_goster($odeme['tarih'] ?? null) . ' · ' . ($odeme['paket_adi'] ?? '-') . ' · ' . para_goster($odeme['tutar'] ?? 0)) ?></option><?php endforeach; ?></select></label>
            <label><span>Fatura Profili</span><select name="profil_turu" data-profile-invoice-type required><option value="bireysel">Bireysel</option><option value="kurumsal">Kurumsal</option></select></label>
            <label><span>VKN / TCKN</span><input name="vkn_tckn" maxlength="11" inputmode="numeric" required></label>
            <label data-profile-individual-field><span>Ad</span><input name="ad"></label><label data-profile-individual-field><span>Soyad</span><input name="soyad"></label>
            <label class="dialog-wide" data-profile-corporate-field hidden><span>Firma Ünvanı</span><input name="unvan"></label><label data-profile-corporate-field hidden><span>Vergi Dairesi</span><input name="vergi_dairesi"></label>
            <label><span>İl</span><input name="il" required></label><label><span>İlçe</span><input name="ilce" required></label><label class="dialog-wide"><span>Adres</span><textarea name="adres" rows="3" required></textarea></label>
            <label><span>E-posta</span><input type="email" name="eposta"></label><label><span>Telefon</span><input name="telefon"></label><input type="hidden" name="ulke" value="TÜRKİYE">
            <label><span>KDV Oranı (%)</span><input type="number" name="kdv_orani" min="0" max="100" step="0.01" required></label>
        </div>
        <div class="info-box compact-info"><strong>Önce taslak oluşturulur</strong><p>VKN/TCKN sorgusuna göre e-Fatura veya e-Arşiv taslağı hazırlanır. Geçersiz PDF kontrolünden sonra ayrıca onaylanır.</p></div>
        <div class="record-actions compact-actions"><span data-form-message></span><button class="btn btn-ghost" type="button" data-profile-invoice-close>Vazgeç</button><button class="btn btn-primary" type="submit">Taslak Oluştur</button></div>
    </form>
</dialog>
<?php endif; ?>
<?php endif; ?>
