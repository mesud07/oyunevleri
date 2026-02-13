<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

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

// Varsayilan ayirac virguldur, gerekirse noktalı virgule gecilir.
$delimiter = ',';
$header = fgetcsv($handle, 1000, $delimiter);
if ($header !== false && count($header) < 2) {
    rewind($handle);
    $delimiter = ';';
    $header = fgetcsv($handle, 1000, $delimiter);
}

$stmt = $db_master->prepare("INSERT INTO kurumlar (kurum_adi, slug, sehir, ilce, telefon, eposta)
    VALUES (:kurum_adi, :slug, :sehir, :ilce, :telefon, :eposta)");

$eklenen = 0;
$atlan = 0;
$hata = 0;
$satir = 1;

while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
    $satir++;
    if (count(array_filter($data, 'strlen')) === 0) {
        $atlan++;
        continue;
    }

    // Excel sutun sirasina gore eslestir (0: isim, 1: konum, 3: tel, 4: mail)
    $kurum_adi = trim((string) ($data[0] ?? ''));
    $konum = trim((string) ($data[1] ?? ''));
    $tel = trim((string) ($data[3] ?? ''));
    $mail = trim((string) ($data[4] ?? ''));

    if ($kurum_adi === '') {
        $atlan++;
        continue;
    }
    $slug = kurum_slug_uret($kurum_adi);

    $ilce = '';
    $sehir = '';
    if ($konum !== '') {
        $konum_array = preg_split('/\s*\/\s*/', $konum);
        $ilce = trim((string) ($konum_array[0] ?? ''));
        $sehir = trim((string) ($konum_array[1] ?? ''));
    }

    try {
        $ok = $stmt->execute([
            'kurum_adi' => $kurum_adi,
            'slug' => $slug,
            'sehir' => $sehir,
            'ilce' => $ilce,
            'telefon' => $tel,
            'eposta' => $mail,
        ]);
        if ($ok) {
            $eklenen++;
            echo "Eklendi: " . htmlspecialchars($kurum_adi, ENT_QUOTES, 'UTF-8') . "<br>";
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
