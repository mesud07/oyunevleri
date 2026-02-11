<?php
// Gmail (Google) login callback
require_once("includes/config.php");

$credential = $_POST['credential'] ?? '';
$next_param = $_GET['next'] ?? '';

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
if ($credential === '' || empty($google_client_id)) {
    $_SESSION['google_login_error'] = 'Google girişi aktif değil.';
    header("Location: login.php");
    exit;
}

$token_info_url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);
$token_info = @file_get_contents($token_info_url);
if ($token_info === false) {
    $_SESSION['google_login_error'] = 'Google doğrulaması yapılamadı.';
    header("Location: login.php");
    exit;
}

$payload = json_decode($token_info, true);
if (empty($payload['sub']) || empty($payload['email']) || ($payload['aud'] ?? '') !== $google_client_id) {
    $_SESSION['google_login_error'] = 'Google doğrulaması başarısız.';
    header("Location: login.php");
    exit;
}

if (empty($db)) {
    $_SESSION['google_login_error'] = 'Kurum veritabani baglantisi bulunamadi.';
    header("Location: login.php");
    exit;
}

$sub = $payload['sub'];
$email = $payload['email'];
$name = $payload['name'] ?? 'Google Kullanıcı';

$yeni_kayit = false;

$stmt = $db->prepare("SELECT * FROM veliler WHERE google_sub = :sub LIMIT 1");
$stmt->execute(['sub' => $sub]);
$veli = $stmt->fetch();

if (!$veli) {
    $stmt = $db->prepare("SELECT * FROM veliler WHERE eposta = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $veli = $stmt->fetch();

    if ($veli) {
        $stmt = $db->prepare("UPDATE veliler SET google_sub = :sub, google_email = :email WHERE id = :id");
        $stmt->execute(['sub' => $sub, 'email' => $email, 'id' => $veli['id']]);
        $veli['google_sub'] = $sub;
        $veli['google_email'] = $email;
    } else {
        $stmt = $db->prepare("INSERT INTO veliler (kurum_id, sube_id, ad_soyad, telefon, eposta, sifre, google_sub, google_email, bakiye_hak)
            VALUES (0, :sube_id, :ad_soyad, NULL, :eposta, NULL, :google_sub, :google_email, 0)");
        $stmt->execute([
            'sube_id' => null,
            'ad_soyad' => $name,
            'eposta' => $email,
            'google_sub' => $sub,
            'google_email' => $email,
        ]);
        $veli_id = (int) $db->lastInsertId();
        $veli = [
            'id' => $veli_id,
            'kurum_id' => 0,
            'sube_id' => 0,
            'ad_soyad' => $name,
            'telefon' => null,
            'eposta' => $email,
            'bakiye_hak' => 0,
            'google_sub' => $sub,
            'google_email' => $email,
        ];
        $yeni_kayit = true;
    }
}

$_SESSION['veli_giris'] = 1;
$_SESSION['veli'] = $veli;
$_SESSION['kurum_id'] = (int) ($veli['kurum_id'] ?? 0);
if ($yeni_kayit) {
    $_SESSION['veli_yeni'] = 1;
}
$hedef = guvenli_yonlendirme($next_param, 'index.php');
header("Location: {$hedef}");
exit;
