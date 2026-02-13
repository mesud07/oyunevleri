<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

site_admin_giris_zorunlu();

if (empty($db_master)) {
    die('Master veritabani baglantisi bulunamadi.');
}

function master_kurum_ozellik_var_mi($db_master) {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    try {
        $stmt = $db_master->query("SHOW COLUMNS FROM kurumlar LIKE 'ozellikler'");
        $cache = (bool) $stmt->fetch();
    } catch (Throwable $e) {
        $cache = false;
    }
    return $cache;
}

function kurum_kodu_uret($db_master) {
    for ($i = 0; $i < 5; $i++) {
        $kod = strtoupper(bin2hex(random_bytes(3)));
        $stmt = $db_master->prepare("SELECT COUNT(*) FROM kurumlar WHERE kurum_kodu = :kod");
        $stmt->execute(['kod' => $kod]);
        if ((int) $stmt->fetchColumn() === 0) {
            return $kod;
        }
    }
    return strtoupper(bin2hex(random_bytes(4)));
}

$mesaj = '';
$mesaj_tipi = '';
$edit_id = (int) ($_GET['edit_id'] ?? 0);
$durum_id = (int) ($_POST['durum_id'] ?? 0);
$is_admin = site_admin_admin_mi();
$kurum_form = [
    'kurum_kodu' => '',
    'kurum_adi' => '',
    'kurum_type' => 'Oyun Evi',
    'sehir' => '',
    'ilce' => '',
    'adres' => '',
    'telefon' => '',
    'eposta' => '',
    'web_site' => '',
    'instagram' => '',
    'hakkimizda' => '',
    'ozellikler' => '',
    'min_ay' => '',
    'max_ay' => '',
    'kurulus_yili' => '',
    'ucret' => '',
    'kapali_alan' => '',
    'acik_alan' => '',
    'meb_onay' => 0,
    'aile_sosyal_onay' => 0,
    'hizmet_bahceli' => 0,
    'hizmet_havuz' => 0,
    'hizmet_guvenlik' => 0,
    'hizmet_guvenlik_kamerasi' => 0,
    'hizmet_yemek' => 0,
    'hizmet_ingilizce' => 0,
    'durum' => 1,
];

$ozellik_kolon_var = master_kurum_ozellik_var_mi($db_master);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle_durum' && $durum_id > 0) {
        if (!$is_admin) {
            $mesaj = 'Bu işlem için yetkiniz yok.';
            $mesaj_tipi = 'error';
        } else {
            $stmt = $db_master->prepare("SELECT durum FROM kurumlar WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $durum_id]);
            $current = (int) $stmt->fetchColumn();
            $yeni = $current === 1 ? 0 : 1;
            $ok = $db_master->prepare("UPDATE kurumlar SET durum = :durum WHERE id = :id")->execute([
                'durum' => $yeni,
                'id' => $durum_id,
            ]);
            $mesaj = $ok ? 'Kurum durumu güncellendi.' : 'Kurum durumu güncellenemedi.';
            $mesaj_tipi = $ok ? 'success' : 'error';
            if ($ok) {
                site_admin_log_ekle('kurum_durum', $durum_id, $yeni === 1 ? 'Kurum aktif edildi' : 'Kurum pasife alindi', 'kurum');
            }
        }
    }
    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $kurum_form = array_merge($kurum_form, [
            'kurum_kodu' => trim($_POST['kurum_kodu'] ?? ''),
            'kurum_adi' => trim($_POST['kurum_adi'] ?? ''),
            'kurum_type' => trim($_POST['kurum_type'] ?? 'Oyun Evi'),
            'sehir' => trim($_POST['sehir'] ?? ''),
            'ilce' => trim($_POST['ilce'] ?? ''),
            'adres' => trim($_POST['adres'] ?? ''),
            'telefon' => trim($_POST['telefon'] ?? ''),
            'eposta' => trim($_POST['eposta'] ?? ''),
            'web_site' => trim($_POST['web_site'] ?? ''),
            'instagram' => trim($_POST['instagram'] ?? ''),
            'hakkimizda' => trim($_POST['hakkimizda'] ?? ''),
            'ozellikler' => trim($_POST['ozellikler'] ?? ''),
            'min_ay' => trim($_POST['min_ay'] ?? ''),
            'max_ay' => trim($_POST['max_ay'] ?? ''),
            'kurulus_yili' => trim($_POST['kurulus_yili'] ?? ''),
            'ucret' => trim($_POST['ucret'] ?? ''),
            'kapali_alan' => trim($_POST['kapali_alan'] ?? ''),
            'acik_alan' => trim($_POST['acik_alan'] ?? ''),
            'meb_onay' => !empty($_POST['meb_onay']) ? 1 : 0,
            'aile_sosyal_onay' => !empty($_POST['aile_sosyal_onay']) ? 1 : 0,
            'hizmet_bahceli' => !empty($_POST['hizmet_bahceli']) ? 1 : 0,
            'hizmet_havuz' => !empty($_POST['hizmet_havuz']) ? 1 : 0,
            'hizmet_guvenlik' => !empty($_POST['hizmet_guvenlik']) ? 1 : 0,
            'hizmet_guvenlik_kamerasi' => !empty($_POST['hizmet_guvenlik_kamerasi']) ? 1 : 0,
            'hizmet_yemek' => !empty($_POST['hizmet_yemek']) ? 1 : 0,
            'hizmet_ingilizce' => !empty($_POST['hizmet_ingilizce']) ? 1 : 0,
            'durum' => !empty($_POST['durum']) ? 1 : 0,
        ]);

        if ($kurum_form['kurum_adi'] === '') {
            $mesaj = 'Kurum adı zorunludur.';
            $mesaj_tipi = 'error';
        } else {
            if ($kurum_form['kurum_kodu'] === '') {
                $kurum_form['kurum_kodu'] = kurum_kodu_uret($db_master);
            }
            $min_ay = $kurum_form['min_ay'] !== '' ? (int) $kurum_form['min_ay'] : null;
            $max_ay = $kurum_form['max_ay'] !== '' ? (int) $kurum_form['max_ay'] : null;
            $kurulus_yili = $kurum_form['kurulus_yili'] !== '' ? (int) $kurum_form['kurulus_yili'] : null;

            if ($id > 0) {
                $sql = "UPDATE kurumlar SET
                    kurum_kodu = :kurum_kodu,
                    kurum_adi = :kurum_adi,
                    kurum_type = :kurum_type,
                    sehir = :sehir,
                    ilce = :ilce,
                    adres = :adres,
                    telefon = :telefon,
                    eposta = :eposta,
                    web_site = :web_site,
                    instagram = :instagram,
                    hakkimizda = :hakkimizda,
                    min_ay = :min_ay,
                    max_ay = :max_ay,
                    kurulus_yili = :kurulus_yili,
                    ucret = :ucret,
                    kapali_alan = :kapali_alan,
                    acik_alan = :acik_alan,
                    meb_onay = :meb_onay,
                    aile_sosyal_onay = :aile_sosyal_onay,
                    hizmet_bahceli = :hizmet_bahceli,
                    hizmet_havuz = :hizmet_havuz,
                    hizmet_guvenlik = :hizmet_guvenlik,
                    hizmet_guvenlik_kamerasi = :hizmet_guvenlik_kamerasi,
                    hizmet_yemek = :hizmet_yemek,
                    hizmet_ingilizce = :hizmet_ingilizce,
                    durum = :durum";
                if ($ozellik_kolon_var) {
                    $sql .= ", ozellikler = :ozellikler";
                }
                $sql .= " WHERE id = :id";
                $stmt = $db_master->prepare($sql);
                $params = [
                    'kurum_kodu' => $kurum_form['kurum_kodu'],
                    'kurum_adi' => $kurum_form['kurum_adi'],
                    'kurum_type' => $kurum_form['kurum_type'],
                    'sehir' => $kurum_form['sehir'],
                    'ilce' => $kurum_form['ilce'],
                    'adres' => $kurum_form['adres'],
                    'telefon' => $kurum_form['telefon'],
                    'eposta' => $kurum_form['eposta'],
                    'web_site' => $kurum_form['web_site'],
                    'instagram' => $kurum_form['instagram'],
                    'hakkimizda' => $kurum_form['hakkimizda'],
                    'min_ay' => $min_ay,
                    'max_ay' => $max_ay,
                    'kurulus_yili' => $kurulus_yili,
                    'ucret' => $kurum_form['ucret'],
                    'kapali_alan' => $kurum_form['kapali_alan'],
                    'acik_alan' => $kurum_form['acik_alan'],
                    'meb_onay' => $kurum_form['meb_onay'],
                    'aile_sosyal_onay' => $kurum_form['aile_sosyal_onay'],
                    'hizmet_bahceli' => $kurum_form['hizmet_bahceli'],
                    'hizmet_havuz' => $kurum_form['hizmet_havuz'],
                    'hizmet_guvenlik' => $kurum_form['hizmet_guvenlik'],
                    'hizmet_guvenlik_kamerasi' => $kurum_form['hizmet_guvenlik_kamerasi'],
                    'hizmet_yemek' => $kurum_form['hizmet_yemek'],
                    'hizmet_ingilizce' => $kurum_form['hizmet_ingilizce'],
                    'durum' => $kurum_form['durum'],
                    'id' => $id,
                ];
                if ($ozellik_kolon_var) {
                    $params['ozellikler'] = $kurum_form['ozellikler'];
                }
                $ok = $stmt->execute($params);
                if ($ok) {
                    $mesaj = 'Kurum güncellendi.';
                    $mesaj_tipi = 'success';
                    $edit_id = 0;
                    $kurum_form = array_map(function () { return ''; }, $kurum_form);
                } else {
                    $mesaj = 'Kurum güncellenemedi.';
                    $mesaj_tipi = 'error';
                }
            } else {
                $sql = "INSERT INTO kurumlar
                    (kurum_kodu, kurum_adi, kurum_type, kurum_db_adi, sehir, ilce, adres, telefon, eposta, web_site, instagram, hakkimizda,
                     min_ay, max_ay, kurulus_yili, ucret, kapali_alan, acik_alan,
                     meb_onay, aile_sosyal_onay, hizmet_bahceli, hizmet_havuz, hizmet_guvenlik, hizmet_guvenlik_kamerasi, hizmet_yemek, hizmet_ingilizce, durum";
                if ($ozellik_kolon_var) {
                    $sql .= ", ozellikler";
                }
                $sql .= ") VALUES
                    (:kurum_kodu, :kurum_adi, :kurum_type, :kurum_db_adi, :sehir, :ilce, :adres, :telefon, :eposta, :web_site, :instagram, :hakkimizda,
                     :min_ay, :max_ay, :kurulus_yili, :ucret, :kapali_alan, :acik_alan,
                     :meb_onay, :aile_sosyal_onay, :hizmet_bahceli, :hizmet_havuz, :hizmet_guvenlik, :hizmet_guvenlik_kamerasi, :hizmet_yemek, :hizmet_ingilizce, :durum";
                if ($ozellik_kolon_var) {
                    $sql .= ", :ozellikler";
                }
                $sql .= ")";
                $stmt = $db_master->prepare($sql);
                $params = [
                    'kurum_kodu' => $kurum_form['kurum_kodu'],
                    'kurum_adi' => $kurum_form['kurum_adi'],
                    'kurum_type' => $kurum_form['kurum_type'],
                    'kurum_db_adi' => 'oyunev_kurum',
                    'sehir' => $kurum_form['sehir'],
                    'ilce' => $kurum_form['ilce'],
                    'adres' => $kurum_form['adres'],
                    'telefon' => $kurum_form['telefon'],
                    'eposta' => $kurum_form['eposta'],
                    'web_site' => $kurum_form['web_site'],
                    'instagram' => $kurum_form['instagram'],
                    'hakkimizda' => $kurum_form['hakkimizda'],
                    'min_ay' => $min_ay,
                    'max_ay' => $max_ay,
                    'kurulus_yili' => $kurulus_yili,
                    'ucret' => $kurum_form['ucret'],
                    'kapali_alan' => $kurum_form['kapali_alan'],
                    'acik_alan' => $kurum_form['acik_alan'],
                    'meb_onay' => $kurum_form['meb_onay'],
                    'aile_sosyal_onay' => $kurum_form['aile_sosyal_onay'],
                    'hizmet_bahceli' => $kurum_form['hizmet_bahceli'],
                    'hizmet_havuz' => $kurum_form['hizmet_havuz'],
                    'hizmet_guvenlik' => $kurum_form['hizmet_guvenlik'],
                    'hizmet_guvenlik_kamerasi' => $kurum_form['hizmet_guvenlik_kamerasi'],
                    'hizmet_yemek' => $kurum_form['hizmet_yemek'],
                    'hizmet_ingilizce' => $kurum_form['hizmet_ingilizce'],
                    'durum' => $kurum_form['durum'],
                ];
                if ($ozellik_kolon_var) {
                    $params['ozellikler'] = $kurum_form['ozellikler'];
                }
                $ok = $stmt->execute($params);
                if ($ok) {
                    $mesaj = 'Kurum eklendi.';
                    $mesaj_tipi = 'success';
                    $kurum_form = array_map(function () { return ''; }, $kurum_form);
                } else {
                    $mesaj = 'Kurum eklenemedi.';
                    $mesaj_tipi = 'error';
                }
            }
        }
    }
}

if ($edit_id > 0) {
    $stmt = $db_master->prepare("SELECT * FROM kurumlar WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $edit_id]);
    $row = $stmt->fetch();
    if ($row) {
        foreach ($kurum_form as $key => $val) {
            if (array_key_exists($key, $row)) {
                $kurum_form[$key] = (string) ($row[$key] ?? '');
            }
        }
        $kurum_form['meb_onay'] = (int) ($row['meb_onay'] ?? 0);
        $kurum_form['aile_sosyal_onay'] = (int) ($row['aile_sosyal_onay'] ?? 0);
        $kurum_form['hizmet_bahceli'] = (int) ($row['hizmet_bahceli'] ?? 0);
        $kurum_form['hizmet_havuz'] = (int) ($row['hizmet_havuz'] ?? 0);
        $kurum_form['hizmet_guvenlik'] = (int) ($row['hizmet_guvenlik'] ?? 0);
        $kurum_form['hizmet_guvenlik_kamerasi'] = (int) ($row['hizmet_guvenlik_kamerasi'] ?? 0);
        $kurum_form['hizmet_yemek'] = (int) ($row['hizmet_yemek'] ?? 0);
        $kurum_form['hizmet_ingilizce'] = (int) ($row['hizmet_ingilizce'] ?? 0);
        $kurum_form['durum'] = (int) ($row['durum'] ?? 1);
    }
}

$select_ozellik = $ozellik_kolon_var ? 'ozellikler' : "'' AS ozellikler";
$stmt = $db_master->query("SELECT id, kurum_kodu, kurum_adi, kurum_type, sehir, ilce, adres, telefon, eposta, web_site, instagram, hakkimizda,
    min_ay, max_ay, kurulus_yili, ucret, kapali_alan, acik_alan, meb_onay, aile_sosyal_onay, hizmet_bahceli, hizmet_havuz, hizmet_guvenlik,
    hizmet_guvenlik_kamerasi, hizmet_yemek, hizmet_ingilizce, durum, {$select_ozellik}
    FROM kurumlar ORDER BY id DESC");
$kurum_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Site Admin - Kurumlar</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&family=Manrope:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#f6f7fb; --ink:#1f2937; --muted:#6b7280; --primary:#ff7a59; --card:#fff; --stroke:rgba(31,41,55,0.08); }
        * { box-sizing: border-box; }
        body { margin:0; font-family:"Manrope",system-ui; background:var(--bg); color:var(--ink); }
        .container { max-width:1200px; margin:0 auto; padding:32px 20px 60px; }
        header { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:20px; }
        h1 { font-family:"Baloo 2",cursive; margin:0; }
        .btn { border:none; padding:10px 16px; border-radius:12px; font-weight:700; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
        .btn.primary { background:var(--primary); color:#fff; }
        .btn.light { background:#fff; color:var(--ink); border:1px solid var(--stroke); }
        .card { background:var(--card); border:1px solid var(--stroke); border-radius:18px; padding:18px; box-shadow:0 12px 22px rgba(31,41,55,0.08); margin-bottom:18px; }
        .alert { padding:10px 12px; border-radius:12px; font-weight:600; margin-bottom:12px; }
        .alert.success { background:#e7f6ed; color:#1f7a46; border:1px solid #c9ecd8; }
        .alert.error { background:#ffe8e3; color:#8a2b17; border:1px solid #ffc9bc; }
        .grid { display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:12px; }
        .field { display:flex; flex-direction:column; gap:6px; }
        .field label { font-size:13px; color:var(--muted); }
        .field input, .field textarea, .field select { border:1px solid var(--stroke); border-radius:12px; padding:10px 12px; font-size:14px; }
        .field textarea { min-height:100px; resize:vertical; }
        .checkboxes { display:flex; flex-wrap:wrap; gap:12px; margin-top:10px; }
        table { width:100%; border-collapse: collapse; }
        th, td { text-align:left; padding:10px 8px; border-bottom:1px solid rgba(31,41,55,0.08); font-size:14px; }
        th { font-size:12px; text-transform:uppercase; letter-spacing:0.4px; color:var(--muted); }
        .badge { display:inline-block; padding:4px 10px; border-radius:999px; font-size:12px; }
        .badge.active { background:#e7f6ed; color:#1f7a46; }
        .badge.passive { background:#f3f4f6; color:#6b7280; }
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 9999;
        }
        .modal-backdrop.is-open { display: flex; }
        .modal-box {
            background: var(--card);
            border-radius: 18px;
            padding: 20px;
            width: 100%;
            max-width: 920px;
            max-height: 90vh;
            overflow: auto;
            border: 1px solid var(--stroke);
            box-shadow: 0 20px 45px rgba(31,41,55,0.18);
        }
        .modal-head {
            display:flex;
            justify-content: space-between;
            align-items:center;
            gap:12px;
            margin-bottom: 12px;
        }
        .btn.close {
            background:#fff;
            border:1px solid var(--stroke);
        }
        @media (max-width: 960px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Site Admin - Kurum Yönetimi</h1>
            <div class="d-flex">
                <a class="btn light" href="site_admin_kullanicilar.php">Kullanıcılar</a>
                <button class="btn primary" type="button" id="kurumYeniBtn">Yeni Kurum</button>
                <a class="btn light" href="site_admin_kurumlar.php">Yenile</a>
                <a class="btn primary" href="site_admin_logout.php">Çıkış</a>
            </div>
        </header>

        <?php if ($mesaj !== '') { ?>
            <div class="alert <?php echo $mesaj_tipi === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($mesaj, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php } ?>

        <div class="card">
            <h3>Kurum Listesi</h3>
            <div style="overflow:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Kurum</th>
                            <th>Tür</th>
                            <th>Şehir</th>
                            <th>Telefon</th>
                            <th>Yaş</th>
                            <th>Durum</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($kurum_list)) { ?>
                            <tr><td colspan="8">Kurum bulunamadı.</td></tr>
                        <?php } else { ?>
                            <?php foreach ($kurum_list as $kurum) { ?>
                                <tr>
                                    <td><?php echo (int) $kurum['id']; ?></td>
                                    <td><?php echo htmlspecialchars($kurum['kurum_adi'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($kurum['kurum_type'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(trim(($kurum['ilce'] ?? '') . ' ' . ($kurum['sehir'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($kurum['telefon'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(($kurum['min_ay'] ?? '-') . ' / ' . ($kurum['max_ay'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if ((int) ($kurum['durum'] ?? 0) === 1) { ?>
                                            <span class="badge active">Aktif</span>
                                        <?php } else { ?>
                                            <span class="badge passive">Pasif</span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php
                                        $kurum_json = htmlspecialchars(json_encode($kurum, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                        ?>
                                        <button class="btn light btn-edit" type="button" data-kurum="<?php echo $kurum_json; ?>">Düzenle</button>
                                        <?php if ($is_admin) { ?>
                                            <form method="post" style="display:inline-block;" onsubmit="return confirm('Bu işlemi yapmak istediğinize emin misiniz?');">
                                                <input type="hidden" name="action" value="toggle_durum">
                                                <input type="hidden" name="durum_id" value="<?php echo (int) $kurum['id']; ?>">
                                                <button class="btn light" type="submit"><?php echo (int) ($kurum['durum'] ?? 0) === 1 ? 'Pasife Al' : 'Aktifleştir'; ?></button>
                                            </form>
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

    <div class="modal-backdrop" id="kurumModal">
        <div class="modal-box">
            <div class="modal-head">
                <h3 id="kurumModalTitle">Yeni Kurum</h3>
                <button class="btn close" type="button" id="kurumModalClose">Kapat</button>
            </div>
            <form method="post" id="kurumForm">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="kurum_id" value="0">
                <div class="grid">
                    <div class="field">
                        <label>Kurum Kodu</label>
                        <input type="text" name="kurum_kodu" id="kurum_kodu" value="">
                    </div>
                    <div class="field">
                        <label>Kurum Adı</label>
                        <input type="text" name="kurum_adi" id="kurum_adi" required>
                    </div>
                    <div class="field">
                        <label>Kurum Türü</label>
                        <select name="kurum_type" id="kurum_type">
                            <?php
                            $types = ['Oyun Evi', 'Kreş', 'Anaokulu', 'Diğer'];
                            foreach ($types as $type) {
                                echo "<option value=\"" . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . "\">{$type}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Şehir</label>
                        <input type="text" name="sehir" id="kurum_sehir">
                    </div>
                    <div class="field">
                        <label>İlçe</label>
                        <input type="text" name="ilce" id="kurum_ilce">
                    </div>
                    <div class="field">
                        <label>Telefon</label>
                        <input type="text" name="telefon" id="kurum_telefon">
                    </div>
                    <div class="field">
                        <label>E-posta</label>
                        <input type="email" name="eposta" id="kurum_eposta">
                    </div>
                    <div class="field">
                        <label>Web Sitesi</label>
                        <input type="text" name="web_site" id="kurum_web_site" placeholder="https://">
                    </div>
                    <div class="field">
                        <label>Instagram</label>
                        <input type="text" name="instagram" id="kurum_instagram" placeholder="https://instagram.com/">
                    </div>
                    <div class="field">
                        <label>Min Ay</label>
                        <input type="number" name="min_ay" id="kurum_min_ay">
                    </div>
                    <div class="field">
                        <label>Max Ay</label>
                        <input type="number" name="max_ay" id="kurum_max_ay">
                    </div>
                    <div class="field">
                        <label>Kuruluş Yılı</label>
                        <input type="number" name="kurulus_yili" id="kurum_kurulus_yili">
                    </div>
                    <div class="field">
                        <label>Ücret Aralığı</label>
                        <input type="text" name="ucret" id="kurum_ucret">
                    </div>
                    <div class="field">
                        <label>Kapalı Alan (m²)</label>
                        <input type="text" name="kapali_alan" id="kurum_kapali_alan">
                    </div>
                    <div class="field">
                        <label>Açık Alan (m²)</label>
                        <input type="text" name="acik_alan" id="kurum_acik_alan">
                    </div>
                </div>
                <div class="field" style="margin-top:12px;">
                    <label>Adres</label>
                    <textarea name="adres" id="kurum_adres"></textarea>
                </div>
                <div class="field">
                    <label>Hakkımızda</label>
                    <textarea name="hakkimizda" id="kurum_hakkimizda"></textarea>
                </div>
                <?php if ($ozellik_kolon_var) { ?>
                    <div class="field">
                        <label>Özellikler</label>
                        <textarea name="ozellikler" id="kurum_ozellikler"></textarea>
                    </div>
                <?php } ?>
                <div class="checkboxes">
                    <label><input type="checkbox" name="meb_onay" id="kurum_meb" value="1"> MEB Onaylı</label>
                    <label><input type="checkbox" name="aile_sosyal_onay" id="kurum_aile" value="1"> Aile Sosyal Onaylı</label>
                    <label><input type="checkbox" name="hizmet_bahceli" id="kurum_bahce" value="1"> Bahçeli</label>
                    <label><input type="checkbox" name="hizmet_havuz" id="kurum_havuz" value="1"> Havuz</label>
                    <label><input type="checkbox" name="hizmet_guvenlik" id="kurum_guvenlik" value="1"> Güvenlik</label>
                    <label><input type="checkbox" name="hizmet_guvenlik_kamerasi" id="kurum_kamera" value="1"> Güvenlik Kamerası</label>
                    <label><input type="checkbox" name="hizmet_yemek" id="kurum_yemek" value="1"> Yemek</label>
                    <label><input type="checkbox" name="hizmet_ingilizce" id="kurum_ingilizce" value="1"> İngilizce</label>
                    <label><input type="checkbox" name="durum" id="kurum_durum" value="1" checked> Aktif</label>
                </div>
                <div style="margin-top:16px;">
                    <button class="btn primary" type="submit" id="kurumKaydetBtn">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            var modal = document.getElementById('kurumModal');
            var closeBtn = document.getElementById('kurumModalClose');
            var newBtn = document.getElementById('kurumYeniBtn');
            var title = document.getElementById('kurumModalTitle');

            function setValue(id, value) {
                var el = document.getElementById(id);
                if (el) {
                    el.value = value === null || value === undefined ? '' : value;
                }
            }
            function setCheck(id, value) {
                var el = document.getElementById(id);
                if (el) {
                    el.checked = value === 1 || value === '1' || value === true;
                }
            }
            function openModal() {
                if (modal) {
                    modal.classList.add('is-open');
                }
            }
            function closeModal() {
                if (modal) {
                    modal.classList.remove('is-open');
                }
            }
            if (closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }
            if (newBtn) {
                newBtn.addEventListener('click', function() {
                    title.textContent = 'Yeni Kurum';
                    setValue('kurum_id', 0);
                    setValue('kurum_kodu', '');
                    setValue('kurum_adi', '');
                    setValue('kurum_type', 'Oyun Evi');
                    setValue('kurum_sehir', '');
                    setValue('kurum_ilce', '');
                    setValue('kurum_telefon', '');
                    setValue('kurum_eposta', '');
                    setValue('kurum_web_site', '');
                    setValue('kurum_instagram', '');
                    setValue('kurum_min_ay', '');
                    setValue('kurum_max_ay', '');
                    setValue('kurum_kurulus_yili', '');
                    setValue('kurum_ucret', '');
                    setValue('kurum_kapali_alan', '');
                    setValue('kurum_acik_alan', '');
                    setValue('kurum_adres', '');
                    setValue('kurum_hakkimizda', '');
                    setValue('kurum_ozellikler', '');
                    setCheck('kurum_meb', 0);
                    setCheck('kurum_aile', 0);
                    setCheck('kurum_bahce', 0);
                    setCheck('kurum_havuz', 0);
                    setCheck('kurum_guvenlik', 0);
                    setCheck('kurum_kamera', 0);
                    setCheck('kurum_yemek', 0);
                    setCheck('kurum_ingilizce', 0);
                    setCheck('kurum_durum', 1);
                    openModal();
                });
            }
            document.querySelectorAll('.btn-edit').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var raw = this.getAttribute('data-kurum') || '{}';
                    var data = {};
                    try { data = JSON.parse(raw); } catch (e) { data = {}; }
                    title.textContent = 'Kurum Güncelle';
                    setValue('kurum_id', data.id || 0);
                    setValue('kurum_kodu', data.kurum_kodu || '');
                    setValue('kurum_adi', data.kurum_adi || '');
                    setValue('kurum_type', data.kurum_type || 'Oyun Evi');
                    setValue('kurum_sehir', data.sehir || '');
                    setValue('kurum_ilce', data.ilce || '');
                    setValue('kurum_telefon', data.telefon || '');
                    setValue('kurum_eposta', data.eposta || '');
                    setValue('kurum_web_site', data.web_site || '');
                    setValue('kurum_instagram', data.instagram || '');
                    setValue('kurum_min_ay', data.min_ay || '');
                    setValue('kurum_max_ay', data.max_ay || '');
                    setValue('kurum_kurulus_yili', data.kurulus_yili || '');
                    setValue('kurum_ucret', data.ucret || '');
                    setValue('kurum_kapali_alan', data.kapali_alan || '');
                    setValue('kurum_acik_alan', data.acik_alan || '');
                    setValue('kurum_adres', data.adres || '');
                    setValue('kurum_hakkimizda', data.hakkimizda || '');
                    setValue('kurum_ozellikler', data.ozellikler || '');
                    setCheck('kurum_meb', data.meb_onay || 0);
                    setCheck('kurum_aile', data.aile_sosyal_onay || 0);
                    setCheck('kurum_bahce', data.hizmet_bahceli || 0);
                    setCheck('kurum_havuz', data.hizmet_havuz || 0);
                    setCheck('kurum_guvenlik', data.hizmet_guvenlik || 0);
                    setCheck('kurum_kamera', data.hizmet_guvenlik_kamerasi || 0);
                    setCheck('kurum_yemek', data.hizmet_yemek || 0);
                    setCheck('kurum_ingilizce', data.hizmet_ingilizce || 0);
                    setCheck('kurum_durum', data.durum || 0);
                    openModal();
                });
            });
            window.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });

            <?php if ($edit_id > 0) { ?>
            openModal();
            title.textContent = 'Kurum Güncelle';
            setValue('kurum_id', <?php echo (int) $edit_id; ?>);
            setValue('kurum_kodu', <?php echo json_encode($kurum_form['kurum_kodu'], JSON_UNESCAPED_UNICODE); ?>);
            setValue('kurum_adi', <?php echo json_encode($kurum_form['kurum_adi'], JSON_UNESCAPED_UNICODE); ?>);
            setValue('kurum_type', <?php echo json_encode($kurum_form['kurum_type'], JSON_UNESCAPED_UNICODE); ?>);
            setValue('kurum_sehir', <?php echo json_encode($kurum_form['sehir'], JSON_UNESCAPED_UNICODE); ?>);
            setValue('kurum_ilce', <?php echo json_encode($kurum_form['ilce'], JSON_UNESCAPED_UNICODE); ?>);
            setValue('kurum_telefon', <?php echo json_encode($kurum_form['telefon'], JSON_UNESCAPED_UNICODE); ?>);
            setValue('kurum_eposta', <?php echo json_encode($kurum_form['eposta'], JSON_UNESCAPED_UNICODE); ?>);
            setValue('kurum_web_site', <?php echo json_encode($kurum_form['web_site'], JSON_UNESCAPED_UNICODE); ?>);
            setValue('kurum_instagram', <?php echo json_encode($kurum_form['instagram'], JSON_UNESCAPED_UNICODE); ?>);
            setValue('kurum_min_ay', <?php echo json_encode($kurum_form['min_ay'], JSON_UNESCAPED_UNICODE); ?>);
            setValue('kurum_max_ay', <?php echo json_encode($kurum_form['max_ay'], JSON_UNESCAPED_UNICODE); ?>);
            setValue('kurum_kurulus_yili', <?php echo json_encode($kurum_form['kurulus_yili'], JSON_UNESCAPED_UNICODE); ?>);
            setValue('kurum_ucret', <?php echo json_encode($kurum_form['ucret'], JSON_UNESCAPED_UNICODE); ?>);
            setValue('kurum_kapali_alan', <?php echo json_encode($kurum_form['kapali_alan'], JSON_UNESCAPED_UNICODE); ?>);
            setValue('kurum_acik_alan', <?php echo json_encode($kurum_form['acik_alan'], JSON_UNESCAPED_UNICODE); ?>);
            setValue('kurum_adres', <?php echo json_encode($kurum_form['adres'], JSON_UNESCAPED_UNICODE); ?>);
            setValue('kurum_hakkimizda', <?php echo json_encode($kurum_form['hakkimizda'], JSON_UNESCAPED_UNICODE); ?>);
            <?php if ($ozellik_kolon_var) { ?>
            setValue('kurum_ozellikler', <?php echo json_encode($kurum_form['ozellikler'], JSON_UNESCAPED_UNICODE); ?>);
            <?php } ?>
            setCheck('kurum_meb', <?php echo (int) $kurum_form['meb_onay']; ?>);
            setCheck('kurum_aile', <?php echo (int) $kurum_form['aile_sosyal_onay']; ?>);
            setCheck('kurum_bahce', <?php echo (int) $kurum_form['hizmet_bahceli']; ?>);
            setCheck('kurum_havuz', <?php echo (int) $kurum_form['hizmet_havuz']; ?>);
            setCheck('kurum_guvenlik', <?php echo (int) $kurum_form['hizmet_guvenlik']; ?>);
            setCheck('kurum_kamera', <?php echo (int) $kurum_form['hizmet_guvenlik_kamerasi']; ?>);
            setCheck('kurum_yemek', <?php echo (int) $kurum_form['hizmet_yemek']; ?>);
            setCheck('kurum_ingilizce', <?php echo (int) $kurum_form['hizmet_ingilizce']; ?>);
            setCheck('kurum_durum', <?php echo (int) $kurum_form['durum']; ?>);
            <?php } ?>
        })();
    </script>
</body>
</html>
