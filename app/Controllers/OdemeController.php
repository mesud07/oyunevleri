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
use App\Models\Ayar;
use App\Services\SmsServisi;
use App\Services\TahsilatAnalizServisi;
use App\Services\TahsilatExcelServisi;
use App\Services\LogServisi;

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
        $bugun = date('Y-m-d');
        $beklenenOdemeTakvimi = [];
        foreach ($borcluPaketler as $borc) {
            $beklenenTarih = (string) ($borc['beklenen_odeme_tarihi'] ?? '');
            if ($beklenenTarih === '' || $beklenenTarih < $bugun) {
                continue;
            }
            $beklenenOdemeTakvimi[] = [
                'paket_id' => (int) ($borc['paket_id'] ?? 0),
                'tarih' => $beklenenTarih,
                'ogrenci' => (string) ($borc['ogrenci'] ?? ''),
                'paket_adi' => (string) ($borc['paket_adi'] ?? ''),
                'kalan_borc' => (float) ($borc['kalan_borc'] ?? 0),
                'tahsilat_notu' => (string) ($borc['tahsilat_notu'] ?? ''),
            ];
        }
        usort($beklenenOdemeTakvimi, static fn(array $a, array $b): int => [$a['tarih'], $a['ogrenci']] <=> [$b['tarih'], $b['ogrenci']]);

        $this->view('panel/odeme-borclular', [
            'baslik' => 'Mevcut Borclular',
            'aktif' => 'odemeler-borclular',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'paketler' => Paket::secenekler(),
            'kasalar' => Kasa::secenekler(),
            'borcluPaketler' => $borcluPaketler,
            'toplamKalanBorc' => $toplamKalanBorc,
            'beklenenOdemeTakvimi' => $beklenenOdemeTakvimi,
        ], 'panel');
    }

    public function tahsilatlarSayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $analizAy = $this->gecerliAnalizAyi($_GET['ay'] ?? date('Y-m'));
        $analizDonem = $this->gecerliAnalizDonemi($_GET['donem'] ?? 'ay');
        $analizAraligi = TahsilatAnalizServisi::donemAraligi($analizAy, $analizDonem);
        $analizVerileri = Odeme::analizVerileri($analizAraligi['baslangic'], $analizAraligi['bitis']);
        $kdvYontemleri = $this->kdvYontemleri();

        $this->view('panel/tahsilatlar', [
            'baslik' => 'Tahsilatlar',
            'aktif' => 'odemeler-tahsilatlar',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'paketler' => Paket::secenekler(),
            'kasalar' => Kasa::secenekler(),
            'tahsilatOzetleri' => Odeme::tahsilatOzetleri(),
            'tahsilatAnalizi' => (new TahsilatAnalizServisi())->olustur($analizAy, $analizVerileri, $kdvYontemleri, $analizAraligi),
            'analizAy' => $analizAy,
            'analizDonem' => $analizDonem,
            'kdvYontemleri' => $kdvYontemleri,
        ], 'panel');
    }

    public function tahsilatlarExcel(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $ay = $this->gecerliAnalizAyi($_GET['ay'] ?? date('Y-m'));
        $donem = $this->gecerliAnalizDonemi($_GET['donem'] ?? 'ay');
        $yontem = $this->gecerliOdemeYontemi($_GET['yontem'] ?? '');
        $aralik = TahsilatAnalizServisi::donemAraligi($ay, $donem);
        if ($yontem !== '') {
            $aralik['dosya_eki'] .= '-' . $yontem;
            $aralik['etiket'] .= ' · ' . $this->odemeYontemiEtiketi($yontem);
        }
        $analiz = (new TahsilatAnalizServisi())->olustur(
            $ay,
            Odeme::analizVerileri($aralik['baslangic'], $aralik['bitis'], $yontem),
            $this->kdvYontemleri(),
            $aralik
        );
        $dosya = (new TahsilatExcelServisi())->olustur(
            $analiz,
            Odeme::tarihAraligiTahsilatlari($aralik['baslangic'], $aralik['bitis'], $yontem),
            (string) (Auth::user()['kurum_adi'] ?? 'Oyun Evleri')
        );
        (new LogServisi())->yaz('tahsilat_excel_indirildi', 'Tahsilat Excel raporu indirildi.', ['ay' => $ay, 'donem' => $donem, 'yontem' => $yontem]);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: ' . $dosya['mime']);
        header('Content-Disposition: attachment; filename="' . basename((string) $dosya['name']) . '"');
        header('Content-Length: ' . strlen((string) $dosya['content']));
        header('Cache-Control: private, no-store');
        echo $dosya['content'];
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
            'veri' => Odeme::liste(
                $sayfa,
                $limit,
                trim((string) ($data['yontem'] ?? '')),
                trim((string) ($data['siralama'] ?? 'tarih_desc')),
                trim((string) ($data['ay'] ?? ''))
            ),
        ]);
    }

    public function kdvAyarlariKaydet(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $yontemler = is_array($data['yontemler'] ?? null) ? $data['yontemler'] : [];
        $yontemler = TahsilatAnalizServisi::kdvYontemleriniNormalize($yontemler);
        Ayar::kaydet(
            'tahsilat_kdv_yontemleri',
            json_encode($yontemler, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'KDV hesabina dahil edilen tahsilat yontemleri.'
        );

        Response::json([
            'basari' => true,
            'mesaj' => 'KDV hesaplama yöntemleri kaydedildi.',
            'veri' => ['yontemler' => $yontemler],
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

    private function gecerliAnalizAyi(mixed $deger): string
    {
        $ay = is_string($deger) ? trim($deger) : '';
        return preg_match('/^20\d{2}-(?:0[1-9]|1[0-2])$/', $ay) ? $ay : date('Y-m');
    }

    private function gecerliAnalizDonemi(mixed $deger): string
    {
        $donem = is_string($deger) ? trim($deger) : '';
        return in_array($donem, TahsilatAnalizServisi::DONEMLER, true) ? $donem : 'ay';
    }

    private function gecerliOdemeYontemi(mixed $deger): string
    {
        $yontem = is_string($deger) ? trim($deger) : '';
        return in_array($yontem, ['nakit', 'kredi_karti', 'havale', 'odeme_baglantisi', 'diger'], true) ? $yontem : '';
    }

    private function odemeYontemiEtiketi(string $yontem): string
    {
        return match ($yontem) {
            'nakit' => 'Nakit',
            'kredi_karti' => 'Kredi Kartı',
            'havale' => 'Havale / EFT',
            'odeme_baglantisi' => 'Ödeme Bağlantısı',
            default => 'Diğer',
        };
    }

    private function kdvYontemleri(): array
    {
        $kayitli = Ayar::deger('tahsilat_kdv_yontemleri');
        if ($kayitli === null || trim($kayitli) === '') {
            return TahsilatAnalizServisi::VARSAYILAN_KDV_YONTEMLERI;
        }
        $cozulmus = json_decode($kayitli, true);
        return TahsilatAnalizServisi::kdvYontemleriniNormalize(is_array($cozulmus) ? $cozulmus : []);
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
