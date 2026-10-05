<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Models\Personel;
use App\Services\BordroHesaplamaServisi;
use App\Services\LogServisi;
use App\Services\PuantajExcelServisi;
use PDOException;

final class PersonelController extends Controller
{
    public function sayfa(): void
    {
        $ay = $this->gecerliAy((string) ($_GET['ay'] ?? date('Y-m')));
        $tarih = $this->gecerliTarih((string) ($_GET['tarih'] ?? date('Y-m-d')));
        $aylik = Personel::aylikVeri($ay);
        $bordro = (new BordroHesaplamaServisi())->hesapla($ay, $aylik['personeller'], Personel::yillikPuantajKayitlari((int) substr($ay, 0, 4)));
        $this->view('panel/personel-puantaj', [
            'baslik' => 'Personel Puantaj',
            'aktif' => 'personel-puantaj',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'ay' => $ay,
            'tarih' => $tarih,
            'personeller' => Personel::liste(),
            'kullaniciSecenekleri' => Personel::kullaniciSecenekleri(),
            'gunluk' => Personel::gunlukListe($tarih),
            'aylik' => $aylik,
            'bordro' => $bordro,
            'durumlar' => Personel::DURUMLAR,
            'sgkTesvikler' => BordroHesaplamaServisi::TESVIKLER,
        ], 'panel');
    }

    public function liste(): void
    {
        Response::json(['basari' => true, 'mesaj' => 'Personeller listelendi.', 'veri' => Personel::liste()]);
    }

    public function kaydet(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = max(0, (int) ($data['id'] ?? 0));
        $ad = trim((string) ($data['ad'] ?? ''));
        $soyad = trim((string) ($data['soyad'] ?? ''));
        $kullaniciId = max(0, (int) ($data['kullanici_id'] ?? 0));
        $tc = preg_replace('/\D+/', '', (string) ($data['tc_kimlik_no'] ?? '')) ?? '';
        $aylikBrutMetni = str_replace(',', '.', trim((string) ($data['aylik_brut_ucret'] ?? '0')));
        $aylikBrut = is_numeric($aylikBrutMetni) ? (float) $aylikBrutMetni : -1;
        $sgkTesvikTuru = (string) ($data['sgk_tesvik_turu'] ?? 'diger_2_puan');
        if ($ad === '' || $soyad === '') {
            Response::json(['basari' => false, 'mesaj' => 'Ad ve soyad zorunludur.', 'hatalar' => []], 422);
            return;
        }
        if ($tc !== '' && strlen($tc) !== 11) {
            Response::json(['basari' => false, 'mesaj' => 'T.C. kimlik numarası 11 haneli olmalıdır.', 'hatalar' => ['tc_kimlik_no' => '11 hane yazın.']], 422);
            return;
        }
        if ($aylikBrut < 0 || $aylikBrut > 99999999.99) {
            Response::json(['basari' => false, 'mesaj' => 'Aylık brüt ücret geçersiz.', 'hatalar' => ['aylik_brut_ucret' => '0 ile 99.999.999,99 arasında bir tutar yazın.']], 422);
            return;
        }
        if (!isset(BordroHesaplamaServisi::TESVIKLER[$sgkTesvikTuru])) {
            Response::json(['basari' => false, 'mesaj' => 'SGK teşvik türü geçersiz.', 'hatalar' => ['sgk_tesvik_turu' => 'Geçerli bir teşvik seçin.']], 422);
            return;
        }
        if ($id > 0 && !Personel::kurumPersoneliMi($id)) {
            Response::json(['basari' => false, 'mesaj' => 'Personel bulunamadı.', 'hatalar' => []], 404);
            return;
        }
        if (!Personel::kullaniciKurumdaMi($kullaniciId)) {
            Response::json(['basari' => false, 'mesaj' => 'Seçilen kullanıcı bu kuruma ait değil.', 'hatalar' => []], 422);
            return;
        }

        foreach (['ise_giris_tarihi', 'isten_cikis_tarihi'] as $alan) {
            if (($data[$alan] ?? '') !== '' && !$this->tarihMi((string) $data[$alan])) {
                Response::json(['basari' => false, 'mesaj' => 'Personel tarihi geçersiz.', 'hatalar' => [$alan => 'Geçersiz tarih.']], 422);
                return;
            }
        }
        foreach (['varsayilan_giris_saati', 'varsayilan_cikis_saati'] as $alan) {
            if (($data[$alan] ?? '') !== '' && !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', (string) $data[$alan])) {
                Response::json(['basari' => false, 'mesaj' => 'Çalışma saati geçersiz.', 'hatalar' => [$alan => 'SS:DD biçiminde yazın.']], 422);
                return;
            }
        }
        $iseGiris = (string) ($data['ise_giris_tarihi'] ?? '');
        $istenCikis = (string) ($data['isten_cikis_tarihi'] ?? '');
        if ($iseGiris !== '' && $istenCikis !== '' && $istenCikis < $iseGiris) {
            Response::json(['basari' => false, 'mesaj' => 'İşten çıkış tarihi işe giriş tarihinden önce olamaz.', 'hatalar' => ['isten_cikis_tarihi' => 'Tarih sırasını kontrol edin.']], 422);
            return;
        }

        try {
            $personelId = Personel::kaydet($id, [
                'kullanici_id' => $kullaniciId,
                'tc_kimlik_no' => $tc,
                'ad' => mb_substr($ad, 0, 120),
                'soyad' => mb_substr($soyad, 0, 120),
                'pozisyon' => mb_substr(trim((string) ($data['pozisyon'] ?? '')), 0, 160),
                'aylik_brut_ucret' => $aylikBrut,
                'sgk_tesvik_turu' => $sgkTesvikTuru,
                'ise_giris_tarihi' => (string) ($data['ise_giris_tarihi'] ?? ''),
                'isten_cikis_tarihi' => (string) ($data['isten_cikis_tarihi'] ?? ''),
                'varsayilan_giris_saati' => (string) ($data['varsayilan_giris_saati'] ?? ''),
                'varsayilan_cikis_saati' => (string) ($data['varsayilan_cikis_saati'] ?? ''),
                'aktif' => (int) ($data['aktif'] ?? 1) === 1 ? 1 : 0,
                'notlar' => mb_substr(trim((string) ($data['notlar'] ?? '')), 0, 2000),
            ]);
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                Response::json(['basari' => false, 'mesaj' => 'T.C. kimlik numarası veya bağlı kullanıcı başka bir personelde kayıtlı.', 'hatalar' => []], 422);
                return;
            }
            throw $e;
        }

        (new LogServisi())->yaz($id > 0 ? 'personel_guncellendi' : 'personel_olusturuldu', 'Personel kartı kaydedildi.', ['personel_id' => $personelId]);
        Response::json(['basari' => true, 'mesaj' => 'Personel kaydedildi.', 'veri' => ['id' => $personelId]], $id > 0 ? 200 : 201);
    }

    public function puantajKaydet(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $personelId = (int) ($data['personel_id'] ?? 0);
        $tarih = (string) ($data['tarih'] ?? '');
        $durum = (string) ($data['durum'] ?? '');
        if ($personelId < 1 || !Personel::kurumPersoneliMi($personelId)) {
            Response::json(['basari' => false, 'mesaj' => 'Personel bulunamadı.', 'hatalar' => []], 404);
            return;
        }
        if (!$this->tarihMi($tarih) || !isset(Personel::DURUMLAR[$durum])) {
            Response::json(['basari' => false, 'mesaj' => 'Tarih veya puantaj durumu geçersiz.', 'hatalar' => []], 422);
            return;
        }
        foreach (['giris_saati', 'cikis_saati'] as $alan) {
            if (($data[$alan] ?? '') !== '' && !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', (string) $data[$alan])) {
                Response::json(['basari' => false, 'mesaj' => 'Giriş/çıkış saati geçersiz.', 'hatalar' => [$alan => 'SS:DD biçiminde yazın.']], 422);
                return;
            }
        }
        Personel::puantajKaydet([
            'personel_id' => $personelId,
            'tarih' => $tarih,
            'durum' => $durum,
            'giris_saati' => (string) ($data['giris_saati'] ?? ''),
            'cikis_saati' => (string) ($data['cikis_saati'] ?? ''),
            'mola_dakika' => (int) ($data['mola_dakika'] ?? 0),
            'aciklama' => mb_substr(trim((string) ($data['aciklama'] ?? '')), 0, 500),
        ], (int) (Auth::user()['id'] ?? 0) ?: null);
        (new LogServisi())->yaz('personel_puantaj_guncellendi', 'Personel puantaj kaydı güncellendi.', ['personel_id' => $personelId, 'tarih' => $tarih, 'durum' => $durum]);
        Response::json(['basari' => true, 'mesaj' => 'Puantaj kaydedildi.', 'veri' => []]);
    }

    public function puantajTopluKaydet(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $durum = (string) ($data['durum'] ?? '');
        $gelenKayitlar = $data['kayitlar'] ?? null;
        if (!isset(Personel::DURUMLAR[$durum]) || !is_array($gelenKayitlar) || count($gelenKayitlar) < 1 || count($gelenKayitlar) > 500) {
            Response::json(['basari' => false, 'mesaj' => 'Toplu puantaj seçimi geçersiz veya 500 kayıt sınırını aşıyor.', 'hatalar' => []], 422);
            return;
        }

        $kayitlar = [];
        $personelIds = [];
        foreach ($gelenKayitlar as $kayit) {
            if (!is_array($kayit)) {
                Response::json(['basari' => false, 'mesaj' => 'Toplu puantaj kaydı geçersiz.', 'hatalar' => []], 422);
                return;
            }
            $personelId = (int) ($kayit['personel_id'] ?? 0);
            $tarih = (string) ($kayit['tarih'] ?? '');
            if ($personelId < 1 || !$this->tarihMi($tarih)) {
                Response::json(['basari' => false, 'mesaj' => 'Personel veya tarih bilgisi geçersiz.', 'hatalar' => []], 422);
                return;
            }
            $anahtar = $personelId . ':' . $tarih;
            $kayitlar[$anahtar] = ['personel_id' => $personelId, 'tarih' => $tarih];
            $personelIds[$personelId] = $personelId;
        }

        if (!Personel::kurumPersonelleriMi(array_values($personelIds))) {
            Response::json(['basari' => false, 'mesaj' => 'Seçimde kuruma ait olmayan personel bulundu.', 'hatalar' => []], 404);
            return;
        }

        $adet = Personel::puantajTopluKaydet(array_values($kayitlar), $durum, (int) (Auth::user()['id'] ?? 0) ?: null);
        (new LogServisi())->yaz('personel_puantaj_toplu_guncellendi', 'Personel puantaj kayıtları toplu güncellendi.', ['adet' => $adet, 'durum' => $durum]);
        Response::json(['basari' => true, 'mesaj' => $adet . ' puantaj kaydı güncellendi.', 'veri' => ['adet' => $adet]]);
    }

    public function hareketKaydet(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $personelId = (int) ($data['personel_id'] ?? 0);
        $tur = (string) ($data['tur'] ?? '');
        if ($personelId < 1 || !Personel::kurumPersoneliMi($personelId)) {
            Response::json(['basari' => false, 'mesaj' => 'Personel bulunamadı.', 'hatalar' => []], 404);
            return;
        }
        if (!in_array($tur, ['giris', 'cikis'], true)) {
            Response::json(['basari' => false, 'mesaj' => 'Hareket türü geçersiz.', 'hatalar' => []], 422);
            return;
        }
        Personel::hareketKaydet($personelId, $tur, (int) (Auth::user()['id'] ?? 0) ?: null);
        (new LogServisi())->yaz('personel_' . $tur . '_kaydi', 'Personel ' . ($tur === 'giris' ? 'giriş' : 'çıkış') . ' saati kaydedildi.', ['personel_id' => $personelId]);
        Response::json(['basari' => true, 'mesaj' => ucfirst($tur) . ' saati kaydedildi.', 'veri' => []]);
    }

    public function excel(): void
    {
        $ay = $this->gecerliAy((string) ($_GET['ay'] ?? date('Y-m')));
        $aylik = Personel::aylikVeri($ay);
        $aylik['bordro'] = (new BordroHesaplamaServisi())->hesapla($ay, $aylik['personeller'], Personel::yillikPuantajKayitlari((int) substr($ay, 0, 4)));
        $dosya = (new PuantajExcelServisi())->olustur($aylik, (string) (Auth::user()['kurum_adi'] ?? 'Oyun Evleri'));
        (new LogServisi())->yaz('personel_puantaj_excel_indirildi', 'Aylık personel puantaj raporu indirildi.', ['ay' => $ay]);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: ' . $dosya['mime']);
        header('Content-Disposition: attachment; filename="' . basename((string) $dosya['name']) . '"');
        header('Content-Length: ' . strlen((string) $dosya['content']));
        header('Cache-Control: private, no-store');
        echo $dosya['content'];
    }

    private function gecerliAy(string $ay): string
    {
        return preg_match('/^20\d{2}-(?:0[1-9]|1[0-2])$/', $ay) ? $ay : date('Y-m');
    }

    private function gecerliTarih(string $tarih): string
    {
        return $this->tarihMi($tarih) ? $tarih : date('Y-m-d');
    }

    private function tarihMi(string $tarih): bool
    {
        if (!preg_match('/^(?:19|20)\d{2}-\d{2}-\d{2}$/', $tarih)) {
            return false;
        }
        [$yil, $ay, $gun] = array_map('intval', explode('-', $tarih));
        return checkdate($ay, $gun, $yil);
    }
}
