<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

site_admin_giris_zorunlu();

if (empty($db_master)) {
    die('Master veritabani baglantisi bulunamadi.');
}

$is_admin = site_admin_admin_mi();
$mesaj = '';
$mesaj_tipi = '';
$edit_id = (int) ($_GET['edit_id'] ?? 0);

$form = [
    'baslik' => '',
    'aciklama' => '',
    'buton_etiket' => 'İNCELE',
    'link_url' => '',
    'sira' => 1,
    'aktif' => 1,
    'gorsel_yol' => '',
];

function slider_upload_dizini_hazirla() {
    $upload_base_dir = rtrim($GLOBALS['upload_base_dir'] ?? (__DIR__ . '/uploads'), '/');
    $upload_base_url = rtrim($GLOBALS['upload_base_url'] ?? '/uploads', '/');
    $upload_dir = $upload_base_dir . '/site_slider';
    $upload_url = $upload_base_url . '/site_slider';
    if (!is_dir($upload_base_dir)) {
        @mkdir($upload_base_dir, 0755, true);
    }
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0755, true);
    }
    return [$upload_dir, $upload_url];
}

function slider_dosya_sil($gorsel_yol) {
    $upload_base_dir = rtrim($GLOBALS['upload_base_dir'] ?? (__DIR__ . '/uploads'), '/');
    $upload_dir = $upload_base_dir . '/site_slider';
    $basename = basename((string) $gorsel_yol);
    if ($basename === '' || $basename === '.') {
        return;
    }
    $path = $upload_dir . '/' . $basename;
    if (is_file($path)) {
        @unlink($path);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        if (!$is_admin) {
            $mesaj = 'Bu işlem için yetkiniz yok.';
            $mesaj_tipi = 'error';
        } else {
            $sil_id = (int) ($_POST['sil_id'] ?? 0);
            if ($sil_id > 0) {
                $stmt = $db_master->prepare("SELECT gorsel_yol FROM site_slider WHERE id = :id");
                $stmt->execute(['id' => $sil_id]);
                $gorsel = $stmt->fetchColumn();
                $stmt = $db_master->prepare("DELETE FROM site_slider WHERE id = :id");
                $ok = $stmt->execute(['id' => $sil_id]);
                if ($ok) {
                    slider_dosya_sil($gorsel);
                    $mesaj = 'Slider silindi.';
                    $mesaj_tipi = 'success';
                    site_admin_log_ekle('slider_sil', $sil_id, 'Slider silindi.', 'slider');
                } else {
                    $mesaj = 'Slider silinemedi.';
                    $mesaj_tipi = 'error';
                }
            }
        }
    }

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $form['baslik'] = trim($_POST['baslik'] ?? '');
        $form['aciklama'] = trim($_POST['aciklama'] ?? '');
        $form['buton_etiket'] = trim($_POST['buton_etiket'] ?? '');
        $form['link_url'] = trim($_POST['link_url'] ?? '');
        $form['sira'] = (int) ($_POST['sira'] ?? 1);
        $form['aktif'] = !empty($_POST['aktif']) ? 1 : 0;

        $dosya_var = !empty($_FILES['gorsel']) && is_array($_FILES['gorsel']) && ($_FILES['gorsel']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;

        if ($form['baslik'] === '') {
            $mesaj = 'Başlık zorunludur.';
            $mesaj_tipi = 'error';
        } elseif ($id === 0 && !$dosya_var) {
            $mesaj = 'Yeni slider için görsel zorunludur.';
            $mesaj_tipi = 'error';
        } else {
            $gorsel_yol = null;
            if ($dosya_var) {
                list($upload_dir, $upload_url) = slider_upload_dizini_hazirla();
                if (!is_dir($upload_dir) || !is_writable($upload_dir)) {
                    $mesaj = 'Yükleme dizini yazılabilir değil: ' . $upload_dir;
                    $mesaj_tipi = 'error';
                } else {
                    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
                    $allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];
                    $ext = strtolower(pathinfo($_FILES['gorsel']['name'] ?? '', PATHINFO_EXTENSION));
                    $tmp_name = $_FILES['gorsel']['tmp_name'] ?? '';
                    $mime = '';
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    if ($finfo) {
                        $mime = finfo_file($finfo, $tmp_name);
                        finfo_close($finfo);
                    }
                    if (!in_array($ext, $allowed_ext, true) || !in_array($mime, $allowed_mime, true)) {
                        $mesaj = 'Geçersiz dosya türü.';
                        $mesaj_tipi = 'error';
                    } else {
                        $filename = 'slider_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        $target_path = $upload_dir . '/' . $filename;
                        if (move_uploaded_file($tmp_name, $target_path)) {
                            $gorsel_yol = rtrim($upload_url, '/') . '/' . $filename;
                        } else {
                            $mesaj = 'Görsel yüklenemedi.';
                            $mesaj_tipi = 'error';
                        }
                    }
                }
            }

            if ($mesaj === '') {
                if ($id > 0) {
                    $sql = "UPDATE site_slider
                        SET baslik = :baslik,
                            aciklama = :aciklama,
                            buton_etiket = :buton_etiket,
                            link_url = :link_url,
                            sira = :sira,
                            aktif = :aktif";
                    $params = [
                        'baslik' => $form['baslik'],
                        'aciklama' => $form['aciklama'],
                        'buton_etiket' => $form['buton_etiket'],
                        'link_url' => $form['link_url'],
                        'sira' => $form['sira'],
                        'aktif' => $form['aktif'],
                        'id' => $id,
                    ];
                    if ($gorsel_yol !== null) {
                        $sql .= ", gorsel_yol = :gorsel_yol";
                        $params['gorsel_yol'] = $gorsel_yol;
                        $stmt = $db_master->prepare("SELECT gorsel_yol FROM site_slider WHERE id = :id");
                        $stmt->execute(['id' => $id]);
                        $old = $stmt->fetchColumn();
                        if ($old && $old !== $gorsel_yol) {
                            slider_dosya_sil($old);
                        }
                    }
                    $sql .= " WHERE id = :id";
                    $stmt = $db_master->prepare($sql);
                    $ok = $stmt->execute($params);
                    $mesaj = $ok ? 'Slider güncellendi.' : 'Slider güncellenemedi.';
                    $mesaj_tipi = $ok ? 'success' : 'error';
                    if ($ok) {
                        site_admin_log_ekle('slider_guncelle', $id, 'Slider güncellendi.', 'slider');
                    }
                } else {
                    $stmt = $db_master->prepare("INSERT INTO site_slider (baslik, aciklama, gorsel_yol, buton_etiket, link_url, sira, aktif)
                        VALUES (:baslik, :aciklama, :gorsel_yol, :buton_etiket, :link_url, :sira, :aktif)");
                    $ok = $stmt->execute([
                        'baslik' => $form['baslik'],
                        'aciklama' => $form['aciklama'],
                        'gorsel_yol' => $gorsel_yol,
                        'buton_etiket' => $form['buton_etiket'],
                        'link_url' => $form['link_url'],
                        'sira' => $form['sira'],
                        'aktif' => $form['aktif'],
                    ]);
                    $mesaj = $ok ? 'Slider eklendi.' : 'Slider eklenemedi.';
                    $mesaj_tipi = $ok ? 'success' : 'error';
                    if ($ok) {
                        $new_id = (int) $db_master->lastInsertId();
                        site_admin_log_ekle('slider_ekle', $new_id, 'Slider eklendi.', 'slider');
                        $form = [
                            'baslik' => '',
                            'aciklama' => '',
                            'buton_etiket' => 'İNCELE',
                            'link_url' => '',
                            'sira' => 1,
                            'aktif' => 1,
                            'gorsel_yol' => '',
                        ];
                    }
                }
            }
        }
    }
}

if ($edit_id > 0) {
    $stmt = $db_master->prepare("SELECT * FROM site_slider WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $edit_id]);
    $row = $stmt->fetch();
    if ($row) {
        $form['baslik'] = $row['baslik'] ?? '';
        $form['aciklama'] = $row['aciklama'] ?? '';
        $form['buton_etiket'] = $row['buton_etiket'] ?? '';
        $form['link_url'] = $row['link_url'] ?? '';
        $form['sira'] = (int) ($row['sira'] ?? 1);
        $form['aktif'] = (int) ($row['aktif'] ?? 1);
        $form['gorsel_yol'] = $row['gorsel_yol'] ?? '';
    }
}

$stmt = $db_master->query("SELECT * FROM site_slider ORDER BY sira ASC, id ASC");
$slider_list = $stmt->fetchAll();
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Site Admin - Mobil Slider</title>
    <?php require_once("includes/analytics.php"); ?>
    <style>
        :root { --bg:#f5f7fb; --ink:#1f2937; --muted:#6b7280; --card:#fff; --stroke:#e5e7eb; --primary:#ff7a59; }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bg); font-family: 'Manrope', Arial, sans-serif; color:var(--ink); }
        .container { max-width:1100px; margin:0 auto; padding:24px; }
        header { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:18px; }
        h1 { font-size:24px; margin:0; }
        .card { background:var(--card); border:1px solid var(--stroke); border-radius:16px; padding:18px; box-shadow:0 10px 22px rgba(15,23,42,0.06); }
        .btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:10px 14px; border-radius:10px; border:1px solid var(--stroke); background:#fff; cursor:pointer; text-decoration:none; color:var(--ink); font-weight:600; }
        .btn.primary { background:var(--primary); color:#fff; border-color:transparent; }
        .btn.light { background:#fff; }
        .btn.danger { background:#fee2e2; border-color:#fecaca; color:#b91c1c; }
        .d-flex { display:flex; gap:10px; flex-wrap:wrap; }
        .alert { padding:10px 12px; border-radius:10px; margin-bottom:14px; font-weight:600; }
        .alert.success { background:#e7f6ed; color:#1f7a46; }
        .alert.error { background:#fdecea; color:#b42318; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:10px; border-bottom:1px solid var(--stroke); text-align:left; font-size:14px; }
        th { font-size:12px; text-transform:uppercase; letter-spacing:0.4px; color:var(--muted); }
        .badge { display:inline-block; padding:4px 10px; border-radius:999px; font-size:12px; }
        .badge.active { background:#e7f6ed; color:#1f7a46; }
        .badge.passive { background:#f3f4f6; color:#6b7280; }
        .thumb { width:90px; height:56px; object-fit:cover; border-radius:8px; border:1px solid var(--stroke); }
        .grid { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:12px; }
        .field label { font-size:12px; color:var(--muted); margin-bottom:6px; display:block; }
        .field input, .field textarea { width:100%; padding:10px 12px; border-radius:10px; border:1px solid var(--stroke); font-size:14px; }
        textarea { min-height:90px; resize:vertical; }
        .modal-backdrop { position:fixed; inset:0; background:rgba(15,23,42,0.45); display:none; align-items:center; justify-content:center; padding:20px; z-index:9999; }
        .modal-backdrop.is-open { display:flex; }
        .modal-box { background:var(--card); border-radius:16px; padding:18px; width:100%; max-width:700px; max-height:90vh; overflow:auto; border:1px solid var(--stroke); }
        .modal-head { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:12px; }
        .modal-head h3 { margin:0; }
        @media (max-width: 860px) { .grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Site Admin - Mobil Slider</h1>
            <div class="d-flex">
                <a class="btn light" href="site_admin_kurumlar.php">Kurumlar</a>
                <a class="btn light" href="site_admin_kullanicilar.php">Kullanıcılar</a>
                <button class="btn primary" type="button" id="sliderYeniBtn">Yeni Slider</button>
                <a class="btn light" href="site_admin_slider.php">Yenile</a>
                <a class="btn primary" href="site_admin_logout.php">Çıkış</a>
            </div>
        </header>

        <?php if ($mesaj !== '') { ?>
            <div class="alert <?php echo $mesaj_tipi === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($mesaj, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php } ?>

        <div class="card">
            <h3>Slider Listesi</h3>
            <div style="overflow:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Görsel</th>
                            <th>Başlık</th>
                            <th>Sıra</th>
                            <th>Durum</th>
                            <th>Link</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($slider_list)) { ?>
                            <tr><td colspan="7">Slider bulunamadı.</td></tr>
                        <?php } else { ?>
                            <?php foreach ($slider_list as $slider) { ?>
                                <tr>
                                    <td><?php echo (int) $slider['id']; ?></td>
                                    <td>
                                        <?php if (!empty($slider['gorsel_yol'])) { ?>
                                            <img class="thumb" src="<?php echo htmlspecialchars($slider['gorsel_yol'], ENT_QUOTES, 'UTF-8'); ?>" alt="slider">
                                        <?php } else { ?>
                                            -
                                        <?php } ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($slider['baslik'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo (int) ($slider['sira'] ?? 0); ?></td>
                                    <td>
                                        <?php if ((int) ($slider['aktif'] ?? 0) === 1) { ?>
                                            <span class="badge active">Aktif</span>
                                        <?php } else { ?>
                                            <span class="badge passive">Pasif</span>
                                        <?php } ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($slider['link_url'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php
                                        $slider_json = htmlspecialchars(json_encode($slider, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                        ?>
                                        <button class="btn light btn-edit" type="button" data-slider="<?php echo $slider_json; ?>">Düzenle</button>
                                        <?php if ($is_admin) { ?>
                                            <form method="post" style="display:inline-block;" onsubmit="return confirm('Slider silinsin mi?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="sil_id" value="<?php echo (int) $slider['id']; ?>">
                                                <button class="btn danger" type="submit">Sil</button>
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

    <div class="modal-backdrop" id="sliderModal">
        <div class="modal-box">
            <div class="modal-head">
                <h3 id="sliderModalTitle">Yeni Slider</h3>
                <button class="btn light" type="button" id="sliderModalClose">Kapat</button>
            </div>
            <form method="post" id="sliderForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="slider_id" value="0">
                <div class="grid">
                    <div class="field">
                        <label>Başlık</label>
                        <input type="text" name="baslik" id="slider_baslik" required>
                    </div>
                    <div class="field">
                        <label>Sıra</label>
                        <input type="number" name="sira" id="slider_sira" value="1" min="1">
                    </div>
                    <div class="field">
                        <label>Buton Etiket</label>
                        <input type="text" name="buton_etiket" id="slider_buton" value="İNCELE">
                    </div>
                    <div class="field">
                        <label>Link (opsiyonel)</label>
                        <input type="text" name="link_url" id="slider_link" placeholder="https://">
                    </div>
                    <div class="field" style="grid-column: 1 / -1;">
                        <label>Açıklama</label>
                        <textarea name="aciklama" id="slider_aciklama"></textarea>
                    </div>
                    <div class="field" style="grid-column: 1 / -1;">
                        <label>Görsel (jpg/png/webp)</label>
                        <input type="file" name="gorsel" accept="image/png,image/jpeg,image/webp">
                        <div style="margin-top:8px; color:var(--muted); font-size:12px;">Yeni slider eklerken görsel zorunludur.</div>
                    </div>
                    <div class="field">
                        <label>Aktif</label>
                        <input type="checkbox" name="aktif" id="slider_aktif" checked>
                    </div>
                </div>
                <div style="margin-top:14px; display:flex; gap:10px;">
                    <button class="btn primary" type="submit">Kaydet</button>
                    <button class="btn light" type="button" id="sliderModalCancel">Vazgeç</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            var modal = document.getElementById('sliderModal');
            var openBtn = document.getElementById('sliderYeniBtn');
            var closeBtn = document.getElementById('sliderModalClose');
            var cancelBtn = document.getElementById('sliderModalCancel');
            var form = document.getElementById('sliderForm');
            var title = document.getElementById('sliderModalTitle');
            var editButtons = document.querySelectorAll('.btn-edit');

            function openModal() {
                modal.classList.add('is-open');
            }
            function closeModal() {
                modal.classList.remove('is-open');
            }

            if (openBtn) {
                openBtn.addEventListener('click', function () {
                    form.reset();
                    document.getElementById('slider_id').value = '0';
                    document.getElementById('slider_sira').value = '1';
                    document.getElementById('slider_aktif').checked = true;
                    title.textContent = 'Yeni Slider';
                    openModal();
                });
            }
            if (closeBtn) { closeBtn.addEventListener('click', closeModal); }
            if (cancelBtn) { cancelBtn.addEventListener('click', closeModal); }

            editButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var data = btn.getAttribute('data-slider');
                    if (!data) { return; }
                    try {
                        var slider = JSON.parse(data);
                        document.getElementById('slider_id').value = slider.id || 0;
                        document.getElementById('slider_baslik').value = slider.baslik || '';
                        document.getElementById('slider_aciklama').value = slider.aciklama || '';
                        document.getElementById('slider_buton').value = slider.buton_etiket || '';
                        document.getElementById('slider_link').value = slider.link_url || '';
                        document.getElementById('slider_sira').value = slider.sira || 1;
                        document.getElementById('slider_aktif').checked = (parseInt(slider.aktif || 0, 10) === 1);
                        title.textContent = 'Slider Düzenle';
                        openModal();
                    } catch (e) {
                        console.error(e);
                    }
                });
            });

            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    closeModal();
                }
            });
        })();
    </script>
</body>
</html>
