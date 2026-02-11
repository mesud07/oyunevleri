<?php
require_once(__DIR__ . '/../../includes/config.php');
require_once(__DIR__ . '/../../includes/functions.php');

giris_zorunlu();
require_once(__DIR__ . '/../../theme/header.php');

$kurum_id = aktif_kurum_id();
$tahsil_bas = trim($_GET['tahsil_bas'] ?? '');
$tahsil_bit = trim($_GET['tahsil_bit'] ?? '');
$borclar = [];

if (!empty($db) && $kurum_id > 0) {
    $borc_sql = "SELECT b.*, v.ad_soyad, v.telefon
        FROM veli_borclar b
        INNER JOIN veliler v ON v.id = b.veli_id
        WHERE b.kurum_id = :kurum_id AND b.durum = 'beklemede'";
    $borc_params = ['kurum_id' => $kurum_id];

    if ($tahsil_bas !== '') {
        $borc_sql .= " AND b.son_odeme_tarihi >= :tahsil_bas";
        $borc_params['tahsil_bas'] = $tahsil_bas;
    }
    if ($tahsil_bit !== '') {
        $borc_sql .= " AND b.son_odeme_tarihi <= :tahsil_bit";
        $borc_params['tahsil_bit'] = $tahsil_bit;
    }

    $borc_sql .= " ORDER BY b.son_odeme_tarihi ASC";
    $stmt = $db->prepare($borc_sql);
    $stmt->execute($borc_params);
    $borclar = $stmt->fetchAll();
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
                                    <h3>Muhasebe / Bekleyen Tahsilatlar</h3>
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
                            <div class="w-info">
                                <h6 class="value">Filtreler</h6>
                            </div>
                            <form class="mt-3" method="get">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Son Ödeme (Başlangıç)</label>
                                        <input type="date" class="form-control" name="tahsil_bas" value="<?php echo htmlspecialchars($tahsil_bas, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Son Ödeme (Bitiş)</label>
                                        <input type="date" class="form-control" name="tahsil_bit" value="<?php echo htmlspecialchars($tahsil_bit, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end gap-2">
                                        <button type="submit" class="btn btn-primary">Filtrele</button>
                                        <a href="modules/muhasebe/bekleyen_tahsilatlar.php" class="btn btn-light">Temizle</a>
                                    </div>
                                </div>
                            </form>
                            <div class="table-responsive mt-3">
                                <table class="table table-striped table-hover" id="zero-config">
                                    <thead>
                                        <tr>
                                            <th>Veli</th>
                                            <th>Telefon</th>
                                            <th>Hak</th>
                                            <th>Tutar</th>
                                            <th>Son Ödeme</th>
                                            <th>Açıklama</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($borclar)) { ?>
                                        <tr>
                                            <td>Tahsilat bekleyen borç bulunamadı.</td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($borclar as $borc) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($borc['ad_soyad'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($borc['telefon'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo (int) ($borc['hak_miktar'] ?? 0); ?></td>
                                                <td><?php echo number_format((float) ($borc['tutar'] ?? 0), 2, ',', '.'); ?> ₺</td>
                                                <td><?php echo htmlspecialchars($borc['son_odeme_tarihi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($borc['aciklama'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-success tahsilat-ekle"
                                                        data-id="<?php echo (int) $borc['id']; ?>"
                                                        data-veli="<?php echo htmlspecialchars($borc['ad_soyad'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-tutar="<?php echo (float) ($borc['tutar'] ?? 0); ?>"
                                                        data-son-odeme="<?php echo htmlspecialchars($borc['son_odeme_tarihi'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                        Tahsil Et
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

<div class="modal fade" id="tahsilatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tahsilat Al</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="tahsilatForm">
                    <input type="hidden" name="islem" value="tahsilat_ekle">
                    <input type="hidden" name="borc_id" id="tahsil_borc_id" value="0">
                    <div class="mb-3">
                        <label class="form-label">Veli</label>
                        <input type="text" class="form-control" id="tahsil_veli" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tutar (₺)</label>
                        <input type="text" class="form-control" id="tahsil_tutar" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Son Ödeme Tarihi</label>
                        <input type="text" class="form-control" id="tahsil_son_odeme" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ödeme Yöntemi</label>
                        <select class="form-select" name="odeme_yontemi" required>
                            <option value="nakit">Nakit</option>
                            <option value="kredi_karti">Kredi Kartı</option>
                            <option value="havale">Havale</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <input type="text" class="form-control" name="aciklama" placeholder="Tahsilat açıklaması">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-success" id="tahsilatBtn">Tahsil Et</button>
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

    $('.tahsilat-ekle').on('click', function () {
        $('#tahsil_borc_id').val($(this).data('id'));
        $('#tahsil_veli').val($(this).data('veli'));
        $('#tahsil_tutar').val($(this).data('tutar'));
        $('#tahsil_son_odeme').val($(this).data('son-odeme'));
        $('#tahsilatModal').modal('show');
    });

    $('#tahsilatBtn').on('click', function () {
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: $('#tahsilatForm').serialize(),
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Tahsilat kaydedilemedi.');
                }
            }
        });
    });

    $('#tahsilatModal').on('hidden.bs.modal', function () {
        $('#tahsilatForm')[0].reset();
        $('#tahsil_borc_id').val('0');
    });
});
</script>

<?php require_once(__DIR__ . '/../../theme/footer.php'); ?>
