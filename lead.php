<?php
// www.oyunevleri.com hızlı iletişim endpoint
require_once("includes/config.php");

header('Content-Type: application/json; charset=utf-8');

$kurum_id = (int) ($_POST['kurum_id'] ?? 0);
$ad_soyad = trim($_POST['ad_soyad'] ?? '');
$telefon = trim($_POST['telefon'] ?? '');
$mesaj = trim($_POST['mesaj'] ?? '');
$sayfa_url = trim($_POST['sayfa_url'] ?? '');

if ($kurum_id <= 0 || $ad_soyad === '' || $telefon === '') {
    echo json_encode([
        'durum' => 'hata',
        'mesaj' => 'Lütfen ad, telefon ve kurum bilgisini giriniz.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($db_master)) {
    echo json_encode([
        'durum' => 'hata',
        'mesaj' => 'Sistem hatası. Lütfen daha sonra tekrar deneyin.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $db_master->prepare("INSERT INTO kurum_iletisim_talepleri
        (kurum_id, ad_soyad, telefon, mesaj, kaynak, ip_adresi, sayfa_url)
        VALUES (:kurum_id, :ad_soyad, :telefon, :mesaj, 'web', :ip, :sayfa_url)");
    $stmt->execute([
        'kurum_id' => $kurum_id,
        'ad_soyad' => $ad_soyad,
        'telefon' => $telefon,
        'mesaj' => $mesaj !== '' ? $mesaj : null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'sayfa_url' => $sayfa_url !== '' ? $sayfa_url : null,
    ]);

    echo json_encode([
        'durum' => 'ok',
        'mesaj' => 'Talebiniz alındı. En kısa sürede dönüş yapılacaktır.',
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('Lead hata: ' . $e->getMessage());
    echo json_encode([
        'durum' => 'hata',
        'mesaj' => 'Talep kaydedilemedi.',
    ], JSON_UNESCAPED_UNICODE);
}
