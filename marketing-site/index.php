<?php
declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$features = [
    ['icon' => 'users', 'title' => 'Öğrenci ve veli yönetimi', 'text' => 'Öğrenci profilleri, veli iletişim bilgileri, sağlık notları ve kayıt geçmişi tek bir düzenli ekranda.'],
    ['icon' => 'calendar', 'title' => 'Grup ve program planlama', 'text' => 'Haftalık ders akışı, grup kapasitesi ve eğitmen planlarını çakışmaları azaltarak yönetin.'],
    ['icon' => 'clock', 'title' => 'Randevu ve telafi takibi', 'text' => 'Yeni randevu, hızlı randevu ve telafi süreçlerini öğrenci profiliyle bağlantılı biçimde takip edin.'],
    ['icon' => 'wallet', 'title' => 'Paket ve tahsilat kontrolü', 'text' => 'Paket kullanımları, ödemeler, giderler ve kasa hareketleriyle finansal görünürlüğünüzü artırın.'],
    ['icon' => 'message', 'title' => 'SMS ve veli iletişimi', 'text' => 'Hatırlatma ve bilgilendirmeleri doğru veliye, doğru öğrenci kaydı üzerinden ulaştırın.'],
    ['icon' => 'document', 'title' => 'Onam formları ve PDF', 'text' => 'Öğrenci bilgileriyle otomatik dolan onam formları oluşturun, düzenleyin ve PDF olarak arşivleyin.'],
    ['icon' => 'phone', 'title' => 'Kuruma özel veli portalı', 'text' => 'Veliler kurumunuza özel bağlantıdan telefon numarasıyla çocuklarının güncel bilgilerine ulaşsın.'],
    ['icon' => 'shield', 'title' => 'Rol bazlı yetkilendirme', 'text' => 'Her kullanıcı yalnızca sorumlu olduğu menüleri ve işlemleri görsün; ekip görevleri netleşsin.'],
];

$audiences = [
    ['icon' => 'home', 'title' => 'Oyun evleri', 'text' => 'Günlük operasyonu, öğrenci akışını ve veli iletişimini tek merkezde toplayın.'],
    ['icon' => 'spark', 'title' => 'Çocuk etkinlik merkezleri', 'text' => 'Farklı yaş grupları ve programları anlaşılır bir takvimle yönetin.'],
    ['icon' => 'palette', 'title' => 'Atölye ve oyun grupları', 'text' => 'Kontenjan, paket ve katılım takibini dağınık listelerden kurtarın.'],
    ['icon' => 'building', 'title' => 'Büyüyen kurumlar', 'text' => 'Yetkileri ve kurum verilerini ayrıştıran yapıyla ekibinizi güvenle büyütün.'],
];

$faqs = [
    ['question' => 'Oyun Evleri Yönetim Sistemi kimler için?', 'answer' => 'Oyun evleri, çocuk etkinlik merkezleri, oyun grupları ve düzenli öğrenci, veli, program veya tahsilat takibi yapan çocuk odaklı kurumlar için geliştirilmiştir.'],
    ['question' => 'Programı kullanmak için kurulum gerekir mi?', 'answer' => 'Hayır. Sistem internet tarayıcısı üzerinden çalışır. Yetkili kullanıcılar bilgisayar, tablet veya telefondan giriş yapabilir.'],
    ['question' => 'Veliler sisteme nasıl ulaşır?', 'answer' => 'Her kuruma özel oluşturulan veli portalı bağlantısı paylaşılır. Veli, kayıtlı telefon numarasıyla doğrulama yaparak yalnızca kendi çocuğuna ait bilgilere ulaşır.'],
    ['question' => 'Onam formları hazırlanabilir mi?', 'answer' => 'Evet. Öğrenci ve kurum bilgileriyle otomatik doldurulan formlar düzenlenebilir, kurum logosuyla hazırlanabilir ve PDF çıktısı alınabilir.'],
    ['question' => 'Personel yetkileri ayrılabilir mi?', 'answer' => 'Evet. Kullanıcı tiplerine göre menü ve işlem görünürlüğü belirlenebilir; böylece ekip üyeleri yalnızca ihtiyaç duyduğu alanlara erişir.'],
    ['question' => 'Mevcut kayıtlarımızı sisteme taşıyabilir miyiz?', 'answer' => 'Kurumunuzun mevcut veri yapısı incelendikten sonra uygun aktarım ve başlangıç planı birlikte oluşturulabilir.'],
];

$year = date('Y');
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Oyun Evleri Yönetim Sistemi | Kurumunuzu Tek Ekrandan Yönetin</title>
    <meta name="description" content="Öğrenci, veli, grup, program, randevu, paket, tahsilat, SMS ve onam formu süreçlerini tek ekrandan yönetin. Oyun evleri için geliştirilen yeni nesil yönetim sistemi.">
    <meta name="theme-color" content="#f5fbff">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="https://www.oyunevleri.com/">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="tr_TR">
    <meta property="og:title" content="Oyun Evleri Yönetim Sistemi">
    <meta property="og:description" content="Oyun evinizi tek ekrandan, güvenle yönetin.">
    <meta property="og:url" content="https://www.oyunevleri.com/">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='18' fill='%2338aaf5'/%3E%3Cpath d='M18 31 32 19l14 12v16H18V31Z' fill='white'/%3E%3Cpath d='M27 47V35h10v12' fill='%2378cfb7'/%3E%3C/svg%3E">
    <style>
        :root {
            --ink: #17263b;
            --muted: #617188;
            --blue: #258fd5;
            --blue-dark: #116da9;
            --sky: #dff3ff;
            --mint: #78cfb7;
            --mint-dark: #12866d;
            --coral: #ef8f7d;
            --cream: #fff8ed;
            --line: #dceaf4;
            --surface: #ffffff;
            --page: #f7fbfe;
            --shadow: 0 24px 70px rgba(31, 88, 128, .12);
            --radius: 28px;
            --container: 1180px;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; scroll-padding-top: 100px; }
        body {
            margin: 0;
            color: var(--ink);
            background: var(--page);
            font-family: Inter, ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 16px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        body.menu-open { overflow: hidden; }
        a { color: inherit; text-decoration: none; }
        button, input { font: inherit; }
        button { color: inherit; }
        svg { display: block; }
        .container { width: min(calc(100% - 40px), var(--container)); margin-inline: auto; }
        .skip-link { position: fixed; top: -100px; left: 20px; z-index: 100; padding: 10px 16px; border-radius: 10px; background: var(--ink); color: white; }
        .skip-link:focus { top: 12px; }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 50;
            border-bottom: 1px solid rgba(220, 234, 244, .75);
            background: rgba(247, 251, 254, .88);
            backdrop-filter: blur(18px);
        }
        .nav-wrap { min-height: 78px; display: flex; align-items: center; justify-content: space-between; gap: 28px; }
        .nav-wrap > * { min-width: 0; }
        .brand { height: 68px; display: inline-flex; align-items: center; overflow: hidden; font-weight: 850; }
        .brand-logo { width: 190px; height: auto; display: block; }
        .brand-mark { width: 42px; height: 42px; border-radius: 14px; display: grid; place-items: center; color: white; background: linear-gradient(135deg, var(--blue), #70c7f3); box-shadow: 0 9px 22px rgba(37, 143, 213, .25); }
        .brand-mark svg { width: 25px; height: 25px; }
        .brand-text { font-size: 19px; line-height: 1.05; }
        .brand-text small { display: block; margin-top: 3px; color: var(--muted); font-size: 10px; font-weight: 750; letter-spacing: .13em; text-transform: uppercase; }
        .nav-links { display: flex; align-items: center; gap: 28px; color: #415269; font-size: 14px; font-weight: 700; }
        .nav-links a { transition: color .2s ease; }
        .nav-links a:hover { color: var(--blue-dark); }
        .nav-links .mobile-login { display: none; }
        .nav-actions { display: flex; align-items: center; gap: 10px; }
        .button { min-height: 48px; display: inline-flex; align-items: center; justify-content: center; gap: 9px; padding: 0 20px; border: 1px solid transparent; border-radius: 15px; cursor: pointer; font-weight: 800; transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
        .button:hover { transform: translateY(-2px); }
        .button-primary { color: white; background: linear-gradient(135deg, var(--blue), #4eb8ef); box-shadow: 0 13px 30px rgba(37, 143, 213, .24); }
        .button-primary:hover { box-shadow: 0 16px 34px rgba(37, 143, 213, .32); }
        .button-light { border-color: var(--line); background: rgba(255,255,255,.78); }
        .button-light:hover { border-color: #b9d8eb; }
        .button svg { width: 19px; height: 19px; }
        .nav-login { font-size: 14px; font-weight: 800; }
        .menu-toggle { width: 46px; height: 46px; display: none; border: 1px solid var(--line); border-radius: 14px; background: white; cursor: pointer; }
        .menu-toggle span { width: 20px; height: 2px; display: block; margin: 5px auto; border-radius: 2px; background: var(--ink); }

        .hero { position: relative; overflow: hidden; padding: 84px 0 72px; }
        .hero::before { content: ""; position: absolute; width: 680px; height: 680px; top: -250px; right: -120px; border-radius: 50%; background: radial-gradient(circle, rgba(112,199,243,.28), rgba(112,199,243,0) 68%); pointer-events: none; }
        .hero::after { content: ""; position: absolute; width: 520px; height: 520px; bottom: -300px; left: -220px; border-radius: 50%; background: radial-gradient(circle, rgba(120,207,183,.18), rgba(120,207,183,0) 68%); pointer-events: none; }
        .hero-grid { position: relative; z-index: 1; display: grid; grid-template-columns: .92fr 1.08fr; align-items: center; gap: 66px; }
        .eyebrow { display: inline-flex; align-items: center; gap: 9px; margin-bottom: 22px; padding: 8px 13px; border: 1px solid #cde8f7; border-radius: 999px; color: var(--blue-dark); background: rgba(232,247,255,.8); font-size: 12px; font-weight: 850; letter-spacing: .08em; text-transform: uppercase; }
        .eyebrow-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--mint); box-shadow: 0 0 0 5px rgba(120,207,183,.18); }
        h1, h2, h3, p { margin-top: 0; }
        h1 { max-width: 720px; margin-bottom: 24px; font-size: clamp(43px, 5.2vw, 72px); line-height: 1.04; letter-spacing: -.055em; }
        .text-gradient { color: var(--blue-dark); background: linear-gradient(100deg, var(--blue-dark), #37a8e7 52%, var(--mint-dark)); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-copy > p { max-width: 620px; margin-bottom: 30px; color: var(--muted); font-size: 18px; line-height: 1.75; }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 30px; }
        .hero-note { display: flex; flex-wrap: wrap; gap: 18px; color: #566a82; font-size: 13px; font-weight: 700; }
        .hero-note span { display: flex; align-items: center; gap: 7px; }
        .hero-note svg { width: 17px; height: 17px; color: var(--mint-dark); }

        .product-scene { position: relative; min-width: 0; padding: 26px 0 18px; }
        .product-scene::before { content: ""; position: absolute; inset: 2% 2% 0; border-radius: 50%; background: linear-gradient(135deg, rgba(103,188,239,.28), rgba(120,207,183,.22)); filter: blur(2px); transform: rotate(-8deg); }
        .dashboard { position: relative; overflow: hidden; min-height: 510px; border: 1px solid rgba(255,255,255,.92); border-radius: 30px; background: rgba(255,255,255,.92); box-shadow: 0 36px 90px rgba(31,88,128,.19); transform: perspective(1500px) rotateY(-4deg) rotateX(1.5deg); }
        .dash-top { height: 66px; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; border-bottom: 1px solid #e8f0f5; }
        .window-dots { display: flex; gap: 6px; }
        .window-dots i { width: 8px; height: 8px; border-radius: 50%; background: #d7e4ec; }
        .dash-search { width: 36%; height: 30px; border-radius: 10px; background: #f2f7fa; }
        .dash-avatar { width: 34px; height: 34px; border-radius: 11px; background: linear-gradient(135deg, var(--mint), #b9ebdc); }
        .dash-body { display: grid; grid-template-columns: 92px minmax(0, 1fr); min-height: 444px; }
        .dash-side { padding: 18px 13px; background: #f4faff; }
        .mini-logo { width: 38px; height: 38px; display: grid; place-items: center; margin: 0 auto 23px; border-radius: 12px; color: white; background: var(--blue); font-weight: 900; }
        .side-line { height: 34px; margin-bottom: 10px; border-radius: 9px; background: #e7f2f9; }
        .side-line.active { background: #d5efff; box-shadow: inset 3px 0 var(--blue); }
        .dash-main { min-width: 0; padding: 24px; }
        .dash-heading { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
        .dash-heading div:first-child { width: 42%; }
        .skeleton-title { height: 12px; margin-bottom: 8px; border-radius: 7px; background: #20314a; }
        .skeleton-sub { width: 68%; height: 7px; border-radius: 5px; background: #d5e0e8; }
        .dash-add { width: 84px; height: 33px; border-radius: 10px; background: linear-gradient(135deg, var(--blue), #70c7f3); }
        .metric-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 11px; margin-bottom: 16px; }
        .metric { min-width: 0; padding: 14px; border: 1px solid #e4edf3; border-radius: 15px; background: #fbfdff; }
        .metric-icon { width: 31px; height: 31px; margin-bottom: 14px; border-radius: 10px; background: #dff2fe; }
        .metric:nth-child(2) .metric-icon { background: #ddf5ed; }
        .metric:nth-child(3) .metric-icon { background: #fff0e8; }
        .metric strong { display: block; margin-bottom: 4px; font-size: 21px; line-height: 1; }
        .metric span { color: #8190a1; font-size: 9px; font-weight: 700; }
        .dash-panels { display: grid; grid-template-columns: 1.25fr .75fr; gap: 12px; }
        .schedule, .activity { min-width: 0; padding: 15px; border: 1px solid #e4edf3; border-radius: 17px; }
        .panel-label { margin-bottom: 13px; font-size: 10px; font-weight: 850; }
        .day-row { display: grid; grid-template-columns: 32px 1fr; align-items: center; gap: 8px; margin-bottom: 8px; }
        .day { padding: 6px 2px; border-radius: 8px; color: var(--blue-dark); background: #eaf7ff; font-size: 8px; text-align: center; font-weight: 850; }
        .lesson { height: 31px; display: flex; align-items: center; padding: 0 9px; border-radius: 8px; color: #3e566d; background: #f3f7fa; font-size: 8px; font-weight: 750; }
        .activity-ring { width: 94px; height: 94px; display: grid; place-items: center; margin: 11px auto 18px; border-radius: 50%; background: conic-gradient(var(--blue) 0 67%, #eaf1f5 67%); }
        .activity-ring::after { content: "Bu hafta"; width: 68px; height: 68px; display: grid; place-items: center; border-radius: 50%; background: white; color: #6c7c8e; font-size: 8px; font-weight: 800; }
        .activity-line { height: 8px; margin: 8px 0; border-radius: 6px; background: #edf3f6; }
        .floating-card { position: absolute; z-index: 3; border: 1px solid rgba(255,255,255,.9); border-radius: 18px; background: rgba(255,255,255,.94); box-shadow: 0 18px 45px rgba(28,79,116,.16); backdrop-filter: blur(10px); }
        .floating-attendance { right: -22px; bottom: 10px; width: 190px; padding: 15px; }
        .floating-attendance b { display: block; font-size: 12px; }
        .floating-attendance span { color: var(--muted); font-size: 10px; }
        .avatar-row { display: flex; margin-top: 11px; }
        .avatar-row i { width: 29px; height: 29px; margin-right: -7px; border: 3px solid white; border-radius: 50%; background: #abdff5; }
        .avatar-row i:nth-child(2) { background: #f5b9a9; }
        .avatar-row i:nth-child(3) { background: #a9dfcf; }
        .avatar-row i:nth-child(4) { display: grid; place-items: center; color: white; background: var(--blue); font-size: 9px; font-style: normal; font-weight: 800; }
        .floating-payment { left: -25px; top: 4px; display: flex; align-items: center; gap: 10px; padding: 13px 16px; }
        .payment-check { width: 35px; height: 35px; display: grid; place-items: center; border-radius: 12px; color: white; background: var(--mint); }
        .payment-check svg { width: 19px; height: 19px; }
        .floating-payment b { display: block; font-size: 11px; }
        .floating-payment span { color: var(--muted); font-size: 9px; }

        .trust-bar { padding: 18px 0 34px; }
        .trust-inner { display: grid; grid-template-columns: repeat(3, 1fr); border: 1px solid var(--line); border-radius: 22px; background: rgba(255,255,255,.7); }
        .trust-item { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 22px; color: #52657c; font-size: 14px; font-weight: 800; text-align: center; }
        .trust-item + .trust-item { border-left: 1px solid var(--line); }
        .trust-item svg { width: 22px; height: 22px; color: var(--blue); flex: 0 0 auto; }

        .meb-offer { padding: 20px 0 54px; }
        .meb-offer-card { position: relative; overflow: hidden; display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 25px; padding: 30px 34px; border: 1px solid #cce9df; border-radius: 27px; color: #173a37; background: linear-gradient(120deg, #e5f8f1, #f4fcf9 54%, #fff7e8); box-shadow: 0 20px 48px rgba(41, 122, 104, .1); }
        .meb-offer-card::after { content: "2 AY"; position: absolute; right: 205px; color: rgba(18, 134, 109, .055); font-size: 82px; font-weight: 950; line-height: 1; letter-spacing: -.07em; pointer-events: none; }
        .meb-offer-badge { width: 76px; height: 76px; display: grid; place-items: center; border-radius: 23px; color: white; background: linear-gradient(135deg, var(--mint-dark), var(--mint)); box-shadow: 0 14px 28px rgba(18, 134, 109, .22); }
        .meb-offer-badge svg { width: 36px; height: 36px; }
        .meb-offer-copy { position: relative; z-index: 1; }
        .meb-offer-label { display: block; margin-bottom: 4px; color: var(--mint-dark); font-size: 11px; font-weight: 900; letter-spacing: .11em; text-transform: uppercase; }
        .meb-offer-copy h2 { margin: 0 0 4px; font-size: 27px; letter-spacing: -.035em; }
        .meb-offer-copy h2 strong { color: var(--mint-dark); }
        .meb-offer-copy p { margin: 0; color: #58726f; font-size: 14px; }
        .meb-offer-actions { position: relative; z-index: 1; display: flex; gap: 10px; }
        .button-mint { color: white; background: linear-gradient(135deg, var(--mint-dark), #36ae91); box-shadow: 0 12px 26px rgba(18, 134, 109, .18); }

        .section { padding: 104px 0; }
        .section-white { background: white; }
        .section-soft { background: linear-gradient(180deg, #edf8ff 0%, #f8fcff 100%); }
        .section-head { max-width: 720px; margin: 0 auto 52px; text-align: center; }
        .section-kicker { display: block; margin-bottom: 12px; color: var(--blue-dark); font-size: 12px; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; }
        h2 { margin-bottom: 18px; font-size: clamp(34px, 4.5vw, 52px); line-height: 1.1; letter-spacing: -.045em; }
        .section-head p, .showcase-copy > p { color: var(--muted); font-size: 17px; }

        .audience-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 17px; }
        .audience-card { position: relative; overflow: hidden; min-height: 242px; padding: 27px; border: 1px solid var(--line); border-radius: 24px; background: white; transition: transform .25s ease, box-shadow .25s ease; }
        .audience-card:hover { transform: translateY(-6px); box-shadow: var(--shadow); }
        .audience-card::after { content: ""; position: absolute; width: 120px; height: 120px; right: -50px; bottom: -55px; border-radius: 50%; background: #eaf7ff; }
        .audience-card:nth-child(2)::after { background: #e8f8f2; }
        .audience-card:nth-child(3)::after { background: #fff0eb; }
        .audience-card:nth-child(4)::after { background: #f0eeff; }
        .icon-box { width: 49px; height: 49px; display: grid; place-items: center; margin-bottom: 42px; border-radius: 15px; color: var(--blue-dark); background: #e8f6ff; }
        .audience-card:nth-child(2) .icon-box { color: var(--mint-dark); background: #e6f7f1; }
        .audience-card:nth-child(3) .icon-box { color: #b95e4b; background: #fff0eb; }
        .audience-card:nth-child(4) .icon-box { color: #6956bd; background: #f0eeff; }
        .icon-box svg, .feature-icon svg { width: 24px; height: 24px; }
        .audience-card h3 { margin-bottom: 8px; font-size: 19px; letter-spacing: -.02em; }
        .audience-card p { margin-bottom: 0; color: var(--muted); font-size: 14px; }

        .feature-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 17px; }
        .feature-card { padding: 26px 24px; border: 1px solid #dbe9f3; border-radius: 22px; background: rgba(255,255,255,.88); transition: border-color .2s ease, transform .2s ease; }
        .feature-card:hover { transform: translateY(-4px); border-color: #a9d8f2; }
        .feature-icon { width: 44px; height: 44px; display: grid; place-items: center; margin-bottom: 22px; border-radius: 14px; color: white; background: linear-gradient(135deg, var(--blue), #64c1ef); }
        .feature-card:nth-child(2n) .feature-icon { background: linear-gradient(135deg, #43ae94, var(--mint)); }
        .feature-card:nth-child(3n) .feature-icon { background: linear-gradient(135deg, #e87d69, #f6b19f); }
        .feature-card h3 { margin-bottom: 9px; font-size: 18px; line-height: 1.3; }
        .feature-card p { margin: 0; color: var(--muted); font-size: 14px; }

        .showcase { display: grid; grid-template-columns: 1fr 1fr; align-items: center; gap: 78px; }
        .showcase + .showcase { margin-top: 120px; }
        .showcase.reverse .showcase-visual { order: 2; }
        .showcase.reverse .showcase-copy { order: 1; }
        .showcase-copy h2 { font-size: clamp(33px, 4vw, 48px); }
        .check-list { display: grid; gap: 14px; margin: 27px 0 0; padding: 0; list-style: none; }
        .check-list li { display: flex; align-items: flex-start; gap: 12px; color: #40546d; font-weight: 700; }
        .check-list .check { width: 25px; height: 25px; display: grid; place-items: center; flex: 0 0 auto; border-radius: 8px; color: var(--mint-dark); background: #e2f7f0; }
        .check svg { width: 15px; height: 15px; }
        .showcase-visual { position: relative; min-width: 0; }
        .visual-shell { overflow: hidden; padding: 26px; border: 1px solid #dbeaf4; border-radius: 30px; background: white; box-shadow: var(--shadow); }
        .program-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; }
        .program-head b { font-size: 17px; }
        .week-arrows { display: flex; gap: 7px; }
        .week-arrows i { width: 30px; height: 30px; border-radius: 9px; background: #eef6fb; }
        .calendar-board { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; }
        .calendar-col { min-width: 0; min-height: 265px; padding: 8px; border-radius: 13px; background: #f7fafc; }
        .calendar-col strong { display: block; margin-bottom: 12px; color: #6a7a8c; font-size: 9px; text-align: center; text-transform: uppercase; }
        .event { margin-bottom: 8px; padding: 9px 7px; border-left: 3px solid var(--blue); border-radius: 8px; color: #36516a; background: #e7f5ff; font-size: 8px; line-height: 1.4; font-weight: 800; }
        .event small { display: block; color: #73869a; font-size: 7px; font-weight: 700; }
        .event.mint { border-left-color: var(--mint-dark); background: #e7f8f2; }
        .event.coral { border-left-color: var(--coral); background: #fff0ec; }
        .form-preview { position: relative; padding: 22px; border: 1px solid #dfeaf2; border-radius: 20px; background: #fbfdff; }
        .form-logo { width: 42px; height: 42px; display: grid; place-items: center; margin: 0 auto 12px; border-radius: 14px; color: white; background: linear-gradient(135deg, var(--blue), var(--mint)); }
        .form-logo svg { width: 24px; height: 24px; }
        .form-title { margin-bottom: 20px; text-align: center; }
        .form-title b { display: block; font-size: 13px; }
        .form-title span { color: #8290a0; font-size: 8px; }
        .form-section { margin-bottom: 12px; padding: 13px; border-radius: 11px; background: white; }
        .form-section b { display: block; margin-bottom: 9px; color: #3f5268; font-size: 9px; }
        .form-lines { display: grid; grid-template-columns: 1fr 1fr; gap: 7px; }
        .form-lines i { height: 24px; border: 1px solid #e4edf3; border-radius: 7px; background: #f9fbfc; }
        .pdf-badge { position: absolute; top: -18px; right: -16px; width: 84px; height: 84px; display: grid; place-items: center; border: 6px solid white; border-radius: 50%; color: white; background: linear-gradient(135deg, #e56d58, #f3a08f); box-shadow: 0 13px 28px rgba(216,101,80,.25); font-size: 13px; font-weight: 900; }

        .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; counter-reset: step; }
        .step { position: relative; padding: 32px; border: 1px solid var(--line); border-radius: 24px; background: white; counter-increment: step; }
        .step::before { content: "0" counter(step); width: 48px; height: 48px; display: grid; place-items: center; margin-bottom: 42px; border-radius: 15px; color: white; background: var(--ink); font-size: 14px; font-weight: 900; }
        .step:not(:last-child)::after { content: ""; position: absolute; top: 55px; left: calc(100% - 2px); width: 24px; border-top: 2px dashed #b9d9ea; }
        .step h3 { margin-bottom: 9px; font-size: 21px; }
        .step p { margin: 0; color: var(--muted); font-size: 14px; }

        .security-card { position: relative; overflow: hidden; display: grid; grid-template-columns: .95fr 1.05fr; gap: 60px; align-items: center; padding: 62px; border-radius: 34px; color: white; background: linear-gradient(125deg, #142a43, #174d6b 58%, #176f75); box-shadow: 0 30px 80px rgba(18,53,77,.2); }
        .security-card::after { content: ""; position: absolute; width: 380px; height: 380px; right: -160px; top: -180px; border: 1px solid rgba(255,255,255,.17); border-radius: 50%; box-shadow: 0 0 0 45px rgba(255,255,255,.025), 0 0 0 90px rgba(255,255,255,.02); }
        .security-card .section-kicker { color: #8ee3ce; }
        .security-card h2 { font-size: clamp(32px, 4vw, 49px); }
        .security-card p { margin: 0; color: #c3d7e2; }
        .security-grid { position: relative; z-index: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .security-item { padding: 22px; border: 1px solid rgba(255,255,255,.13); border-radius: 18px; background: rgba(255,255,255,.07); backdrop-filter: blur(10px); }
        .security-item svg { width: 24px; height: 24px; margin-bottom: 26px; color: #8ee3ce; }
        .security-item b { display: block; margin-bottom: 5px; font-size: 14px; }
        .security-item span { display: block; color: #b7ccd8; font-size: 12px; line-height: 1.5; }

        .faq-list { max-width: 850px; margin: 0 auto; border-top: 1px solid var(--line); }
        .faq-item { border-bottom: 1px solid var(--line); }
        .faq-question { width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 24px; padding: 25px 4px; border: 0; background: transparent; cursor: pointer; text-align: left; font-size: 17px; font-weight: 850; }
        .faq-plus { width: 30px; height: 30px; position: relative; flex: 0 0 auto; border-radius: 10px; background: #eaf6fd; }
        .faq-plus::before, .faq-plus::after { content: ""; position: absolute; top: 14px; left: 9px; width: 12px; height: 2px; border-radius: 2px; background: var(--blue-dark); transition: transform .2s ease; }
        .faq-plus::after { transform: rotate(90deg); }
        .faq-question[aria-expanded="true"] .faq-plus::after { transform: rotate(0); }
        .faq-answer { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .25s ease; }
        .faq-answer > div { overflow: hidden; }
        .faq-answer p { max-width: 740px; margin: 0; padding: 0 4px 25px; color: var(--muted); }
        .faq-question[aria-expanded="true"] + .faq-answer { grid-template-rows: 1fr; }

        .cta { padding: 30px 0 95px; }
        .cta-card { position: relative; overflow: hidden; padding: 72px 40px; border-radius: 36px; text-align: center; background: linear-gradient(135deg, #dff4ff, #edfaff 50%, #e5f8f1); }
        .cta-card::before, .cta-card::after { content: ""; position: absolute; border-radius: 50%; border: 1px solid rgba(37,143,213,.18); }
        .cta-card::before { width: 260px; height: 260px; left: -120px; top: -130px; box-shadow: 0 0 0 40px rgba(37,143,213,.035); }
        .cta-card::after { width: 210px; height: 210px; right: -95px; bottom: -120px; box-shadow: 0 0 0 38px rgba(120,207,183,.06); }
        .cta-card h2 { position: relative; z-index: 1; max-width: 760px; margin-inline: auto; }
        .cta-card p { position: relative; z-index: 1; max-width: 620px; margin: 0 auto 28px; color: var(--muted); font-size: 17px; }
        .cta-actions { position: relative; z-index: 1; display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; }

        .site-footer { padding: 60px 0 26px; color: #c6d2dc; background: #112337; }
        .footer-grid { display: grid; grid-template-columns: 1.4fr .7fr .8fr; gap: 70px; padding-bottom: 48px; }
        .site-footer .brand { width: 218px; padding: 0 14px; border-radius: 17px; color: white; background: white; }
        .site-footer .brand-logo { width: 190px; }
        .site-footer .brand-text small { color: #8fa5b6; }
        .footer-about p { max-width: 420px; margin: 20px 0 0; color: #96aabb; font-size: 14px; }
        .footer-col h3 { margin-bottom: 18px; color: white; font-size: 14px; }
        .footer-links { display: grid; gap: 11px; color: #aebfcb; font-size: 14px; }
        .footer-links a:hover { color: white; }
        .footer-bottom { display: flex; justify-content: space-between; gap: 20px; padding-top: 24px; border-top: 1px solid rgba(255,255,255,.1); color: #8299aa; font-size: 12px; }

        .reveal { opacity: 0; transform: translateY(18px); transition: opacity .65s ease, transform .65s ease; }
        .reveal.is-visible { opacity: 1; transform: none; }

        @media (max-width: 1040px) {
            .nav-links { display: none; }
            .hero-grid { grid-template-columns: 1fr; gap: 55px; }
            .hero-copy { max-width: 760px; text-align: center; margin-inline: auto; }
            .hero-copy > p { margin-inline: auto; }
            .hero-actions, .hero-note { justify-content: center; }
            .product-scene { width: min(760px, 100%); margin-inline: auto; }
            .audience-grid, .feature-grid { grid-template-columns: repeat(2, 1fr); }
            .showcase { gap: 45px; }
            .security-card { grid-template-columns: 1fr; }
        }

        @media (max-width: 760px) {
            body { overflow-x: hidden; }
            .container { width: min(calc(100% - 28px), var(--container)); }
            .site-header { background: rgba(247,251,254,.96); }
            .nav-wrap { min-height: 68px; }
            .brand { height: 58px; flex: 1 1 auto; }
            .brand-logo { width: 154px; }
            .brand-mark { width: 38px; height: 38px; flex: 0 0 auto; }
            .brand-text { font-size: 17px; }
            .brand-text small { font-size: 8px; }
            .nav-actions .nav-login, .nav-actions > .button { display: none; }
            .menu-toggle { display: block; flex: 0 0 auto; }
            .nav-links { position: fixed; inset: 68px 0 auto; max-height: calc(100vh - 68px); display: grid; gap: 0; padding: 18px 22px 28px; border-bottom: 1px solid var(--line); background: white; box-shadow: 0 24px 50px rgba(22,48,70,.12); transform: translateY(-130%); opacity: 0; visibility: hidden; transition: transform .25s ease, opacity .25s ease, visibility .25s ease; }
            .nav-links.is-open { transform: translateY(0); opacity: 1; visibility: visible; }
            .nav-links a { padding: 14px 6px; border-bottom: 1px solid #edf3f7; font-size: 16px; }
            .nav-links .mobile-login { display: inline-flex; margin-top: 14px; border: 0; color: white; background: var(--blue); }
            .hero { padding: 58px 0 52px; }
            .hero-copy { min-width: 0; overflow: hidden; }
            h1 { max-width: 100%; font-size: clamp(37px, 10.5vw, 42px); overflow-wrap: normal; }
            .hero-line { display: block; }
            .text-gradient { -webkit-box-decoration-break: clone; box-decoration-break: clone; }
            .hero-copy > p { font-size: 16px; line-height: 1.65; }
            .hero-actions { display: grid; }
            .hero-actions .button { width: 100%; }
            .hero-note { align-items: center; flex-direction: column; gap: 9px; }
            .dashboard { min-height: 400px; transform: none; }
            .dash-top { height: 52px; }
            .dash-body { grid-template-columns: 58px minmax(0, 1fr); min-height: 348px; }
            .dash-side { padding: 14px 8px; }
            .mini-logo { width: 31px; height: 31px; margin-bottom: 16px; font-size: 12px; }
            .side-line { height: 26px; }
            .dash-main { padding: 15px 12px; }
            .metric { padding: 10px; }
            .metric-icon { width: 24px; height: 24px; margin-bottom: 10px; }
            .metric strong { font-size: 16px; }
            .dash-panels { grid-template-columns: 1fr; }
            .activity { display: none; }
            .floating-payment { left: -4px; }
            .floating-attendance { right: -3px; width: 160px; }
            .trust-inner { grid-template-columns: 1fr; }
            .trust-item { justify-content: flex-start; padding: 17px 22px; text-align: left; }
            .trust-item + .trust-item { border-top: 1px solid var(--line); border-left: 0; }
            .meb-offer { padding-bottom: 32px; }
            .meb-offer-card { grid-template-columns: 1fr; gap: 18px; padding: 27px 22px; text-align: center; }
            .meb-offer-card::after { right: -12px; top: 18px; font-size: 65px; }
            .meb-offer-badge { width: 65px; height: 65px; margin-inline: auto; }
            .meb-offer-copy h2 { font-size: 24px; }
            .meb-offer-actions { display: grid; }
            .meb-offer-actions .button { width: 100%; }
            .section { padding: 76px 0; }
            .section-head { margin-bottom: 36px; }
            .section-head p, .showcase-copy > p { font-size: 15px; }
            .audience-grid, .feature-grid, .steps { grid-template-columns: 1fr; }
            .audience-card { min-height: auto; }
            .icon-box { margin-bottom: 25px; }
            .showcase, .showcase.reverse { grid-template-columns: 1fr; gap: 36px; }
            .showcase.reverse .showcase-visual, .showcase.reverse .showcase-copy { order: initial; }
            .showcase + .showcase { margin-top: 85px; }
            .visual-shell { padding: 15px; border-radius: 23px; }
            .calendar-board { gap: 4px; overflow: hidden; }
            .calendar-col { min-height: 220px; padding: 5px; }
            .event { padding: 7px 4px; font-size: 7px; }
            .step:not(:last-child)::after { display: none; }
            .step::before { margin-bottom: 25px; }
            .security-card { gap: 38px; padding: 38px 22px; border-radius: 27px; }
            .security-grid { grid-template-columns: 1fr; }
            .security-item { display: grid; grid-template-columns: 34px 1fr; column-gap: 10px; }
            .security-item svg { grid-row: span 2; margin: 0; }
            .faq-question { padding: 21px 2px; font-size: 15px; }
            .cta-card { padding: 52px 20px; border-radius: 28px; }
            .cta-actions { display: grid; }
            .cta-actions .button { width: 100%; }
            .footer-grid { grid-template-columns: 1fr; gap: 38px; }
            .footer-bottom { flex-direction: column; }
        }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; }
            .reveal { opacity: 1; transform: none; }
        }
    </style>
</head>
<body>
<a class="skip-link" href="#icerik">İçeriğe geç</a>

<svg width="0" height="0" aria-hidden="true" focusable="false" style="position:absolute">
    <symbol id="icon-home" viewBox="0 0 24 24"><path d="M3 11.5 12 4l9 7.5M5.5 10v10h13V10M9 20v-6h6v6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    <symbol id="icon-users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M3.5 19c.4-4 2.2-6 5.5-6s5.1 2 5.5 6M15 5.5a3 3 0 0 1 0 5.8M16 13c2.8.3 4.2 2.2 4.5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></symbol>
    <symbol id="icon-calendar" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M7 3v4M17 3v4M3 10h18M7 14h3M14 14h3M7 17h3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></symbol>
    <symbol id="icon-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 7v5l3.5 2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></symbol>
    <symbol id="icon-wallet" viewBox="0 0 24 24"><path d="M4 6.5A2.5 2.5 0 0 1 6.5 4H19v16H6.5A2.5 2.5 0 0 1 4 17.5v-11Z" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M15 10h6v5h-6a2.5 2.5 0 0 1 0-5Z" fill="none" stroke="currentColor" stroke-width="1.8"/></symbol>
    <symbol id="icon-message" viewBox="0 0 24 24"><path d="M4 5.5h16v11H9l-5 4v-15Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M8 9h8M8 13h5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></symbol>
    <symbol id="icon-document" viewBox="0 0 24 24"><path d="M6 3h8l4 4v14H6V3Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M14 3v5h4M9 12h6M9 16h6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></symbol>
    <symbol id="icon-phone" viewBox="0 0 24 24"><rect x="6.5" y="2.5" width="11" height="19" rx="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M10 5h4M11 18.5h2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></symbol>
    <symbol id="icon-shield" viewBox="0 0 24 24"><path d="M12 3 20 6v5c0 5.2-3.1 8.3-8 10-4.9-1.7-8-4.8-8-10V6l8-3Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="m8.5 12 2.2 2.2 4.8-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    <symbol id="icon-spark" viewBox="0 0 24 24"><path d="m12 2 1.5 5.2L18 9l-4.5 1.8L12 16l-1.5-5.2L6 9l4.5-1.8L12 2ZM19 15l.7 2.3L22 18l-2.3.7L19 21l-.7-2.3L16 18l2.3-.7L19 15ZM5 14l.6 1.9 1.9.6-1.9.6L5 19l-.6-1.9-1.9-.6 1.9-.6L5 14Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></symbol>
    <symbol id="icon-palette" viewBox="0 0 24 24"><path d="M12 3a9 9 0 1 0 0 18h1.5a2 2 0 0 0 0-4H12a2 2 0 0 1 0-4h3a6 6 0 0 0 6-6c0-2.2-3.6-4-9-4Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="7.5" cy="9" r="1" fill="currentColor"/><circle cx="10.5" cy="6.5" r="1" fill="currentColor"/><circle cx="15" cy="7" r="1" fill="currentColor"/></symbol>
    <symbol id="icon-building" viewBox="0 0 24 24"><path d="M4 21V5h10v16M14 10h6v11M2 21h20M7 8h4M7 12h4M7 16h4M17 14h1M17 17h1" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    <symbol id="icon-check" viewBox="0 0 24 24"><path d="m5 12.5 4.2 4L19 7" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    <symbol id="icon-arrow" viewBox="0 0 24 24"><path d="M5 12h14M14 7l5 5-5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    <symbol id="icon-lock" viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="11" rx="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></symbol>
    <symbol id="icon-cloud" viewBox="0 0 24 24"><path d="M7 19h11a4 4 0 0 0 .3-8A6.5 6.5 0 0 0 5.7 9.3 5 5 0 0 0 7 19Z" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m9 14 3-3 3 3M12 11v6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
</svg>

<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="#" aria-label="Oyun Evleri ana sayfa"><img class="brand-logo" src="/oyun_Evleri_son_logo.png" alt="Oyun Evleri Yönetim Yazılımı"></a>
        <nav class="nav-links" id="ana-menu" aria-label="Ana menü">
            <a href="#kimler-icin">Kimler için?</a>
            <a href="#ozellikler">Özellikler</a>
            <a href="#veli-deneyimi">Veli deneyimi</a>
            <a href="#guvenlik">Güvenli yapı</a>
            <a href="#sss">Sık sorulanlar</a>
            <a class="button mobile-login" href="https://app.oyunevleri.com/giris">Sisteme giriş</a>
        </nav>
        <div class="nav-actions">
            <a class="nav-login" href="https://app.oyunevleri.com/giris">Giriş yap</a>
            <a class="button button-primary" href="#tanitim">Tanıtım iste</a>
            <button class="menu-toggle" type="button" aria-label="Menüyü aç" aria-controls="ana-menu" aria-expanded="false"><span></span><span></span><span></span></button>
        </div>
    </div>
</header>

<main id="icerik">
    <section class="hero">
        <div class="container hero-grid">
            <div class="hero-copy reveal">
                <span class="eyebrow"><i class="eyebrow-dot"></i> Oyun evleri için geliştirildi</span>
                <h1><span class="hero-line">Oyun evinizi</span> <span class="text-gradient hero-line">tek ekrandan,</span> <span class="hero-line">güvenle yönetin.</span></h1>
                <p>Öğrenciden veli iletişimine, programdan tahsilata kadar tüm operasyonunuzu sadeleştirin. Ekibinize zaman, velilerinize güçlü bir deneyim kazandırın.</p>
                <div class="hero-actions">
                    <a class="button button-primary" href="#tanitim">Tanıtım görüşmesi iste <svg aria-hidden="true"><use href="#icon-arrow"/></svg></a>
                    <a class="button button-light" href="#ozellikler">Özellikleri keşfet</a>
                </div>
                <div class="hero-note">
                    <span><svg aria-hidden="true"><use href="#icon-check"/></svg> Tarayıcıdan kolay erişim</span>
                    <span><svg aria-hidden="true"><use href="#icon-check"/></svg> Kuruma özel yapı</span>
                    <span><svg aria-hidden="true"><use href="#icon-check"/></svg> Mobil uyumlu kullanım</span>
                </div>
            </div>

            <div class="product-scene reveal" aria-label="Oyun Evleri Yönetim Sistemi panel görünümü">
                <div class="floating-card floating-payment">
                    <span class="payment-check"><svg aria-hidden="true"><use href="#icon-check"/></svg></span>
                    <span><b>Tahsilat kaydedildi</b><span>Paket bakiyesi güncellendi</span></span>
                </div>
                <div class="dashboard">
                    <div class="dash-top"><span class="window-dots"><i></i><i></i><i></i></span><span class="dash-search"></span><span class="dash-avatar"></span></div>
                    <div class="dash-body">
                        <aside class="dash-side"><div class="mini-logo">OE</div><div class="side-line active"></div><div class="side-line"></div><div class="side-line"></div><div class="side-line"></div><div class="side-line"></div></aside>
                        <div class="dash-main">
                            <div class="dash-heading"><div><div class="skeleton-title"></div><div class="skeleton-sub"></div></div><div class="dash-add"></div></div>
                            <div class="metric-grid">
                                <div class="metric"><div class="metric-icon"></div><strong>128</strong><span>Aktif öğrenci</span></div>
                                <div class="metric"><div class="metric-icon"></div><strong>12</strong><span>Bugünkü program</span></div>
                                <div class="metric"><div class="metric-icon"></div><strong>8</strong><span>Bekleyen işlem</span></div>
                            </div>
                            <div class="dash-panels">
                                <div class="schedule"><div class="panel-label">Bugünün programı</div><div class="day-row"><span class="day">09:30</span><span class="lesson">Minikler Oyun Grubu</span></div><div class="day-row"><span class="day">11:00</span><span class="lesson">Duyu Atölyesi</span></div><div class="day-row"><span class="day">13:30</span><span class="lesson">Yaratıcı Drama</span></div><div class="day-row"><span class="day">15:00</span><span class="lesson">Serbest Oyun</span></div></div>
                                <div class="activity"><div class="panel-label">Doluluk</div><div class="activity-ring"></div><div class="activity-line"></div><div class="activity-line"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="floating-card floating-attendance"><b>Bugün kurumda</b><span>Öğrenci akışı güncel</span><div class="avatar-row"><i></i><i></i><i></i><i>+9</i></div></div>
            </div>
        </div>
    </section>

    <div class="trust-bar">
        <div class="container trust-inner reveal">
            <div class="trust-item"><svg aria-hidden="true"><use href="#icon-cloud"/></svg> Kurulum gerektirmeden kullanın</div>
            <div class="trust-item"><svg aria-hidden="true"><use href="#icon-phone"/></svg> Telefon, tablet ve bilgisayardan erişin</div>
            <div class="trust-item"><svg aria-hidden="true"><use href="#icon-shield"/></svg> Kurum ve rol bazlı yapıyla yönetin</div>
        </div>
    </div>

    <section class="meb-offer" aria-labelledby="meb-kampanya-baslik">
        <div class="container">
            <div class="meb-offer-card reveal">
                <span class="meb-offer-badge"><svg aria-hidden="true"><use href="#icon-building"/></svg></span>
                <div class="meb-offer-copy"><span class="meb-offer-label">MEB'e bağlı kurumlara özel</span><h2 id="meb-kampanya-baslik"><strong>2 ay ücretsiz</strong> kullanım hakkı</h2><p>MEB'e bağlı oyun evleri ve öğrenci etkinlik merkezleri sistemi iki ay boyunca ücretsiz deneyebilir.</p></div>
                <div class="meb-offer-actions"><a class="button button-mint" href="mailto:info@oyunevleri.com?subject=MEB%27e%20Ba%C4%9Fl%C4%B1%20Kurum%20-%202%20Ay%20%C3%9Ccretsiz%20Kullan%C4%B1m">Ücretsiz kullanıma başla</a><a class="button button-light" href="/mebe-bagli-oyun-evleri/">MEB kurum rehberi</a></div>
            </div>
        </div>
    </section>

    <section class="section section-white" id="kimler-icin">
        <div class="container">
            <div class="section-head reveal"><span class="section-kicker">İşinize uyum sağlar</span><h2>Çocuklarla büyüyen her kurum için</h2><p>Günlük işlerinizi karmaşıklaştırmadan, ihtiyaç duyduğunuz tüm operasyonu bir araya getiren esnek bir çalışma alanı.</p></div>
            <div class="audience-grid">
                <?php foreach ($audiences as $audience): ?>
                    <article class="audience-card reveal"><div class="icon-box"><svg aria-hidden="true"><use href="#icon-<?= e($audience['icon']) ?>"/></svg></div><h3><?= e($audience['title']) ?></h3><p><?= e($audience['text']) ?></p></article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section section-soft" id="ozellikler">
        <div class="container">
            <div class="section-head reveal"><span class="section-kicker">Tek sistem, bütün süreçler</span><h2>Dağınık tabloları geride bırakın</h2><p>Ekibinizin gün boyunca ihtiyaç duyduğu bilgiler, birbiriyle bağlantılı ve ulaşılması kolay ekranlarda.</p></div>
            <div class="feature-grid">
                <?php foreach ($features as $feature): ?>
                    <article class="feature-card reveal"><div class="feature-icon"><svg aria-hidden="true"><use href="#icon-<?= e($feature['icon']) ?>"/></svg></div><h3><?= e($feature['title']) ?></h3><p><?= e($feature['text']) ?></p></article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section section-white">
        <div class="container">
            <div class="showcase">
                <div class="showcase-visual reveal">
                    <div class="visual-shell"><div class="program-head"><b>Haftalık Program</b><span class="week-arrows"><i></i><i></i></span></div><div class="calendar-board">
                        <div class="calendar-col"><strong>Pzt</strong><div class="event">Oyun Grubu<small>09:30 · 8 öğrenci</small></div><div class="event mint">Duyu Atölyesi<small>13:00 · 6 öğrenci</small></div></div>
                        <div class="calendar-col"><strong>Sal</strong><div class="event coral">Drama<small>10:00 · 9 öğrenci</small></div><div class="event">Serbest Oyun<small>14:30 · 7 öğrenci</small></div></div>
                        <div class="calendar-col"><strong>Çar</strong><div class="event mint">Oyun Grubu<small>09:30 · 8 öğrenci</small></div><div class="event coral">Müzik<small>15:00 · 6 öğrenci</small></div></div>
                        <div class="calendar-col"><strong>Per</strong><div class="event">Duyu Atölyesi<small>11:00 · 7 öğrenci</small></div><div class="event mint">Drama<small>13:30 · 8 öğrenci</small></div></div>
                        <div class="calendar-col"><strong>Cum</strong><div class="event coral">Oyun Grubu<small>10:30 · 9 öğrenci</small></div><div class="event">Sanat<small>15:30 · 6 öğrenci</small></div></div>
                    </div></div>
                </div>
                <div class="showcase-copy reveal"><span class="section-kicker">Planlama</span><h2>Programınız net, kontenjanınız kontrol altında</h2><p>Grupları, eğitmenleri ve öğrenci katılımlarını haftalık akışta görün. Yoğun günleri önceden fark edin, boş kapasiteyi değerlendirin.</p><ul class="check-list"><li><span class="check"><svg aria-hidden="true"><use href="#icon-check"/></svg></span>Grup kapasitesi ve doluluk görünümü</li><li><span class="check"><svg aria-hidden="true"><use href="#icon-check"/></svg></span>Öğrenci, program ve paket bağlantısı</li><li><span class="check"><svg aria-hidden="true"><use href="#icon-check"/></svg></span>Randevu ve telafi süreçlerinde bütünlük</li></ul></div>
            </div>

            <div class="showcase reverse" id="veli-deneyimi">
                <div class="showcase-visual reveal"><div class="visual-shell"><div class="form-preview"><div class="pdf-badge">PDF</div><div class="form-logo"><svg aria-hidden="true"><use href="#icon-home"/></svg></div><div class="form-title"><b>Görsel İçerik Kullanım Onam Formu</b><span>Kurum logosu ve öğrenci bilgileriyle hazırlandı</span></div><div class="form-section"><b>Öğrenci ve Veli Bilgileri</b><div class="form-lines"><i></i><i></i><i></i><i></i></div></div><div class="form-section"><b>Onam Kapsamı</b><div class="form-lines"><i></i><i></i></div></div><div class="form-section"><b>İmza Bilgileri</b><div class="form-lines"><i></i><i></i></div></div></div></div></div>
                <div class="showcase-copy reveal"><span class="section-kicker">Veli deneyimi</span><h2>Veli iletişiminde güven veren bir düzen</h2><p>Kuruma özel veli portalı, düzenli SMS akışı ve öğrenci profilinden oluşturulan belgelerle iletişimi daha şeffaf hale getirin.</p><ul class="check-list"><li><span class="check"><svg aria-hidden="true"><use href="#icon-check"/></svg></span>Telefon numarasıyla kuruma özel veli erişimi</li><li><span class="check"><svg aria-hidden="true"><use href="#icon-check"/></svg></span>Otomatik dolan, düzenlenebilir onam formları</li><li><span class="check"><svg aria-hidden="true"><use href="#icon-check"/></svg></span>Kurum logosuyla profesyonel PDF çıktıları</li></ul></div>
            </div>
        </div>
    </section>

    <section class="section section-soft">
        <div class="container">
            <div class="section-head reveal"><span class="section-kicker">Kolay başlangıç</span><h2>Üç adımda daha düzenli bir kurum</h2><p>İhtiyacınızı belirleyip ekibinizi sisteme hazırlayarak günlük iş akışınıza hızlıca geçin.</p></div>
            <div class="steps"><article class="step reveal"><h3>Kurumunuzu tanıyalım</h3><p>Çalışma biçiminizi, gruplarınızı ve öncelikli ihtiyaçlarınızı birlikte değerlendirelim.</p></article><article class="step reveal"><h3>Yapınızı oluşturalım</h3><p>Kullanıcı rolleri, gruplar, program ve başlangıç verileri kurum düzeninize göre hazırlansın.</p></article><article class="step reveal"><h3>Tek ekrandan yönetin</h3><p>Ekibinizle kullanmaya başlayın; günlük kayıtları ve operasyonu aynı sistemde sürdürün.</p></article></div>
        </div>
    </section>

    <section class="section section-white" id="guvenlik">
        <div class="container">
            <div class="security-card reveal">
                <div><span class="section-kicker">Kontrollü erişim</span><h2>Her kullanıcıya ihtiyacı kadar alan</h2><p>Kurum verilerini birbirinden ayıran ve personel rollerini tanımlamanıza imkân veren yapı sayesinde operasyonu daha kontrollü yürütün.</p></div>
                <div class="security-grid"><div class="security-item"><svg aria-hidden="true"><use href="#icon-building"/></svg><b>Kurum bazlı ayrım</b><span>Her kurum kendi çalışma alanında ilerler.</span></div><div class="security-item"><svg aria-hidden="true"><use href="#icon-users"/></svg><b>Rol bazlı yetki</b><span>Menü görünürlüğünü kullanıcı tipine göre belirleyin.</span></div><div class="security-item"><svg aria-hidden="true"><use href="#icon-lock"/></svg><b>Kontrollü oturum</b><span>Yetkili kullanıcı girişleriyle erişimi sınırlandırın.</span></div><div class="security-item"><svg aria-hidden="true"><use href="#icon-document"/></svg><b>Düzenli kayıt yapısı</b><span>İlgili işlemleri öğrenci ve kurum kayıtlarıyla eşleştirin.</span></div></div>
            </div>
        </div>
    </section>

    <section class="section section-white" id="sss">
        <div class="container">
            <div class="section-head reveal"><span class="section-kicker">Merak ettikleriniz</span><h2>Sık sorulan sorular</h2><p>Oyun Evleri Yönetim Sistemi hakkında en çok sorulan soruların kısa yanıtları.</p></div>
            <div class="faq-list reveal">
                <?php foreach ($faqs as $index => $faq): ?>
                    <div class="faq-item"><button class="faq-question" type="button" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="faq-<?= $index ?>"><span><?= e($faq['question']) ?></span><i class="faq-plus"></i></button><div class="faq-answer" id="faq-<?= $index ?>"><div><p><?= e($faq['answer']) ?></p></div></div></div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="cta" id="tanitim">
        <div class="container">
            <div class="cta-card reveal"><span class="section-kicker">Tanışmaya hazır mısınız?</span><h2>İşinizi büyütmeye, operasyonunuzu sadeleştirerek başlayın.</h2><p>Oyun evinizin ihtiyaçlarını konuşalım; sistemin günlük iş akışınıza nasıl uyum sağlayacağını birlikte görelim.</p><div class="cta-actions"><a class="button button-primary" href="mailto:info@oyunevleri.com?subject=Oyun%20Evleri%20Y%C3%B6netim%20Sistemi%20Tan%C4%B1t%C4%B1m%20Talebi">Tanıtım görüşmesi iste <svg aria-hidden="true"><use href="#icon-arrow"/></svg></a><a class="button button-light" href="https://app.oyunevleri.com/giris">Mevcut kullanıcı girişi</a></div></div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid"><div class="footer-about"><a class="brand" href="#" aria-label="Oyun Evleri ana sayfa"><img class="brand-logo" src="/oyun_Evleri_son_logo.png" alt="Oyun Evleri Yönetim Yazılımı"></a><p>Oyun evleri ve çocuk etkinlik merkezlerinin öğrenci, veli, program ve finans süreçlerini tek ekranda buluşturan yönetim yazılımı.</p></div><div class="footer-col"><h3>Ürünü keşfet</h3><nav class="footer-links" aria-label="Alt menü"><a href="#kimler-icin">Kimler için?</a><a href="#ozellikler">Özellikler</a><a href="#veli-deneyimi">Veli deneyimi</a><a href="/mebe-bagli-oyun-evleri/">MEB kurum rehberi</a><a href="#sss">Sık sorulanlar</a></nav></div><div class="footer-col"><h3>Hızlı erişim</h3><nav class="footer-links" aria-label="Hızlı erişim"><a href="https://app.oyunevleri.com/giris">Sisteme giriş</a><a href="#tanitim">Tanıtım talebi</a><a href="mailto:info@oyunevleri.com">info@oyunevleri.com</a></nav></div></div>
        <div class="footer-bottom"><span>© <?= e($year) ?> Oyun Evleri Yönetim Sistemi. Tüm hakları saklıdır.</span><span>Çocuk odaklı kurumlar için özenle geliştirildi.</span></div>
    </div>
</footer>

<script>
    (() => {
        const toggle = document.querySelector('.menu-toggle');
        const menu = document.querySelector('.nav-links');
        if (toggle && menu) {
            toggle.addEventListener('click', () => {
                const open = toggle.getAttribute('aria-expanded') === 'true';
                toggle.setAttribute('aria-expanded', String(!open));
                toggle.setAttribute('aria-label', open ? 'Menüyü aç' : 'Menüyü kapat');
                menu.classList.toggle('is-open', !open);
                document.body.classList.toggle('menu-open', !open);
            });
            menu.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
                toggle.setAttribute('aria-expanded', 'false');
                toggle.setAttribute('aria-label', 'Menüyü aç');
                menu.classList.remove('is-open');
                document.body.classList.remove('menu-open');
            }));
        }

        document.querySelectorAll('.faq-question').forEach(button => {
            button.addEventListener('click', () => {
                button.setAttribute('aria-expanded', String(button.getAttribute('aria-expanded') !== 'true'));
            });
        });

        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const reveals = document.querySelectorAll('.reveal');
        if (reducedMotion || !('IntersectionObserver' in window)) {
            reveals.forEach(item => item.classList.add('is-visible'));
        } else {
            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: .08 });
            reveals.forEach(item => observer.observe(item));
        }
    })();
</script>
</body>
</html>
