<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Models\Grup;
use App\Models\HaftalikTema;
use App\Models\Ogrenci;
use App\Models\OgrenciEtkinlikKaydi;

final class HaftalikTemaController extends Controller
{
    public function temalarSayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $this->view('panel/haftalik-temalar', [
            'baslik' => 'Haftalik Temalar',
            'aktif' => 'haftalik-temalar',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'yasGruplari' => HaftalikTema::yasGruplari(),
            'gruplar' => Grup::secenekler(),
            'tablolarHazir' => HaftalikTema::tablolarVarMi(),
        ], 'panel');
    }

    public function liste(): void
    {
        Response::json(['basari' => true, 'mesaj' => 'Temalar listelendi.', 'veri' => HaftalikTema::liste()]);
    }

    public function detay(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
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
        $ageGroups = array_values(array_filter(array_map('intval', (array) ($data['age_group_ids'] ?? [])), static fn(int $id): bool => $id > 0));
        $activities = $this->etkinlikleriTemizle((array) ($data['activities'] ?? []));

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
        foreach ($activities as $index => $activity) {
            if (empty($activity['group_ids'])) {
                $hatalar['activity_groups_' . $index] = 'Her etkinlik icin en az bir grup secilmelidir.';
            }
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
            'age_group_ids' => array_values(array_unique($ageGroups)),
            'activities' => $activities,
        ]);

        Response::json(['basari' => true, 'mesaj' => 'Tema kaydedildi.', 'veri' => ['id' => $temaId]]);
    }

    public function sil(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
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

    public function sablonListe(): void
    {
        Response::json(['basari' => true, 'mesaj' => 'Sablonlar listelendi.', 'veri' => EtkinlikSablonu::liste(false)]);
    }

    public function grupSecimListe(): void
    {
        Response::json(['basari' => true, 'mesaj' => 'Grup secim sablonlari listelendi.', 'veri' => HaftalikTema::grupSecimSablonlari()]);
    }

    public function grupSecimKaydet(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $title = trim((string) ($data['title'] ?? ''));
        $groupIds = array_values(array_unique(array_filter(array_map('intval', (array) ($data['group_ids'] ?? [])), static fn(int $id): bool => $id > 0)));

        $hatalar = [];
        if ($title === '') {
            $hatalar['title'] = 'Secim adi zorunludur.';
        }
        if (!$groupIds) {
            $hatalar['group_ids'] = 'En az bir grup secilmelidir.';
        }
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik veya hatali alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        $id = HaftalikTema::grupSecimKaydet((int) ($data['id'] ?? 0), [
            'title' => $title,
            'group_ids' => $groupIds,
        ]);

        Response::json(['basari' => true, 'mesaj' => 'Grup secimi kaydedildi.', 'veri' => ['id' => $id]]);
    }

    public function grupSecimSil(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        if ($id < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Grup secimi gecersiz.', 'hatalar' => []], 422);
            return;
        }

        if (!HaftalikTema::grupSecimSil($id)) {
            Response::json(['basari' => false, 'mesaj' => 'Grup secimi bulunamadi.', 'hatalar' => []], 404);
            return;
        }

        Response::json(['basari' => true, 'mesaj' => 'Grup secimi silindi.', 'veri' => ['id' => $id]]);
    }

    public function sablonKaydet(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            Response::json(['basari' => false, 'mesaj' => 'Sablon basligi zorunludur.', 'hatalar' => ['title' => 'Zorunlu']], 422);
            return;
        }

        $id = EtkinlikSablonu::kaydet((int) ($data['id'] ?? 0), [
            'title' => $title,
            'description' => trim((string) ($data['description'] ?? '')),
            'is_active' => (int) ($data['is_active'] ?? 1),
        ]);

        Response::json(['basari' => true, 'mesaj' => 'Sablon kaydedildi.', 'veri' => ['id' => $id]]);
    }

    public function sablonDurum(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        if ($id < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Sablon secimi gecersiz.', 'hatalar' => []], 422);
            return;
        }

        EtkinlikSablonu::durumDegistir($id, (int) ($data['is_active'] ?? 0) === 1);
        Response::json(['basari' => true, 'mesaj' => 'Sablon durumu guncellendi.', 'veri' => ['id' => $id]]);
    }

    public function sablonSil(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        if ($id < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Sablon secimi gecersiz.', 'hatalar' => []], 422);
            return;
        }

        if (!EtkinlikSablonu::sil($id)) {
            Response::json(['basari' => false, 'mesaj' => 'Sablon bulunamadi.', 'hatalar' => []], 404);
            return;
        }

        Response::json(['basari' => true, 'mesaj' => 'Sablon silindi.', 'veri' => ['id' => $id]]);
    }

    public function ogrenciEtkinlikEkle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $ogrenciId = (int) ($data['student_id'] ?? 0);
        $activityId = (int) ($data['activity_id'] ?? 0);
        $completedAt = trim((string) ($data['completed_at'] ?? date('Y-m-d')));

        if ($ogrenciId < 1 || !Ogrenci::profil($ogrenciId)) {
            Response::json(['basari' => false, 'mesaj' => 'Ogrenci bulunamadi.', 'hatalar' => []], 404);
            return;
        }
        if ($activityId < 1 || !$this->gecerliTarih($completedAt)) {
            Response::json(['basari' => false, 'mesaj' => 'Etkinlik ve tarih secimi zorunludur.', 'hatalar' => []], 422);
            return;
        }

        $id = OgrenciEtkinlikKaydi::ekle($ogrenciId, $activityId, $completedAt);
        Response::json(['basari' => true, 'mesaj' => 'Etkinlik yapildi olarak kaydedildi.', 'veri' => ['id' => $id]]);
    }

    public function ogrenciEtkinlikSil(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        $ogrenciId = (int) ($data['student_id'] ?? 0);
        if ($id < 1 || $ogrenciId < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Kayit secimi gecersiz.', 'hatalar' => []], 422);
            return;
        }

        if (!OgrenciEtkinlikKaydi::sil($id, $ogrenciId)) {
            Response::json(['basari' => false, 'mesaj' => 'Kayit bulunamadi.', 'hatalar' => []], 404);
            return;
        }

        Response::json(['basari' => true, 'mesaj' => 'Etkinlik kaydi silindi.', 'veri' => ['id' => $id]]);
    }

    private function etkinlikleriTemizle(array $activities): array
    {
        $temiz = [];
        foreach ($activities as $etkinlik) {
            if (!is_array($etkinlik)) {
                continue;
            }
            $title = trim((string) ($etkinlik['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $temiz[] = [
                'id' => (int) ($etkinlik['id'] ?? 0),
                'activity_template_id' => (int) ($etkinlik['activity_template_id'] ?? 0),
                'title' => $title,
                'description' => trim((string) ($etkinlik['description'] ?? '')),
                'group_ids' => array_values(array_unique(array_filter(array_map('intval', (array) ($etkinlik['group_ids'] ?? [])), static fn(int $id): bool => $id > 0))),
            ];
        }

        return $temiz;
    }

    private function gecerliTarih(string $tarih): bool
    {
        $parca = \DateTimeImmutable::createFromFormat('Y-m-d', $tarih);
        return $parca instanceof \DateTimeImmutable && $parca->format('Y-m-d') === $tarih;
    }

}
