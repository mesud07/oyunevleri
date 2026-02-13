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

// CSV delimiter kontrolu (varsayilan ;)
$delimiter = ';';
$header = fgetcsv($handle, 2000, $delimiter);
if ($header !== false && count($header) < 3) {
    rewind($handle);
    $delimiter = ',';
    $header = fgetcsv($handle, 2000, $delimiter);
}

if ($header === false) {
    die("CSV baslik satiri okunamadi.");
}

$header_map = [];
foreach ($header as $i => $col) {
    $key = mb_strtolower(trim((string) $col), 'UTF-8');
    $header_map[$key] = $i;
}

$idx_kurum = $header_map['firma adı'] ?? $header_map['firma adi'] ?? 0;
$idx_hakkimizda = $header_map['hakkımızda'] ?? $header_map['hakkimizda'] ?? null;
$idx_ozellik = $header_map['özellikler'] ?? $header_map['ozellikler'] ?? null;

function parse_ages_from_features($features) {
    $features = mb_strtolower($features, 'UTF-8');
    $min_year = null;
    $max_year = null;
    $has_plus = false;

    if (preg_match_all('/(\d+(?:[\\.,]\\d+)?)\\s*[-–]\\s*(\\d+(?:[\\.,]\\d+)?)\\s*yaş/u', $features, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $start = (float) str_replace(',', '.', $m[1]);
            $end = (float) str_replace(',', '.', $m[2]);
            if ($min_year === null || $start < $min_year) {
                $min_year = $start;
            }
            if ($max_year === null || $end > $max_year) {
                $max_year = $end;
            }
        }
    }

    if (preg_match_all('/(\\d+(?:[\\.,]\\d+)?)\\s*\\+\\s*yaş/u', $features, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $val = (float) str_replace(',', '.', $m[1]);
            if ($min_year === null || $val < $min_year) {
                $min_year = $val;
            }
            $has_plus = true;
        }
    }

    $min_ay = $min_year !== null ? (int) round($min_year * 12) : null;
    $max_ay = $has_plus ? null : ($max_year !== null ? (int) round($max_year * 12) : null);

    return [$min_ay, $max_ay];
}

$stmt_find = $db_master->prepare("SELECT id FROM kurumlar WHERE kurum_adi = :kurum_adi LIMIT 1");
$stmt_update = $db_master->prepare("UPDATE kurumlar
    SET hakkimizda = :hakkimizda,
        min_ay = :min_ay,
        max_ay = :max_ay,
        ozellikler = :ozellikler
    WHERE kurum_adi = :kurum_adi");

$eklenen = 0;
$atlan = 0;
$hata = 0;
$satir = 1;

while (($data = fgetcsv($handle, 4000, $delimiter)) !== false) {
    $satir++;
    if (count(array_filter($data, 'strlen')) === 0) {
        $atlan++;
        continue;
    }

    $kurum_adi = trim((string) ($data[$idx_kurum] ?? ''));
    if ($kurum_adi === '') {
        $atlan++;
        continue;
    }

    $hakkimizda = $idx_hakkimizda !== null ? trim((string) ($data[$idx_hakkimizda] ?? '')) : '';
    $ozellikler = $idx_ozellik !== null ? trim((string) ($data[$idx_ozellik] ?? '')) : '';

    if ($hakkimizda === '' && $ozellikler === '') {
        $atlan++;
        continue;
    }

    $stmt_find->execute(['kurum_adi' => $kurum_adi]);
    $found = $stmt_find->fetch();
    if (!$found) {
        $atlan++;
        echo "Kurum bulunamadi (Satir {$satir}): " . htmlspecialchars($kurum_adi, ENT_QUOTES, 'UTF-8') . "<br>";
        continue;
    }

    [$min_ay, $max_ay] = parse_ages_from_features($ozellikler);

    try {
        $ok = $stmt_update->execute([
            'hakkimizda' => $hakkimizda !== '' ? $hakkimizda : null,
            'min_ay' => $min_ay,
            'max_ay' => $max_ay,
            'ozellikler' => $ozellikler !== '' ? $ozellikler : null,
            'kurum_adi' => $kurum_adi,
        ]);
        if ($ok) {
            $eklenen++;
            echo "Guncellendi: " . htmlspecialchars($kurum_adi, ENT_QUOTES, 'UTF-8') . "<br>";
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
echo "<br>Islem Tamamlandi! Guncellenen: {$eklenen}, Atlanan: {$atlan}, Hatali: {$hata}";
