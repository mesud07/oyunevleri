<?php
$tarihYaz = static function (?string $tarih): string {
    if (!$tarih) {
        return '-';
    }
    $zaman = strtotime($tarih);
    return $zaman ? date('d/m/Y', $zaman) : $tarih;
};
$deger = static fn(mixed $icerik): string => e(trim((string) $icerik) !== '' ? (string) $icerik : '-');
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 23px 28px 22px; }
    * { box-sizing: border-box; }
    body { margin: 0; color: #303b50; font-family: "DejaVu Sans", sans-serif; font-size: 9.1px; line-height: 1.32; }
    .brand { height: 52px; margin: 0 0 12px; color: #58aa96; text-align: center; font-size: 21px; font-weight: bold; }
    .brand-logo { display: block; margin: 0 auto; }
    .brand-mark { display: inline-block; width: 24px; height: 24px; margin-right: 5px; border: 5px solid #78c7b4; border-radius: 50%; vertical-align: -6px; }
    .grid { width: 100%; border-collapse: separate; border-spacing: 9px 0; margin-left: -9px; }
    .grid td { width: 50%; vertical-align: top; }
    .box { margin-bottom: 10px; padding: 12px 13px; border-radius: 9px; background: #f5f7fa; }
    .eyebrow { color: #6d7b94; font-size: 8.6px; text-transform: uppercase; }
    h1 { margin: 4px 0 7px; color: #273247; font-size: 15px; line-height: 1.18; }
    h2 { margin: 0 0 7px; padding-bottom: 5px; border-bottom: 1.6px solid #5262d6; color: #303b50; font-size: 11.5px; }
    p { margin: 0 0 7px; }
    .info { width: 100%; border-collapse: collapse; }
    .info td { width: auto; padding: 3px 0; border-bottom: 1px solid #e8ebf0; }
    .info td:first-child { width: 41%; font-weight: bold; }
    .value { color: #4351c6; }
    .purposes { margin: 4px 0 0; padding: 0; list-style: none; }
    .purposes li { padding: 4px 0; border-bottom: 1px solid #e8ebf0; }
    .purposes b { color: #3d485c; }
    .consent { margin: 0 0 10px; padding: 12px 13px; border-radius: 9px; background: #f5f7fa; }
    .warning { margin: 7px 0 10px; padding: 8px 9px; border: 1px solid #f3d58b; border-radius: 6px; background: #fff2cc; color: #8b6200; font-weight: bold; }
    .declaration { font-size: 8.7px; text-align: justify; }
    .line-label { margin: 12px 7px 15px; font-weight: bold; }
    .write-line { height: 19px; border-bottom: 1px solid #cbd3df; }
    .signatures { width: 100%; margin-top: 9px; border-collapse: separate; border-spacing: 7px 0; }
    .signatures td { vertical-align: top; }
    .field-label { font-size: 8px; font-weight: bold; }
    .field-value { min-height: 17px; padding: 3px 0 4px; border-bottom: 1px solid #cbd3df; color: #40506a; }
    .staff { margin-top: 10px; padding: 10px 12px; border: 1px solid #edf0f4; border-radius: 8px; background: #fafbfc; }
    .staff-table { width: 100%; border-collapse: separate; border-spacing: 8px 0; }
    .footer { margin-top: 10px; padding-top: 8px; border-top: 1px solid #dfe4eb; color: #71809a; text-align: center; font-size: 7.8px; }
</style>
</head>
<body>
    <div class="brand">
        <?php if (!empty($logoDataUri)) : ?>
            <img class="brand-logo" width="<?= e($logoPdfWidth) ?>" height="<?= e($logoPdfHeight) ?>" src="<?= e($logoDataUri) ?>" alt="<?= e($form['kurum_adi'] ?? 'Kurum') ?>">
        <?php else : ?>
            <span class="brand-mark"></span> <?= e($form['kurum_adi'] ?? 'Oyun Evleri') ?>
        <?php endif; ?>
    </div>

    <table class="grid"><tr>
        <td>
            <div class="box">
                <div class="eyebrow">Belge Adı</div>
                <h1>GÖRSEL İÇERİK KULLANIM ONAM FORMU</h1>
                <p>Bu form, öğrencinize ait fotoğraf ve/veya video içeriklerinin belirtilen amaçlarla kullanılmasına ilişkin açık rızanızı almak amacıyla hazırlanmıştır.</p>
            </div>
        </td>
        <td>
            <div class="box">
                <div class="eyebrow">Öğrenci Bilgileri</div>
                <table class="info">
                    <tr><td>Adı Soyadı:</td><td class="value"><?= $deger($form['ogrenci_ad_soyad']) ?></td></tr>
                    <tr><td>T.C. Kimlik No:</td><td class="value"><?= $deger($form['ogrenci_tc_kimlik_no']) ?></td></tr>
                    <tr><td>Doğum Tarihi:</td><td class="value"><?= e($tarihYaz($form['ogrenci_dogum_tarihi'])) ?></td></tr>
                    <tr><td>Form Tarihi:</td><td class="value"><?= e($tarihYaz($form['form_tarihi'])) ?></td></tr>
                </table>
            </div>
        </td>
    </tr></table>

    <table class="grid"><tr>
        <td>
            <div class="box">
                <h2>Görsel İçerik Kullanım Bilgilendirmesi</h2>
                <p>Sayın Veli/Yasal Temsilci,</p>
                <p><?= $deger($form['kurum_adi']) ?> bünyesinde yürütülen oyun, etkinlik, eğitim ve tanıtım faaliyetleri kapsamında öğrencinize ait fotoğraf ve/veya video içeriklerinin kullanılması planlanmaktadır.</p>
            </div>
            <div class="box">
                <h2>Gizlilik ve Haklarınız</h2>
                <p>Görsel içeriklerin kullanımı sırasında öğrencinin üstün yararı gözetilecek; özel nitelikteki bilgiler paylaşılmayacaktır. Dilediğiniz zaman bu onamı geri çekme hakkına sahipsiniz.</p>
                <p>Bu onam formu öğrencinin eğitim, etkinlik ve hizmetlerden yararlanma sürecini etkilemez.</p>
            </div>
        </td>
        <td>
            <div class="box">
                <h2>Görsel İçerikler Hangi Amaçlarla Kullanılacak?</h2>
                <p>Görsel içerikler aşağıdaki amaçlarla kullanılabilecektir:</p>
                <ul class="purposes">
                    <li><b>Sosyal Medya Paylaşımları:</b> Kuruma ait sosyal medya hesaplarında</li>
                    <li><b>Web Sitesi:</b> Kurumsal web sitesi ve duyuru sayfalarında</li>
                    <li><b>Tanıtım Materyalleri:</b> Broşür, afiş ve dijital tanıtım içeriklerinde</li>
                    <li><b>Eğitim ve Etkinlik Arşivi:</b> Kurum içi etkinlik kayıtlarında</li>
                </ul>
            </div>
        </td>
    </tr></table>

    <div class="consent">
        <h2>Görsel İçerik Kullanım Açık Rıza Beyanı</h2>
        <div class="warning">18 YAŞ ALTI ÖĞRENCİ BİLGİLENDİRMESİ: Öğrenci 18 yaşından küçük olduğu için veli/yasal temsilci onayı gerekmektedir.</div>
        <p><b>YUKARIDA YER ALAN BİLGİLENDİRME METNİNİ OKUDUM VE İÇERİĞİNİ TAMAMEN ANLADIĞIMI BEYAN EDERİM.</b></p>
        <p class="declaration">Bu kapsamda; öğrencime ait fotoğraf ve/veya video çekimlerinin yapılmasına, bu çekimlerden elde edilen görsel içeriklerin öğrencinin mahremiyeti ve üstün yararı gözetilmek kaydıyla yukarıda belirtilen tanıtım, bilgilendirme ve eğitim amaçları doğrultusunda kurum tarafından işlenmesine, paylaşılmasına ve kullanılmasına açık rıza verdiğimi; bu metni okuyup anladığımı kabul ederim.</p>
        <div class="line-label">Kendi el yazınız ile “Okudum, anladım ve kabul ediyorum” yazınız:<div class="write-line"></div></div>

        <table class="signatures"><tr>
            <td style="width:44%"><div class="field-label">Veli/Yasal Temsilci Adı Soyadı</div><div class="field-value"><?= $deger($form['veli_ad_soyad']) ?></div></td>
            <td style="width:34%"><div class="field-label">Veli/Yasal Temsilci T.C. Kimlik No</div><div class="field-value"><?= $deger($form['veli_tc_kimlik_no']) ?></div></td>
            <td style="width:22%"><div class="field-label">Tarih</div><div class="field-value"><?= e($tarihYaz($form['form_tarihi'])) ?></div></td>
        </tr><tr>
            <td><div class="field-label" style="margin-top:7px">Veli/Yasal Temsilci İmza</div><div class="field-value">&nbsp;</div></td>
            <td colspan="2"><div class="field-label" style="margin-top:7px">Yakınlık Derecesi</div><div class="field-value"><?= $deger($form['veli_yakinlik']) ?></div></td>
        </tr></table>

        <div class="staff">
            <h2>Formu Hazırlayan Kurum Personeli</h2>
            <p>Bu formun doldurulması ve onaylanması sürecinde görev yapan kurum personeli bilgileri aşağıda yer almaktadır.</p>
            <table class="staff-table"><tr>
                <td><div class="field-label">Unvan</div><div class="field-value"><?= $deger($form['personel_unvan']) ?></div></td>
                <td><div class="field-label">Adı Soyadı</div><div class="field-value"><?= $deger($form['personel_ad_soyad']) ?></div></td>
                <td><div class="field-label">İmza</div><div class="field-value">&nbsp;</div></td>
                <td><div class="field-label">Tarih</div><div class="field-value"><?= e($tarihYaz($form['form_tarihi'])) ?></div></td>
            </tr></table>
        </div>
    </div>

    <div class="footer">Bu belge <?= $deger($form['kurum_adi']) ?> tarafından Oyun Evleri Yönetim Sistemi aracılığıyla oluşturulmuştur. Formun bir suretini talep edebilirsiniz.</div>
</body>
</html>
