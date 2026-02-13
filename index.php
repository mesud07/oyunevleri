<?php
// www.oyunevleri.com landing page
require_once("includes/config.php");
require_once("includes/functions.php");

$sehirler = [];
$ilceler = [];
$kurum_turleri = [];
$sehirler_by_type = [];
$kurum_sayisi = 0;
$yorum_sayisi = 0;
$ortalama_puan = 0;
$onecikanlar = [];
$modal_hata = '';
$kurum_type = trim($_GET['kurum_type'] ?? '');
$slider_items = [];

if (!empty($db_master)) {
    $sehirler = $db_master->query("SELECT DISTINCT sehir FROM kurumlar WHERE durum = 1 AND sehir IS NOT NULL AND sehir <> '' ORDER BY sehir")->fetchAll(PDO::FETCH_COLUMN);
    $ilceler = $db_master->query("SELECT DISTINCT ilce FROM kurumlar WHERE durum = 1 AND ilce IS NOT NULL AND ilce <> '' ORDER BY ilce")->fetchAll(PDO::FETCH_COLUMN);
    $kurum_turleri = $db_master->query("SELECT DISTINCT kurum_type FROM kurumlar WHERE durum = 1 AND kurum_type IS NOT NULL AND kurum_type <> '' ORDER BY kurum_type")->fetchAll(PDO::FETCH_COLUMN);

    $kurum_sayisi = (int) $db_master->query("SELECT COUNT(*) FROM kurumlar WHERE durum = 1")->fetchColumn();
    $yorum_sayisi = (int) $db_master->query("SELECT COUNT(*) FROM kurum_yorumlar")->fetchColumn();
    $ortalama_puan = (float) $db_master->query("SELECT AVG(puan) FROM kurum_yorumlar")->fetchColumn();

    $sql = "SELECT k.*,
                (SELECT g.gorsel_yol FROM kurum_galeri g WHERE g.kurum_id = k.id ORDER BY g.sira ASC, g.id ASC LIMIT 1) AS kapak_gorsel,
                kf.min_fiyat
            FROM kurumlar k
            LEFT JOIN (
                SELECT kurum_id, MIN(fiyat) AS min_fiyat
                FROM kurum_fiyatlar
                GROUP BY kurum_id
            ) kf ON kf.kurum_id = k.id
            WHERE k.durum = 1
            ORDER BY k.kayit_tarihi DESC
            LIMIT 3";
    $stmt = $db_master->prepare($sql);
    $stmt->execute();
    $onecikanlar = $stmt->fetchAll();

    $stmt = $db_master->prepare("SELECT DISTINCT kurum_type, sehir
        FROM kurumlar
        WHERE durum = 1 AND kurum_type IS NOT NULL AND kurum_type <> '' AND sehir IS NOT NULL AND sehir <> ''
        ORDER BY sehir");
    $stmt->execute();
    $type_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($type_rows as $row) {
        $type = trim($row['kurum_type'] ?? '');
        $sehir = trim($row['sehir'] ?? '');
        if ($type === '' || $sehir === '') { continue; }
        if (!isset($sehirler_by_type[$type])) {
            $sehirler_by_type[$type] = [];
        }
        $sehirler_by_type[$type][] = $sehir;
    }
    foreach ($sehirler_by_type as $type => $list) {
        $list = array_values(array_unique($list));
        sort($list);
        $sehirler_by_type[$type] = $list;
    }

    try {
        $stmt = $db_master->query("SELECT id, baslik, aciklama, gorsel_yol, buton_etiket, link_url
            FROM site_slider
            WHERE aktif = 1
            ORDER BY sira ASC, id ASC");
        $slider_items = $stmt->fetchAll();
        $slider_items = array_values(array_filter($slider_items, function ($row) {
            return !empty($row['gorsel_yol']);
        }));
    } catch (Throwable $e) {
        $slider_items = [];
    }
}

$kurum_turleri_default = ['Oyun Evi', 'Anaokulu', 'Kreş'];
$kurum_turleri = array_values(array_unique(array_filter(array_merge($kurum_turleri, $kurum_turleri_default))));

$type_color_map = [];
$type_desc_map = [];
foreach ($kurum_turleri as $tur) {
    $key = function_exists('mb_strtolower') ? mb_strtolower($tur, 'UTF-8') : strtolower($tur);
    $color = '#ff8a65';
    $desc = 'Bölgenizdeki ' . $tur . ' kurumlarını inceleyin.';
    if (strpos($key, 'anaokul') !== false) {
        $color = '#5aa7ff';
        $desc = 'Bölgenizdeki anaokullarına dair tüm bilgileri inceleyin.';
    } elseif (strpos($key, 'oyun') !== false) {
        $color = '#6fd3c5';
        $desc = 'Bölgenizdeki oyun evi ve atölyeleri keşfedin.';
    } elseif (strpos($key, 'kre') !== false) {
        $color = '#ff8a65';
        $desc = 'Bölgenizdeki kreşleri zaman kaybetmeden keşfedin.';
    } else {
        $color = '#ffd36e';
    }
    $type_color_map[$tur] = $color;
    $type_desc_map[$tur] = $desc;
}

$type_first_letter = function ($text) {
    $text = trim((string) $text);
    if ($text === '') { return '•'; }
    if (function_exists('mb_substr')) {
        return mb_strtoupper(mb_substr($text, 0, 1, 'UTF-8'), 'UTF-8');
    }
    return strtoupper(substr($text, 0, 1));
};

$kurum_sayisi = $kurum_sayisi > 0 ? $kurum_sayisi : 120;
$yorum_sayisi = $yorum_sayisi > 0 ? $yorum_sayisi : 3000;
$ortalama_puan = $ortalama_puan > 0 ? round($ortalama_puan, 1) : 4.8;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'veli_ogrenci_hizli_ekle') {
    $veli = $_SESSION['veli'] ?? null;
    $veli_id = (int) ($veli['id'] ?? 0);
    $kurum_id = (int) ($_SESSION['kurum_id'] ?? 0);
    $ad_soyad = trim($_POST['ad_soyad'] ?? '');
    $dogum_tarihi = trim($_POST['dogum_tarihi'] ?? '');
    $saglik_notlari = trim($_POST['saglik_notlari'] ?? '');

    if (empty($_SESSION['veli_giris']) || $veli_id <= 0) {
        $modal_hata = 'Önce giriş yapmalısınız.';
    } elseif (empty($db)) {
        $modal_hata = 'Kurum veritabanı bağlantısı bulunamadı.';
    } elseif ($ad_soyad === '' || $dogum_tarihi === '') {
        $modal_hata = 'Öğrenci adı ve doğum tarihi zorunludur.';
    } else {
        $data = [
            'kurum_id' => $kurum_id,
            'veli_id' => $veli_id,
            'ad_soyad' => $ad_soyad,
            'dogum_tarihi' => $dogum_tarihi,
            'saglik_notlari' => $saglik_notlari,
        ];
        $new_id = insert_into('ogrenciler', $data);
        if ($new_id) {
            header("Location: index.php#grup-takvimim");
            exit;
        }
        $modal_hata = 'Öğrenci kaydedilemedi. Lütfen tekrar deneyin.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Oyunevleri.com | Şehir seç, yaş seç, eğlenceyi bul</title>
    <?php require_once("includes/analytics.php"); ?>
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
            background: radial-gradient(1200px 600px at 10% -10%, #ffe6dd 0%, transparent 60%),
                        radial-gradient(900px 400px at 100% 0%, #dff5f2 0%, transparent 65%),
                        var(--bg);
        }

        .container {
            max-width: 1180px;
            margin: 0 auto;
            padding: 0 24px;
        }

        header {
            padding: 16px 0;
            background: linear-gradient(90deg, #dff3f1 0%, #eaf7f7 50%, #f6fbfb 100%);
            border-bottom: 1px solid rgba(31,41,55,0.06);
        }

        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .logo {
            font-family: "Baloo 2", cursive;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 0.2px;
            color: var(--ink);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .logo-mark {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 3px solid #36b6cf;
            box-shadow: inset 0 0 0 4px #d6f1f4;
        }

        .logo-accent {
            color: #36b6cf;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 28px;
            font-weight: 600;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: var(--ink);
            text-decoration: none;
            font-size: 15px;
        }

        .nav-links a.active {
            color: #19a7bd;
        }

        .btn {
            border: none;
            padding: 12px 18px;
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
            color: white;
            box-shadow: 0 12px 24px rgba(255, 122, 89, 0.35);
        }

        .btn-outline {
            background: white;
            color: var(--ink);
            border: 1px solid var(--stroke);
        }

        .hero {
            padding: 70px 0 40px;
            background:
                linear-gradient(90deg, rgba(255,247,242,0.96) 0%, rgba(255,236,228,0.78) 42%, rgba(255,236,228,0.15) 100%),
                url('https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;
            position: relative;
        }

        .hero-wrap {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 40px;
            align-items: center;
        }

        .hero-content {
            max-width: 760px;
        }

        .hero-content .search-card,
        .hero-content .stats,
        .hero-content .type-discovery {
            width: 100%;
        }

        .hero h1 {
            font-family: "Baloo 2", cursive;
            font-size: 48px;
            line-height: 1.05;
            margin: 0 0 12px;
        }

        .hero p {
            color: var(--muted);
            font-size: 18px;
            line-height: 1.6;
        }

        .hero-ctas {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .hero-cta-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 22px;
            border-radius: 999px;
            font-weight: 700;
            text-decoration: none;
            border: 2px solid transparent;
            font-size: 15px;
        }

        .hero-cta-btn.primary {
            background: #ff7a59;
            color: #fff;
            box-shadow: 0 14px 26px rgba(255,122,89,0.25);
        }

        .hero-cta-btn.secondary {
            background: #fff;
            color: #ff7a59;
            border-color: #ff7a59;
        }

        .search-card {
            margin-top: 24px;
            background: var(--card);
            border-radius: 18px;
            padding: 18px;
            box-shadow: var(--shadow);
            border: 1px solid var(--stroke);
        }

        .mobile-slider {
            display: none;
            padding: 18px 0 0;
        }

        .slider-shell {
            position: relative;
            overflow: hidden;
            border-radius: 22px;
            box-shadow: var(--shadow);
        }

        .slider-track {
            display: flex;
            transition: transform 0.45s ease;
            will-change: transform;
        }

        .slider-slide {
            min-width: 100%;
        }

        .slider-card {
            position: relative;
            display: block;
            color: inherit;
            text-decoration: none;
        }

        .slider-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            display: block;
        }

        .slider-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,0.05) 0%, rgba(0,0,0,0.35) 65%, rgba(0,0,0,0.55) 100%);
            display: flex;
            align-items: flex-end;
            padding: 16px 18px;
        }

        .slider-title {
            font-family: "Baloo 2", cursive;
            font-size: 20px;
            color: #fff;
            margin-bottom: 6px;
        }

        .slider-text {
            margin: 0 0 10px;
            color: rgba(255,255,255,0.85);
            font-size: 14px;
            line-height: 1.4;
        }

        .slider-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            border-radius: 999px;
            background: #fff;
            color: var(--primary);
            font-weight: 700;
            font-size: 12px;
        }

        .slider-dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }

        .slider-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            border: none;
            background: #d1d5db;
            cursor: pointer;
        }

        .slider-dot.is-active {
            background: var(--primary);
        }

        .type-discovery {
            margin-top: 22px;
        }

        .type-list {
            background: #fff;
            border-radius: 18px;
            border: 1px solid var(--stroke);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .type-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid rgba(31,41,55,0.08);
            text-decoration: none;
            color: var(--ink);
            background: #fff;
            cursor: pointer;
            text-align: left;
            width: 100%;
            border: none;
        }

        .type-item:last-child {
            border-bottom: none;
        }

        .type-left {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
        }

        .type-title {
            font-size: 16px;
        }

        .type-desc {
            display: none;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .type-cta {
            display: none;
            margin-top: auto;
            padding: 10px 14px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            color: var(--primary);
            font-weight: 700;
            font-size: 13px;
            background: #fff;
        }

        .type-icon {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 16px;
            font-weight: 700;
        }

        .type-arrow {
            width: 20px;
            height: 20px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            border: 1px solid rgba(148,163,184,0.4);
        }

        .type-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 9999;
        }

        .type-modal.is-open {
            display: flex;
        }

        .type-modal-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 22px;
            padding: 18px 18px 8px;
            box-shadow: 0 20px 45px rgba(31,41,55,0.2);
            border: 1px solid rgba(31,41,55,0.08);
        }

        .type-modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
        }

        .type-modal-head h3 {
            margin: 0;
            font-family: "Baloo 2", cursive;
            font-size: 20px;
        }

        .type-modal-close {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            border: 1px solid var(--stroke);
            background: #fff;
            cursor: pointer;
            font-size: 18px;
        }

        .type-city-list {
            display: flex;
            flex-direction: column;
            gap: 2px;
            max-height: 60vh;
            overflow: auto;
            padding-bottom: 10px;
        }

        .type-city-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 6px;
            text-decoration: none;
            color: var(--ink);
            border-bottom: 1px solid rgba(31,41,55,0.08);
        }

        .type-city-row:last-child {
            border-bottom: none;
        }

        .type-empty {
            padding: 14px 4px;
            color: var(--muted);
            font-size: 14px;
        }

        .search-grid {
            display: grid;
            grid-template-columns: 1.1fr 1.1fr 1fr 1fr auto;
            gap: 12px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field label {
            font-size: 13px;
            color: var(--muted);
        }

        .field input, .field select {
            border-radius: 12px;
            border: 1px solid var(--stroke);
            padding: 12px 14px;
            font-size: 15px;
            outline: none;
            background: #fff;
        }

        .hero-visual {
            position: relative;
            min-height: 360px;
        }

        .calendar-wrap {
            margin-top: 28px;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: 0.8fr 1.6fr;
            gap: 20px;
        }

        .calendar-card {
            background: var(--card);
            border-radius: 16px;
            padding: 18px;
            box-shadow: var(--shadow);
            border: 1px solid var(--stroke);
        }

        .card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .btn-xs {
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 12px;
        }

        .group-list {
            display: grid;
            gap: 12px;
            margin-top: 12px;
        }

        .group-item {
            background: #fff7f3;
            border: 1px solid rgba(255, 122, 89, 0.15);
            padding: 12px 14px;
            border-radius: 12px;
        }

        .group-title {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .group-meta {
            color: var(--muted);
            font-size: 13px;
        }

        .day-block {
            padding: 10px 0;
            border-bottom: 1px dashed rgba(31,41,55,0.12);
        }

        .day-block:last-child {
            border-bottom: none;
        }

        .day-title {
            font-weight: 700;
            margin-bottom: 6px;
        }

        .day-item {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 6px;
        }

        .day-item .badge {
            background: var(--accent);
            color: #0f3b37;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .day-item .meta {
            font-size: 13px;
            color: var(--muted);
        }

        .text-muted {
            color: var(--muted);
            font-size: 13px;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 9999;
        }

        .modal-overlay.is-open {
            display: flex;
        }

        .modal-box {
            background: #fff;
            border-radius: 18px;
            padding: 22px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 20px 45px rgba(31,41,55,0.2);
            border: 1px solid rgba(31,41,55,0.08);
        }

        .modal-box h3 {
            margin: 0 0 8px;
            font-family: "Baloo 2", cursive;
        }

        .modal-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
            justify-content: flex-end;
        }

        .modal-alert {
            padding: 10px 12px;
            border-radius: 12px;
            margin-top: 12px;
            background: #ffe9e6;
            color: #8a2d22;
            font-weight: 600;
            font-size: 13px;
        }
        .alert.success {
            background: #e7f6ed;
            border: 1px solid #c9ecd8;
            color: #1f7a46;
            padding: 10px 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
        }

        .month-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 8px;
            margin-top: 12px;
        }

        .month-head {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 8px;
        }

        .month-head div {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--muted);
            text-align: center;
        }

        .month-cell {
            min-height: 130px;
            border: 1px solid rgba(31,41,55,0.08);
            background: #fff;
            border-radius: 12px;
            padding: 8px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .month-cell.is-empty {
            background: #f9fafb;
            border-style: dashed;
        }

        .month-day {
            font-weight: 700;
            font-size: 13px;
            color: var(--ink);
        }

        .session-chip {
            display: block;
            padding: 6px 8px;
            border-radius: 10px;
            background: #e7f7ef;
            color: #0f3b37;
            font-size: 12px;
            cursor: pointer;
        }

        .session-chip.full {
            background: #ffe7e4;
            color: #8f2b22;
        }

        .session-chip.past {
            background: #f2f3f7;
            color: #9aa0a6;
            cursor: not-allowed;
        }

        .session-chip.joined {
            background: #dbeafe;
            color: #1e40af;
        }

        .session-chip.past.joined {
            background: #dbeafe;
            color: #1e40af;
            cursor: not-allowed;
        }

        .session-chip .joined-label {
            color: #1e40af;
        }

        .session-chip small {
            display: block;
            color: var(--muted);
            font-size: 11px;
            margin-top: 2px;
        }

        @media (max-width: 920px) {
            .calendar-grid {
                grid-template-columns: 1fr;
            }
            .month-grid,
            .month-head {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .bubble {
            position: absolute;
            border-radius: 30px;
            background: var(--card);
            box-shadow: var(--shadow);
            border: 1px solid var(--stroke);
            padding: 18px 20px;
            font-weight: 600;
        }

        .bubble.one { top: 20px; right: 0; background: #fff7e0; }
        .bubble.two { top: 140px; left: 0; background: #e9f7f5; }
        .bubble.three { bottom: 0; right: 30px; background: #ffe8e3; }

        .hero-visual::before {
            content: "";
            position: absolute;
            inset: 30px 40px 60px 20px;
            border-radius: 30px;
            background: linear-gradient(140deg, #ffd9cf, #d7f3f0);
            z-index: -1;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 24px;
        }

        .stat {
            background: var(--card);
            border-radius: 14px;
            padding: 16px;
            border: 1px solid var(--stroke);
        }

        .stat b {
            display: block;
            font-size: 22px;
            font-family: "Baloo 2", cursive;
        }

        .section {
            padding: 40px 0;
        }

        .section h2 {
            font-family: "Baloo 2", cursive;
            font-size: 32px;
            margin: 0 0 14px;
        }

        .section p.lead {
            color: var(--muted);
            margin-top: 0;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--stroke);
            border-radius: 18px;
            padding: 16px;
            box-shadow: 0 10px 24px rgba(31,41,55,0.08);
        }

        .card .tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #f1f5f9;
            margin-bottom: 10px;
        }
        .card .tag-success {
            background: #e7f6ed;
            color: #1f7a46;
        }
        .card .tag-warning {
            background: #fff3cd;
            color: #856404;
        }
        .card .tag-danger {
            background: #fdecea;
            color: #b42318;
        }
        .card .tag-info {
            background: #dbeafe;
            color: #1e40af;
        }
        .card .tag-blue {
            background: #e7f0ff;
            color: #1f4b99;
        }
        .card .tag-muted {
            background: #f3f4f6;
            color: #6b7280;
        }

        .card h3 {
            margin: 6px 0;
        }

        .card .meta {
            color: var(--muted);
            font-size: 14px;
        }

        .steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .step {
            background: #fff;
            border-radius: 16px;
            padding: 18px;
            border: 1px dashed rgba(31,41,55,0.15);
        }

        .step span {
            display: inline-block;
            background: var(--accent-2);
            padding: 6px 10px;
            border-radius: 10px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .cta {
            background: linear-gradient(135deg, #ffe9e2, #e5f7f4);
            border-radius: 24px;
            padding: 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            border: 1px solid var(--stroke);
        }

        .cta-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        footer {
            padding: 30px 0 60px;
            color: var(--muted);
            font-size: 14px;
        }

        @media (max-width: 980px) {
            .hero-wrap { grid-template-columns: 1fr; }
            .search-grid { grid-template-columns: 1fr 1fr; }
            .cards, .steps, .stats { grid-template-columns: 1fr; }
            .cta { flex-direction: column; align-items: flex-start; }
            .nav { flex-direction: column; align-items: flex-start; }
            .nav-links { gap: 16px; }
            .type-discovery { grid-template-columns: 1fr; }
            .type-list {
                display: flex;
                background: #fff;
                border-radius: 18px;
                border: 1px solid var(--stroke);
            }
            .type-item {
                flex-direction: row;
                align-items: center;
                border-bottom: 1px solid rgba(31,41,55,0.08);
            }
            .type-left {
                flex-direction: row;
                align-items: center;
            }
            .type-arrow { display: inline-flex; }
            .type-desc, .type-cta { display: none; }
            .mobile-slider { display: block; }
        }

        @media (max-width: 600px) {
            .search-grid { grid-template-columns: 1fr; }
            .hero h1 { font-size: 36px; }
            .hero { padding-top: 26px; }
            .month-grid,
            .month-head {
                grid-template-columns: 1fr;
            }
            .hero-ctas { flex-direction: column; align-items: stretch; }
            .hero-cta-btn { width: 100%; justify-content: center; }
            .type-discovery { grid-template-columns: 1fr; }
            .slider-image { height: 200px; }
        }

        @media (min-width: 981px) {
            .type-discovery {
                grid-template-columns: 1fr;
            }
            .type-list {
                background: transparent;
                border: none;
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 18px;
                overflow: visible;
            }
            .type-item {
                border: 1px solid var(--stroke);
                border-radius: 20px;
                padding: 20px;
                flex-direction: column;
                align-items: center;
                gap: 12px;
                text-align: center;
                box-shadow: 0 14px 26px rgba(31,41,55,0.08);
            }
            .type-left {
                flex-direction: column;
                align-items: center;
                gap: 12px;
            }
            .type-title {
                font-size: 17px;
            }
            .type-icon {
                width: 56px;
                height: 56px;
                border-radius: 18px;
                font-size: 22px;
            }
            .type-desc {
                display: block;
                text-align: center;
            }
            .type-cta {
                display: inline-flex;
                justify-content: center;
                width: 100%;
            }
            .type-arrow {
                display: none;
            }
        }
        <?php require_once("includes/public_header.css.php"); ?>
    </style>
</head>
<body>
    <?php $public_nav_active = 'home'; require_once("includes/public_header.php"); ?>

    <?php if (!empty($slider_items)) { ?>
        <section class="mobile-slider">
            <div class="container">
                <div class="slider-shell" data-mobile-slider>
                    <div class="slider-track" data-slider-track>
                        <?php foreach ($slider_items as $index => $slide) {
                            $baslik = trim($slide['baslik'] ?? '');
                            $aciklama = trim($slide['aciklama'] ?? '');
                            $gorsel = trim($slide['gorsel_yol'] ?? '');
                            $buton = trim($slide['buton_etiket'] ?? '');
                            $link = trim($slide['link_url'] ?? '');
                            $card_open = $link !== '' ? "<a class=\"slider-card\" href=\"" . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . "\">" : "<div class=\"slider-card\">";
                            $card_close = $link !== '' ? "</a>" : "</div>";
                            ?>
                            <div class="slider-slide" data-slide>
                                <?php echo $card_open; ?>
                                    <img class="slider-image" src="<?php echo htmlspecialchars($gorsel, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($baslik !== '' ? $baslik : 'Slider görseli', ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="slider-overlay">
                                        <div>
                                            <?php if ($baslik !== '') { ?>
                                                <div class="slider-title"><?php echo htmlspecialchars($baslik, ENT_QUOTES, 'UTF-8'); ?></div>
                                            <?php } ?>
                                            <?php if ($aciklama !== '') { ?>
                                                <p class="slider-text"><?php echo htmlspecialchars($aciklama, ENT_QUOTES, 'UTF-8'); ?></p>
                                            <?php } ?>
                                            <?php if ($buton !== '') { ?>
                                                <span class="slider-button"><?php echo htmlspecialchars($buton, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php echo $card_close; ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="slider-dots" data-slider-dots>
                    <?php foreach ($slider_items as $index => $slide) { ?>
                        <button class="slider-dot <?php echo $index === 0 ? 'is-active' : ''; ?>" type="button" data-slider-dot="<?php echo (int) $index; ?>"></button>
                    <?php } ?>
                </div>
            </div>
        </section>
        <script>
            (function () {
                var slider = document.querySelector('[data-mobile-slider]');
                if (!slider) { return; }
                var track = slider.querySelector('[data-slider-track]');
                var slides = slider.querySelectorAll('[data-slide]');
                var dots = document.querySelectorAll('[data-slider-dot]');
                if (!track || slides.length === 0) { return; }
                var index = 0;
                var timer = null;

                function setActive(i) {
                    index = i;
                    track.style.transform = 'translateX(' + (-100 * index) + '%)';
                    dots.forEach(function (dot) { dot.classList.remove('is-active'); });
                    if (dots[index]) { dots[index].classList.add('is-active'); }
                }

                function next() {
                    var nextIndex = index + 1;
                    if (nextIndex >= slides.length) { nextIndex = 0; }
                    setActive(nextIndex);
                }

                function start() {
                    if (timer) { clearInterval(timer); }
                    timer = setInterval(next, 5000);
                }

                dots.forEach(function (dot) {
                    dot.addEventListener('click', function () {
                        var i = parseInt(dot.getAttribute('data-slider-dot'), 10);
                        if (!Number.isNaN(i)) {
                            setActive(i);
                            start();
                        }
                    });
                });

                slider.addEventListener('touchstart', function () {
                    if (timer) { clearInterval(timer); }
                });
                slider.addEventListener('touchend', function () {
                    start();
                });

                start();
            })();
        </script>
    <?php } ?>

    <section class="hero">
        <div class="container hero-wrap">
            <div class="hero-content">
                <?php if (!empty($_SESSION['flash_basarili'])) { ?>
                    <div class="alert success" style="margin-bottom:16px;">
                        <?php echo htmlspecialchars($_SESSION['flash_basarili'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <?php unset($_SESSION['flash_basarili']); ?>
                <?php } ?>
                <h1>Aradığın Anaokulu, Kreş ve Oyun Grubu Bu Platformda!</h1>
                <p>Oyun evlerini, anaokullarını ve kreşleri tek ekrandan keşfet, güvenli ve onaylı kurumları filtrele, hızlıca iletişime geç.</p>

                <div class="hero-ctas">
                    <a class="hero-cta-btn primary" href="firma_kayit.php">Firmalar için Kayıt Olma Sayfası</a>
                    <a class="hero-cta-btn secondary" href="register.php">Veliler için Abone Olma Sayfası</a>
                </div>

                <div class="search-card">
                    <form method="get" action="search.php">
                        <div class="search-grid">
                            <div class="field">
                                <label>Şehir</label>
                                <input type="text" name="sehir" list="sehirler" placeholder="Örn. İstanbul">
                                <datalist id="sehirler">
                                    <?php foreach ($sehirler as $sehir) { ?>
                                        <option value="<?php echo htmlspecialchars($sehir, ENT_QUOTES, 'UTF-8'); ?>"></option>
                                    <?php } ?>
                                </datalist>
                            </div>
                            <div class="field">
                                <label>İlçe</label>
                                <input type="text" name="ilce" list="ilceler" placeholder="Örn. Kadıköy">
                                <datalist id="ilceler">
                                    <?php foreach ($ilceler as $ilce) { ?>
                                        <option value="<?php echo htmlspecialchars($ilce, ENT_QUOTES, 'UTF-8'); ?>"></option>
                                    <?php } ?>
                                </datalist>
                            </div>
                            <div class="field">
                                <label>Yaş Aralığı</label>
                                <select name="yas_araligi">
                                    <option value="">Seçiniz</option>
                                    <option value="0-24">0-24 Ay</option>
                                    <option value="24-36">24-36 Ay</option>
                                    <option value="36-48">36-48 Ay</option>
                                    <option value="48-72">48-72 Ay</option>
                                </select>
                            </div>
                            <div class="field">
                                <label>Kurum Türü</label>
                                <select name="kurum_type">
                                    <option value="">Tümü</option>
                                    <?php foreach ($kurum_turleri as $tur) { ?>
                                        <option value="<?php echo htmlspecialchars($tur, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $kurum_type === $tur ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tur, ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="field">
                                <label>&nbsp;</label>
                                <button class="btn btn-primary" type="submit">Ara</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="stats">
                    <div class="stat">
                        <b><?php echo $kurum_sayisi; ?>+</b>
                        Kurum (Oyun Evi / Anaokulu / Kreş)
                    </div>
                    <div class="stat">
                        <b><?php echo $yorum_sayisi; ?>+</b>
                        Kullanıcı yorumu
                    </div>
                    <div class="stat">
                        <b><?php echo $ortalama_puan; ?>/5</b>
                        Kullanıcı memnuniyeti
                    </div>
                </div>
            </div>

            <div class="hero-visual">
                <div class="bubble one">MEB Onaylı</div>
                <div class="bubble two">Bahçeli • Güvenlik Kamerası</div>
                <div class="bubble three">İngilizce Oyun Grupları</div>
            </div>
        </div>
        <div class="container">
            <div class="type-discovery">
                <div class="type-list">
                    <?php foreach ($kurum_turleri as $tur) {
                        $color = $type_color_map[$tur] ?? '#ff8a65';
                        $letter = $type_first_letter($tur);
                        $desc = $type_desc_map[$tur] ?? ('Bölgenizdeki ' . $tur . ' kurumlarını inceleyin.');
                        ?>
                        <button class="type-item" type="button" data-type-select="<?php echo htmlspecialchars($tur, ENT_QUOTES, 'UTF-8'); ?>">
                            <span class="type-left">
                                <span class="type-icon" style="background: <?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($letter, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <span class="type-title"><?php echo htmlspecialchars($tur, ENT_QUOTES, 'UTF-8'); ?></span>
                            </span>
                            <span class="type-desc"><?php echo htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="type-cta">İNCELE</span>
                            <span class="type-arrow">›</span>
                        </button>
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>

    <?php
    $veli = $_SESSION['veli'] ?? null;
    $veli_kurum_id = (int) ($_SESSION['kurum_id'] ?? 0);
    $veli_rezervasyonlar = [];
    $veli_bakiye = 0;
    $veli_hak_bitis = '';
    $kurum_gruplar = [];
    $kurum_seanslar = [];
    $veli_ogrenciler = [];
    $ogrenci_yaslar = [];
    $iptal_kural_saat = 48;
    $veli_katilim_seans = [];
    $veli_rez_map = [];
    $veli_rez_by_student = [];
    $aktif_ogrenci_id = 0;
    $grup_filtre_aktif = isset($_GET['uygun']) && $_GET['uygun'] === '1';
    $gorunen_gruplar = [];
    $ay_hucreler = [];
    $takvim = [];
    $ay_etiket = '';
    $profil_modal_goster = false;
    if (!empty($_SESSION['veli_giris']) && $veli && !empty($db) && $veli_kurum_id > 0) {
        $stmt = $db->prepare("SELECT bakiye_hak, hak_gecerlilik_bitis FROM veliler WHERE id = :id AND kurum_id = :kurum_id LIMIT 1");
        $stmt->execute(['id' => $veli['id'], 'kurum_id' => $veli_kurum_id]);
        $veli_row = $stmt->fetch();
        $veli_bakiye = (int) ($veli_row['bakiye_hak'] ?? 0);
        $veli_hak_bitis = $veli_row['hak_gecerlilik_bitis'] ?? '';
        $iptal_kural_saat = (int) sistem_ayar_get('iptal_kural_saat', $veli_kurum_id, 48);

        $sql = "SELECT r.id, r.durum, r.iptal_onay, s.seans_baslangic, g.grup_adi
                FROM rezervasyonlar r
                INNER JOIN seanslar s ON s.id = r.seans_id
                INNER JOIN oyun_gruplari g ON g.id = s.grup_id
                WHERE r.ogrenci_id IN (SELECT id FROM ogrenciler WHERE veli_id = :veli_id AND kurum_id = :kurum_id)
                  AND r.kurum_id = :kurum_id
                ORDER BY s.seans_baslangic DESC
                LIMIT 10";
        $stmt = $db->prepare($sql);
        $stmt->execute(['veli_id' => $veli['id'], 'kurum_id' => $veli_kurum_id]);
        $veli_rezervasyonlar = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT id, ad_soyad, dogum_tarihi FROM ogrenciler
            WHERE veli_id = :veli_id AND kurum_id = :kurum_id");
        $stmt->execute(['veli_id' => $veli['id'], 'kurum_id' => $veli_kurum_id]);
        $veli_ogrenciler = $stmt->fetchAll();
        $aktif_ogrenci_id = (int) ($veli_ogrenciler[0]['id'] ?? 0);
        foreach ($veli_ogrenciler as $ogr) {
            if (!empty($ogr['dogum_tarihi'])) {
                $dt = new DateTime($ogr['dogum_tarihi']);
                $now = new DateTime();
                $diff = $dt->diff($now);
                $ogrenci_yaslar[] = ($diff->y * 12) + $diff->m;
            }
        }

        $stmt = $db->prepare("SELECT id, grup_adi, min_ay, max_ay, kapasite, tekrar_tipi, tekrar_gunleri, seans_baslangic_saati, seans_suresi_dk
            FROM oyun_gruplari
            WHERE kurum_id = :kurum_id
            ORDER BY grup_adi");
        $stmt->execute(['kurum_id' => $veli_kurum_id]);
        $kurum_gruplar = $stmt->fetchAll();
        $gorunen_gruplar = $kurum_gruplar;
        if ($grup_filtre_aktif) {
            $filtreli = [];
            foreach ($kurum_gruplar as $grup) {
                $min_ay = (int) ($grup['min_ay'] ?? 0);
                $max_ay = (int) ($grup['max_ay'] ?? 0);
                foreach ($ogrenci_yaslar as $yas) {
                    $uygun = true;
                    if ($min_ay > 0 && $yas < $min_ay) { $uygun = false; }
                    if ($max_ay > 0 && $yas > $max_ay) { $uygun = false; }
                    if ($uygun) { $filtreli[] = $grup; break; }
                }
            }
            $gorunen_gruplar = $filtreli;
        }

        $ay_bas = new DateTime('first day of this month');
        $ay_bit = new DateTime('last day of this month 23:59:59');
        $stmt = $db->prepare("SELECT r.seans_id, r.ogrenci_id
            FROM rezervasyonlar r
            INNER JOIN seanslar s ON s.id = r.seans_id
            INNER JOIN ogrenciler o ON o.id = r.ogrenci_id
            WHERE o.veli_id = :veli_id
              AND r.kurum_id = :kurum_id
              AND r.durum = 'onayli'
              AND s.seans_baslangic BETWEEN :start AND :end");
        $stmt->execute([
            'veli_id' => $veli['id'],
            'kurum_id' => $veli_kurum_id,
            'start' => $ay_bas->format('Y-m-d 00:00:00'),
            'end' => $ay_bit->format('Y-m-d 23:59:59'),
        ]);
        $rez_rows = $stmt->fetchAll();
        foreach ($rez_rows as $row) {
            $sid = (int) ($row['seans_id'] ?? 0);
            $oid = (int) ($row['ogrenci_id'] ?? 0);
            if ($sid <= 0 || $oid <= 0) {
                continue;
            }
            if (!isset($veli_rez_map[$sid])) {
                $veli_rez_map[$sid] = [];
            }
            $veli_rez_map[$sid][] = $oid;
            if (!isset($veli_rez_by_student[$oid])) {
                $veli_rez_by_student[$oid] = [];
            }
            $veli_rez_by_student[$oid][$sid] = true;
        }

        $stmt = $db->prepare("SELECT s.id, s.seans_baslangic, s.seans_bitis, s.kontenjan, g.grup_adi, g.min_ay, g.max_ay,
                (SELECT COUNT(*) FROM rezervasyonlar r WHERE r.seans_id = s.id AND r.durum = 'onayli') AS dolu
            FROM seanslar s
            INNER JOIN oyun_gruplari g ON g.id = s.grup_id
            WHERE s.kurum_id = :kurum_id
              AND s.seans_baslangic BETWEEN :start AND :end
              AND s.durum = 'aktif'
            ORDER BY s.seans_baslangic ASC");
        $stmt->execute([
            'kurum_id' => $veli_kurum_id,
            'start' => $ay_bas->format('Y-m-d 00:00:00'),
            'end' => $ay_bit->format('Y-m-d 23:59:59'),
        ]);
        $kurum_seanslar = $stmt->fetchAll();

        foreach ($kurum_seanslar as $seans) {
            $k = date('Y-m-d', strtotime($seans['seans_baslangic']));
            if (!isset($takvim[$k])) {
                $takvim[$k] = [];
            }
            $takvim[$k][] = $seans;
        }

        $ay_adlari = [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran',
            7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
        ];
        $ay_etiket = ($ay_adlari[(int) $ay_bas->format('n')] ?? $ay_bas->format('F')) . ' ' . $ay_bas->format('Y');
        $start_dow = (int) $ay_bas->format('N'); // 1=Mon ... 7=Sun
        for ($i = 1; $i < $start_dow; $i++) {
            $ay_hucreler[] = null;
        }
        $days_in_month = (int) $ay_bas->format('t');
        for ($day = 1; $day <= $days_in_month; $day++) {
            $dt = new DateTime($ay_bas->format('Y-m-') . str_pad((string) $day, 2, '0', STR_PAD_LEFT));
            $ay_hucreler[] = [
                'date' => $dt->format('Y-m-d'),
                'day' => $day,
            ];
        }
        while (count($ay_hucreler) % 7 !== 0) {
            $ay_hucreler[] = null;
        }
    }

    if (!empty($_SESSION['veli_giris']) && $veli && !empty($db)) {
        $sql = "SELECT COUNT(*) FROM ogrenciler WHERE veli_id = :veli_id";
        $params = ['veli_id' => (int) $veli['id']];
        if ($veli_kurum_id > 0) {
            $sql .= " AND kurum_id = :kurum_id";
            $params['kurum_id'] = $veli_kurum_id;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $ogrenci_sayisi = (int) $stmt->fetchColumn();
        if ($ogrenci_sayisi === 0) {
            $profil_modal_goster = true;
        }
    }
    ?>

    <?php if (!empty($_SESSION['veli_giris'])) { ?>
        <section class="section" id="grup-takvimim">
            <div class="container">
                <h2>Grup Takvimim</h2>
                <?php if ($veli_kurum_id <= 0) { ?>
                    <p class="lead">Henüz bir kurum kaydınız bulunmuyor. Kurum seçimi yaptığınızda rezervasyonlarınız burada görünecek.</p>
                <?php } else { ?>
                    <p class="lead">
                        Kalan hak: <strong><?php echo $veli_bakiye; ?></strong>
                        <span class="text-muted">• Son kullanım:
                            <strong>
                                <?php echo $veli_hak_bitis ? htmlspecialchars(date('d.m.Y', strtotime($veli_hak_bitis)), ENT_QUOTES, 'UTF-8') : '-'; ?>
                            </strong>
                        </span>
                    </p>
                    <div class="cards">
                        <?php if (empty($veli_rezervasyonlar)) { ?>
                            <div class="card">
                                <h3>Rezervasyon bulunamadı</h3>
                                <p>Henüz bir rezervasyonunuz yok.</p>
                            </div>
                        <?php } else { ?>
                            <?php foreach ($veli_rezervasyonlar as $rez) { ?>
                                <?php
                                    $rez_tarih = strtotime($rez['seans_baslangic']);
                                    $rez_gecmis = $rez_tarih !== false && $rez_tarih < time();
                                    $durum = $rez['durum'] ?? '';
                                    $durum_etiket = $durum;
                                    $durum_class = 'tag-gray';
                                    if ($durum === 'onayli') {
                                        $durum_etiket = 'Katılım Onaylandı';
                                        $durum_class = 'tag-success';
                                    } elseif ($durum === 'iptal') {
                                        if ((int) ($rez['iptal_onay'] ?? 0) === 1) {
                                            $durum_etiket = 'İptal Onaylandı';
                                            $durum_class = 'tag-muted';
                                        } else {
                                            $durum_etiket = 'İptal Onayı Bekleniyor';
                                            $durum_class = 'tag-warning';
                                        }
                                    } elseif ($durum === 'hak_yandi') {
                                        $durum_etiket = 'Hak Yandı';
                                        $durum_class = 'tag-danger';
                                    }
                                    $katilim_etiket = '';
                                    $katilim_class = 'tag-gray';
                                    if ($durum === 'onayli') {
                                        $katilim_etiket = $rez_gecmis ? 'Katılım Onaylandı' : 'Katılım Bekleniyor';
                                        $katilim_class = $rez_gecmis ? 'tag-info' : 'tag-blue';
                                    }
                                    $iptal_aktif = ($durum === 'onayli' && !$rez_gecmis);
                                ?>
                                <div class="card">
                                    <div class="tags" style="display:flex;gap:6px;flex-wrap:wrap;">
                                        <span class="tag <?php echo htmlspecialchars($durum_class, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($durum_etiket, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php if ($katilim_etiket !== '') { ?>
                                            <span class="tag <?php echo htmlspecialchars($katilim_class, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($katilim_etiket, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php } ?>
                                    </div>
                                    <h3><?php echo htmlspecialchars($rez['grup_adi'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <div class="meta">
                                        <?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($rez['seans_baslangic'])), ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <?php if ($iptal_aktif) { ?>
                                        <div class="d-flex gap-2" style="margin-top:10px;">
                                            <button
                                                class="btn btn-outline rezervasyon-iptal"
                                                data-rez-id="<?php echo (int) $rez['id']; ?>"
                                                data-seans="<?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($rez['seans_baslangic'])), ENT_QUOTES, 'UTF-8'); ?>">
                                                İptal Et
                                            </button>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>

                    <div class="calendar-wrap" style="margin-top:28px;">
                        <div class="calendar-grid">
                            <div class="calendar-card">
                                <div class="card-head">
                                    <h3>Gruplar</h3>
                                    <?php if (!empty($kurum_gruplar)) {
                                        $filter_url = $grup_filtre_aktif ? 'index.php#grup-takvimim' : 'index.php?uygun=1#grup-takvimim';
                                        $filter_label = $grup_filtre_aktif ? 'Filtreyi Kaldır' : 'Uygun Gruplar';
                                        ?>
                                        <a class="btn btn-outline btn-xs" href="<?php echo $filter_url; ?>"><?php echo $filter_label; ?></a>
                                    <?php } ?>
                                </div>
                                <?php if (empty($kurum_gruplar)) { ?>
                                    <p>Bu kurumda grup bulunamadı.</p>
                                <?php } else { ?>
                                    <div class="group-list">
                                        <?php if ($grup_filtre_aktif && empty($ogrenci_yaslar)) { ?>
                                            <div class="group-item">
                                                <div class="group-title">Öğrenci kaydı bulunamadı</div>
                                                <div class="group-meta">Uygun grup filtresi için önce öğrenci ekleyin.</div>
                                            </div>
                                        <?php } elseif ($grup_filtre_aktif && empty($gorunen_gruplar)) { ?>
                                            <div class="group-item">
                                                <div class="group-title">Uygun grup bulunamadı</div>
                                                <div class="group-meta">Öğrencilerin yaşına uygun grup bulunmuyor.</div>
                                            </div>
                                        <?php } else { ?>
                                        <?php foreach ($gorunen_gruplar as $grup) {
                                            $yas_etiket = (!empty($grup['min_ay']) || !empty($grup['max_ay']))
                                                ? ($grup['min_ay'] ?: '0') . '-' . ($grup['max_ay'] ?: '72') . ' Ay'
                                                : 'Tüm yaşlar';
                                            $tekrar = $grup['tekrar_tipi'] === 'haftalik' ? 'Haftalık' : 'Tekil';
                                            $gunler = trim((string) $grup['tekrar_gunleri']);
                                            $gunler = $gunler !== '' ? str_replace(',', ', ', $gunler) : '-';
                                            $saat = $grup['seans_baslangic_saati'] ? substr($grup['seans_baslangic_saati'], 0, 5) : '-';
                                            $sure = $grup['seans_suresi_dk'] ? $grup['seans_suresi_dk'] . ' dk' : '-';
                                            ?>
                                            <div class="group-item">
                                                <div class="group-title"><?php echo htmlspecialchars($grup['grup_adi'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="group-meta"><?php echo htmlspecialchars($yas_etiket, ENT_QUOTES, 'UTF-8'); ?> • Kapasite <?php echo (int) ($grup['kapasite'] ?? 0); ?></div>
                                                <div class="group-meta"><?php echo htmlspecialchars($tekrar, ENT_QUOTES, 'UTF-8'); ?> • <?php echo htmlspecialchars($gunler, ENT_QUOTES, 'UTF-8'); ?> • <?php echo htmlspecialchars($saat, ENT_QUOTES, 'UTF-8'); ?> • <?php echo htmlspecialchars($sure, ENT_QUOTES, 'UTF-8'); ?></div>
                                            </div>
                                        <?php } ?>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>
                            <div class="calendar-card">
                                <div class="card-head">
                                    <div>
                                        <h3>Aylık Takvim</h3>
                                        <?php if ($ay_etiket !== '') { ?>
                                            <div class="text-muted"><?php echo htmlspecialchars($ay_etiket, ENT_QUOTES, 'UTF-8'); ?></div>
                                        <?php } ?>
                                    </div>
                                    <?php if (!empty($veli_ogrenciler)) { ?>
                                        <select id="takvim-ogrenci" class="btn btn-outline btn-xs">
                                            <?php foreach ($veli_ogrenciler as $ogr) { ?>
                                                <option value="<?php echo (int) $ogr['id']; ?>">
                                                    <?php echo htmlspecialchars($ogr['ad_soyad'], ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    <?php } ?>
                                </div>
                                <div class="month-head" style="margin-top:12px;">
                                    <div>Pzt</div>
                                    <div>Sal</div>
                                    <div>Çar</div>
                                    <div>Per</div>
                                    <div>Cum</div>
                                    <div>Cmt</div>
                                    <div>Paz</div>
                                </div>
                                <div class="month-grid">
                                    <?php foreach ($ay_hucreler as $cell) { ?>
                                        <?php if ($cell === null) { ?>
                                            <div class="month-cell is-empty"></div>
                                        <?php } else {
                                            $gun_key = $cell['date'];
                                            $gun_liste = $takvim[$gun_key] ?? [];
                                            ?>
                                            <div class="month-cell">
                                                <div class="month-day"><?php echo (int) $cell['day']; ?></div>
                                                <?php if (empty($gun_liste)) { ?>
                                                    <span class="text-muted">Seans yok</span>
                                                <?php } else { ?>
                                                    <?php foreach ($gun_liste as $seans) {
                                                        $yas = (!empty($seans['min_ay']) || !empty($seans['max_ay']))
                                                            ? ($seans['min_ay'] ?: '0') . '-' . ($seans['max_ay'] ?: '72') . ' Ay'
                                                            : 'Tüm yaşlar';
                                                        $saat = date('H:i', strtotime($seans['seans_baslangic'])) . ' - ' . date('H:i', strtotime($seans['seans_bitis']));
                                                        $kontenjan = (int) ($seans['kontenjan'] ?? 0);
                                                        $dolu = (int) ($seans['dolu'] ?? 0);
                                                        $doluluk = $kontenjan > 0 ? $dolu . '/' . $kontenjan : $dolu;
                                                        $full = $kontenjan > 0 && $dolu >= $kontenjan;
                                                        ?>
                                                        <?php
                                                            $gecmis = strtotime($seans['seans_baslangic']) < time();
                                                            $seans_id = (int) $seans['id'];
                                                            $katildi = $aktif_ogrenci_id > 0 && !empty($veli_rez_by_student[$aktif_ogrenci_id][$seans_id]);
                                                            $rez_liste = $veli_rez_map[$seans_id] ?? [];
                                                            $rez_attr = !empty($rez_liste) ? implode(',', $rez_liste) : '';
                                                        ?>
                                                        <div class="session-chip<?php echo $full ? ' full' : ''; ?><?php echo $gecmis ? ' past' : ''; ?><?php echo $katildi ? ' joined' : ''; ?>"
                                                             data-seans-id="<?php echo (int) $seans['id']; ?>"
                                                             data-reservations="<?php echo htmlspecialchars($rez_attr, ENT_QUOTES, 'UTF-8'); ?>"
                                                             data-joined="<?php echo $katildi ? '1' : '0'; ?>"
                                                             data-gecmis="<?php echo $gecmis ? '1' : '0'; ?>"
                                                             data-kontenjan="<?php echo $kontenjan; ?>"
                                                             data-dolu="<?php echo $dolu; ?>">
                                                            <strong><?php echo htmlspecialchars($seans['grup_adi'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                            <small><?php echo htmlspecialchars($saat, ENT_QUOTES, 'UTF-8'); ?> • <?php echo htmlspecialchars($yas, ENT_QUOTES, 'UTF-8'); ?></small>
                                                            <small>Doluluk: <?php echo htmlspecialchars($doluluk, ENT_QUOTES, 'UTF-8'); ?></small>
                                                            <small class="joined-label"<?php echo $katildi ? '' : ' style="display:none"'; ?>>Katıldınız</small>
                                                        </div>
                                                    <?php } ?>
                                                <?php } ?>
                                            </div>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </section>
        <?php if (!empty($veli) && $veli_kurum_id > 0) { ?>
        <div class="modal-overlay" id="iptalModal">
            <div class="modal-box">
                <h3>Rezervasyon İptali</h3>
                <p id="iptalUyari" class="lead" style="margin-bottom:12px;"></p>
                <div class="field" style="margin-bottom:12px;">
                    <label>İptal Sebebi</label>
                    <textarea id="iptalSebep" rows="3" placeholder="İptal sebebinizi yazın" required></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-light" id="iptalVazgec">Vazgeç</button>
                    <button class="btn btn-primary" id="iptalOnayla">İptal Et</button>
                </div>
            </div>
        </div>
        <script>
            (function() {
                var veliId = <?php echo (int) ($veli['id'] ?? 0); ?>;
                var iptalKuralSaat = <?php echo (int) $iptal_kural_saat; ?>;
                var ogrSelect = document.getElementById('takvim-ogrenci');
                var iptalModal = document.getElementById('iptalModal');
                var iptalUyari = document.getElementById('iptalUyari');
                var iptalSebep = document.getElementById('iptalSebep');
                var iptalOnayBtn = document.getElementById('iptalOnayla');
                var iptalVazgecBtn = document.getElementById('iptalVazgec');
                var aktifRezId = 0;
                var aktifOnay = 0;
                if (!veliId) { return; }

                function postAjax(payload) {
                    return fetch('ajax.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: new URLSearchParams(payload)
                    }).then(function(res) { return res.json(); });
                }

                function hakUyarisiMesaji(data) {
                    var bakiye = parseInt(data.bakiye_hak || 0, 10);
                    var donduruldu = parseInt(data.hak_donduruldu || 0, 10) === 1;
                    if (donduruldu) {
                        return 'Haklarınız dondurulmuş görünüyor. Hak almak için kurum yöneticisiyle iletişime geçin.';
                    }
                    if (data.hak_gecerlilik_bitis) {
                        var bitis = new Date(data.hak_gecerlilik_bitis + 'T23:59:59');
                        if (bitis < new Date()) {
                            return 'Hak süreniz dolmuş. Hak almak için kurum yöneticisiyle iletişime geçin.';
                        }
                    }
                    if (bakiye <= 0) {
                        return 'Hak bakiyeniz yok. Hak almak için kurum yöneticisiyle iletişime geçin.';
                    }
                    return '';
                }

                function modalAc() {
                    if (iptalModal) {
                        iptalModal.classList.add('is-open');
                    }
                }

                function modalKapat() {
                    if (iptalModal) {
                        iptalModal.classList.remove('is-open');
                    }
                    if (iptalSebep) {
                        iptalSebep.value = '';
                    }
                    aktifRezId = 0;
                    aktifOnay = 0;
                }

                document.querySelectorAll('.rezervasyon-iptal').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var rezId = this.getAttribute('data-rez-id');
                        var seansStr = this.getAttribute('data-seans');
                        if (!rezId || !seansStr) {
                            return;
                        }
                        var seansTarih = new Date(seansStr.replace(' ', 'T'));
                        var kalanSaat = (seansTarih - new Date()) / 3600000;
                        aktifOnay = kalanSaat < iptalKuralSaat ? 1 : 0;
                        if (kalanSaat >= iptalKuralSaat) {
                            iptalUyari.textContent = 'Rezervasyon iptali ' + iptalKuralSaat + ' saat öncesinde olduğu için hak iadesi yapılacaktır.';
                        } else {
                            iptalUyari.textContent = 'Grubun başlamasına ' + iptalKuralSaat + ' saatten az kaldığı için hakkınız iade edilmeyecektir.';
                        }
                        aktifRezId = parseInt(rezId, 10) || 0;
                        modalAc();
                    });
                });

                if (iptalVazgecBtn) {
                    iptalVazgecBtn.addEventListener('click', function() {
                        modalKapat();
                    });
                }

                if (iptalOnayBtn) {
                    iptalOnayBtn.addEventListener('click', function() {
                        if (!aktifRezId) {
                            return;
                        }
                        var sebep = (iptalSebep && iptalSebep.value) ? iptalSebep.value.trim() : '';
                        if (!sebep) {
                            alert('İptal sebebi giriniz.');
                            return;
                        }
                        postAjax({action: 'rezervasyon_iptal', rezervasyon_id: aktifRezId, onay: aktifOnay, iptal_sebebi: sebep}).then(function(res) {
                            if (res && res.durum === 'ok') {
                                alert(res.mesaj || 'İptal talebi alındı.');
                                window.location.reload();
                                return;
                            }
                            if (res && res.durum === 'uyari') {
                                alert(res.mesaj || 'Uyarı');
                                return;
                            }
                            alert((res && res.mesaj) ? res.mesaj : 'İptal işlemi yapılamadı.');
                        }).catch(function() {
                            alert('İptal işlemi sırasında hata oluştu.');
                        });
                    });
                }

                document.querySelectorAll('.session-chip').forEach(function(chip) {
                    chip.addEventListener('click', function() {
                        if (this.getAttribute('data-joined') === '1') {
                            alert('Bu seans için zaten rezervasyonunuz var.');
                            return;
                        }
                        if (this.getAttribute('data-gecmis') === '1') {
                            alert('Geçmiş tarihli seanslara katılım sağlanamaz.');
                            return;
                        }
                        var seansId = this.getAttribute('data-seans-id');
                        var ogrenciId = ogrSelect ? ogrSelect.value : '';
                        if (!ogrenciId) {
                            alert('Rezervasyon için önce öğrenci seçin.');
                            return;
                        }
                        postAjax({action: 'hak_kontrol', veli_id: veliId}).then(function(resp) {
                            if (!resp || resp.durum !== 'ok') {
                                alert((resp && resp.mesaj) ? resp.mesaj : 'Hak bilgisi alınamadı.');
                                return;
                            }
                            var uyari = hakUyarisiMesaji(resp);
                            if (uyari) {
                                alert(uyari);
                                return;
                            }
                            if (!confirm('Bu seansa katılmak istiyor musunuz?')) {
                                return;
                            }
                            postAjax({action: 'rezervasyon_yap', ogrenci_id: ogrenciId, seans_id: seansId}).then(function(r2) {
                                if (r2 && r2.mesaj) {
                                    alert(r2.mesaj);
                                } else {
                                    alert('İşlem tamamlandı.');
                                }
                                if (r2 && r2.durum === 'ok') {
                                    window.location.reload();
                                }
                            }).catch(function() {
                                alert('Rezervasyon işlemi sırasında hata oluştu.');
                            });
                        }).catch(function() {
                            alert('Hak kontrolü sırasında hata oluştu.');
                        });
                    });
                });

                if (ogrSelect) {
                    ogrSelect.addEventListener('change', function() {
                        var selectedId = this.value;
                        document.querySelectorAll('.session-chip').forEach(function(chip) {
                            var list = chip.getAttribute('data-reservations') || '';
                            var ids = list.split(',').filter(Boolean);
                            var joined = ids.indexOf(String(selectedId)) !== -1;
                            chip.setAttribute('data-joined', joined ? '1' : '0');
                            chip.classList.toggle('joined', joined);
                            var label = chip.querySelector('.joined-label');
                            if (label) {
                                label.style.display = joined ? 'block' : 'none';
                            }
                        });
                    });
                }
            })();
        </script>
        <?php } ?>
    <?php } ?>

    <section class="section">
        <div class="container">
            <h2>Öne Çıkan Kurumlar</h2>
            <p class="lead">Filtrelere göre en çok tercih edilen kurumlar.</p>
            <div class="cards">
                <?php if (empty($onecikanlar)) { ?>
                    <div class="card">
                        <span class="tag">Öne Çıkan</span>
                        <h3>Henüz kurum yok</h3>
                        <div class="meta">Lütfen daha sonra tekrar deneyin.</div>
                        <p>İlk kurum kaydı ile burada görünebilirsiniz.</p>
                        <a class="btn btn-outline" href="#">Detayı Gör</a>
                    </div>
                <?php } else { ?>
                    <?php foreach ($onecikanlar as $kurum) {
                        $tags = [];
                        if (!empty($kurum['kurum_type'])) { $tags[] = $kurum['kurum_type']; }
                        if ((int) $kurum['meb_onay'] === 1) { $tags[] = 'MEB Bağlı'; }
                        if ((int) $kurum['aile_sosyal_onay'] === 1) { $tags[] = 'Aile Sosyal Onaylı'; }
                        if ((int) $kurum['hizmet_ingilizce'] === 1) { $tags[] = 'İngilizce Grup'; }
                        $lokasyon_parca = array_filter([trim((string) $kurum['ilce']), trim((string) $kurum['sehir'])]);
                        $lokasyon = implode(', ', $lokasyon_parca);
                        $yas_etiket = (!empty($kurum['min_ay']) || !empty($kurum['max_ay']))
                            ? ($kurum['min_ay'] ?: '0') . '-' . ($kurum['max_ay'] ?: '72') . ' Ay'
                            : 'Tüm yaşlar';
                        ?>
                        <div class="card">
                            <?php if (!empty($tags)) { ?>
                                <span class="tag"><?php echo htmlspecialchars($tags[0], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php } ?>
                            <h3><?php echo htmlspecialchars($kurum['kurum_adi'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class="meta">
                                <?php echo htmlspecialchars($lokasyon !== '' ? $lokasyon : 'Konum bilgisi yok', ENT_QUOTES, 'UTF-8'); ?>
                                • <?php echo htmlspecialchars($yas_etiket, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <p>
                                <?php if (!empty($kurum['kapak_gorsel'])) { ?>
                                    <img src="<?php echo htmlspecialchars($kurum['kapak_gorsel'], ENT_QUOTES, 'UTF-8'); ?>" alt="Kurum görseli" style="width:100%;border-radius:12px;margin-top:8px;">
                                <?php } else { ?>
                                    Güvenilir oyun evleri ile tanışın.
                                <?php } ?>
                            </p>
                            <?php $seo_url = kurum_seo_url($kurum); ?>
                            <a class="btn btn-outline" href="<?php echo htmlspecialchars($seo_url, ENT_QUOTES, 'UTF-8'); ?>">Detayı Gör</a>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2>Nasıl Çalışır?</h2>
            <p class="lead">Hızlıca arayın, karşılaştırın ve iletişime geçin.</p>
            <div class="steps">
                <div class="step">
                    <span>1</span>
                    <h3>Şehir ve yaş seç</h3>
                    <p>Size en uygun kurumları filtreleyin.</p>
                </div>
                <div class="step">
                    <span>2</span>
                    <h3>Detayları incele</h3>
                    <p>Galeri, eğitmen ve fiyatları tek ekranda görün.</p>
                </div>
                <div class="step">
                    <span>3</span>
                    <h3>Hızlı iletişim</h3>
                    <p>Kayıt için doğrudan uygulamaya yönlenin.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="cta">
                <div>
                    <h2>Yeni işletmelere danışmanlık</h2>
                    <p class="lead">Açılış, konumlandırma ve pazarlama desteğiyle yanınızdayız.</p>
                </div>
                <div class="cta-actions">
                    <a class="btn btn-primary" href="firma_kayit.php?tip=danismanlik">Danışmanlık Al</a>
                    <a class="btn btn-outline" href="firma_kayit.php?tip=basvuru">Yeni İşletme Başvurusu</a>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div>Oyunevleri.com • Güvenli, onaylı ve eğlenceli oyun grupları</div>
        </div>
    </footer>

    <div class="type-modal" data-type-modal>
        <div class="type-modal-card">
            <div class="type-modal-head">
                <h3 data-type-title>İl Seçiniz</h3>
                <button class="type-modal-close" type="button" data-type-close>×</button>
            </div>
            <div class="type-city-list" data-type-city-list></div>
        </div>
    </div>
    <script>
        (function () {
            var typeCities = <?php echo json_encode($sehirler_by_type, JSON_UNESCAPED_UNICODE); ?>;
            var modal = document.querySelector('[data-type-modal]');
            var titleEl = document.querySelector('[data-type-title]');
            var listEl = document.querySelector('[data-type-city-list]');
            var closeBtn = document.querySelector('[data-type-close]');
            if (!modal || !titleEl || !listEl) { return; }

            function closeModal() {
                modal.classList.remove('is-open');
                document.body.style.overflow = '';
            }

            function openModal(type) {
                titleEl.textContent = type + ' için İl Seçiniz';
                listEl.innerHTML = '';
                var cities = typeCities[type] || [];
                if (!cities.length) {
                    var empty = document.createElement('div');
                    empty.className = 'type-empty';
                    empty.textContent = 'Bu tür için şehir bulunamadı.';
                    listEl.appendChild(empty);
                } else {
                    cities.forEach(function (city) {
                        var row = document.createElement('a');
                        row.className = 'type-city-row';
                        row.href = 'search.php?kurum_type=' + encodeURIComponent(type) + '&sehir=' + encodeURIComponent(city);
                        row.innerHTML = '<span>' + city + '</span><span>›</span>';
                        listEl.appendChild(row);
                    });
                }
                modal.classList.add('is-open');
                document.body.style.overflow = 'hidden';
            }

            document.querySelectorAll('[data-type-select]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var type = btn.getAttribute('data-type-select');
                    if (!type) { return; }
                    openModal(type);
                });
            });

            if (closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    closeModal();
                }
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeModal();
                }
            });
        })();
    </script>

    <?php if ($profil_modal_goster) { ?>
        <div class="modal-overlay is-open" id="profilModal">
            <div class="modal-box">
                <h3>Öğrenci Bilgisi Eksik</h3>
                <p>Kayıtlı bir çocuğunuz bulunmuyor. Rezervasyon yapabilmek için profilinize giderek öğrenci bilgilerini ekleyin.</p>
                <form method="post" style="margin-top:12px;">
                    <input type="hidden" name="action" value="veli_ogrenci_hizli_ekle">
                    <div class="field" style="margin-bottom:10px;">
                        <label>Öğrenci Ad Soyad</label>
                        <input type="text" name="ad_soyad" required>
                    </div>
                    <div class="field" style="margin-bottom:10px;">
                        <label>Doğum Tarihi</label>
                        <input type="date" name="dogum_tarihi" required>
                    </div>
                    <div class="field">
                        <label>Sağlık Notları (opsiyonel)</label>
                        <textarea name="saglik_notlari"></textarea>
                    </div>
                    <?php if ($modal_hata !== '') { ?>
                        <div class="modal-alert"><?php echo htmlspecialchars($modal_hata, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php } ?>
                    <div class="modal-actions">
                        <button class="btn btn-outline" type="button" id="profilModalKapat">Daha sonra</button>
                        <a class="btn btn-outline" href="profilim.php">Profilime Git</a>
                        <button class="btn btn-primary" type="submit">Öğrenciyi Ekle</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            (function() {
                var modal = document.getElementById('profilModal');
                var kapat = document.getElementById('profilModalKapat');
                if (!modal) { return; }
                document.body.style.overflow = 'hidden';
                function closeModal() {
                    modal.classList.remove('is-open');
                    document.body.style.overflow = '';
                }
                kapat.addEventListener('click', closeModal);
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) { closeModal(); }
                });
            })();
        </script>
    <?php } ?>
</body>
</html>
