<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Models\HaftalikTema;

final class HaftalikTemaController extends Controller
{
    public function temalarSayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $bugun = date('Y-m-d');
        $baslangic = trim((string) ($_GET['baslangic'] ?? date('Y-m-d', strtotime('-30 days'))));
        $bitis = trim((string) ($_GET['bitis'] ?? $bugun));
        $katilim = trim((string) ($_GET['katilim'] ?? 'tumu'));
        $baslangic = $this->gecerliTarih($baslangic) ? $baslangic : date('Y-m-d', strtotime('-30 days'));
        $bitis = $this->gecerliTarih($bitis) ? $bitis : $bugun;
        if ($bitis < $baslangic) {
            [$baslangic, $bitis] = [$bitis, $baslangic];
        }
        if (!in_array($katilim, ['tumu', 'geldi', 'gelmedi', 'bekliyor', 'iptal'], true)) {
            $katilim = 'tumu';
        }

        $temaTakibi = HaftalikTema::ogrenciTemaTakibi($baslangic, $bitis, $katilim);
        $ogrenciIdleri = [];
        $takipOzeti = ['toplam' => count($temaTakibi), 'ogrenci' => 0, 'islendi' => 0, 'islenmedi' => 0, 'bekliyor' => 0];
        foreach ($temaTakibi as $kayit) {
            $ogrenciIdleri[(int) $kayit['ogrenci_id']] = true;
            $durum = (string) ($kayit['tema_durumu'] ?? 'bekliyor');
            if (isset($takipOzeti[$durum])) {
                $takipOzeti[$durum]++;
            }
        }
        $takipOzeti['ogrenci'] = count($ogrenciIdleri);

        $this->view('panel/haftalik-temalar', [
            'baslik' => 'Haftalik Temalar',
            'aktif' => 'haftalik-temalar',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'yasGruplari' => HaftalikTema::yasGruplari(),
            'tablolarHazir' => HaftalikTema::tablolarVarMi(),
            'baslangic' => $baslangic,
            'bitis' => $bitis,
            'katilim' => $katilim,
            'temaTakibi' => $temaTakibi,
            'takipOzeti' => $takipOzeti,
        ], 'panel');
    }

    public function liste(): void
    {
        Response::json(['basari' => true, 'mesaj' => 'Temalar listelendi.', 'veri' => HaftalikTema::liste()]);
    }

    public function detay(): void
    {
        $id = (int) (($GLOBALS['talya_ajax_data'] ?? [])['id'] ?? 0);
        if ($id < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Tema secimi gecersiz.', 'hatalar' => []], 422);
            return;
        }

        $tema = HaftalikTema::detay($id);
        if (!$tema) {
            Response::json(['basari' => false, 'mesaj' => 'Tema bulunamadi.', 'hatalar' => []], 404);
            return;
        }
        Response::json(['basari' => true, 'mesaj' => 'Tema getirildi.', 'veri' => $tema]);
    }

    public function kaydet(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));
        $weekStart = trim((string) ($data['week_start'] ?? ''));
        $weekEnd = trim((string) ($data['week_end'] ?? ''));
        $ageGroups = array_values(array_unique(array_filter(
            array_map('intval', (array) ($data['age_group_ids'] ?? [])),
            static fn(int $ageGroupId): bool => $ageGroupId > 0
        )));

        $hatalar = [];
        if ($title === '') {
            $hatalar['title'] = 'Tema basligi zorunludur.';
        }
        if (!$this->gecerliTarih($weekStart)) {
            $hatalar['week_start'] = 'Baslangic tarihi gecersiz.';
        }
        if (!$this->gecerliTarih($weekEnd)) {
            $hatalar['week_end'] = 'Bitis tarihi gecersiz.';
        }
        if ($weekStart !== '' && $weekEnd !== '' && $weekEnd < $weekStart) {
            $hatalar['week_end'] = 'Bitis tarihi baslangictan once olamaz.';
        }
        if (!$ageGroups) {
            $hatalar['age_group_ids'] = 'En az bir yas grubu secilmelidir.';
        }
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik veya hatali alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        $temaId = HaftalikTema::kaydet($id, [
            'title' => $title,
            'description' => trim((string) ($data['description'] ?? '')),
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'age_group_ids' => $ageGroups,
        ]);
        Response::json(['basari' => true, 'mesaj' => 'Tema kaydedildi.', 'veri' => ['id' => $temaId]]);
    }

    public function sil(): void
    {
        $id = (int) (($GLOBALS['talya_ajax_data'] ?? [])['id'] ?? 0);
        if ($id < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Tema secimi gecersiz.', 'hatalar' => []], 422);
            return;
        }
        if (!HaftalikTema::sil($id)) {
            Response::json(['basari' => false, 'mesaj' => 'Tema bulunamadi.', 'hatalar' => []], 404);
            return;
        }
        Response::json(['basari' => true, 'mesaj' => 'Tema silindi.', 'veri' => ['id' => $id]]);
    }

    private function gecerliTarih(string $tarih): bool
    {
        $parca = \DateTimeImmutable::createFromFormat('Y-m-d', $tarih);
        return $parca instanceof \DateTimeImmutable && $parca->format('Y-m-d') === $tarih;
    }
}
