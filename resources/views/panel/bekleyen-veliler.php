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
?>

<section class="page-head">
    <div>
        <h1>Bekleyen Veliler</h1>
        <p>Grup kontenjanı bekleyen aday öğrenci ve veli talepleri.</p>
    </div>
    <button class="btn btn-primary" type="button" data-open-dialog="#bekleyen-veli-dialog">Bekleyen Veli Ekle</button>
</section>

<section class="panel-grid single-wide">
    <article class="panel-card">
        <div class="appointment-toolbar">
            <div>
                <h2>Bekleyen Veli Listesi</h2>
                <p>İsim, telefon, grup, durum veya görüşme notuna göre arama yapabilirsiniz.</p>
            </div>
            <div class="appointment-toolbar-actions waiting-parent-toolbar-actions">
                <div class="calendar-view-switch waiting-parent-view-switch" aria-label="Bekleyen veli görünümü">
                    <button class="btn is-active" type="button" data-waiting-parent-view="waiting">Bekleyenler <span data-waiting-parent-count="waiting">0</span></button>
                    <button class="btn" type="button" data-waiting-parent-view="converted">Kayda Dönüşenler <span data-waiting-parent-count="converted">0</span></button>
                    <button class="btn" type="button" data-waiting-parent-view="all">Tümü <span data-waiting-parent-count="all">0</span></button>
                </div>
                <label class="table-search">
                    <span>Arama</span>
                    <input type="search" data-waiting-parent-search placeholder="İsim, telefon veya grup ara">
                </label>
            </div>
        </div>
        <div id="bekleyen-veli-tablosu" class="table-wrap" data-table="bekleyen_veli_listele"></div>
    </article>
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

<dialog class="appointment-dialog dialog-wide waiting-parent-history-dialog" id="bekleyen-veli-gorusme-dialog" data-waiting-parent-history-dialog>
    <div class="dialog-head">
        <div><h2>Veli Görüşme Tarihçesi</h2><p data-waiting-parent-history-title></p></div>
        <button type="button" class="dialog-close" data-close-dialog aria-label="Kapat">x</button>
    </div>
    <div class="waiting-parent-history-layout">
        <section>
            <form class="waiting-parent-group-form" data-waiting-parent-groups-form>
                <input type="hidden" name="bekleyen_veli_id">
                <div><h3>Beklediği Gruplar</h3><p>Veli birden fazla uygun gruba bağlanabilir.</p></div>
                <div class="waiting-parent-group-options" data-waiting-parent-group-options></div>
                <div class="form-actions"><button class="btn btn-secondary" type="submit">Grupları Kaydet</button><span data-waiting-parent-groups-message></span></div>
            </form>
            <h3>Geçmiş Görüşmeler</h3>
            <div class="waiting-parent-timeline" data-waiting-parent-history-list><div class="empty-table">Görüşmeler yükleniyor...</div></div>
        </section>
        <form class="appointment-dialog-form" data-waiting-parent-history-form>
            <h3>Yeni Görüşme Ekle</h3>
            <input type="hidden" name="bekleyen_veli_id">
            <div class="form-grid">
                <label><span>Görüşme Tarihi *</span><input type="datetime-local" name="gorusme_tarihi" required></label>
                <label><span>Görüşme Kanalı *</span><select name="kanal" required><option value="telefon">Telefon</option><option value="whatsapp">WhatsApp</option><option value="yuz_yuze">Yüz yüze</option><option value="sms">SMS</option><option value="diger">Diğer</option></select></label>
                <label><span>Görüşme Sonucu *</span><select name="sonuc" required><option value="bilgi_verildi">Bilgi verildi</option><option value="tekrar_aranacak">Tekrar aranacak</option><option value="randevu_planlandi">Randevu planlandı</option><option value="kararsiz">Kararsız</option><option value="ulasilamadi">Ulaşılamadı</option><option value="katilmadi">Katılmadı</option><option value="olumsuz">Olumsuz</option><option value="diger">Diğer</option></select></label>
                <label><span>Sonraki Takip Tarihi</span><input type="date" name="sonraki_takip_tarihi"></label>
                <label class="full"><span>Görüşme Özeti *</span><textarea name="ozet" rows="5" maxlength="2000" required placeholder="Görüşmede konuşulanlar, velinin talepleri ve alınan karar..."></textarea></label>
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Görüşmeyi Kaydet</button><span data-waiting-parent-history-message></span></div>
        </form>
    </div>
</dialog>
