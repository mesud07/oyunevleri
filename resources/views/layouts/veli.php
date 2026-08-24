<?php
$asset = static function (string $path): string {
    $fullPath = BASE_PATH . '/public' . $path;
    $version = is_file($fullPath) ? (string) filemtime($fullPath) : (string) time();
    return $path . '?v=' . rawurlencode($version);
};
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($baslik ?? 'Veli Bilgi Ekrani') ?> | Oyun Evleri Yönetim Sistemi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@200;300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e($asset('/assets/css/panel.css')) ?>">
    <link rel="stylesheet" href="<?= e($asset('/assets/css/formlar.css')) ?>">
    <link rel="stylesheet" href="<?= e($asset('/assets/css/liquid-glass.css')) ?>">
    <link rel="stylesheet" href="<?= e($asset('/assets/css/mobil.css')) ?>">
</head>
<body class="parent-portal-body">
    <?php require $viewDosyasi; ?>
</body>
</html>
