<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Models\Kurum;
use App\Models\Rapor;

final class PanelController extends Controller
{
    public function genelBakis(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $kurumId = Auth::kurumId();
        $rapor = Rapor::genelBakisVerisi();
        $raporOzet = $rapor['ozet'] ?? [];
        $ozet = [
            'ogrenci' => (int) ($raporOzet['aktif_ogrenci'] ?? 0),
            'randevu' => (int) ($raporOzet['bugunku_randevu'] ?? 0),
        ];

        $this->view('panel/genel-bakis', [
            'baslik' => 'Genel Bakis',
            'aktif' => 'genel-bakis',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'ozet' => $ozet,
            'rapor' => $rapor,
            'veliPortalAnahtari' => Kurum::veliPortalAnahtari($kurumId),
        ], 'panel');
    }
}
