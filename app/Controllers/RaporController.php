<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Models\FinansRaporu;
use App\Models\Rapor;

final class RaporController extends Controller
{
    public function sayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $this->view('panel/raporlar', [
            'baslik' => 'Raporlar',
            'aktif' => 'raporlar',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'rapor' => Rapor::sayfaVerisi(trim((string) ($_GET['hafta'] ?? ''))),
        ], 'panel');
    }

    public function ozet(): void
    {
        Response::json(['basari' => true, 'mesaj' => 'Rapor ozeti hazir.', 'veri' => Rapor::sayfaVerisi()]);
    }

    public function gelirGiderSayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $gorunum = trim((string) ($_GET['gorunum'] ?? 'aylik'));
        $baslangic = trim((string) ($_GET['baslangic'] ?? ''));
        $bitis = trim((string) ($_GET['bitis'] ?? ''));
        $this->view('panel/gelir-gider', [
            'baslik' => 'Gelir Gider Analizi',
            'aktif' => 'gelir-gider',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'analiz' => FinansRaporu::gelirGider($gorunum, $baslangic, $bitis),
        ], 'panel');
    }

    public function gelirGiderVeri(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $gorunum = trim((string) ($data['gorunum'] ?? 'aylik'));
        $baslangic = trim((string) ($data['baslangic'] ?? ''));
        $bitis = trim((string) ($data['bitis'] ?? ''));
        $analiz = FinansRaporu::gelirGider($gorunum, $baslangic, $bitis);

        ob_start();
        require BASE_PATH . '/resources/views/panel/partials/gelir-gider-sonuclari.php';
        $html = (string) ob_get_clean();

        Response::json([
            'basari' => true,
            'mesaj' => 'Gelir gider analizi guncellendi.',
            'veri' => [
                'html' => $html,
                'gorunum' => $analiz['gorunum'],
                'baslangic' => $analiz['baslangic_tarihi'],
                'bitis' => $analiz['bitis_tarihi'],
            ],
        ]);
    }
}
