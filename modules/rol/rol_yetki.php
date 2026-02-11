<?php
require_once(__DIR__ . '/../../includes/config.php');
require_once(__DIR__ . '/../../includes/functions.php');

giris_zorunlu();
require_once(__DIR__ . '/../../theme/header.php');

$kurum_id = aktif_kurum_id();
$roller = [];
$yetkiler = [];
$rol_yetki_map = [];

$yetki_ekle_uyari = false;

if (!merkez_admin_mi()) {
    echo '<div class="layout-px-spacing"><div class="alert alert-danger mt-3">Bu sayfayı sadece merkez admin kullanabilir.</div></div>';
    require_once(__DIR__ . '/../../theme/footer.php');
    exit;
}

if (!empty($db_master) && $kurum_id > 0) {
    $stmt = $db_master->prepare("SELECT * FROM roller WHERE kurum_id = :kurum_id ORDER BY id DESC");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $roller = $stmt->fetchAll();

    $yetkiler = $db_master->query("SELECT * FROM yetkiler ORDER BY yetki_adi")->fetchAll();
    if (empty($yetkiler)) {
        $yetki_ekle_uyari = true;
    }

    if (!empty($roller)) {
        $rol_ids = array_map(function ($r) { return (int) $r['id']; }, $roller);
        $in = implode(',', $rol_ids);
        if ($in !== '') {
            $rows = $db_master->query("SELECT * FROM rol_yetkiler WHERE rol_id IN ($in)")->fetchAll();
            foreach ($rows as $row) {
                $rol_id = (int) $row['rol_id'];
                if (!isset($rol_yetki_map[$rol_id])) {
                    $rol_yetki_map[$rol_id] = [];
                }
                $rol_yetki_map[$rol_id][] = (int) $row['yetki_id'];
            }
        }
    }
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
                                    <h3>Rol & Yetki Yönetimi</h3>
                                </div>
                            </div>
                        </div>
                    </header>
                </div>
            </div>

            <?php if ($yetki_ekle_uyari) { ?>
                <div class="alert alert-warning mt-3">
                    Sistemde yetki tanımı bulunamadı. "Varsayılan Yetkileri Ekle" butonunu kullanın.
                </div>
            <?php } ?>

            <div class="row layout-top-spacing">
                <div class="col-xl-4 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="w-info">
                                    <h6 class="value">Roller</h6>
                                </div>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#rolModal">Yeni Rol</button>
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Rol</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($roller)) { ?>
                                        <tr>
                                            <td>Rol bulunamadı.</td>
                                            <td></td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($roller as $rol) { ?>
                                            <?php $rol_id = (int) $rol['id']; ?>
                                            <tr>
                                                <td>
                                                    <button class="btn btn-link rol-sec" data-id="<?php echo $rol_id; ?>"
                                                        data-adi="<?php echo htmlspecialchars($rol['rol_adi'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-yetkiler="<?php echo htmlspecialchars(json_encode($rol_yetki_map[$rol_id] ?? []), ENT_QUOTES, 'UTF-8'); ?>">
                                                        <?php echo htmlspecialchars($rol['rol_adi'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </button>
                                                </td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-danger rol-sil" data-id="<?php echo $rol_id; ?>">Sil</button>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if ($yetki_ekle_uyari) { ?>
                                <button class="btn btn-outline-primary mt-3" id="yetkiSeedBtn">Varsayılan Yetkileri Ekle</button>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="col-xl-8 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="w-info">
                                <h6 class="value">Rol Yetkileri</h6>
                                <small class="text-muted" id="seciliRolText">Bir rol seçiniz.</small>
                            </div>
                            <form id="rolYetkiForm" class="mt-3">
                                <input type="hidden" name="islem" value="rol_yetki_kaydet">
                                <input type="hidden" name="rol_id" id="rol_id" value="0">
                                <div class="row">
                                    <?php if (empty($yetkiler)) { ?>
                                        <div class="col-12">
                                            <div class="alert alert-info">Henüz yetki eklenmemiş.</div>
                                        </div>
                                    <?php } else { ?>
                                        <?php foreach ($yetkiler as $yetki) { ?>
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input yetki-checkbox" type="checkbox" name="yetkiler[]"
                                                        value="<?php echo (int) $yetki['id']; ?>" id="yetki_<?php echo (int) $yetki['id']; ?>">
                                                    <label class="form-check-label" for="yetki_<?php echo (int) $yetki['id']; ?>">
                                                        <?php echo htmlspecialchars($yetki['yetki_adi'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-primary" id="rolYetkiKaydetBtn">Yetkileri Kaydet</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="rolModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Rol</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="rolForm">
                    <input type="hidden" name="islem" value="rol_kaydet">
                    <input type="hidden" name="rol_id" id="rol_id_modal" value="0">
                    <div class="mb-3">
                        <label class="form-label">Rol Adı</label>
                        <input type="text" class="form-control" name="rol_adi" id="rol_adi" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary" id="rolKaydetBtn">Kaydet</button>
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

    $('.rol-sec').on('click', function () {
        var rolId = $(this).data('id');
        var rolAdi = $(this).data('adi');
        var yetkiler = [];
        try {
            yetkiler = JSON.parse($(this).attr('data-yetkiler')) || [];
        } catch (e) {
            yetkiler = [];
        }
        $('#rol_id').val(rolId);
        $('#seciliRolText').text('Seçili Rol: ' + rolAdi);
        $('.yetki-checkbox').prop('checked', false);
        yetkiler.forEach(function (id) {
            $('#yetki_' + id).prop('checked', true);
        });
    });

    $('#rolKaydetBtn').on('click', function () {
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: $('#rolForm').serialize(),
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Rol kaydedilemedi.');
                }
            }
        });
    });

    $('#rolYetkiKaydetBtn').on('click', function () {
        var rolId = $('#rol_id').val();
        if (!rolId || rolId === '0') {
            alert('Önce bir rol seçiniz.');
            return;
        }
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: $('#rolYetkiForm').serialize(),
            success: function (res) {
                if (res.durum === 'ok') {
                    alert('Yetkiler güncellendi.');
                } else {
                    alert(res.mesaj || 'Yetkiler güncellenemedi.');
                }
            }
        });
    });

    $('.rol-sil').on('click', function () {
        var rolId = $(this).data('id');
        if (!confirm('Rol silinsin mi?')) {
            return;
        }
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: { islem: 'rol_sil', rol_id: rolId },
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Rol silinemedi.');
                }
            }
        });
    });

    $('#yetkiSeedBtn').on('click', function () {
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: { islem: 'yetki_seed' },
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Yetkiler eklenemedi.');
                }
            }
        });
    });

    $('#rolModal').on('hidden.bs.modal', function () {
        $('#rolForm')[0].reset();
        $('#rol_id_modal').val('0');
    });
});
</script>

<?php require_once(__DIR__ . '/../../theme/footer.php'); ?>
