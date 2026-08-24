<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Veritabani;
use App\Models\Rapor;
use DateTimeImmutable;
use PDO;

final class PanelController extends Controller
{
    public function genelBakis(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $db = Veritabani::baglan();
        $kurumId = Auth::kurumId();
        $dogumGunleri = $this->haftalikDogumGunleri($db);
        $veliStmt = $db->prepare('SELECT COUNT(*) FROM veliler WHERE kurum_id = :kurum_id');
        $veliStmt->execute(['kurum_id' => $kurumId]);
        $grupStmt = $db->prepare('SELECT COUNT(*) FROM gruplar WHERE kurum_id = :kurum_id AND aktif = 1');
        $grupStmt->execute(['kurum_id' => $kurumId]);
        $randevuStmt = $db->prepare('SELECT COUNT(*) FROM randevular WHERE kurum_id = :kurum_id AND tarih = CURDATE()');
        $randevuStmt->execute(['kurum_id' => $kurumId]);
        $ozet = [
            'ogrenci' => Rapor::aktifGrupOgrenciSayisi(),
            'veli' => (int) $veliStmt->fetchColumn(),
            'grup' => (int) $grupStmt->fetchColumn(),
            'randevu' => (int) $randevuStmt->fetchColumn(),
            'dogum_gunu' => count($dogumGunleri),
        ];

        $this->view('panel/genel-bakis', [
            'baslik' => 'Genel Bakis',
            'aktif' => 'genel-bakis',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'ozet' => $ozet,
            'rapor' => Rapor::sayfaVerisi(),
            'dogumGunleri' => $dogumGunleri,
        ], 'panel');
    }

    private function haftalikDogumGunleri(PDO $db): array
    {
        $baslangic = new DateTimeImmutable('monday this week');
        $bitis = $baslangic->modify('+6 days');
        $baslangicMd = (int) $baslangic->format('md');
        $bitisMd = (int) $bitis->format('md');

        $kosul = 'CAST(DATE_FORMAT(dogum_tarihi, "%m%d") AS UNSIGNED) BETWEEN :baslangic_md AND :bitis_md';
        if ($baslangicMd > $bitisMd) {
            $kosul = '(CAST(DATE_FORMAT(dogum_tarihi, "%m%d") AS UNSIGNED) >= :baslangic_md OR CAST(DATE_FORMAT(dogum_tarihi, "%m%d") AS UNSIGNED) <= :bitis_md)';
        }

        $stmt = $db->prepare(
            'SELECT id, CONCAT(ad, " ", soyad) AS ad_soyad, dogum_tarihi
             FROM ogrenciler
             WHERE kurum_id = :kurum_id
               AND dogum_tarihi IS NOT NULL
               AND durum = "aktif"
               AND ' . $kosul . '
             ORDER BY CAST(DATE_FORMAT(dogum_tarihi, "%m%d") AS UNSIGNED) ASC, ad ASC, soyad ASC'
        );
        $stmt->execute([
            'kurum_id' => Auth::kurumId(),
            'baslangic_md' => $baslangicMd,
            'bitis_md' => $bitisMd,
        ]);

        $yil = (int) date('Y');
        return array_map(static function (array $row) use ($yil): array {
            $dogum = new DateTimeImmutable((string) $row['dogum_tarihi']);
            $dogumGunu = DateTimeImmutable::createFromFormat('Y-m-d', sprintf('%04d-%s', $yil, $dogum->format('m-d'))) ?: $dogum;
            return [
                'id' => (int) $row['id'],
                'ad_soyad' => (string) $row['ad_soyad'],
                'dogum_tarihi' => (string) $row['dogum_tarihi'],
                'dogum_gunu' => $dogumGunu->format('d.m.Y'),
                'yas' => max(0, $yil - (int) $dogum->format('Y')),
            ];
        }, $stmt->fetchAll());
    }
}
