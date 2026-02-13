<?php
require_once __DIR__ . '/includes/config.php';

$csv_file = trim($_GET['file'] ?? 'kurumlar_listesi.csv');
$csv_file = basename($csv_file);
$csv_path = __DIR__ . '/' . $csv_file;

if (empty($db_master)) {
    die("Master veritabani baglantisi bulunamadi.");
}

if (!file_exists($csv_path)) {
    die("CSV dosyasi bulunamadi: " . htmlspecialchars($csv_path, ENT_QUOTES, 'UTF-8'));
}

$handle = fopen($csv_path, 'r');
if ($handle === false) {
    die("CSV dosyasi acilamadi.");
}

// Delimiter kontrolu
$delimiter = ';';
$header = fgetcsv($handle, 1000, $delimiter);
if ($header !== false && count($header) < 3) {
    rewind($handle);
    $delimiter = ',';
    $header = fgetcsv($handle, 1000, $delimiter);
}

$kurum_stmt = $db_master->prepare("SELECT id FROM kurumlar WHERE kurum_adi = :kurum_adi LIMIT 1");
$galeri_var_stmt = $db_master->prepare("SELECT COUNT(*) FROM kurum_galeri WHERE kurum_id = :kurum_id");
$galeri_var_gorsel_stmt = $db_master->prepare("SELECT id FROM kurum_galeri WHERE kurum_id = :kurum_id AND gorsel_yol = :gorsel LIMIT 1");
$sira_stmt = $db_master->prepare("SELECT COALESCE(MAX(sira),0) FROM kurum_galeri WHERE kurum_id = :kurum_id");
$insert_stmt = $db_master->prepare("INSERT INTO kurum_galeri (kurum_id, gorsel_yol, sira) VALUES (:kurum_id, :gorsel_yol, :sira)");

$eklenen = 0;
$atlan = 0;
$hata = 0;
$satir = 1;

function normalize_gorsel_yol($path) {
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }
    if (stripos($path, 'http://') === 0 || stripos($path, 'https://') === 0) {
        return $path;
    }
    if (stripos($path, '/uploads/') === 0) {
        return $path;
    }
    if (stripos($path, 'kurum_galeri/') === 0) {
        return '/uploads/' . ltrim($path, '/');
    }
    if (stripos($path, '/kurum_galeri/') === 0) {
        return '/uploads' . $path;
    }
    return '/uploads/kurum_galeri/' . ltrim($path, '/');
}

while (($data = fgetcsv($handle, 2000, $delimiter)) !== false) {
    $satir++;
    if (count(array_filter($data, 'strlen')) === 0) {
        $atlan++;
        continue;
    }

    // 0: Firma Adı, 2: Resim Yolu
    $kurum_adi = trim((string) ($data[0] ?? ''));
    $gorsel_raw = trim((string) ($data[2] ?? ''));

    if ($kurum_adi === '' || $gorsel_raw === '') {
        $atlan++;
        continue;
    }

    $kurum_stmt->execute(['kurum_adi' => $kurum_adi]);
    $kurum = $kurum_stmt->fetch();
    if (!$kurum) {
        $atlan++;
        echo "Kurum bulunamadi (Satir {$satir}): " . htmlspecialchars($kurum_adi, ENT_QUOTES, 'UTF-8') . "<br>";
        continue;
    }

    $kurum_id = (int) $kurum['id'];
    $galeri_var_stmt->execute(['kurum_id' => $kurum_id]);
    $galeri_var = (int) $galeri_var_stmt->fetchColumn();
    if ($galeri_var > 0) {
        $atlan++;
        continue;
    }

    $gorsel_yol = normalize_gorsel_yol($gorsel_raw);
    if ($gorsel_yol === '') {
        $atlan++;
        continue;
    }

    $galeri_var_gorsel_stmt->execute(['kurum_id' => $kurum_id, 'gorsel' => $gorsel_yol]);
    if ($galeri_var_gorsel_stmt->fetch()) {
        $atlan++;
        continue;
    }

    $sira_stmt->execute(['kurum_id' => $kurum_id]);
    $sira = (int) $sira_stmt->fetchColumn() + 1;

    try {
        $ok = $insert_stmt->execute([
            'kurum_id' => $kurum_id,
            'gorsel_yol' => $gorsel_yol,
            'sira' => $sira,
        ]);
        if ($ok) {
            $eklenen++;
            echo "Gorsel eklendi: " . htmlspecialchars($kurum_adi, ENT_QUOTES, 'UTF-8') . "<br>";
        } else {
            $hata++;
            echo "Hata: " . htmlspecialchars($kurum_adi, ENT_QUOTES, 'UTF-8') . "<br>";
        }
    } catch (PDOException $e) {
        $hata++;
        echo "Hata (Satir {$satir}): " . htmlspecialchars($kurum_adi, ENT_QUOTES, 'UTF-8') . " - " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "<br>";
    }
}

fclose($handle);
echo "<br>Islem Tamamlandi! Eklenen: {$eklenen}, Atlanan: {$atlan}, Hatali: {$hata}";
