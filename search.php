<?php
// www.oyunevleri.com listing page
require_once("includes/config.php");

$sehir = trim($_GET['sehir'] ?? '');
$ilce = trim($_GET['ilce'] ?? '');
$yas_araligi = trim($_GET['yas_araligi'] ?? '');
$kurum_type = trim($_GET['kurum_type'] ?? '');
$min_fiyat = trim($_GET['min_fiyat'] ?? '');
$max_fiyat = trim($_GET['max_fiyat'] ?? '');
$meb_onay = !empty($_GET['meb_onay']) ? 1 : 0;
$aile_onay = !empty($_GET['aile_sosyal_onay']) ? 1 : 0;
$bahceli = !empty($_GET['hizmet_bahceli']) ? 1 : 0;
$kamera = !empty($_GET['hizmet_guvenlik']) ? 1 : 0;
$ingilizce = !empty($_GET['hizmet_ingilizce']) ? 1 : 0;
$sort = trim($_GET['sort'] ?? 'kayit');

$yas_min = null;
$yas_max = null;
if (preg_match('/^(\d+)\-(\d+)$/', $yas_araligi, $m)) {
    $yas_min = (int) $m[1];
    $yas_max = (int) $m[2];
}

$where = ["k.durum = 1"];
$params = [];

if ($sehir !== '') {
    $where[] = "k.sehir = :sehir";
    $params['sehir'] = $sehir;
}
if ($ilce !== '') {
    $where[] = "k.ilce = :ilce";
    $params['ilce'] = $ilce;
}
if (!is_null($yas_min) && !is_null($yas_max)) {
    $where[] = "(k.min_ay IS NULL OR k.min_ay <= :yas_max)";
    $where[] = "(k.max_ay IS NULL OR k.max_ay >= :yas_min)";
    $params['yas_min'] = $yas_min;
    $params['yas_max'] = $yas_max;
}
if ($meb_onay) {
    $where[] = "k.meb_onay = 1";
}
if ($aile_onay) {
    $where[] = "k.aile_sosyal_onay = 1";
}
if ($bahceli) {
    $where[] = "k.hizmet_bahceli = 1";
}
if ($kamera) {
    $where[] = "k.hizmet_guvenlik_kamerasi = 1";
}
if ($ingilizce) {
    $where[] = "k.hizmet_ingilizce = 1";
}
if ($kurum_type !== '') {
    $where[] = "k.kurum_type = :kurum_type";
    $params['kurum_type'] = $kurum_type;
}
if ($min_fiyat !== '' && is_numeric($min_fiyat)) {
    $where[] = "kf.min_fiyat >= :min_fiyat";
    $params['min_fiyat'] = (float) $min_fiyat;
}
if ($max_fiyat !== '' && is_numeric($max_fiyat)) {
    $where[] = "kf.min_fiyat <= :max_fiyat";
    $params['max_fiyat'] = (float) $max_fiyat;
}

$order_by = "k.kayit_tarihi DESC";
if ($sort === 'puan') {
    $order_by = "ky.avg_puan DESC";
} elseif ($sort === 'fiyat') {
    $order_by = "kf.min_fiyat ASC";
}

$kurumlar = [];
if (!empty($db_master)) {
    $sql = "SELECT k.*,
                (SELECT g.gorsel_yol FROM kurum_galeri g WHERE g.kurum_id = k.id ORDER BY g.sira ASC, g.id ASC LIMIT 1) AS kapak_gorsel,
                kf.min_fiyat,
                kf.max_fiyat,
                COALESCE(ky.avg_puan, 0) AS avg_puan,
                COALESCE(ky.yorum_sayisi, 0) AS yorum_sayisi
            FROM kurumlar k
            LEFT JOIN (
                SELECT kurum_id, MIN(fiyat) AS min_fiyat, MAX(fiyat) AS max_fiyat
                FROM kurum_fiyatlar
                GROUP BY kurum_id
            ) kf ON kf.kurum_id = k.id
            LEFT JOIN (
                SELECT kurum_id, AVG(puan) AS avg_puan, COUNT(*) AS yorum_sayisi
                FROM kurum_yorumlar
                GROUP BY kurum_id
            ) ky ON ky.kurum_id = k.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY {$order_by}
            LIMIT 50";
    $stmt = $db_master->prepare($sql);
    $stmt->execute($params);
    $kurumlar = $stmt->fetchAll();
}

$chip_sehir = $sehir !== '' ? $sehir : 'Tüm şehirler';
$chip_ilce = $ilce !== '' ? $ilce : 'Tüm ilçeler';
$chip_yas = $yas_araligi !== '' ? $yas_araligi . ' Ay' : 'Tüm yaşlar';
$chip_type = $kurum_type !== '' ? $kurum_type : 'Tüm türler';
$toplam = count($kurumlar);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Oyunevleri.com | Listeleme</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f9fafc;
            --ink: #1f2937;
            --muted: #6b7280;
            --primary: #ff7a59;
            --primary-dark: #ea6a4b;
            --accent: #6fd3c5;
            --accent-2: #ffd36e;
            --card: #ffffff;
            --stroke: rgba(31,41,55,0.08);
            --shadow: 0 20px 45px rgba(31,41,55,0.12);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Manrope", system-ui, -apple-system, sans-serif;
            color: var(--ink);
            background: radial-gradient(900px 500px at 0% -10%, #ffe6dd 0%, transparent 60%),
                        radial-gradient(700px 350px at 100% 0%, #dff5f2 0%, transparent 65%),
                        var(--bg);
        }

        .container {
            max-width: 1180px;
            margin: 0 auto;
            padding: 0 24px;
        }

        header {
            padding: 22px 0 12px;
        }

        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .logo {
            font-family: "Baloo 2", cursive;
            font-size: 26px;
            font-weight: 700;
            color: var(--ink);
            text-decoration: none;
        }

        .nav-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            border: none;
            padding: 10px 16px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 12px 24px rgba(255, 122, 89, 0.35);
        }

        .btn-outline {
            background: #fff;
            color: var(--ink);
            border: 1px solid var(--stroke);
        }

        .summary {
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
        }

        .summary-left {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .chip {
            background: #fff;
            border: 1px solid var(--stroke);
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 13px;
            color: var(--muted);
        }

        .layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 24px;
            margin-top: 22px;
        }

        .filters {
            background: #fff;
            border-radius: 18px;
            border: 1px solid var(--stroke);
            padding: 18px;
            box-shadow: 0 8px 22px rgba(31,41,55,0.08);
            position: sticky;
            top: 20px;
            height: fit-content;
        }

        .filters h3 {
            margin: 0 0 8px;
            font-size: 18px;
        }

        .filters .group {
            margin-top: 16px;
        }

        .filters label {
            display: flex;
            gap: 8px;
            align-items: center;
            font-size: 14px;
            margin-top: 8px;
            color: var(--muted);
        }

        .filters input[type="checkbox"] {
            accent-color: var(--primary);
        }

        .filters input[type="text"],
        .filters select {
            width: 100%;
            border-radius: 10px;
            border: 1px solid var(--stroke);
            padding: 10px 12px;
            margin-top: 8px;
        }

        .results-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .results-header h1 {
            font-family: "Baloo 2", cursive;
            font-size: 30px;
            margin: 0;
        }

        .results-header select {
            border-radius: 10px;
            border: 1px solid var(--stroke);
            padding: 8px 12px;
            background: #fff;
        }

        .cards {
            margin-top: 16px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--stroke);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 14px 28px rgba(31,41,55,0.08);
            display: grid;
            grid-template-columns: 140px 1fr;
            animation: fadeUp 0.6s ease forwards;
            opacity: 0;
        }

        .card:nth-child(1) { animation-delay: 0.05s; }
        .card:nth-child(2) { animation-delay: 0.1s; }
        .card:nth-child(3) { animation-delay: 0.15s; }
        .card:nth-child(4) { animation-delay: 0.2s; }

        .thumb {
            background: linear-gradient(140deg, #ffd9cf, #d7f3f0);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #6b7280;
            font-size: 14px;
            overflow: hidden;
        }

        .thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .card-body {
            padding: 14px 16px;
        }

        .tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 8px;
        }

        .tag {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 999px;
            background: #f1f5f9;
        }

        .card h3 {
            margin: 0 0 6px;
        }

        .meta {
            color: var(--muted);
            font-size: 13px;
        }

        .price {
            margin-top: 10px;
            font-weight: 700;
        }

        .card-actions {
            display: flex;
            gap: 10px;
            margin-top: 12px;
        }

        .banner {
            margin-top: 24px;
            padding: 16px 18px;
            border-radius: 14px;
            background: linear-gradient(135deg, #ffe9e2, #e5f7f4);
            border: 1px solid var(--stroke);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        @keyframes fadeUp {
            from { transform: translateY(12px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @media (max-width: 980px) {
            .layout { grid-template-columns: 1fr; }
            .cards { grid-template-columns: 1fr; }
            .filters { position: static; }
        }
        <?php require_once("includes/public_header.css.php"); ?>
    </style>
</head>
<body>
    <?php $public_nav_active = 'storage'; require_once("includes/public_header.php"); ?>

    <section class="container">
        <div class="summary">
            <div class="summary-left">
                <span class="chip"><?php echo htmlspecialchars($chip_sehir, ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="chip"><?php echo htmlspecialchars($chip_ilce, ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="chip"><?php echo htmlspecialchars($chip_yas, ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="chip"><?php echo htmlspecialchars($chip_type, ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="chip">Toplam <?php echo $toplam; ?> kurum</span>
            </div>
            <div>
                <a class="btn btn-outline" href="index.php">Aramayı Düzenle</a>
            </div>
        </div>
    </section>

    <section class="container layout">
        <aside class="filters">
            <form method="get" action="search.php">
            <h3>Filtreler</h3>
            <div class="group">
                <label>Fiyat Aralığı</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <input type="text" name="min_fiyat" placeholder="Min ₺" value="<?php echo htmlspecialchars($min_fiyat, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="text" name="max_fiyat" placeholder="Max ₺" value="<?php echo htmlspecialchars($max_fiyat, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
            <div class="group">
                <label>Kurum Türü</label>
                <?php
                $kurum_type_options = ['Oyun Evi', 'Anaokulu', 'Kreş'];
                if ($kurum_type !== '' && !in_array($kurum_type, $kurum_type_options, true)) {
                    $kurum_type_options[] = $kurum_type;
                }
                ?>
                <select name="kurum_type">
                    <option value="">Tümü</option>
                    <?php foreach ($kurum_type_options as $tur) { ?>
                        <option value="<?php echo htmlspecialchars($tur, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $kurum_type === $tur ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tur, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="group">
                <label>Yaş Aralığı</label>
                <select name="yas_araligi">
                    <option value="">Tümü</option>
                    <option value="0-24" <?php echo $yas_araligi === '0-24' ? 'selected' : ''; ?>>0-24 Ay</option>
                    <option value="24-48" <?php echo $yas_araligi === '24-48' ? 'selected' : ''; ?>>24-48 Ay</option>
                    <option value="48-72" <?php echo $yas_araligi === '48-72' ? 'selected' : ''; ?>>48-72 Ay</option>
                </select>
            </div>
            <div class="group">
                <label>Bakanlık Onayı</label>
                <label><input type="checkbox" name="meb_onay" value="1" <?php echo $meb_onay ? 'checked' : ''; ?>> MEB Bağlı</label>
                <label><input type="checkbox" name="aile_sosyal_onay" value="1" <?php echo $aile_onay ? 'checked' : ''; ?>> Aile Sosyal Bağlı</label>
            </div>
            <div class="group">
                <label>Hizmetler</label>
                <label><input type="checkbox" name="hizmet_bahceli" value="1" <?php echo $bahceli ? 'checked' : ''; ?>> Bahçeli</label>
                <label><input type="checkbox" name="hizmet_guvenlik" value="1" <?php echo $kamera ? 'checked' : ''; ?>> Güvenlik Kamerası</label>
                <label><input type="checkbox" name="hizmet_ingilizce" value="1" <?php echo $ingilizce ? 'checked' : ''; ?>> İngilizce Grup</label>
            </div>
            <div class="group">
                <label>Konum</label>
                <input type="text" name="sehir" placeholder="Şehir" value="<?php echo htmlspecialchars($sehir, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="text" name="ilce" placeholder="İlçe" value="<?php echo htmlspecialchars($ilce, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="group">
                <button class="btn btn-primary" style="width:100%;">Filtrele</button>
            </div>
            </form>
        </aside>

        <div>
            <div class="results-header">
                <h1>Kurumlar</h1>
                <select onchange="location.href='search.php?<?php echo htmlspecialchars(http_build_query(array_merge($_GET, ['sort' => ''])), ENT_QUOTES, 'UTF-8'); ?>'.replace('sort=', 'sort=' + this.value)">
                    <option value="kayit" <?php echo $sort === 'kayit' ? 'selected' : ''; ?>>En Yeni</option>
                    <option value="puan" <?php echo $sort === 'puan' ? 'selected' : ''; ?>>En Yüksek Puan</option>
                    <option value="fiyat" <?php echo $sort === 'fiyat' ? 'selected' : ''; ?>>Fiyata Göre</option>
                </select>
            </div>

            <div class="cards">
                <?php if (empty($kurumlar)) { ?>
                    <div class="card" style="grid-template-columns: 1fr;">
                        <div class="card-body">
                            <h3>Sonuç bulunamadı</h3>
                            <div class="meta">Filtreleri değiştirerek tekrar deneyin.</div>
                        </div>
                    </div>
                <?php } else { ?>
                    <?php foreach ($kurumlar as $kurum) {
                        $tags = [];
                        if (!empty($kurum['kurum_type'])) { $tags[] = $kurum['kurum_type']; }
                        if ((int) $kurum['meb_onay'] === 1) { $tags[] = 'MEB Onaylı'; }
                        if ((int) $kurum['aile_sosyal_onay'] === 1) { $tags[] = 'Aile Sosyal'; }
                        if ((int) $kurum['hizmet_bahceli'] === 1) { $tags[] = 'Bahçeli'; }
                        if ((int) $kurum['hizmet_guvenlik_kamerasi'] === 1) { $tags[] = 'Kamera'; }
                        if ((int) $kurum['hizmet_ingilizce'] === 1) { $tags[] = 'İngilizce'; }
                        $yas_etiket = '';
                        if (!empty($kurum['min_ay']) || !empty($kurum['max_ay'])) {
                            $yas_etiket = trim(($kurum['min_ay'] ?: '0') . '-' . ($kurum['max_ay'] ?: '72') . ' Ay');
                        } else {
                            $yas_etiket = 'Tüm yaşlar';
                        }
                        $lokasyon_parca = array_filter([trim((string) $kurum['ilce']), trim((string) $kurum['sehir'])]);
                        $lokasyon = implode(', ', $lokasyon_parca);
                        $fiyat = $kurum['min_fiyat'] !== null
                            ? '₺' . number_format((float) $kurum['min_fiyat'], 0, ',', '.') . ' ve üzeri'
                            : 'Fiyat bilgisi yok';
                        ?>
                        <div class="card">
                            <div class="thumb">
                                <?php if (!empty($kurum['kapak_gorsel'])) { ?>
                                    <img src="<?php echo htmlspecialchars($kurum['kapak_gorsel'], ENT_QUOTES, 'UTF-8'); ?>" alt="Kurum görseli">
                                <?php } else { ?>
                                    Galeri
                                <?php } ?>
                            </div>
                            <div class="card-body">
                                <div class="tags">
                                    <?php foreach ($tags as $tag) { ?>
                                        <span class="tag"><?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php } ?>
                                </div>
                                <h3><?php echo htmlspecialchars($kurum['kurum_adi'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <div class="meta">
                                    <?php echo htmlspecialchars($lokasyon !== '' ? $lokasyon : 'Konum bilgisi yok', ENT_QUOTES, 'UTF-8'); ?>
                                    • <?php echo htmlspecialchars($yas_etiket, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <div class="price"><?php echo $fiyat; ?></div>
                                <div class="card-actions">
                                    <a class="btn btn-outline" href="store.php?id=<?php echo (int) $kurum['id']; ?>">Detayı Gör</a>
                                    <a class="btn btn-primary" href="store.php?id=<?php echo (int) $kurum['id']; ?>">Hızlı İletişim</a>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>

            <div class="banner">
                <div>
                    <strong>Size en uygun oyun evini bulamadınız mı?</strong>
                    <div class="meta">Bize yazın, size özel liste hazırlayalım.</div>
                </div>
                <a class="btn btn-primary" href="#">Talep Oluştur</a>
            </div>
        </div>
    </section>
</body>
</html>
