<?php
declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$allInstitutions = [
    ['contact' => 'Hürrem Nayın Arıca', 'province' => 'İstanbul', 'name' => 'Uğurböceği'],
    ['contact' => 'Derya Çağlar', 'province' => 'Samsun', 'name' => 'Oyun Adası'],
    ['contact' => 'Merve Atalay', 'province' => 'Eskişehir', 'name' => ''],
    ['contact' => 'Enes Tekin / Ayşe Tekin', 'province' => 'Uşak', 'name' => 'Bambino'],
    ['contact' => 'Gülşah Cabbar', 'province' => 'Trabzon', 'name' => 'Kozalaklar Ormanda'],
    ['contact' => 'Şeyma Gürsoy', 'province' => 'Samsun', 'name' => 'Mutlu Ebeveynler'],
    ['contact' => 'Dilay Şişkooğlu', 'province' => 'Bursa', 'name' => 'Çocuk Akademisi'],
    ['contact' => 'Oğuzhan Bey', 'province' => 'Manisa', 'name' => 'Olini'],
    ['contact' => 'Ayşegül Işın', 'province' => 'Çanakkale', 'name' => 'Ayka'],
    ['contact' => 'Aylin Ceylan', 'province' => 'Aksaray', 'name' => 'Bir Çocuk Bir Dünya'],
    ['contact' => 'Büşra Kesemen', 'province' => 'Artvin', 'name' => 'Kampüs'],
    ['contact' => 'Melike Arslan Keleş', 'province' => 'Düzce', 'name' => 'Parla'],
    ['contact' => 'Selma Demirer', 'province' => 'Tekirdağ', 'name' => 'Minimini'],
    ['contact' => 'Şeyna Sarıkaya', 'province' => 'Çankırı', 'name' => 'Masalizi'],
    ['contact' => 'Burcu Öksüz', 'province' => 'Kastamonu', 'name' => 'Sobe'],
    ['contact' => 'Beste Kalpakçıoğlu', 'province' => 'Edirne', 'name' => 'Harmoni'],
    ['contact' => 'Beyza Özfidan', 'province' => 'Kocaeli', 'name' => 'Minik Fidanlar'],
    ['contact' => 'Semanur Ünal', 'province' => 'Ankara', 'name' => 'Hoop'],
    ['contact' => 'Cansın Ersin', 'province' => 'Sivas', 'name' => 'Ege'],
    ['contact' => 'Ozan Samancı', 'province' => 'Diyarbakır', 'name' => 'Çocuk Dünyası'],
    ['contact' => 'Gözde Açıkgöz', 'province' => 'Zonguldak', 'name' => 'Mini Dünya'],
    ['contact' => 'Büşra Topal', 'province' => 'Edirne', 'name' => 'Domingo'],
    ['contact' => 'Büsre Baysal', 'province' => 'Aydın', 'name' => 'Kuş Yuvası'],
    ['contact' => 'Ayşegül Bakalcı', 'province' => 'İzmir', 'name' => 'Muzipo / Bornova'],
    ['contact' => 'Fatoş Kuş', 'province' => 'İzmir', 'name' => 'Muzipo / Narlıdere'],
    ['contact' => 'Diler Kale', 'province' => 'Tekirdağ', 'name' => 'Hayal Dünyam'],
    ['contact' => 'Umut Toskar', 'province' => 'Kayseri', 'name' => 'Hayriye Toskar'],
    ['contact' => 'Leyla Yılmaz', 'province' => 'Düzce', 'name' => 'Minisen'],
    ['contact' => 'Yeliz Çakır', 'province' => 'Ankara', 'name' => 'Minik Kahramanlar'],
    ['contact' => 'Sevda Metin', 'province' => 'Antalya', 'name' => 'Oyun Molası'],
    ['contact' => 'Cansu Başkıran', 'province' => 'Düzce', 'name' => 'Hop Bala'],
    ['contact' => 'Muhammed Mesut Karakayalı', 'province' => 'Antalya', 'name' => 'Talya'],
    ['contact' => 'Eda Saraç', 'province' => 'Sivas', 'name' => 'Neşeli Afacanlar'],
    ['contact' => 'Ezgi Kutlu', 'province' => 'Samsun', 'name' => 'Ezgi Kutlu'],
    ['contact' => 'Seda Nur Saygın', 'province' => 'Sakarya', 'name' => 'Minik Işıklar'],
    ['contact' => 'Gizem Genç', 'province' => 'Aydın', 'name' => 'Star'],
    ['contact' => 'Ebru Has', 'province' => 'İstanbul', 'name' => 'Maya'],
    ['contact' => 'Merve Gülşah Danacı', 'province' => 'Edirne', 'name' => 'Mucit Müzisyenler'],
    ['contact' => 'Duygu Yeniel / Büşra Çamuroğlu', 'province' => 'Kocaeli', 'name' => 'Renkli Düşler'],
    ['contact' => 'Çiğdem Teomançomer', 'province' => 'Edirne', 'name' => 'Mini Kanguru'],
    ['contact' => 'Kübra Fidancı', 'province' => 'İstanbul', 'name' => 'Kübra Fidancı'],
    ['contact' => 'Ayşe Uçak', 'province' => 'Konya', 'name' => 'Ayşe Uçak'],
    ['contact' => 'Mevlüde Berçem Onat', 'province' => 'Diyarbakır', 'name' => 'Birdirbir Gelecek'],
];

$provinces = ['Aksaray', 'Ankara', 'Antalya', 'Artvin', 'Aydın', 'Bursa', 'Çanakkale', 'Çankırı', 'Diyarbakır', 'Düzce', 'Edirne', 'Eskişehir', 'İstanbul', 'İzmir', 'Kastamonu', 'Kayseri', 'Kocaeli', 'Konya', 'Manisa', 'Sakarya', 'Samsun', 'Sivas', 'Tekirdağ', 'Trabzon', 'Uşak', 'Zonguldak'];
$requestedProvince = trim((string) ($_GET['il'] ?? ''));
$selectedProvince = in_array($requestedProvince, $provinces, true) ? $requestedProvince : '';
$institutions = $selectedProvince === ''
    ? []
    : array_values(array_filter($allInstitutions, static fn (array $institution): bool => $institution['province'] === $selectedProvince));

$pageTitle = $selectedProvince !== ''
    ? $selectedProvince . " MEB'e Bağlı Oyun Evleri ve Öğrenci Etkinlik Merkezleri"
    : "İllere Göre MEB'e Bağlı Oyun Evleri";
$year = date('Y');
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($pageTitle) ?> | Oyun Evleri</title>
    <meta name="description" content="MEB'e bağlı oyun evlerini illere göre inceleyin. Listelenen kurumları ve yetkililerini görüntüleyin; MEB'e bağlı kurumlara özel iki ay ücretsiz kullanım fırsatını keşfedin.">
    <meta name="theme-color" content="#f4fbff">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="https://www.oyunevleri.com/mebe-bagli-oyun-evleri/<?= $selectedProvince !== '' ? '?il=' . rawurlencode($selectedProvince) : '' ?>">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='18' fill='%2338aaf5'/%3E%3Cpath d='M18 31 32 19l14 12v16H18V31Z' fill='white'/%3E%3Cpath d='M27 47V35h10v12' fill='%2378cfb4'/%3E%3C/svg%3E">
    <style>
        :root { --ink:#17263b; --muted:#62738a; --blue:#258fd5; --blue-dark:#116da9; --mint:#78cfb7; --mint-dark:#12866d; --line:#dceaf4; --page:#f7fbfe; --white:#fff; --shadow:0 24px 65px rgba(31,88,128,.12); --container:1180px; }
        * { box-sizing:border-box; }
        html { scroll-behavior:smooth; }
        body { margin:0; color:var(--ink); background:var(--page); font-family:Inter,ui-sans-serif,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; line-height:1.6; -webkit-font-smoothing:antialiased; }
        a { color:inherit; text-decoration:none; }
        button,input,select { font:inherit; }
        svg { display:block; }
        .container { width:min(calc(100% - 40px),var(--container)); margin-inline:auto; }
        .site-header { position:sticky; top:0; z-index:30; border-bottom:1px solid rgba(220,234,244,.8); background:rgba(247,251,254,.9); backdrop-filter:blur(18px); }
        .nav { min-height:76px; display:flex; align-items:center; justify-content:space-between; gap:24px; }
        .brand { height:66px; display:inline-flex; align-items:center; overflow:hidden; font-weight:850; }
        .brand-logo { width:186px; height:auto; display:block; }
        .brand-mark { width:42px; height:42px; display:grid; place-items:center; border-radius:14px; color:white; background:linear-gradient(135deg,var(--blue),#70c7f3); box-shadow:0 9px 22px rgba(37,143,213,.25); }
        .brand-mark svg { width:25px; height:25px; }
        .brand-text { font-size:19px; line-height:1.05; }
        .brand-text small { display:block; margin-top:3px; color:var(--muted); font-size:10px; font-weight:750; letter-spacing:.13em; text-transform:uppercase; }
        .nav-links { display:flex; align-items:center; gap:10px; }
        .back-link { display:inline-flex; align-items:center; gap:7px; color:#43566e; font-size:14px; font-weight:800; }
        .button { min-height:47px; display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:0 19px; border:1px solid transparent; border-radius:14px; cursor:pointer; font-weight:850; }
        .button-primary { color:white; background:linear-gradient(135deg,var(--blue),#50b9ed); box-shadow:0 12px 28px rgba(37,143,213,.2); }
        .button-mint { color:white; background:linear-gradient(135deg,var(--mint-dark),#36ae91); }
        .button-light { border-color:var(--line); background:white; }
        .hero { position:relative; overflow:hidden; padding:82px 0 64px; background:linear-gradient(145deg,#edf9ff,#f9fcfe 56%,#effbf7); }
        .hero::after { content:""; position:absolute; width:520px; height:520px; top:-300px; right:-130px; border-radius:50%; border:1px solid rgba(37,143,213,.14); box-shadow:0 0 0 65px rgba(37,143,213,.03),0 0 0 130px rgba(120,207,183,.025); }
        .breadcrumbs { display:flex; flex-wrap:wrap; gap:7px; margin-bottom:25px; color:#708197; font-size:13px; font-weight:700; }
        .breadcrumbs a:hover { color:var(--blue-dark); }
        .hero-grid { position:relative; z-index:1; display:grid; grid-template-columns:1fr 370px; align-items:center; gap:70px; }
        .eyebrow { display:inline-flex; align-items:center; gap:8px; margin-bottom:18px; padding:7px 12px; border:1px solid #c9e8f8; border-radius:999px; color:var(--blue-dark); background:#f3fbff; font-size:11px; font-weight:900; letter-spacing:.09em; text-transform:uppercase; }
        .eyebrow i { width:8px; height:8px; border-radius:50%; background:var(--mint); box-shadow:0 0 0 5px rgba(120,207,183,.18); }
        h1,h2,h3,p { margin-top:0; }
        h1 { max-width:780px; margin-bottom:20px; font-size:clamp(42px,5vw,64px); line-height:1.06; letter-spacing:-.052em; }
        .gradient { color:var(--blue-dark); background:linear-gradient(100deg,var(--blue-dark),#3aa9e7,var(--mint-dark)); background-clip:text; -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        .hero-copy>p { max-width:720px; margin:0; color:var(--muted); font-size:17px; }
        .offer-card { position:relative; padding:30px; border:1px solid rgba(255,255,255,.9); border-radius:27px; color:white; background:linear-gradient(135deg,#153c52,#176d6f); box-shadow:var(--shadow); }
        .offer-label { display:inline-block; margin-bottom:18px; padding:6px 10px; border-radius:999px; color:#b7f4df; background:rgba(255,255,255,.1); font-size:10px; font-weight:900; letter-spacing:.09em; text-transform:uppercase; }
        .offer-card strong { display:block; margin-bottom:3px; font-size:51px; line-height:1; letter-spacing:-.06em; }
        .offer-card h2 { margin-bottom:10px; font-size:21px; }
        .offer-card p { margin-bottom:23px; color:#c6dade; font-size:13px; }
        .offer-card .button { width:100%; }
        .directory { padding:74px 0 100px; }
        .source-note { display:flex; align-items:flex-start; gap:13px; margin-bottom:30px; padding:18px 20px; border:1px solid #cfe4f1; border-radius:17px; color:#52687e; background:#eef8fe; font-size:13px; }
        .source-note svg { width:23px; height:23px; flex:0 0 auto; color:var(--blue); }
        .source-note p { margin:0; }
        .source-note a { color:var(--blue-dark); font-weight:850; text-decoration:underline; text-underline-offset:3px; }
        .filter-card { display:grid; grid-template-columns:1fr auto; gap:16px; align-items:end; margin-bottom:32px; padding:25px; border:1px solid var(--line); border-radius:22px; background:white; box-shadow:0 15px 42px rgba(31,88,128,.07); }
        .filter-card label { display:grid; gap:8px; color:#465970; font-size:13px; font-weight:850; }
        select,.search-input { width:100%; height:52px; padding:0 15px; border:1px solid #cfe0eb; border-radius:13px; color:var(--ink); outline:none; background:#fbfdff; }
        select:focus,.search-input:focus { border-color:#69bced; box-shadow:0 0 0 4px rgba(37,143,213,.11); }
        .province-intro { display:flex; align-items:end; justify-content:space-between; gap:25px; margin:34px 0 22px; }
        .province-intro h2 { margin:0 0 5px; font-size:29px; letter-spacing:-.035em; }
        .province-intro p { margin:0; color:var(--muted); font-size:14px; }
        .result-count { flex:0 0 auto; padding:8px 13px; border-radius:999px; color:var(--mint-dark); background:#e5f8f1; font-size:12px; font-weight:900; }
        .province-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
        .province-link { display:flex; align-items:center; justify-content:space-between; gap:10px; min-height:61px; padding:0 17px; border:1px solid var(--line); border-radius:15px; background:white; font-size:13px; font-weight:850; transition:transform .2s,border-color .2s,box-shadow .2s; }
        .province-link:hover { transform:translateY(-2px); border-color:#8dcbed; box-shadow:0 12px 25px rgba(31,88,128,.09); }
        .province-link span:last-child { color:var(--blue); font-size:17px; }
        .result-tools { display:grid; grid-template-columns:1fr auto; gap:14px; margin-bottom:19px; }
        .source-date { align-self:center; color:#74859a; font-size:12px; font-weight:750; }
        .institution-list { display:grid; gap:14px; }
        .institution-card { display:grid; grid-template-columns:70px 1fr auto; gap:19px; align-items:center; padding:22px; border:1px solid var(--line); border-radius:20px; background:white; transition:border-color .2s,box-shadow .2s; }
        .institution-card:hover { border-color:#b5dced; box-shadow:0 14px 35px rgba(31,88,128,.08); }
        .institution-number { width:52px; height:52px; display:grid; place-items:center; border-radius:16px; color:var(--blue-dark); background:#e7f6ff; font-size:13px; font-weight:900; }
        .institution-main h3 { margin-bottom:5px; font-size:17px; line-height:1.35; letter-spacing:-.015em; }
        .institution-meta { display:flex; flex-wrap:wrap; gap:8px 15px; color:#6c7d91; font-size:12px; }
        .district { color:var(--mint-dark); font-weight:900; }
        .contact-badge { min-width:190px; display:grid; gap:2px; padding:10px 13px; border-radius:12px; color:var(--blue-dark); background:#edf8ff; font-size:13px; font-weight:900; }
        .contact-badge small { color:#71869a; font-size:9px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
        .empty-state { padding:55px 25px; border:1px dashed #bfd8e6; border-radius:22px; text-align:center; background:white; }
        .empty-icon { width:60px; height:60px; display:grid; place-items:center; margin:0 auto 17px; border-radius:18px; color:var(--blue); background:#e8f6ff; font-size:27px; }
        .empty-state h2 { margin-bottom:8px; font-size:24px; }
        .empty-state p { max-width:620px; margin:0 auto 20px; color:var(--muted); }
        .disclaimer { margin-top:35px; padding:21px; border-left:4px solid #f0b65c; border-radius:5px 15px 15px 5px; color:#735b34; background:#fff8ea; font-size:12px; }
        .disclaimer strong { display:block; margin-bottom:4px; }
        .cta { padding:0 0 90px; }
        .cta-card { display:flex; align-items:center; justify-content:space-between; gap:30px; padding:40px; border-radius:27px; color:white; background:linear-gradient(125deg,#173b55,#177477); }
        .cta-card h2 { margin-bottom:6px; font-size:28px; letter-spacing:-.035em; }
        .cta-card p { margin:0; color:#c6dcdf; font-size:14px; }
        .cta-actions { display:flex; gap:10px; flex:0 0 auto; }
        footer { padding:35px 0; color:#97aabd; background:#112337; font-size:12px; }
        .footer-inner { display:flex; align-items:center; justify-content:space-between; gap:20px; }
        .footer-links { display:flex; gap:20px; color:#c3d0da; font-weight:750; }
        [hidden] { display:none !important; }

        @media(max-width:900px) { .hero-grid { grid-template-columns:1fr; gap:38px; } .offer-card { max-width:470px; } .province-grid { grid-template-columns:repeat(3,1fr); } .cta-card { align-items:flex-start; flex-direction:column; } }
        @media(max-width:650px) {
            .container { width:min(calc(100% - 28px),var(--container)); }
            .nav { min-height:68px; }
            .brand { height:57px; }
            .brand-logo { width:150px; }
            .brand-text { font-size:17px; }
            .brand-text small { font-size:8px; }
            .back-link { display:none; }
            .nav-links .button { min-height:42px; padding:0 13px; font-size:12px; }
            .hero { padding:52px 0 45px; }
            h1 { font-size:39px; }
            .hero-copy>p { font-size:15px; }
            .directory { padding:50px 0 75px; }
            .filter-card { grid-template-columns:1fr; padding:18px; }
            .filter-card .button { width:100%; }
            .province-grid { grid-template-columns:repeat(2,1fr); }
            .province-link { min-height:55px; padding:0 13px; font-size:12px; }
            .province-intro { align-items:flex-start; flex-direction:column; gap:12px; }
            .result-tools { grid-template-columns:1fr; }
            .institution-card { grid-template-columns:48px 1fr; gap:13px; padding:16px; }
            .institution-number { width:44px; height:44px; }
            .contact-badge { grid-column:2; width:max-content; max-width:100%; min-width:0; }
            .cta-card { padding:28px 21px; }
            .cta-actions { width:100%; display:grid; }
            .footer-inner,.footer-links { align-items:flex-start; flex-direction:column; }
        }
    </style>
</head>
<body>
<svg width="0" height="0" aria-hidden="true" style="position:absolute">
    <symbol id="home" viewBox="0 0 24 24"><path d="M3 11.5 12 4l9 7.5M5.5 10v10h13V10M9 20v-6h6v6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    <symbol id="info" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 11v6M12 7.5v.2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
</svg>

<header class="site-header">
    <div class="container nav">
        <a class="brand" href="/" aria-label="Oyun Evleri ana sayfa"><img class="brand-logo" src="/oyun_Evleri_son_logo.png" alt="Oyun Evleri Yönetim Yazılımı"></a>
        <div class="nav-links"><a class="back-link" href="/">← Ana sayfaya dön</a><a class="button button-primary" href="mailto:info@oyunevleri.com?subject=MEB%27e%20Ba%C4%9Fl%C4%B1%20Kurum%20-%202%20Ay%20%C3%9Ccretsiz%20Kullan%C4%B1m">2 ay ücretsiz kullan</a></div>
    </div>
</header>

<main>
    <section class="hero">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Sayfa yolu"><a href="/">Ana Sayfa</a><span>/</span><span>MEB Kurum Rehberi</span><?= $selectedProvince !== '' ? '<span>/</span><span>' . e($selectedProvince) . '</span>' : '' ?></nav>
            <div class="hero-grid">
                <div class="hero-copy"><span class="eyebrow"><i></i> 43 kurumluk özel rehber</span><h1>İllere göre <span class="gradient">MEB'e bağlı</span> oyun evleri</h1><p>MEB'e bağlı oyun evlerini şehir, kurum adı ve yetkili bilgileriyle inceleyin. Rehber yalnızca doğrulanarak tarafımıza iletilen 43 kurumu içerir.</p></div>
                <aside class="offer-card"><span class="offer-label">MEB'e bağlı kurumlara özel</span><strong>2 ay</strong><h2>ücretsiz kullanım hakkı</h2><p>Operasyonunuzu Oyun Evleri Yönetim Sistemi'ne taşıyın, tüm özellikleri iki ay ücretsiz deneyin.</p><a class="button button-mint" href="mailto:info@oyunevleri.com?subject=MEB%27e%20Ba%C4%9Fl%C4%B1%20Kurum%20-%202%20Ay%20%C3%9Ccretsiz%20Kullan%C4%B1m">Başvuru yap</a></aside>
            </div>
        </div>
    </section>

    <section class="directory">
        <div class="container">
            <div class="source-note"><svg aria-hidden="true"><use href="#info"/></svg><p>Bu sayfa yalnızca tarafımıza iletilen 43 MEB'e bağlı oyun evini gösterir. Listede bulunmayan kurumlar bu rehbere dahil edilmez. Bir kurumun güncel resmî statüsünü ayrıca <a href="https://ookgm.meb.gov.tr/kurumlar.php?tur=ogrencietkinlik" target="_blank" rel="noopener noreferrer">MEB kurum sorgusundan</a> kontrol edebilirsiniz.</p></div>

            <form class="filter-card" method="get" action="/mebe-bagli-oyun-evleri/">
                <label>İl seçin<select name="il" required><option value="">Kurumların listeleneceği ili seçin</option><?php foreach ($provinces as $province): ?><option value="<?= e($province) ?>" <?= $province === $selectedProvince ? 'selected' : '' ?>><?= e($province) ?></option><?php endforeach; ?></select></label>
                <button class="button button-primary" type="submit">Kurumları listele</button>
            </form>

            <?php if ($selectedProvince === ''): ?>
                <div class="province-intro"><div><h2>Listedeki iller</h2><p>Paylaşılan 43 oyun evini şehir bazında görüntüleyin.</p></div><span class="result-count"><?= count($provinces) ?> il · <?= count($allInstitutions) ?> kurum</span></div>
                <div class="province-grid"><?php foreach ($provinces as $province): ?><a class="province-link" href="?il=<?= rawurlencode($province) ?>"><span><?= e($province) ?></span><span>→</span></a><?php endforeach; ?></div>
            <?php else: ?>
                <div class="province-intro"><div><h2><?= e($selectedProvince) ?> kurum listesi</h2><p>Paylaşılan MEB'e bağlı oyun evleri</p></div><span class="result-count"><?= count($institutions) ?> kurum</span></div>
                <?php if ($institutions !== []): ?>
                    <div class="result-tools"><input class="search-input" type="search" placeholder="Kurum veya yetkili adında ara..." aria-label="Kurumlarda ara" data-directory-search><span class="source-date">Toplam liste: <?= count($allInstitutions) ?> kurum</span></div>
                    <div class="institution-list" data-institution-list>
                        <?php foreach ($institutions as $index => $institution): ?>
                            <article class="institution-card" data-institution-card data-search-value="<?= e(mb_strtolower($institution['name'] . ' ' . $institution['contact'] . ' ' . $institution['province'], 'UTF-8')) ?>"><span class="institution-number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><div class="institution-main"><h3><?= e($institution['name'] !== '' ? $institution['name'] : $institution['contact']) ?></h3><div class="institution-meta"><span class="district"><?= e($institution['province']) ?></span><?php if ($institution['name'] === ''): ?><span>Kurum adı belirtilmedi</span><?php endif; ?></div></div><span class="contact-badge"><small>Kurum yetkilisi</small><?= e($institution['contact']) ?></span></article>
                        <?php endforeach; ?>
                    </div>
                    <div class="empty-state" hidden data-search-empty><span class="empty-icon">⌕</span><h2>Aramanızla eşleşen kurum bulunamadı</h2><p>Farklı bir kurum adı, ilçe veya adres ifadesi deneyebilirsiniz.</p></div>
                <?php else: ?>
                    <div class="empty-state"><span class="empty-icon">⌕</span><h2>Bu il için kurum bulunamadı</h2><p>Rehber yalnızca tarafımıza iletilen 43 kurumdan oluşur.</p></div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="disclaimer"><strong>Bilgilendirme</strong>Bu rehber yalnızca tarafımıza iletilen 43 kurumluk listeyi yansıtır; Türkiye'deki tüm resmî kurumların genel MEB dökümü değildir. Oyun Evleri Yönetim Sistemi, Millî Eğitim Bakanlığına ait veya Bakanlık tarafından işletilen bir hizmet değildir.</div>
        </div>
    </section>

    <section class="cta"><div class="container"><div class="cta-card"><div><h2>MEB'e bağlı kurumunuza iki ay bizden</h2><p>Öğrenci, veli, program, tahsilat ve onam süreçlerini tek ekrandan yönetmeye başlayın.</p></div><div class="cta-actions"><a class="button button-mint" href="mailto:info@oyunevleri.com?subject=MEB%27e%20Ba%C4%9Fl%C4%B1%20Kurum%20-%202%20Ay%20%C3%9Ccretsiz%20Kullan%C4%B1m">Ücretsiz kullanım iste</a><a class="button button-light" href="/">Ürünü incele</a></div></div></div></section>
</main>

<footer><div class="container footer-inner"><span>© <?= e((string) $year) ?> Oyun Evleri Yönetim Sistemi</span><nav class="footer-links"><a href="/">Ana sayfa</a><a href="https://app.oyunevleri.com/giris">Sisteme giriş</a><a href="mailto:info@oyunevleri.com">İletişim</a></nav></div></footer>

<script>
    (() => {
        const input = document.querySelector('[data-directory-search]');
        if (!input) return;
        const cards = [...document.querySelectorAll('[data-institution-card]')];
        const empty = document.querySelector('[data-search-empty]');
        const normalize = value => value.toLocaleLowerCase('tr-TR').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        input.addEventListener('input', () => {
            const query = normalize(input.value.trim());
            let visible = 0;
            cards.forEach(card => {
                const match = normalize(card.dataset.searchValue || '').includes(query);
                card.hidden = !match;
                if (match) visible++;
            });
            if (empty) empty.hidden = visible !== 0;
        });
    })();
</script>
</body>
</html>
