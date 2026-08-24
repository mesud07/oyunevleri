<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Response;
use App\Models\IslemKaydi;
use App\Models\Ogrenci;
use App\Models\OnamFormu;
use Dompdf\Dompdf;
use Dompdf\Options;

final class OnamFormuController
{
    public function olustur(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $ogrenciId = (int) ($data['ogrenci_id'] ?? 0);
        $profil = $ogrenciId > 0 ? Ogrenci::profil($ogrenciId) : null;

        if (!$profil) {
            Response::json(['basari' => false, 'mesaj' => 'Öğrenci bulunamadı.', 'hatalar' => []], 404);
            return;
        }

        $alanlar = [
            'ogrenci_ad_soyad' => trim((string) ($data['ogrenci_ad_soyad'] ?? '')),
            'ogrenci_tc_kimlik_no' => trim((string) ($data['ogrenci_tc_kimlik_no'] ?? '')),
            'ogrenci_dogum_tarihi' => trim((string) ($data['ogrenci_dogum_tarihi'] ?? '')),
            'ogrenci_telefon' => trim((string) ($data['ogrenci_telefon'] ?? '')),
            'veli_ad_soyad' => trim((string) ($data['veli_ad_soyad'] ?? '')),
            'veli_tc_kimlik_no' => trim((string) ($data['veli_tc_kimlik_no'] ?? '')),
            'veli_yakinlik' => trim((string) ($data['veli_yakinlik'] ?? '')),
            'personel_unvan' => trim((string) ($data['personel_unvan'] ?? '')),
            'personel_ad_soyad' => trim((string) ($data['personel_ad_soyad'] ?? '')),
            'form_tarihi' => trim((string) ($data['form_tarihi'] ?? '')),
        ];

        $hatalar = [];
        foreach (['ogrenci_ad_soyad', 'veli_ad_soyad', 'personel_unvan', 'personel_ad_soyad', 'form_tarihi'] as $alan) {
            if ($alanlar[$alan] === '') {
                $hatalar[$alan] = 'Bu alan zorunludur.';
            }
        }
        if ($alanlar['form_tarihi'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $alanlar['form_tarihi'])) {
            $hatalar['form_tarihi'] = 'Geçerli bir form tarihi girin.';
        }
        if ((int) ($data['bilgiler_dogrulandi'] ?? 0) !== 1) {
            $hatalar['bilgiler_dogrulandi'] = 'Bilgilerin doğruluğunu onaylamalısınız.';
        }
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik veya hatalı alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        $veliId = (int) ($data['veli_id'] ?? 0);
        $profilVeliIdleri = array_map(static fn(array $veli): int => (int) $veli['id'], $profil['veliler'] ?? []);
        if ($veliId > 0 && !in_array($veliId, $profilVeliIdleri, true)) {
            Response::json(['basari' => false, 'mesaj' => 'Seçilen veli bu öğrenciye bağlı değil.', 'hatalar' => []], 422);
            return;
        }

        $kullanici = Auth::user() ?? [];
        $id = OnamFormu::olustur([
            ...$alanlar,
            'ogrenci_id' => $ogrenciId,
            'veli_id' => $veliId,
            'olusturan_kullanici_id' => (int) ($kullanici['id'] ?? 0),
        ]);

        IslemKaydi::ekle(
            (int) ($kullanici['id'] ?? 0) ?: null,
            'onam_formu_olusturuldu',
            OnamFormu::FORM_ADI . ' oluşturuldu.',
            ['onam_formu_id' => $id, 'ogrenci_id' => $ogrenciId]
        );

        Response::json([
            'basari' => true,
            'mesaj' => 'Onam formu oluşturuldu.',
            'veri' => [
                'id' => $id,
                'pdf_url' => '/panel/onam-formlari/pdf?id=' . $id,
                'indir_url' => '/panel/onam-formlari/pdf?id=' . $id . '&indir=1',
            ],
        ], 201);
    }

    public function pdf(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }
        if (!yetki_var('ogrenci_listele')) {
            http_response_code(403);
            require BASE_PATH . '/resources/views/errors/403.php';
            return;
        }

        $id = (int) ($_GET['id'] ?? 0);
        $form = $id > 0 ? OnamFormu::bul($id) : null;
        if (!$form) {
            http_response_code(404);
            require BASE_PATH . '/resources/views/errors/404.php';
            return;
        }

        $logoDataUri = null;
        $logoPdfWidth = 0;
        $logoPdfHeight = 0;
        $logoYolu = (string) ($form['kurum_logo_yolu'] ?? '');
        if (str_starts_with($logoYolu, '/uploads/kurum-logolari/')) {
            $logoTamYol = BASE_PATH . '/public' . $logoYolu;
            if (is_file($logoTamYol)) {
                $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($logoTamYol);
                if (in_array($mime, ['image/png', 'image/jpeg'], true)) {
                    $icerik = file_get_contents($logoTamYol);
                    if ($icerik !== false) {
                        $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode($icerik);
                        $boyutlar = @getimagesize($logoTamYol);
                        if ($boyutlar && (int) $boyutlar[0] > 0 && (int) $boyutlar[1] > 0) {
                            $oran = min(190 / (int) $boyutlar[0], 50 / (int) $boyutlar[1]);
                            $logoPdfWidth = max(1, (int) round((int) $boyutlar[0] * $oran));
                            $logoPdfHeight = max(1, (int) round((int) $boyutlar[1] * $oran));
                        }
                    }
                }
            }
        }

        ob_start();
        require BASE_PATH . '/resources/views/pdf/gorsel-icerik-onam.php';
        $html = (string) ob_get_clean();

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $ad = preg_replace('/[^a-z0-9]+/i', '-', self::ascii((string) $form['ogrenci_ad_soyad'])) ?: 'ogrenci';
        $dosyaAdi = trim($ad, '-') . '-gorsel-icerik-onam-formu.pdf';
        $dompdf->stream($dosyaAdi, ['Attachment' => isset($_GET['indir']) ? 1 : 0]);
    }

    private static function ascii(string $metin): string
    {
        return strtr($metin, [
            'ç' => 'c', 'Ç' => 'C', 'ğ' => 'g', 'Ğ' => 'G', 'ı' => 'i', 'İ' => 'I',
            'ö' => 'o', 'Ö' => 'O', 'ş' => 's', 'Ş' => 'S', 'ü' => 'u', 'Ü' => 'U',
        ]);
    }
}
