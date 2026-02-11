<?php
require_once(__DIR__ . '/../../includes/config.php');
require_once(__DIR__ . '/../../includes/functions.php');

giris_zorunlu();
require_once(__DIR__ . '/../../theme/header.php');

$kurum_id = aktif_kurum_id();
$durum = trim($_GET['durum'] ?? '');
$veli_id = (int) ($_GET['veli_id'] ?? 0);
$son_bas = trim($_GET['son_bas'] ?? '');
$son_bit = trim($_GET['son_bit'] ?? '');
$veliler = [];
$borclar = [];
$toplam_bekleyen = 0;
$toplam_odenen = 0;

if (!empty($db) && $kurum_id > 0) {
    $stmt = $db->prepare("SELECT id, ad_soyad FROM veliler WHERE kurum_id = :kurum_id ORDER BY ad_soyad");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $veliler = $stmt->fetchAll();

    $sql = "SELECT b.*, v.ad_soyad, v.telefon
        FROM veli_borclar b
        INNER JOIN veliler v ON v.id = b.veli_id
        WHERE b.kurum_id = :kurum_id";
    $params = ['kurum_id' => $kurum_id];

    if ($durum !== '' && in_array($durum, ['beklemede', 'odendi', 'iptal'], true)) {
        $sql .= " AND b.durum = :durum";
        $params['durum'] = $durum;
    }
    if ($veli_id > 0) {
        $sql .= " AND b.veli_id = :veli_id";
        $params['veli_id'] = $veli_id;
    }
    if ($son_bas !== '') {
        $sql .= " AND b.son_odeme_tarihi >= :son_bas";
        $params['son_bas'] = $son_bas;
    }
    if ($son_bit !== '') {
        $sql .= " AND b.son_odeme_tarihi <= :son_bit";
        $params['son_bit'] = $son_bit;
    }

    $sql .= " ORDER BY b.son_odeme_tarihi ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $borclar = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT COALESCE(SUM(tutar),0) FROM veli_borclar WHERE kurum_id = :kurum_id AND durum = 'beklemede'");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $toplam_bekleyen = (float) $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COALESCE(SUM(tahsil_tutar),0) FROM veli_borclar WHERE kurum_id = :kurum_id AND durum = 'odendi'");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $toplam_odenen = (float) $stmt->fetchColumn();
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
                                    <h3>Muhasebe / Tahsilatlar</h3>
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
                                <h6 class="value">Özet</h6>
                            </div>
                            <div class="row g-3 mt-2">
                                <div class="col-md-3">
                                    <div class="card p-3">
                                        <div class="text-muted">Bekleyen Tahsilat</div>
                                        <div class="h5 mb-0"><?php echo number_format($toplam_bekleyen, 2, ',', '.'); ?> ₺</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card p-3">
                                        <div class="text-muted">Tahsil Edilen</div>
                                        <div class="h5 mb-0"><?php echo number_format($toplam_odenen, 2, ',', '.'); ?> ₺</div>
                                    </div>
                                </div>
                            </div>
                            <form class="mt-4" method="get">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Durum</label>
                                        <select class="form-select" name="durum">
                                            <option value="">Tümü</option>
                                            <option value="beklemede" <?php echo $durum === 'beklemede' ? 'selected' : ''; ?>>Beklemede</option>
                                            <option value="odendi" <?php echo $durum === 'odendi' ? 'selected' : ''; ?>>Ödendi</option>
                                            <option value="iptal" <?php echo $durum === 'iptal' ? 'selected' : ''; ?>>İptal</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Veli</label>
                                        <select class="form-select" name="veli_id">
                                            <option value="">Tümü</option>
                                            <?php foreach ($veliler as $veli) { ?>
                                                <option value="<?php echo (int) $veli['id']; ?>" <?php echo $veli_id === (int) $veli['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($veli['ad_soyad'], ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Son Ödeme (Başlangıç)</label>
                                        <input type="date" class="form-control" name="son_bas" value="<?php echo htmlspecialchars($son_bas, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Son Ödeme (Bitiş)</label>
                                        <input type="date" class="form-control" name="son_bit" value="<?php echo htmlspecialchars($son_bit, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                                <div class="mt-3 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">Uygula</button>
                                    <a href="modules/muhasebe/tahsilatlar.php" class="btn btn-light">Temizle</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="w-info">
                                <h6 class="value">Tahsilat Listesi</h6>
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-striped table-hover" id="zero-config">
                                    <thead>
                                        <tr>
                                            <th>Veli</th>
                                            <th>Telefon</th>
                                            <th>Hak</th>
                                            <th>Tutar</th>
                                            <th>Son Ödeme</th>
                                            <th>Durum</th>
                                            <th>Ödeme Tarihi</th>
                                            <th>Yöntem</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($borclar)) { ?>
                                        <tr>
                                            <td>Tahsilat bulunamadı.</td>
                                            <td></td>
                                            <td></td>
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
                                                <td><?php echo htmlspecialchars($borc['durum'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo !empty($borc['odeme_tarihi']) ? htmlspecialchars(date('d.m.Y H:i', strtotime($borc['odeme_tarihi'])), ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                                                <td><?php echo htmlspecialchars($borc['odeme_yontemi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="text-end">
                                                    <?php if (($borc['durum'] ?? '') === 'beklemede') { ?>
                                                        <button class="btn btn-sm btn-outline-success tahsilat-ekle"
                                                            data-id="<?php echo (int) $borc['id']; ?>"
                                                            data-veli="<?php echo htmlspecialchars($borc['ad_soyad'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-tutar="<?php echo (float) ($borc['tutar'] ?? 0); ?>"
                                                            data-son-odeme="<?php echo htmlspecialchars($borc['son_odeme_tarihi'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                            Tahsil Et
                                                        </button>
                                                    <?php } ?>
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
        return;
    }
    var $ = window.jQuery;

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

    $('.tahsilat-ekle').on('click', function () {
        $('#tahsil_borc_id').val($(this).data('id'));
        $('#tahsil_veli').val($(this).data('veli'));
        $('#tahsil_tutar').val($(this).data('tutar'));
        $('#tahsil_son_odeme').val($(this).data('son-odeme'));
        $('#tahsilatModal').modal('show');
    });

    $('#tahsilatModal').on('hidden.bs.modal', function () {
        $('#tahsilatForm')[0].reset();
        $('#tahsil_borc_id').val('0');
    });
});
</script>

<?php require_once(__DIR__ . '/../../theme/footer.php'); ?>
