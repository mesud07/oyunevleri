<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Kurum;

final class KurumController extends Controller
{
    public function sayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }
        if (!Auth::sistemYoneticisiMi()) {
            http_response_code(403);
            require BASE_PATH . '/resources/views/errors/403.php';
            return;
        }

        $this->view('panel/kurumlar', [
            'baslik' => 'Kurumlar',
            'aktif' => 'kurumlar',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
        ], 'panel');
    }

    public function liste(): void
    {
        Response::json([
            'basari' => true,
            'mesaj' => 'Kurumlar listelendi.',
            'veri' => Kurum::liste(),
        ]);
    }

    public function kaydet(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        $hatalar = Validator::gerekli($data, ['ad', 'kod']);
        if ($hatalar) {
            Response::json([
                'basari' => false,
                'mesaj' => 'Kurum adi ve kodu zorunludur.',
                'hatalar' => $hatalar,
            ], 422);
            return;
        }

        $kod = strtoupper(trim((string) $data['kod']));
        if (!preg_match('/^[A-Z0-9_-]{2,30}$/', $kod)) {
            Response::json([
                'basari' => false,
                'mesaj' => 'Kurum kodu 2-30 karakter olmali; harf, rakam, tire veya alt cizgi icermelidir.',
                'hatalar' => ['kod' => 'Gecersiz kurum kodu.'],
            ], 422);
            return;
        }
        if (Kurum::kodVarMi($kod, $id)) {
            Response::json([
                'basari' => false,
                'mesaj' => 'Bu kurum kodu zaten kullaniliyor.',
                'hatalar' => ['kod' => 'Kurum kodu kullaniliyor.'],
            ], 422);
            return;
        }

        $mevcut = $id > 0 ? Kurum::idIleBul($id) : null;
        if ($id > 0 && !$mevcut) {
            Response::json(['basari' => false, 'mesaj' => 'Kurum bulunamadi.', 'hatalar' => []], 404);
            return;
        }

        $kurucuGerekli = $id < 1 || !Kurum::kurucuVarMi($id);
        $kurucu = null;
        if ($kurucuGerekli) {
            $kurucuVerisi = [
                'ad' => trim((string) ($data['kurucu_ad'] ?? '')),
                'soyad' => trim((string) ($data['kurucu_soyad'] ?? '')),
                'eposta' => trim((string) ($data['kurucu_eposta'] ?? '')),
                'telefon' => trim((string) ($data['kurucu_telefon'] ?? '')),
                'sifre' => (string) ($data['kurucu_sifre'] ?? ''),
            ];
            $kurucuHatalari = Validator::gerekli($kurucuVerisi, ['ad', 'soyad', 'eposta', 'sifre']);
            if ($kurucuHatalari) {
                Response::json([
                    'basari' => false,
                    'mesaj' => 'Kurucu kullanici bilgileri zorunludur.',
                    'hatalar' => $kurucuHatalari,
                ], 422);
                return;
            }
            if (!preg_match('/^[A-Za-z0-9._@-]{3,190}$/', $kurucuVerisi['eposta'])) {
                Response::json([
                    'basari' => false,
                    'mesaj' => 'Gecerli bir kurucu kullanici adi veya e-posta yazin.',
                    'hatalar' => ['kurucu_eposta' => 'Gecersiz giris bilgisi.'],
                ], 422);
                return;
            }
            if (strlen($kurucuVerisi['sifre']) < 8) {
                Response::json([
                    'basari' => false,
                    'mesaj' => 'Kurucu sifresi en az 8 karakter olmalidir.',
                    'hatalar' => ['kurucu_sifre' => 'Sifre kisa.'],
                ], 422);
                return;
            }
            $kurucu = $kurucuVerisi;
        }

        $aktif = (int) ($data['aktif'] ?? 0) === 1 ? 1 : 0;
        if ($id === Auth::kurumId() && $aktif !== 1) {
            Response::json([
                'basari' => false,
                'mesaj' => 'Oturum acik olan kurum pasife alinamaz.',
                'hatalar' => ['aktif' => 'Bu kurum aktif kalmalidir.'],
            ], 422);
            return;
        }

        try {
            $sonuc = Kurum::kurucuIleKaydet($id, [
                'ad' => trim((string) $data['ad']),
                'kod' => $kod,
                'aktif' => $aktif,
            ], $kurucu);
        } catch (\Throwable $e) {
            Response::json(['basari' => false, 'mesaj' => $e->getMessage(), 'hatalar' => []], 422);
            return;
        }

        Response::json([
            'basari' => true,
            'mesaj' => $id > 0
                ? ($kurucu !== null ? 'Kurum guncellendi ve kurucu kullanici olusturuldu.' : 'Kurum guncellendi.')
                : 'Kurum ve kurucu kullanici olusturuldu.',
            'veri' => ['id' => $sonuc['kurum_id'], 'kurucu_id' => $sonuc['kurucu_id']],
        ], $id > 0 ? 200 : 201);
    }

    public function logoYukle(): void
    {
        if (!Auth::check()) {
            Response::json(['basari' => false, 'mesaj' => 'Oturum gerekli.', 'hatalar' => []], 401);
            return;
        }
        if (!Auth::sistemYoneticisiMi()) {
            Response::json(['basari' => false, 'mesaj' => 'Bu işlem için yetkiniz yok.', 'hatalar' => []], 403);
            return;
        }
        if (!Csrf::dogrula((string) ($_POST['csrf'] ?? ''))) {
            Response::json(['basari' => false, 'mesaj' => 'Güvenlik doğrulaması başarısız.', 'hatalar' => []], 419);
            return;
        }

        $kurumId = (int) ($_POST['kurum_id'] ?? 0);
        if ($kurumId < 1 || !Kurum::idIleBul($kurumId)) {
            Response::json(['basari' => false, 'mesaj' => 'Kurum bulunamadı.', 'hatalar' => []], 404);
            return;
        }

        $dosya = $_FILES['logo'] ?? null;
        if (!is_array($dosya) || (int) ($dosya['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Response::json(['basari' => false, 'mesaj' => 'Yüklenecek logo dosyasını seçin.', 'hatalar' => ['logo' => 'Dosya seçilmedi.']], 422);
            return;
        }
        if ((int) ($dosya['size'] ?? 0) < 1 || (int) $dosya['size'] > 2 * 1024 * 1024) {
            Response::json(['basari' => false, 'mesaj' => 'Logo en fazla 2 MB olabilir.', 'hatalar' => ['logo' => 'Dosya boyutu uygun değil.']], 422);
            return;
        }

        $geciciYol = (string) ($dosya['tmp_name'] ?? '');
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($geciciYol);
        $uzantilar = ['image/png' => 'png', 'image/jpeg' => 'jpg'];
        if (!isset($uzantilar[$mime])) {
            Response::json(['basari' => false, 'mesaj' => 'Yalnızca PNG veya JPG logo yükleyebilirsiniz.', 'hatalar' => ['logo' => 'Dosya türü desteklenmiyor.']], 422);
            return;
        }

        $boyutlar = @getimagesize($geciciYol);
        if (!$boyutlar || (int) $boyutlar[0] < 1 || (int) $boyutlar[1] < 1 || (int) $boyutlar[0] > 4000 || (int) $boyutlar[1] > 4000) {
            Response::json(['basari' => false, 'mesaj' => 'Logo boyutları en fazla 4000×4000 piksel olabilir.', 'hatalar' => ['logo' => 'Görsel boyutları uygun değil.']], 422);
            return;
        }

        $goreliDizin = '/uploads/kurum-logolari';
        $hedefDizin = BASE_PATH . '/public' . $goreliDizin;
        if (!is_dir($hedefDizin) && !mkdir($hedefDizin, 0775, true) && !is_dir($hedefDizin)) {
            Response::json(['basari' => false, 'mesaj' => 'Logo dizini oluşturulamadı.', 'hatalar' => []], 500);
            return;
        }

        $dosyaAdi = 'kurum-' . $kurumId . '-' . bin2hex(random_bytes(8)) . '.' . $uzantilar[$mime];
        $goreliYol = $goreliDizin . '/' . $dosyaAdi;
        $hedefYol = $hedefDizin . '/' . $dosyaAdi;
        if (!@move_uploaded_file($geciciYol, $hedefYol)) {
            Response::json(['basari' => false, 'mesaj' => 'Logo sunucuya kaydedilemedi.', 'hatalar' => []], 500);
            return;
        }

        try {
            $eskiLogo = Kurum::logoGuncelle($kurumId, $goreliYol);
        } catch (\Throwable $e) {
            @unlink($hedefYol);
            throw $e;
        }

        if ($eskiLogo && str_starts_with($eskiLogo, $goreliDizin . '/')) {
            $eskiTamYol = BASE_PATH . '/public' . $eskiLogo;
            if (is_file($eskiTamYol) && realpath(dirname($eskiTamYol)) === realpath($hedefDizin)) {
                @unlink($eskiTamYol);
            }
        }

        Response::json([
            'basari' => true,
            'mesaj' => 'Kurum logosu yüklendi.',
            'veri' => ['logo_yolu' => $goreliYol],
        ]);
    }
}
