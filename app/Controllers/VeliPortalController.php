<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Kurum;
use App\Models\VeliPortali;

final class VeliPortalController extends Controller
{
    public function form(): void
    {
        $anahtar = strtolower(trim((string) ($_GET['k'] ?? '')));
        $kurum = Kurum::veliPortalAnahtariIleBul($anahtar);
        $this->view('veli-portal/form', [
            'baslik' => $kurum ? $kurum['ad'] . ' Veli Bilgi Ekranı' : 'Veli Bilgi Ekranı',
            'csrf' => Csrf::token(),
            'telefon' => '',
            'hata' => null,
            'sonuc' => null,
            'kurum' => $kurum,
            'portalAnahtari' => $anahtar,
        ], 'veli');
    }

    public function dogrula(): void
    {
        $telefon = trim((string) ($_POST['telefon'] ?? ''));
        $anahtar = strtolower(trim((string) ($_POST['portal_anahtari'] ?? '')));
        $kurum = Kurum::veliPortalAnahtariIleBul($anahtar);
        $hata = null;
        $sonuc = null;

        if (!$kurum) {
            $hata = 'Kurum bağlantısı geçersiz veya artık aktif değil. Lütfen kurumunuzdan güncel veli portalı bağlantısını isteyin.';
        } elseif (!Csrf::dogrula((string) ($_POST['csrf'] ?? ''))) {
            $hata = 'Güvenlik doğrulaması geçersiz. Lütfen tekrar deneyin.';
        } else {
            $sonuc = VeliPortali::telefonlaBul($telefon, (int) $kurum['id']);
            if (!$sonuc['gecerli']) {
                $hata = 'Lütfen 05xx xxx xx xx formatında geçerli bir telefon numarası girin.';
            } elseif ($sonuc['cocuklar'] === []) {
                $hata = 'Bu telefon numarasına ait öğrenci kaydı bulunamadı.';
            }
        }

        $this->view('veli-portal/form', [
            'baslik' => $kurum ? $kurum['ad'] . ' Veli Bilgi Ekranı' : 'Veli Bilgi Ekranı',
            'csrf' => Csrf::token(),
            'telefon' => $telefon,
            'hata' => $hata,
            'sonuc' => $sonuc,
            'kurum' => $kurum,
            'portalAnahtari' => $anahtar,
        ], 'veli');
    }
}
