<section class="page-head">
    <div>
        <h1>Haftalik Ders Programi</h1>
        <p>Gruplari gun, saat, yas araligi, program adi ve durum bilgisiyle yonetin.</p>
    </div>
    <div class="appointment-toolbar-actions">
        <button class="btn btn-ghost" type="button" data-sync-group-appointments title="Mevcut randevulari grup gun ve saatleriyle yeniden eslestir">Yenile Randevulari Senkronize Et</button>
        <button class="btn btn-ghost" type="button" data-open-vacancy-calendar>Bos Kontenjanlari Goster</button>
        <button class="btn btn-primary" type="button" data-group-add-row>Satir Ekle</button>
    </div>
</section>

<section class="weekly-program" data-group-program-page>
    <article class="weekly-program-card">
        <div class="info-box compact-info weekly-info">
            <button type="button" data-weekly-info-close>x</button>
            <strong>Bilgilendirme</strong>
            <p>Her satir bir ders programi ve grup kaydidir. Alanlari degistirdiginizde otomatik kaydedilir. Ogrenci Ata ile gruba ogrenci ekleyip cikarabilirsiniz.</p>
        </div>

        <div class="weekly-program-table-wrap fast-table-wrap">
            <table class="weekly-program-table">
                <thead>
                    <tr>
                        <th>Gun</th>
                        <th>Baslangic</th>
                        <th>Bitis</th>
                        <th>
                            <button class="weekly-sort-button" type="button" data-sort-age-group aria-sort="none">
                                Yas / Grup
                            </button>
                        </th>
                        <th>Program Adi</th>
                        <th>Kontenjan</th>
                        <th>Durum</th>
                        <th>Islem</th>
                    </tr>
                </thead>
                <tbody data-group-program-body>
                    <tr>
                        <td colspan="8">Yukleniyor...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="form-message" data-group-program-message></p>
    </article>

    <dialog class="appointment-dialog group-student-dialog" data-group-student-dialog>
        <div class="appointment-dialog-form">
            <div class="dialog-head">
                <h2>Gruba Ogrenci Ata</h2>
                <button type="button" data-group-student-close>x</button>
            </div>
            <div class="group-student-assign">
                <label>
                    <span>Ogrenci</span>
                    <select data-group-student-select>
                        <option value="">Ogrenci seciniz</option>
                    </select>
                </label>
                <button class="btn btn-primary" type="button" data-group-student-add>Gruba Ata</button>
            </div>
            <div class="group-target-select">
                <strong>Atanacak Gruplar</strong>
                <p>Ayni ogrenciyi birden fazla gun/saat grubuna tek seferde ekleyebilirsiniz.</p>
                <div class="group-target-list" data-group-target-list></div>
            </div>
            <div class="group-student-list" data-group-student-list></div>
            <div class="group-monthly-head">
                <div>
                    <h3>Aylik Grup Takibi</h3>
                    <p>Ogrencilerin bu ayki dersleri, ders sirasi ve paket bitisleri.</p>
                </div>
                <label>
                    <span>Ay</span>
                    <input type="month" data-group-month value="<?= e(date('Y-m')) ?>">
                </label>
            </div>
            <div class="group-monthly-list" data-group-monthly-list></div>
            <p class="form-message" data-group-student-message></p>
        </div>
    </dialog>

    <dialog class="appointment-dialog group-vacancy-dialog" data-group-vacancy-dialog>
        <div class="appointment-dialog-form">
            <div class="dialog-head">
                <h2>Grup Kontenjanlari</h2>
                <div class="dialog-head-actions">
                    <button class="btn btn-primary" type="button" data-group-vacancy-print-button>Yazdir</button>
                    <button type="button" data-group-vacancy-close>x</button>
                </div>
            </div>
            <div class="group-vacancy-print" data-group-vacancy-print>
                <div class="group-vacancy-print-head">
                    <h3>Haftalik Grup Kontenjan Takvimi</h3>
                    <span data-group-vacancy-range-label><?= e(date('d.m.Y H:i')) ?></span>
                </div>
                <div class="group-vacancy-controls">
                    <div class="group-vacancy-quick">
                        <button class="btn btn-ghost" type="button" data-group-vacancy-this-week>Bu Hafta</button>
                        <button class="btn btn-ghost" type="button" data-group-vacancy-next-week>Sonraki Hafta</button>
                    </div>
                    <div class="group-vacancy-dates">
                        <label>
                            <span>Baslangic</span>
                            <input type="date" data-group-vacancy-start>
                        </label>
                        <label>
                            <span>Bitis</span>
                            <input type="date" data-group-vacancy-end>
                        </label>
                        <button class="btn btn-primary" type="button" data-group-vacancy-apply>Uygula</button>
                    </div>
                </div>
                <label class="group-vacancy-option">
                    <input type="checkbox" data-group-vacancy-show-students>
                    <span>Ogrenci adlariyla yazdir</span>
                </label>
                <label class="group-vacancy-option">
                    <input type="checkbox" data-group-vacancy-show-last-week-ended>
                    <span>Gecen Hafta Dersi Bitenleri Dahil Et</span>
                </label>
                <div class="group-vacancy-summary" data-group-vacancy-summary></div>
                <div class="group-vacancy-calendar" data-group-vacancy-calendar></div>
            </div>
            <div class="record-actions compact-actions">
                <span data-group-vacancy-message></span>
                <button class="btn btn-ghost" type="button" data-group-vacancy-close>Kapat</button>
                <button class="btn btn-primary" type="button" data-group-vacancy-print-button>Yazdir</button>
            </div>
        </div>
    </dialog>
</section>
