<?php
require_once(__DIR__ . '/../../includes/config.php');
require_once(__DIR__ . '/../../includes/functions.php');

giris_zorunlu();
require_once(__DIR__ . '/../../theme/header.php');

$kurum_id = aktif_kurum_id();
$veliler = [];
$arama = trim($_GET['q'] ?? '');

if (!empty($db) && $kurum_id > 0) {
    $veli_sql = "SELECT id, ad_soyad, telefon, bakiye_hak, hak_gecerlilik_bitis, hak_donduruldu
        FROM veliler
        WHERE kurum_id = :kurum_id";
    $veli_params = ['kurum_id' => $kurum_id];
    if ($arama !== '') {
        $veli_sql .= " AND (ad_soyad LIKE :arama OR telefon LIKE :arama)";
        $veli_params['arama'] = '%' . $arama . '%';
    }
    $veli_sql .= " ORDER BY ad_soyad";
    $stmt = $db->prepare($veli_sql);
    $stmt->execute($veli_params);
    $veliler = $stmt->fetchAll();
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
                                    <h3>Hak Yönetimi</h3>
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
                                    <div class="col-md-4">
                                        <label class="form-label">Veli Arama</label>
                                        <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($arama, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Veli adı veya telefon">
                                    </div>
                                </div>
                                <div class="mt-3 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">Uygula</button>
                                    <a href="modules/hak/hak_yonetimi.php" class="btn btn-light">Temizle</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="w-info">
                                    <h6 class="value">Veliler</h6>
                                </div>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#hakEkleModal">Hak Ekle</button>
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Veli</th>
                                            <th>Telefon</th>
                                            <th>Bakiye</th>
                                            <th>Geçerlilik</th>
                                            <th>Dondurma</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($veliler)) { ?>
                                        <tr>
                                            <td>Veli kaydı bulunamadı.</td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($veliler as $veli) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($veli['ad_soyad'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($veli['telefon'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo (int) ($veli['bakiye_hak'] ?? 0); ?></td>
                                                <td><?php echo htmlspecialchars($veli['hak_gecerlilik_bitis'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo !empty($veli['hak_donduruldu']) ? 'Aktif' : '-'; ?></td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-primary hak-ekle" data-id="<?php echo (int) $veli['id']; ?>">Hak Ekle</button>
                                                    <button class="btn btn-sm btn-outline-warning hak-dondur" data-id="<?php echo (int) $veli['id']; ?>">Dondur</button>
                                                    <button class="btn btn-sm btn-outline-secondary hak-dondurma-kaldir" data-id="<?php echo (int) $veli['id']; ?>">Dondurma Kaldır</button>
                                                    <button class="btn btn-sm btn-outline-success hak-sure-uzat" data-id="<?php echo (int) $veli['id']; ?>">Süre Uzat</button>
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

<div class="modal fade" id="hakEkleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hak Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="hakEkleForm">
                    <input type="hidden" name="islem" value="hak_ekle">
                    <div class="mb-3">
                        <label class="form-label">Veli</label>
                        <select class="form-select" name="veli_id" id="hak_veli_id" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($veliler as $veli) { ?>
                                <option value="<?php echo (int) $veli['id']; ?>"><?php echo htmlspecialchars($veli['ad_soyad'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Miktar</label>
                        <input type="number" class="form-control" name="miktar" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hak Geçerlilik Bitiş</label>
                        <input type="date" class="form-control" name="gecerlilik_bitis">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alınacak Ücret (₺)</label>
                        <input type="number" class="form-control" name="ucret" min="0" step="0.01" placeholder="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Son Ödeme Tarihi</label>
                        <input type="date" class="form-control" name="son_odeme_tarihi">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <input type="text" class="form-control" name="aciklama">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary" id="hakEkleBtn">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="hakDondurModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hak Dondur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="hakDondurForm">
                    <input type="hidden" name="islem" value="hak_dondur">
                    <input type="hidden" name="veli_id" id="dondur_veli_id" value="0">
                    <div class="mb-3">
                        <label class="form-label">Başlangıç Tarihi</label>
                        <input type="date" class="form-control" name="baslangic_tarihi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bitiş Tarihi</label>
                        <input type="date" class="form-control" name="bitis_tarihi">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <input type="text" class="form-control" name="aciklama">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-warning" id="hakDondurBtn">Dondur</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="hakSureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Süre Uzat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="hakSureForm">
                    <input type="hidden" name="islem" value="hak_sure_uzat">
                    <input type="hidden" name="veli_id" id="sure_veli_id" value="0">
                    <div class="mb-3">
                        <label class="form-label">Yeni Bitiş Tarihi</label>
                        <input type="date" class="form-control" name="yeni_tarih" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-success" id="hakSureBtn">Kaydet</button>
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

    $('#hakEkleBtn').on('click', function () {
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: $('#hakEkleForm').serialize(),
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Hak eklenemedi.');
                }
            }
        });
    });

    $('#hakEkleForm input[name="ucret"]').on('input', function () {
        var val = parseFloat($(this).val() || '0');
        var $sonOdeme = $('#hakEkleForm input[name="son_odeme_tarihi"]');
        if (val > 0) {
            $sonOdeme.prop('required', true);
        } else {
            $sonOdeme.prop('required', false);
        }
    });

    $('#hakDondurBtn').on('click', function () {
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: $('#hakDondurForm').serialize(),
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Hak dondurma islemi yapilamadi.');
                }
            }
        });
    });

    $('#hakSureBtn').on('click', function () {
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: $('#hakSureForm').serialize(),
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Hak sure uzatilamadi.');
                }
            }
        });
    });

    $('.hak-ekle').on('click', function () {
        $('#hak_veli_id').val($(this).data('id'));
        $('#hakEkleModal').modal('show');
    });

    $('.hak-dondur').on('click', function () {
        $('#dondur_veli_id').val($(this).data('id'));
        $('#hakDondurModal').modal('show');
    });

    $('.hak-dondurma-kaldir').on('click', function () {
        var id = $(this).data('id');
        if (!confirm('Dondurma kaldirilsin mi?')) {
            return;
        }
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: { islem: 'hak_dondurma_kaldir', veli_id: id },
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Dondurma kaldirilmadi.');
                }
            }
        });
    });

    $('.hak-sure-uzat').on('click', function () {
        $('#sure_veli_id').val($(this).data('id'));
        $('#hakSureModal').modal('show');
    });

    $('#hakEkleModal').on('hidden.bs.modal', function () {
        $('#hakEkleForm')[0].reset();
        $('#hak_veli_id').val('');
    });

    $('#hakDondurModal').on('hidden.bs.modal', function () {
        $('#hakDondurForm')[0].reset();
        $('#dondur_veli_id').val('0');
    });

    $('#hakSureModal').on('hidden.bs.modal', function () {
        $('#hakSureForm')[0].reset();
        $('#sure_veli_id').val('0');
    });

});
</script>

<?php require_once(__DIR__ . '/../../theme/footer.php'); ?>
