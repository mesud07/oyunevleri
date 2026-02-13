<?php
require_once("includes/config.php");
require_once("includes/functions.php");
$public_nav_active = '';
$form_error = '';
$form_success = '';
$form_values = [
    'kurum_adi' => '',
    'yetkili_adi' => '',
    'telefon' => '',
    'eposta' => '',
    'sehir' => '',
    'ilce' => '',
    'kurum_turu' => '',
    'mesaj' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form_values as $key => $val) {
        $form_values[$key] = trim((string) ($_POST[$key] ?? ''));
    }

    if ($form_values['kurum_adi'] === '' || $form_values['yetkili_adi'] === '' || $form_values['telefon'] === '' || $form_values['eposta'] === '') {
        $form_error = 'Lütfen zorunlu alanları doldurun.';
    } elseif (!filter_var($form_values['eposta'], FILTER_VALIDATE_EMAIL)) {
        $form_error = 'Lütfen geçerli bir e-posta adresi girin.';
    } else {
        $subject = 'Yeni Firma Başvurusu: ' . $form_values['kurum_adi'];
        $html = '
            <h2>Yeni Firma Başvurusu</h2>
            <table style="border-collapse:collapse;">
                <tr><td style="padding:6px 10px;border:1px solid #eee;"><strong>Kurum Adı</strong></td><td style="padding:6px 10px;border:1px solid #eee;">' . htmlspecialchars($form_values['kurum_adi']) . '</td></tr>
                <tr><td style="padding:6px 10px;border:1px solid #eee;"><strong>Yetkili Adı</strong></td><td style="padding:6px 10px;border:1px solid #eee;">' . htmlspecialchars($form_values['yetkili_adi']) . '</td></tr>
                <tr><td style="padding:6px 10px;border:1px solid #eee;"><strong>Telefon</strong></td><td style="padding:6px 10px;border:1px solid #eee;">' . htmlspecialchars($form_values['telefon']) . '</td></tr>
                <tr><td style="padding:6px 10px;border:1px solid #eee;"><strong>E-posta</strong></td><td style="padding:6px 10px;border:1px solid #eee;">' . htmlspecialchars($form_values['eposta']) . '</td></tr>
                <tr><td style="padding:6px 10px;border:1px solid #eee;"><strong>Şehir</strong></td><td style="padding:6px 10px;border:1px solid #eee;">' . htmlspecialchars($form_values['sehir']) . '</td></tr>
                <tr><td style="padding:6px 10px;border:1px solid #eee;"><strong>İlçe</strong></td><td style="padding:6px 10px;border:1px solid #eee;">' . htmlspecialchars($form_values['ilce']) . '</td></tr>
                <tr><td style="padding:6px 10px;border:1px solid #eee;"><strong>Kurum Türü</strong></td><td style="padding:6px 10px;border:1px solid #eee;">' . htmlspecialchars($form_values['kurum_turu']) . '</td></tr>
                <tr><td style="padding:6px 10px;border:1px solid #eee;"><strong>Mesaj</strong></td><td style="padding:6px 10px;border:1px solid #eee;">' . nl2br(htmlspecialchars($form_values['mesaj'])) . '</td></tr>
            </table>
        ';
        if (mail_gonder('info@oyunevleri.com', $subject, $html)) {
            $form_success = 'Başvurunuz alındı. En kısa sürede sizinle iletişime geçeceğiz.';
            foreach ($form_values as $key => $val) {
                $form_values[$key] = '';
            }
        } else {
            $form_error = 'E-posta gönderilemedi. Lütfen daha sonra tekrar deneyin.';
        }
    }
}
?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Firma Başvuru | Oyunevleri.com</title>
    <?php require_once("includes/analytics.php"); ?>
    <link rel="icon" type="image/x-icon" href="favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f9fafc;
            --ink: #1f2937;
            --muted: #6b7280;
            --primary: #ff4f7b;
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
            max-width: 920px;
            margin: 0 auto;
            padding: 0 24px;
        }
        .hero {
            padding: 70px 0 40px;
            text-align: left;
        }
        .card {
            background: var(--card);
            border-radius: 20px;
            border: 1px solid var(--stroke);
            box-shadow: var(--shadow);
            padding: 24px;
        }
        h1 {
            font-family: "Baloo 2", cursive;
            font-size: 36px;
            margin: 0 0 12px;
        }
        p {
            color: var(--muted);
            line-height: 1.6;
        }
        .cta {
            margin-top: 18px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .alert {
            margin: 12px 0 18px;
            padding: 12px 16px;
            border-radius: 12px;
            font-weight: 600;
        }
        .alert.success {
            background: #ecfdf5;
            color: #0f766e;
            border: 1px solid rgba(15,118,110,0.2);
        }
        .alert.error {
            background: #fff1f2;
            color: #be123c;
            border: 1px solid rgba(190,18,60,0.2);
        }
        .form-grid {
            margin-top: 18px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }
        .form-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-field label {
            font-weight: 700;
            font-size: 14px;
        }
        .form-field input,
        .form-field select,
        .form-field textarea {
            border-radius: 12px;
            border: 1px solid var(--stroke);
            padding: 12px 14px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-field textarea {
            resize: vertical;
            min-height: 120px;
        }
        .form-actions {
            margin-top: 20px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 18px;
            border-radius: 999px;
            font-weight: 700;
            text-decoration: none;
            border: 2px solid transparent;
        }
        .btn-primary {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 12px 20px rgba(255,79,123,0.25);
        }
        .btn-outline {
            background: #fff;
            color: var(--primary);
            border-color: var(--primary);
        }
        <?php require_once("includes/public_header.css.php"); ?>
    </style>
</head>
<body>
    <?php require_once("includes/public_header.php"); ?>

    <section class="hero">
        <div class="container">
            <div class="card">
                <h1>Firmalar İçin Başvuru</h1>
                <p>Oyunevleri.com pazaryerinde yer almak ve kurumunuzu velilerle buluşturmak için formu doldurun. Ekibimiz sizinle en kısa sürede iletişime geçecek.</p>
                <?php if ($form_error !== '') { ?>
                    <div class="alert error"><?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php } ?>
                <?php if ($form_success !== '') { ?>
                    <div class="alert success"><?php echo htmlspecialchars($form_success, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php } ?>
                <form method="post" action="">
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="kurum_adi">Kurum Adı *</label>
                            <input type="text" id="kurum_adi" name="kurum_adi" required value="<?php echo htmlspecialchars($form_values['kurum_adi'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-field">
                            <label for="yetkili_adi">Yetkili Adı *</label>
                            <input type="text" id="yetkili_adi" name="yetkili_adi" required value="<?php echo htmlspecialchars($form_values['yetkili_adi'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-field">
                            <label for="telefon">Telefon *</label>
                            <input type="text" id="telefon" name="telefon" required value="<?php echo htmlspecialchars($form_values['telefon'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-field">
                            <label for="eposta">E-posta *</label>
                            <input type="email" id="eposta" name="eposta" required value="<?php echo htmlspecialchars($form_values['eposta'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-field">
                            <label for="sehir">Şehir</label>
                            <input type="text" id="sehir" name="sehir" value="<?php echo htmlspecialchars($form_values['sehir'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-field">
                            <label for="ilce">İlçe</label>
                            <input type="text" id="ilce" name="ilce" value="<?php echo htmlspecialchars($form_values['ilce'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-field">
                            <label for="kurum_turu">Kurum Türü</label>
                            <select id="kurum_turu" name="kurum_turu">
                                <?php $selected = $form_values['kurum_turu']; ?>
                                <option value="" <?php echo $selected === '' ? 'selected' : ''; ?>>Seçiniz</option>
                                <option value="Oyun Evi" <?php echo $selected === 'Oyun Evi' ? 'selected' : ''; ?>>Oyun Evi</option>
                                <option value="Kreş" <?php echo $selected === 'Kreş' ? 'selected' : ''; ?>>Kreş</option>
                                <option value="Anaokulu" <?php echo $selected === 'Anaokulu' ? 'selected' : ''; ?>>Anaokulu</option>
                                <option value="Diğer" <?php echo $selected === 'Diğer' ? 'selected' : ''; ?>>Diğer</option>
                            </select>
                        </div>
                        <div class="form-field" style="grid-column: span 2;">
                            <label for="mesaj">Not / Ek Bilgi</label>
                            <textarea id="mesaj" name="mesaj"><?php echo htmlspecialchars($form_values['mesaj'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Başvuruyu Gönder</button>
                        <a class="btn btn-outline" href="index.php">Ana Sayfaya Dön</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</body>
</html>
