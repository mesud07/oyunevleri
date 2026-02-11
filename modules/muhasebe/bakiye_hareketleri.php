<?php
require_once(__DIR__ . '/../../includes/config.php');
require_once(__DIR__ . '/../../includes/functions.php');

giris_zorunlu();
require_once(__DIR__ . '/../../theme/header.php');

$kurum_id = aktif_kurum_id();
$hareket_veli_id = (int) ($_GET['hareket_veli_id'] ?? 0);
$hareket_islem = trim($_GET['hareket_islem'] ?? '');
$tarih_bas = trim($_GET['tarih_bas'] ?? '');
$tarih_bit = trim($_GET['tarih_bit'] ?? '');
$veli_ops = [];
$hareketler = [];

if (!empty($db) && $kurum_id > 0) {
    $stmt = $db->prepare("SELECT id, ad_soyad FROM veliler WHERE kurum_id = :kurum_id ORDER BY ad_soyad");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $veli_ops = $stmt->fetchAll();

    $hareket_sql = "SELECT h.*, v.ad_soyad, b.id AS borc_id, b.durum AS borc_durum
        FROM veli_hak_hareketleri h
        INNER JOIN veliler v ON v.id = h.veli_id
        LEFT JOIN veli_borclar b ON b.hak_hareket_id = h.id AND b.kurum_id = h.kurum_id
        WHERE h.kurum_id = :kurum_id";
    $hareket_params = ['kurum_id' => $kurum_id];

    if ($hareket_veli_id > 0) {
        $hareket_sql .= " AND h.veli_id = :veli_id";
        $hareket_params['veli_id'] = $hareket_veli_id;
    }
    if ($hareket_islem !== '' && in_array($hareket_islem, ['ekleme', 'kullanim', 'iade'], true)) {
        $hareket_sql .= " AND h.islem_tipi = :islem_tipi";
        $hareket_params['islem_tipi'] = $hareket_islem;
    }
    if ($tarih_bas !== '') {
        $hareket_sql .= " AND h.tarih >= :tarih_bas";
        $hareket_params['tarih_bas'] = $tarih_bas . " 00:00:00";
    }
    if ($tarih_bit !== '') {
        $hareket_sql .= " AND h.tarih <= :tarih_bit";
        $hareket_params['tarih_bit'] = $tarih_bit . " 23:59:59";
    }

    $hareket_sql .= " ORDER BY h.tarih DESC LIMIT 200";
    $stmt = $db->prepare($hareket_sql);
    $stmt->execute($hareket_params);
    $hareketler = $stmt->fetchAll();
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
                                    <h3>Muhasebe / Bakiye Hareketleri</h3>
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
                                        <label class="form-label">Veli</label>
                                        <select class="form-select" name="hareket_veli_id">
                                            <option value="">Tümü</option>
                                            <?php foreach ($veli_ops as $veli_opt) { ?>
                                                <option value="<?php echo (int) $veli_opt['id']; ?>" <?php echo $hareket_veli_id === (int) $veli_opt['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($veli_opt['ad_soyad'], ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">İşlem Tipi</label>
                                        <select class="form-select" name="hareket_islem">
                                            <option value="">Tümü</option>
                                            <option value="ekleme" <?php echo $hareket_islem === 'ekleme' ? 'selected' : ''; ?>>Ekleme</option>
                                            <option value="kullanim" <?php echo $hareket_islem === 'kullanim' ? 'selected' : ''; ?>>Kullanım</option>
                                            <option value="iade" <?php echo $hareket_islem === 'iade' ? 'selected' : ''; ?>>İade</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">Tarih Aralığı</label>
                                        <div class="d-flex gap-2">
                                            <input type="date" class="form-control" name="tarih_bas" value="<?php echo htmlspecialchars($tarih_bas, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="date" class="form-control" name="tarih_bit" value="<?php echo htmlspecialchars($tarih_bit, ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">Uygula</button>
                                    <a href="modules/muhasebe/bakiye_hareketleri.php" class="btn btn-light">Temizle</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="w-info">
                                <h6 class="value">Bakiye Hareketleri</h6>
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-striped table-hover" id="zero-config">
                                    <thead>
                                        <tr>
                                            <th>Tarih</th>
                                            <th>Veli</th>
                                            <th>İşlem</th>
                                            <th>Miktar</th>
                                            <th>Açıklama</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($hareketler)) { ?>
                                        <tr>
                                            <td>Hareket bulunamadı.</td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($hareketler as $hareket) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($hareket['tarih'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($hareket['ad_soyad'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($hareket['islem_tipi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo (int) ($hareket['miktar'] ?? 0); ?></td>
                                                <td><?php echo htmlspecialchars($hareket['aciklama'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="text-end">
                                                    <?php if (($hareket['islem_tipi'] ?? '') === 'ekleme') { ?>
                                                        <?php if (($hareket['borc_durum'] ?? '') === 'odendi') { ?>
                                                            <span class="badge badge-light-secondary">Ödeme alındı</span>
                                                        <?php } else { ?>
                                                            <button class="btn btn-sm btn-outline-danger hak-geri-al" data-id="<?php echo (int) $hareket['id']; ?>">Geri Al</button>
                                                        <?php } ?>
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

<script>
window.addEventListener('load', function () {
    if (!window.jQuery) {
        console.error('jQuery yuklenemedi.');
        return;
    }
    var $ = window.jQuery;

    $('.hak-geri-al').on('click', function () {
        var id = $(this).data('id');
        if (!confirm('Bu hak geri alınsın mı?')) {
            return;
        }
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: { islem: 'hak_geri_al', hareket_id: id },
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Hak geri alınamadı.');
                }
            }
        });
    });
});
</script>

<?php require_once(__DIR__ . '/../../theme/footer.php'); ?>
