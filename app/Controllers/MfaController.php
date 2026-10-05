<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Session;
use App\Core\Veritabani;
use App\Models\IslemKaydi;
use App\Models\Kullanici;
use App\Services\HizSinirlayici;
use App\Services\MfaServisi;

final class MfaController extends Controller
{
    public function form(): void
    {
        $pendingId = (int) Session::get('mfa_pending_id', 0);
        if ($pendingId < 1 || (int) Session::get('mfa_pending_expires', 0) < time()) {
            $this->pendingTemizle();
            Response::redirect('/giris');
        }
        $this->view('auth/mfa', ['baslik' => 'İki Aşamalı Doğrulama', 'csrf' => Csrf::token(), 'hata' => null], 'giris');
    }

    public function dogrula(): void
    {
        $pendingId = (int) Session::get('mfa_pending_id', 0);
        if (!Csrf::dogrula((string) ($_POST['csrf'] ?? '')) || $pendingId < 1 || (int) Session::get('mfa_pending_expires', 0) < time()) {
            $this->pendingTemizle();
            Response::redirect('/giris');
        }
        $scope = 'mfa-login:' . $pendingId;
        $limit = new HizSinirlayici();
        if ($limit->engelliMi($scope, 5, 900)['engelli']) {
            http_response_code(429);
            $this->view('auth/mfa', ['baslik' => 'İki Aşamalı Doğrulama', 'csrf' => Csrf::token(), 'hata' => 'Çok fazla hatalı kod girildi. 15 dakika sonra tekrar deneyin.'], 'giris');
            return;
        }
        if (!(new MfaServisi())->kullaniciDogrula($pendingId, (string) ($_POST['kod'] ?? ''))) {
            $limit->kaydet($scope, 5, 900, 900);
            $this->view('auth/mfa', ['baslik' => 'İki Aşamalı Doğrulama', 'csrf' => Csrf::token(), 'hata' => 'Doğrulama kodu geçersiz.'], 'giris');
            return;
        }
        $kullanici = Kullanici::mfaIcinBul($pendingId);
        if (!$kullanici) {
            Response::redirect('/giris');
        }
        $limit->temizle($scope);
        $beniHatirla = (bool) Session::get('mfa_pending_remember', false);
        $this->pendingTemizle();
        Auth::login($kullanici, $beniHatirla);
        Session::set('mfa_verified_at', time());
        IslemKaydi::ekle($pendingId, 'mfa_giris_basarili', 'MFA doğrulamasıyla giriş yapıldı.');
        Response::redirect('/panel');
    }

    public function ayarlar(): void
    {
        $kullanici = Auth::user();
        if (!$kullanici) {
            Response::redirect('/giris');
        }
        $secret = (string) Session::get('mfa_enrollment_secret', '');
        if ((int) ($kullanici['mfa_enabled'] ?? 0) !== 1 && $secret === '') {
            $secret = (new MfaServisi())->secretOlustur();
            Session::set('mfa_enrollment_secret', $secret);
        }
        $recovery = Session::get('mfa_recovery_codes', []);
        Session::remove('mfa_recovery_codes');
        $this->view('panel/mfa-ayarlari', [
            'baslik' => 'Hesap Güvenliği', 'aktif' => 'mfa', 'kullanici' => $kullanici, 'csrf' => Csrf::token(),
            'secret' => $secret, 'otpauth' => $secret !== '' ? (new MfaServisi())->otpauthUri($kullanici, $secret) : '',
            'recoveryCodes' => is_array($recovery) ? $recovery : [], 'hata' => null,
        ], 'panel');
    }

    public function etkinlestir(): void
    {
        $kullanici = Auth::user();
        $secret = (string) Session::get('mfa_enrollment_secret', '');
        if (!$kullanici || !Csrf::dogrula((string) ($_POST['csrf'] ?? '')) || $secret === '') {
            Response::redirect('/panel/guvenlik/mfa');
        }
        if (!(new MfaServisi())->kodDogrula($secret, (string) ($_POST['kod'] ?? ''))) {
            $this->view('panel/mfa-ayarlari', [
                'baslik' => 'Hesap Güvenliği', 'aktif' => 'mfa', 'kullanici' => $kullanici, 'csrf' => Csrf::token(),
                'secret' => $secret, 'otpauth' => (new MfaServisi())->otpauthUri($kullanici, $secret),
                'recoveryCodes' => [], 'hata' => 'Kod doğrulanamadı. Telefonunuzun saatinin otomatik ayarlı olduğunu kontrol edin.',
            ], 'panel');
            return;
        }
        $db = Veritabani::baglan();
        try {
            $db->beginTransaction();
            $kodlar = (new MfaServisi())->etkinlestir((int) $kullanici['id'], $secret);
            IslemKaydi::ekle((int) $kullanici['id'], 'mfa_etkinlestirildi', 'Kullanıcı MFA doğrulamasını etkinleştirdi.');
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $referans = bin2hex(random_bytes(4));
            error_log(sprintf(
                'MFA etkinlestirme hatasi [%s] %s: %s',
                $referans,
                get_class($e),
                preg_replace('/[\r\n]+/', ' ', mb_substr($e->getMessage(), 0, 500))
            ));
            http_response_code(422);
            $this->view('panel/mfa-ayarlari', [
                'baslik' => 'Hesap Güvenliği', 'aktif' => 'mfa', 'kullanici' => $kullanici, 'csrf' => Csrf::token(),
                'secret' => $secret, 'otpauth' => (new MfaServisi())->otpauthUri($kullanici, $secret),
                'recoveryCodes' => [], 'hata' => 'MFA kurulumu tamamlanamadı. Destek kodu: ' . $referans,
            ], 'panel');
            return;
        }
        Session::remove('mfa_enrollment_secret');
        Session::set('mfa_recovery_codes', $kodlar);
        Session::set('mfa_verified_at', time());
        Response::redirect('/panel/guvenlik/mfa');
    }

    private function pendingTemizle(): void
    {
        Session::remove('mfa_pending_id');
        Session::remove('mfa_pending_expires');
        Session::remove('mfa_pending_remember');
    }
}
