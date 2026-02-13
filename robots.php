<?php
header('Content-Type: text/plain; charset=utf-8');
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '';
$base = $host !== '' ? ($scheme . '://' . $host) : '';

echo "User-agent: *\n";
echo "Allow: /\n";
if ($base !== '') {
    echo "Sitemap: " . $base . "/sitemap.xml\n";
}
