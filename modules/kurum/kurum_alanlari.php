<?php
require_once(__DIR__ . '/../../includes/config.php');
require_once(__DIR__ . '/../../includes/functions.php');

giris_zorunlu();
require_once(__DIR__ . '/../../theme/header.php');

$kurum_id = aktif_kurum_id();
$alanlar = [];

if (!empty($db) && $kurum_id > 0) {
    $stmt = $db->prepare("SELECT id, alan_adi, kapasite, aciklama, durum FROM kurum_alanlari WHERE kurum_id = :kurum_id ORDER BY alan_adi");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $alanlar = $stmt->fetchAll();
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
                                    <h3>Kurum Alanları</h3>
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
                                    <h6 class="value">Alan Listesi</h6>
                                </div>
                                <button class="btn btn-primary" id="alanEkleBtn">Alan Ekle</button>
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-striped table-hover" id="zero-config">
                                    <thead>
                                        <tr>
                                            <th>Alan</th>
                                            <th>Kapasite</th>
                                            <th>Durum</th>
                                            <th>Açıklama</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($alanlar)) { ?>
                                        <tr>
                                            <td>Alan kaydı bulunamadı.</td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($alanlar as $alan) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($alan['alan_adi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo (int) ($alan['kapasite'] ?? 0); ?></td>
                                                <td><?php echo !empty($alan['durum']) ? 'Aktif' : 'Pasif'; ?></td>
                                                <td><?php echo htmlspecialchars($alan['aciklama'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-primary alan-duzenle"
                                                        data-id="<?php echo (int) $alan['id']; ?>"
                                                        data-adi="<?php echo htmlspecialchars($alan['alan_adi'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-kapasite="<?php echo (int) ($alan['kapasite'] ?? 0); ?>"
                                                        data-durum="<?php echo (int) ($alan['durum'] ?? 0); ?>"
                                                        data-aciklama="<?php echo htmlspecialchars($alan['aciklama'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                        Düzenle
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger alan-sil" data-id="<?php echo (int) $alan['id']; ?>">Sil</button>
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

<div class="modal fade" id="alanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kurum Alanı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="alanForm">
                    <input type="hidden" name="islem" value="kurum_alani_kaydet">
                    <input type="hidden" name="alan_id" id="alan_id" value="0">
                    <div class="mb-3">
                        <label class="form-label">Alan Adı</label>
                        <input type="text" class="form-control" name="alan_adi" id="alan_adi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kapasite</label>
                        <input type="number" class="form-control" name="kapasite" id="alan_kapasite" min="0" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Durum</label>
                        <select class="form-select" name="durum" id="alan_durum">
                            <option value="1">Aktif</option>
                            <option value="0">Pasif</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="aciklama" id="alan_aciklama"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary" id="alanKaydetBtn">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function () {
    if (!window.jQuery) {
        return;
    }
    var $ = window.jQuery;

    $('#alanEkleBtn').on('click', function () {
        $('#alanForm')[0].reset();
        $('#alan_id').val('0');
        $('#alan_durum').val('1');
        $('#alanModal').modal('show');
    });

    $('.alan-duzenle').on('click', function () {
        $('#alan_id').val($(this).data('id'));
        $('#alan_adi').val($(this).data('adi'));
        $('#alan_kapasite').val($(this).data('kapasite'));
        $('#alan_durum').val($(this).data('durum'));
        $('#alan_aciklama').val($(this).data('aciklama'));
        $('#alanModal').modal('show');
    });

    $('#alanKaydetBtn').on('click', function () {
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: $('#alanForm').serialize(),
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Alan kaydedilemedi.');
                }
            }
        });
    });

    $('.alan-sil').on('click', function () {
        var id = $(this).data('id');
        if (!confirm('Alan silinsin mi?')) {
            return;
        }
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: { islem: 'kurum_alani_sil', alan_id: id },
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Alan silinemedi.');
                }
            }
        });
    });
});
</script>

<?php require_once(__DIR__ . '/../../theme/footer.php'); ?>
