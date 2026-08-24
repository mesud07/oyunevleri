<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Grup;
use App\Models\Ogrenci;
use App\Models\Rapor;

final class GrupController extends Controller
{
    public function sayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        Grup::varsayilanProgramHazirla();

        $this->view('panel/gruplar', [
            'baslik' => 'Gruplar',
            'aktif' => 'gruplar',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
        ], 'panel');
    }

    public function kontenjanlarSayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $this->view('panel/grup-kontenjanlari', [
            'baslik' => 'Grup Kontenjanlari',
            'aktif' => 'gruplar-kontenjanlar',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'grupKontenjanlari' => Rapor::grupKontenjanlari(),
        ], 'panel');
    }

    public function liste(): void
    {
        Response::json(['basari' => true, 'mesaj' => 'Gruplar listelendi.', 'veri' => Grup::liste()]);
    }

    public function programListe(): void
    {
        Response::json(['basari' => true, 'mesaj' => 'Ders programi listelendi.', 'veri' => Grup::programListe()]);
    }

    public function bosKontenjanTakvimi(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $baslangic = $this->tarihDegeri((string) ($data['baslangic_tarihi'] ?? '')) ?: date('Y-m-d', strtotime('monday this week'));
        $bitis = $this->tarihDegeri((string) ($data['bitis_tarihi'] ?? '')) ?: date('Y-m-d', strtotime('sunday this week'));

        if (strtotime($bitis) < strtotime($baslangic)) {
            [$baslangic, $bitis] = [$bitis, $baslangic];
        }

        $maksimumBitis = date('Y-m-d', strtotime($baslangic . ' +60 days'));
        if (strtotime($bitis) > strtotime($maksimumBitis)) {
            $bitis = $maksimumBitis;
        }

        Response::json([
            'basari' => true,
            'mesaj' => 'Bos kontenjan takvimi hazir.',
            'veri' => Grup::bosKontenjanTakvimi($baslangic, $bitis),
            'meta' => [
                'baslangic_tarihi' => $baslangic,
                'bitis_tarihi' => $bitis,
            ],
        ]);
    }

    public function randevulariSenkronizeEt(): void
    {
        Response::json([
            'basari' => true,
            'mesaj' => 'Randevular mevcut grup programlariyla senkronize edildi.',
            'veri' => Grup::randevulariSenkronizeEt(),
        ]);
    }

    public function ekle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['ad']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        $id = Grup::ekle([
            'ad' => trim((string) $data['ad']),
            'yas_araligi' => trim((string) ($data['yas_araligi'] ?? '')),
            'kontenjan' => (int) ($data['kontenjan'] ?? 8),
            'aktif' => (int) ($data['aktif'] ?? 1),
            'aciklama' => trim((string) ($data['aciklama'] ?? '')),
        ]);

        Response::json(['basari' => true, 'mesaj' => 'Grup kaydi olusturuldu.', 'veri' => ['id' => $id]], 201);
    }

    public function programEkle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['gun', 'baslangic_saati', 'bitis_saati', 'yas_araligi', 'program_adi']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        $id = Grup::programEkle($this->programVerisi($data));
        Response::json(['basari' => true, 'mesaj' => 'Ders programi satiri eklendi.', 'veri' => ['id' => $id]], 201);
    }

    public function programGuncelle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['id', 'gun', 'baslangic_saati', 'bitis_saati', 'yas_araligi', 'program_adi']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        $basarili = Grup::programGuncelle((int) $data['id'], $this->programVerisi($data));
        Response::json([
            'basari' => $basarili,
            'mesaj' => $basarili ? 'Ders programi satiri guncellendi.' : 'Ders programi satiri bulunamadi.',
            'veri' => ['id' => (int) $data['id']],
        ], $basarili ? 200 : 404);
    }

    public function programSil(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        if ($id < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Silinecek satir secilmelidir.', 'hatalar' => []], 422);
            return;
        }

        $basarili = Grup::programSil($id);
        Response::json([
            'basari' => $basarili,
            'mesaj' => $basarili ? 'Ders programi satiri silindi.' : 'Ders programi satiri bulunamadi.',
            'veri' => ['id' => $id],
        ], $basarili ? 200 : 404);
    }

    public function ogrenciSecenekleri(): void
    {
        Response::json(['basari' => true, 'mesaj' => 'Ogrenciler listelendi.', 'veri' => Ogrenci::secenekler()]);
    }

    public function grupOgrencileri(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $grupId = (int) ($data['grup_id'] ?? 0);
        if ($grupId < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Grup secilmelidir.', 'hatalar' => []], 422);
            return;
        }

        Response::json(['basari' => true, 'mesaj' => 'Grup ogrencileri listelendi.', 'veri' => Grup::grupOgrencileri($grupId)]);
    }

    public function aylikTakip(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $grupId = (int) ($data['grup_id'] ?? 0);
        $ay = trim((string) ($data['ay'] ?? date('Y-m')));
        if ($grupId < 1 || !preg_match('/^\d{4}-\d{2}$/', $ay)) {
            Response::json(['basari' => false, 'mesaj' => 'Grup ve ay secilmelidir.', 'hatalar' => []], 422);
            return;
        }

        Response::json(['basari' => true, 'mesaj' => 'Aylik grup takibi listelendi.', 'veri' => Grup::aylikTakip($grupId, $ay)]);
    }

    public function ogrenciAta(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $grupId = (int) ($data['grup_id'] ?? 0);
        $grupIds = $data['grup_ids'] ?? [];
        $ogrenciId = (int) ($data['ogrenci_id'] ?? 0);
        if (!is_array($grupIds)) {
            $grupIds = [];
        }
        if ($grupId > 0) {
            $grupIds[] = $grupId;
        }
        $grupIds = array_values(array_unique(array_filter(array_map('intval', $grupIds), static fn(int $id): bool => $id > 0)));

        if (!$grupIds || $ogrenciId < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Grup ve ogrenci secilmelidir.', 'hatalar' => []], 422);
            return;
        }

        $adet = Grup::ogrenciAtaCoklu($grupIds, $ogrenciId);
        Response::json([
            'basari' => true,
            'mesaj' => $adet > 1 ? 'Ogrenci secilen gruplara atandi.' : 'Ogrenci gruba atandi.',
            'veri' => ['grup_ids' => $grupIds, 'ogrenci_id' => $ogrenciId, 'adet' => $adet],
        ]);
    }

    public function ogrenciCikar(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $grupId = (int) ($data['grup_id'] ?? 0);
        $ogrenciId = (int) ($data['ogrenci_id'] ?? 0);
        if ($grupId < 1 || $ogrenciId < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Grup ve ogrenci secilmelidir.', 'hatalar' => []], 422);
            return;
        }

        Grup::ogrenciCikar($grupId, $ogrenciId);
        Response::json(['basari' => true, 'mesaj' => 'Ogrenci gruptan cikarildi.', 'veri' => ['grup_id' => $grupId, 'ogrenci_id' => $ogrenciId]]);
    }

    private function programVerisi(array $data): array
    {
        return [
            'gun' => max(1, min(7, (int) $data['gun'])),
            'baslangic_saati' => trim((string) $data['baslangic_saati']),
            'bitis_saati' => trim((string) $data['bitis_saati']),
            'yas_araligi' => trim((string) $data['yas_araligi']),
            'program_adi' => trim((string) $data['program_adi']),
            'durum' => trim((string) ($data['durum'] ?? 'durum_yok')),
            'kontenjan' => (int) ($data['kontenjan'] ?? 8),
        ];
    }

    private function tarihDegeri(string $tarih): ?string
    {
        $tarih = trim($tarih);
        if ($tarih === '') {
            return null;
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $tarih);
        if (!$dt || $dt->format('Y-m-d') !== $tarih) {
            return null;
        }

        return $dt->format('Y-m-d');
    }
}
