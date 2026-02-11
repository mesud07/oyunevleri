<?php
require_once('includes/config.php');
require_once('includes/functions.php');

$code = trim($_GET['code'] ?? '');
if ($code === '') {
    echo 'Davet kodu bulunamadı.';
    exit;
}

if (empty($db_master)) {
    echo 'Sistem hatası: kurum bilgisi alınamadı.';
    exit;
}

$stmt = $db_master->prepare("SELECT id, kurum_adi, durum FROM kurumlar WHERE kurum_kodu = :kod LIMIT 1");
$stmt->execute(['kod' => $code]);
$kurum = $stmt->fetch();
if (!$kurum || (int) ($kurum['durum'] ?? 0) !== 1) {
    echo 'Geçersiz veya pasif davet kodu.';
    exit;
}

$kurum_id = (int) $kurum['id'];
$next_url = 'davet.php?code=' . urlencode($code);

if (empty($_SESSION['veli_giris'])) {
    header('Location: register.php?kurum_kodu=' . urlencode($code) . '&next=' . urlencode($next_url));
    exit;
}

$veli = $_SESSION['veli'] ?? null;
$veli_id = (int) ($veli['id'] ?? 0);
if ($veli_id <= 0) {
    header('Location: login.php?next=' . urlencode($next_url));
    exit;
}

if (empty($db)) {
    echo 'Kurum veritabanı bağlantısı bulunamadı.';
    exit;
}

try {
    $db->beginTransaction();
    $db->prepare("UPDATE veliler SET kurum_id = :kurum_id, sube_id = NULL WHERE id = :id")
        ->execute(['kurum_id' => $kurum_id, 'id' => $veli_id]);
    $db->prepare("UPDATE ogrenciler SET kurum_id = :kurum_id WHERE veli_id = :veli_id AND (kurum_id IS NULL OR kurum_id = 0)")
        ->execute(['kurum_id' => $kurum_id, 'veli_id' => $veli_id]);
    $db->commit();
} catch (PDOException $e) {
    $db->rollBack();
    error_log('Davet join hata: ' . $e->getMessage());
    echo 'Kurum katılımı sırasında hata oluştu.';
    exit;
}

$_SESSION['kurum_id'] = $kurum_id;
$_SESSION['veli']['kurum_id'] = $kurum_id;
$_SESSION['kurum_adi'] = $kurum['kurum_adi'] ?? '';
$_SESSION['flash_basarili'] = 'Kuruma katıldınız.';

header('Location: index.php?kurum_katildi=1');
exit;
