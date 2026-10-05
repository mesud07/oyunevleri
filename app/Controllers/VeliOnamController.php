<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Models\IslemKaydi;
use App\Models\VeliOnam;
use App\Services\HizSinirlayici;
use App\Services\VeliOnamPdfServisi;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;

final class VeliOnamController extends Controller
{
    private const ANTALYA_ILCELERI = [
        'Akseki', 'Aksu', 'Alanya', 'Demre', 'Döşemealtı', 'Elmalı', 'Finike',
        'Gazipaşa', 'Gündoğmuş', 'İbradı', 'Kaş', 'Kemer', 'Kepez', 'Konyaaltı',
        'Korkuteli', 'Kumluca', 'Manavgat', 'Muratpaşa', 'Serik',
    ];

    public function form(): void
    {
        $token = trim((string) ($_GET['t'] ?? ''));
        $ayar = VeliOnam::tokenIleAyar($token);
        if (!$ayar) {
            http_response_code(404);
        }
        $this->formGoster($ayar, [], null, false);
    }

    public function kaydet(): void
    {
        $token = trim((string) ($_POST['token'] ?? ''));
        $ayar = VeliOnam::tokenIleAyar($token);
        if (!$ayar) {
            http_response_code(404);
            $this->formGoster(null, [], 'Form bağlantısı geçersiz veya kullanım dışı.', false);
            return;
        }
        if (!Csrf::dogrula((string) ($_POST['csrf'] ?? ''))) {
            http_response_code(419);
            $this->formGoster($ayar, $_POST, 'İşlem doğrulanamadı. Sayfayı yenileyip tekrar deneyin.', false);
            return;
        }

        $ip = Request::clientIp();
        $limit = new HizSinirlayici();
        if ($limit->engelliMi('veli-onam:ip:' . $ip, 8, 900)['engelli']) {
            http_response_code(429);
            $this->formGoster($ayar, $_POST, 'Çok fazla gönderim yapıldı. Lütfen 15 dakika sonra tekrar deneyin.', false);
            return;
        }
        $limit->kaydet('veli-onam:ip:' . $ip, 8, 900, 900);

        $veri = [
            'veli_ad_soyad' => trim((string) ($_POST['veli_ad_soyad'] ?? '')),
            'veli_telefon' => trim((string) ($_POST['veli_telefon'] ?? '')),
            'veli_eposta' => trim((string) ($_POST['veli_eposta'] ?? '')),
            'ogrenci_ad_soyad' => trim((string) ($_POST['ogrenci_ad_soyad'] ?? '')),
            'ogrenci_dogum_tarihi' => trim((string) ($_POST['ogrenci_dogum_tarihi'] ?? '')),
            'ek_bilgiler' => [
                'anne_ad_soyad' => trim((string) ($_POST['anne_ad_soyad'] ?? '')),
                'baba_ad_soyad' => trim((string) ($_POST['baba_ad_soyad'] ?? '')),
                'il' => trim((string) ($_POST['il'] ?? 'Antalya')),
                'ilce' => trim((string) ($_POST['ilce'] ?? '')),
                'ev_adresi' => trim((string) ($_POST['ev_adresi'] ?? '')),
                'uzman_destegi' => trim((string) ($_POST['uzman_destegi'] ?? '')),
                'uzman_aciklama' => trim((string) ($_POST['uzman_aciklama'] ?? '')),
                'oyun_grubu_deneyimi' => trim((string) ($_POST['oyun_grubu_deneyimi'] ?? '')),
                'oyun_grubu_aciklama' => trim((string) ($_POST['oyun_grubu_aciklama'] ?? '')),
                'tani_durumu' => trim((string) ($_POST['tani_durumu'] ?? '')),
                'tani_aciklama' => trim((string) ($_POST['tani_aciklama'] ?? '')),
                'ilac_durumu' => trim((string) ($_POST['ilac_durumu'] ?? '')),
                'ilac_aciklama' => trim((string) ($_POST['ilac_aciklama'] ?? '')),
                'alerji_durumu' => trim((string) ($_POST['alerji_durumu'] ?? '')),
                'ozel_saglik_durumu' => trim((string) ($_POST['ozel_saglik_durumu'] ?? '')),
                'gorsel_kayit_izni' => !empty($_POST['gorsel_kayit_izni']),
                'sosyal_medya_izni' => !empty($_POST['sosyal_medya_izni']),
                'program_kurallari_kabul' => !empty($_POST['program_kurallari_kabul']),
            ],
        ];
        if ($veri['veli_ad_soyad'] === '' || $veri['ogrenci_ad_soyad'] === '' || $veri['veli_telefon'] === '') {
            $this->formGoster($ayar, $veri, 'Veli adı, öğrenci adı ve cep telefonu zorunludur.', false);
            return;
        }
        if ($veri['ek_bilgiler']['il'] !== 'Antalya') {
            $this->formGoster($ayar, $veri, 'Geçerli bir il seçin.', false);
            return;
        }
        if ($veri['ek_bilgiler']['ilce'] !== '' && !in_array($veri['ek_bilgiler']['ilce'], self::ANTALYA_ILCELERI, true)) {
            $this->formGoster($ayar, $veri, 'Geçerli bir Antalya ilçesi seçin.', false);
            return;
        }
        if (empty($_POST['dijital_onay'])) {
            $this->formGoster($ayar, $veri, 'Formu göndermek için onay kutusunu işaretleyin.', false);
            return;
        }
        foreach (['uzman_destegi', 'oyun_grubu_deneyimi'] as $alan) {
            if (!in_array($veri['ek_bilgiler'][$alan], ['evet', 'hayir'], true)) {
                $this->formGoster($ayar, $veri, 'Uzman desteği ve oyun grubu deneyimi sorularını cevaplayın.', false);
                return;
            }
        }
        foreach (['tani_durumu', 'ilac_durumu'] as $alan) {
            if (!in_array($veri['ek_bilgiler'][$alan], ['var', 'yok'], true)) {
                $this->formGoster($ayar, $veri, 'Tanı/gelişimsel farklılık ve düzenli ilaç sorularını cevaplayın.', false);
                return;
            }
        }
        if ($veri['ek_bilgiler']['uzman_destegi'] === 'evet' && $veri['ek_bilgiler']['uzman_aciklama'] === '') {
            $this->formGoster($ayar, $veri, 'Alınan uzman desteğini kısaca açıklayın.', false);
            return;
        }
        if ($veri['ek_bilgiler']['tani_durumu'] === 'var' && $veri['ek_bilgiler']['tani_aciklama'] === '') {
            $this->formGoster($ayar, $veri, 'Tanı veya gelişimsel farklılığı kısaca açıklayın.', false);
            return;
        }
        if ($veri['ek_bilgiler']['ilac_durumu'] === 'var' && $veri['ek_bilgiler']['ilac_aciklama'] === '') {
            $this->formGoster($ayar, $veri, 'Düzenli kullanılan ilacı kısaca açıklayın.', false);
            return;
        }
        if (empty($veri['ek_bilgiler']['program_kurallari_kabul'])) {
            $this->formGoster($ayar, $veri, 'Program kurallarını okuyup kabul etmelisiniz.', false);
            return;
        }
        if ($veri['veli_eposta'] !== '' && !filter_var($veri['veli_eposta'], FILTER_VALIDATE_EMAIL)) {
            $this->formGoster($ayar, $veri, 'Geçerli bir e-posta adresi girin.', false);
            return;
        }
        if ($veri['ogrenci_dogum_tarihi'] !== '') {
            $tarih = \DateTimeImmutable::createFromFormat('!Y-m-d', $veri['ogrenci_dogum_tarihi']);
            if (!$tarih || $tarih->format('Y-m-d') !== $veri['ogrenci_dogum_tarihi'] || $tarih > new \DateTimeImmutable('today')) {
                $this->formGoster($ayar, $veri, 'Geçerli bir doğum tarihi girin.', false);
                return;
            }
        }

        try {
            $id = VeliOnam::kaydet($ayar, $veri, [
                'ip_hash' => hash_hmac('sha256', $ip, (string) Config::get('APP_KEY', 'talya-onam')),
                'tarayici' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->formGoster($ayar, $veri, $e->getMessage(), false);
            return;
        }

        IslemKaydi::ekle(null, 'veli_onam_formu_dolduruldu', 'Veli onam formu dijital olarak dolduruldu.', [
            'kurum_id' => (int) $ayar['kurum_id'],
            'onam_kaydi_id' => $id,
        ]);
        $this->formGoster($ayar, [], null, true);
    }

    public function panel(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }
        $ayar = VeliOnam::ayar();
        $link = rtrim((string) Config::get('APP_URL', ''), '/') . '/oyun-grubu-onam?t=' . rawurlencode((string) $ayar['public_token']);
        $this->view('panel/veli-onamlari', [
            'baslik' => 'Veli Onam Formları',
            'aktif' => 'veli-onamlari',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'ayar' => $ayar,
            'link' => $link,
            'kayitlar' => VeliOnam::liste(),
            'ayarDuzenleyebilir' => $this->ayarDuzenleyebilir(),
        ], 'panel');
    }

    public function ayarKaydet(): void
    {
        if (!$this->ayarDuzenleyebilir()) {
            Response::json(['basari' => false, 'mesaj' => 'Bu ayarı yalnızca kurum kurucusu değiştirebilir.', 'hatalar' => []], 403);
            return;
        }

        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $onamMetni = trim((string) ($data['onam_metni'] ?? ''));
        $uzunluk = mb_strlen($onamMetni);
        if ($uzunluk < 20 || $uzunluk > 30000) {
            Response::json([
                'basari' => false,
                'mesaj' => 'Onam metni 20 ile 30.000 karakter arasında olmalıdır.',
                'hatalar' => ['onam_metni' => 'Geçerli uzunlukta bir metin yazın.'],
            ], 422);
            return;
        }

        $ayar = VeliOnam::onamMetniGuncelle($onamMetni);
        IslemKaydi::ekle((int) (Auth::user()['id'] ?? 0), 'veli_onam_ayari_guncellendi', 'Kurumun program kuralları ve katılım onamı metni güncellendi.', [
            'form_surumu' => (int) ($ayar['form_surumu'] ?? 1),
        ]);

        Response::json([
            'basari' => true,
            'mesaj' => 'Program kuralları ve katılım onamı güncellendi.',
            'veri' => ['form_surumu' => (int) ($ayar['form_surumu'] ?? 1)],
        ]);
    }

    public function qr(): void
    {
        $ayar = VeliOnam::ayar();
        $link = rtrim((string) Config::get('APP_URL', ''), '/') . '/oyun-grubu-onam?t=' . rawurlencode((string) $ayar['public_token']);
        $result = Builder::create()
            ->writer(new SvgWriter())
            ->data($link)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(720)
            ->margin(24)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();
        header('Content-Type: image/svg+xml; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: ' . (!empty($_GET['indir']) ? 'attachment' : 'inline') . '; filename="oyun-evleri-veli-onam-qr.svg"');
        echo $result->getString();
    }

    public function pdf(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $kayit = VeliOnam::bul($id);
        if (!$kayit) {
            http_response_code(404);
            echo 'Onam kaydı bulunamadı.';
            return;
        }
        $pdf = (new VeliOnamPdfServisi())->olustur($kayit);
        $dosya = 'veli-onam-' . $id . '.pdf';
        header('Content-Type: application/pdf');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: inline; filename="' . $dosya . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
    }

    private function formGoster(?array $ayar, array $degerler, ?string $hata, bool $basarili): void
    {
        $this->view('veli-onam/form', [
            'baslik' => 'Oyun Grubu Veli Onam Formu',
            'csrf' => Csrf::token(),
            'ayar' => $ayar,
            'degerler' => $degerler,
            'hata' => $hata,
            'basarili' => $basarili,
        ], 'veli');
    }

    private function ayarDuzenleyebilir(): bool
    {
        $kullanici = Auth::user();
        return $kullanici !== null
            && ((string) ($kullanici['rol_kodu'] ?? '') === 'kurucu' || Auth::sistemYoneticisiMi());
    }
}
