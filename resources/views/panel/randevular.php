<?php
$canCreateAppointment = yetki_var('randevu_ekle');
$canEditAppointment = yetki_var('randevu_ekle');
$canChangeAppointmentStatus = yetki_var('randevu_durum_degistir');
?>
<section class="page-head appointment-page-head">
    <div>
        <h1>Randevular</h1>
        <p>Ders, telafi, workshop ve gorusme planlari.</p>
    </div>
    <div class="appointment-toolbar-actions">
        <?php if ($canCreateAppointment) : ?>
            <button class="btn btn-ghost" type="button" data-open-dialog="#hizli-randevu-dialog">Hizli Randevu Olustur</button>
            <a class="btn btn-ghost" href="/panel/randevular/yeni">Toplu Randevu</a>
            <a class="btn btn-primary" href="/panel/paketler/tanimla">Randevu Olustur</a>
        <?php endif; ?>
    </div>
</section>

<section
    class="appointment-page"
    data-randevu-page
    data-can-edit-appointments="<?= $canEditAppointment ? '1' : '0' ?>"
    data-can-change-appointment-status="<?= $canChangeAppointmentStatus ? '1' : '0' ?>"
>
    <div class="appointment-stats">
        <article class="appointment-stat">
            <span>Planlanan</span>
            <strong data-randevu-stat="planlandi">0</strong>
            <button type="button" data-randevu-filter="planlandi">Goruntule</button>
        </article>
        <article class="appointment-stat success">
            <span>Gelen</span>
            <strong data-randevu-stat="geldi">0</strong>
            <button type="button" data-randevu-filter="geldi">Goruntule</button>
        </article>
        <article class="appointment-stat danger">
            <span>Gelmeyen</span>
            <strong data-randevu-stat="gelmedi">0</strong>
            <button type="button" data-randevu-filter="gelmedi">Goruntule</button>
        </article>
        <article class="appointment-shortcuts">
            <strong>Kisayollar</strong>
            <div>
                <a class="btn btn-primary" href="/panel/ogrenciler">Ogrenciler</a>
                <?php if ($canCreateAppointment) : ?>
                    <button class="btn btn-primary" type="button" data-open-dialog="#hizli-randevu-dialog">Hizli Randevu</button>
                    <a class="btn btn-primary" href="/panel/randevular/yeni">Toplu Randevu</a>
                    <a class="btn btn-primary" href="/panel/paketler/tanimla">Yeni Randevu</a>
                <?php endif; ?>
            </div>
        </article>
    </div>

    <article class="panel-card appointment-calendar-card">
        <div class="appointment-toolbar">
            <div>
                <h2>Randevu Takvimi</h2>
                <p data-calendar-title></p>
            </div>
            <div class="appointment-toolbar-actions">
                <div class="calendar-view-switch" aria-label="Takvim gorunumu">
                    <button class="btn btn-ghost is-active" type="button" data-calendar-view="month">Ay</button>
                    <button class="btn btn-ghost" type="button" data-calendar-view="week">Hafta</button>
                    <button class="btn btn-ghost" type="button" data-calendar-view="day">Gun</button>
                </div>
                <button class="btn btn-ghost" type="button" data-calendar-prev>&lt;</button>
                <button class="btn btn-ghost" type="button" data-calendar-today>Bugun</button>
                <button class="btn btn-ghost" type="button" data-calendar-next>&gt;</button>
                <button class="btn btn-ghost" type="button" data-calendar-print>Yazdir</button>
            </div>
        </div>
        <div class="appointment-calendar" data-randevu-calendar></div>
    </article>

    <article class="panel-card">
        <div class="appointment-toolbar">
            <div>
                <h2>Randevu Listesi</h2>
                <p>Secili randevulari toplu guncelleyebilir veya silebilirsiniz.</p>
            </div>
            <div class="appointment-toolbar-actions">
                <?php if ($canChangeAppointmentStatus) : ?>
                    <select data-bulk-status>
                        <option value="">Durum sec</option>
                        <option value="planlandi">Planlandi</option>
                        <option value="geldi">Geldi</option>
                        <option value="gelmedi">Gelmedi</option>
                        <option value="mazeretli_gelmedi">Mazeretli Gelmedi</option>
                        <option value="gec_iptal">Gec Iptal</option>
                        <option value="kurum_iptali">Kurum Iptali</option>
                        <option value="ertelendi">Ertelendi</option>
                        <option value="tamamlandi">Tamamlandi</option>
                    </select>
                    <button class="btn btn-ghost" type="button" data-bulk-status-apply>Durum Degistir</button>
                <?php endif; ?>
                <?php if ($canEditAppointment) : ?>
                    <button class="btn btn-ghost" type="button" data-bulk-edit-open>Toplu Duzenle</button>
                    <button class="btn btn-danger" type="button" data-bulk-delete>Secilenleri Sil</button>
                <?php endif; ?>
            </div>
        </div>
        <div class="table-wrap appointment-list-wrap" data-randevu-table></div>
        <p class="form-message" data-randevu-message></p>
    </article>

    <dialog class="appointment-dialog appointment-detail-dialog" data-randevu-detail-dialog>
        <div class="appointment-dialog-form">
            <div class="dialog-head">
                <h2>Randevu Detaylari</h2>
                <button type="button" data-dialog-close>x</button>
            </div>
            <div class="info-box compact-info detail-info">
                <strong>Bilgilendirme</strong>
                <p>Randevunuza ait detaylari bu ekrandan goruntuleyebilirsiniz. Hasta profiline git butonunu kullanarak ogrenci listesine ulasabilirsiniz.</p>
            </div>
            <div data-randevu-detail-content></div>
            <div class="record-actions compact-actions">
                <button class="btn btn-primary" type="button" data-detail-profile>Hasta Profiline Git</button>
                <?php if ($canEditAppointment) : ?>
                    <button class="btn btn-ghost" type="button" data-detail-note>Not Ekle</button>
                <?php endif; ?>
                <?php if ($canEditAppointment) : ?>
                    <button class="btn btn-ghost" type="button" data-detail-edit>Duzenle</button>
                <?php endif; ?>
                <button class="btn btn-danger" type="button" data-dialog-close>Kapat</button>
            </div>
        </div>
    </dialog>

    <?php if ($canEditAppointment) : ?>
    <dialog class="appointment-dialog" data-randevu-note-dialog>
        <form method="dialog" class="appointment-dialog-form" data-randevu-note-form>
            <div class="dialog-head">
                <h2>Gunluk Not Ekle</h2>
                <button type="button" data-note-close>x</button>
            </div>
            <input type="hidden" name="randevu_id">
            <div class="info-box compact-info detail-info">
                <strong data-note-student>Ogrenci</strong>
                <p data-note-appointment>Randevu bilgisi</p>
            </div>
            <div class="dialog-grid">
                <label><span>Tarih</span><input type="date" name="tarih" required></label>
                <label>
                    <span>Kategori</span>
                    <select name="kategori">
                        <option value="Genel">Genel</option>
                        <option value="Davranis">Davranis</option>
                        <option value="Beslenme">Beslenme</option>
                        <option value="Uyku">Uyku</option>
                        <option value="Etkinlik">Etkinlik</option>
                        <option value="Saglik">Saglik</option>
                        <option value="Veli Bilgilendirme">Veli Bilgilendirme</option>
                    </select>
                </label>
                <label class="dialog-wide"><span>Not</span><textarea name="not_metni" rows="6" required placeholder="Bugun gozlenen davranis, katilim, etkinlik veya takip notu..."></textarea></label>
            </div>
            <div class="record-actions compact-actions">
                <span data-note-message></span>
                <button class="btn btn-ghost" type="button" data-note-close>Vazgec</button>
                <button class="btn btn-primary" type="submit">Kaydet</button>
            </div>
        </form>
    </dialog>

    <?php endif; ?>

    <?php if ($canEditAppointment) : ?>
    <dialog class="appointment-dialog" data-randevu-dialog>
        <form method="dialog" class="appointment-dialog-form" data-randevu-edit-form>
            <div class="dialog-head">
                <h2>Randevu Duzenle</h2>
                <button type="button" data-dialog-close>x</button>
            </div>
            <input type="hidden" name="id">
            <div class="dialog-grid">
                <label><span>Tarih</span><input type="date" name="tarih" required></label>
                <label><span>Saat</span><input type="time" name="baslangic_saati" required></label>
                <label><span>Sure</span><input type="number" name="sure_dakika" min="15" step="15" value="45" required></label>
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
                <label><span>Tur</span><input name="tur" required></label>
                <label><span>Hak Kaynagi</span><input name="hak_kaynagi" required></label>
                <label class="dialog-wide"><span>Not</span><textarea name="aciklama" rows="4"></textarea></label>
                <label class="check-row dialog-wide">
                    <span>SMS</span>
                    <div class="check-list">
                        <label><input type="checkbox" name="randevu_sms_gonder" value="1"> Guncelleme SMS'i gonder</label>
                    </div>
                </label>
            </div>
            <div class="record-actions compact-actions">
                <span data-edit-message></span>
                <button class="btn btn-ghost" type="button" data-dialog-close>Vazgec</button>
                <button class="btn btn-primary" type="submit">Kaydet</button>
            </div>
        </form>
    </dialog>
    <?php endif; ?>

    <?php if ($canEditAppointment) : ?>
    <dialog class="appointment-dialog" data-randevu-bulk-dialog>
        <form method="dialog" class="appointment-dialog-form" data-randevu-bulk-form>
            <div class="dialog-head">
                <h2>Toplu Duzenle</h2>
                <button type="button" data-dialog-close>x</button>
            </div>
            <div class="dialog-grid">
                <label><span>Yeni Tarih</span><input type="date" name="tarih"></label>
                <label><span>Yeni Saat</span><input type="time" name="baslangic_saati"></label>
                <label><span>Sure</span><input type="number" name="sure_dakika" min="15" step="15" value="45"></label>
                <label>
                    <span>Durum</span>
                    <select name="durum">
                        <option value="">Degistirme</option>
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
                <label class="dialog-wide"><span>Not Ekle</span><textarea name="aciklama" rows="4"></textarea></label>
            </div>
            <div class="record-actions compact-actions">
                <span data-bulk-message></span>
                <button class="btn btn-ghost" type="button" data-dialog-close>Vazgec</button>
                <button class="btn btn-primary" type="submit">Secilenleri Guncelle</button>
            </div>
        </form>
    </dialog>
    <?php endif; ?>
</section>
