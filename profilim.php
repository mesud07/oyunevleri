<?php
require_once("includes/config.php");
require_once("includes/functions.php");

if (empty($_SESSION['veli_giris']) || empty($_SESSION['veli']['id'])) {
    header('Location: login.php');
    exit;
}

$veli_id = (int) ($_SESSION['veli']['id'] ?? 0);
$kurum_id = (int) ($_SESSION['kurum_id'] ?? 0);
$hata = '';
$basari = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (empty($db)) {
        $hata = 'Kurum veritabanı bağlantısı bulunamadı.';
    } elseif ($action === 'veli_guncelle') {
        $ad_soyad = trim($_POST['ad_soyad'] ?? '');
        $telefon = trim($_POST['telefon'] ?? '');
        $eposta = trim($_POST['eposta'] ?? '');
        $sifre = $_POST['sifre'] ?? '';
        $sifre_tekrar = $_POST['sifre_tekrar'] ?? '';

        if ($ad_soyad === '') {
            $hata = 'Ad soyad alanı zorunludur.';
        } elseif ($sifre !== '' && $sifre !== $sifre_tekrar) {
            $hata = 'Şifreler eşleşmiyor.';
        } else {
            $data = [
                'ad_soyad' => $ad_soyad,
                'telefon' => $telefon !== '' ? $telefon : null,
                'eposta' => $eposta !== '' ? $eposta : null,
            ];
            if ($sifre !== '') {
                $data['sifre'] = parola_hash($sifre);
            }
            $ok = update_data('veliler', $data, ['id' => $veli_id, 'kurum_id' => $kurum_id]);
            if ($ok) {
                $basari = 'Profil bilgileri güncellendi.';
                $_SESSION['veli']['ad_soyad'] = $ad_soyad;
                $_SESSION['veli']['telefon'] = $telefon !== '' ? $telefon : null;
                $_SESSION['veli']['eposta'] = $eposta !== '' ? $eposta : null;
            } else {
                $hata = 'Profil güncellenemedi.';
            }
        }
    } elseif ($action === 'ogrenci_guncelle') {
        $ogrenci_id = (int) ($_POST['ogrenci_id'] ?? 0);
        $ad_soyad = trim($_POST['ad_soyad'] ?? '');
        $dogum_tarihi = trim($_POST['dogum_tarihi'] ?? '');
        $saglik_notlari = trim($_POST['saglik_notlari'] ?? '');

        if ($ogrenci_id <= 0 || $ad_soyad === '' || $dogum_tarihi === '') {
            $hata = 'Öğrenci bilgileri eksik.';
        } else {
            $data = [
                'ad_soyad' => $ad_soyad,
                'dogum_tarihi' => $dogum_tarihi,
                'saglik_notlari' => $saglik_notlari,
            ];
            $ok = update_data('ogrenciler', $data, [
                'id' => $ogrenci_id,
                'veli_id' => $veli_id,
                'kurum_id' => $kurum_id,
            ]);
            $basari = $ok ? 'Öğrenci güncellendi.' : 'Öğrenci güncellenemedi.';
        }
    } elseif ($action === 'ogrenci_ekle') {
        $ad_soyad = trim($_POST['ad_soyad'] ?? '');
        $dogum_tarihi = trim($_POST['dogum_tarihi'] ?? '');
        $saglik_notlari = trim($_POST['saglik_notlari'] ?? '');

        if ($ad_soyad === '' || $dogum_tarihi === '') {
            $hata = 'Öğrenci bilgileri eksik.';
        } else {
            $data = [
                'kurum_id' => $kurum_id,
                'veli_id' => $veli_id,
                'ad_soyad' => $ad_soyad,
                'dogum_tarihi' => $dogum_tarihi,
                'saglik_notlari' => $saglik_notlari,
            ];
            $new_id = insert_into('ogrenciler', $data);
            $basari = $new_id ? 'Öğrenci eklendi.' : 'Öğrenci eklenemedi.';
        }
    }
}

$veli = null;
$ogrenciler = [];
if (!empty($db)) {
    $stmt = $db->prepare("SELECT id, ad_soyad, telefon, eposta FROM veliler WHERE id = :id AND kurum_id = :kurum_id LIMIT 1");
    $stmt->execute(['id' => $veli_id, 'kurum_id' => $kurum_id]);
    $veli = $stmt->fetch();

    $stmt = $db->prepare("SELECT id, ad_soyad, dogum_tarihi, saglik_notlari FROM ogrenciler WHERE veli_id = :veli_id AND kurum_id = :kurum_id ORDER BY ad_soyad");
    $stmt->execute(['veli_id' => $veli_id, 'kurum_id' => $kurum_id]);
    $ogrenciler = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Oyunevleri.com | Profilim</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f9fafc;
            --ink: #1f2937;
            --muted: #6b7280;
            --primary: #ff7a59;
            --primary-dark: #ea6a4b;
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
            min-height: 100vh;
        }
        .container {
            max-width: 980px;
            margin: 0 auto;
            padding: 0 24px;
        }
        header { padding: 24px 0 12px; }
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
        }
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
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
            color: white;
            box-shadow: 0 12px 24px rgba(255, 122, 89, 0.35);
        }
        .btn-outline {
            background: white;
            color: var(--ink);
            border: 1px solid var(--stroke);
        }
        .section {
            padding: 24px 0 40px;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--stroke);
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 10px 24px rgba(31,41,55,0.08);
            margin-bottom: 18px;
        }
        .card h2 {
            margin: 0 0 14px;
            font-family: "Baloo 2", cursive;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
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
        .field input, .field textarea {
            border-radius: 12px;
            border: 1px solid var(--stroke);
            padding: 12px 14px;
            font-size: 15px;
            outline: none;
            background: #fff;
        }
        .field textarea { resize: vertical; min-height: 80px; }
        .actions { margin-top: 16px; display: flex; gap: 10px; }
        .notice {
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-weight: 600;
        }
        .notice.ok { background: #e8f7f1; color: #0f5f4a; }
        .notice.err { background: #ffe9e6; color: #8a2d22; }
        .divider { height: 1px; background: rgba(31,41,55,0.1); margin: 12px 0 16px; }
        @media (max-width: 720px) {
            .grid { grid-template-columns: 1fr; }
            .nav { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <header>
        <div class="container nav">
            <a class="logo" href="index.php">Oyunevleri.com</a>
            <div class="nav-actions">
                <a class="btn btn-outline" href="index.php#grup-takvimim">Grup Takvimim</a>
                <a class="btn btn-outline" href="logout.php">Çıkış</a>
            </div>
        </div>
    </header>

    <section class="section">
        <div class="container">
            <?php if ($basari !== '') { ?>
                <div class="notice ok"><?php echo htmlspecialchars($basari, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php } ?>
            <?php if ($hata !== '') { ?>
                <div class="notice err"><?php echo htmlspecialchars($hata, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php } ?>

            <div class="card">
                <h2>Profil Bilgilerim</h2>
                <?php if (empty($veli)) { ?>
                    <p>Profil bilgileriniz bulunamadı. Kurum veritabanı bağlantısını kontrol edin.</p>
                <?php } else { ?>
                    <form method="post">
                        <input type="hidden" name="action" value="veli_guncelle">
                        <div class="grid">
                            <div class="field">
                                <label>Ad Soyad</label>
                                <input type="text" name="ad_soyad" value="<?php echo htmlspecialchars($veli['ad_soyad'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="field">
                                <label>Telefon</label>
                                <input type="text" name="telefon" value="<?php echo htmlspecialchars($veli['telefon'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="field">
                                <label>E-posta</label>
                                <input type="email" name="eposta" value="<?php echo htmlspecialchars($veli['eposta'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="field">
                                <label>Yeni Şifre</label>
                                <input type="password" name="sifre" placeholder="Şifreyi boş bırakabilirsiniz">
                            </div>
                            <div class="field">
                                <label>Yeni Şifre (Tekrar)</label>
                                <input type="password" name="sifre_tekrar" placeholder="Şifreyi tekrar girin">
                            </div>
                        </div>
                        <div class="actions">
                            <button class="btn btn-primary" type="submit">Profilimi Güncelle</button>
                        </div>
                    </form>
                <?php } ?>
            </div>

            <div class="card">
                <h2>Öğrenci Bilgilerim</h2>
                <?php if (empty($ogrenciler)) { ?>
                    <p>Kayıtlı öğrenci bulunamadı. Aşağıdan yeni öğrenci ekleyebilirsiniz.</p>
                <?php } else { ?>
                    <?php foreach ($ogrenciler as $ogrenci) { ?>
                        <form method="post" style="margin-bottom:18px;">
                            <input type="hidden" name="action" value="ogrenci_guncelle">
                            <input type="hidden" name="ogrenci_id" value="<?php echo (int) $ogrenci['id']; ?>">
                            <div class="grid">
                                <div class="field">
                                    <label>Öğrenci Ad Soyad</label>
                                    <input type="text" name="ad_soyad" value="<?php echo htmlspecialchars($ogrenci['ad_soyad'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                                <div class="field">
                                    <label>Doğum Tarihi</label>
                                    <input type="date" name="dogum_tarihi" value="<?php echo htmlspecialchars($ogrenci['dogum_tarihi'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                                <div class="field" style="grid-column: 1 / -1;">
                                    <label>Sağlık Notları</label>
                                    <textarea name="saglik_notlari"><?php echo htmlspecialchars($ogrenci['saglik_notlari'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                            </div>
                            <div class="actions">
                                <button class="btn btn-outline" type="submit">Öğrenciyi Güncelle</button>
                            </div>
                        </form>
                        <div class="divider"></div>
                    <?php } ?>
                <?php } ?>

                <h3>Yeni Öğrenci Ekle</h3>
                <form method="post">
                    <input type="hidden" name="action" value="ogrenci_ekle">
                    <div class="grid">
                        <div class="field">
                            <label>Öğrenci Ad Soyad</label>
                            <input type="text" name="ad_soyad" required>
                        </div>
                        <div class="field">
                            <label>Doğum Tarihi</label>
                            <input type="date" name="dogum_tarihi" required>
                        </div>
                        <div class="field" style="grid-column: 1 / -1;">
                            <label>Sağlık Notları</label>
                            <textarea name="saglik_notlari"></textarea>
                        </div>
                    </div>
                    <div class="actions">
                        <button class="btn btn-primary" type="submit">Öğrenci Ekle</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</body>
</html>
