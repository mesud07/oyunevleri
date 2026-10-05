<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Services\HizSinirlayici;
use App\Services\MfaServisi;
use App\Services\KimlikDogrulamaServisi;

final class GirisController extends Controller
{
    public function form(): void
    {
        if (Auth::check()) {
            Response::redirect('/panel');
        }

        $this->view('auth/giris', [
            'baslik' => 'Giris',
            'csrf' => Csrf::token(),
            'hata' => null,
        ], 'giris');
    }

    public function giris(): void
    {
        $veri = [
            'kurum_kodu' => $_POST['kurum_kodu'] ?? '',
            'eposta' => $_POST['eposta'] ?? '',
            'sifre' => $_POST['sifre'] ?? '',
            'csrf' => $_POST['csrf'] ?? '',
            'beni_hatirla' => $_POST['beni_hatirla'] ?? '',
        ];
        $beniHatirla = (string) $veri['beni_hatirla'] === '1';

        $hatalar = Validator::gerekli($veri, ['eposta', 'sifre']);
        if (!Csrf::dogrula((string) $veri['csrf'])) {
            $hatalar['csrf'] = 'Guvenlik dogrulamasi gecersiz.';
        }

        if ($hatalar) {
            $this->view('auth/giris', [
                'baslik' => 'Giris',
                'csrf' => Csrf::token(),
                'hata' => 'Kullanici adi, sifre veya guvenlik bilgisi hatali.',
                'kurumKodu' => (string) $veri['kurum_kodu'],
                'eposta' => (string) $veri['eposta'],
                'beniHatirla' => $beniHatirla,
            ], 'giris');
            return;
        }

        $eposta = mb_strtolower(trim((string) $veri['eposta']));
        $kurumKodu = strtoupper(trim((string) $veri['kurum_kodu']));
        $ip = Request::clientIp();
        $hesapScope = 'login:hesap:' . $kurumKodu . ':' . $eposta . ':' . $ip;
        $ipScope = 'login:ip:' . $ip;
        $sinirlayici = new HizSinirlayici();
        $hesapDurum = $sinirlayici->engelliMi($hesapScope, 5, 900);
        $ipDurum = $sinirlayici->engelliMi($ipScope, 30, 900);
        if ($hesapDurum['engelli'] || $ipDurum['engelli']) {
            http_response_code(429);
            header('Retry-After: ' . max(60, (int) max($hesapDurum['kalan_saniye'], $ipDurum['kalan_saniye'])));
            $this->view('auth/giris', [
                'baslik' => 'Giris',
                'csrf' => Csrf::token(),
                'hata' => 'Çok fazla giriş denemesi yapıldı. Lütfen bir süre sonra tekrar deneyin.',
                'kurumKodu' => (string) $veri['kurum_kodu'],
                'eposta' => (string) $veri['eposta'],
                'beniHatirla' => $beniHatirla,
            ], 'giris');
            return;
        }

        $servis = new KimlikDogrulamaServisi();
        $kullanici = $servis->dogrula($eposta, (string) $veri['sifre'], $kurumKodu);
        if (!$kullanici) {
            $sinirlayici->kaydet($hesapScope, 5, 900, 900);
            $sinirlayici->kaydet($ipScope, 30, 900, 900);
            $this->view('auth/giris', [
                'baslik' => 'Giris',
                'csrf' => Csrf::token(),
                'hata' => 'Kullanici adi veya sifre hatali.',
                'kurumKodu' => (string) $veri['kurum_kodu'],
                'eposta' => (string) $veri['eposta'],
                'beniHatirla' => $beniHatirla,
            ], 'giris');
            return;
        }

        $sinirlayici->temizle($hesapScope);
        $sinirlayici->temizle($ipScope);

        if ((int) ($kullanici['mfa_enabled'] ?? 0) === 1) {
            Session::regenerate();
            Session::set('mfa_pending_id', (int) $kullanici['id']);
            Session::set('mfa_pending_expires', time() + 300);
            Session::set('mfa_pending_remember', $beniHatirla);
            Response::redirect('/mfa');
        }

        $servis->oturumAc($kullanici, $beniHatirla);
        if ((new MfaServisi())->kayitGerekliMi($kullanici)) {
            Response::redirect('/panel/guvenlik/mfa');
        }

        Response::redirect('/panel');
    }

    public function cikis(): void
    {
        if (!Csrf::dogrula((string) ($_POST['csrf'] ?? ''))) {
            Response::redirect('/panel');
        }

        Auth::logout();
        Response::redirect('/giris');
    }
}
