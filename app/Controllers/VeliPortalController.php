<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Models\IslemKaydi;
use App\Models\Kurum;
use App\Models\VeliPortalDogrulama;
use App\Models\VeliPortali;
use App\Services\HizSinirlayici;
use App\Services\NetgsmServisi;

final class VeliPortalController extends Controller
{
    public function form(): void
    {
        $verified = Session::get('veli_portal_verified');
        $sonuc = null;
        if (is_array($verified) && (int) ($verified['expires_at'] ?? 0) >= time()) {
            $sonuc = VeliPortali::telefonlaBul((string) $verified['telefon'], (int) $verified['kurum_id']);
        } else {
            Session::remove('veli_portal_verified');
        }
        $this->goster('', null, $sonuc, 'telefon');
    }

    public function dogrula(): void
    {
        if (!Csrf::dogrula((string) ($_POST['csrf'] ?? ''))) {
            $this->goster('', 'İşlem doğrulanamadı. Lütfen sayfayı yenileyip tekrar deneyin.', null, 'telefon');
            return;
        }

        $islem = (string) ($_POST['islem'] ?? 'kod_gonder');
        if ($islem === 'kod_dogrula') {
            $this->koduDogrula();
            return;
        }
        $this->kodGonder();
    }

    private function kodGonder(): void
    {
        $telefon = VeliPortali::telefonNormalize((string) ($_POST['telefon'] ?? ''));
        if ($telefon === null) {
            $this->goster('', 'Geçerli bir cep telefonu numarası girin.', null, 'telefon');
            return;
        }

        $ip = Request::clientIp();
        $limit = new HizSinirlayici();
        if ($limit->engelliMi('veli-portal:ip:' . $ip, 10, 900)['engelli']
            || $limit->engelliMi('veli-portal:telefon:' . $telefon, 5, 900)['engelli']) {
            http_response_code(429);
            header('Retry-After: 900');
            $this->goster('', 'Çok fazla doğrulama isteği yapıldı. Lütfen 15 dakika sonra tekrar deneyin.', null, 'telefon');
            return;
        }
        $limit->kaydet('veli-portal:ip:' . $ip, 10, 900, 900);
        $limit->kaydet('veli-portal:telefon:' . $telefon, 5, 900, 900);

        $kurum = Kurum::kodIleBul((string) Config::get('VELI_PORTAL_KURUM_KODU', 'TALYA'));
        if (!$kurum) {
            $this->goster('', 'İşlem şu anda tamamlanamıyor. Lütfen daha sonra tekrar deneyin.', null, 'telefon');
            return;
        }

        $kod = (string) random_int(100000, 999999);
        $challengeId = VeliPortalDogrulama::olustur((int) $kurum['id'], $telefon, $kod);
        Session::set('veli_portal_challenge', [
            'id' => $challengeId,
            'telefon' => $telefon,
            'kurum_id' => (int) $kurum['id'],
            'expires_at' => time() + 300,
        ]);

        if (VeliPortali::telefonKayitliMi($telefon, (int) $kurum['id'])) {
            $sonuc = (new NetgsmServisi())->smsGonder([[
                'telefon' => $telefon,
                'mesaj' => 'Oyun Evleri veli portalı doğrulama kodunuz: ' . $kod . '. Kod 5 dakika geçerlidir.',
            ]]);
            if (empty($sonuc['basarili'])) {
                error_log('Veli portal OTP SMS gönderimi başarısız. challenge=' . $challengeId);
            }
        }

        $testKodu = null;
        if (Config::get('APP_ENV') !== 'production' && Config::bool('VELI_PORTAL_SHOW_TEST_CODE', false)) {
            $testKodu = $kod;
        }
        $this->goster($telefon, null, null, 'kod', 'Telefonunuza gönderilen 6 haneli kodu girin.', $testKodu);
    }

    private function koduDogrula(): void
    {
        $challenge = Session::get('veli_portal_challenge');
        $kod = trim((string) ($_POST['kod'] ?? ''));
        if (!is_array($challenge) || (int) ($challenge['expires_at'] ?? 0) < time()) {
            Session::remove('veli_portal_challenge');
            $this->goster('', 'Doğrulama süresi doldu. Lütfen yeniden kod isteyin.', null, 'telefon');
            return;
        }

        $ip = Request::clientIp();
        $limit = new HizSinirlayici();
        $scope = 'veli-portal:kod:' . (int) $challenge['id'] . ':' . $ip;
        if ($limit->engelliMi($scope, 5, 900)['engelli']) {
            http_response_code(429);
            $this->goster((string) $challenge['telefon'], 'Çok fazla hatalı kod girildi. Lütfen yeniden kod isteyin.', null, 'telefon');
            return;
        }

        $basarili = VeliPortalDogrulama::dogrula(
            (int) $challenge['id'],
            (int) $challenge['kurum_id'],
            (string) $challenge['telefon'],
            $kod
        );
        if (!$basarili) {
            $limit->kaydet($scope, 5, 900, 900);
            $this->goster((string) $challenge['telefon'], 'Kod geçersiz veya süresi dolmuş.', null, 'kod');
            return;
        }

        $limit->temizle($scope);
        Session::remove('veli_portal_challenge');
        Session::set('veli_portal_verified', [
            'telefon' => (string) $challenge['telefon'],
            'kurum_id' => (int) $challenge['kurum_id'],
            'expires_at' => time() + 600,
        ]);
        IslemKaydi::ekle(null, 'veli_portal_dogrulandi', 'Veli portalı SMS doğrulaması tamamlandı.', [
            'kurum_id' => (int) $challenge['kurum_id'],
        ]);
        $sonuc = VeliPortali::telefonlaBul((string) $challenge['telefon'], (int) $challenge['kurum_id']);
        $this->goster('', null, $sonuc, 'dogrulandi');
    }

    private function goster(string $telefon, ?string $hata, ?array $sonuc, string $adim, ?string $mesaj = null, ?string $testKodu = null): void
    {
        $this->view('veli-portal/form', [
            'baslik' => 'Veli Bilgi Ekrani',
            'csrf' => Csrf::token(),
            'telefon' => $telefon,
            'hata' => $hata,
            'mesaj' => $mesaj,
            'sonuc' => $sonuc,
            'adim' => $adim,
            'testKodu' => $testKodu,
        ], 'veli');
    }
}
