<?php
require_once "includes/config.php";
$hata_mesaji = '';
$hata_tipi = '';
$login_tipi = $_POST['login_tipi'] ?? ($_GET['login_tipi'] ?? 'veli');
$next_param = $_POST['next'] ?? ($_GET['next'] ?? '');
$kurum_list = [];

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

if (!empty($_SESSION['google_login_error'])) {
    $hata_mesaji = $_SESSION['google_login_error'];
    $hata_tipi = 'veli';
    unset($_SESSION['google_login_error']);
}

if (!empty($db_master)) {
    $kurum_list = $db_master->query("SELECT id, kurum_adi, kurum_kodu FROM kurumlar WHERE durum = 1 ORDER BY kurum_adi")
        ->fetchAll(PDO::FETCH_ASSOC);
}

if (!empty($_SESSION['db_hata'])) {
    $hata_mesaji = 'Veritabani baglantisi yapilamadi. Lutfen sistem yoneticisine bildiriniz.';
    unset($_SESSION['db_hata']);
}

if ($login_tipi === 'yonetici' && !empty($_POST['kurum_kodu']) && !empty($_POST['kullanici_adi']) && !empty($_POST['sifre'])) {
    $kurum_kodu = trim($_POST['kurum_kodu']);
    $kullanici_adi = trim($_POST['kullanici_adi']);
    $sifre = $_POST['sifre'];

    // kurum bilgisi master db'den bulunur
    if (empty($db_master)) {
        $hata_mesaji = 'Master veritabani baglantisi bulunamadi.';
    } else {
        $kurum_sorgu = $db_master->prepare("SELECT id, kurum_adi, durum
        FROM kurumlar
        WHERE kurum_kodu = :kurum_kodu
        LIMIT 1");
        $kurum_sorgu->execute(['kurum_kodu' => $kurum_kodu]);
        $kurum = $kurum_sorgu->fetch();

        if (!$kurum) {
            $hata_mesaji = 'Kurum kodu bulunamadi.';
        } elseif ((int) $kurum['durum'] !== 1) {
            $hata_mesaji = 'Kurum pasif durumdadir.';
        } else {
            $_SESSION['kurum_db'] = $_SESSION['kurum_db'] ?? 'oyunev_kurum';

            // kullanici dogrulama master db'den yapilir
            $kullanici_sorgu = $db_master->prepare("SELECT *
                FROM kullanicilar
                WHERE kurum_id = :kurum_id
                  AND kullanici_adi = :kullanici_adi
                LIMIT 1");
            $kullanici_sorgu->execute([
                'kurum_id' => (int) $kurum['id'],
                'kullanici_adi' => $kullanici_adi,
            ]);
            $kullanici = $kullanici_sorgu->fetch();

            if ($kullanici && password_verify($sifre, $kullanici['sifre'])) {
                if (!in_array($kullanici['yetki_seviyesi'], ['merkez_admin', 'sube_admin', 'egitmen'], true)) {
                    $hata_mesaji = 'Bu sayfa sadece kurum yöneticileri ve eğitmenler içindir.';
                    $hata_tipi = 'yonetici';
                } else {
                $_SESSION['giris'] = 1;
                $_SESSION['kurum_id'] = $kurum['id'];
                $_SESSION['kurum_adi'] = $kurum['kurum_adi'];
                $_SESSION['kullanici'] = $kullanici;

                if (empty($db)) {
                    $hata_mesaji = 'Kurum veritabani baglantisi bulunamadi.';
                    $hata_tipi = 'yonetici';
                } else {
                    $kullanici_sube_id = (int) $kullanici['sube_id'];
                    if ($kullanici['yetki_seviyesi'] === 'merkez_admin') {
                        $_SESSION['sube_id'] = $kullanici_sube_id;
                        $_SESSION['sube_adi'] = $_SESSION['sube_adi'] ?? '';
                        header("Location: modules/dashboard.php");
                        exit;
                    } elseif ($kullanici_sube_id > 0) {
                        $sube_sorgu = $db->prepare("SELECT id, sube_adi FROM subeler WHERE id = :id AND kurum_id = :kurum_id LIMIT 1");
                        $sube_sorgu->execute([
                            'id' => $kullanici_sube_id,
                            'kurum_id' => (int) $kurum['id'],
                        ]);
                        $sube = $sube_sorgu->fetch();
                        if ($sube) {
                            $_SESSION['sube_id'] = $sube['id'];
                            $_SESSION['sube_adi'] = $sube['sube_adi'];
                            header("Location: modules/dashboard.php");
                            exit;
                        }
                        $hata_mesaji = 'Sube yetkisi bulunamadi.';
                        $hata_tipi = 'yonetici';
                    } else {
                        $hata_mesaji = 'Sube yetkisi bulunamadi.';
                        $hata_tipi = 'yonetici';
                    }
                }
                }
            } else {
                $hata_mesaji = 'Kullanici adi veya sifre hatali.';
                $hata_tipi = 'yonetici';
            }
        }
    }
}

if ($login_tipi === 'veli' && !empty($_POST['kimlik']) && !empty($_POST['sifre'])) {
    $kimlik = trim($_POST['kimlik']);
    $sifre = $_POST['sifre'];

    if (empty($db)) {
        $hata_mesaji = 'Kurum veritabani baglantisi bulunamadi.';
        $hata_tipi = 'veli';
    } else {
        $stmt = $db->prepare("SELECT * FROM veliler
            WHERE (eposta = :kimlik OR telefon = :kimlik)
            LIMIT 1");
        $stmt->execute(['kimlik' => $kimlik]);
        $veli = $stmt->fetch();

        if ($veli && !empty($veli['sifre']) && password_verify($sifre, $veli['sifre'])) {
            $_SESSION['veli_giris'] = 1;
            $_SESSION['veli'] = $veli;
            $_SESSION['kurum_id'] = (int) ($veli['kurum_id'] ?? 0);
            $hedef = guvenli_yonlendirme($next_param, 'index.php');
            header("Location: {$hedef}");
            exit;
        }

        $hata_mesaji = 'Veli girişi başarısız. Kayıtlı değilseniz kayıt olun.';
        $hata_tipi = 'veli';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Oyunevleri.com | Kurum Girişi</title>
    <?php require_once("includes/analytics.php"); ?>
    <link rel="icon" type="image/x-icon" href="favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f9fafc;
            --ink: #1f2937;
            --muted: #6b7280;
            --primary: #ff7a59;
            --primary-dark: #ea6a4b;
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
        .nav-actions {
            display: flex;
            gap: 10px;
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
        .tips {
            margin-top: 18px;
            display: grid;
            gap: 10px;
        }
        .tip {
            background: #fff;
            border-radius: 14px;
            padding: 12px 14px;
            border: 1px solid var(--stroke);
        }
        .card {
            background: var(--card);
            border-radius: 20px;
            padding: 20px;
            border: 1px solid var(--stroke);
            box-shadow: var(--shadow);
        }
        .card h2 {
            margin: 0 0 6px;
        }
        .card p {
            margin: 0 0 16px;
            color: var(--muted);
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
        .toggle {
            margin-top: 12px;
            text-align: center;
            font-size: 14px;
            color: var(--muted);
        }
        .toggle button {
            background: none;
            border: none;
            color: var(--primary-dark);
            font-weight: 600;
            cursor: pointer;
        }
        .hidden {
            display: none;
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
            <div class="nav-actions">
                <a class="btn btn-outline" href="index.php">Pazaryeri</a>
                <a class="btn btn-primary" href="https://app.oyunevleri.com/login.php">Uygulama</a>
            </div>
        </div>
    </header>

    <section class="container page">
        <div id="hero-veli" class="hero <?php echo ($login_tipi === 'yonetici') ? 'hidden' : ''; ?>">
            <h1>Veli Girişi</h1>
            <p>Rezervasyonlarınızı, haklarınızı ve grup takviminizi tek ekrandan yönetin.</p>
            <div class="tips">
                <div class="tip">Kayıtlı değilseniz hızlıca üyelik oluşturabilirsiniz.</div>
                <div class="tip">Kurumunuzdan davet aldıysanız link üzerinden kolayca katılabilirsiniz.</div>
            </div>
        </div>

        <div id="hero-yonetici" class="hero <?php echo ($login_tipi === 'yonetici') ? '' : 'hidden'; ?>">
            <h1>Kurum Paneli Girişi</h1>
            <p>Kurum yöneticileri ve eğitmenler için güvenli giriş ekranı. Kurum kodu ile erişim sağlanır.</p>
            <div class="tips">
                <div class="tip">Kurum kodunuzu işletme yöneticinizden alabilirsiniz.</div>
                <div class="tip">Şifrenizi unuttuysanız yöneticinizle iletişime geçin.</div>
            </div>
        </div>

        <div class="card">
            <div id="veli-form" class="<?php echo ($login_tipi === 'yonetici') ? 'hidden' : ''; ?>">
                <h2>Veli Girişi</h2>
                <p>Oyunevleri.com kullanıcıları için giriş</p>

                <?php if (!empty($hata_mesaji) && $hata_tipi === 'veli') { ?>
                    <div class="alert"><?php echo $hata_mesaji; ?></div>
                <?php } ?>

                <form method="post" action="login.php">
                    <input type="hidden" name="login_tipi" value="veli">
                    <input type="hidden" name="next" value="<?php echo htmlspecialchars($next_param, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="field">
                        <label>E-posta veya Telefon</label>
                        <input type="text" name="kimlik" required>
                    </div>
                    <div class="field">
                        <label>Şifreniz</label>
                        <input type="password" name="sifre" required>
                    </div>
                    <button class="btn btn-primary" type="submit" style="width:100%;">Giriş Yap</button>
                </form>

                <div class="toggle" style="margin-top:10px;">
                    <a href="forgot_password.php" style="color:var(--primary-dark);font-weight:600;text-decoration:none;">Şifremi Unuttum</a>
                </div>

                <div class="toggle" style="margin-top:10px;">
                    Hesabın yok mu? <a href="register.php?next=<?php echo urlencode($next_param); ?>" style="color:var(--primary-dark);font-weight:600;text-decoration:none;">Kayıt Ol</a>
                </div>

                <?php if (!empty($google_client_id)) { ?>
                    <div style="margin-top:14px;text-align:center;">
                        <div id="g_id_onload"
                             data-client_id="<?php echo htmlspecialchars($google_client_id, ENT_QUOTES, 'UTF-8'); ?>"
                             data-login_uri="google_login.php?next=<?php echo urlencode($next_param); ?>"
                             data-auto_prompt="false"></div>
                        <div class="g_id_signin" data-type="standard" data-size="large" data-theme="outline" data-text="signin_with" data-shape="pill"></div>
                    </div>
                    <script src="https://accounts.google.com/gsi/client" async defer></script>
                <?php } ?>

                <div class="toggle">
                    Yönetici girişi için <button type="button" onclick="showAdmin()">tıklayın</button>
                </div>
            </div>

            <div id="admin-form" class="<?php echo ($login_tipi === 'yonetici') ? '' : 'hidden'; ?>">
                <h2>Yönetici Girişi</h2>
                <p>Kurum yöneticisi ve eğitmen girişi</p>

                <?php if (!empty($hata_mesaji) && $hata_tipi === 'yonetici') { ?>
                    <div class="alert"><?php echo $hata_mesaji; ?></div>
                <?php } ?>

                <form method="post" action="login.php">
                    <input type="hidden" name="login_tipi" value="yonetici">
                    <input type="hidden" name="next" value="<?php echo htmlspecialchars($next_param, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php if (empty($_GET['c'])) { ?>
                        <div class="field">
                            <label>Kurum Kodu</label>
                            <input type="text" id="kurum_kodu" name="kurum_kodu" required>
                        </div>
                    <?php } else { ?>
                        <input type="hidden" id="kurum_kodu" name="kurum_kodu" value="<?php echo htmlspecialchars($_GET['c'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php } ?>
                    <div class="field">
                        <label>Kullanıcı Adı</label>
                        <input type="text" id="kullanici_adi" name="kullanici_adi" required>
                    </div>
                    <div class="field">
                        <label>Şifreniz</label>
                        <input type="password" id="sifre" name="sifre" required>
                    </div>
                    <button class="btn btn-primary" type="submit" style="width:100%;">Giriş Yap</button>
                </form>

                <div class="toggle">
                    Veli girişi için <button type="button" onclick="showVeli()">tıklayın</button>
                </div>
            </div>
        </div>
    </section>

    <script>
        function showAdmin() {
            document.getElementById('veli-form').classList.add('hidden');
            document.getElementById('admin-form').classList.remove('hidden');
            document.getElementById('hero-veli').classList.add('hidden');
            document.getElementById('hero-yonetici').classList.remove('hidden');
        }
        function showVeli() {
            document.getElementById('admin-form').classList.add('hidden');
            document.getElementById('veli-form').classList.remove('hidden');
            document.getElementById('hero-yonetici').classList.add('hidden');
            document.getElementById('hero-veli').classList.remove('hidden');
        }
    </script>
</body>
</html>
