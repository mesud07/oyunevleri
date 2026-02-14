<?php
require_once(__DIR__ . "/../includes/config.php");
require_once(__DIR__ . "/../includes/functions.php");
require_once(__DIR__ . "/../theme/header.php");

$kurum_id = (int) ($_SESSION['kurum_id'] ?? 0);
$sube_id = (int) ($_SESSION['sube_id'] ?? 0);
$filter = 'today';

$calendar_ay = trim($_GET['ay'] ?? '');
$calendar_alan_id = (int) ($_GET['alan_id'] ?? 0);
if ($calendar_ay === '' || !preg_match('/^\d{4}-\d{2}$/', $calendar_ay)) {
    $calendar_ay = date('Y-m');
}
$calendar_ay_bas = $calendar_ay . '-01';
$calendar_ay_bit = date('Y-m-t', strtotime($calendar_ay_bas));
$calendar_prev_ay = date('Y-m', strtotime($calendar_ay_bas . ' -1 month'));
$calendar_next_ay = date('Y-m', strtotime($calendar_ay_bas . ' +1 month'));
$calendar_bugun_ay = date('Y-m');
$calendar_ay_isimleri = [
    '01' => 'Ocak',
    '02' => 'Şubat',
    '03' => 'Mart',
    '04' => 'Nisan',
    '05' => 'Mayıs',
    '06' => 'Haziran',
    '07' => 'Temmuz',
    '08' => 'Ağustos',
    '09' => 'Eylül',
    '10' => 'Ekim',
    '11' => 'Kasım',
    '12' => 'Aralık',
];
$calendar_ay_etiket = ($calendar_ay_isimleri[date('m', strtotime($calendar_ay_bas))] ?? date('F', strtotime($calendar_ay_bas))) . ' ' . date('Y', strtotime($calendar_ay_bas));
$calendar_alan_list = [];
$calendar_seanslar = [];
$calendar_gunluk = [];
$calendar_grid_days = [];

$start = new DateTime('today');
$end = new DateTime('today 23:59:59');
if ($filter === 'week') {
    $start = new DateTime('monday this week');
    $end = new DateTime('sunday this week 23:59:59');
} elseif ($filter === 'month') {
    $start = new DateTime('first day of this month 00:00:00');
    $end = new DateTime('last day of this month 23:59:59');
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

    try {
        $stmt = $db->prepare("SELECT id, alan_adi FROM kurum_alanlari WHERE kurum_id = :kurum_id ORDER BY alan_adi");
        $stmt->execute(['kurum_id' => $kurum_id]);
        foreach ($stmt->fetchAll() as $alan) {
            $calendar_alan_list[(int) $alan['id']] = $alan['alan_adi'];
        }

        $calendar_alan_sql = '';
        $calendar_params = [
            'kurum_id' => $kurum_id,
            'tarih_bas' => $calendar_ay_bas . ' 00:00:00',
            'tarih_bit' => $calendar_ay_bit . ' 23:59:59',
        ];
        if ($calendar_alan_id > 0) {
            $calendar_alan_sql = ' AND g.alan_id = :alan_id ';
            $calendar_params['alan_id'] = $calendar_alan_id;
        }
        if ($sube_id > 0) {
            $calendar_alan_sql .= ' AND g.sube_id = :sube_id ';
            $calendar_params['sube_id'] = $sube_id;
        }

        $stmt = $db->prepare("SELECT s.id, s.seans_baslangic, s.seans_bitis, s.kontenjan, s.durum,
                g.grup_adi, g.alan_id,
                sb.sube_adi,
                (SELECT COUNT(*) FROM rezervasyonlar r WHERE r.seans_id = s.id AND r.kurum_id = :kurum_id AND r.durum = 'onayli') AS dolu
            FROM seanslar s
            INNER JOIN oyun_gruplari g ON g.id = s.grup_id
            LEFT JOIN subeler sb ON sb.id = g.sube_id
            WHERE s.kurum_id = :kurum_id
              AND s.seans_baslangic BETWEEN :tarih_bas AND :tarih_bit
              $calendar_alan_sql
            ORDER BY s.seans_baslangic ASC");
        $stmt->execute($calendar_params);
        $calendar_seanslar = $stmt->fetchAll();

        $calendar_alan_tanimsiz = false;
        foreach ($calendar_seanslar as $seans) {
            $alan_id = (int) ($seans['alan_id'] ?? 0);
            if ($alan_id === 0) {
                $calendar_alan_tanimsiz = true;
            }
            $gun_key = date('Y-m-d', strtotime($seans['seans_baslangic']));
            if (!isset($calendar_gunluk[$gun_key])) {
                $calendar_gunluk[$gun_key] = [];
            }
            $calendar_gunluk[$gun_key][] = $seans;
        }

        if ($calendar_alan_tanimsiz && !isset($calendar_alan_list[0])) {
            $calendar_alan_list[0] = 'Alan Tanımsız';
        }
    } catch (PDOException $e) {
        error_log('Dashboard takvim hata: ' . $e->getMessage());
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
$filter_label = 'Bugün';

$calendar_hafta_gunleri = ['Paz', 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt'];
$calendar_baslangic_ts = strtotime($calendar_ay_bas);
$calendar_baslangic_gun = (int) date('w', $calendar_baslangic_ts);
$calendar_grid_start = strtotime($calendar_ay_bas . ' -' . $calendar_baslangic_gun . ' days');
for ($i = 0; $i < 42; $i++) {
    $ts = strtotime('+' . $i . ' days', $calendar_grid_start);
    $date = date('Y-m-d', $ts);
    $calendar_grid_days[] = [
        'date' => $date,
        'day' => (int) date('j', $ts),
        'is_current' => date('Y-m', $ts) === $calendar_ay,
        'is_today' => $date === date('Y-m-d'),
    ];
}
?>

<style>
.circle-chart {
    --p: 0;
    --size: 52px;
    --color: #3b82f6;
    width: var(--size);
    height: var(--size);
    border-radius: 50%;
    background: conic-gradient(var(--color) calc(var(--p) * 1%), #e5e7eb 0);
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.circle-chart span {
    width: calc(var(--size) - 10px);
    height: calc(var(--size) - 10px);
    border-radius: 50%;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 600;
    color: #111827;
    box-shadow: inset 0 0 0 1px #e5e7eb;
}
.calendar-toolbar {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 12px;
    margin-top: 8px;
}
.calendar-toolbar .calendar-nav {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.calendar-title {
    font-size: 18px;
    font-weight: 600;
    margin-left: 6px;
}
.calendar-filters {
    display: flex;
    gap: 12px;
    align-items: flex-end;
    flex-wrap: wrap;
}
.calendar-grid {
    margin-top: 18px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
}
.calendar-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: #f5f6f8;
    border-bottom: 1px solid #e5e7eb;
}
.calendar-weekdays div {
    padding: 10px;
    font-weight: 600;
    text-align: center;
    color: #6b7280;
}
.calendar-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    grid-auto-rows: 140px;
}
.calendar-day-cell {
    border-right: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
    padding: 8px 10px;
    position: relative;
    background: #fff;
}
.calendar-day-cell:nth-child(7n) {
    border-right: none;
}
.calendar-day-cell.is-outside {
    background: #fafafa;
    color: #9ca3af;
}
.calendar-day-cell.is-today {
    box-shadow: inset 0 0 0 2px #3b82f6;
}
.calendar-day-number {
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 6px;
}
.calendar-events {
    display: flex;
    flex-direction: column;
    gap: 6px;
    max-height: 100px;
    overflow: hidden;
}
.calendar-event {
    font-size: 11px;
    line-height: 1.2;
    padding: 4px 6px;
    border-radius: 6px;
    background: #e7f0ff;
    color: #1f2937;
    border-left: 3px solid #3b82f6;
    cursor: pointer;
}
.calendar-event .event-time {
    font-weight: 600;
    display: inline-block;
    margin-right: 4px;
}
.calendar-event .event-sub {
    display: block;
    color: #6b7280;
}
.calendar-event.color-1 { background: #e8f5ff; border-left-color: #3b82f6; }
.calendar-event.color-2 { background: #e7f9f0; border-left-color: #10b981; }
.calendar-event.color-3 { background: #fff7e6; border-left-color: #f59e0b; }
.calendar-event.color-4 { background: #fde8ef; border-left-color: #ec4899; }
.calendar-event.color-5 { background: #f3e8ff; border-left-color: #8b5cf6; }
@media (max-width: 992px) {
    .calendar-days { grid-auto-rows: 120px; }
}
</style>

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
                            <li class="nav-item">
                                <div class="btn btn-light" aria-disabled="true"><?php echo $filter_label; ?></div>
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

            <div class="row layout-top-spacing" id="kurum-takvimi">
                <div class="col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="w-info">
                                <h6 class="value">Kurum Takvimi</h6>
                            </div>
                            <div class="calendar-toolbar">
                                <div class="calendar-nav">
                                    <a class="btn btn-light" href="modules/dashboard.php?<?php echo htmlspecialchars(http_build_query(['ay' => $calendar_bugun_ay, 'alan_id' => $calendar_alan_id, 'filter' => $filter]), ENT_QUOTES, 'UTF-8'); ?>">Bugün</a>
                                    <a class="btn btn-outline-secondary" href="modules/dashboard.php?<?php echo htmlspecialchars(http_build_query(['ay' => $calendar_prev_ay, 'alan_id' => $calendar_alan_id, 'filter' => $filter]), ENT_QUOTES, 'UTF-8'); ?>">‹</a>
                                    <a class="btn btn-outline-secondary" href="modules/dashboard.php?<?php echo htmlspecialchars(http_build_query(['ay' => $calendar_next_ay, 'alan_id' => $calendar_alan_id, 'filter' => $filter]), ENT_QUOTES, 'UTF-8'); ?>">›</a>
                                    <div class="calendar-title"><?php echo htmlspecialchars($calendar_ay_etiket, ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                                <form class="calendar-filters" method="get">
                                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter, ENT_QUOTES, 'UTF-8'); ?>">
                                    <div>
                                        <label class="form-label">Ay</label>
                                        <input type="month" class="form-control" name="ay" value="<?php echo htmlspecialchars($calendar_ay, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div>
                                        <label class="form-label">Alan</label>
                                        <select class="form-select" name="alan_id">
                                            <option value="0">Tüm Alanlar</option>
                                            <?php foreach ($calendar_alan_list as $alan_id => $alan_adi) { ?>
                                                <option value="<?php echo (int) $alan_id; ?>" <?php echo $calendar_alan_id === (int) $alan_id ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($alan_adi, ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div>
                                        <button type="submit" class="btn btn-primary" style="margin-top: 28px;">Uygula</button>
                                    </div>
                                </form>
                            </div>

                            <div class="calendar-grid">
                                <div class="calendar-weekdays">
                                    <?php foreach ($calendar_hafta_gunleri as $gun_adi) { ?>
                                        <div><?php echo htmlspecialchars($gun_adi, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php } ?>
                                </div>
                                <div class="calendar-days">
                                    <?php foreach ($calendar_grid_days as $day) { ?>
                                        <?php
                                            $classes = 'calendar-day-cell';
                                            if (!$day['is_current']) {
                                                $classes .= ' is-outside';
                                            }
                                            if ($day['is_today']) {
                                                $classes .= ' is-today';
                                            }
                                            $kayitlar = $calendar_gunluk[$day['date']] ?? [];
                                        ?>
                                        <div class="<?php echo $classes; ?>">
                                            <div class="calendar-day-number"><?php echo (int) $day['day']; ?></div>
                                            <div class="calendar-events">
                                                <?php if (!empty($kayitlar)) { ?>
                                                    <?php foreach ($kayitlar as $seans) { ?>
                                                        <?php
                                                            $alan_id = (int) ($seans['alan_id'] ?? 0);
                                                            $alan_adi = $calendar_alan_list[$alan_id] ?? 'Alan Tanımsız';
                                                            $color = ($alan_id % 5) + 1;
                                                        ?>
                                                        <div class="calendar-event color-<?php echo (int) $color; ?>"
                                                            data-id="<?php echo (int) $seans['id']; ?>"
                                                            data-grup="<?php echo htmlspecialchars($seans['grup_adi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-alan="<?php echo htmlspecialchars($alan_adi, ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-sube="<?php echo htmlspecialchars($seans['sube_adi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-baslangic="<?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($seans['seans_baslangic'])), ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-bitis="<?php echo htmlspecialchars(date('H:i', strtotime($seans['seans_bitis'])), ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-dolu="<?php echo (int) ($seans['dolu'] ?? 0); ?>"
                                                            data-kontenjan="<?php echo (int) ($seans['kontenjan'] ?? 0); ?>"
                                                            data-durum="<?php echo htmlspecialchars($seans['durum'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>">
                                                            <span class="event-time"><?php echo htmlspecialchars(date('H:i', strtotime($seans['seans_baslangic'])), ENT_QUOTES, 'UTF-8'); ?></span>
                                                            <span class="event-title"><?php echo htmlspecialchars($seans['grup_adi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></span>
                                                            <span class="event-sub"><?php echo htmlspecialchars($alan_adi, ENT_QUOTES, 'UTF-8'); ?></span>
                                                        </div>
                                                    <?php } ?>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

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
                                <h6 class="value">24 Saat Kuralı</h6>
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
                                            if ($kont === 0) {
                                                $circle_color = '#cbd5f5';
                                            } elseif ($oran >= 85) {
                                                $circle_color = '#10b981';
                                            } elseif ($oran >= 60) {
                                                $circle_color = '#f59e0b';
                                            } else {
                                                $circle_color = '#ef4444';
                                            }
                                            ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($seans['grup_adi'], ENT_QUOTES, 'UTF-8'); ?><br>
                                                    <small><?php echo htmlspecialchars(date('d.m H:i', strtotime($seans['seans_baslangic'])), ENT_QUOTES, 'UTF-8'); ?></small>
                                                </td>
                                                <td><?php echo $dolu; ?></td>
                                                <td><?php echo $kont; ?></td>
                                                <td>
                                                    <div class="circle-chart" style="--p: <?php echo (int) $oran; ?>; --color: <?php echo $circle_color; ?>;">
                                                        <span><?php echo $oran; ?>%</span>
                                                    </div>
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

<div class="modal fade" id="seansDetayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Seans Detayı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2"><strong>Grup:</strong> <span id="detay_grup">-</span></div>
                <div class="mb-2"><strong>Alan:</strong> <span id="detay_alan">-</span></div>
                <div class="mb-2"><strong>Şube:</strong> <span id="detay_sube">-</span></div>
                <div class="mb-2"><strong>Seans:</strong> <span id="detay_tarih">-</span></div>
                <div class="mb-2"><strong>Doluluk:</strong> <span id="detay_doluluk">-</span></div>
                <div class="mb-2"><strong>Durum:</strong> <span id="detay_durum">-</span></div>
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
        // Filtre dropdown kaldirildi, sadece Bugun verileri gosteriliyor.
        $('.calendar-event').on('click', function () {
            var $item = $(this);
            $('#detay_grup').text($item.data('grup') || '-');
            $('#detay_alan').text($item.data('alan') || '-');
            $('#detay_sube').text($item.data('sube') || '-');
            $('#detay_tarih').text(($item.data('baslangic') || '-') + ' - ' + ($item.data('bitis') || '-'));
            $('#detay_doluluk').text(($item.data('dolu') || 0) + '/' + ($item.data('kontenjan') || 0));
            $('#detay_durum').text($item.data('durum') || '-');
            $('#seansDetayModal').modal('show');
        });
    });
</script>

<?php require_once(__DIR__ . "/../theme/footer.php"); ?>
