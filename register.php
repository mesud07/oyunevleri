<?php
// Veli kayit ekrani
require_once("includes/config.php");

$hata = '';
$basari = '';
$kurum_list = [];
$kurum_kodu_prefill = trim($_GET['kurum_kodu'] ?? '');
$kurum_adi_prefill = '';
$next_param = $_POST['next'] ?? ($_GET['next'] ?? '');

function guvenli_yonlendirme($next, $default = 'index.php') {
    $next = trim((string) $next);
    if ($next === '') {
        return $default;
    }
    if (preg_match('#^(https?:)?//#i', $next)) {
        return $default;
    }
    if (strpos($next, "\n") !== false || strpos($next, "\r") !== false) {
        return $default;
    }
    return $next;
}

if (!empty($db_master)) {
    $kurum_list = $db_master->query("SELECT id, kurum_adi, kurum_kodu FROM kurumlar WHERE durum = 1 ORDER BY kurum_adi")
        ->fetchAll(PDO::FETCH_ASSOC);
    if ($kurum_kodu_prefill !== '') {
        $stmt = $db_master->prepare("SELECT kurum_adi FROM kurumlar WHERE kurum_kodu = :kod AND durum = 1 LIMIT 1");
        $stmt->execute(['kod' => $kurum_kodu_prefill]);
        $kurum_adi_prefill = (string) ($stmt->fetchColumn() ?? '');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad_soyad = trim($_POST['ad_soyad'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $sifre = $_POST['sifre'] ?? '';
    $sifre_tekrar = $_POST['sifre_tekrar'] ?? '';
    $kurum_kodu = trim($_POST['kurum_kodu'] ?? '');

    if ($ad_soyad === '' || ($email === '' && $telefon === '') || $sifre === '') {
        $hata = 'Lütfen gerekli alanları doldurun.';
    } elseif ($sifre !== $sifre_tekrar) {
        $hata = 'Şifreler eşleşmiyor.';
    } elseif (strlen($sifre) < 6) {
        $hata = 'Şifre en az 6 karakter olmalıdır.';
    } elseif (empty($db)) {
        $hata = 'Kurum veritabani baglantisi bulunamadi.';
    } else {
        $kurum_id = 0;
        if ($kurum_kodu !== '' && !empty($db_master)) {
            $stmt = $db_master->prepare("SELECT id FROM kurumlar WHERE kurum_kodu = :kod AND durum = 1 LIMIT 1");
            $stmt->execute(['kod' => $kurum_kodu]);
            $kurum_id = (int) $stmt->fetchColumn();
            if ($kurum_id <= 0) {
                $hata = 'Kurum kodu bulunamadı.';
            }
        }

        if ($hata === '') {
            $stmt = $db->prepare("SELECT COUNT(*) FROM veliler WHERE (eposta = :email AND :email <> '') OR (telefon = :telefon AND :telefon <> '')");
            $stmt->execute(['email' => $email, 'telefon' => $telefon]);
            $var_mi = (int) $stmt->fetchColumn();
            if ($var_mi > 0) {
                $hata = 'Bu e-posta veya telefon ile kayıt zaten var.';
            } else {
                $stmt = $db->prepare("INSERT INTO veliler (kurum_id, sube_id, ad_soyad, telefon, eposta, sifre, bakiye_hak)
                    VALUES (:kurum_id, :sube_id, :ad_soyad, :telefon, :eposta, :sifre, 0)");
                $ok = $stmt->execute([
                    'kurum_id' => $kurum_id,
                    'sube_id' => null,
                    'ad_soyad' => $ad_soyad,
                    'telefon' => $telefon !== '' ? $telefon : null,
                    'eposta' => $email !== '' ? $email : null,
                    'sifre' => password_hash($sifre, PASSWORD_DEFAULT),
                ]);
                if (!$ok) {
                    $err = $stmt->errorInfo();
                    error_log('Veli kayit hata: ' . implode(' | ', $err));
                    $hata = 'Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin.';
                } else {
                    $veli_id = (int) $db->lastInsertId();
                    if ($veli_id <= 0) {
                        error_log('Veli kayit hata: lastInsertId bos geldi.');
                        $hata = 'Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin.';
                    } else {
                        $_SESSION['veli_giris'] = 1;
                        $_SESSION['veli_yeni'] = 1;
                        $_SESSION['veli'] = [
                            'id' => $veli_id,
                            'kurum_id' => $kurum_id,
                            'sube_id' => 0,
                            'ad_soyad' => $ad_soyad,
                            'telefon' => $telefon !== '' ? $telefon : null,
                            'eposta' => $email !== '' ? $email : null,
                            'bakiye_hak' => 0,
                        ];
                        $_SESSION['kurum_id'] = $kurum_id;
                        $hedef = guvenli_yonlendirme($next_param, 'index.php');
                        header("Location: {$hedef}");
                        exit;
                    }
                }
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
    <title>Oyunevleri.com | Veli Kayıt</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f9fafc;
            --ink: #1f2937;
            --muted: #6b7280;
            --primary: #ff7a59;
            --primary-dark: #ea6a4b;
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
            max-width: 1100px;
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
            grid-template-columns: 1.1fr 0.9fr;
            gap: 40px;
            align-items: center;
            padding: 20px 0 60px;
        }
        .hero h1 {
            font-family: "Baloo 2", cursive;
            font-size: 44px;
            margin: 0 0 10px;
        }
        .hero p {
            color: var(--muted);
            font-size: 17px;
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
        .text-muted {
            color: var(--muted);
            font-size: 12px;
        }
        .field input, .field select {
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
        @media (max-width: 980px) {
            .page { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header>
        <div class="container nav">
            <a class="logo" href="index.php">Oyunevleri.com</a>
            <div>
                <a class="btn btn-outline" href="login.php?next=<?php echo urlencode($next_param); ?>">Giriş Yap</a>
            </div>
        </div>
    </header>

    <section class="container page">
        <div class="hero">
            <h1>Veli Kayıt</h1>
            <p>Oyun evlerini takip etmek, rezervasyon yapmak ve haklarını yönetmek için kayıt olun.</p>
        </div>

        <div class="card">
            <?php if ($hata !== '') { ?>
                <div class="alert"><?php echo $hata; ?></div>
            <?php } ?>
            <form method="post" action="register.php">
                <input type="hidden" name="next" value="<?php echo htmlspecialchars($next_param, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="field">
                    <label>Ad Soyad</label>
                    <input type="text" name="ad_soyad" required>
                </div>
                <div class="field">
                    <label>E-posta</label>
                    <input type="email" name="email">
                </div>
                <div class="field">
                    <label>Telefon</label>
                    <input type="text" name="telefon">
                </div>
                <div class="field">
                    <label>Kurum Kodu (opsiyonel)</label>
                    <?php if ($kurum_kodu_prefill !== '') { ?>
                        <input type="text" name="kurum_kodu" value="<?php echo htmlspecialchars($kurum_kodu_prefill, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                        <?php if ($kurum_adi_prefill !== '') { ?>
                            <small class="text-muted">Davet edilen kurum: <?php echo htmlspecialchars($kurum_adi_prefill, ENT_QUOTES, 'UTF-8'); ?></small>
                        <?php } ?>
                    <?php } else { ?>
                        <input type="text" name="kurum_kodu" placeholder="Örn. KRM001">
                    <?php } ?>
                </div>
                <div class="field">
                    <label>Şifre</label>
                    <input type="password" name="sifre" required>
                </div>
                <div class="field">
                    <label>Şifre Tekrar</label>
                    <input type="password" name="sifre_tekrar" required>
                </div>
                <button class="btn btn-primary" type="submit" style="width:100%;">Kayıt Ol</button>
            </form>
        </div>
    </section>
</body>
</html>
