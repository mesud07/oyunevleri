<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
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

            $servis = new SmsServisi();
            $servis->randevuHatirlatmalariOlustur();
            $servis->dogumGunuMesajlariOlustur();
            $servis->kuyrukIsle(100);
            $servis->durumlariSorgula(50);
        } catch (Throwable $e) {
            error_log('[sms-otomasyon] ' . $e->getMessage());
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
