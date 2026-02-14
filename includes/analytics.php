<?php
$meta_desc_val = $meta_desc ?? '';
$canonical_val = $canonical_url ?? '';
$meta_title_val = $meta_title ?? '';
$og_title_val = $og_title ?? '';
$og_desc_val = $og_desc ?? '';
$og_image_val = $og_image ?? '';
$og_url_val = $og_url ?? '';
$og_type_val = $og_type ?? '';
if ($meta_desc_val === '') {
    $meta_desc_val = 'Oyunevleri.com ile oyun evleri, anaokulları ve kreşleri keşfedin, filtreleyin ve iletişime geçin.';
}
if ($canonical_val === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if ($host !== '') {
        $canonical_val = $scheme . '://' . $host . $uri;
    }
}
$base_url = '';
if (!empty($canonical_val)) {
    $base_url = preg_replace('#(https?://[^/]+).*#', '$1', $canonical_val);
}
if ($meta_title_val === '') {
    $meta_title_val = 'Oyunevleri.com';
}
if ($og_title_val === '') {
    $og_title_val = $meta_title_val;
}
if ($og_desc_val === '') {
    $og_desc_val = $meta_desc_val;
}
if ($og_url_val === '') {
    $og_url_val = $canonical_val;
}
if ($og_image_val === '') {
    $og_image_val = $base_url !== '' ? ($base_url . '/assets/og-default.png') : '/assets/og-default.png';
} elseif (!preg_match('#^https?://#', $og_image_val) && $base_url !== '') {
    $og_image_val = $base_url . '/' . ltrim($og_image_val, '/');
}
if ($og_type_val === '') {
    $og_type_val = 'website';
}
?>
<meta name="description" content="<?php echo htmlspecialchars($meta_desc_val, ENT_QUOTES, 'UTF-8'); ?>">
<?php if ($canonical_val !== '') { ?>
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_val, ENT_QUOTES, 'UTF-8'); ?>">
<?php } ?>
<meta property="og:site_name" content="Oyunevleri.com">
<meta property="og:title" content="<?php echo htmlspecialchars($og_title_val, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($og_desc_val, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:url" content="<?php echo htmlspecialchars($og_url_val, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:type" content="<?php echo htmlspecialchars($og_type_val, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:image" content="<?php echo htmlspecialchars($og_image_val, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo htmlspecialchars($og_title_val, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="twitter:description" content="<?php echo htmlspecialchars($og_desc_val, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="twitter:image" content="<?php echo htmlspecialchars($og_image_val, ENT_QUOTES, 'UTF-8'); ?>">
<!-- Google tag (gtag.js) -->
<link rel="icon" type="image/x-icon" href="/favicon.ico">
<link rel="shortcut icon" href="/favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="48x48" href="/favicon-48x48.png">
<link rel="icon" type="image/png" sizes="192x192" href="/favicon-192x192.png">
<link rel="apple-touch-icon" sizes="180x180" href="/favicon-180x180.png">
<script async src="https://www.googletagmanager.com/gtag/js?id=G-HX90LVYB1B"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-HX90LVYB1B');
</script>
