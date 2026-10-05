<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Validator;
use App\Models\GunlukKayit;
use App\Models\Ogrenci;
use App\Models\Randevu;
use App\Services\GozlemRaporuPdfServisi;
use App\Services\GozlemRaporuServisi;
use App\Services\HizSinirlayici;
use App\Services\LogServisi;
use App\Services\YetkiServisi;

final class GunlukKayitController extends Controller
{
    public function sayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        if (!(new YetkiServisi())->izinliMi('yoklama_listele')) {
            http_response_code(403);
            require BASE_PATH . '/resources/views/errors/403.php';
            return;
        }

        $bugun = date('Y-m-d');
        $baslangic = $this->tarihAl($_GET['baslangic'] ?? $bugun, $bugun);
        $bitis = $this->tarihAl($_GET['bitis'] ?? $baslangic, $baslangic);

        if ($bitis < $baslangic) {
            $gecici = $baslangic;
            $baslangic = $bitis;
            $bitis = $gecici;
        }

        $kayitlar = GunlukKayit::liste($baslangic, $bitis);

        $this->view('panel/gunluk-kayitlar', [
            'baslik' => 'Gunluk Notlar',
            'aktif' => 'gunluk-kayitlar',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'baslangic' => $baslangic,
            'bitis' => $bitis,
            'kayitlar' => $kayitlar,
            'ozet' => GunlukKayit::ozet($kayitlar),
            'ogrenciler' => Ogrenci::secenekler(),
            'yapayZekaHazir' => trim((string) Config::get('OPENAI_API_KEY', '')) !== '',
        ], 'panel');
    }

    public function ekle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['randevu_id', 'not_metni']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Randevu ve not zorunludur.', 'hatalar' => $hatalar], 422);
            return;
        }

        $randevu = Randevu::idIleBul((int) $data['randevu_id']);
        if (!$randevu) {
            Response::json(['basari' => false, 'mesaj' => 'Randevu bulunamadi.', 'hatalar' => []], 404);
            return;
        }

        $notMetni = trim((string) $data['not_metni']);
        if ($notMetni === '') {
            Response::json(['basari' => false, 'mesaj' => 'Not metni bos olamaz.', 'hatalar' => ['not_metni' => 'Not yazin.']], 422);
            return;
        }

        $tarih = $this->tarihAl($data['tarih'] ?? ($randevu['tarih'] ?? date('Y-m-d')), (string) ($randevu['tarih'] ?? date('Y-m-d')));
        $id = GunlukKayit::ekle([
            'ogrenci_id' => (int) $randevu['ogrenci_id'],
            'randevu_id' => (int) $randevu['id'],
            'tarih' => $tarih,
            'kategori' => trim((string) ($data['kategori'] ?? 'Genel')) ?: 'Genel',
            'not_metni' => $notMetni,
            'olusturan_kullanici_id' => (int) (Auth::user()['id'] ?? 0),
        ]);

        Response::json(['basari' => true, 'mesaj' => 'Gunluk not kaydedildi.', 'veri' => ['id' => $id]], 201);
    }

    public function raporOlustur(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $ogrenciId = (int) ($data['ogrenci_id'] ?? 0);
        $donem = (int) ($data['donem'] ?? 1);
        if ($ogrenciId < 1 || !in_array($donem, [1, 3], true)) {
            Response::json(['basari' => false, 'mesaj' => 'Ogrenci ve rapor donemi secilmelidir.', 'hatalar' => []], 422);
            return;
        }

        $ogrenci = Ogrenci::raporBilgisi($ogrenciId);
        if (!$ogrenci) {
            Response::json(['basari' => false, 'mesaj' => 'Ogrenci bulunamadi.', 'hatalar' => []], 404);
            return;
        }

        $bitis = new \DateTimeImmutable('today');
        $baslangic = $bitis->sub(new \DateInterval('P' . $donem . 'M'));
        $notlar = GunlukKayit::liste($baslangic->format('Y-m-d'), $bitis->format('Y-m-d'), ['ogrenci_id' => $ogrenciId]);
        if (!$notlar) {
            Response::json(['basari' => false, 'mesaj' => 'Secilen donemde bu ogrenciye ait gunluk not bulunamadi.', 'hatalar' => []], 422);
            return;
        }

        $kullaniciId = (int) (Auth::user()['id'] ?? 0);
        $scope = 'gozlem-raporu:' . Auth::kurumId() . ':' . $kullaniciId;
        $sinirlayici = new HizSinirlayici();
        if (($sinirlayici->engelliMi($scope, 10, 3600)['engelli'] ?? false) === true) {
            Response::json(['basari' => false, 'mesaj' => 'Saatlik rapor olusturma sinirina ulasildi. Lutfen daha sonra tekrar deneyin.', 'hatalar' => []], 429);
            return;
        }
        $sinirlayici->kaydet($scope, 10, 3600, 3600);

        try {
            $rapor = (new GozlemRaporuServisi())->olustur(
                $ogrenci,
                $notlar,
                $baslangic->format('Y-m-d'),
                $bitis->format('Y-m-d')
            );
            (new LogServisi())->yaz('gozlem_raporu_taslagi_olusturuldu', 'Yapay zeka ile ogrenci gozlem raporu taslagi olusturuldu.', [
                'ogrenci_id' => $ogrenciId,
                'donem_ay' => $donem,
                'not_sayisi' => count($notlar),
            ]);
            Response::json(['basari' => true, 'mesaj' => 'Rapor taslagi hazirlandi. PDF oncesi metni kontrol edin.', 'veri' => $rapor]);
        } catch (\InvalidArgumentException $e) {
            Response::json(['basari' => false, 'mesaj' => $e->getMessage(), 'hatalar' => []], 422);
        } catch (\RuntimeException $e) {
            error_log('Gozlem raporu servisi: ' . $e->getMessage());
            $mesaj = str_contains($e->getMessage(), 'OPENAI_API_KEY')
                ? $e->getMessage()
                : 'Rapor su anda olusturulamadi. Lutfen kisa bir sure sonra tekrar deneyin.';
            Response::json(['basari' => false, 'mesaj' => $mesaj, 'hatalar' => []], 503);
        }
    }

    public function raporPdf(): void
    {
        if (!Csrf::dogrula((string) ($_POST['csrf'] ?? ''))) {
            http_response_code(419);
            echo 'Guvenlik dogrulamasi basarisiz.';
            return;
        }

        $payload = json_decode((string) ($_POST['rapor'] ?? ''), true);
        if (!is_array($payload)) {
            http_response_code(422);
            echo 'Rapor verisi gecersiz.';
            return;
        }
        $ogrenciId = (int) ($payload['ogrenci_id'] ?? 0);
        $ogrenci = Ogrenci::raporBilgisi($ogrenciId);
        if (!$ogrenci) {
            http_response_code(422);
            echo 'Rapor verisi gecersiz.';
            return;
        }

        $baslangic = $this->tarihAl($payload['baslangic'] ?? '', '');
        $bitis = $this->tarihAl($payload['bitis'] ?? '', '');
        if ($baslangic === '' || $bitis === '' || $baslangic > $bitis || strtotime($bitis) - strtotime($baslangic) > 370 * 86400) {
            http_response_code(422);
            echo 'Rapor tarih araligi gecersiz.';
            return;
        }

        $izinliBolumler = [
            'genel_gozlem', 'etkinliklere_katilim', 'yonerge_ve_grup_uyumu',
            'cocuk_gelisimci_gorusu', 'psikolojik_danisman_gorusu', 'genel_degerlendirme',
        ];
        $bolumler = [];
        foreach ($izinliBolumler as $anahtar) {
            $bolumler[$anahtar] = mb_substr(trim((string) ($payload['bolumler'][$anahtar] ?? '')), 0, 6000);
            if ($bolumler[$anahtar] === '') {
                http_response_code(422);
                echo 'Rapor bolumleri eksik.';
                return;
            }
        }

        $notlar = GunlukKayit::liste($baslangic, $bitis, ['ogrenci_id' => $ogrenciId]);
        $payload['ogrenci_adi'] = trim((string) $ogrenci['ad'] . ' ' . (string) $ogrenci['soyad']);
        $payload['yas_grubu'] = (new GozlemRaporuServisi())->yasGrubu((string) ($ogrenci['dogum_tarihi'] ?? ''), $bitis);
        $payload['not_sayisi'] = count($notlar);
        $payload['bolumler'] = $bolumler;

        try {
            $pdf = (new GozlemRaporuPdfServisi())->olustur($payload);
            $dosyaAdi = $this->dosyaAdi($payload['ogrenci_adi']) . '-gozlem-raporu.pdf';
            (new LogServisi())->yaz('gozlem_raporu_pdf_indirildi', 'Ogrenci gozlem raporu PDF olarak olusturuldu.', [
                'ogrenci_id' => $ogrenciId,
                'baslangic' => $baslangic,
                'bitis' => $bitis,
            ]);
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: application/pdf');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: private, no-store');
            header('Content-Disposition: attachment; filename="' . $dosyaAdi . '"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf;
        } catch (\Throwable $e) {
            error_log('Gozlem raporu PDF hatasi: ' . $e->getMessage());
            http_response_code(500);
            echo 'PDF su anda olusturulamadi.';
        }
    }

    private function tarihAl($deger, string $varsayilan): string
    {
        $deger = (string) $deger;

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $deger) === 1 ? $deger : $varsayilan;
    }

    private function dosyaAdi(string $deger): string
    {
        $deger = strtr($deger, ['ç' => 'c', 'Ç' => 'C', 'ğ' => 'g', 'Ğ' => 'G', 'ı' => 'i', 'İ' => 'I', 'ö' => 'o', 'Ö' => 'O', 'ş' => 's', 'Ş' => 'S', 'ü' => 'u', 'Ü' => 'U']);
        $deger = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $deger));
        return trim($deger, '-') ?: 'ogrenci';
    }
}
