<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$lokasyon_slug = trim($_GET['lokasyon'] ?? '');
$kategori_slug = trim($_GET['kategori'] ?? '');

if ($lokasyon_slug === '' || $kategori_slug === '') {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$kategori_map = [
    'oyun-gruplari' => ['label' => 'Oyun Grupları', 'types' => ['Oyun Grubu', 'Oyun Evi', 'Oyun Grubu']],
    'oyun-evi' => ['label' => 'Oyun Evi', 'types' => ['Oyun Evi']],
    'oyun-evleri' => ['label' => 'Oyun Evleri', 'types' => ['Oyun Evi']],
    'anaokulu' => ['label' => 'Anaokulu', 'types' => ['Anaokulu']],
    'kres' => ['label' => 'Kreş', 'types' => ['Kreş']],
];

if (!isset($kategori_map[$kategori_slug])) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$kategori_label = $kategori_map[$kategori_slug]['label'];
$kategori_types = $kategori_map[$kategori_slug]['types'];

$sehir = '';
$ilce = '';
$kurumlar = [];
$en_iyi = [];

if (!empty($db_master)) {
    $stmt = $db_master->query("SELECT DISTINCT sehir FROM kurumlar WHERE durum = 1 AND sehir IS NOT NULL AND sehir <> ''");
    $sehirler = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $stmt = $db_master->query("SELECT DISTINCT ilce, sehir FROM kurumlar WHERE durum = 1 AND ilce IS NOT NULL AND ilce <> ''");
    $ilceler = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sehir_slug_map = [];
    foreach ($sehirler as $s) {
        $sehir_slug_map[seo_slugify($s)] = $s;
    }
    $ilce_slug_map = [];
    foreach ($ilceler as $row) {
        $ilce_slug = seo_slugify($row['ilce'] ?? '');
        if ($ilce_slug !== '') {
            $ilce_slug_map[$ilce_slug] = ['ilce' => $row['ilce'], 'sehir' => $row['sehir'] ?? ''];
        }
    }

    if (isset($sehir_slug_map[$lokasyon_slug])) {
        $sehir = $sehir_slug_map[$lokasyon_slug];
    } elseif (isset($ilce_slug_map[$lokasyon_slug])) {
        $ilce = $ilce_slug_map[$lokasyon_slug]['ilce'];
        $sehir = $ilce_slug_map[$lokasyon_slug]['sehir'];
    } else {
        http_response_code(404);
        include __DIR__ . '/404.php';
        exit;
    }

    $where = "WHERE k.durum = 1";
    $params = [];
    if ($sehir !== '') {
        $where .= " AND k.sehir = :sehir";
        $params['sehir'] = $sehir;
    }
    if ($ilce !== '') {
        $where .= " AND k.ilce = :ilce";
        $params['ilce'] = $ilce;
    }
    if (!empty($kategori_types)) {
        $placeholders = [];
        foreach ($kategori_types as $idx => $t) {
            $key = 't' . $idx;
            $placeholders[] = ':' . $key;
            $params[$key] = $t;
        }
        $where .= " AND k.kurum_type IN (" . implode(',', $placeholders) . ")";
    }

    $sql = "SELECT k.*, 
            (SELECT g.gorsel_yol FROM kurum_galeri g WHERE g.kurum_id = k.id ORDER BY g.sira ASC, g.id ASC LIMIT 1) AS kapak_gorsel
        FROM kurumlar k
        {$where}
        ORDER BY k.kayit_tarihi DESC
        LIMIT 30";
    $stmt = $db_master->prepare($sql);
    $stmt->execute($params);
    $kurumlar = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT k.*, AVG(y.puan) AS avg_puan, COUNT(y.id) AS yorum_sayisi
        FROM kurumlar k
        LEFT JOIN kurum_yorumlar y ON y.kurum_id = k.id
        {$where}
        GROUP BY k.id
        ORDER BY avg_puan DESC, yorum_sayisi DESC, k.kayit_tarihi DESC
        LIMIT 10";
    $stmt = $db_master->prepare($sql);
    $stmt->execute($params);
    $en_iyi = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$loc_label = $ilce !== '' ? ($ilce . ' ' . $sehir) : $sehir;
$meta_title = $loc_label . ' En İyi ' . $kategori_label . ' | Oyunevleri.com';
$meta_desc = $loc_label . ' bölgesindeki ' . $kategori_label . ' kurumlarını keşfedin. Fiyat, yorum ve hizmetleri karşılaştırın.';

$base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
$canonical_url = $base . '/' . $lokasyon_slug . '-' . $kategori_slug;

$breadcrumb = [
    ['name' => 'Anasayfa', 'url' => $base . '/'],
    ['name' => $loc_label, 'url' => $base . '/search.php?sehir=' . urlencode($sehir) . ($ilce !== '' ? '&ilce=' . urlencode($ilce) : '')],
    ['name' => $kategori_label, 'url' => $canonical_url],
];
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($meta_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_desc, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url, ENT_QUOTES, 'UTF-8'); ?>">
    <?php require_once("includes/analytics.php"); ?>
    <link rel="icon" type="image/x-icon" href="favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#f9fafc; --ink:#1f2937; --muted:#6b7280; --primary:#ff7a59; --card:#fff; --stroke:rgba(31,41,55,0.08); --shadow:0 20px 45px rgba(31,41,55,0.12); }
        * { box-sizing: border-box; }
        body { margin:0; font-family:"Manrope",system-ui; color:var(--ink); background: radial-gradient(900px 500px at 0% -10%, #ffe6dd 0%, transparent 60%), radial-gradient(700px 350px at 100% 0%, #dff5f2 0%, transparent 65%), var(--bg); }
        .container { max-width:1180px; margin:0 auto; padding:0 24px; }
        .page-head { padding:24px 0 10px; }
        .breadcrumb { font-size:13px; color:var(--muted); margin-bottom:12px; }
        .breadcrumb a { color:var(--muted); text-decoration:none; }
        .breadcrumb span { margin:0 6px; }
        h1 { font-family:"Baloo 2",cursive; margin:0 0 8px; }
        .lead { color:var(--muted); margin:0; }
        .cards { display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:18px; margin-top:18px; }
        .card { background:var(--card); border:1px solid var(--stroke); border-radius:18px; padding:16px; box-shadow:0 10px 24px rgba(31,41,55,0.08); }
        .card .tag { display:inline-flex; gap:6px; font-size:12px; padding:6px 10px; border-radius:999px; background:#f1f5f9; margin-bottom:10px; }
        .card .meta { color:var(--muted); font-size:14px; }
        .btn { border:none; padding:10px 16px; border-radius:12px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
        .btn-outline { background:#fff; color:var(--ink); border:1px solid var(--stroke); }
        .section { padding:20px 0 40px; }
        @media (max-width: 980px) { .cards { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php $public_nav_active = 'storage'; require_once("includes/public_header.php"); ?>

    <section class="page-head">
        <div class="container">
            <div class="breadcrumb">
                <?php foreach ($breadcrumb as $i => $crumb) { ?>
                    <a href="<?php echo htmlspecialchars($crumb['url'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($crumb['name'], ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php if ($i < count($breadcrumb) - 1) { ?><span>›</span><?php } ?>
                <?php } ?>
            </div>
            <h1><?php echo htmlspecialchars($loc_label . ' ' . $kategori_label, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="lead"><?php echo htmlspecialchars($meta_desc, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2>En iyi 10 <?php echo htmlspecialchars($kategori_label, ENT_QUOTES, 'UTF-8'); ?></h2>
            <div class="cards">
                <?php if (empty($en_iyi)) { ?>
                    <div class="card">Henüz liste bulunamadı.</div>
                <?php } else { ?>
                    <?php foreach ($en_iyi as $kurum) {
                        $tags = [];
                        if (!empty($kurum['kurum_type'])) { $tags[] = $kurum['kurum_type']; }
                        $lokasyon = trim(($kurum['ilce'] ?? '') . ' ' . ($kurum['sehir'] ?? ''));
                        $seo_url = kurum_seo_url($kurum);
                        ?>
                        <div class="card">
                            <?php if (!empty($tags)) { ?>
                                <span class="tag"><?php echo htmlspecialchars($tags[0], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php } ?>
                            <h3><?php echo htmlspecialchars($kurum['kurum_adi'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class="meta"><?php echo htmlspecialchars($lokasyon, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php if (!empty($kurum['kapak_gorsel'])) { ?>
                                <img src="<?php echo htmlspecialchars($kurum['kapak_gorsel'], ENT_QUOTES, 'UTF-8'); ?>" alt="Kurum" style="width:100%;border-radius:12px;margin-top:8px;">
                            <?php } ?>
                            <a class="btn btn-outline" href="<?php echo htmlspecialchars($seo_url, ENT_QUOTES, 'UTF-8'); ?>">Detayı Gör</a>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2><?php echo htmlspecialchars($loc_label, ENT_QUOTES, 'UTF-8'); ?> bölgesindeki tüm kurumlar</h2>
            <div class="cards">
                <?php if (empty($kurumlar)) { ?>
                    <div class="card">Listelenecek kurum bulunamadı.</div>
                <?php } else { ?>
                    <?php foreach ($kurumlar as $kurum) {
                        $tags = [];
                        if (!empty($kurum['kurum_type'])) { $tags[] = $kurum['kurum_type']; }
                        $lokasyon = trim(($kurum['ilce'] ?? '') . ' ' . ($kurum['sehir'] ?? ''));
                        $seo_url = kurum_seo_url($kurum);
                        ?>
                        <div class="card">
                            <?php if (!empty($tags)) { ?>
                                <span class="tag"><?php echo htmlspecialchars($tags[0], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php } ?>
                            <h3><?php echo htmlspecialchars($kurum['kurum_adi'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class="meta"><?php echo htmlspecialchars($lokasyon, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php if (!empty($kurum['kapak_gorsel'])) { ?>
                                <img src="<?php echo htmlspecialchars($kurum['kapak_gorsel'], ENT_QUOTES, 'UTF-8'); ?>" alt="Kurum" style="width:100%;border-radius:12px;margin-top:8px;">
                            <?php } ?>
                            <a class="btn btn-outline" href="<?php echo htmlspecialchars($seo_url, ENT_QUOTES, 'UTF-8'); ?>">Detayı Gör</a>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
    </section>

    <?php
    $breadcrumb_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [],
    ];
    foreach ($breadcrumb as $idx => $crumb) {
        $breadcrumb_schema['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $idx + 1,
            'name' => $crumb['name'],
            'item' => $crumb['url'],
        ];
    }
    $breadcrumb_json = json_encode($breadcrumb_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($breadcrumb_json) {
        echo '<script type="application/ld+json">' . $breadcrumb_json . '</script>';
    }
    ?>
</body>
</html>
