<?php
$grupProgramMetni = static function (array $grup): string {
    $programlar = $grup['programlar'] ?? [];
    if (!$programlar) {
        return 'Program saati tanımlı değil';
    }
    return implode(' · ', array_map(
        static fn(array $program): string => sprintf(
            '%s %s–%s',
            (string) ($program['gun_adi'] ?? '-'),
            (string) ($program['baslangic_saati'] ?? '-'),
            (string) ($program['bitis_saati'] ?? '-')
        ),
        $programlar
    ));
};
$yasAraliklari = array_values(array_unique(array_filter(array_map(
    static fn(array $grup): string => trim((string) ($grup['yas_araligi'] ?? '')),
    $gruplar ?? []
))));
sort($yasAraliklari, SORT_NATURAL);
?>

<section class="waiting-parent-crm-page" data-waiting-parent-crm-page>
<div class="page-head">
    <div>
        <h1>Bekleyen Veliler</h1>
        <p>Günlük veli takibini, sonraki aksiyonları ve kayıt fırsatlarını tek ekrandan yönetin.</p>
    </div>
    <button class="btn btn-primary" type="button" data-open-dialog="#bekleyen-veli-dialog">Bekleyen Veli Ekle</button>
</div>

<div class="waiting-parent-crm-topbar">
    <div class="calendar-view-switch waiting-parent-display-switch" aria-label="Sayfa görünümü">
        <button class="btn is-active" type="button" data-waiting-parent-display="crm">CRM Görünümü</button>
        <button class="btn" type="button" data-waiting-parent-display="list">Liste Görünümü</button>
    </div>
    <label class="waiting-parent-crm-search">
        <span class="sr-only">Bekleyen veli ara</span>
        <input type="search" data-waiting-parent-search placeholder="Öğrenci, veli veya telefon ara">
    </label>
</div>

<section class="waiting-parent-smart-planner" data-waiting-parent-smart-planner>
    <div class="waiting-parent-smart-planner-icon" aria-hidden="true">✦</div>
    <div>
        <strong>Önümüzdeki 7 günün grup müsaitliklerine göre uygun adayları getir</strong>
        <p>Şu andan başlayan 7 günlük dönemdeki boş kontenjanları; son görüşme sonucu, takip tarihi ve son temas süresiyle birlikte değerlendirir.</p>
        <small data-waiting-parent-smart-plan-summary>Uygun adaylar hesaplanıyor...</small>
    </div>
    <button class="btn waiting-parent-smart-plan-button" type="button" data-waiting-parent-smart-plan aria-pressed="false">Otomatik Planla</button>
</section>

<section class="waiting-parent-plan-groups" data-waiting-parent-plan-groups hidden>
    <header>
        <div><span>MÜSAİT GRUPLAR</span><h2>Önümüzdeki 7 gün</h2></div>
        <small data-waiting-parent-plan-groups-count></small>
    </header>
    <div class="waiting-parent-plan-group-list" data-waiting-parent-plan-group-list></div>
</section>

<section class="waiting-parent-crm-filters" data-waiting-parent-crm-filters>
    <div class="waiting-parent-quick-filters" aria-label="Hızlı filtreler">
        <button class="is-active" type="button" data-waiting-parent-filter="all">Tümü</button>
        <button type="button" data-waiting-parent-filter="today">Bugün Aranacak</button>
        <button type="button" data-waiting-parent-filter="overdue">Gecikenler</button>
        <button type="button" data-waiting-parent-filter="closed">Kapananlar</button>
    </div>
    <label><span>Yaş grubu</span><select data-waiting-parent-age-filter><option value="">Tüm yaş grupları</option><?php foreach ($yasAraliklari as $yasAraligi): ?><option value="<?= e($yasAraligi) ?>"><?= e($yasAraligi) ?></option><?php endforeach; ?></select></label>
</section>

<p class="waiting-parent-mobile-swipe-hint" data-waiting-parent-swipe-hint aria-hidden="true"><span>←</span> Sütunları sürükleyin <span>→</span></p>
<p class="waiting-parent-drop-message" data-waiting-parent-drop-message aria-live="polite" hidden></p>
<section class="waiting-parent-kanban" data-waiting-parent-crm-view aria-label="Bekleyen veli aşamaları">
    <article class="waiting-parent-kanban-column is-new"><header><div><span></span><hgroup><h2>Yeni Eklenenler</h2><p>Henüz iletişim kurulmadı</p></hgroup></div><strong data-kanban-count="new">0</strong></header><div class="waiting-parent-kanban-list" data-kanban-column="new"></div></article>
    <article class="waiting-parent-kanban-column is-contacted"><header><div><span></span><hgroup><h2>İletişime Geçilenler</h2><p>Görüşme ve takip bilgileri</p></hgroup></div><strong data-kanban-count="contacted">0</strong></header><div class="waiting-parent-kanban-list" data-kanban-column="contacted"></div></article>
    <article class="waiting-parent-kanban-column is-appointment"><header><div><span></span><hgroup><h2>Randevu Oluşturulanlar</h2><p>Güncel randevusu bulunanlar</p></hgroup></div><strong data-kanban-count="appointment">0</strong></header><div class="waiting-parent-kanban-list" data-kanban-column="appointment"></div></article>
</section>

<section class="panel-grid single-wide" data-waiting-parent-list-view hidden>
    <article class="panel-card waiting-parent-list-panel">
        <div class="appointment-toolbar">
            <div>
                <h2>Bekleyen Veli Listesi</h2>
                <p>Detaylı raporlama, sıralama ve toplu kontrol görünümü.</p>
            </div>
            <div class="appointment-toolbar-actions waiting-parent-toolbar-actions">
                <div class="calendar-view-switch waiting-parent-view-switch" aria-label="Bekleyen veli görünümü">
                    <button class="btn is-active" type="button" data-waiting-parent-view="waiting">Bekleyenler <span data-waiting-parent-count="waiting">0</span></button>
                    <button class="btn" type="button" data-waiting-parent-view="converted">Kayda Dönüşenler <span data-waiting-parent-count="converted">0</span></button>
                    <button class="btn" type="button" data-waiting-parent-view="all">Tümü <span data-waiting-parent-count="all">0</span></button>
                </div>
            </div>
        </div>
        <div id="bekleyen-veli-tablosu" class="table-wrap" data-table="bekleyen_veli_listele"></div>
    </article>
</section>
</section>

<dialog class="appointment-dialog waiting-parent-create-dialog" id="bekleyen-veli-dialog">
    <form class="appointment-dialog-form waiting-parent-create-form" data-ajax-form="bekleyen_veli_ekle" data-refresh="bekleyen_veli_listele" data-target="#bekleyen-veli-tablosu">
        <div class="dialog-head">
            <div><h2>Bekleyen Veli Ekle</h2><p>Aday bilgilerini ve beklediği mevcut grupları tek adımda kaydedin.</p></div>
            <button type="button" class="dialog-close" data-close-dialog aria-label="Kapat">x</button>
        </div>

        <div class="waiting-parent-create-sections">
            <section class="waiting-parent-create-card">
                <div class="waiting-parent-section-title"><span>1</span><div><h3>Öğrenci Bilgileri</h3><p>Çocuğun temel bilgileri</p></div></div>
                <div class="form-grid">
                    <label><span>Öğrenci Ad Soyad *</span><input name="ogrenci_ad_soyad" placeholder="Öğrenci ad soyad" required></label>
                    <label><span>Doğum Tarihi</span><input type="date" name="ogrenci_dogum_tarihi"></label>
                </div>
            </section>

            <section class="waiting-parent-create-card">
                <div class="waiting-parent-section-title"><span>2</span><div><h3>Veli İletişim</h3><p>Ulaşılacak veli bilgileri</p></div></div>
                <div class="form-grid">
                    <label><span>Veli Ad Soyad *</span><input name="veli_ad_soyad" placeholder="Veli ad soyad" required></label>
                    <label><span>Telefon *</span><input name="veli_telefon" inputmode="tel" maxlength="16" data-phone-mask placeholder="0(537) 495 83 06" required></label>
                    <label class="full"><span>E-posta</span><input type="email" name="veli_eposta" placeholder="veli@ornek.com"></label>
                </div>
            </section>

            <section class="waiting-parent-create-card full">
                <div class="waiting-parent-section-title"><span>3</span><div><h3>Beklediği Gruplar</h3><p>Birden fazla uygun grup seçebilirsiniz; seçimler daha sonra görüşme detayından değiştirilebilir.</p></div></div>
                <div class="waiting-parent-create-groups">
                    <?php if (!empty($gruplar)): ?>
                        <?php foreach ($gruplar as $grup): ?>
                            <label>
                                <input type="checkbox" name="grup_ids[]" value="<?= (int) $grup['id'] ?>">
                                <span>
                                    <strong><?= e($grup['ad']) ?></strong>
                                    <small><?= e($grup['yas_araligi'] ?: 'Yaş aralığı belirtilmedi') ?></small>
                                    <small class="waiting-parent-group-schedule"><?= e($grupProgramMetni($grup)) ?></small>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-table">Seçilebilecek aktif grup bulunamadı.</div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="waiting-parent-create-card full">
                <div class="waiting-parent-section-title"><span>4</span><div><h3>Tercih ve İlk Not</h3><p>Yerleşim ve ilk görüşme için yardımcı bilgiler</p></div></div>
                <div class="form-grid">
                    <label><span>Ay Grubu</span><input name="ay_grubu" placeholder="Örn. 25-36 Ay"></label>
                    <label><span>Zaman Tercihi</span><select name="zaman_tercihi"><option value="farketmez">Fark etmez</option><option value="hafta_ici">Hafta içi</option><option value="hafta_sonu">Hafta sonu</option></select></label>
                    <label class="full"><span>Not</span><textarea name="notlar" rows="3" maxlength="500" placeholder="Uygun saatler, uygun olmadığı günler veya ilk görüşme notu"></textarea></label>
                </div>
            </section>
        </div>

        <div class="form-actions waiting-parent-create-actions">
            <span data-form-message></span>
            <button class="btn btn-secondary" type="button" data-close-dialog>Vazgeç</button>
            <button class="btn btn-primary" type="submit">Bekleyen Veli Olarak Kaydet</button>
        </div>
    </form>
</dialog>

<dialog class="appointment-dialog waiting-parent-edit-dialog" id="bekleyen-veli-duzenle-dialog" data-waiting-parent-edit-dialog>
    <form class="appointment-dialog-form waiting-parent-edit-form" data-ajax-form="bekleyen_veli_guncelle" data-refresh="bekleyen_veli_listele" data-target="#bekleyen-veli-tablosu">
        <input type="hidden" name="id">
        <div class="dialog-head">
            <div><h2>Bekleyen Veli Kaydını Düzenle</h2><p>Öğrenci, veli ve grup tercihlerini güncelleyin.</p></div>
            <button type="button" class="dialog-close" data-close-dialog aria-label="Kapat">x</button>
        </div>
        <div class="waiting-parent-edit-body">
            <div class="form-grid">
                <label><span>Öğrenci Ad Soyad *</span><input name="ogrenci_ad_soyad" required></label>
                <label><span>Doğum Tarihi</span><input type="date" name="ogrenci_dogum_tarihi"></label>
                <label><span>Veli Ad Soyad *</span><input name="veli_ad_soyad" required></label>
                <label><span>Telefon *</span><input name="veli_telefon" inputmode="tel" maxlength="16" data-phone-mask required></label>
                <label><span>E-posta</span><input type="email" name="veli_eposta"></label>
                <label><span>Ay Grubu</span><input name="ay_grubu" placeholder="Örn. 25-36 Ay"></label>
                <label><span>Zaman Tercihi</span><select name="zaman_tercihi"><option value="farketmez">Fark etmez</option><option value="hafta_ici">Hafta içi</option><option value="hafta_sonu">Hafta sonu</option></select></label>
                <label class="full"><span>Not</span><textarea name="notlar" rows="3" maxlength="500"></textarea></label>
            </div>
            <section class="waiting-parent-edit-groups">
                <h3>Beklediği Gruplar</h3>
                <div class="waiting-parent-create-groups">
                    <?php foreach (($gruplar ?? []) as $grup): ?>
                        <label>
                            <input type="checkbox" name="grup_ids[]" value="<?= (int) $grup['id'] ?>">
                            <span><strong><?= e($grup['ad']) ?></strong><small><?= e($grup['yas_araligi'] ?: 'Yaş aralığı belirtilmedi') ?></small><small class="waiting-parent-group-schedule"><?= e($grupProgramMetni($grup)) ?></small></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
        <div class="form-actions waiting-parent-create-actions">
            <span data-form-message></span>
            <button class="btn btn-secondary" type="button" data-close-dialog>Vazgeç</button>
            <button class="btn btn-primary" type="submit">Değişiklikleri Kaydet</button>
        </div>
    </form>
</dialog>

<dialog class="appointment-dialog waiting-parent-history-dialog waiting-parent-drawer" id="bekleyen-veli-gorusme-dialog" data-waiting-parent-history-dialog>
    <div class="dialog-head">
        <div><span class="waiting-parent-drawer-kicker">ADAY DETAYI</span><h2 data-waiting-parent-history-title>Veli Görüşme Tarihçesi</h2><p>Bilgiler, takip planı ve görüşme geçmişi</p></div>
        <button type="button" class="dialog-close" data-close-dialog aria-label="Kapat">x</button>
    </div>
    <div class="waiting-parent-drawer-summary" data-waiting-parent-drawer-summary></div>
    <div class="waiting-parent-history-layout">
        <form class="appointment-dialog-form" data-waiting-parent-history-form>
            <h3>Görüşme Ekle</h3>
            <input type="hidden" name="bekleyen_veli_id">
            <input type="hidden" name="gorusme_tarihi">
            <div class="form-grid">
                <label><span>Görüşme Kanalı *</span><select name="kanal" required><option value="telefon">Telefon</option><option value="whatsapp">WhatsApp</option><option value="yuz_yuze">Yüz yüze</option><option value="sms">SMS</option></select></label>
                <label><span>Görüşme Sonucu *</span><select name="sonuc" required><option value="goruldu">Görüşüldü</option><option value="ulasilamadi">Ulaşılamadı</option><option value="bilgi_verildi">Bilgi verildi</option><option value="veli_donecek">Veli dönecek</option><option value="tekrar_aranacak">Tekrar aranacak</option><option value="kayit_istiyor">Kayıt istiyor</option><option value="uygun_grup_yok">Uygun grup yok</option><option value="diger">Diğer</option></select></label>
                <label class="full"><span>Görüşme Özeti *</span><textarea name="ozet" rows="3" maxlength="2000" required placeholder="Konuşulanları kısaca yazın..."></textarea></label>
            </div>
            <details class="waiting-parent-follow-options">
                <summary>Takip planla <small>İsteğe bağlı</small></summary>
                <div class="form-grid">
                    <label><span>Sonraki Takip</span><input type="datetime-local" name="next_follow_up_at"></label>
                    <label><span>Sonraki Aksiyon</span><select name="next_action_type"><option value="">Aksiyon seçin</option><option value="telefonla_ara">Telefonla ara</option><option value="whatsapp_gonder">WhatsApp gönder</option><option value="veli_donusunu_bekle">Veli dönüşünü bekle</option><option value="grup_kontrol_et">Grup kontrol et</option><option value="kayit_icin_ara">Kayıt için ara</option><option value="diger">Diğer</option></select></label>
                    <label class="full"><span>Takip Notu</span><input name="next_action_note" maxlength="500" placeholder="Kısa hatırlatma"></label>
                </div>
            </details>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Görüşmeyi Kaydet</button><span data-waiting-parent-history-message></span></div>
        </form>
        <section class="waiting-parent-history-section">
            <div class="waiting-parent-history-heading">
                <div><span>İLETİŞİM KAYITLARI</span><h3>Geçmiş Görüşmeler</h3></div>
            </div>
            <div class="waiting-parent-timeline" data-waiting-parent-history-list><div class="empty-table">Görüşmeler yükleniyor...</div></div>
            <details class="waiting-parent-group-collapse">
                <summary>Beklediği grupları düzenle</summary>
                <form class="waiting-parent-group-form" data-waiting-parent-groups-form>
                    <input type="hidden" name="bekleyen_veli_id">
                    <div class="waiting-parent-group-options" data-waiting-parent-group-options></div>
                    <div class="form-actions"><button class="btn btn-secondary" type="submit">Grupları Kaydet</button><span data-waiting-parent-groups-message></span></div>
                </form>
            </details>
        </section>
    </div>
</dialog>

<dialog class="appointment-dialog waiting-parent-followup-dialog" data-waiting-parent-followup-dialog>
    <form class="appointment-dialog-form" data-waiting-parent-followup-form>
        <input type="hidden" name="id">
        <div class="dialog-head"><div><h2>Takip Tarihi Belirle</h2><p data-waiting-parent-followup-title></p></div><button type="button" data-close-dialog aria-label="Kapat">x</button></div>
        <div class="form-grid">
            <label><span>Takip Tarihi ve Saati *</span><input type="datetime-local" name="next_follow_up_at" required></label>
            <label><span>Sonraki Aksiyon *</span><select name="next_action_type" required><option value="telefonla_ara">Telefonla ara</option><option value="whatsapp_gonder">WhatsApp gönder</option><option value="veli_donusunu_bekle">Veli dönüşünü bekle</option><option value="grup_kontrol_et">Grup kontrol et</option><option value="kayit_icin_ara">Kayıt için ara</option><option value="diger">Diğer</option></select></label>
            <label class="full"><span>Not</span><textarea name="next_action_note" rows="3" maxlength="500"></textarea></label>
        </div>
        <div class="form-actions"><span data-form-message></span><button class="btn btn-secondary" type="button" data-close-dialog>Vazgeç</button><button class="btn btn-primary" type="submit">Takibi Kaydet</button></div>
    </form>
</dialog>
