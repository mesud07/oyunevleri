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
$sil_id = (int) ($_POST['sil_id'] ?? 0);

$form = [
    'ad_soyad' => '',
    'kullanici_adi' => '',
    'rol' => 'editor',
    'aktif' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!$is_admin) {
        $mesaj = 'Bu işlem için yetkiniz yok.';
        $mesaj_tipi = 'error';
    } elseif ($action === 'delete' && $sil_id > 0) {
        if ($sil_id === (int) ($_SESSION['site_admin']['id'] ?? 0)) {
            $mesaj = 'Kendi hesabınızı silemezsiniz.';
            $mesaj_tipi = 'error';
        } else {
        try {
            $stmt = $db_master->prepare("DELETE FROM site_admin_kullanicilar WHERE id = :id");
            $stmt->execute(['id' => $sil_id]);
            $mesaj = 'Kullanıcı silindi.';
            $mesaj_tipi = 'success';
            site_admin_log_ekle('kullanici_sil', $sil_id, 'Site admin kullanicisi silindi.', 'user');
        } catch (PDOException $e) {
            $mesaj = 'Kullanıcı silinemedi.';
            $mesaj_tipi = 'error';
            error_log('Site admin silme hata: ' . $e->getMessage());
        }
        }
    } elseif ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $form['ad_soyad'] = trim($_POST['ad_soyad'] ?? '');
        $form['kullanici_adi'] = trim($_POST['kullanici_adi'] ?? '');
        $form['rol'] = $_POST['rol'] ?? 'editor';
        $form['aktif'] = !empty($_POST['aktif']) ? 1 : 0;
        $sifre = $_POST['sifre'] ?? '';

        if ($form['ad_soyad'] === '' || $form['kullanici_adi'] === '') {
            $mesaj = 'Ad soyad ve kullanıcı adı zorunludur.';
            $mesaj_tipi = 'error';
        } else {
            if ($id > 0) {
                $sql = "UPDATE site_admin_kullanicilar
                    SET ad_soyad = :ad_soyad,
                        kullanici_adi = :kullanici_adi,
                        rol = :rol,
                        aktif = :aktif";
                if ($sifre !== '') {
                    $sql .= ", sifre = :sifre";
                }
                $sql .= " WHERE id = :id";
                $stmt = $db_master->prepare($sql);
                $params = [
                    'ad_soyad' => $form['ad_soyad'],
                    'kullanici_adi' => $form['kullanici_adi'],
                    'rol' => $form['rol'],
                    'aktif' => $form['aktif'],
                    'id' => $id,
                ];
                if ($sifre !== '') {
                    $params['sifre'] = password_hash($sifre, PASSWORD_DEFAULT);
                }
                $ok = $stmt->execute($params);
                $mesaj = $ok ? 'Kullanıcı güncellendi.' : 'Kullanıcı güncellenemedi.';
                $mesaj_tipi = $ok ? 'success' : 'error';
                if ($ok) {
                    site_admin_log_ekle('kullanici_guncelle', $id, 'Site admin kullanicisi guncellendi.', 'user');
                }
            } else {
                if ($sifre === '') {
                    $mesaj = 'Şifre zorunludur.';
                    $mesaj_tipi = 'error';
                } else {
                    $stmt = $db_master->prepare("INSERT INTO site_admin_kullanicilar (ad_soyad, kullanici_adi, sifre, rol, aktif)
                        VALUES (:ad_soyad, :kullanici_adi, :sifre, :rol, :aktif)");
                    $ok = $stmt->execute([
                        'ad_soyad' => $form['ad_soyad'],
                        'kullanici_adi' => $form['kullanici_adi'],
                        'sifre' => password_hash($sifre, PASSWORD_DEFAULT),
                        'rol' => $form['rol'],
                        'aktif' => $form['aktif'],
                    ]);
                    $mesaj = $ok ? 'Kullanıcı eklendi.' : 'Kullanıcı eklenemedi.';
                    $mesaj_tipi = $ok ? 'success' : 'error';
                    if ($ok) {
                        $new_id = (int) $db_master->lastInsertId();
                        site_admin_log_ekle('kullanici_ekle', $new_id, 'Site admin kullanicisi eklendi.', 'user');
                    }
                    if ($ok) {
                        $form = [
                            'ad_soyad' => '',
                            'kullanici_adi' => '',
                            'rol' => 'editor',
                            'aktif' => 1,
                        ];
                    }
                }
            }
        }
    }
}

if ($edit_id > 0) {
    $stmt = $db_master->prepare("SELECT * FROM site_admin_kullanicilar WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $edit_id]);
    $row = $stmt->fetch();
    if ($row) {
        $form['ad_soyad'] = $row['ad_soyad'] ?? '';
        $form['kullanici_adi'] = $row['kullanici_adi'] ?? '';
        $form['rol'] = $row['rol'] ?? 'editor';
        $form['aktif'] = (int) ($row['aktif'] ?? 1);
    }
}

$stmt = $db_master->query("SELECT id, ad_soyad, kullanici_adi, rol, aktif, olusturma_tarihi FROM site_admin_kullanicilar ORDER BY id DESC");
$adminler = $stmt->fetchAll();

$loglar = [];
$log_user_id = (int) ($_GET['log_user_id'] ?? 0);
if ($is_admin) {
    $log_sql = "SELECT l.*, u.ad_soyad AS admin_adi
        FROM site_admin_loglar l
        LEFT JOIN site_admin_kullanicilar u ON u.id = l.admin_id
        WHERE l.hedef_tur = 'user'";
    $log_params = [];
    if ($log_user_id > 0) {
        $log_sql .= " AND l.hedef_id = :log_user_id";
        $log_params['log_user_id'] = $log_user_id;
    }
    $log_sql .= " ORDER BY l.olusturma_tarihi DESC LIMIT 200";
    $stmt = $db_master->prepare($log_sql);
    $stmt->execute($log_params);
    $loglar = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Site Admin - Kullanıcılar</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&family=Manrope:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#f6f7fb; --ink:#1f2937; --muted:#6b7280; --primary:#ff7a59; --card:#fff; --stroke:rgba(31,41,55,0.08); }
        * { box-sizing: border-box; }
        body { margin:0; font-family:"Manrope",system-ui; background:var(--bg); color:var(--ink); }
        .container { max-width:1100px; margin:0 auto; padding:32px 20px 60px; }
        header { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:20px; }
        h1 { font-family:"Baloo 2",cursive; margin:0; }
        .btn { border:none; padding:10px 16px; border-radius:12px; font-weight:700; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
        .btn.primary { background:var(--primary); color:#fff; }
        .btn.light { background:#fff; color:var(--ink); border:1px solid var(--stroke); }
        .card { background:var(--card); border:1px solid var(--stroke); border-radius:18px; padding:18px; box-shadow:0 12px 22px rgba(31,41,55,0.08); margin-bottom:18px; }
        .alert { padding:10px 12px; border-radius:12px; font-weight:600; margin-bottom:12px; }
        .alert.success { background:#e7f6ed; color:#1f7a46; border:1px solid #c9ecd8; }
        .alert.error { background:#ffe8e3; color:#8a2b17; border:1px solid #ffc9bc; }
        .grid { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:12px; }
        .field { display:flex; flex-direction:column; gap:6px; }
        .field label { font-size:13px; color:var(--muted); }
        .field input, .field select { border:1px solid var(--stroke); border-radius:12px; padding:10px 12px; font-size:14px; }
        table { width:100%; border-collapse: collapse; }
        th, td { text-align:left; padding:10px 8px; border-bottom:1px solid rgba(31,41,55,0.08); font-size:14px; }
        th { font-size:12px; text-transform:uppercase; letter-spacing:0.4px; color:var(--muted); }
        .badge { display:inline-block; padding:4px 10px; border-radius:999px; font-size:12px; }
        .badge.active { background:#e7f6ed; color:#1f7a46; }
        .badge.passive { background:#f3f4f6; color:#6b7280; }
        @media (max-width: 900px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Site Admin Kullanıcıları</h1>
            <div class="d-flex">
                <a class="btn light" href="site_admin_kurumlar.php">Kurumlar</a>
                <a class="btn primary" href="site_admin_logout.php">Çıkış</a>
            </div>
        </header>

        <?php if ($mesaj !== '') { ?>
            <div class="alert <?php echo $mesaj_tipi === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($mesaj, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php } ?>

        <?php if (!$is_admin) { ?>
            <div class="card">
                Bu sayfaya erişim için admin yetkisi gereklidir.
            </div>
        <?php } else { ?>
            <div class="card">
                <h3><?php echo $edit_id > 0 ? 'Kullanıcı Güncelle' : 'Yeni Kullanıcı Ekle'; ?></h3>
                <form method="post">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?php echo (int) $edit_id; ?>">
                    <div class="grid">
                        <div class="field">
                            <label>Ad Soyad</label>
                            <input type="text" name="ad_soyad" required value="<?php echo htmlspecialchars($form['ad_soyad'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="field">
                            <label>Kullanıcı Adı</label>
                            <input type="text" name="kullanici_adi" required value="<?php echo htmlspecialchars($form['kullanici_adi'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="field">
                            <label>Rol</label>
                            <select name="rol">
                                <option value="admin" <?php echo $form['rol'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="editor" <?php echo $form['rol'] === 'editor' ? 'selected' : ''; ?>>Editor</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Durum</label>
                            <select name="aktif">
                                <option value="1" <?php echo $form['aktif'] ? 'selected' : ''; ?>>Aktif</option>
                                <option value="0" <?php echo !$form['aktif'] ? 'selected' : ''; ?>>Pasif</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Şifre <?php echo $edit_id > 0 ? '(değiştirmek için doldurun)' : ''; ?></label>
                            <input type="password" name="sifre" <?php echo $edit_id > 0 ? '' : 'required'; ?>>
                        </div>
                    </div>
                    <div style="margin-top:16px;">
                        <button class="btn primary" type="submit"><?php echo $edit_id > 0 ? 'Güncelle' : 'Kaydet'; ?></button>
                        <?php if ($edit_id > 0) { ?>
                            <a class="btn light" href="site_admin_kullanicilar.php">Yeni kayıt</a>
                        <?php } ?>
                    </div>
                </form>
            </div>
        <?php } ?>

        <div class="card">
            <h3>Kullanıcı Listesi</h3>
            <div style="overflow:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ad Soyad</th>
                            <th>Kullanıcı Adı</th>
                            <th>Rol</th>
                            <th>Durum</th>
                            <th>Oluşturma</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($adminler)) { ?>
                            <tr><td colspan="7">Kayıt bulunamadı.</td></tr>
                        <?php } else { ?>
                            <?php foreach ($adminler as $admin) { ?>
                                <tr>
                                    <td><?php echo (int) $admin['id']; ?></td>
                                    <td><?php echo htmlspecialchars($admin['ad_soyad'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($admin['kullanici_adi'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($admin['rol'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if ((int) ($admin['aktif'] ?? 0) === 1) { ?>
                                            <span class="badge active">Aktif</span>
                                        <?php } else { ?>
                                            <span class="badge passive">Pasif</span>
                                        <?php } ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($admin['olusturma_tarihi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <a class="btn light" href="site_admin_kullanicilar.php?edit_id=<?php echo (int) $admin['id']; ?>">Düzenle</a>
                                        <?php if ($is_admin) { ?>
                                            <form method="post" style="display:inline-block;" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="sil_id" value="<?php echo (int) $admin['id']; ?>">
                                                <button class="btn light" type="submit">Sil</button>
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

        <?php if ($is_admin) { ?>
            <div class="card">
                <h3>İşlem Geçmişi</h3>
                <form method="get" style="margin-bottom:12px;">
                    <label>Hedef Kullanıcı</label>
                    <select name="log_user_id" style="margin-left:8px;">
                        <option value="">Tümü</option>
                        <?php foreach ($adminler as $adm) { ?>
                            <option value="<?php echo (int) $adm['id']; ?>" <?php echo $log_user_id === (int) $adm['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($adm['ad_soyad'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <button class="btn light" type="submit">Filtrele</button>
                </form>
                <div style="overflow:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Tarih</th>
                                <th>Admin</th>
                                <th>İşlem</th>
                                <th>Hedef ID</th>
                                <th>Detay</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($loglar)) { ?>
                                <tr><td colspan="6">Log kaydı bulunamadı.</td></tr>
                            <?php } else { ?>
                                <?php foreach ($loglar as $log) { ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['olusturma_tarihi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($log['admin_adi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($log['islem'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($log['hedef_id'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($log['detay'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($log['ip'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php } ?>
    </div>
</body>
</html>
