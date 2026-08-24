<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Validator;
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
            'kurum_kodu' => $_POST['kurum_kodu'] ?? 'TALYA',
            'eposta' => $_POST['eposta'] ?? '',
            'sifre' => $_POST['sifre'] ?? '',
            'csrf' => $_POST['csrf'] ?? '',
        ];

        $hatalar = Validator::gerekli($veri, ['eposta', 'sifre']);
        if (!Csrf::dogrula((string) $veri['csrf'])) {
            $hatalar['csrf'] = 'Guvenlik dogrulamasi gecersiz.';
        }

        if ($hatalar) {
            $this->view('auth/giris', [
                'baslik' => 'Giris',
                'csrf' => Csrf::token(),
                'hata' => 'Kullanici adi, sifre veya guvenlik bilgisi hatali.',
            ], 'giris');
            return;
        }

        $servis = new KimlikDogrulamaServisi();
        if (!$servis->giris((string) $veri['eposta'], (string) $veri['sifre'], (string) $veri['kurum_kodu'])) {
            $this->view('auth/giris', [
                'baslik' => 'Giris',
                'csrf' => Csrf::token(),
                'hata' => 'Kullanici adi veya sifre hatali.',
            ], 'giris');
            return;
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
