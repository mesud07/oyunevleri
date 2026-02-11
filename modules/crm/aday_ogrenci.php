<?php
require_once(__DIR__ . '/../../includes/config.php');
require_once(__DIR__ . '/../../includes/functions.php');

giris_zorunlu();
require_once(__DIR__ . '/../../theme/header.php');

$kurum_id = aktif_kurum_id();
$adaylar = [];
$subeler = [];
$ogrenciler = [];

if (!empty($db) && $kurum_id > 0) {
    $stmt = $db->prepare("SELECT * FROM subeler WHERE kurum_id = :kurum_id ORDER BY sube_adi");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $subeler = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT a.*, s.sube_adi,
            v.id AS veli_id, v.ad_soyad AS veli_ad, v.telefon AS veli_telefon, v.eposta AS veli_eposta,
            o.id AS ogrenci_id, o.ad_soyad AS ogrenci_ad, o.dogum_tarihi, o.saglik_notlari
        FROM adaylar a
        LEFT JOIN subeler s ON s.id = a.sube_id
        LEFT JOIN veliler v ON v.id = a.veli_id
        LEFT JOIN ogrenciler o ON o.id = a.ogrenci_id
        WHERE a.kurum_id = :kurum_id
        ORDER BY a.kayit_tarihi DESC");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $adaylar = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT o.id, o.ad_soyad, o.dogum_tarihi, o.saglik_notlari,
            v.id AS veli_id, v.ad_soyad AS veli_adi, v.telefon, v.eposta, v.sube_id,
            s.sube_adi
        FROM ogrenciler o
        INNER JOIN veliler v ON v.id = o.veli_id
        LEFT JOIN subeler s ON s.id = v.sube_id
        WHERE o.kurum_id = :kurum_id
        ORDER BY o.id DESC");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $ogrenciler = $stmt->fetchAll();
}
?>

<div id="content" class="main-content">
    <div class="layout-px-spacing">
        <div class="middle-content container-xxl p-0">
            <div class="secondary-nav">
                <div class="breadcrumbs-container">
                    <header class="header navbar navbar-expand-sm">
                        <div class="d-flex breadcrumb-content">
                            <div class="page-header">
                                <div class="page-title">
                                    <h3>CRM / Ön Kayıt</h3>
                                </div>
                            </div>
                        </div>
                    </header>
                </div>
            </div>

            <div class="row layout-top-spacing">
                <div class="col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="w-info">
                                    <h6 class="value">Aday Listesi</h6>
                                </div>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adayModal">Yeni Aday</button>
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-striped table-hover" id="zero-config">
                                    <thead>
                                        <tr>
                                            <th>Ad Soyad</th>
                                            <th>Telefon</th>
                                            <th>E-posta</th>
                                            <th>Şube</th>
                                            <th>Yaş (Ay)</th>
                                            <th>Durum</th>
                                            <th>Kayıt</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($adaylar)) { ?>
                                        <tr>
                                            <td>Aday bulunamadı.</td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($adaylar as $aday) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($aday['ad_soyad'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($aday['telefon'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($aday['eposta'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($aday['sube_adi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($aday['yas_ay'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($aday['durum'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($aday['kayit_tarihi'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-primary aday-duzenle"
                                                        data-id="<?php echo (int) $aday['id']; ?>"
                                                        data-sube="<?php echo (int) ($aday['sube_id'] ?? 0); ?>"
                                                        data-ad="<?php echo htmlspecialchars($aday['ad_soyad'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-telefon="<?php echo htmlspecialchars($aday['telefon'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-eposta="<?php echo htmlspecialchars($aday['eposta'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-yas="<?php echo htmlspecialchars($aday['yas_ay'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-notlar="<?php echo htmlspecialchars($aday['notlar'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-durum="<?php echo htmlspecialchars($aday['durum'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-veli-id="<?php echo (int) ($aday['veli_id'] ?? 0); ?>"
                                                        data-veli-ad="<?php echo htmlspecialchars($aday['veli_ad'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-veli-telefon="<?php echo htmlspecialchars($aday['veli_telefon'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-veli-eposta="<?php echo htmlspecialchars($aday['veli_eposta'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-ogrenci-id="<?php echo (int) ($aday['ogrenci_id'] ?? 0); ?>"
                                                        data-ogrenci-ad="<?php echo htmlspecialchars($aday['ogrenci_ad'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-dogum="<?php echo htmlspecialchars($aday['dogum_tarihi'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-saglik="<?php echo htmlspecialchars($aday['saglik_notlari'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                        Düzenle
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row layout-top-spacing">
                <div class="col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="w-info">
                                    <h6 class="value">Öğrenci Listesi</h6>
                                </div>
                                <button class="btn btn-primary" id="veliOgrenciYeniBtn">Yeni Veli & Öğrenci</button>
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Öğrenci</th>
                                            <th>Veli</th>
                                            <th>Telefon</th>
                                            <th>Şube</th>
                                            <th>Doğum Tarihi</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($ogrenciler)) { ?>
                                        <tr>
                                            <td>Öğrenci bulunamadı.</td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($ogrenciler as $ogr) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($ogr['ad_soyad'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($ogr['veli_adi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($ogr['telefon'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($ogr['sube_adi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($ogr['dogum_tarihi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-primary veli-ogrenci-duzenle"
                                                        data-ogrenci-id="<?php echo (int) $ogr['id']; ?>"
                                                        data-ogrenci-ad="<?php echo htmlspecialchars($ogr['ad_soyad'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-dogum="<?php echo htmlspecialchars($ogr['dogum_tarihi'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-saglik="<?php echo htmlspecialchars($ogr['saglik_notlari'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-veli-id="<?php echo (int) $ogr['veli_id']; ?>"
                                                        data-veli-ad="<?php echo htmlspecialchars($ogr['veli_adi'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-veli-telefon="<?php echo htmlspecialchars($ogr['telefon'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-veli-eposta="<?php echo htmlspecialchars($ogr['eposta'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-sube-id="<?php echo (int) ($ogr['sube_id'] ?? 0); ?>">
                                                        Veli/Öğrenci
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="adayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Aday Bilgileri</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="adayForm">
                    <input type="hidden" name="islem" value="aday_kaydet">
                    <input type="hidden" name="aday_id" id="aday_id" value="0">
                    <input type="hidden" name="veli_id" id="aday_veli_id" value="0">
                    <input type="hidden" name="ogrenci_id" id="aday_ogrenci_id" value="0">
                    <div class="mb-3">
                        <label class="form-label">Ad Soyad</label>
                        <input type="text" class="form-control" name="ad_soyad" id="aday_ad" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefon</label>
                        <input type="text" class="form-control" name="telefon" id="aday_telefon" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-posta</label>
                        <input type="email" class="form-control" name="eposta" id="aday_eposta">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Şube</label>
                        <select class="form-select" name="sube_id" id="aday_sube">
                            <option value="">Seçiniz</option>
                            <?php foreach ($subeler as $sube) { ?>
                                <option value="<?php echo (int) $sube['id']; ?>"><?php echo htmlspecialchars($sube['sube_adi'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Yaş (Ay)</label>
                        <input type="number" class="form-control" name="yas_ay" id="aday_yas">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notlar</label>
                        <textarea class="form-control" name="notlar" id="aday_notlar" rows="3"></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="donustur" value="1" id="aday_donustur">
                        <label class="form-check-label" for="aday_donustur">Adayı veli & öğrenciye dönüştür</label>
                    </div>
                    <div id="donusumAlanlari" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label">Veli Ad Soyad</label>
                            <input type="text" class="form-control" name="veli_ad" id="aday_veli_ad">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Veli Telefon</label>
                            <input type="text" class="form-control" name="veli_telefon" id="aday_veli_telefon">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Veli E-posta</label>
                            <input type="email" class="form-control" name="veli_eposta" id="aday_veli_eposta">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Öğrenci Ad Soyad</label>
                            <input type="text" class="form-control" name="ogrenci_ad" id="aday_ogrenci_ad">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Doğum Tarihi</label>
                            <input type="date" class="form-control" name="dogum_tarihi" id="aday_dogum_tarihi">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sağlık Notları</label>
                            <textarea class="form-control" name="saglik_notlari" id="aday_saglik_notlari" rows="3"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary" id="adayKaydetBtn">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="veliOgrenciModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Veli & Öğrenci Bilgileri</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="veliOgrenciForm">
                    <input type="hidden" name="islem" value="veli_ogrenci_kaydet">
                    <input type="hidden" name="veli_id" id="veli_id" value="0">
                    <input type="hidden" name="ogrenci_id" id="ogrenci_id" value="0">
                    <div class="mb-3">
                        <label class="form-label">Şube</label>
                        <select class="form-select" name="sube_id" id="veli_sube">
                            <option value="">Seçiniz</option>
                            <?php foreach ($subeler as $sube) { ?>
                                <option value="<?php echo (int) $sube['id']; ?>"><?php echo htmlspecialchars($sube['sube_adi'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Veli Ad Soyad</label>
                        <input type="text" class="form-control" name="veli_ad" id="veli_ad" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Veli Telefon</label>
                        <input type="text" class="form-control" name="veli_telefon" id="veli_telefon">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Veli E-posta</label>
                        <input type="email" class="form-control" name="veli_eposta" id="veli_eposta">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Öğrenci Ad Soyad</label>
                        <input type="text" class="form-control" name="ogrenci_ad" id="ogrenci_ad" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Doğum Tarihi</label>
                        <input type="date" class="form-control" name="dogum_tarihi" id="ogrenci_dogum">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sağlık Notları</label>
                        <textarea class="form-control" name="saglik_notlari" id="ogrenci_saglik" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary" id="veliOgrenciKaydetBtn">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function () {
    if (!window.jQuery) {
        console.error('jQuery yuklenemedi.');
        return;
    }
    var $ = window.jQuery;

    $('#adayKaydetBtn').on('click', function () {
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: $('#adayForm').serialize(),
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Aday kaydedilemedi.');
                }
            }
        });
    });

    $('.aday-duzenle').on('click', function () {
        var durum = $(this).data('durum') || '';
        $('#aday_id').val($(this).data('id'));
        $('#aday_veli_id').val($(this).data('veli-id'));
        $('#aday_ogrenci_id').val($(this).data('ogrenci-id'));
        $('#aday_ad').val($(this).data('ad'));
        $('#aday_telefon').val($(this).data('telefon'));
        $('#aday_eposta').val($(this).data('eposta'));
        $('#aday_yas').val($(this).data('yas'));
        $('#aday_notlar').val($(this).data('notlar'));
        $('#aday_sube').val($(this).data('sube'));
        $('#aday_veli_ad').val($(this).data('veli-ad') || $(this).data('ad'));
        $('#aday_veli_telefon').val($(this).data('veli-telefon') || $(this).data('telefon'));
        $('#aday_veli_eposta').val($(this).data('veli-eposta') || $(this).data('eposta'));
        $('#aday_ogrenci_ad').val($(this).data('ogrenci-ad') || $(this).data('ad'));
        $('#aday_dogum_tarihi').val($(this).data('dogum'));
        $('#aday_saglik_notlari').val($(this).data('saglik'));
        $('#aday_donustur').prop('checked', durum === 'donustu');
        $('#aday_donustur').trigger('change');
        $('#adayModal').modal('show');
    });

    $('#aday_donustur').on('change', function () {
        var aktif = $(this).is(':checked');
        $('#donusumAlanlari').toggle(aktif);
        $('#aday_sube').prop('required', aktif);
        $('#aday_veli_ad').prop('required', aktif);
        $('#aday_ogrenci_ad').prop('required', aktif);
        if (aktif) {
            if (!$('#aday_veli_ad').val()) {
                $('#aday_veli_ad').val($('#aday_ad').val());
            }
            if (!$('#aday_veli_telefon').val()) {
                $('#aday_veli_telefon').val($('#aday_telefon').val());
            }
            if (!$('#aday_veli_eposta').val()) {
                $('#aday_veli_eposta').val($('#aday_eposta').val());
            }
            if (!$('#aday_ogrenci_ad').val()) {
                $('#aday_ogrenci_ad').val($('#aday_ad').val());
            }
        }
    });

    $('#adayModal').on('hidden.bs.modal', function () {
        $('#adayForm')[0].reset();
        $('#aday_id').val('0');
        $('#aday_veli_id').val('0');
        $('#aday_ogrenci_id').val('0');
        $('#aday_donustur').prop('checked', false);
        $('#donusumAlanlari').hide();
        $('#aday_sube').prop('required', false);
        $('#aday_veli_ad').prop('required', false);
        $('#aday_ogrenci_ad').prop('required', false);
    });

    $('#veliOgrenciYeniBtn').on('click', function () {
        $('#veliOgrenciForm')[0].reset();
        $('#veli_id').val('0');
        $('#ogrenci_id').val('0');
        $('#veliOgrenciModal').modal('show');
    });

    $('.veli-ogrenci-duzenle').on('click', function () {
        $('#veli_id').val($(this).data('veli-id'));
        $('#ogrenci_id').val($(this).data('ogrenci-id'));
        $('#veli_sube').val($(this).data('sube-id'));
        $('#veli_ad').val($(this).data('veli-ad'));
        $('#veli_telefon').val($(this).data('veli-telefon'));
        $('#veli_eposta').val($(this).data('veli-eposta'));
        $('#ogrenci_ad').val($(this).data('ogrenci-ad'));
        $('#ogrenci_dogum').val($(this).data('dogum'));
        $('#ogrenci_saglik').val($(this).data('saglik'));
        $('#veliOgrenciModal').modal('show');
    });

    $('#veliOgrenciKaydetBtn').on('click', function () {
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: $('#veliOgrenciForm').serialize(),
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Veli/ogrenci kaydedilemedi.');
                }
            }
        });
    });

    $('#veliOgrenciModal').on('hidden.bs.modal', function () {
        $('#veliOgrenciForm')[0].reset();
        $('#veli_id').val('0');
        $('#ogrenci_id').val('0');
    });
});
</script>

<?php require_once(__DIR__ . '/../../theme/footer.php'); ?>
