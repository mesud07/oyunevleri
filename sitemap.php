<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '';
$base = $host !== '' ? ($scheme . '://' . $host) : '';

$urls = [];
if ($base !== '') {
    $urls[] = [
        'loc' => $base . '/',
        'lastmod' => date('c'),
        'changefreq' => 'daily',
        'priority' => '1.0',
    ];
}

if (!empty($db_master)) {
    $stmt = $db_master->query("SELECT id, kurum_adi, slug, sehir, ilce, kurum_type, kayit_tarihi FROM kurumlar WHERE durum = 1");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $update_stmt = $db_master->prepare("UPDATE kurumlar SET slug = :slug WHERE id = :id");

    $kategori_slug_map = [
        'anaokulu' => ['Anaokulu'],
        'kres' => ['Kreş'],
        'oyun-gruplari' => ['Oyun Grubu', 'Oyun Evi', 'Oyun Grubu'],
    ];
    $category_pairs = [];

    foreach ($rows as $row) {
        $sehir_slug = seo_slugify($row['sehir'] ?? '');
        $ilce_slug = seo_slugify($row['ilce'] ?? '');
        $slug = trim((string) ($row['slug'] ?? ''));
        if ($slug === '') {
            $slug = kurum_slug_uret($row['kurum_adi'] ?? '');
            if ($slug !== '' && (int) $row['id'] > 0) {
                try {
                    $update_stmt->execute(['slug' => $slug, 'id' => (int) $row['id']]);
                } catch (Throwable $e) {
                    // ignore
                }
            }
        }

        if ($base === '' || $sehir_slug === '' || $ilce_slug === '' || $slug === '') {
            continue;
        }

        $lastmod = '';
        if (!empty($row['kayit_tarihi'])) {
            $ts = strtotime($row['kayit_tarihi']);
            if ($ts) {
                $lastmod = date('c', $ts);
            }
        }

        $urls[] = [
            'loc' => $base . '/' . $sehir_slug . '/' . $ilce_slug . '/' . $slug,
            'lastmod' => $lastmod,
            'changefreq' => 'weekly',
            'priority' => '0.7',
        ];

        $type = (string) ($row['kurum_type'] ?? '');
        foreach ($kategori_slug_map as $cat_slug => $types) {
            if (in_array($type, $types, true) && $sehir_slug !== '') {
                $category_pairs[$sehir_slug . '|' . $cat_slug] = $base . '/' . $sehir_slug . '-' . $cat_slug;
            }
        }
    }

    foreach ($category_pairs as $url) {
        $urls[] = [
            'loc' => $url,
            'lastmod' => date('c'),
            'changefreq' => 'weekly',
            'priority' => '0.6',
        ];
    }
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $url) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($url['loc'], ENT_QUOTES, 'UTF-8') . "</loc>\n";
    if (!empty($url['lastmod'])) {
        echo '    <lastmod>' . htmlspecialchars($url['lastmod'], ENT_QUOTES, 'UTF-8') . "</lastmod>\n";
    }
    if (!empty($url['changefreq'])) {
        echo '    <changefreq>' . htmlspecialchars($url['changefreq'], ENT_QUOTES, 'UTF-8') . "</changefreq>\n";
    }
    if (!empty($url['priority'])) {
        echo '    <priority>' . htmlspecialchars($url['priority'], ENT_QUOTES, 'UTF-8') . "</priority>\n";
    }
    echo "  </url>\n";
}
echo '</urlset>';
