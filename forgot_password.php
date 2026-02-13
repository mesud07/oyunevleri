<?php
require_once('includes/config.php');

$hata = '';
$basari = '';
$email = '';

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

function reset_mail_gonder($to, $subject, $html) {
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($autoload)) {
        error_log('PHPMailer autoload bulunamadi.');
        return false;
    }

    require_once $autoload;

    $mail_host = $GLOBALS['mail_host'] ?? '';
    $mail_port = $GLOBALS['mail_port'] ?? 587;
    $mail_user = $GLOBALS['mail_user'] ?? '';
    $mail_pass = $GLOBALS['mail_pass'] ?? '';
    $mail_secure = $GLOBALS['mail_secure'] ?? 'tls';
    $mail_from = $GLOBALS['mail_from'] ?? 'noreply@oyunevleri.com';
    $mail_from_name = $GLOBALS['mail_from_name'] ?? 'Oyunevleri';
    $mail_allow_insecure = $GLOBALS['mail_allow_insecure'] ?? false;

    if ($mail_host === '' || $mail_user === '' || $mail_pass === '') {
        error_log('SMTP ayarlari eksik.');
        return false;
    }

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = $mail_host;
        $mail->SMTPAuth = true;
        $mail->Username = $mail_user;
        $mail->Password = $mail_pass;
        if ($mail_secure === 'ssl') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }
        $mail->Port = (int)$mail_port;
        if ($mail_allow_insecure) {
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];
        }

        $mail->setFrom($mail_from, $mail_from_name);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html;
        $mail->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)));

        return $mail->send();
    } catch (Throwable $e) {
        error_log('Mail hata: ' . $e->getMessage());
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email === '') {
        $hata = 'Lütfen e-posta adresinizi girin.';
    } elseif (empty($db)) {
        $hata = 'Kurum veritabani baglantisi bulunamadi.';
    } else {
        $stmt = $db->prepare("SELECT id, ad_soyad, eposta, kurum_id FROM veliler WHERE eposta = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $veli = $stmt->fetch();

        if ($veli) {
            $token = bin2hex(random_bytes(32));
            $token_hash = hash('sha256', $token);
            $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

            $stmt = $db->prepare("INSERT INTO password_resets (kurum_id, veli_id, token_hash, expires_at, ip, user_agent)
                VALUES (:kurum_id, :veli_id, :token_hash, :expires_at, :ip, :user_agent)");
            $ok = $stmt->execute([
                'kurum_id' => (int) ($veli['kurum_id'] ?? 0),
                'veli_id' => (int) $veli['id'],
                'token_hash' => $token_hash,
                'expires_at' => $expires_at,
                'ip' => $ip,
                'user_agent' => $ua,
            ]);

            if ($ok) {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
                $reset_link = $scheme . '://' . $_SERVER['HTTP_HOST'] . $base_path . '/reset_password.php?token=' . urlencode($token);

                $subject = 'Şifre Sıfırlama';
                $message = "
                    <p>Merhaba {$veli['ad_soyad']},</p>
                    <p>Şifrenizi sıfırlamak için aşağıdaki bağlantıyı kullanın:</p>
                    <p><a href=\"{$reset_link}\">Şifreyi Sıfırla</a></p>
                    <p>Bu bağlantı <strong>tek kullanımlık</strong>tır ve <strong>30 dakika</strong> geçerlidir.</p>
                    <p>Eğer bu isteği siz yapmadıysanız bu e-postayı yok sayabilirsiniz.</p>
                ";
                if (!reset_mail_gonder($email, $subject, $message)) {
                    $hata = 'E-posta gönderilemedi. Lütfen daha sonra tekrar deneyin.';
                }
            } else {
                $hata = 'Şifre sıfırlama isteği oluşturulamadı.';
            }
        }

        if ($hata === '') {
            $basari = 'Eğer e-posta kayıtlıysa şifre sıfırlama bağlantısı gönderildi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Oyunevleri.com | Şifre Sıfırlama</title>
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
            <h1>Şifre Sıfırlama</h1>
            <p>E-posta adresinizi girin, şifre sıfırlama bağlantısı göndereceğiz.</p>
        </div>

        <div class="card">
            <?php if ($hata !== '') { ?>
                <div class="alert"><?php echo $hata; ?></div>
            <?php } ?>
            <?php if ($basari !== '') { ?>
                <div class="alert success"><?php echo $basari; ?></div>
            <?php } ?>
            <form method="post" action="forgot_password.php">
                <div class="field">
                    <label>E-posta</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <button class="btn btn-primary" type="submit" style="width:100%;">Sıfırlama Linki Gönder</button>
            </form>
        </div>
    </section>
</body>
</html>
