<?php
require_once(__DIR__ . "/../../includes/config.php");
require_once(__DIR__ . "/../../includes/functions.php");

giris_zorunlu();
require_once(__DIR__ . "/../../theme/header.php");

$kurum_id = (int) ($_SESSION['kurum_id'] ?? 0);
$sube_id = (int) ($_SESSION['sube_id'] ?? 0);
$iptaller = [];
$hata_mesaji = '';

if (!empty($db) && $kurum_id > 0) {
    try {
        $sql = "SELECT r.id, r.islem_tarihi, r.durum, s.seans_baslangic, g.grup_adi,
                    o.ad_soyad AS ogrenci_adi, v.ad_soyad AS veli_adi, sb.sube_adi
                FROM rezervasyonlar r
                INNER JOIN seanslar s ON s.id = r.seans_id
                INNER JOIN oyun_gruplari g ON g.id = s.grup_id
                INNER JOIN ogrenciler o ON o.id = r.ogrenci_id
                INNER JOIN veliler v ON v.id = o.veli_id
                LEFT JOIN subeler sb ON sb.id = g.sube_id
                WHERE r.kurum_id = :kurum_id
                  AND r.durum = 'iptal'
                  AND r.iptal_onay = 0";
        if ($sube_id > 0) {
            $sql .= " AND g.sube_id = :sube_id";
        }
        $sql .= " ORDER BY r.islem_tarihi DESC";
        $stmt = $db->prepare($sql);
        $params = ['kurum_id' => $kurum_id];
        if ($sube_id > 0) {
            $params['sube_id'] = $sube_id;
        }
        $stmt->execute($params);
        $iptaller = $stmt->fetchAll();
    } catch (PDOException $e) {
        $hata_mesaji = 'Bekleyen iptaller listelenemedi.';
        error_log('Iptal listesi hata: ' . $e->getMessage());
    }
} else {
    $hata_mesaji = 'Kurum bilgisi bulunamadı.';
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
                                    <h3>Bekleyen İptaller</h3>
                                </div>
                            </div>
                        </div>
                        <ul class="navbar-nav flex-row ms-auto breadcrumb-action-dropdown">
                            <li class="nav-item more-dropdown">
                                <a class="btn" href="modules/dashboard.php">Dashboard'a Dön</a>
                            </li>
                        </ul>
                    </header>
                </div>
            </div>

            <div class="row layout-top-spacing">
                <div class="col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <?php if ($hata_mesaji !== '') { ?>
                                <div class="alert alert-danger"><?php echo $hata_mesaji; ?></div>
                            <?php } ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Öğrenci</th>
                                            <th>Veli</th>
                                            <th>Şube</th>
                                            <th>Grup</th>
                                            <th>Seans</th>
                                            <th>İşlem Tarihi</th>
                                            <th>Durum</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($iptaller)) { ?>
                                        <tr>
                                            <td colspan="8">Bekleyen iptal bulunamadı.</td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($iptaller as $iptal) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($iptal['ogrenci_adi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($iptal['veli_adi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($iptal['sube_adi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($iptal['grup_adi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($iptal['seans_baslangic'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($iptal['islem_tarihi'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($iptal['durum'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-success iptal-onayla" data-id="<?php echo (int) $iptal['id']; ?>">Onayla & İade</button>
                                                    <button class="btn btn-sm btn-outline-danger iptal-reddet" data-id="<?php echo (int) $iptal['id']; ?>">Reddet</button>
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
    $('.iptal-onayla').on('click', function () {
        var id = $(this).data('id');
        if (!confirm('İptali onaylayıp hak iadesi yapmak istiyor musunuz?')) {
            return;
        }
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: { islem: 'iptal_onayla', rezervasyon_id: id },
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'İptal onaylanamadı.');
                }
            }
        });
    });

    $('.iptal-reddet').on('click', function () {
        var id = $(this).data('id');
        if (!confirm('İptal talebini reddetmek istiyor musunuz?')) {
            return;
        }
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: { islem: 'iptal_reddet', rezervasyon_id: id },
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'İptal reddedilemedi.');
                }
            }
        });
    });
});
</script>

<?php require_once(__DIR__ . "/../../theme/footer.php"); ?>
