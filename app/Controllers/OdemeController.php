<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Gider;
use App\Models\Kasa;
use App\Models\Odeme;
use App\Models\Paket;
use App\Models\Rapor;
use App\Services\SmsServisi;

final class OdemeController extends Controller
{
    public function sayfa(): void
    {
        Response::redirect('/panel/odemeler/borclular');
    }

    public function borclularSayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $borcluPaketler = Odeme::borcluPaketler();
        $toplamKalanBorc = 0.0;
        foreach ($borcluPaketler as $borc) {
            $toplamKalanBorc += (float) ($borc['kalan_borc'] ?? 0);
        }

        $this->view('panel/odeme-borclular', [
            'baslik' => 'Mevcut Borclular',
            'aktif' => 'odemeler-borclular',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'paketler' => Paket::secenekler(),
            'kasalar' => Kasa::secenekler(),
            'borcluPaketler' => $borcluPaketler,
            'toplamKalanBorc' => $toplamKalanBorc,
        ], 'panel');
    }

    public function tahsilatlarSayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $this->view('panel/tahsilatlar', [
            'baslik' => 'Tahsilatlar',
            'aktif' => 'odemeler-tahsilatlar',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'paketler' => Paket::secenekler(),
            'kasalar' => Kasa::secenekler(),
            'tahsilatOzetleri' => Odeme::tahsilatOzetleri(),
            'yaklasanTahsilatlar' => Rapor::yaklasanTahsilatlar(),
            'gecikmisTahsilatlar' => Rapor::gecikmisTahsilatlar(),
        ], 'panel');
    }

    public function tahsilatTakibiSayfa(): void
    {
        Response::redirect('/panel/odemeler/tahsilatlar');
    }

    public function giderlerSayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $this->view('panel/giderler', [
            'baslik' => 'Yapilacak Odemeler',
            'aktif' => 'odemeler-giderler',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'giderKategorileri' => Gider::kategoriler(),
            'kasalar' => Kasa::secenekler(),
        ], 'panel');
    }

    public function liste(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $sayfa = max(1, (int) ($data['sayfa'] ?? 1));
        $limit = max(10, min(100, (int) ($data['limit'] ?? 20)));
        Response::json([
            'basari' => true,
            'mesaj' => 'Odemeler listelendi.',
            'veri' => Odeme::liste($sayfa, $limit),
        ]);
    }

    public function ekle(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['paket_id', 'tarih', 'tutar', 'yontem']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Eksik alanlar var.', 'hatalar' => $hatalar], 422);
            return;
        }

        try {
            $kullanici = Auth::user();
            $paketId = (int) $data['paket_id'];
            $tutar = $this->paraDegeri($data['tutar'], 'Tahsilat tutari');
            $paketTutariGuncellendi = false;
            if ((string) ($data['paket_tutari_guncelle'] ?? '') === '1') {
                if (!array_key_exists('yeni_paket_tutari', $data) || trim((string) $data['yeni_paket_tutari']) === '') {
                    throw new \RuntimeException('Yeni toplam odenecek tutar girilmelidir.');
                }
                $yeniPaketTutari = $this->paraDegeri($data['yeni_paket_tutari'], 'Yeni toplam odenecek tutar');
                Paket::tutarGuncelle(
                    $paketId,
                    $yeniPaketTutari,
                    (int) ($kullanici['id'] ?? 0)
                );
                $paketTutariGuncellendi = true;
            }

            $id = Odeme::ekle([
                'paket_id' => $paketId,
                'veli_id' => (int) ($data['veli_id'] ?? 0),
                'tarih' => trim((string) $data['tarih']),
                'tutar' => $tutar,
                'yontem' => trim((string) $data['yontem']),
                'kasa_id' => (int) ($data['kasa_id'] ?? 0),
                'makbuz_numarasi' => trim((string) ($data['makbuz_numarasi'] ?? '')),
                'aciklama' => trim((string) ($data['aciklama'] ?? '')),
                'alan_kullanici_id' => (int) ($kullanici['id'] ?? 0),
            ]);
            if ((string) ($data['odeme_sms_gonder'] ?? '') === '1') {
                try {
                    $smsServisi = new SmsServisi();
                    $smsServisi->odemeAlindi($id);
                    $smsServisi->kuyrukIsle(20);
                } catch (\Throwable $e) {
                    error_log('Odeme SMS kuyrugu olusturulamadi veya islenemedi: ' . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            Response::json(['basari' => false, 'mesaj' => $e->getMessage(), 'hatalar' => []], 422);
            return;
        }

        Response::json([
            'basari' => true,
            'mesaj' => $paketTutariGuncellendi ? 'Paket tutari guncellendi ve odeme kaydi olusturuldu.' : 'Odeme kaydi olusturuldu.',
            'veri' => ['id' => $id],
        ], 201);
    }

    public function odemeYapilmadiKapat(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $paketId = (int) ($data['paket_id'] ?? ($data['id'] ?? 0));
        if ($paketId < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Kapatilacak paket secilmelidir.', 'hatalar' => []], 422);
            return;
        }

        try {
            $kullanici = Auth::user();
            $sonuc = Paket::odemeYapilmadiKapat(
                $paketId,
                (int) ($kullanici['id'] ?? 0),
                trim((string) ($data['neden'] ?? ''))
            );
            if (!$sonuc) {
                Response::json(['basari' => false, 'mesaj' => 'Paket bulunamadi.', 'hatalar' => []], 404);
                return;
            }
        } catch (\Throwable $e) {
            Response::json(['basari' => false, 'mesaj' => $e->getMessage(), 'hatalar' => []], 422);
            return;
        }

        Response::json(['basari' => true, 'mesaj' => (string) $sonuc['mesaj'], 'veri' => $sonuc]);
    }

    private function paraDegeri($deger, string $alan): float
    {
        $ham = trim((string) $deger);
        if ($ham === '') {
            throw new \RuntimeException($alan . ' bos birakilamaz.');
        }

        $normalize = str_replace(["\xc2\xa0", ' '], '', $ham);
        if (str_contains($normalize, ',') && str_contains($normalize, '.')) {
            $normalize = str_replace('.', '', $normalize);
            $normalize = str_replace(',', '.', $normalize);
        } elseif (str_contains($normalize, ',')) {
            $normalize = str_replace(',', '.', $normalize);
        }

        if (!is_numeric($normalize)) {
            throw new \RuntimeException($alan . ' gecerli bir para tutari olmalidir.');
        }

        $tutar = round((float) $normalize, 2);
        if ($tutar < 0) {
            throw new \RuntimeException($alan . ' sifirdan kucuk olamaz.');
        }

        return $tutar;
    }

    public function geriAl(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        if ($id < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Tahsilat secilmelidir.', 'hatalar' => []], 422);
            return;
        }

        $basarili = Odeme::geriAl($id, trim((string) ($data['iptal_nedeni'] ?? '')));
        Response::json([
            'basari' => $basarili,
            'mesaj' => $basarili ? 'Tahsilat geri alindi.' : 'Tahsilat bulunamadi veya zaten geri alinmis.',
            'veri' => ['id' => $id],
        ], $basarili ? 200 : 404);
    }

    public function kasayaAktar(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        $kasaId = (int) ($data['kasa_id'] ?? 0);
        if ($id < 1 || $kasaId < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Tahsilat ve kasa secilmelidir.', 'hatalar' => []], 422);
            return;
        }

        $basarili = Odeme::kasayaAktar($id, $kasaId);
        Response::json([
            'basari' => $basarili,
            'mesaj' => $basarili ? 'Tahsilat kasaya aktarildi.' : 'Tahsilat bulunamadi veya zaten bu kasada.',
            'veri' => ['id' => $id, 'kasa_id' => $kasaId],
        ], $basarili ? 200 : 404);
    }

    public function sil(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        if ($id < 1) {
            Response::json(['basari' => false, 'mesaj' => 'Silinecek tahsilat secilmelidir.', 'hatalar' => []], 422);
            return;
        }

        try {
            $basarili = Odeme::sil($id);
            Response::json([
                'basari' => $basarili,
                'mesaj' => $basarili ? 'Tahsilat silindi.' : 'Tahsilat bulunamadi.',
                'veri' => ['id' => $id],
            ], $basarili ? 200 : 404);
        } catch (\Throwable $e) {
            Response::json(['basari' => false, 'mesaj' => 'Bagli kaydi olan tahsilat silinemedi. Geri alma islemini kullanin.', 'hatalar' => []], 409);
        }
    }
}
