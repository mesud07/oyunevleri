<?php
require_once "includes/config.php";
require_once "includes/functions.php";

$public_nav_active = '';
$veli = $_SESSION['veli'] ?? null;
$veli_kurum_id = (int) ($_SESSION['kurum_id'] ?? 0);

if (empty($_SESSION['veli_giris']) || empty($veli)) {
    header('Location: login.php?next=grup_bilgilerim.php');
    exit;
}

$veli_bakiye = 0;
$veli_hak_bitis = '';
$veli_rezervasyonlar = [];
$veli_ogrenciler = [];
$ogrenci_yas = [];
$ogrenci_rez_sayisi = [];
$kurum_bilgi = null;
$kurum_gruplar = [];
$kurum_seanslar = [];
$takvim = [];
$ay_hucreler = [];
$ay_etiket = '';
$iptal_kural_saat = 48;
$veli_rez_map = [];
$veli_rez_by_student = [];
$aktif_ogrenci_id = 0;
$grup_filtre_aktif = isset($_GET['uygun']) && $_GET['uygun'] === '1';
$gorunen_gruplar = [];

if (!empty($db) && $veli_kurum_id > 0) {
    $stmt = $db->prepare("SELECT bakiye_hak, hak_gecerlilik_bitis FROM veliler WHERE id = :id AND kurum_id = :kurum_id LIMIT 1");
    $stmt->execute(['id' => (int) $veli['id'], 'kurum_id' => $veli_kurum_id]);
    $veli_row = $stmt->fetch();
    $veli_bakiye = (int) ($veli_row['bakiye_hak'] ?? 0);
    $veli_hak_bitis = $veli_row['hak_gecerlilik_bitis'] ?? '';
    $iptal_kural_saat = (int) sistem_ayar_get('iptal_kural_saat', $veli_kurum_id, 48);

    $stmt = $db->prepare("SELECT id, ad_soyad, dogum_tarihi, saglik_notlari
        FROM ogrenciler
        WHERE veli_id = :veli_id AND kurum_id = :kurum_id
        ORDER BY ad_soyad");
    $stmt->execute(['veli_id' => (int) $veli['id'], 'kurum_id' => $veli_kurum_id]);
    $veli_ogrenciler = $stmt->fetchAll();
    $aktif_ogrenci_id = (int) ($veli_ogrenciler[0]['id'] ?? 0);
    foreach ($veli_ogrenciler as $ogr) {
        $yas_text = '-';
        if (!empty($ogr['dogum_tarihi'])) {
            $dt = new DateTime($ogr['dogum_tarihi']);
            $now = new DateTime();
            $diff = $dt->diff($now);
            $yas_text = $diff->y . ' yaş ' . $diff->m . ' ay';
        }
        $ogrenci_yas[$ogr['id']] = $yas_text;
    }

    $sql = "SELECT r.id, r.durum, r.iptal_onay, r.ogrenci_id,
                s.seans_baslangic, s.seans_bitis, g.grup_adi,
                o.ad_soyad AS ogrenci_adi, o.dogum_tarihi
            FROM rezervasyonlar r
            INNER JOIN seanslar s ON s.id = r.seans_id
            INNER JOIN oyun_gruplari g ON g.id = s.grup_id
            INNER JOIN ogrenciler o ON o.id = r.ogrenci_id
            WHERE o.veli_id = :veli_id
              AND r.kurum_id = :kurum_id
            ORDER BY s.seans_baslangic DESC
            LIMIT 50";
    $stmt = $db->prepare($sql);
    $stmt->execute(['veli_id' => (int) $veli['id'], 'kurum_id' => $veli_kurum_id]);
    $veli_rezervasyonlar = $stmt->fetchAll();
    foreach ($veli_rezervasyonlar as $rez) {
        $oid = (int) ($rez['ogrenci_id'] ?? 0);
        if ($oid > 0) {
            $ogrenci_rez_sayisi[$oid] = ($ogrenci_rez_sayisi[$oid] ?? 0) + 1;
        }
    }

    $stmt = $db->prepare("SELECT id, grup_adi, min_ay, max_ay, kapasite, tekrar_tipi, tekrar_gunleri, seans_baslangic_saati, seans_suresi_dk
        FROM oyun_gruplari
        WHERE kurum_id = :kurum_id
        ORDER BY grup_adi");
    $stmt->execute(['kurum_id' => $veli_kurum_id]);
    $kurum_gruplar = $stmt->fetchAll();
    $gorunen_gruplar = $kurum_gruplar;
    if ($grup_filtre_aktif && !empty($veli_ogrenciler)) {
        $filtreli = [];
        foreach ($kurum_gruplar as $grup) {
            $min_ay = (int) ($grup['min_ay'] ?? 0);
            $max_ay = (int) ($grup['max_ay'] ?? 0);
            foreach ($veli_ogrenciler as $ogr) {
                if (empty($ogr['dogum_tarihi'])) {
                    continue;
                }
                $dt = new DateTime($ogr['dogum_tarihi']);
                $now = new DateTime();
                $diff = $dt->diff($now);
                $yas = ($diff->y * 12) + $diff->m;
                $uygun = true;
                if ($min_ay > 0 && $yas < $min_ay) { $uygun = false; }
                if ($max_ay > 0 && $yas > $max_ay) { $uygun = false; }
                if ($uygun) { $filtreli[] = $grup; break; }
            }
        }
        $gorunen_gruplar = $filtreli;
    }

    $ay_bas = new DateTime('first day of this month');
    $ay_bit = new DateTime('last day of this month 23:59:59');
    $stmt = $db->prepare("SELECT r.seans_id, r.ogrenci_id
        FROM rezervasyonlar r
        INNER JOIN seanslar s ON s.id = r.seans_id
        INNER JOIN ogrenciler o ON o.id = r.ogrenci_id
        WHERE o.veli_id = :veli_id
          AND r.kurum_id = :kurum_id
          AND r.durum = 'onayli'
          AND s.seans_baslangic BETWEEN :start AND :end");
    $stmt->execute([
        'veli_id' => (int) $veli['id'],
        'kurum_id' => $veli_kurum_id,
        'start' => $ay_bas->format('Y-m-d 00:00:00'),
        'end' => $ay_bit->format('Y-m-d 23:59:59'),
    ]);
    $rez_rows = $stmt->fetchAll();
    foreach ($rez_rows as $row) {
        $sid = (int) ($row['seans_id'] ?? 0);
        $oid = (int) ($row['ogrenci_id'] ?? 0);
        if ($sid <= 0 || $oid <= 0) {
            continue;
        }
        if (!isset($veli_rez_map[$sid])) {
            $veli_rez_map[$sid] = [];
        }
        $veli_rez_map[$sid][] = $oid;
        if (!isset($veli_rez_by_student[$oid])) {
            $veli_rez_by_student[$oid] = [];
        }
        $veli_rez_by_student[$oid][$sid] = true;
    }

    $stmt = $db->prepare("SELECT s.id, s.seans_baslangic, s.seans_bitis, s.kontenjan, g.grup_adi, g.min_ay, g.max_ay,
            (SELECT COUNT(*) FROM rezervasyonlar r WHERE r.seans_id = s.id AND r.durum = 'onayli') AS dolu
        FROM seanslar s
        INNER JOIN oyun_gruplari g ON g.id = s.grup_id
        WHERE s.kurum_id = :kurum_id
          AND s.seans_baslangic BETWEEN :start AND :end
          AND s.durum = 'aktif'
        ORDER BY s.seans_baslangic ASC");
    $stmt->execute([
        'kurum_id' => $veli_kurum_id,
        'start' => $ay_bas->format('Y-m-d 00:00:00'),
        'end' => $ay_bit->format('Y-m-d 23:59:59'),
    ]);
    $kurum_seanslar = $stmt->fetchAll();
    foreach ($kurum_seanslar as $seans) {
        $k = date('Y-m-d', strtotime($seans['seans_baslangic']));
        if (!isset($takvim[$k])) {
            $takvim[$k] = [];
        }
        $takvim[$k][] = $seans;
    }

    $ay_adlari = [
        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran',
        7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
    ];
    $ay_etiket = ($ay_adlari[(int) $ay_bas->format('n')] ?? $ay_bas->format('F')) . ' ' . $ay_bas->format('Y');
    $start_dow = (int) $ay_bas->format('N');
    for ($i = 1; $i < $start_dow; $i++) {
        $ay_hucreler[] = null;
    }
    $days_in_month = (int) $ay_bas->format('t');
    for ($day = 1; $day <= $days_in_month; $day++) {
        $dt = new DateTime($ay_bas->format('Y-m-') . str_pad((string) $day, 2, '0', STR_PAD_LEFT));
        $ay_hucreler[] = [
            'date' => $dt->format('Y-m-d'),
            'day' => $day,
        ];
    }
    while (count($ay_hucreler) % 7 !== 0) {
        $ay_hucreler[] = null;
    }
}

if (!empty($db_master) && $veli_kurum_id > 0) {
    $stmt = $db_master->prepare("SELECT kurum_adi, kurum_type, sehir, ilce, adres, telefon, eposta, hakkimizda
        FROM kurumlar WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $veli_kurum_id]);
    $kurum_bilgi = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Grup Bilgilerim | Oyunevleri.com</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f9fafc;
            --ink: #1f2937;
            --muted: #6b7280;
            --primary: #ff7a59;
            --accent: #6fd3c5;
            --accent-2: #ffd36e;
            --card: #ffffff;
            --stroke: rgba(31,41,55,0.08);
            --shadow: 0 20px 45px rgba(31,41,55,0.12);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Manrope", system-ui, -apple-system, sans-serif;
            color: var(--ink);
            background: var(--bg);
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px;
        }
        .section {
            padding: 36px 0;
        }
        .section h1,
        .section h2 {
            font-family: "Baloo 2", cursive;
            margin: 0 0 14px;
        }
        .section h1 { font-size: 36px; }
        .section h2 { font-size: 28px; }
        .lead {
            color: var(--muted);
            margin: 0 0 18px;
        }
        .text-muted {
            color: var(--muted);
            font-size: 13px;
        }
        .cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--stroke);
            border-radius: 18px;
            padding: 16px;
            box-shadow: 0 10px 24px rgba(31,41,55,0.08);
        }
        .card h3 { margin: 6px 0; }
        .card .meta {
            color: var(--muted);
            font-size: 14px;
        }
        .tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #f1f5f9;
            margin-bottom: 10px;
        }
        .tag-success { background: #e7f6ed; color: #1f7a46; }
        .tag-warning { background: #fff3cd; color: #856404; }
        .tag-danger { background: #fdecea; color: #b42318; }
        .tag-info { background: #dbeafe; color: #1e40af; }
        .tag-blue { background: #e7f0ff; color: #1f4b99; }
        .tag-muted { background: #f3f4f6; color: #6b7280; }
        .btn {
            border: none;
            padding: 10px 16px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 12px 24px rgba(255, 122, 89, 0.35);
        }
        .btn-outline {
            background: #fff;
            color: var(--ink);
            border: 1px solid var(--stroke);
        }
        .btn-xs {
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 12px;
        }
        .calendar-wrap { margin-top: 20px; }
        .calendar-grid {
            display: grid;
            grid-template-columns: 0.8fr 1.6fr;
            gap: 20px;
        }
        .calendar-card {
            background: var(--card);
            border-radius: 16px;
            padding: 18px;
            box-shadow: var(--shadow);
            border: 1px solid var(--stroke);
        }
        .card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .group-list {
            display: grid;
            gap: 12px;
            margin-top: 12px;
        }
        .group-item {
            background: #fff7f3;
            border: 1px solid rgba(255, 122, 89, 0.15);
            padding: 12px 14px;
            border-radius: 12px;
        }
        .group-title {
            font-weight: 700;
            margin-bottom: 4px;
        }
        .group-meta {
            color: var(--muted);
            font-size: 13px;
        }
        .month-grid,
        .month-head {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 8px;
        }
        .month-head div {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--muted);
            text-align: center;
        }
        .month-cell {
            min-height: 130px;
            border: 1px solid rgba(31,41,55,0.08);
            background: #fff;
            border-radius: 12px;
            padding: 8px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .month-cell.is-empty { background: #f9fafb; border-style: dashed; }
        .month-day { font-weight: 700; font-size: 13px; color: var(--ink); }
        .session-chip {
            display: block;
            padding: 6px 8px;
            border-radius: 10px;
            background: #e7f7ef;
            color: #0f3b37;
            font-size: 12px;
            cursor: pointer;
        }
        .session-chip.full { background: #ffe7e4; color: #8f2b22; }
        .session-chip.past {
            background: #f2f3f7;
            color: #9aa0a6;
            cursor: not-allowed;
        }
        .session-chip.joined { background: #dbeafe; color: #1e40af; }
        .session-chip.past.joined { background: #dbeafe; color: #1e40af; cursor: not-allowed; }
        .session-chip .joined-label { color: #1e40af; }
        .session-chip small {
            display: block;
            color: var(--muted);
            font-size: 11px;
            margin-top: 2px;
        }
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 9999;
        }
        .modal-overlay.is-open { display: flex; }
        .modal-box {
            background: #fff;
            border-radius: 18px;
            padding: 22px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 20px 45px rgba(31,41,55,0.2);
            border: 1px solid rgba(31,41,55,0.08);
        }
        .modal-box h3 {
            margin: 0 0 8px;
            font-family: "Baloo 2", cursive;
        }
        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .field textarea {
            border-radius: 12px;
            border: 1px solid var(--stroke);
            padding: 10px 12px;
            font-size: 14px;
            font-family: inherit;
        }
        .d-flex { display: flex; }
        .gap-2 { gap: 8px; }
        @media (max-width: 980px) {
            .cards { grid-template-columns: 1fr; }
            .calendar-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 600px) {
            .month-grid, .month-head { grid-template-columns: 1fr; }
        }
        <?php require_once("includes/public_header.css.php"); ?>
    </style>
</head>
<body>
    <?php require_once("includes/public_header.php"); ?>

    <section class="section">
        <div class="container">
            <h1>Grup Bilgilerim</h1>
            <p class="lead">Rezervasyonlarınız, öğrencileriniz ve kurum bilgileriniz burada.</p>
            <?php if ($veli_kurum_id <= 0) { ?>
                <div class="card">
                    Henüz bir kurum kaydınız bulunmuyor. Kurum seçimi yaptığınızda grup bilgileri burada görünecek.
                </div>
            <?php } else { ?>
                <p class="lead">
                    Kalan hak: <strong><?php echo $veli_bakiye; ?></strong>
                    <span class="text-muted">• Son kullanım:
                        <strong>
                            <?php echo $veli_hak_bitis ? htmlspecialchars(date('d.m.Y', strtotime($veli_hak_bitis)), ENT_QUOTES, 'UTF-8') : '-'; ?>
                        </strong>
                    </span>
                </p>
            <?php } ?>
        </div>
    </section>

    <?php if ($veli_kurum_id > 0) { ?>
    <section class="section">
        <div class="container">
            <h2>Rezervasyonlarım</h2>
            <div class="cards">
                <?php if (empty($veli_rezervasyonlar)) { ?>
                    <div class="card">
                        <h3>Rezervasyon bulunamadı</h3>
                        <p class="meta">Henüz bir rezervasyonunuz yok.</p>
                    </div>
                <?php } else { ?>
                    <?php foreach ($veli_rezervasyonlar as $rez) { ?>
                        <?php
                            $rez_tarih = strtotime($rez['seans_baslangic']);
                            $rez_gecmis = $rez_tarih !== false && $rez_tarih < time();
                            $durum = $rez['durum'] ?? '';
                            $durum_etiket = $durum;
                            $durum_class = 'tag-muted';
                            if ($durum === 'onayli') {
                                $durum_etiket = 'Katılım Onaylandı';
                                $durum_class = 'tag-success';
                            } elseif ($durum === 'iptal') {
                                if ((int) ($rez['iptal_onay'] ?? 0) === 1) {
                                    $durum_etiket = 'İptal Onaylandı';
                                    $durum_class = 'tag-muted';
                                } else {
                                    $durum_etiket = 'İptal Onayı Bekleniyor';
                                    $durum_class = 'tag-warning';
                                }
                            } elseif ($durum === 'hak_yandi') {
                                $durum_etiket = 'Hak Yandı';
                                $durum_class = 'tag-danger';
                            }
                            $katilim_etiket = '';
                            $katilim_class = 'tag-muted';
                            if ($durum === 'onayli') {
                                $katilim_etiket = $rez_gecmis ? 'Katılım Onaylandı' : 'Katılım Bekleniyor';
                                $katilim_class = $rez_gecmis ? 'tag-info' : 'tag-blue';
                            }
                            $iptal_aktif = ($durum === 'onayli' && !$rez_gecmis);
                            $ogr_yas = $ogrenci_yas[(int) ($rez['ogrenci_id'] ?? 0)] ?? '-';
                        ?>
                        <div class="card">
                            <div class="tags" style="display:flex;gap:6px;flex-wrap:wrap;">
                                <span class="tag <?php echo htmlspecialchars($durum_class, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($durum_etiket, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if ($katilim_etiket !== '') { ?>
                                    <span class="tag <?php echo htmlspecialchars($katilim_class, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($katilim_etiket, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php } ?>
                            </div>
                            <h3><?php echo htmlspecialchars($rez['grup_adi'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class="meta"><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($rez['seans_baslangic'])), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="meta">Öğrenci: <?php echo htmlspecialchars($rez['ogrenci_adi'], ENT_QUOTES, 'UTF-8'); ?> • <?php echo htmlspecialchars($ogr_yas, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php if ($iptal_aktif) { ?>
                                <div class="d-flex gap-2" style="margin-top:10px;">
                                    <button
                                        class="btn btn-outline rezervasyon-iptal"
                                        data-rez-id="<?php echo (int) $rez['id']; ?>"
                                        data-seans="<?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($rez['seans_baslangic'])), ENT_QUOTES, 'UTF-8'); ?>">
                                        İptal Et
                                    </button>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2>Öğrenci Bilgilerim</h2>
            <div class="cards">
                <?php if (empty($veli_ogrenciler)) { ?>
                    <div class="card">
                        <h3>Öğrenci kaydı bulunamadı</h3>
                        <p class="meta">Profilinizden öğrenci ekleyebilirsiniz.</p>
                    </div>
                <?php } else { ?>
                    <?php foreach ($veli_ogrenciler as $ogr) { ?>
                        <?php
                            $yas_text = $ogrenci_yas[$ogr['id']] ?? '-';
                            $rez_sayi = $ogrenci_rez_sayisi[$ogr['id']] ?? 0;
                        ?>
                        <div class="card">
                            <span class="tag tag-info">Öğrenci</span>
                            <h3><?php echo htmlspecialchars($ogr['ad_soyad'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class="meta">Yaş: <?php echo htmlspecialchars($yas_text, ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="meta">Doğum: <?php echo htmlspecialchars($ogr['dogum_tarihi'] ? date('d.m.Y', strtotime($ogr['dogum_tarihi'])) : '-', ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="meta">Rezervasyon sayısı: <?php echo (int) $rez_sayi; ?></div>
                            <?php if (!empty($ogr['saglik_notlari'])) { ?>
                                <div class="meta">Sağlık Notu: <?php echo htmlspecialchars($ogr['saglik_notlari'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2>Kurum Bilgileri</h2>
            <div class="cards">
                <div class="card">
                    <?php if (!empty($kurum_bilgi)) { ?>
                        <span class="tag tag-success"><?php echo htmlspecialchars($kurum_bilgi['kurum_type'] ?? 'Kurum', ENT_QUOTES, 'UTF-8'); ?></span>
                        <h3><?php echo htmlspecialchars($kurum_bilgi['kurum_adi'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                        <div class="meta"><?php echo htmlspecialchars(trim(($kurum_bilgi['ilce'] ?? '') . ' ' . ($kurum_bilgi['sehir'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="meta"><?php echo htmlspecialchars($kurum_bilgi['adres'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="meta">Telefon: <?php echo htmlspecialchars($kurum_bilgi['telefon'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="meta">E-posta: <?php echo htmlspecialchars($kurum_bilgi['eposta'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php if (!empty($kurum_bilgi['hakkimizda'])) { ?>
                            <p class="lead" style="margin-top:12px;"><?php echo nl2br(htmlspecialchars($kurum_bilgi['hakkimizda'], ENT_QUOTES, 'UTF-8')); ?></p>
                        <?php } ?>
                    <?php } else { ?>
                        <h3>Kurum bilgisi bulunamadı</h3>
                        <p class="meta">Kurum bilgileri için yönetici ile iletişime geçebilirsiniz.</p>
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="grup-takvimim">
        <div class="container">
            <h2>Grup Takvimim</h2>
            <div class="calendar-wrap">
                <div class="calendar-grid">
                    <div class="calendar-card">
                        <div class="card-head">
                            <h3>Gruplar</h3>
                            <?php if (!empty($kurum_gruplar)) {
                                $filter_url = $grup_filtre_aktif ? 'grup_bilgilerim.php#grup-takvimim' : 'grup_bilgilerim.php?uygun=1#grup-takvimim';
                                $filter_label = $grup_filtre_aktif ? 'Filtreyi Kaldır' : 'Uygun Gruplar';
                                ?>
                                <a class="btn btn-outline btn-xs" href="<?php echo $filter_url; ?>"><?php echo $filter_label; ?></a>
                            <?php } ?>
                        </div>
                        <?php if (empty($kurum_gruplar)) { ?>
                            <p>Bu kurumda grup bulunamadı.</p>
                        <?php } else { ?>
                            <div class="group-list">
                                <?php if ($grup_filtre_aktif && empty($veli_ogrenciler)) { ?>
                                    <div class="group-item">
                                        <div class="group-title">Öğrenci kaydı bulunamadı</div>
                                        <div class="group-meta">Uygun grup filtresi için önce öğrenci ekleyin.</div>
                                    </div>
                                <?php } elseif ($grup_filtre_aktif && empty($gorunen_gruplar)) { ?>
                                    <div class="group-item">
                                        <div class="group-title">Uygun grup bulunamadı</div>
                                        <div class="group-meta">Öğrencilerin yaşına uygun grup bulunmuyor.</div>
                                    </div>
                                <?php } else { ?>
                                    <?php foreach ($gorunen_gruplar as $grup) {
                                        $yas_etiket = (!empty($grup['min_ay']) || !empty($grup['max_ay']))
                                            ? ($grup['min_ay'] ?: '0') . '-' . ($grup['max_ay'] ?: '72') . ' Ay'
                                            : 'Tüm yaşlar';
                                        $tekrar = $grup['tekrar_tipi'] === 'haftalik' ? 'Haftalık' : 'Tekil';
                                        $gunler = trim((string) $grup['tekrar_gunleri']);
                                        $gunler = $gunler !== '' ? str_replace(',', ', ', $gunler) : '-';
                                        $saat = $grup['seans_baslangic_saati'] ? substr($grup['seans_baslangic_saati'], 0, 5) : '-';
                                        $sure = $grup['seans_suresi_dk'] ? $grup['seans_suresi_dk'] . ' dk' : '-';
                                        ?>
                                        <div class="group-item">
                                            <div class="group-title"><?php echo htmlspecialchars($grup['grup_adi'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="group-meta"><?php echo htmlspecialchars($yas_etiket, ENT_QUOTES, 'UTF-8'); ?> • Kapasite <?php echo (int) ($grup['kapasite'] ?? 0); ?></div>
                                            <div class="group-meta"><?php echo htmlspecialchars($tekrar, ENT_QUOTES, 'UTF-8'); ?> • <?php echo htmlspecialchars($gunler, ENT_QUOTES, 'UTF-8'); ?> • <?php echo htmlspecialchars($saat, ENT_QUOTES, 'UTF-8'); ?> • <?php echo htmlspecialchars($sure, ENT_QUOTES, 'UTF-8'); ?></div>
                                        </div>
                                    <?php } ?>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="calendar-card">
                        <div class="card-head">
                            <div>
                                <h3>Aylık Takvim</h3>
                                <?php if ($ay_etiket !== '') { ?>
                                    <div class="meta"><?php echo htmlspecialchars($ay_etiket, ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php } ?>
                            </div>
                            <?php if (!empty($veli_ogrenciler)) { ?>
                                <select id="takvim-ogrenci" class="btn btn-outline btn-xs">
                                    <?php foreach ($veli_ogrenciler as $ogr) { ?>
                                        <option value="<?php echo (int) $ogr['id']; ?>">
                                            <?php echo htmlspecialchars($ogr['ad_soyad'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            <?php } ?>
                        </div>
                        <div class="month-head" style="margin-top:12px;">
                            <div>Pzt</div>
                            <div>Sal</div>
                            <div>Çar</div>
                            <div>Per</div>
                            <div>Cum</div>
                            <div>Cmt</div>
                            <div>Paz</div>
                        </div>
                        <div class="month-grid">
                            <?php foreach ($ay_hucreler as $cell) { ?>
                                <?php if ($cell === null) { ?>
                                    <div class="month-cell is-empty"></div>
                                <?php } else {
                                    $gun_key = $cell['date'];
                                    $gun_liste = $takvim[$gun_key] ?? [];
                                    ?>
                                    <div class="month-cell">
                                        <div class="month-day"><?php echo (int) $cell['day']; ?></div>
                                        <?php if (empty($gun_liste)) { ?>
                                            <span class="meta">Seans yok</span>
                                        <?php } else { ?>
                                            <?php foreach ($gun_liste as $seans) {
                                                $yas = (!empty($seans['min_ay']) || !empty($seans['max_ay']))
                                                    ? ($seans['min_ay'] ?: '0') . '-' . ($seans['max_ay'] ?: '72') . ' Ay'
                                                    : 'Tüm yaşlar';
                                                $saat = date('H:i', strtotime($seans['seans_baslangic'])) . ' - ' . date('H:i', strtotime($seans['seans_bitis']));
                                                $kontenjan = (int) ($seans['kontenjan'] ?? 0);
                                                $dolu = (int) ($seans['dolu'] ?? 0);
                                                $doluluk = $kontenjan > 0 ? $dolu . '/' . $kontenjan : $dolu;
                                                $full = $kontenjan > 0 && $dolu >= $kontenjan;
                                                $gecmis = strtotime($seans['seans_baslangic']) < time();
                                                $seans_id = (int) $seans['id'];
                                                $katildi = $aktif_ogrenci_id > 0 && !empty($veli_rez_by_student[$aktif_ogrenci_id][$seans_id]);
                                                $rez_liste = $veli_rez_map[$seans_id] ?? [];
                                                $rez_attr = !empty($rez_liste) ? implode(',', $rez_liste) : '';
                                                ?>
                                                <div class="session-chip<?php echo $full ? ' full' : ''; ?><?php echo $gecmis ? ' past' : ''; ?><?php echo $katildi ? ' joined' : ''; ?>"
                                                     data-seans-id="<?php echo (int) $seans['id']; ?>"
                                                     data-reservations="<?php echo htmlspecialchars($rez_attr, ENT_QUOTES, 'UTF-8'); ?>"
                                                     data-joined="<?php echo $katildi ? '1' : '0'; ?>"
                                                     data-gecmis="<?php echo $gecmis ? '1' : '0'; ?>"
                                                     data-kontenjan="<?php echo $kontenjan; ?>"
                                                     data-dolu="<?php echo $dolu; ?>">
                                                    <strong><?php echo htmlspecialchars($seans['grup_adi'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                    <small><?php echo htmlspecialchars($saat, ENT_QUOTES, 'UTF-8'); ?> • <?php echo htmlspecialchars($yas, ENT_QUOTES, 'UTF-8'); ?></small>
                                                    <small>Doluluk: <?php echo htmlspecialchars($doluluk, ENT_QUOTES, 'UTF-8'); ?></small>
                                                    <small class="joined-label"<?php echo $katildi ? '' : ' style="display:none"'; ?>>Katıldınız</small>
                                                </div>
                                            <?php } ?>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal-overlay" id="iptalModal">
        <div class="modal-box">
            <h3>Rezervasyon İptali</h3>
            <p id="iptalUyari" class="lead" style="margin-bottom:12px;"></p>
            <div class="field" style="margin-bottom:12px;">
                <label>İptal Sebebi</label>
                <textarea id="iptalSebep" rows="3" placeholder="İptal sebebinizi yazın" required></textarea>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline" id="iptalVazgec">Vazgeç</button>
                <button class="btn btn-primary" id="iptalOnayla">İptal Et</button>
            </div>
        </div>
    </div>
    <script>
        (function() {
            var veliId = <?php echo (int) ($veli['id'] ?? 0); ?>;
            var iptalKuralSaat = <?php echo (int) $iptal_kural_saat; ?>;
            var ogrSelect = document.getElementById('takvim-ogrenci');
            var iptalModal = document.getElementById('iptalModal');
            var iptalUyari = document.getElementById('iptalUyari');
            var iptalSebep = document.getElementById('iptalSebep');
            var iptalOnayBtn = document.getElementById('iptalOnayla');
            var iptalVazgecBtn = document.getElementById('iptalVazgec');
            var aktifRezId = 0;
            var aktifOnay = 0;
            if (!veliId) { return; }

            function postAjax(payload) {
                return fetch('ajax.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(payload)
                }).then(function(res) { return res.json(); });
            }

            function hakUyarisiMesaji(data) {
                var bakiye = parseInt(data.bakiye_hak || 0, 10);
                var donduruldu = parseInt(data.hak_donduruldu || 0, 10) === 1;
                if (donduruldu) {
                    return 'Haklarınız dondurulmuş görünüyor. Hak almak için kurum yöneticisiyle iletişime geçin.';
                }
                if (data.hak_gecerlilik_bitis) {
                    var bitis = new Date(data.hak_gecerlilik_bitis + 'T23:59:59');
                    if (bitis < new Date()) {
                        return 'Hak süreniz dolmuş. Hak almak için kurum yöneticisiyle iletişime geçin.';
                    }
                }
                if (bakiye <= 0) {
                    return 'Hak bakiyeniz yok. Hak almak için kurum yöneticisiyle iletişime geçin.';
                }
                return '';
            }

            function modalAc() {
                if (iptalModal) {
                    iptalModal.classList.add('is-open');
                }
            }

            function modalKapat() {
                if (iptalModal) {
                    iptalModal.classList.remove('is-open');
                }
                if (iptalSebep) {
                    iptalSebep.value = '';
                }
                aktifRezId = 0;
                aktifOnay = 0;
            }

            document.querySelectorAll('.rezervasyon-iptal').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var rezId = this.getAttribute('data-rez-id');
                    var seansStr = this.getAttribute('data-seans');
                    if (!rezId || !seansStr) {
                        return;
                    }
                    var seansTarih = new Date(seansStr.replace(' ', 'T'));
                    var kalanSaat = (seansTarih - new Date()) / 3600000;
                    aktifOnay = kalanSaat < iptalKuralSaat ? 1 : 0;
                    if (kalanSaat >= iptalKuralSaat) {
                        iptalUyari.textContent = 'Rezervasyon iptali ' + iptalKuralSaat + ' saat öncesinde olduğu için hak iadesi yapılacaktır.';
                    } else {
                        iptalUyari.textContent = 'Grubun başlamasına ' + iptalKuralSaat + ' saatten az kaldığı için hakkınız iade edilmeyecektir.';
                    }
                    aktifRezId = parseInt(rezId, 10) || 0;
                    modalAc();
                });
            });

            if (iptalVazgecBtn) {
                iptalVazgecBtn.addEventListener('click', function() {
                    modalKapat();
                });
            }

            if (iptalOnayBtn) {
                iptalOnayBtn.addEventListener('click', function() {
                    if (!aktifRezId) {
                        return;
                    }
                    var sebep = (iptalSebep && iptalSebep.value) ? iptalSebep.value.trim() : '';
                    if (!sebep) {
                        alert('İptal sebebi giriniz.');
                        return;
                    }
                    postAjax({action: 'rezervasyon_iptal', rezervasyon_id: aktifRezId, onay: aktifOnay, iptal_sebebi: sebep}).then(function(res) {
                        if (res && res.durum === 'ok') {
                            alert(res.mesaj || 'İptal talebi alındı.');
                            window.location.reload();
                            return;
                        }
                        if (res && res.durum === 'uyari') {
                            alert(res.mesaj || 'Uyarı');
                            return;
                        }
                        alert((res && res.mesaj) ? res.mesaj : 'İptal işlemi yapılamadı.');
                    }).catch(function() {
                        alert('İptal işlemi sırasında hata oluştu.');
                    });
                });
            }

            document.querySelectorAll('.session-chip').forEach(function(chip) {
                chip.addEventListener('click', function() {
                    if (this.getAttribute('data-joined') === '1') {
                        alert('Bu seans için zaten rezervasyonunuz var.');
                        return;
                    }
                    if (this.getAttribute('data-gecmis') === '1') {
                        alert('Geçmiş tarihli seanslara katılım sağlanamaz.');
                        return;
                    }
                    var seansId = this.getAttribute('data-seans-id');
                    var ogrenciId = ogrSelect ? ogrSelect.value : '';
                    if (!ogrenciId) {
                        alert('Rezervasyon için önce öğrenci seçin.');
                        return;
                    }
                    postAjax({action: 'hak_kontrol', veli_id: veliId}).then(function(resp) {
                        if (!resp || resp.durum !== 'ok') {
                            alert((resp && resp.mesaj) ? resp.mesaj : 'Hak bilgisi alınamadı.');
                            return;
                        }
                        var uyari = hakUyarisiMesaji(resp);
                        if (uyari) {
                            alert(uyari);
                            return;
                        }
                        if (!confirm('Bu seansa katılmak istiyor musunuz?')) {
                            return;
                        }
                        postAjax({action: 'rezervasyon_yap', ogrenci_id: ogrenciId, seans_id: seansId}).then(function(r2) {
                            if (r2 && r2.mesaj) {
                                alert(r2.mesaj);
                            } else {
                                alert('İşlem tamamlandı.');
                            }
                            if (r2 && r2.durum === 'ok') {
                                window.location.reload();
                            }
                        }).catch(function() {
                            alert('Rezervasyon işlemi sırasında hata oluştu.');
                        });
                    }).catch(function() {
                        alert('Hak kontrolü sırasında hata oluştu.');
                    });
                });
            });

            if (ogrSelect) {
                ogrSelect.addEventListener('change', function() {
                    var selectedId = this.value;
                    document.querySelectorAll('.session-chip').forEach(function(chip) {
                        var list = chip.getAttribute('data-reservations') || '';
                        var ids = list.split(',').filter(Boolean);
                        var joined = ids.indexOf(String(selectedId)) !== -1;
                        chip.setAttribute('data-joined', joined ? '1' : '0');
                        chip.classList.toggle('joined', joined);
                        var label = chip.querySelector('.joined-label');
                        if (label) {
                            label.style.display = joined ? 'block' : 'none';
                        }
                    });
                });
            }
        })();
    </script>
    <?php } ?>
</body>
</html>
