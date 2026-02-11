<?php
// www.oyunevleri.com store page
require_once("includes/config.php");

$kurum_id = (int) ($_GET['id'] ?? 0);
$kurum_adi_param = trim($_GET['kurum'] ?? '');
$kurum = null;
$galeri = [];
$egitmenler = [];
$yorumlar = [];
$fiyatlar = [];
$avg_puan = 0;
$yorum_sayisi = 0;
$veli_giris = !empty($_SESSION['veli_giris']) && !empty($_SESSION['veli']);
$next_url = $_SERVER['REQUEST_URI'] ?? 'store.php';

if (!empty($db_master)) {
    if ($kurum_id > 0) {
        $stmt = $db_master->prepare("SELECT * FROM kurumlar WHERE id = :id AND durum = 1 LIMIT 1");
        $stmt->execute(['id' => $kurum_id]);
        $kurum = $stmt->fetch();
    } elseif ($kurum_adi_param !== '') {
        $stmt = $db_master->prepare("SELECT * FROM kurumlar WHERE kurum_adi = :adi AND durum = 1 LIMIT 1");
        $stmt->execute(['adi' => $kurum_adi_param]);
        $kurum = $stmt->fetch();
    }

    if ($kurum) {
        $kurum_id = (int) $kurum['id'];

        $stmt = $db_master->prepare("SELECT gorsel_yol FROM kurum_galeri WHERE kurum_id = :kurum_id ORDER BY sira ASC, id ASC");
        $stmt->execute(['kurum_id' => $kurum_id]);
        $galeri = $stmt->fetchAll();

        $stmt = $db_master->prepare("SELECT ad_soyad, uzmanlik, biyografi, fotograf_yol FROM kurum_egitmenler WHERE kurum_id = :kurum_id ORDER BY id DESC");
        $stmt->execute(['kurum_id' => $kurum_id]);
        $egitmenler = $stmt->fetchAll();

        $stmt = $db_master->prepare("SELECT veli_adi, puan, yorum, tarih FROM kurum_yorumlar WHERE kurum_id = :kurum_id ORDER BY tarih DESC");
        $stmt->execute(['kurum_id' => $kurum_id]);
        $yorumlar = $stmt->fetchAll();

        $stmt = $db_master->prepare("SELECT paket_adi, aciklama, fiyat, birim FROM kurum_fiyatlar WHERE kurum_id = :kurum_id ORDER BY id DESC");
        $stmt->execute(['kurum_id' => $kurum_id]);
        $fiyatlar = $stmt->fetchAll();

        $stmt = $db_master->prepare("SELECT AVG(puan) AS avg_puan, COUNT(*) AS yorum_sayisi FROM kurum_yorumlar WHERE kurum_id = :kurum_id");
        $stmt->execute(['kurum_id' => $kurum_id]);
        $puan = $stmt->fetch();
        $avg_puan = $puan ? round((float) $puan['avg_puan'], 1) : 0;
        $yorum_sayisi = $puan ? (int) $puan['yorum_sayisi'] : 0;
    }
}

$kurum_adi = $kurum ? $kurum['kurum_adi'] : 'Kurum bulunamadı';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($kurum_adi, ENT_QUOTES, 'UTF-8'); ?> | Oyunevleri.com</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f9fafc;
            --ink: #1f2937;
            --muted: #6b7280;
            --primary: #ff7a59;
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
        .hero {
            margin-top: 16px;
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 20px;
        }
        .gallery {
            background: #fff;
            border-radius: 22px;
            border: 1px solid var(--stroke);
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        .gallery-main {
            height: 320px;
            background: linear-gradient(140deg, #ffd9cf, #d7f3f0);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #6b7280;
            overflow: hidden;
        }
        .gallery-main img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .gallery-thumbs {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            padding: 10px;
            background: #fff;
        }
        .thumb {
            height: 70px;
            border-radius: 12px;
            background: #f1f5f9;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: var(--muted);
        }
        .thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .info-card {
            background: #fff;
            border-radius: 22px;
            border: 1px solid var(--stroke);
            padding: 18px;
            box-shadow: var(--shadow);
        }
        .info-card h1 {
            margin: 0 0 8px;
            font-family: "Baloo 2", cursive;
        }
        .tags {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .tag {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 999px;
            background: #f1f5f9;
        }
        .price-box {
            margin-top: 12px;
            padding: 12px;
            border-radius: 14px;
            background: #fff7e0;
            font-weight: 700;
        }
        .quick-contact {
            margin-top: 14px;
            display: grid;
            gap: 10px;
        }
        .quick-contact input {
            border-radius: 10px;
            border: 1px solid var(--stroke);
            padding: 10px 12px;
        }
        .tabs {
            margin-top: 26px;
            background: #fff;
            border-radius: 20px;
            border: 1px solid var(--stroke);
            padding: 18px;
            box-shadow: 0 10px 24px rgba(31,41,55,0.08);
        }
        .tab-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .tab-buttons button {
            border: 1px solid var(--stroke);
            background: #fff;
            padding: 8px 12px;
            border-radius: 12px;
            cursor: pointer;
        }
        .tab-content {
            margin-top: 14px;
            color: var(--muted);
            line-height: 1.6;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-top: 12px;
        }
        .person-card {
            padding: 12px;
            border-radius: 14px;
            border: 1px solid var(--stroke);
            background: #f9fafc;
        }
        .review {
            padding: 12px;
            border-radius: 14px;
            border: 1px solid var(--stroke);
            background: #fff;
        }
        @media (max-width: 980px) {
            .hero { grid-template-columns: 1fr; }
            .grid-2 { grid-template-columns: 1fr; }
        }
        .login-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 9999;
        }
        .login-modal.is-open { display: flex; }
        .login-box {
            background: #fff;
            border-radius: 18px;
            padding: 22px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 45px rgba(31,41,55,0.2);
            border: 1px solid rgba(31,41,55,0.08);
        }
        .login-box h3 {
            margin: 0 0 8px;
            font-family: "Baloo 2", cursive;
        }
        .login-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
            justify-content: flex-end;
        }
        .price-locked {
            font-size: 13px;
            color: var(--muted);
            margin-top: 6px;
        }
        .login-trigger {
            cursor: pointer;
            color: #19a7bd;
            font-weight: 600;
            text-decoration: underline;
        }
        .about-text {
            color: var(--muted);
            line-height: 1.6;
            margin: 0;
        }
        <?php require_once("includes/public_header.css.php"); ?>
    </style>
</head>
<body>
    <?php $public_nav_active = 'storage'; $public_login_next = $next_url; require_once("includes/public_header.php"); ?>

    <section class="container hero">
            <div class="gallery">
                <div class="gallery-main">
                    <?php if (!empty($galeri)) { ?>
                        <img src="<?php echo htmlspecialchars($galeri[0]['gorsel_yol'], ENT_QUOTES, 'UTF-8'); ?>" alt="Kurum görseli">
                    <?php } else { ?>
                        Galeri Slider
                    <?php } ?>
                </div>
                <div class="gallery-thumbs">
                    <?php if (!empty($galeri)) { ?>
                        <?php foreach (array_slice($galeri, 0, 4) as $gorsel) { ?>
                            <div class="thumb">
                                <img src="<?php echo htmlspecialchars($gorsel['gorsel_yol'], ENT_QUOTES, 'UTF-8'); ?>" alt="Galeri">
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="thumb"></div>
                        <div class="thumb"></div>
                        <div class="thumb"></div>
                        <div class="thumb"></div>
                    <?php } ?>
                </div>
            </div>
            <div class="info-card">
            <h1><?php echo htmlspecialchars($kurum_adi, ENT_QUOTES, 'UTF-8'); ?></h1>
            <div class="tags">
                <?php if ($kurum) {
                    $tags = [];
                    if ((int) $kurum['meb_onay'] === 1) { $tags[] = 'MEB Onaylı'; }
                    if ((int) $kurum['aile_sosyal_onay'] === 1) { $tags[] = 'Aile Sosyal'; }
                    if ((int) $kurum['hizmet_bahceli'] === 1) { $tags[] = 'Bahçeli'; }
                    if ((int) $kurum['hizmet_guvenlik_kamerasi'] === 1) { $tags[] = 'Kamera'; }
                    if ((int) $kurum['hizmet_ingilizce'] === 1) { $tags[] = 'İngilizce'; }
                    foreach ($tags as $tag) { ?>
                        <span class="tag"><?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php } ?>
                <?php } ?>
            </div>
            <div>
                <?php if ($kurum) {
                    $lokasyon_parca = array_filter([trim((string) $kurum['ilce']), trim((string) $kurum['sehir'])]);
                    $lokasyon = implode(', ', $lokasyon_parca);
                    ?>
                    <?php echo htmlspecialchars($lokasyon !== '' ? $lokasyon : 'Konum bilgisi yok', ENT_QUOTES, 'UTF-8'); ?>
                    • <?php echo !empty($kurum['min_ay']) || !empty($kurum['max_ay'])
                        ? htmlspecialchars(($kurum['min_ay'] ?: '0') . '-' . ($kurum['max_ay'] ?: '72') . ' Ay', ENT_QUOTES, 'UTF-8')
                        : 'Tüm yaşlar'; ?>
                <?php } ?>
            </div>
            <div class="price-box">
                <?php if ($veli_giris) { ?>
                    <?php if (!empty($fiyatlar)) { ?>
                        <?php echo '₺' . number_format((float) $fiyatlar[0]['fiyat'], 0, ',', '.') . ' / ' . htmlspecialchars($fiyatlar[0]['birim'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php } else { ?>
                        Fiyat bilgisi yok
                    <?php } ?>
                <?php } else { ?>
                    Fiyatlar giriş sonrası görüntülenir
                    <div class="price-locked">
                        <span class="login-trigger" data-open-login="1">Fiyatları görmek için giriş yapın</span>
                    </div>
                <?php } ?>
            </div>
            <form class="quick-contact" id="quick-contact-form">
                <input type="hidden" name="kurum_id" value="<?php echo (int) $kurum_id; ?>">
                <input type="hidden" name="sayfa_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="text" name="ad_soyad" placeholder="Ad Soyad" required>
                <input type="text" name="telefon" placeholder="Telefon" required>
                <input type="text" name="mesaj" placeholder="Mesaj (opsiyonel)">
                <button class="btn btn-primary" type="submit">Hızlı İletişim</button>
                <div id="quick-contact-msg" style="font-size:14px;color:var(--muted);"></div>
            </form>
            <?php if (!$veli_giris) { ?>
                <div style="margin-top:12px;">
                    <a class="btn btn-outline" href="#" id="loginModalOpen">Kayıt Ol / Giriş Yap</a>
                </div>
            <?php } ?>
            <?php if ($yorum_sayisi > 0) { ?>
                <div style="margin-top:10px;color:var(--muted);font-size:14px;">
                    Ortalama Puan: <?php echo $avg_puan; ?> (<?php echo $yorum_sayisi; ?> yorum)
                </div>
            <?php } ?>
        </div>
    </section>

    <section class="container tabs">
        <div class="tab-buttons">
            <button>Hakkımızda</button>
            <button>Gruplarımız</button>
            <button>Eğitmenler</button>
            <button>Yorumlar</button>
        </div>
        <div class="tab-content">
            <?php if (!empty($kurum['hakkimizda'])) { ?>
                <p class="about-text"><?php echo nl2br(htmlspecialchars($kurum['hakkimizda'], ENT_QUOTES, 'UTF-8')); ?></p>
            <?php } else { ?>
                <p class="about-text"><?php echo $kurum ? 'Kurum hakkında bilgi eklenmemiş.' : 'Kurum bilgisi bulunamadı.'; ?></p>
            <?php } ?>
            <div class="grid-2">
                <div>
                    <h3>Gruplarımız</h3>
                    <?php if ($veli_giris) { ?>
                        <?php if (!empty($fiyatlar)) { ?>
                            <ul>
                                <?php foreach ($fiyatlar as $fiyat) { ?>
                                    <li><?php echo htmlspecialchars($fiyat['paket_adi'], ENT_QUOTES, 'UTF-8'); ?>
                                        • ₺<?php echo number_format((float) $fiyat['fiyat'], 0, ',', '.'); ?>
                                        / <?php echo htmlspecialchars($fiyat['birim'], ENT_QUOTES, 'UTF-8'); ?>
                                    </li>
                                <?php } ?>
                            </ul>
                        <?php } else { ?>
                            <div>Henüz paket bilgisi yok.</div>
                        <?php } ?>
                    <?php } else { ?>
                        <div><span class="login-trigger" data-open-login="1">Fiyatları görmek için giriş yapın</span></div>
                    <?php } ?>
                </div>
                <div>
                    <h3>Eğitmenler</h3>
                    <?php if (!empty($egitmenler)) { ?>
                        <?php foreach (array_slice($egitmenler, 0, 3) as $egitmen) { ?>
                            <div class="person-card"><?php echo htmlspecialchars($egitmen['ad_soyad'], ENT_QUOTES, 'UTF-8'); ?>
                                • <?php echo htmlspecialchars($egitmen['uzmanlik'] ?? 'Eğitmen', ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="person-card">Henüz eğitmen bilgisi yok.</div>
                    <?php } ?>
                </div>
            </div>
            <div class="grid-2" style="margin-top:16px;">
                <?php if (!empty($yorumlar)) { ?>
                    <?php foreach (array_slice($yorumlar, 0, 2) as $yorum) { ?>
                        <div class="review">
                            <strong><?php echo htmlspecialchars($yorum['veli_adi'] ?? 'Veli', ENT_QUOTES, 'UTF-8'); ?></strong>
                            <p><?php echo htmlspecialchars($yorum['yorum'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <div class="review">
                        <strong>Henüz yorum yok</strong>
                        <p>İlk yorumu siz bırakın.</p>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>
    <?php if (!$veli_giris) { ?>
        <div class="login-modal is-open" id="loginModal">
            <div class="login-box">
                <h3>Giriş Gerekli</h3>
                <p>Fiyatları görebilmek ve kayıt oluşturmak için giriş yapmanız veya kayıt olmanız gerekiyor.</p>
                <div class="login-actions">
                    <button class="btn btn-outline" type="button" id="loginModalClose">Daha Sonra</button>
                    <a class="btn btn-outline" href="login.php?next=<?php echo urlencode($next_url); ?>">Giriş Yap</a>
                    <a class="btn btn-primary" href="register.php?next=<?php echo urlencode($next_url); ?>">Kayıt Ol</a>
                </div>
            </div>
        </div>
    <?php } ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        $('#quick-contact-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $msg = $('#quick-contact-msg');
            $msg.text('Gönderiliyor...');
            $.ajax({
                url: 'lead.php',
                type: 'POST',
                data: $form.serialize(),
                success: function(res) {
                    if (res && res.durum === 'ok') {
                        $msg.text(res.mesaj || 'Talebiniz alındı.');
                        $form[0].reset();
                    } else {
                        $msg.text((res && res.mesaj) ? res.mesaj : 'Bir hata oluştu.');
                    }
                },
                error: function() {
                    $msg.text('Bir hata oluştu.');
                }
            });
        });
    </script>
    <?php if (!$veli_giris) { ?>
    <script>
        (function() {
            var modal = document.getElementById('loginModal');
            var closeBtn = document.getElementById('loginModalClose');
            var openBtn = document.getElementById('loginModalOpen');
            var priceTriggers = document.querySelectorAll('[data-open-login="1"]');
            if (!modal) { return; }
            function closeModal() { modal.classList.remove('is-open'); }
            closeBtn && closeBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', function(e) {
                if (e.target === modal) { closeModal(); }
            });
            if (openBtn) {
                openBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    modal.classList.add('is-open');
                });
            }
            priceTriggers.forEach(function(el) {
                el.addEventListener('click', function(e) {
                    e.preventDefault();
                    modal.classList.add('is-open');
                });
            });
        })();
    </script>
    <?php } ?>
</body>
</html>
