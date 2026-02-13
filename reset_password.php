<?php
require_once('includes/config.php');

$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));
$hata = '';
$basari = '';

if ($token === '') {
    $hata = 'Geçersiz şifre sıfırlama bağlantısı.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hata === '') {
    $sifre = $_POST['sifre'] ?? '';
    $sifre_tekrar = $_POST['sifre_tekrar'] ?? '';

    if ($sifre === '' || $sifre_tekrar === '') {
        $hata = 'Lütfen şifre alanlarını doldurun.';
    } elseif ($sifre !== $sifre_tekrar) {
        $hata = 'Şifreler eşleşmiyor.';
    } elseif (strlen($sifre) < 6) {
        $hata = 'Şifre en az 6 karakter olmalıdır.';
    } elseif (empty($db)) {
        $hata = 'Kurum veritabani baglantisi bulunamadi.';
    } else {
        $token_hash = hash('sha256', $token);
        $stmt = $db->prepare("SELECT id, veli_id, expires_at, used_at FROM password_resets
            WHERE token_hash = :hash AND used_at IS NULL AND expires_at >= NOW()
            ORDER BY id DESC LIMIT 1");
        $stmt->execute(['hash' => $token_hash]);
        $row = $stmt->fetch();

        if (!$row) {
            $hata = 'Şifre sıfırlama bağlantısı geçersiz veya süresi dolmuş.';
        } else {
            try {
                $db->beginTransaction();
                $db->prepare("UPDATE veliler SET sifre = :sifre WHERE id = :id")
                    ->execute([
                        'sifre' => password_hash($sifre, PASSWORD_DEFAULT),
                        'id' => (int) $row['veli_id'],
                    ]);
                $db->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = :id")
                    ->execute(['id' => (int) $row['id']]);
                $db->commit();
                $basari = 'Şifreniz başarıyla güncellendi. Giriş yapabilirsiniz.';
            } catch (PDOException $e) {
                $db->rollBack();
                error_log('Reset sifre hata: ' . $e->getMessage());
                $hata = 'Şifre güncellenemedi. Lütfen tekrar deneyin.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Oyunevleri.com | Şifre Yenile</title>
    <?php require_once("includes/analytics.php"); ?>
    <link rel="icon" type="image/x-icon" href="favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f9fafc;
            --ink: #1f2937;
            --muted: #6b7280;
            --primary: #ff7a59;
            --card: #ffffff;
            --stroke: rgba(31,41,55,0.08);
            --shadow: 0 20px 45px rgba(31,41,55,0.12);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Manrope", system-ui, -apple-system, sans-serif;
            color: var(--ink);
            background: radial-gradient(1200px 600px at 10% -10%, #ffe6dd 0%, transparent 60%),
                        radial-gradient(900px 400px at 100% 0%, #dff5f2 0%, transparent 65%),
                        var(--bg);
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 24px;
        }
        header {
            padding: 24px 0 12px;
        }
        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        .logo {
            font-family: "Baloo 2", cursive;
            font-size: 28px;
            font-weight: 700;
            color: var(--ink);
            text-decoration: none;
        }
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
        .page {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            align-items: center;
            padding: 20px 0 60px;
        }
        .hero h1 {
            font-family: "Baloo 2", cursive;
            font-size: 40px;
            margin: 0 0 10px;
        }
        .hero p {
            color: var(--muted);
            font-size: 16px;
            line-height: 1.6;
        }
        .card {
            background: var(--card);
            border-radius: 20px;
            padding: 20px;
            border: 1px solid var(--stroke);
            box-shadow: var(--shadow);
        }
        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 12px;
        }
        .field label {
            font-size: 13px;
            color: var(--muted);
        }
        .field input {
            border-radius: 12px;
            border: 1px solid var(--stroke);
            padding: 12px 14px;
            font-size: 15px;
            outline: none;
            background: #fff;
        }
        .alert {
            background: #ffe8e3;
            border: 1px solid #ffc9bc;
            color: #8a2b17;
            padding: 10px 12px;
            border-radius: 12px;
            margin-bottom: 12px;
            font-size: 14px;
        }
        .success {
            background: #e7f6ed;
            border: 1px solid #c9ecd8;
            color: #1f7a46;
        }
    </style>
</head>
<body>
    <header>
        <div class="container nav">
            <a class="logo" href="index.php">Oyunevleri.com</a>
            <div>
                <a class="btn btn-outline" href="login.php">Giriş Yap</a>
            </div>
        </div>
    </header>

    <section class="container page">
        <div class="hero">
            <h1>Şifre Yenile</h1>
            <p>Yeni şifrenizi belirleyin.</p>
        </div>

        <div class="card">
            <?php if ($hata !== '') { ?>
                <div class="alert"><?php echo $hata; ?></div>
            <?php } ?>
            <?php if ($basari !== '') { ?>
                <div class="alert success"><?php echo $basari; ?></div>
                <a class="btn btn-primary" href="login.php">Giriş Yap</a>
            <?php } else { ?>
                <form method="post" action="reset_password.php">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="field">
                        <label>Yeni Şifre</label>
                        <input type="password" name="sifre" required>
                    </div>
                    <div class="field">
                        <label>Şifre Tekrar</label>
                        <input type="password" name="sifre_tekrar" required>
                    </div>
                    <button class="btn btn-primary" type="submit" style="width:100%;">Şifreyi Güncelle</button>
                </form>
            <?php } ?>
        </div>
    </section>
</body>
</html>
