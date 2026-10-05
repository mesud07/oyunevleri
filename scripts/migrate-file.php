<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Veritabani;

$root = dirname(__DIR__);
require $root . '/app/Core/Config.php';
require $root . '/app/Core/Veritabani.php';

Config::load($root . '/.env');

$dosya = (string) ($argv[1] ?? '');
if ($dosya === '' || !is_file($dosya) || !is_readable($dosya)) {
    fwrite(STDERR, "Kullanım: php scripts/migrate-file.php <migration.sql>\n");
    exit(1);
}

$satirlar = file($dosya, FILE_IGNORE_NEW_LINES);
if ($satirlar === false) {
    fwrite(STDERR, "Migration dosyası okunamadı.\n");
    exit(1);
}

$db = Veritabani::baglan();
$ayrac = ';';
$ifade = '';
$calisan = 0;

foreach ($satirlar as $satir) {
    $kirpilmis = trim($satir);
    if (trim($ifade) === '' && preg_match('/^DELIMITER\s+(.+)$/i', $kirpilmis, $eslesme)) {
        $ifade = '';
        $ayrac = $eslesme[1];
        continue;
    }

    $ifade .= $satir . "\n";
    if (!str_ends_with(rtrim($ifade), $ayrac)) {
        continue;
    }

    $sql = trim(substr(rtrim($ifade), 0, -strlen($ayrac)));
    $ifade = '';
    if ($sql === '' || preg_match('/^(?:--[^\n]*\n|\s*)+$/', $sql)) {
        continue;
    }

    // CALL/EXECUTE ifadeleri MySQL sürücüsünde boş sonuç kümeleri
    // bırakabilir. Sonraki ifadeye geçmeden hepsini tüket.
    $sorgu = $db->prepare($sql);
    $sorgu->execute();
    do {
        if ($sorgu->columnCount() > 0) {
            $sorgu->fetchAll();
        }
    } while ($sorgu->nextRowset());
    $sorgu->closeCursor();
    $calisan++;
}

$kalan = preg_replace('/^\s*--[^\r\n]*(?:\r?\n|$)/m', '', $ifade) ?? $ifade;
if (trim($kalan) !== '') {
    throw new RuntimeException('Migration sonunda tamamlanmamış SQL ifadesi bulundu.');
}

fwrite(STDOUT, basename($dosya) . ": {$calisan} ifade uygulandı.\n");
