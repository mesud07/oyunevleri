<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Session;
use App\Models\Ayar;
use App\Models\Kurum;
use Throwable;

final class SmsOtomasyonCalistirici
{
    private const CALISMA_ARALIGI_SANIYE = 600;

    public static function webIstegindenCalistir(): void
    {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if (
            PHP_SAPI === 'cli'
                || !Config::bool('SMS_WEB_AUTOMATION_ENABLED', true)
            || str_starts_with($host, 'localhost')
            || str_starts_with($host, '127.0.0.1')
        ) {
            return;
        }

        $storage = BASE_PATH . '/storage';
        if (!is_dir($storage)) {
            @mkdir($storage, 0775, true);
        }

        $sonCalismaDosyasi = $storage . '/sms-otomasyon.last';
        $sonCalisma = is_file($sonCalismaDosyasi) ? (int) @file_get_contents($sonCalismaDosyasi) : 0;
        if ($sonCalisma > 0 && time() - $sonCalisma < self::CALISMA_ARALIGI_SANIYE) {
            return;
        }

        $lock = @fopen($storage . '/sms-otomasyon.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            return;
        }

        try {
            @file_put_contents($sonCalismaDosyasi, (string) time());

            self::tumKurumlarIcinCalistir(100, true, 'web-yedek');
        } catch (Throwable $e) {
            error_log('[sms-otomasyon] ' . $e->getMessage());
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public static function tumKurumlarIcinCalistir(
        int $limit = 100,
        bool $durumlariSorgula = true,
        string $kaynak = 'cron'
    ): array {
        $oncekiKurumId = (int) Session::get('kurum_id', 0);
        $sonuclar = [];

        try {
            foreach (Kurum::aktifIdler() as $kurumId) {
                Session::set('kurum_id', $kurumId);
                $baslangic = microtime(true);
                try {
                    $servis = new SmsServisi();
                    $sonuc = [
                        'kurum_id' => $kurumId,
                        'randevu_hatirlatmasi' => $servis->randevuHatirlatmalariOlustur(),
                        'dogum_gunu_mesaji' => $servis->dogumGunuMesajlariOlustur(),
                        'kuyruk' => $servis->kuyrukIsle(max(1, $limit)),
                    ];
                    if ($durumlariSorgula) {
                        $sonuc['durum_guncellemesi'] = $servis->durumlariSorgula(max(1, $limit));
                    }
                    $sonuc['sure_ms'] = (int) round((microtime(true) - $baslangic) * 1000);
                    self::calismaDurumunuKaydet('basarili', $kaynak, $sonuc);
                    $sonuclar[] = $sonuc;
                } catch (Throwable $e) {
                    $sonuc = [
                        'kurum_id' => $kurumId,
                        'hata' => $e->getMessage(),
                        'sure_ms' => (int) round((microtime(true) - $baslangic) * 1000),
                    ];
                    try {
                        self::calismaDurumunuKaydet('basarisiz', $kaynak, $sonuc);
                    } catch (Throwable $kayitHatasi) {
                        error_log('[sms-otomasyon-kayit] ' . $kayitHatasi->getMessage());
                    }
                    $sonuclar[] = $sonuc;
                }
            }
        } finally {
            if ($oncekiKurumId > 0) {
                Session::set('kurum_id', $oncekiKurumId);
            } else {
                Session::remove('kurum_id');
            }
        }

        return $sonuclar;
    }

    private static function calismaDurumunuKaydet(string $durum, string $kaynak, array $sonuc): void
    {
        Ayar::kaydetCoklu([
            'sms_automation_last_run_at' => date('Y-m-d H:i:s'),
            'sms_automation_last_status' => $durum,
            'sms_automation_last_source' => $kaynak,
            'sms_automation_last_result' => json_encode($sonuc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
        ], [
            'sms_automation_last_run_at' => 'SMS otomasyonunun son calisma tarihi.',
            'sms_automation_last_status' => 'SMS otomasyonunun son calisma durumu.',
            'sms_automation_last_source' => 'SMS otomasyonunu calistiran kaynak.',
            'sms_automation_last_result' => 'SMS otomasyonunun son ozet sonucu.',
        ]);
    }
}
