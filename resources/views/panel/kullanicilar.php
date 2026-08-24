<?php
$roller = $roller ?? [];
$yetkiSecenekleri = $yetkiSecenekleri ?? [];
$yetkiHaritasi = [];

foreach ($yetkiSecenekleri as $yetki) {
    $kategori = (string) ($yetki['kategori'] ?? 'islem');
    $bolum = (string) ($yetki['bolum'] ?? $yetki['grup'] ?? 'Diger');
    $yetkiHaritasi[$kategori][$bolum][] = $yetki;
}
?>

<section class="page-head">
    <div>
        <h1>Kullanicilar</h1>
        <p>Kullanici tiplerini, giris bilgilerini ve sifreleri yonetin.</p>
    </div>
    <button class="btn btn-primary" type="button" data-user-new>Kullanici Ekle</button>
</section>

<section class="panel-card report-panel user-admin-page" data-user-page>
    <div class="appointment-toolbar user-admin-toolbar">
        <div>
            <h2>Kullanicilar</h2>
            <p>Kullanici bilgilerini, rollerini ve aktiflik durumlarini tek listeden yonetin.</p>
        </div>
    </div>
    <div class="info-box compact-info">
        <strong>Bilgilendirme</strong>
        <p>Kullanicinin gorecegi menuler ve sayfalarda yapabilecegi islemler rol yetkilerine gore belirlenir. Sifreyi bos birakirsaniz mevcut sifre degismez.</p>
    </div>
    <div class="table-wrap fast-table-wrap user-admin-table" data-user-table></div>
    <p class="form-message" data-user-message></p>

    <dialog class="appointment-dialog" data-user-dialog>
        <form method="dialog" class="appointment-dialog-form" data-user-form>
            <div class="dialog-head">
                <h2 data-user-form-title>Kullanici Ekle</h2>
                <button type="button" data-user-dialog-close>x</button>
            </div>
            <input type="hidden" name="id">
            <div class="dialog-grid">
                <label><span>Ad</span><input name="ad" required></label>
                <label><span>Soyad</span><input name="soyad" required></label>
                <label><span>Kullanici Adi / E-posta</span><input type="text" name="eposta" autocomplete="username" required></label>
                <label><span>Telefon</span><input name="telefon"></label>
                <label>
                    <span>Kullanici Tipi</span>
                    <select name="rol_id" required>
                        <?php foreach ($roller as $rol) : ?>
                            <option value="<?= e($rol['id']) ?>"><?= e($rol['ad']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Durum</span>
                    <select name="aktif">
                        <option value="1">Aktif</option>
                        <option value="0">Pasif</option>
                    </select>
                </label>
                <label class="full-row"><span>Yeni Sifre</span><input type="password" name="sifre" autocomplete="new-password" placeholder="Yeni kullanicida zorunlu, duzenlemede opsiyonel"></label>
            </div>
            <div class="record-actions compact-actions">
                <span data-user-form-message></span>
                <button class="btn btn-ghost" type="button" data-user-dialog-close>Vazgec</button>
                <button class="btn btn-primary" type="submit">Kaydet</button>
            </div>
        </form>
    </dialog>
</section>

<section class="panel-card report-panel user-role-page" data-role-page>
    <div class="appointment-toolbar user-admin-toolbar">
        <div>
            <h2>Kullanici Tipleri ve Yetkiler</h2>
            <p>Hangi menuleri goreceklerini ve hangi sayfalarda hangi islemleri yapabileceklerini buradan belirleyin.</p>
        </div>
        <button class="btn btn-ghost" type="button" data-role-new>Kullanici Tipi Ekle</button>
    </div>
    <div class="table-wrap fast-table-wrap user-role-table" data-role-table></div>
    <p class="form-message" data-role-message></p>

    <dialog class="appointment-dialog role-dialog" data-role-dialog>
        <form method="dialog" class="appointment-dialog-form user-role-dialog-form" data-role-form>
            <div class="dialog-head">
                <h2 data-role-form-title>Kullanici Tipi Ekle</h2>
                <button type="button" data-role-dialog-close>x</button>
            </div>
            <input type="hidden" name="id">
            <div class="dialog-grid user-role-head-grid">
                <label><span>Tip Adi</span><input name="ad" placeholder="Orn. Satis Danismani" required></label>
                <label><span>Tip Kodu</span><input name="kod" placeholder="Yeni tipte otomatik olusur"></label>
            </div>
            <div class="user-role-matrix">
                <section class="user-role-matrix-section">
                    <div class="user-role-matrix-head">
                        <div>
                            <h3>Menu Gorunurlugu</h3>
                            <p>Kullanici sol menude hangi alanlari gorebilsin.</p>
                        </div>
                        <button class="btn btn-ghost" type="button" data-role-toggle-section="menu">Tum menuleri sec</button>
                    </div>
                    <div class="user-role-groups">
                        <?php foreach (($yetkiHaritasi['menu'] ?? []) as $bolum => $yetkiler) : ?>
                            <article class="user-role-group">
                                <div class="user-role-group-head">
                                    <div>
                                        <strong><?= e($bolum) ?></strong>
                                        <small><?= count($yetkiler) ?> menu yetkisi</small>
                                    </div>
                                    <label class="user-role-group-toggle">
                                        <input type="checkbox" data-role-group-toggle="<?= e($bolum) ?>" data-role-group-kind="menu">
                                        <span>Hepsini sec</span>
                                    </label>
                                </div>
                                <div class="user-role-option-list">
                                    <?php foreach ($yetkiler as $yetki) : ?>
                                        <label class="user-role-option" data-permission-group="<?= e($bolum) ?>" data-permission-kind="menu">
                                            <input type="checkbox" name="yetkiler[]" value="<?= e($yetki['kod']) ?>">
                                            <span>
                                                <strong><?= e($yetki['ad']) ?></strong>
                                                <em><?= e((string) ($yetki['aciklama'] ?? ($yetki['grup'] . ' / ' . $yetki['kod']))) ?></em>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="user-role-matrix-section">
                    <div class="user-role-matrix-head">
                        <div>
                            <h3>Sayfa ve Islem Yetkileri</h3>
                            <p>Girdigi sayfalarda hangi islemleri yapabilecegini belirler.</p>
                        </div>
                        <button class="btn btn-ghost" type="button" data-role-toggle-section="islem">Tum islemleri sec</button>
                    </div>
                    <div class="user-role-groups">
                        <?php foreach (($yetkiHaritasi['islem'] ?? []) as $bolum => $yetkiler) : ?>
                            <article class="user-role-group">
                                <div class="user-role-group-head">
                                    <div>
                                        <strong><?= e($bolum) ?></strong>
                                        <small><?= count($yetkiler) ?> islem yetkisi</small>
                                    </div>
                                    <label class="user-role-group-toggle">
                                        <input type="checkbox" data-role-group-toggle="<?= e($bolum) ?>" data-role-group-kind="islem">
                                        <span>Hepsini sec</span>
                                    </label>
                                </div>
                                <div class="user-role-option-list">
                                    <?php foreach ($yetkiler as $yetki) : ?>
                                        <label class="user-role-option" data-permission-group="<?= e($bolum) ?>" data-permission-kind="islem">
                                            <input type="checkbox" name="yetkiler[]" value="<?= e($yetki['kod']) ?>">
                                            <span>
                                                <strong><?= e($yetki['ad']) ?></strong>
                                                <em><?= e((string) ($yetki['aciklama'] ?? ($yetki['grup'] . ' / ' . $yetki['kod']))) ?></em>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
            <div class="record-actions compact-actions">
                <span data-role-form-message></span>
                <button class="btn btn-ghost" type="button" data-role-dialog-close>Vazgec</button>
                <button class="btn btn-primary" type="submit">Kaydet</button>
            </div>
        </form>
    </dialog>
    <script type="application/json" data-role-permission-map><?= json_encode($yetkiSecenekleri, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</section>
