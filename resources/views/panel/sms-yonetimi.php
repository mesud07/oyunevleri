<section class="record-titlebar sms-titlebar">
    <div class="sms-titlebar-main">
        <div class="sms-titlebar-badge">SMS</div>
        <div>
            <h1>SMS Yonetimi</h1>
            <p>NetGSM baglantisi, gonderim ayarlari ve SMS operasyonlarini tek ekrandan yonetin.</p>
        </div>
    </div>
    <div class="breadcrumb">Ayarlar <span>›</span> SMS Yonetimi</div>
</section>

<?php
$smsReminderSettings = $smsReminderSettings ?? [
    'appointment_reminder_enabled' => $smsConfig['appointment_reminder_enabled'] ?? true,
    'appointment_reminder_days_before' => $smsConfig['appointment_reminder_days_before'] ?? 1,
    'appointment_reminder_time' => $smsConfig['appointment_reminder_time'] ?? '14:00',
];
$smsConnectionStatus = $smsConnectionStatus ?? [];
?>

<section class="definition-card sms-page" data-sms-page>
    <?php if (yetki_var('sms_ayar_yonet')): ?>
        <article class="form-card sms-section-card sms-connection-settings">
            <div class="sms-section-head">
                <div>
                    <h2>NetGSM Baglanti Ayarlari</h2>
                    <p>Bu alanlar kurum bazlidir. Her oyun evi kendi NetGSM kullanici bilgilerini ve gonderici basligini buradan yonetebilir.</p>
                </div>
                <div class="sms-head-actions">
                    <div class="sms-connection-pill <?= ($smsConnectionStatus['son_test_durumu'] ?? '') === 'basarili' ? 'is-success' : 'is-muted' ?>">
                        <strong>Baglanti Durumu</strong>
                        <span><?= ($smsConnectionStatus['son_test_durumu'] ?? '') === 'basarili' ? 'Baglanti basarili' : 'Henuz dogrulanmadi' ?></span>
                    </div>
                    <button class="btn btn-ghost" type="button" data-netgsm-headers>NetGSM Basliklarini Kontrol Et</button>
                </div>
            </div>
            <div class="sms-inline-notices">
                <?php if (!$smsConfig['enabled'] || $smsConfig['test_mode']): ?>
                    <div class="info-box">
                        <strong>Test Modu</strong>
                        <p>SMS gonderimleri NetGSM API'ye iletilmeden kuyrukta islenmis sayilir. Canli kullanim icin servis aktifligini ve test modunu kontrol edin.</p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($smsConfig['force_to'])): ?>
                    <div class="info-box">
                        <strong>Yonlendirme Aktif</strong>
                        <p>Tum SMS'ler gecici olarak <?= e($smsConfig['force_to']) ?> numarasina yonlendirilir.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="info-box sms-connection-status" data-sms-connection-status>
                <div class="sms-status-icon">✓</div>
                <div class="sms-status-content">
                    <strong>Son Baglanti Testi</strong>
                    <p>
                        Tarih:
                        <span data-sms-last-at><?= e((string) ($smsConnectionStatus['son_test_tarihi'] ?? '-')) ?></span>
                        <span class="sms-dot">•</span>
                        Durum:
                        <span data-sms-last-status><?= e((string) ($smsConnectionStatus['son_test_durumu'] ?? '-')) ?></span>
                    </p>
                    <p data-sms-last-message><?= e((string) ($smsConnectionStatus['son_test_mesaji'] ?? 'Bu kurum icin henuz baglanti testi yapilmadi.')) ?></p>
                </div>
            </div>
            <form class="sms-settings-form" data-sms-connection-form>
                <div class="sms-switch-grid">
                    <label class="sms-switch-card">
                        <div>
                            <strong>SMS servisi aktif</strong>
                            <small>Kuruma ait gonderim servisinin calisma durumu.</small>
                        </div>
                        <input type="checkbox" name="sms_enabled" value="1" <?= !empty($smsConfig['enabled']) ? 'checked' : '' ?>>
                    </label>
                    <label class="sms-switch-card">
                        <div>
                            <strong>Test modu aktif</strong>
                            <small>Aciksa gercek gonderim yapilmaz.</small>
                        </div>
                        <input type="checkbox" name="sms_test_mode" value="1" <?= !empty($smsConfig['test_mode']) ? 'checked' : '' ?>>
                    </label>
                </div>

                <div class="sms-settings-grid">
                    <label class="sms-field">
                        <span>NetGSM Kullanici Kodu</span>
                        <input name="sms_netgsm_usercode" value="<?= e((string) ($smsConfig['netgsm']['usercode'] ?? '')) ?>" autocomplete="off">
                    </label>
                    <label class="sms-field">
                        <span class="sms-label-with-help">
                            <span>NetGSM Sifre</span>
                            <details class="sms-help-popover">
                                <summary>!</summary>
                                <div class="sms-help-popover-card">
                                    <strong>NetGSM sifresi nedir?</strong>
                                    <p>Bu alan, kurumunuzun NetGSM hesabina API ile baglanmak icin kullanilan sifredir. Kullanici kodu ile birlikte dogrulama yapar.</p>
                                    <p>Bu sifreyi NetGSM panelinizden veya hesabinizi acan NetGSM yetkilisinden temin etmeniz gerekir. SMS gonderiminde kullanilan web servis / API sifresi olmalidir.</p>
                                </div>
                            </details>
                        </span>
                        <input type="password" name="sms_netgsm_password" value="<?= e((string) ($smsConfig['netgsm']['password'] ?? '')) ?>" autocomplete="new-password">
                    </label>
                    <label class="sms-field">
                        <span>Onayli Gonderici Basligi</span>
                        <input name="sms_netgsm_header" value="<?= e((string) ($smsConfig['netgsm']['header'] ?? '')) ?>" placeholder="TALYAKIDS">
                    </label>
                    <label class="sms-field">
                        <span>Encoding</span>
                        <select name="sms_netgsm_encoding">
                            <?php foreach (['TR', 'UTF8'] as $encodingSecenek): ?>
                                <option value="<?= e($encodingSecenek) ?>" <?= (($smsConfig['netgsm']['encoding'] ?? 'TR') === $encodingSecenek) ? 'selected' : '' ?>><?= e($encodingSecenek) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="sms-field">
                        <span>IYS Filter</span>
                        <select name="sms_netgsm_filter">
                            <?php foreach (['0', '1'] as $filterSecenek): ?>
                                <option value="<?= e($filterSecenek) ?>" <?= ((string) ($smsConfig['netgsm']['filter'] ?? '0') === $filterSecenek) ? 'selected' : '' ?>><?= e($filterSecenek) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="sms-field">
                        <span>Varsayilan Test Telefonu</span>
                        <input name="sms_test_phone" value="<?= e((string) ($smsConfig['test_phone'] ?? '')) ?>" placeholder="05xxxxxxxxx">
                    </label>
                    <label class="sms-field">
                        <span>Zorunlu Yonlendirme Telefonu</span>
                        <input name="sms_force_to" value="<?= e((string) ($smsConfig['force_to'] ?? '')) ?>" placeholder="Bos birakirsaniz gonderilmez">
                    </label>
                    <label class="sms-field">
                        <span>Maksimum Alici / Istek</span>
                        <input type="number" min="1" max="10000" name="sms_max_recipients_per_request" value="<?= e((string) ($smsConfig['max_recipients_per_request'] ?? 1000)) ?>">
                    </label>
                    <label class="sms-field">
                        <span>Maksimum Tekrar Deneme</span>
                        <input type="number" min="0" max="20" name="sms_max_retry_count" value="<?= e((string) ($smsConfig['max_retry_count'] ?? 3)) ?>">
                    </label>
                    <label class="sms-field">
                        <span>Tekrar Bekleme (dk)</span>
                        <input type="number" min="1" max="1440" name="sms_retry_delay_minutes" value="<?= e((string) ($smsConfig['retry_delay_minutes'] ?? 10)) ?>">
                    </label>
                    <label class="sms-field full">
                        <span>API Base URL</span>
                        <input name="sms_netgsm_base_url" value="<?= e((string) ($smsConfig['netgsm']['base_url'] ?? 'https://api.netgsm.com.tr')) ?>">
                    </label>
                    <label class="sms-field">
                        <span>Gonderim Yolu</span>
                        <input name="sms_netgsm_send_path" value="<?= e((string) ($smsConfig['netgsm']['send_path'] ?? '/sms/rest/v2/send')) ?>">
                    </label>
                    <label class="sms-field">
                        <span>Rapor Yolu</span>
                        <input name="sms_netgsm_report_path" value="<?= e((string) ($smsConfig['netgsm']['report_path'] ?? '/sms/report')) ?>">
                    </label>
                    <label class="sms-field">
                        <span>Baglanti Timeout (sn)</span>
                        <input type="number" min="1" max="120" name="sms_netgsm_connect_timeout" value="<?= e((string) ($smsConfig['netgsm']['connect_timeout'] ?? 10)) ?>">
                    </label>
                    <label class="sms-field">
                        <span>Istek Timeout (sn)</span>
                        <input type="number" min="1" max="300" name="sms_netgsm_timeout" value="<?= e((string) ($smsConfig['netgsm']['timeout'] ?? 30)) ?>">
                    </label>
                </div>

                <div class="sms-form-footer">
                    <small data-sms-connection-message>Bu ayarlar sadece secili kuruma uygulanir.</small>
                    <div class="sms-footer-actions">
                        <button class="btn btn-ghost" type="button" data-netgsm-verify>Baglantiyi Dogrula</button>
                        <button class="btn btn-ghost" type="button" data-netgsm-verify-form>Kaydetmeden Test Et</button>
                        <button class="btn btn-primary" type="submit">NetGSM Ayarlarini Kaydet</button>
                    </div>
                </div>
            </form>
        </article>

        <article class="form-card sms-section-card sms-reminder-settings">
            <div class="sms-section-head">
                <div>
                    <h2>Randevu Hatirlatma Ayarlari</h2>
                    <p>Hatirlatma cron'u calistiginda bu ayarlara gore SMS kuyrugu olusturulur.</p>
                </div>
            </div>
            <form class="sms-reminder-form" data-sms-reminder-form>
                <label class="sms-switch-card">
                    <div>
                        <strong>Randevu hatirlatma SMS'leri aktif</strong>
                        <small>Aktifse secilen kurala gore otomatik kuyruk olusur.</small>
                    </div>
                    <input type="checkbox" name="appointment_reminder_enabled" value="1" <?= !empty($smsReminderSettings['appointment_reminder_enabled']) ? 'checked' : '' ?>>
                </label>
                <label class="sms-field">
                    <span>Kac gun once</span>
                    <select name="appointment_reminder_days_before">
                        <?php for ($i = 0; $i <= 7; $i++): ?>
                            <option value="<?= $i ?>" <?= ((int) ($smsReminderSettings['appointment_reminder_days_before'] ?? 1) === $i) ? 'selected' : '' ?>>
                                <?= $i === 0 ? 'Ayni gun' : $i . ' gun once' ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label class="sms-field">
                    <span>Gonderim saati</span>
                    <input type="time" name="appointment_reminder_time" value="<?= e((string) ($smsReminderSettings['appointment_reminder_time'] ?? '14:00')) ?>">
                </label>
                <div class="sms-form-footer">
                    <small data-sms-reminder-message>Ornek: 1 gun once / 14:00 ayari, yarinki randevulari bugun 14:00'ten sonra kuyruga alir.</small>
                    <button class="btn btn-primary" type="submit">Ayarlari Kaydet</button>
                </div>
            </form>
        </article>
    <?php endif; ?>

    <div class="two-column-grid">
        <article class="form-card">
            <h2>Manuel SMS</h2>
            <form class="form-grid compact-form" data-sms-single-form>
                <label><span>Telefon</span><input name="telefon" placeholder="0(537) 495 83 06" required></label>
                <label class="full"><span>Sablon</span>
                    <select data-sms-template-select>
                        <option value="">Manuel metin</option>
                        <?php foreach ($sablonlar as $sablon): ?>
                            <option value="<?= e($sablon['anahtar']) ?>" data-message="<?= e($sablon['mesaj']) ?>"><?= e($sablon['baslik']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="full"><span>Mesaj</span><textarea name="mesaj" rows="5" required></textarea></label>
                <div class="form-actions full">
                    <small data-sms-counter>0 karakter / 0 parca</small>
                    <button class="btn btn-primary" type="submit">Kuyruga Ekle</button>
                </div>
            </form>
        </article>

        <article class="form-card">
            <h2>Toplu SMS</h2>
            <form class="form-grid compact-form" data-sms-bulk-form>
                <label class="full"><span>Telefonlar</span><textarea name="telefonlar" rows="5" placeholder="Her satira bir numara veya virgul ile ayirin" required></textarea></label>
                <label class="full"><span>Mesaj</span><textarea name="mesaj" rows="5" required></textarea></label>
                <div class="form-actions full">
                    <small data-sms-bulk-counter>0 karakter / 0 parca</small>
                    <button class="btn btn-sky" type="submit">Toplu Kuyruga Ekle</button>
                </div>
            </form>
        </article>
    </div>

    <article class="definition-card nested-card">
        <div class="definition-head">
            <h2>SMS Kayitlari</h2>
            <div class="inline-actions">
                <?php if (yetki_var('sms_rapor_goruntule')): ?>
                    <a class="btn btn-ghost" href="/panel/sms/raporlar">SMS Raporlari</a>
                <?php endif; ?>
                <input class="search-input" type="search" placeholder="Telefon, ogrenci, mesaj ara" data-sms-search>
                <select data-sms-status-filter>
                    <option value="">Tum durumlar</option>
                    <option value="bekliyor">Bekliyor</option>
                    <option value="isleniyor">Isleniyor</option>
                    <option value="gonderildi">Gonderildi</option>
                    <option value="teslim_edildi">Teslim edildi</option>
                    <option value="basarisiz">Basarisiz</option>
                    <option value="tekrar_bekliyor">Tekrar bekliyor</option>
                    <option value="iptal">Iptal</option>
                </select>
            </div>
        </div>
        <div class="table-wrap definition-table" data-sms-table></div>
    </article>

    <article class="definition-card nested-card sms-template-section">
        <div class="definition-head">
            <h2>SMS Sablonlari</h2>
            <div class="inline-actions">
                <button class="btn btn-sky" type="button" data-open-sms-template>+ Sablon Ekle</button>
            </div>
        </div>
        <div class="info-box">
            <strong>Bilgilendirme</strong>
            <p>Sablon olustururken ya da guncellerken SMS iceriginde {klinik_adi} etiketinin bulunmasi zorunludur.</p>
            <p>NetGSM dokumaninda SMS metin sablonu onaylatmak icin public API bulunmuyor. Bu ekrandaki onay sureci uygulama icinde yonetilir; NetGSM tarafinda sadece onayli gonderici adlari sorgulanabilir.</p>
        </div>
        <div class="table-wrap definition-table" data-sms-template-table></div>
    </article>
</section>

<dialog id="sms-template-dialog" class="appointment-dialog sms-template-dialog">
    <form method="dialog" class="appointment-dialog-form" data-sms-template-form>
        <div class="dialog-head">
            <h2 data-sms-template-title>SMS Sablonu Ekle</h2>
            <button type="button" data-close-dialog>x</button>
        </div>
        <div class="info-box compact-info">
            <strong>Bilgilendirme</strong>
            <p>Sablon iceriginde {klinik_adi} etiketi zorunludur. Reklam, tanitim ve kampanya icerikleri kullanmayin.</p>
            <p><strong>Kullanilabilir Etiketler:</strong> {veli_adi}, {ogrenci_adi}, {hasta_adi}, {grup_adi}, {tarih}, {saat}, {bitis_saati}, {ogretmen_adi}, {uzman_adi}, {paket_adi}, {katilim_linki}, {odeme_tutari}, {kalan_borc}, {odeme_sozu_tarihi}, {kurum_adi}, {klinik_adi}, {kurum_telefonu}</p>
        </div>
        <div class="dialog-grid one-column">
            <label><span>Sablon Anahtari</span><input name="anahtar" placeholder="randevu_olusturuldu" required></label>
            <label><span>Sablon Adi</span><input name="baslik" placeholder="Randevu Olusturuldu Sablonu" required></label>
            <label><span>SMS Icerigi</span><textarea name="mesaj" rows="7" placeholder="Sayin {veli_adi}, {ogrenci_adi} icin {tarih} {saat} randevunuz olusturulmustur. {klinik_adi}" required></textarea></label>
            <small class="sms-char-count" data-template-char-count>Karakter Sayisi: 0</small>
            <input type="hidden" name="aktif" value="0">
            <input type="hidden" name="otomatik_gonderim" value="0">
            <input type="hidden" name="aciklama" value="">
        </div>
        <div class="record-actions compact-actions">
            <span data-template-form-message></span>
            <button class="btn btn-ghost" type="button" data-close-dialog>Vazgec</button>
            <button class="btn btn-primary" type="submit" data-sms-template-submit>SMS Sablonunu Ekle</button>
        </div>
    </form>
</dialog>
