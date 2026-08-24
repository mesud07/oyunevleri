<section class="record-titlebar">
    <h1>Ogrenciye Paket ve Randevu Tanimla</h1>
    <div class="breadcrumb">Paket Islemleri <span>›</span> Ogrenciye Paket ve Randevu Tanimla</div>
</section>

<?php $seciliOgrenciId = (int) ($secili_ogrenci_id ?? 0); ?>

<form class="record-form" data-ajax-form="paket_ekle" data-success-redirect="/panel/randevular" data-package-assignment-form>
    <div class="record-grid">
        <article class="record-card">
            <h2>Paket ve Ogrenci</h2>
            <div class="record-fields">
            <label>
                <span>Ogrenci</span>
                <select name="ogrenci_id" required>
                    <option value="">Seciniz</option>
                    <?php foreach (($ogrenciler ?? []) as $ogrenci) : ?>
                        <option value="<?= e($ogrenci['id']) ?>" <?= (int) $ogrenci['id'] === $seciliOgrenciId ? 'selected' : '' ?>><?= e($ogrenci['ad_soyad']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Paket</span>
                <select name="hizmet_id" data-service-select required>
                    <option value="">Paket seciniz</option>
                    <?php foreach (($hizmetler ?? []) as $hizmet) : ?>
                        <option
                            value="<?= e($hizmet['id']) ?>"
                            data-name="<?= e($hizmet['hizmet_adi']) ?>"
                            data-price="<?= e($hizmet['ucret']) ?>"
                            data-weekly="<?= e($hizmet['haftalik_katilim_sayisi']) ?>"
                            data-normal="<?= e($hizmet['toplam_normal_hak']) ?>"
                            data-makeup="<?= e($hizmet['toplam_telafi_hak']) ?>"
                        ><?= e($hizmet['hizmet_adi']) ?> - <?= e(para_goster($hizmet['ucret'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span>Baslangic Tarihi</span><input type="date" name="baslangic_tarihi" value="<?= e(date('Y-m-d')) ?>" required></label>
            <label data-single-appointment-time hidden>
                <span>Saat</span>
                <input type="time" name="tek_randevu_saati" value="15:00">
            </label>
            <label>
                <span>Haftalik Katilim</span>
                <select name="haftalik_katilim_sayisi_gosterim" disabled>
                    <option value="1">Haftada 1 Gun</option>
                    <option value="2">Haftada 2 Gun</option>
                </select>
                <input type="hidden" name="haftalik_katilim_sayisi" value="1">
            </label>
            <label class="check-row">
                <span>SMS</span>
                <div class="check-list">
                    <label><input type="checkbox" name="randevu_sms_gonder" value="1" checked> Randevu olusturma SMS'i gonder</label>
                </div>
            </label>
            <label class="check-row">
                <span>Tanisma Dersi</span>
                <div class="check-list">
                    <label><input type="checkbox" name="tanisma_dersi_ilk_ders_sayilsin" value="1"> Tanisma dersini paketin ilk dersi olarak say</label>
                    <small>Secilirse toplam hak ayni kalir, 1 ders kullanilmis kabul edilir ve olusturulacak randevu sayisi 1 azalir.</small>
                </div>
            </label>
            </div>
        </article>

        <article class="record-card">
            <h2>Hak ve Finans Bilgileri</h2>
            <div class="record-fields">
            <label><span>Normal Hak</span><input type="number" name="toplam_normal_hak" min="0" value="4" readonly></label>
            <label><span>Telafi Hakki</span><input type="number" name="toplam_telafi_hak" min="0" value="1" readonly></label>
            <label><span>Liste Fiyati</span><input type="number" step="0.01" name="liste_fiyati" readonly></label>
            <label>
                <span>Indirim Turu</span>
                <select name="indirim_turu">
                    <option value="">Yok</option>
                    <option value="Nakit indirimi">Nakit indirimi</option>
                    <option value="Kardes indirimi">Kardes indirimi</option>
                    <option value="Kampanya">Kampanya</option>
                    <option value="Yonetici indirimi">Yonetici indirimi</option>
                    <option value="Personel indirimi">Personel indirimi</option>
                    <option value="Diger">Diger</option>
                </select>
            </label>
            <label><span>Indirim Tutari</span><input type="number" step="0.01" name="indirim_tutari" value="0"></label>
            <label class="textarea-row"><span>Not</span><textarea name="yonetici_notu" rows="4"></textarea></label>
            </div>
        </article>

        <article class="record-card" data-weekly-schedule-card>
            <h2>Haftalik Gelis Plani</h2>
            <div class="info-box compact-info">
                <strong>Bilgilendirme</strong>
                <p>Paketi sectikten sonra ogrencinin duzenli gelecegi gun ve saatleri secin. Paketteki hak sayisi kadar randevu otomatik olusturulur. Tahmini son ders tarihi son olusturulan randevudan otomatik hesaplanir.</p>
            </div>
            <div class="schedule-planner">
                <?php
                $gunler = array(
                    '1' => 'Pazartesi',
                    '2' => 'Sali',
                    '3' => 'Carsamba',
                    '4' => 'Persembe',
                    '5' => 'Cuma',
                    '6' => 'Cumartesi',
                    '7' => 'Pazar',
                );
                foreach ($gunler as $gunDegeri => $gunAdi) :
                    ?>
                    <label class="schedule-row">
                        <span><input type="checkbox" name="program_gunleri[]" value="<?= e($gunDegeri) ?>"> <?= e($gunAdi) ?></span>
                        <input type="time" name="program_saat_<?= e($gunDegeri) ?>" value="15:00">
                    </label>
                <?php endforeach; ?>
            </div>
        </article>
        <div data-single-schedule-fields hidden></div>
    </div>

    <div class="record-actions">
        <span data-form-message></span>
        <a class="btn btn-ghost" href="/panel/randevular">Vazgec</a>
        <button class="btn btn-primary" type="submit">Randevulari Olustur</button>
    </div>
</form>
