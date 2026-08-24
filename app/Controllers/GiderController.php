<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Gider;

final class GiderController
{
    public function liste(): void
    {
        $filtre = $this->tarihFiltresi($GLOBALS['talya_ajax_data'] ?? []);

        Response::json([
            'basari' => true,
            'mesaj' => 'Giderler listelendi.',
            'veri' => [
                'filtre' => $filtre,
                'ozet' => Gider::ozet($filtre),
                'kayitlar' => Gider::liste($filtre),
            ],
        ]);
    }

    public function ekle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['tarih', 'tedarikci', 'tutar', 'odeme_turu']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        $kategori = $this->kategori($data);
        if ($kategori === null) {
            return;
        }

        $tekrarTuru = trim((string) ($data['tekrar_turu'] ?? 'tek'));
        $tekrarAdet = $tekrarTuru === 'aylik' ? max(1, min(60, (int) ($data['tekrar_adet'] ?? 12))) : 1;
        $ids = Gider::ekleTekrarli([
            'tarih' => trim((string) $data['tarih']),
            'tedarikci' => trim((string) $data['tedarikci']),
            'kategori' => $kategori,
            'aciklama' => trim((string) ($data['aciklama'] ?? '')),
            'tutar' => (float) $data['tutar'],
            'odeme_turu' => trim((string) $data['odeme_turu']),
            'kasa_id' => (int) ($data['kasa_id'] ?? 0),
            'olusturan_kullanici_id' => (int) (Auth::user()['id'] ?? 0),
        ], $tekrarAdet);

        Response::json([
            'basari' => true,
            'mesaj' => $tekrarAdet > 1 ? $tekrarAdet . ' aylik gider plani olusturuldu.' : 'Gider kaydi olusturuldu.',
            'veri' => ['id' => $ids[0] ?? 0, 'adet' => count($ids)],
        ], 201);
    }

    public function guncelle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['id', 'tarih', 'tedarikci', 'tutar', 'odeme_turu']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        $kategori = $this->kategori($data);
        if ($kategori === null) {
            return;
        }

        $basarili = Gider::guncelle((int) $data['id'], [
            'tarih' => trim((string) $data['tarih']),
            'tedarikci' => trim((string) $data['tedarikci']),
            'kategori' => $kategori,
            'aciklama' => trim((string) ($data['aciklama'] ?? '')),
            'tutar' => (float) $data['tutar'],
            'odeme_turu' => trim((string) $data['odeme_turu']),
            'kasa_id' => (int) ($data['kasa_id'] ?? 0),
        ]);

        Response::json([
            'basari' => $basarili,
            'mesaj' => $basarili ? 'Gider kaydi guncellendi.' : 'Gider bulunamadi.',
            'veri' => ['id' => (int) $data['id']],
        ], $basarili ? 200 : 404);
    }

    public function odendi(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        if ($id < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Gider secilmelidir.', 'hatalar' => []], 422);
            return;
        }

        $basarili = Gider::odendi($id);
        Response::json([
            'basari' => $basarili,
            'mesaj' => $basarili ? 'Gider odendi olarak isaretlendi.' : 'Gider bulunamadi veya zaten islenmis.',
            'veri' => ['id' => $id],
        ], $basarili ? 200 : 404);
    }

    public function sil(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        if ($id < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Silinecek gider secilmelidir.', 'hatalar' => []], 422);
            return;
        }

        $basarili = Gider::sil($id);
        Response::json([
            'basari' => $basarili,
            'mesaj' => $basarili ? 'Gider silindi.' : 'Gider bulunamadi.',
            'veri' => ['id' => $id],
        ], $basarili ? 200 : 404);
    }

    private function kategori(array $data): ?string
    {
        $kategori = trim((string) ($data['kategori'] ?? ''));
        $yeniKategori = trim((string) ($data['yeni_kategori'] ?? ''));
        if ($kategori === '__new') {
            if ($yeniKategori === '') {
                Response::json(['basari' => false, 'mesaj' => 'Yeni kategori adi yazilmalidir.', 'hatalar' => ['yeni_kategori' => 'Yeni kategori adi yazilmalidir.']], 422);
                return null;
            }
            return $yeniKategori;
        }

        return $kategori;
    }

    private function tarihFiltresi(array $data): array
    {
        $tur = trim((string) ($data['tarih_filtresi'] ?? 'bu_ay'));
        $bugun = new \DateTimeImmutable('today');

        if ($tur === 'sonraki_ay') {
            $baslangic = $bugun->modify('first day of next month');
            $bitis = $baslangic->modify('last day of this month');
        } elseif ($tur === 'ozel') {
            $baslangic = $this->tarihDegeri((string) ($data['baslangic_tarihi'] ?? '')) ?? $bugun->modify('first day of this month');
            $bitis = $this->tarihDegeri((string) ($data['bitis_tarihi'] ?? '')) ?? $bugun->modify('last day of this month');
            if ($bitis < $baslangic) {
                [$baslangic, $bitis] = [$bitis, $baslangic];
            }
        } else {
            $tur = 'bu_ay';
            $baslangic = $bugun->modify('first day of this month');
            $bitis = $bugun->modify('last day of this month');
        }

        return [
            'tarih_filtresi' => $tur,
            'baslangic_tarihi' => $baslangic->format('Y-m-d'),
            'bitis_tarihi' => $bitis->format('Y-m-d'),
        ];
    }

    private function tarihDegeri(string $tarih): ?\DateTimeImmutable
    {
        $tarih = trim($tarih);
        if ($tarih === '') {
            return null;
        }

        $deger = \DateTimeImmutable::createFromFormat('!Y-m-d', $tarih);
        return $deger instanceof \DateTimeImmutable ? $deger : null;
    }
}
