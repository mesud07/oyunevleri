<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

if (empty($db_master)) {
    die("Master veritabani baglantisi bulunamadi.\n");
}

$stmt = $db_master->query("SELECT id, kurum_adi FROM kurumlar");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$update = $db_master->prepare("UPDATE kurumlar SET slug = :slug WHERE id = :id");

$updated = 0;
$skipped = 0;

foreach ($rows as $row) {
    $id = (int) ($row['id'] ?? 0);
    $adi = $row['kurum_adi'] ?? '';
    if ($id <= 0) {
        $skipped++;
        continue;
    }
    $slug = kurum_slug_uret($adi);
    if ($slug === '') {
        $skipped++;
        continue;
    }
    try {
        $update->execute([
            'slug' => $slug,
            'id' => $id,
        ]);
        $updated++;
        echo "Guncellendi: {$id} -> {$slug}\n";
    } catch (PDOException $e) {
        $skipped++;
        echo "Hata: {$id} - {$e->getMessage()}\n";
    }
}

echo "\nIslem tamamlandi. Guncellenen: {$updated}, Atlanan: {$skipped}\n";
