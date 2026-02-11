<?php
require_once(__DIR__ . '/../../includes/config.php');
require_once(__DIR__ . '/../../includes/functions.php');

giris_zorunlu();
require_once(__DIR__ . '/../../theme/header.php');

$kurum_id = aktif_kurum_id();
$ay = trim($_GET['ay'] ?? '');
$secili_alan = (int) ($_GET['alan_id'] ?? 0);
if ($ay === '' || !preg_match('/^\d{4}-\d{2}$/', $ay)) {
    $ay = date('Y-m');
}

$ay_bas = $ay . '-01';
$ay_bit = date('Y-m-t', strtotime($ay_bas));
$prev_ay = date('Y-m', strtotime($ay_bas . ' -1 month'));
$next_ay = date('Y-m', strtotime($ay_bas . ' +1 month'));
$bugun_ay = date('Y-m');

$ay_isimleri = [
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
$ay_etiket = ($ay_isimleri[date('m', strtotime($ay_bas))] ?? date('F', strtotime($ay_bas))) . ' ' . date('Y', strtotime($ay_bas));

$alan_list = [];
$seanslar = [];
$gunluk = [];

if (!empty($db) && $kurum_id > 0) {
    $stmt = $db->prepare("SELECT id, alan_adi FROM kurum_alanlari WHERE kurum_id = :kurum_id ORDER BY alan_adi");
    $stmt->execute(['kurum_id' => $kurum_id]);
    foreach ($stmt->fetchAll() as $alan) {
        $alan_list[(int) $alan['id']] = $alan['alan_adi'];
    }

    $alan_sql = '';
    if ($secili_alan > 0) {
        $alan_sql = ' AND g.alan_id = :alan_id ';
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
          $alan_sql
        ORDER BY s.seans_baslangic ASC");
    $params = [
        'kurum_id' => $kurum_id,
        'tarih_bas' => $ay_bas . ' 00:00:00',
        'tarih_bit' => $ay_bit . ' 23:59:59',
    ];
    if ($secili_alan > 0) {
        $params['alan_id'] = $secili_alan;
    }
    $stmt->execute($params);
    $seanslar = $stmt->fetchAll();

    $alan_tanimsiz = false;
    foreach ($seanslar as $seans) {
        $alan_id = (int) ($seans['alan_id'] ?? 0);
        if ($alan_id === 0) {
            $alan_tanimsiz = true;
        }
        $gun_key = date('Y-m-d', strtotime($seans['seans_baslangic']));
        if (!isset($gunluk[$gun_key])) {
            $gunluk[$gun_key] = [];
        }
        $gunluk[$gun_key][] = $seans;
    }

    if ($alan_tanimsiz && !isset($alan_list[0])) {
        $alan_list[0] = 'Alan Tanımsız';
    }
}

$hafta_gunleri = ['Paz', 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt'];
$baslangic_ts = strtotime($ay_bas);
$baslangic_gun = (int) date('w', $baslangic_ts); // 0: Pazar
$grid_start = strtotime($ay_bas . ' -' . $baslangic_gun . ' days');
$grid_days = [];
for ($i = 0; $i < 42; $i++) {
    $ts = strtotime('+' . $i . ' days', $grid_start);
    $date = date('Y-m-d', $ts);
    $grid_days[] = [
        'date' => $date,
        'day' => (int) date('j', $ts),
        'is_current' => date('Y-m', $ts) === $ay,
        'is_today' => $date === date('Y-m-d'),
    ];
}
?>

<style>
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
                                    <h3>Kurum Takvimi</h3>
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
                            <div class="calendar-toolbar">
                                <div class="calendar-nav">
                                    <a class="btn btn-light" href="modules/kurum/kurum_takvimi.php?ay=<?php echo htmlspecialchars($bugun_ay, ENT_QUOTES, 'UTF-8'); ?>&alan_id=<?php echo (int) $secili_alan; ?>">Bugün</a>
                                    <a class="btn btn-outline-secondary" href="modules/kurum/kurum_takvimi.php?ay=<?php echo htmlspecialchars($prev_ay, ENT_QUOTES, 'UTF-8'); ?>&alan_id=<?php echo (int) $secili_alan; ?>">‹</a>
                                    <a class="btn btn-outline-secondary" href="modules/kurum/kurum_takvimi.php?ay=<?php echo htmlspecialchars($next_ay, ENT_QUOTES, 'UTF-8'); ?>&alan_id=<?php echo (int) $secili_alan; ?>">›</a>
                                    <div class="calendar-title"><?php echo htmlspecialchars($ay_etiket, ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                                <form class="calendar-filters" method="get">
                                    <div>
                                        <label class="form-label">Ay</label>
                                        <input type="month" class="form-control" name="ay" value="<?php echo htmlspecialchars($ay, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div>
                                        <label class="form-label">Alan</label>
                                        <select class="form-select" name="alan_id">
                                            <option value="0">Tüm Alanlar</option>
                                            <?php foreach ($alan_list as $alan_id => $alan_adi) { ?>
                                                <option value="<?php echo (int) $alan_id; ?>" <?php echo $secili_alan === (int) $alan_id ? 'selected' : ''; ?>>
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
                                    <?php foreach ($hafta_gunleri as $gun_adi) { ?>
                                        <div><?php echo htmlspecialchars($gun_adi, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php } ?>
                                </div>
                                <div class="calendar-days">
                                    <?php foreach ($grid_days as $day) { ?>
                                        <?php
                                            $classes = 'calendar-day-cell';
                                            if (!$day['is_current']) {
                                                $classes .= ' is-outside';
                                            }
                                            if ($day['is_today']) {
                                                $classes .= ' is-today';
                                            }
                                            $kayitlar = $gunluk[$day['date']] ?? [];
                                        ?>
                                        <div class="<?php echo $classes; ?>">
                                            <div class="calendar-day-number"><?php echo (int) $day['day']; ?></div>
                                            <div class="calendar-events">
                                                <?php if (!empty($kayitlar)) { ?>
                                                    <?php foreach ($kayitlar as $seans) { ?>
                                                        <?php
                                                            $alan_id = (int) ($seans['alan_id'] ?? 0);
                                                            $alan_adi = $alan_list[$alan_id] ?? 'Alan Tanımsız';
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
window.addEventListener('load', function () {
    if (!window.jQuery) {
        return;
    }
    var $ = window.jQuery;

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

<?php require_once(__DIR__ . '/../../theme/footer.php'); ?>
