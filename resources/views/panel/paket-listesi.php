<?php $canManagePackages = yetki_var('paket_ekle'); ?>

<section class="page-head">
    <div>
        <h1>Paketler</h1>
        <p>Ogrenciye atanacak paket tanimlari, ucretler ve hak bilgileri.</p>
    </div>
    <?php if ($canManagePackages) : ?>
    <div class="head-actions">
        <button class="btn btn-sky" type="button" data-toggle-panel="#paket-form-panel">+ Paket Ekle</button>
    </div>
    <?php endif; ?>
</section>

<section class="panel-grid single-wide">
    <article class="panel-card">
        <h2>Paket Tanimlari</h2>
        <p class="section-helper">Bir ogrenciye paket atandiginda bu tanimin fiyat ve hak bilgileri ogrenci paketine kopyalanir.</p>
        <?php if ($canManagePackages) : ?>
        <article id="paket-form-panel" class="inline-form-panel" hidden>
            <h3>Yeni Paket Tanimi</h3>
            <form class="form-grid" data-ajax-form="hizmet_ekle" data-refresh="hizmet_listele" data-target="#paket-tablosu">
                <label><span>Paket Adi</span><input name="hizmet_adi" placeholder="Oyun Grubu 4 Seans (24-36 Ay)" required></label>
                <label><span>Ucret</span><input type="number" step="0.01" name="ucret" required></label>
                <label><span>Haftalik Katilim</span><input type="number" name="haftalik_katilim_sayisi" min="1" value="1"></label>
                <label><span>Normal Ders</span><input type="number" name="toplam_normal_hak" min="1" value="4"></label>
                <label><span>Telafi Hakki</span><input type="number" name="toplam_telafi_hak" min="0" value="1"></label>
                <div class="form-actions full">
                    <button class="btn btn-primary" type="submit">Paket Kaydet</button>
                    <span data-form-message></span>
                </div>
            </form>
        </article>
        <?php endif; ?>
        <div id="paket-tablosu" class="table-wrap" data-table="hizmet_listele" data-can-manage-services="<?= $canManagePackages ? '1' : '0' ?>"></div>
    </article>

    <?php if ($canManagePackages) : ?>
    <dialog id="hizmet-duzenle-dialog" class="appointment-dialog payment-dialog">
        <form method="dialog" class="appointment-dialog-form" data-ajax-form="hizmet_guncelle" data-refresh="hizmet_listele" data-target="#paket-tablosu">
            <div class="dialog-head">
                <div>
                    <h2>Paket Tanımını Düzenle</h2>
                    <p>Değişiklikler bundan sonra öğrenciye atanacak paketlerde kullanılır.</p>
                </div>
                <button type="button" data-close-dialog>x</button>
            </div>
            <input type="hidden" name="id">
            <div class="dialog-grid">
                <label class="dialog-wide"><span>Paket Adı</span><input name="hizmet_adi" required></label>
                <label><span>Ücret</span><input type="number" step="0.01" min="0" name="ucret" required></label>
                <label><span>Haftalık Katılım</span><input type="number" min="1" name="haftalik_katilim_sayisi" required></label>
                <label><span>Normal Ders</span><input type="number" min="1" name="toplam_normal_hak" required></label>
                <label><span>Telafi Hakkı</span><input type="number" min="0" name="toplam_telafi_hak" required></label>
                <label>
                    <span>Durum</span>
                    <select name="aktif">
                        <option value="1">Aktif</option>
                        <option value="0">Pasif</option>
                    </select>
                </label>
            </div>
            <div class="record-actions compact-actions">
                <span data-form-message></span>
                <button class="btn btn-ghost" type="button" data-close-dialog>Vazgeç</button>
                <button class="btn btn-primary" type="submit">Değişiklikleri Kaydet</button>
            </div>
        </form>
    </dialog>
    <?php endif; ?>
</section>
