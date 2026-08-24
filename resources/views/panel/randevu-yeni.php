<section class="record-titlebar">
    <h1>Randevu Asistani</h1>
    <div class="breadcrumb">Randevu Islemleri <span>›</span> Randevu Olustur</div>
</section>

<form class="record-form appointment-form" data-ajax-form="randevu_ekle" data-success-redirect="/panel/randevular">
    <div class="assistant-heading">
        <h2>Randevu Olustur</h2>
    </div>

    <div class="info-box">
        <strong>Bilgilendirme</strong>
        <p>Randevu asistani ile tek seferde ders, telafi, paket disi ders veya gorusme randevusu olusturabilirsiniz. Paket secildiginde ogrenci ve hak kaynagi randevuya baglanir.</p>
    </div>

    <div class="record-grid">
        <article class="record-card">
            <h2>Randevu Bilgileri</h2>
            <div class="record-fields">
                <label>
                    <span>Paket</span>
                    <select name="paket_id">
                        <option value="">Paketsiz / paket disi randevu</option>
                        <?php foreach (($paketler ?? []) as $paket) : ?>
                            <option value="<?= e($paket['id']) ?>"><?= e($paket['etiket']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Ogrenci</span>
                    <select name="ogrenci_id">
                        <option value="">Paket secildiyse bos kalabilir</option>
                        <?php foreach (($ogrenciler ?? []) as $ogrenci) : ?>
                            <option value="<?= e($ogrenci['id']) ?>"><?= e($ogrenci['ad_soyad']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="textarea-row">
                    <span>Toplu Ogrenci</span>
                    <select name="ogrenci_ids[]" multiple size="9">
                        <?php foreach (($ogrenciler ?? []) as $ogrenci) : ?>
                            <option value="<?= e($ogrenci['id']) ?>"><?= e($ogrenci['ad_soyad']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>Paketsiz randevularda birden fazla ogrenci secerek ayni tarih ve saatte toplu randevu olusturabilirsiniz. Paket secerseniz paket tek ogrenciye bagli oldugu icin tek kayit olusur.</small>
                </label>
                <label>
                    <span>Grup</span>
                    <select name="grup_id">
                        <option value="">Cocuk Etkinlik ve Oyun Evi</option>
                        <?php foreach (($gruplar ?? []) as $grup) : ?>
                            <option value="<?= e($grup['id']) ?>"><?= e($grup['ad']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span>Randevu Tarihi</span><input type="date" name="tarih" value="<?= e(date('Y-m-d')) ?>" required></label>
                <label><span>Saat</span><input type="time" name="baslangic_saati" required></label>
                <label>
                    <span>Sure</span>
                    <select name="sure_dakika">
                        <option value="45">45 Dakika</option>
                        <option value="60">60 Dakika</option>
                        <option value="90">90 Dakika</option>
                    </select>
                </label>
                <label>
                    <span>Randevu Tanimi</span>
                    <select name="tur" required>
                        <option value="Normal ders">Normal ders</option>
                        <option value="Telafi dersi">Telafi dersi</option>
                        <option value="Paket disi ders">Paket disi ders</option>
                        <option value="Tanisma dersi">Tanisma dersi</option>
                        <option value="Veli gorusmesi">Veli gorusmesi</option>
                        <option value="Workshop">Workshop</option>
                    </select>
                </label>
                <label>
                    <span>Durum</span>
                    <select name="durum" required>
                        <option value="planlandi">Planlandi</option>
                        <option value="geldi">Geldi</option>
                        <option value="gelmedi">Gelmedi</option>
                        <option value="mazeretli_gelmedi">Mazeretli Gelmedi</option>
                        <option value="gec_iptal">Gec Iptal</option>
                        <option value="kurum_iptali">Kurum Iptali</option>
                        <option value="ertelendi">Ertelendi</option>
                        <option value="tamamlandi">Tamamlandi</option>
                    </select>
                </label>
                <label>
                    <span>Hak Kaynagi</span>
                    <select name="hak_kaynagi" required>
                        <option value="Hak dusulmeyecek">Hak dusulmeyecek</option>
                        <option value="Aktif paket">Aktif paket</option>
                        <option value="Paket telafi hakki">Paket telafi hakki</option>
                        <option value="Paket disi hak">Paket disi hak</option>
                        <option value="Ucretsiz ek hak">Ucretsiz ek hak</option>
                        <option value="Ucretli tek ders">Ucretli tek ders</option>
                    </select>
                </label>
            </div>
        </article>

        <article class="record-card">
            <h2>Bildirim ve Not</h2>
            <div class="record-fields">
                <label>
                    <span>SMS Sablonu</span>
                    <select name="sms_sablonu">
                        <option value="">SMS gonderme</option>
                        <option value="Randevu Hatirlatma Sablonu">Randevu Hatirlatma Sablonu</option>
                    </select>
                </label>
                <label>
                    <span>SMS Zamani</span>
                    <select name="sms_zamani">
                        <option value="">Secilmedi</option>
                        <option value="1_gun_once_14">Randevudan 1 gun once (Saat 14:00)</option>
                        <option value="2_saat_once">Randevudan 2 saat once</option>
                    </select>
                </label>
                <label class="check-row">
                    <span>Olusturma SMS'i</span>
                    <div class="check-list">
                        <label><input type="checkbox" name="randevu_sms_gonder" value="1" checked> Randevu olusturma SMS'i gonder</label>
                    </div>
                </label>
                <label class="textarea-row">
                    <span>Randevu Notu</span>
                    <textarea name="aciklama" rows="6" placeholder="Varsa randevu notunuzu yazabilirsiniz."></textarea>
                    <small>Bu not kurum ici gorunum icindir.</small>
                </label>
                <label class="check-row">
                    <span>Haftanin Gunleri</span>
                    <div class="check-list">
                        <label><input type="checkbox" name="gun_pazartesi" value="1"> Pazartesi</label>
                        <label><input type="checkbox" name="gun_sali" value="1"> Sali</label>
                        <label><input type="checkbox" name="gun_carsamba" value="1"> Carsamba</label>
                        <label><input type="checkbox" name="gun_persembe" value="1"> Persembe</label>
                        <label><input type="checkbox" name="gun_cuma" value="1"> Cuma</label>
                        <label><input type="checkbox" name="gun_cumartesi" value="1"> Cumartesi</label>
                    </div>
                </label>
            </div>
        </article>
    </div>

    <div class="record-actions">
        <span data-form-message></span>
        <a class="btn btn-ghost" href="/panel/randevular">Vazgec</a>
        <button class="btn btn-primary" type="submit">Randevu Olustur</button>
    </div>
</form>
