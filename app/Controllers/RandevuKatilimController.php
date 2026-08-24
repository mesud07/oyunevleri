<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Randevu;

final class RandevuKatilimController extends Controller
{
    public function form(): void
    {
        $token = trim((string) ($_GET['t'] ?? ''));
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
        $basarili = Randevu::katilimYanitiKaydet($token, $yanit);

        $this->view('randevu-katilim/form', [
            'baslik' => 'Randevu Katilim Durumu',
            'token' => $token,
            'randevu' => Randevu::katilimTokenIleBul($token),
            'mesaj' => $basarili ? 'Katilim durumunuz kaydedildi.' : 'Katilim durumu kaydedilemedi.',
        ], 'veli');
    }
}
