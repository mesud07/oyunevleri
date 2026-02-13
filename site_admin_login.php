<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$hata = '';
$basari = '';

if (site_admin_giris_var_mi()) {
    header('Location: site_admin_kurumlar.php');
    exit;
}

if (empty($db_master)) {
    die('Master veritabani baglantisi bulunamadi.');
}

$stmt = $db_master->query("SELECT COUNT(*) FROM site_admin_kullanicilar");
$admin_sayisi = (int) $stmt->fetchColumn();
$kurulum_modu = $admin_sayisi === 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'setup' && $kurulum_modu) {
        $ad_soyad = trim($_POST['ad_soyad'] ?? '');
        $kullanici_adi = trim($_POST['kullanici_adi'] ?? '');
        $sifre = $_POST['sifre'] ?? '';

        if ($ad_soyad === '' || $kullanici_adi === '' || $sifre === '') {
            $hata = 'Tüm alanlar zorunludur.';
        } else {
            $hash = password_hash($sifre, PASSWORD_DEFAULT);
            $stmt = $db_master->prepare("INSERT INTO site_admin_kullanicilar (ad_soyad, kullanici_adi, sifre, rol, aktif)
                VALUES (:ad_soyad, :kullanici_adi, :sifre, 'admin', 1)");
            $ok = $stmt->execute([
                'ad_soyad' => $ad_soyad,
                'kullanici_adi' => $kullanici_adi,
                'sifre' => $hash,
            ]);
            if ($ok) {
                $basari = 'Kurulum tamamlandi. Simdi giris yapabilirsiniz.';
                $kurulum_modu = false;
            } else {
                $hata = 'Kullanici olusturulamadi.';
            }
        }
    } elseif ($action === 'login') {
        $kullanici_adi = trim($_POST['kullanici_adi'] ?? '');
        $sifre = $_POST['sifre'] ?? '';

        $stmt = $db_master->prepare("SELECT * FROM site_admin_kullanicilar
            WHERE kullanici_adi = :kullanici_adi AND aktif = 1 LIMIT 1");
        $stmt->execute(['kullanici_adi' => $kullanici_adi]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($sifre, $admin['sifre'])) {
            $_SESSION['site_admin_giris'] = 1;
            $_SESSION['site_admin'] = [
                'id' => $admin['id'],
                'ad_soyad' => $admin['ad_soyad'],
                'rol' => $admin['rol'],
            ];
            header('Location: site_admin_kurumlar.php');
            exit;
        }
        $hata = 'Kullanici adi veya sifre hatali.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Site Admin Girişi</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&family=Manrope:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#f6f7fb; --ink:#1f2937; --muted:#6b7280; --primary:#ff7a59; --card:#fff; --stroke:rgba(31,41,55,0.08); }
        * { box-sizing: border-box; }
        body { margin:0; font-family:"Manrope",system-ui; background:var(--bg); color:var(--ink); }
        .container { max-width:520px; margin:0 auto; padding:48px 20px; }
        .card { background:var(--card); border:1px solid var(--stroke); border-radius:18px; padding:24px; box-shadow:0 16px 30px rgba(31,41,55,0.08); }
        h1 { font-family:"Baloo 2",cursive; margin:0 0 8px; }
        p { color:var(--muted); margin:0 0 16px; }
        .field { display:flex; flex-direction:column; gap:6px; margin-bottom:12px; }
        .field label { font-size:13px; color:var(--muted); }
        .field input { padding:12px 14px; border-radius:12px; border:1px solid var(--stroke); }
        .btn { border:none; padding:12px 16px; border-radius:12px; background:var(--primary); color:#fff; font-weight:700; cursor:pointer; width:100%; }
        .alert { padding:10px 12px; border-radius:12px; margin-bottom:12px; font-weight:600; }
        .alert.error { background:#ffe8e3; color:#8a2b17; border:1px solid #ffc9bc; }
        .alert.success { background:#e7f6ed; color:#1f7a46; border:1px solid #c9ecd8; }
        .switch { margin-top:12px; text-align:center; font-size:14px; }
        .switch a { color:var(--primary); text-decoration:none; font-weight:700; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Oyunevleri.com Site Admin</h1>
            <p><?php echo $kurulum_modu ? 'İlk yönetici hesabını oluşturun.' : 'Site admin hesabınızla giriş yapın.'; ?></p>

            <?php if ($hata !== '') { ?>
                <div class="alert error"><?php echo htmlspecialchars($hata, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php } ?>
            <?php if ($basari !== '') { ?>
                <div class="alert success"><?php echo htmlspecialchars($basari, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php } ?>

            <?php if ($kurulum_modu) { ?>
                <form method="post">
                    <input type="hidden" name="action" value="setup">
                    <div class="field">
                        <label>Ad Soyad</label>
                        <input type="text" name="ad_soyad" required>
                    </div>
                    <div class="field">
                        <label>Kullanıcı Adı</label>
                        <input type="text" name="kullanici_adi" required>
                    </div>
                    <div class="field">
                        <label>Şifre</label>
                        <input type="password" name="sifre" required>
                    </div>
                    <button class="btn" type="submit">İlk Admini Oluştur</button>
                </form>
            <?php } else { ?>
                <form method="post">
                    <input type="hidden" name="action" value="login">
                    <div class="field">
                        <label>Kullanıcı Adı</label>
                        <input type="text" name="kullanici_adi" required>
                    </div>
                    <div class="field">
                        <label>Şifre</label>
                        <input type="password" name="sifre" required>
                    </div>
                    <button class="btn" type="submit">Giriş Yap</button>
                </form>
            <?php } ?>
        </div>
    </div>
</body>
</html>
