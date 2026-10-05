<?php $modulTanimlari = $modulTanimlari ?? []; ?>

<section class="page-head">
    <div>
        <h1>Kurumlar</h1>
        <p>Sisteme bagli kurumlari ve kurum kodlarini yonetin.</p>
    </div>
    <button class="btn btn-primary" type="button" data-institution-new>Yeni Kurum</button>
</section>

<section class="panel-card report-panel" data-institution-page>
    <div class="info-box compact-info">
        <strong>Sistem Yonetimi</strong>
        <p>Kurum kodu giris ekraninda kullanilir ve kurumlar arasinda benzersizdir. Yeni kurumlar ilk olarak pasif acilir.</p>
    </div>
    <div class="table-wrap fast-table-wrap" data-institution-table></div>
    <p class="form-message" data-institution-message></p>

    <dialog class="appointment-dialog institution-dialog" data-institution-dialog>
        <form method="dialog" class="appointment-dialog-form" data-institution-form>
            <div class="dialog-head">
                <div>
                    <h2 data-institution-form-title>Yeni Kurum</h2>
                    <p>Kurumun panel girisinde kullanacagi temel bilgileri girin.</p>
                </div>
                <button type="button" data-institution-dialog-close aria-label="Kapat">x</button>
            </div>
            <input type="hidden" name="id">
            <div class="dialog-grid">
                <label>
                    <span>Kurum Adi</span>
                    <input name="ad" maxlength="190" required>
                </label>
                <label>
                    <span>Kurum Kodu</span>
                    <input name="kod" maxlength="30" pattern="[A-Za-z0-9_-]{2,30}" autocomplete="off" required>
                </label>
                <label>
                    <span>Durum</span>
                    <select name="aktif">
                        <option value="0">Pasif</option>
                        <option value="1">Aktif</option>
                    </select>
                </label>
                <section class="dialog-wide institution-module-section">
                    <div class="institution-section-heading">
                        <div>
                            <strong>Kurum Modülleri</strong>
                            <p>Genel Bakış her zaman açıktır. Modülleri ve altındaki sidebar sayfalarını ayrı ayrı yönetin.</p>
                        </div>
                        <button class="btn btn-ghost" type="button" data-institution-modules-toggle>Tümünü Seç</button>
                    </div>
                    <div class="institution-module-list">
                        <?php foreach ($modulTanimlari as $modulKodu => $modul) : ?>
                            <article class="institution-module-option" data-institution-module="<?= e($modulKodu) ?>">
                                <label class="institution-module-main">
                                    <input type="checkbox" name="moduller[]" value="<?= e($modulKodu) ?>" data-institution-module-toggle checked>
                                    <span>
                                    <strong><?= e($modul['ad']) ?></strong>
                                        <small>Modülün tamamını açar veya kapatır.</small>
                                    </span>
                                </label>
                                <div class="institution-page-options">
                                    <?php foreach ($modul['sayfalar'] as $sayfaKodu => $sayfaAdi) : ?>
                                        <label>
                                            <input type="checkbox" name="sayfalar[]" value="<?= e($sayfaKodu) ?>" data-institution-page-option checked>
                                            <span><?= e($sayfaAdi) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
                <section class="dialog-wide institution-manager-section" data-institution-manager-section>
                    <div class="institution-section-heading">
                        <div>
                            <strong>Kurum Müdürü Hesabı</strong>
                            <p>Hesabı buradan oluşturabilir, bilgilerini güncelleyebilir veya yeni şifre verebilirsiniz.</p>
                        </div>
                        <span class="status-pill" data-institution-manager-status>Tanımlı değil</span>
                    </div>
                    <div class="dialog-grid">
                        <label><span>Ad</span><input name="mudur_ad" maxlength="100" autocomplete="off"></label>
                        <label><span>Soyad</span><input name="mudur_soyad" maxlength="100" autocomplete="off"></label>
                        <label><span>Kullanıcı Adı veya E-posta</span><input name="mudur_eposta" maxlength="190" autocomplete="off"></label>
                        <label><span>Telefon</span><input name="mudur_telefon" maxlength="40" autocomplete="off"></label>
                        <label>
                            <span>Hesap Durumu</span>
                            <select name="mudur_aktif">
                                <option value="1">Aktif</option>
                                <option value="0">Pasif</option>
                            </select>
                        </label>
                        <label>
                            <span>Yeni Şifre</span>
                            <input type="password" name="mudur_sifre" minlength="12" maxlength="128" autocomplete="new-password" placeholder="Mevcut hesapta boş bırakılabilir">
                        </label>
                    </div>
                </section>
                <section class="dialog-wide institution-logo-section" data-institution-logo-section hidden>
                    <div class="institution-logo-preview" data-institution-logo-preview>
                        <span>Logo yüklenmedi</span>
                    </div>
                    <div class="institution-logo-fields">
                        <strong>Kurum Logosu</strong>
                        <p>Onam formlarının PDF çıktısında kullanılacaktır. PNG veya JPG; en fazla 2 MB.</p>
                        <input type="file" name="logo" accept="image/png,image/jpeg" data-institution-logo-input>
                        <div class="row-actions">
                            <button class="btn btn-sky" type="button" data-institution-logo-upload>Logoyu Yükle</button>
                            <span data-institution-logo-message></span>
                        </div>
                    </div>
                </section>
                <section class="dialog-wide institution-portal-section" data-institution-portal-section hidden>
                    <strong>Veli Portalı Bağlantısı</strong>
                    <p>Bu bağlantı yalnızca bu kuruma aittir. Veliler bağlantıyı açıp kayıtlı telefon numaralarını girerek bilgilerine ulaşabilir.</p>
                    <div class="institution-portal-link-row">
                        <input type="text" readonly data-institution-portal-url aria-label="Veli portalı bağlantısı">
                        <button class="btn btn-sky" type="button" data-institution-portal-copy>Bağlantıyı Kopyala</button>
                        <a class="btn btn-ghost" target="_blank" rel="noopener" data-institution-portal-open>Portalı Aç</a>
                    </div>
                    <span data-institution-portal-message></span>
                </section>
                <div class="dialog-wide institution-founder-fields institution-manager-section" data-institution-founder-fields>
                    <div class="institution-section-heading">
                        <div>
                            <strong>Kurucu Kullanıcı</strong>
                            <p data-institution-founder-description>Bu kullanıcı yalnızca kendi kurumundaki tüm panel yetkilerine sahip olur.</p>
                        </div>
                        <span class="status-pill" data-institution-founder-status>Tanımlı değil</span>
                    </div>
                    <div class="dialog-grid">
                        <label><span>Ad</span><input name="kurucu_ad" maxlength="100" autocomplete="given-name"></label>
                        <label><span>Soyad</span><input name="kurucu_soyad" maxlength="100" autocomplete="family-name"></label>
                        <label><span>Kullanici Adi veya E-posta</span><input name="kurucu_eposta" maxlength="190" autocomplete="username"></label>
                        <label><span>Telefon</span><input name="kurucu_telefon" maxlength="30" autocomplete="tel"></label>
                        <label class="dialog-wide"><span>Şifre</span><input type="password" name="kurucu_sifre" minlength="8" autocomplete="new-password"></label>
                    </div>
                </div>
            </div>
            <div class="record-actions compact-actions">
                <span data-institution-form-message></span>
                <button class="btn btn-ghost" type="button" data-institution-dialog-close>Vazgec</button>
                <button class="btn btn-primary" type="submit">Kaydet</button>
            </div>
        </form>
    </dialog>
</section>
