<?php
declare(strict_types=1);

namespace App\Services;

final class NetgsmServisi
{
    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? require BASE_PATH . '/config/sms.php';
    }

    public function smsGonder(array $mesajlar): array
    {
        $netgsm = $this->config['netgsm'];
        if (!$this->config['enabled'] || $this->config['test_mode']) {
            return [
                'basarili' => true,
                'gecici_hata' => false,
                'islem_no' => 'TEST-' . date('YmdHis'),
                'cevap' => $this->config['enabled'] ? 'SMS test modunda; NetGSM API cagrisi yapilmadi.' : 'SMS kapali; NetGSM API cagrisi yapilmadi.',
            ];
        }

        foreach (['usercode', 'password', 'header'] as $alan) {
            if (trim((string) ($netgsm[$alan] ?? '')) === '') {
                return [
                    'basarili' => false,
                    'gecici_hata' => false,
                    'islem_no' => null,
                    'cevap' => 'NetGSM ayarlari eksik: ' . $alan,
                ];
            }
        }

        $body = [
            'msgheader' => $netgsm['header'],
            'messages' => array_map(static fn(array $m): array => [
                'msg' => $m['mesaj'],
                'no' => $m['telefon'],
            ], $mesajlar),
            'encoding' => $netgsm['encoding'],
            'iysfilter' => $netgsm['filter'],
            'appname' => 'Oyun Evleri Yönetim Sistemi',
        ];

        $url = $netgsm['base_url'] . $netgsm['send_path'];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $netgsm['usercode'] . ':' . $netgsm['password'],
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_CONNECTTIMEOUT => (int) $netgsm['connect_timeout'],
            CURLOPT_TIMEOUT => (int) $netgsm['timeout'],
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        if (PHP_VERSION_ID < 80500) {
            curl_close($ch);
        }

        if ($response === false || $curlError !== '') {
            return [
                'basarili' => false,
                'gecici_hata' => true,
                'islem_no' => null,
                'cevap' => 'cURL hata: ' . $curlError,
            ];
        }

        $parsed = $this->yanitCoz((string) $response);
        if ($httpCode >= 500 || $httpCode === 429) {
            $parsed['gecici_hata'] = true;
        }
        if ($httpCode >= 400 && $httpCode < 500) {
            $parsed['basarili'] = false;
        }

        return $parsed + ['cevap' => (string) $response];
    }

    public function durumSorgula(string $islemNo): array
    {
        if (!$this->config['enabled'] || $this->config['test_mode']) {
            return ['durum' => 'bilinmiyor', 'cevap' => 'Test modu; durum sorgulanmadi.'];
        }

        $netgsm = $this->config['netgsm'];
        $url = $netgsm['base_url'] . $netgsm['report_path'];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $netgsm['usercode'] . ':' . $netgsm['password'],
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS => json_encode(['jobid' => $islemNo], JSON_UNESCAPED_UNICODE),
            CURLOPT_CONNECTTIMEOUT => (int) $netgsm['connect_timeout'],
            CURLOPT_TIMEOUT => (int) $netgsm['timeout'],
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        if (PHP_VERSION_ID < 80500) {
            curl_close($ch);
        }

        if ($response === false || $error !== '') {
            return ['durum' => 'bilinmiyor', 'cevap' => 'cURL hata: ' . $error];
        }

        $metin = (string) $response;
        $lower = mb_strtolower($metin);
        if (str_contains($lower, 'deliv') || str_contains($lower, 'teslim') || str_contains($lower, '00')) {
            return ['durum' => 'teslim_edildi', 'cevap' => $metin];
        }
        if (str_contains($lower, 'fail') || str_contains($lower, 'hata')) {
            return ['durum' => 'basarisiz', 'cevap' => $metin];
        }
        return ['durum' => 'bilinmiyor', 'cevap' => $metin];
    }

    public function mesajBasliklari(): array
    {
        $netgsm = $this->config['netgsm'];
        if (trim((string) $netgsm['usercode']) === '' || trim((string) $netgsm['password']) === '') {
            return ['basarili' => false, 'basliklar' => [], 'cevap' => 'NetGSM kullanici bilgileri eksik.'];
        }

        $ch = curl_init($netgsm['base_url'] . '/sms/rest/v2/msgheader');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $netgsm['usercode'] . ':' . $netgsm['password'],
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_CONNECTTIMEOUT => (int) $netgsm['connect_timeout'],
            CURLOPT_TIMEOUT => (int) $netgsm['timeout'],
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        if (PHP_VERSION_ID < 80500) {
            curl_close($ch);
        }

        if ($response === false || $error !== '') {
            return ['basarili' => false, 'basliklar' => [], 'cevap' => $error];
        }

        $json = json_decode((string) $response, true);
        if (is_array($json) && (string) ($json['code'] ?? '') === '00') {
            return ['basarili' => true, 'basliklar' => $json['msgheaders'] ?? [], 'cevap' => (string) $response];
        }

        return ['basarili' => false, 'basliklar' => [], 'cevap' => (string) $response];
    }

    private function yanitCoz(string $response): array
    {
        $json = json_decode($response, true);
        if (is_array($json)) {
            $code = (string) ($json['code'] ?? $json['status'] ?? '');
            $jobId = (string) ($json['jobid'] ?? $json['job_id'] ?? $json['jobId'] ?? '');
            return [
                'basarili' => $code === '00' || $code === '0' || $jobId !== '',
                'gecici_hata' => in_array($code, ['50', '51', '80', '85'], true),
                'islem_no' => $jobId !== '' ? $jobId : null,
                'cevap' => $response,
            ];
        }

        $trimmed = trim($response);
        $parts = preg_split('/\s+/', $trimmed) ?: [];
        $code = (string) ($parts[0] ?? '');
        return [
            'basarili' => $code === '00',
            'gecici_hata' => in_array($code, ['50', '51', '80', '85'], true),
            'islem_no' => $code === '00' ? ($parts[1] ?? null) : null,
            'cevap' => $this->hataMesaji($code, $trimmed),
        ];
    }

    private function hataMesaji(string $code, string $cevap): string
    {
        $harita = [
            '20' => 'Eksik veya hatali parametre.',
            '30' => 'Kullanici adi, sifre veya IP yetkisi hatali.',
            '40' => 'Mesaj basligi yetkisiz veya hatali.',
            '50' => 'Hesap kredisi yetersiz.',
            '70' => 'Sorgu/istek hatali.',
        ];
        return $harita[$code] ?? $cevap;
    }
}
