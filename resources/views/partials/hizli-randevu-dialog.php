<?php
$hizliHizmetler = \App\Models\Hizmet::aktifListe();
$hizliTanisma = \App\Models\Hizmet::tanismaDersi();
$hizliTanismaId = (int) ($hizliTanisma['id'] ?? 0);
?>
<dialog class="appointment-dialog" id="hizli-randevu-dialog">
    <form class="appointment-dialog-form" data-ajax-form="hizli_randevu_ekle" data-success-redirect="/panel/randevular" data-quick-appointment-form>
        <div class="dialog-head">
            <h2>Hizli Randevu Olustur</h2>
            <button type="button" data-close-dialog>x</button>
        </div>

        <div class="dialog-grid">
            <label>
                <span>Ogrenci Ad Soyad</span>
                <input name="ogrenci_ad_soyad" placeholder="Ogrenci ad soyad" required>
            </label>
            <label>
                <span>Dogum Tarihi</span>
                <input type="date" name="dogum_tarihi" required>
            </label>
            <label>
                <span>Veli Ad Soyad</span>
                <input name="veli_ad_soyad" placeholder="Veli ad soyad" required>
            </label>
            <label>
                <span>Veli Telefon</span>
                <input name="veli_telefon" data-phone-mask maxlength="16" placeholder="0(5__) ___ __ __" required>
            </label>
            <label>
                <span>Paket</span>
                <select name="hizmet_id" required>
                    <?php foreach ($hizliHizmetler as $hizmet) : ?>
                        <option value="<?= e($hizmet['id']) ?>" <?= (int) $hizmet['id'] === $hizliTanismaId ? 'selected' : '' ?>>
                            <?= e($hizmet['hizmet_adi']) ?> - <?= e(para_goster($hizmet['ucret'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Baslangic Tarihi</span>
                <input type="date" name="randevu_tarihi" value="<?= e(date('Y-m-d')) ?>" data-quick-appointment-date required>
            </label>
            <label>
                <span>Gun</span>
                <select name="randevu_gunu" data-quick-appointment-day required>
                    <option value="1">Pazartesi</option>
                    <option value="2">Sali</option>
                    <option value="3">Carsamba</option>
                    <option value="4">Persembe</option>
                    <option value="5">Cuma</option>
                    <option value="6">Cumartesi</option>
                    <option value="7">Pazar</option>
                </select>
            </label>
            <label>
                <span>Saat</span>
                <input type="time" name="randevu_saati" value="15:00" required>
            </label>
            <label class="checkbox-row">
                <input type="checkbox" name="randevu_sms_gonder" value="1" checked>
                <span>Randevu olusturma SMS'i gonder</span>
            </label>
        </div>

        <div class="record-actions compact-actions">
            <span data-form-message></span>
            <button class="btn btn-ghost" type="button" data-close-dialog>Vazgec</button>
            <button class="btn btn-primary" type="submit">Randevu Olustur</button>
        </div>
    </form>
</dialog>
