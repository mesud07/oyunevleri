<?php
require_once('functions_db.php');
require_once('functions_tools.php');

// Oturumdaki kullanici bilgilerini dondurur.
function aktif_kullanici() {
    return $_SESSION['kullanici'] ?? null;
}

// Oturumdaki kullanici ID bilgisini dondurur.
function aktif_kullanici_id() {
    $kullanici = aktif_kullanici();
    return (int) ($kullanici['id'] ?? 0);
}

// Oturumdaki kurum ID bilgisini dondurur.
function aktif_kurum_id() {
    return (int) ($_SESSION['kurum_id'] ?? 0);
}

// Oturumdaki sube ID bilgisini dondurur.
function aktif_sube_id() {
    return (int) ($_SESSION['sube_id'] ?? 0);
}

// Giris yoksa login sayfasina yonlendirir.
function giris_zorunlu() {
    if (empty($_SESSION['giris'])) {
        header("Location: login");
        exit;
    }
}

// Verilen parolayi hashleyerek dondurur.
function parola_hash($sifre) {
    return password_hash($sifre, PASSWORD_DEFAULT);
}

// Parola dogrulamasini yapar.
function parola_dogrula($sifre, $hash) {
    return password_verify($sifre, $hash);
}

// SMTP ile HTML e-posta gonderir.
function mail_gonder($to, $subject, $html) {
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
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
        $mail->Port = (int) $mail_port;
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

// Kurum bilgisini (ad + eposta) getirir.
function kurum_bilgi_get($kurum_id) {
    global $db_master;
    $kurum_id = (int) $kurum_id;
    if (empty($db_master) || $kurum_id <= 0) {
        return null;
    }
    $stmt = $db_master->prepare("SELECT kurum_adi, eposta FROM kurumlar WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $kurum_id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// Merkez admin kontrolu yapar.
function merkez_admin_mi() {
    $kullanici = aktif_kullanici();
    return !empty($kullanici) && ($kullanici['yetki_seviyesi'] ?? '') === 'merkez_admin';
}

// Kullanici yetkilerini session'a yukler.
function kullanici_yetkileri_yukle($kullanici_id, $kurum_id) {
    global $db_master;
    $_SESSION['yetkiler'] = [];

    if (empty($db_master) || $kullanici_id <= 0 || $kurum_id <= 0) {
        return [];
    }

    $sql = "SELECT DISTINCT y.yetki_kodu
            FROM kullanici_roller kr
            INNER JOIN roller r ON r.id = kr.rol_id AND r.kurum_id = :kurum_id
            INNER JOIN rol_yetkiler ry ON ry.rol_id = r.id
            INNER JOIN yetkiler y ON y.id = ry.yetki_id
            WHERE kr.kullanici_id = :kullanici_id";
    $stmt = $db_master->prepare($sql);
    $stmt->execute([
        'kurum_id' => (int) $kurum_id,
        'kullanici_id' => (int) $kullanici_id,
    ]);
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $_SESSION['yetkiler'][$row['yetki_kodu']] = true;
    }

    return $_SESSION['yetkiler'];
}

// Belirtilen yetkiye sahipligi kontrol eder.
function yetki_var($yetki_kodu) {
    if (merkez_admin_mi()) {
        return true;
    }
    return !empty($_SESSION['yetkiler'][$yetki_kodu]);
}

// Sistem ayari degerini getirir.
function sistem_ayar_get($anahtar, $kurum_id = null, $default = null) {
    global $db;
    $kurum_id = (int) ($kurum_id ?? aktif_kurum_id());
    if (empty($db) || $kurum_id <= 0 || $anahtar === '') {
        return $default;
    }
    $stmt = $db->prepare("SELECT deger FROM sistem_ayarlar WHERE kurum_id = :kurum_id AND anahtar = :anahtar LIMIT 1");
    $stmt->execute([
        'kurum_id' => $kurum_id,
        'anahtar' => $anahtar,
    ]);
    $row = $stmt->fetch();
    return $row ? $row['deger'] : $default;
}

// Sistem ayari degerini gunceller veya ekler.
function sistem_ayar_set($anahtar, $deger, $kurum_id = null) {
    global $db;
    $kurum_id = (int) ($kurum_id ?? aktif_kurum_id());
    if (empty($db) || $kurum_id <= 0 || $anahtar === '') {
        return false;
    }
    $sql = "INSERT INTO sistem_ayarlar (kurum_id, anahtar, deger)
            VALUES (:kurum_id, :anahtar, :deger)
            ON DUPLICATE KEY UPDATE deger = VALUES(deger)";
    $stmt = $db->prepare($sql);
    return $stmt->execute([
        'kurum_id' => $kurum_id,
        'anahtar' => $anahtar,
        'deger' => $deger,
    ]);
}

// Kasa hareketlerinde kategori kolonu var mi kontrol eder.
function kasa_kategori_var_mi() {
    static $cache = null;
    global $db;
    if ($cache !== null) {
        return $cache;
    }
    if (empty($db)) {
        $cache = false;
        return $cache;
    }
    try {
        $stmt = $db->query("SHOW COLUMNS FROM kasa_hareketleri LIKE 'kategori'");
        $cache = (bool) $stmt->fetch();
    } catch (Throwable $e) {
        $cache = false;
    }
    return $cache;
}

// Veli hak dondurma durumunu kontrol eder.
function veli_hak_dondurulmus_mu($veli_id, $kurum_id = null) {
    global $db;
    $kurum_id = (int) ($kurum_id ?? aktif_kurum_id());
    $veli_id = (int) $veli_id;
    if (empty($db) || $kurum_id <= 0 || $veli_id <= 0) {
        return false;
    }

    $stmt = $db->prepare("SELECT hak_donduruldu FROM veliler WHERE id = :id AND kurum_id = :kurum_id LIMIT 1");
    $stmt->execute(['id' => $veli_id, 'kurum_id' => $kurum_id]);
    $row = $stmt->fetch();
    if ($row && (int) $row['hak_donduruldu'] === 1) {
        return true;
    }

    $stmt = $db->prepare("SELECT 1 FROM veli_hak_dondurma
        WHERE veli_id = :veli_id AND kurum_id = :kurum_id AND durum = 'aktif'
          AND (baslangic_tarihi IS NULL OR baslangic_tarihi <= CURDATE())
          AND (bitis_tarihi IS NULL OR bitis_tarihi >= CURDATE())
        LIMIT 1");
    $stmt->execute(['veli_id' => $veli_id, 'kurum_id' => $kurum_id]);
    return (bool) $stmt->fetch();
}

// Veli hak bakiyesini getirir.
function veli_bakiye_get($veli_id, $kurum_id = null) {
    global $db;
    $kurum_id = (int) ($kurum_id ?? aktif_kurum_id());
    $veli_id = (int) $veli_id;
    if (empty($db) || $kurum_id <= 0 || $veli_id <= 0) {
        return 0;
    }
    $stmt = $db->prepare("SELECT bakiye_hak FROM veliler WHERE id = :id AND kurum_id = :kurum_id LIMIT 1");
    $stmt->execute(['id' => $veli_id, 'kurum_id' => $kurum_id]);
    $row = $stmt->fetch();
    return (int) ($row['bakiye_hak'] ?? 0);
}

// Veli hak hareketi ekler ve bakiye bilgisini gunceller.
function veli_hak_hareket_ekle($veli_id, $islem_tipi, $miktar, $aciklama = '', $kurum_id = null) {
    global $db;
    $kurum_id = (int) ($kurum_id ?? aktif_kurum_id());
    $veli_id = (int) $veli_id;
    $miktar = (int) $miktar;

    if (empty($db) || $kurum_id <= 0 || $veli_id <= 0 || $miktar <= 0) {
        return false;
    }

    $delta = 0;
    if ($islem_tipi === 'ekleme' || $islem_tipi === 'iade') {
        $delta = $miktar;
    } elseif ($islem_tipi === 'kullanim') {
        $delta = -$miktar;
    }

    $db->prepare("UPDATE veliler SET bakiye_hak = bakiye_hak + :delta WHERE id = :id AND kurum_id = :kurum_id")
        ->execute(['delta' => $delta, 'id' => $veli_id, 'kurum_id' => $kurum_id]);

    $stmt = $db->prepare("INSERT INTO veli_hak_hareketleri (kurum_id, veli_id, islem_tipi, miktar, aciklama)
        VALUES (:kurum_id, :veli_id, :islem_tipi, :miktar, :aciklama)");
    $ok = $stmt->execute([
        'kurum_id' => $kurum_id,
        'veli_id' => $veli_id,
        'islem_tipi' => $islem_tipi,
        'miktar' => $miktar,
        'aciklama' => $aciklama,
    ]);
    return $ok ? (int) $db->lastInsertId() : false;
}

// Ogrencinin yasini ay bazinda hesaplar.
function ogrenci_yas_ay($dogum_tarihi) {
    if (empty($dogum_tarihi)) {
        return 0;
    }
    $dt = new DateTime($dogum_tarihi);
    $now = new DateTime();
    $diff = $dt->diff($now);
    return ($diff->y * 12) + $diff->m;
}

// Seans iptalinde kalan saat farkini hesaplar.
function seans_iptal_kalan_saat($seans_baslangic) {
    $baslangic = strtotime($seans_baslangic);
    if ($baslangic === false) {
        return 0;
    }
    return ($baslangic - time()) / 3600;
}

// Materyal yukleme yetkisini kontrol eder.
function materyal_yukleme_yetkili_mi() {
    $kullanici = aktif_kullanici();
    if (empty($kullanici)) {
        return false;
    }
    $yetki = $kullanici['yetki_seviyesi'] ?? '';
    return in_array($yetki, ['egitmen', 'sube_admin', 'merkez_admin'], true);
}

// Dosya adini guvenli hale getirir.
function dosya_adi_temizle($dosya_adi) {
    $dosya_adi = preg_replace('/[^a-zA-Z0-9._-]/', '_', $dosya_adi);
    return trim($dosya_adi, '_');
}

// JSON yanit dondurup cikis yapar.
function json_yanit($durum, $mesaj = '', $ek = []) {
    header('Content-Type: application/json');
    $payload = array_merge([
        'durum' => $durum ? 'ok' : 'hata',
        'mesaj' => $mesaj,
    ], $ek);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// Basit log kaydi yazar (gerekirse gelistirilecek).
function logTut($subject, $details, $lt_id, $link = '#', $tutar = 0, $pb_id = 0) {
    $kullanici_id = aktif_kullanici_id();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    error_log("LOG: {$subject} | {$details} | {$kullanici_id} | {$ip}");
    return true;
}
