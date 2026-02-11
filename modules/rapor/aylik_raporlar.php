<?php
require_once(__DIR__ . '/../../includes/config.php');
require_once(__DIR__ . '/../../includes/functions.php');

giris_zorunlu();
require_once(__DIR__ . '/../../theme/header.php');

$kurum_id = aktif_kurum_id();
$ay = $_GET['ay'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $ay)) {
    $ay = date('Y-m');
}

$start = $ay . '-01 00:00:00';
$end = date('Y-m-t 23:59:59', strtotime($start));

$ciro = 0;
$kontenjan_toplam = 0;
$rezervasyon_sayisi = 0;
$iptal_sayisi = 0;
$toplam_rez = 0;

if (!empty($db) && $kurum_id > 0) {
    $stmt = $db->prepare("SELECT COALESCE(SUM(tutar), 0) FROM kasa_hareketleri
        WHERE kurum_id = :kurum_id AND islem_tipi = 'gelir' AND tarih BETWEEN :start AND :end");
    $stmt->execute(['kurum_id' => $kurum_id, 'start' => $start, 'end' => $end]);
    $ciro = (float) $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COALESCE(SUM(s.kontenjan), 0)
        FROM seanslar s
        WHERE s.kurum_id = :kurum_id AND s.seans_baslangic BETWEEN :start AND :end");
    $stmt->execute(['kurum_id' => $kurum_id, 'start' => $start, 'end' => $end]);
    $kontenjan_toplam = (int) $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*)
        FROM rezervasyonlar r
        INNER JOIN seanslar s ON s.id = r.seans_id
        WHERE r.kurum_id = :kurum_id AND r.durum = 'onayli' AND s.seans_baslangic BETWEEN :start AND :end");
    $stmt->execute(['kurum_id' => $kurum_id, 'start' => $start, 'end' => $end]);
    $rezervasyon_sayisi = (int) $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*)
        FROM rezervasyonlar r
        WHERE r.kurum_id = :kurum_id AND r.islem_tarihi BETWEEN :start AND :end");
    $stmt->execute(['kurum_id' => $kurum_id, 'start' => $start, 'end' => $end]);
    $toplam_rez = (int) $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*)
        FROM rezervasyonlar r
        WHERE r.kurum_id = :kurum_id AND r.islem_tarihi BETWEEN :start AND :end
          AND r.durum IN ('iptal','hak_yandi')");
    $stmt->execute(['kurum_id' => $kurum_id, 'start' => $start, 'end' => $end]);
    $iptal_sayisi = (int) $stmt->fetchColumn();
}

$doluluk_oran = $kontenjan_toplam > 0 ? round(($rezervasyon_sayisi / $kontenjan_toplam) * 100, 1) : 0;
$iptal_oran = $toplam_rez > 0 ? round(($iptal_sayisi / $toplam_rez) * 100, 1) : 0;
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
                                    <h3>Aylık Raporlar</h3>
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
                            <form class="d-flex align-items-center gap-2" method="get">
                                <label class="form-label mb-0">Ay Seç</label>
                                <input type="month" class="form-control" name="ay" value="<?php echo htmlspecialchars($ay, ENT_QUOTES, 'UTF-8'); ?>" style="max-width:180px;">
                                <button class="btn btn-primary" type="submit">Uygula</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row layout-top-spacing">
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="w-info">
                                <h6 class="value">Aylık Ciro</h6>
                                <p class="value"><?php echo number_format($ciro, 2, ',', '.'); ?> TL</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="w-info">
                                <h6 class="value">Doluluk Oranı</h6>
                                <p class="value"><?php echo $doluluk_oran; ?>%</p>
                                <small class="text-muted"><?php echo $rezervasyon_sayisi; ?> / <?php echo $kontenjan_toplam; ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="w-info">
                                <h6 class="value">İptal Oranı</h6>
                                <p class="value"><?php echo $iptal_oran; ?>%</p>
                                <small class="text-muted"><?php echo $iptal_sayisi; ?> / <?php echo $toplam_rez; ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once(__DIR__ . '/../../theme/footer.php'); ?>
