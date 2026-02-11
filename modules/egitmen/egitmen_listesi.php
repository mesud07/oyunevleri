<?php
require_once(__DIR__ . '/../../includes/config.php');
require_once(__DIR__ . '/../../includes/functions.php');

giris_zorunlu();
require_once(__DIR__ . '/../../theme/header.php');

$kurum_id = aktif_kurum_id();
$egitmenler = [];

if (!empty($db_master) && $kurum_id > 0) {
    $stmt = $db_master->prepare("SELECT * FROM kurum_egitmenler WHERE kurum_id = :kurum_id ORDER BY id DESC");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $egitmenler = $stmt->fetchAll();
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
                                    <h3>Eğitmen Yönetimi</h3>
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
                                    <h6 class="value">Eğitmenler</h6>
                                </div>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#egitmenModal">Yeni Eğitmen</button>
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-striped table-hover" id="zero-config">
                                    <thead>
                                        <tr>
                                            <th>Ad Soyad</th>
                                            <th>Uzmanlık</th>
                                            <th>Biyografi</th>
                                            <th>Fotoğraf</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($egitmenler)) { ?>
                                        <tr>
                                            <td colspan="5">Eğitmen kaydı bulunamadı.</td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($egitmenler as $egitmen) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($egitmen['ad_soyad'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($egitmen['uzmanlik'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <?php
                                                    $bio_raw = $egitmen['biyografi'] ?? '-';
                                                    if (function_exists('mb_strimwidth')) {
                                                        $bio_kisa = mb_strimwidth($bio_raw, 0, 80, '...');
                                                    } else {
                                                        $bio_kisa = strlen($bio_raw) > 80 ? substr($bio_raw, 0, 77) . '...' : $bio_raw;
                                                    }
                                                ?>
                                                <td><?php echo htmlspecialchars($bio_kisa, ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($egitmen['fotograf_yol'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-primary egitmen-duzenle"
                                                        data-id="<?php echo (int) $egitmen['id']; ?>"
                                                        data-ad="<?php echo htmlspecialchars($egitmen['ad_soyad'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-uzmanlik="<?php echo htmlspecialchars($egitmen['uzmanlik'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-biyografi="<?php echo htmlspecialchars($egitmen['biyografi'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-fotograf="<?php echo htmlspecialchars($egitmen['fotograf_yol'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                        Düzenle
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger egitmen-sil" data-id="<?php echo (int) $egitmen['id']; ?>">Sil</button>
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

<div class="modal fade" id="egitmenModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Eğitmen Bilgileri</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="egitmenForm">
                    <input type="hidden" name="islem" value="egitmen_kaydet">
                    <input type="hidden" name="egitmen_id" id="egitmen_id" value="0">
                    <div class="mb-3">
                        <label class="form-label">Ad Soyad</label>
                        <input type="text" class="form-control" name="ad_soyad" id="egitmen_ad" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Uzmanlık</label>
                        <input type="text" class="form-control" name="uzmanlik" id="egitmen_uzmanlik">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Biyografi</label>
                        <textarea class="form-control" name="biyografi" id="egitmen_biyografi" rows="4"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fotoğraf Yolu</label>
                        <input type="text" class="form-control" name="fotograf_yol" id="egitmen_fotograf">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary" id="egitmenKaydetBtn">Kaydet</button>
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
    $('#egitmenKaydetBtn').on('click', function () {
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: $('#egitmenForm').serialize(),
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Eğitmen kaydedilemedi.');
                }
            }
        });
    });

    $('.egitmen-duzenle').on('click', function () {
        $('#egitmen_id').val($(this).data('id'));
        $('#egitmen_ad').val($(this).data('ad'));
        $('#egitmen_uzmanlik').val($(this).data('uzmanlik'));
        $('#egitmen_biyografi').val($(this).data('biyografi'));
        $('#egitmen_fotograf').val($(this).data('fotograf'));
        $('#egitmenModal').modal('show');
    });

    $('.egitmen-sil').on('click', function () {
        var id = $(this).data('id');
        if (!confirm('Eğitmen silinsin mi?')) {
            return;
        }
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: { islem: 'egitmen_sil', egitmen_id: id },
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Eğitmen silinemedi.');
                }
            }
        });
    });

    $('#egitmenModal').on('hidden.bs.modal', function () {
        $('#egitmenForm')[0].reset();
        $('#egitmen_id').val('0');
    });
});
</script>

<?php require_once(__DIR__ . '/../../theme/footer.php'); ?>
