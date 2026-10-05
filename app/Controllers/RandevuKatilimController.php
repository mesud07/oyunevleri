<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Randevu;
use App\Services\HizSinirlayici;

final class RandevuKatilimController extends Controller
{
    public function form(): void
    {
        $token = trim((string) ($_GET['t'] ?? ''));
        $limit = new HizSinirlayici();
        $scope = 'randevu-katilim:get:' . Request::clientIp();
        if ($limit->engelliMi($scope, 60, 600)['engelli']) {
            http_response_code(429);
            $token = '';
        } else {
            $limit->kaydet($scope, 60, 600, 600);
        }
        $this->view('randevu-katilim/form', [
            'baslik' => 'Randevu Katilim Durumu',
            'token' => $token,
            'randevu' => Randevu::katilimTokenIleBul($token),
            'mesaj' => '',
        ], 'veli');
    }

    public function kaydet(): void
    {
        $token = trim((string) ($_POST['token'] ?? ''));
        $yanit = trim((string) ($_POST['yanit'] ?? ''));
        $limit = new HizSinirlayici();
        $scope = 'randevu-katilim:post:' . Request::clientIp() . ':' . hash('sha256', $token);
        if ($limit->engelliMi($scope, 10, 600)['engelli']) {
            http_response_code(429);
            $basarili = false;
        } else {
            $limit->kaydet($scope, 10, 600, 600);
            $basarili = Randevu::katilimYanitiKaydet($token, $yanit);
        }

        $randevu = Randevu::katilimTokenIleBul($token);
        if (!$basarili && $randevu && empty($randevu['katilim_acik'])) {
            $mesaj = 'Ders saati gectigi icin katilim durumu artik degistirilemez.';
        } else {
            $mesaj = $basarili ? 'Katilim durumunuz kaydedildi.' : 'Katilim durumu kaydedilemedi.';
        }

        $this->view('randevu-katilim/form', [
            'baslik' => 'Randevu Katilim Durumu',
            'token' => $token,
            'randevu' => $randevu,
            'mesaj' => $mesaj,
        ], 'veli');
    }
}
