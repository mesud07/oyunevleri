<?php
require_once(__DIR__ . "/../includes/config.php");
require_once(__DIR__ . "/../includes/functions.php");
require_once(__DIR__ . "/../theme/header.php");

$kurum_id = (int) ($_SESSION['kurum_id'] ?? 0);
$sube_id = (int) ($_SESSION['sube_id'] ?? 0);
$filter = $_GET['filter'] ?? 'today';
$filter = in_array($filter, ['today', 'week'], true) ? $filter : 'today';

$start = new DateTime('today');
$end = new DateTime('today 23:59:59');
if ($filter === 'week') {
    $start = new DateTime('monday this week');
    $end = new DateTime('sunday this week 23:59:59');
}

$start_str = $start->format('Y-m-d H:i:s');
$end_str = $end->format('Y-m-d H:i:s');

$gunluk_cocuk = 0;
$doluluk_oran = 0;
$aylik_ciro = 0;
$bekleyen_iptal = 0;
$hak_yandi = 0;
$kontenjan_toplam = 0;
$rezervasyon_sayisi = 0;
$son_rezervasyonlar = [];
$seans_doluluk = [];
$davet_link = '';
$davet_kodu = '';
$davet_kurum = '';

if (!empty($db) && $kurum_id > 0) {
    $sube_filter = $sube_id > 0 ? " AND g.sube_id = :sube_id" : "";
    $params = [
        'kurum_id' => $kurum_id,
        'start' => $start_str,
        'end' => $end_str,
    ];
    if ($sube_id > 0) {
        $params['sube_id'] = $sube_id;
    }

    try {
        $sql = "SELECT COALESCE(SUM(s.kontenjan), 0) AS toplam
                FROM seanslar s
                INNER JOIN oyun_gruplari g ON g.id = s.grup_id
                WHERE s.kurum_id = :kurum_id
                  AND s.seans_baslangic BETWEEN :start AND :end" . $sube_filter;
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $kontenjan_toplam = (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Dashboard kontenjan hata: ' . $e->getMessage());
    }

    try {
        $sql = "SELECT COUNT(*) AS toplam
                FROM rezervasyonlar r
                INNER JOIN seanslar s ON s.id = r.seans_id
                INNER JOIN oyun_gruplari g ON g.id = s.grup_id
                WHERE r.kurum_id = :kurum_id
                  AND r.durum = 'onayli'
                  AND s.seans_baslangic BETWEEN :start AND :end" . $sube_filter;
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rezervasyon_sayisi = (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Dashboard rezervasyon hata: ' . $e->getMessage());
    }

    try {
        $sql = "SELECT COUNT(DISTINCT r.ogrenci_id) AS toplam
                FROM rezervasyonlar r
                INNER JOIN seanslar s ON s.id = r.seans_id
                INNER JOIN oyun_gruplari g ON g.id = s.grup_id
                WHERE r.kurum_id = :kurum_id
                  AND r.durum = 'onayli'
                  AND s.seans_baslangic BETWEEN :start AND :end" . $sube_filter;
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $gunluk_cocuk = (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Dashboard cocuk sayisi hata: ' . $e->getMessage());
    }

    try {
                $sql = "SELECT COUNT(*) AS toplam
                FROM rezervasyonlar r
                INNER JOIN seanslar s ON s.id = r.seans_id
                INNER JOIN oyun_gruplari g ON g.id = s.grup_id
                WHERE r.kurum_id = :kurum_id
                  AND r.durum = 'iptal'
                  AND r.iptal_onay = 0
                  AND r.islem_tarihi BETWEEN :start AND :end" . $sube_filter;
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $bekleyen_iptal = (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Dashboard iptal hata: ' . $e->getMessage());
    }

    try {
        $sql = "SELECT COUNT(*) AS toplam
                FROM rezervasyonlar r
                INNER JOIN seanslar s ON s.id = r.seans_id
                INNER JOIN oyun_gruplari g ON g.id = s.grup_id
                WHERE r.kurum_id = :kurum_id
                  AND r.durum = 'hak_yandi'
                  AND r.islem_tarihi BETWEEN :start AND :end" . $sube_filter;
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $hak_yandi = (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Dashboard hak yandi hata: ' . $e->getMessage());
    }

    try {
        $ay_bas = date('Y-m-01 00:00:00');
        $ay_bit = date('Y-m-t 23:59:59');
        $sql = "SELECT COALESCE(SUM(tutar), 0) AS toplam
                FROM kasa_hareketleri
                WHERE kurum_id = :kurum_id
                  AND islem_tipi = 'gelir'
                  AND tarih BETWEEN :ay_bas AND :ay_bit";
        if ($sube_id > 0) {
            $sql .= " AND sube_id = :sube_id";
        }
        $stmt = $db->prepare($sql);
        $params_ciro = [
            'kurum_id' => $kurum_id,
            'ay_bas' => $ay_bas,
            'ay_bit' => $ay_bit,
        ];
        if ($sube_id > 0) {
            $params_ciro['sube_id'] = $sube_id;
        }
        $stmt->execute($params_ciro);
        $aylik_ciro = (float) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Dashboard ciro hata: ' . $e->getMessage());
    }

    try {
        $sql = "SELECT r.id, r.durum, r.islem_tarihi, s.seans_baslangic, g.grup_adi,
                    o.ad_soyad AS ogrenci_adi, v.ad_soyad AS veli_adi
                FROM rezervasyonlar r
                INNER JOIN seanslar s ON s.id = r.seans_id
                INNER JOIN oyun_gruplari g ON g.id = s.grup_id
                INNER JOIN ogrenciler o ON o.id = r.ogrenci_id
                INNER JOIN veliler v ON v.id = o.veli_id
                WHERE r.kurum_id = :kurum_id" . $sube_filter . "
                ORDER BY r.islem_tarihi DESC
                LIMIT 8";
        $stmt = $db->prepare($sql);
        $params_list = ['kurum_id' => $kurum_id];
        if ($sube_id > 0) {
            $params_list['sube_id'] = $sube_id;
        }
        $stmt->execute($params_list);
        $son_rezervasyonlar = $stmt->fetchAll();
    } catch (PDOException $e) {
        $son_rezervasyonlar = [];
        error_log('Dashboard son rezervasyon hata: ' . $e->getMessage());
    }

    try {
        $sql = "SELECT s.id, s.seans_baslangic, s.kontenjan, g.grup_adi,
                    SUM(CASE WHEN r.durum = 'onayli' THEN 1 ELSE 0 END) AS dolu
                FROM seanslar s
                INNER JOIN oyun_gruplari g ON g.id = s.grup_id
                LEFT JOIN rezervasyonlar r ON r.seans_id = s.id AND r.kurum_id = :kurum_id
                WHERE s.kurum_id = :kurum_id
                  AND s.seans_baslangic >= NOW()" . $sube_filter . "
                GROUP BY s.id
                ORDER BY s.seans_baslangic ASC
                LIMIT 8";
        $stmt = $db->prepare($sql);
        $params_list = ['kurum_id' => $kurum_id];
        if ($sube_id > 0) {
            $params_list['sube_id'] = $sube_id;
        }
        $stmt->execute($params_list);
        $seans_doluluk = $stmt->fetchAll();
    } catch (PDOException $e) {
        $seans_doluluk = [];
        error_log('Dashboard seans doluluk hata: ' . $e->getMessage());
    }
}

$davet_kodu = '';
if (!empty($db_master) && $kurum_id > 0) {
    try {
        $stmt = $db_master->prepare("SELECT kurum_kodu, kurum_adi FROM kurumlar WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $kurum_id]);
        $row = $stmt->fetch();
        if ($row) {
            $davet_kodu = (string) ($row['kurum_kodu'] ?? '');
            $davet_kurum = (string) ($row['kurum_adi'] ?? '');
        }
    } catch (PDOException $e) {
        error_log('Dashboard davet hata: ' . $e->getMessage());
    }
}

if ($davet_kodu !== '' && !empty($_SERVER['HTTP_HOST'])) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base_path = rtrim(str_replace('/modules', '', dirname($_SERVER['SCRIPT_NAME'])), '/');
    $davet_link = $scheme . '://' . $_SERVER['HTTP_HOST'] . $base_path . '/davet.php?code=' . urlencode($davet_kodu);
}

$doluluk_oran = $kontenjan_toplam > 0 ? round(($rezervasyon_sayisi / $kontenjan_toplam) * 100, 1) : 0;
$filter_label = $filter === 'week' ? 'Bu Hafta' : 'Bugün';
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
                                    <h3>Dashboard</h3>
                                </div>
                            </div>
                        </div>
                        <ul class="navbar-nav flex-row ms-auto breadcrumb-action-dropdown">
                            <li class="nav-item more-dropdown">
                                <div class="dropdown custom-dropdown-icon">
                                    <a class="dropdown-toggle btn" href="#" role="button" id="filterDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <span id="filter-label"><?php echo $filter_label; ?></span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-down custom-dropdown-arrow"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="filterDropdown">
                                        <a class="dropdown-item dashboard-filter" data-filter="today" href="#">Bugün</a>
                                        <a class="dropdown-item dashboard-filter" data-filter="week" href="#">Bu Hafta</a>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </header>
                </div>
            </div>

            <?php if ($davet_link !== '') { ?>
            <div class="row layout-top-spacing">
                <div class="col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="w-info">
                                    <h6 class="value">Kurum Davet Linki</h6>
                                    <?php if ($davet_kurum !== '') { ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($davet_kurum, ENT_QUOTES, 'UTF-8'); ?></small>
                                    <?php } ?>
                                </div>
                                <button class="btn btn-outline-primary" id="davetKopyalaBtn">Kopyala</button>
                            </div>
                            <div class="mt-3">
                                <input type="text" class="form-control" id="davetLink" value="<?php echo htmlspecialchars($davet_link, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <small class="text-muted">Bu linki velilerle paylaşın. Giriş yapan veli link üzerinden kuruma katılır, giriş yapmayan kayıt ekranına yönlendirilir.</small>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>

            <div class="row layout-top-spacing">
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="w-info">
                                <h6 class="value">Günlük Çocuk Sayısı</h6>
                                <p class="value" id="metric-cocuk"><?php echo number_format($gunluk_cocuk, 0, ",", "."); ?></p>
                                <small class="text-muted">Doluluk: <span id="metric-doluluk"><?php echo $doluluk_oran; ?></span>%</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="w-info">
                                <h6 class="value">Aylık Ciro</h6>
                                <p class="value" id="metric-ciro"><?php echo number_format($aylik_ciro, 2, ",", "."); ?> TL</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="w-info">
                                <h6 class="value">Bekleyen İptaller</h6>
                                <p class="value">
                                    <a href="modules/rezervasyon/iptal_listesi.php" style="color:inherit;text-decoration:none;">
                                        <span id="metric-iptal"><?php echo number_format($bekleyen_iptal, 0, ",", "."); ?></span>
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="w-info">
                                <h6 class="value">48 Saat Kuralı</h6>
                                <p class="value" id="metric-hak"><?php echo number_format($hak_yandi, 0, ",", "."); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row layout-top-spacing">
                <div class="col-xl-7 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="w-info">
                                <h6 class="value">Son Rezervasyonlar</h6>
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Öğrenci</th>
                                            <th>Grup</th>
                                            <th>Seans</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($son_rezervasyonlar)) { ?>
                                        <tr>
                                            <td colspan="4">Kayıt bulunamadı.</td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($son_rezervasyonlar as $rez) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($rez['ogrenci_adi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($rez['grup_adi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($rez['seans_baslangic'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($rez['durum'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-5 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="w-info">
                                <h6 class="value">Seans Doluluk</h6>
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Seans</th>
                                            <th>Dolu</th>
                                            <th>Kont.</th>
                                            <th>%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($seans_doluluk)) { ?>
                                        <tr>
                                            <td colspan="4">Seans bulunamadı.</td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($seans_doluluk as $seans) {
                                            $dolu = (int) $seans['dolu'];
                                            $kont = (int) $seans['kontenjan'];
                                            $oran = $kont > 0 ? round(($dolu / $kont) * 100) : 0;
                                            ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($seans['grup_adi'], ENT_QUOTES, 'UTF-8'); ?><br>
                                                    <small><?php echo htmlspecialchars(date('d.m H:i', strtotime($seans['seans_baslangic'])), ENT_QUOTES, 'UTF-8'); ?></small>
                                                </td>
                                                <td><?php echo $dolu; ?></td>
                                                <td><?php echo $kont; ?></td>
                                                <td><?php echo $oran; ?>%</td>
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
    $(function () {
        var $copyBtn = $('#davetKopyalaBtn');
        var $linkInput = $('#davetLink');
        if ($copyBtn.length && $linkInput.length) {
            $copyBtn.on('click', function () {
                var text = $linkInput.val() || '';
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function () {
                        $copyBtn.text('Kopyalandı');
                        setTimeout(function () { $copyBtn.text('Kopyala'); }, 1500);
                    }).catch(function () {
                        $linkInput.trigger('select');
                        document.execCommand('copy');
                    });
                } else {
                    $linkInput.trigger('select');
                    document.execCommand('copy');
                }
            });
        }
        $('.dashboard-filter').on('click', function (e) {
            e.preventDefault();
            var filter = $(this).data('filter');
            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                data: { islem: 'dashboard_ozet', filter: filter },
                success: function (res) {
                    if (!res || res.durum !== 'ok') {
                        return;
                    }
                    $('#filter-label').text(res.filter_label);
                    $('#metric-cocuk').text(res.gunluk_cocuk);
                    $('#metric-doluluk').text(res.doluluk_oran);
                    $('#metric-ciro').text(res.aylik_ciro);
                    $('#metric-iptal').text(res.bekleyen_iptal);
                    $('#metric-hak').text(res.hak_yandi);
                }
            });
        });
    });
</script>

<?php require_once(__DIR__ . "/../theme/footer.php"); ?>
