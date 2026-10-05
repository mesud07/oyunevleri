<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\SecretBox;
use App\Core\Session;
use App\Models\Kullanici;

final class MfaServisi
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function gerekliMi(array $kullanici): bool
    {
        if ((int) ($kullanici['sistem_yoneticisi'] ?? 0) === 1) {
            return true;
        }
        $roller = array_filter(array_map('trim', explode(',', (string) Config::get('MFA_REQUIRED_ROLES', 'kurucu,muhasebe'))));
        return in_array((string) ($kullanici['rol_kodu'] ?? ''), $roller, true);
    }

    public function zorunluMu(): bool
    {
        return Config::bool('MFA_ENFORCE', false);
    }

    public function kayitGerekliMi(array $kullanici): bool
    {
        return $this->zorunluMu() && $this->gerekliMi($kullanici) && (int) ($kullanici['mfa_enabled'] ?? 0) !== 1;
    }

    public function yuksekRiskDogrulandiMi(array $kullanici): bool
    {
        if (!$this->zorunluMu()) {
            return true;
        }
        return (int) ($kullanici['mfa_enabled'] ?? 0) === 1
            && (time() - (int) Session::get('mfa_verified_at', 0)) <= max(300, (int) Config::get('MFA_STEP_UP_SECONDS', 14400));
    }

    public function secretOlustur(): string
    {
        return $this->base32Encode(random_bytes(20));
    }

    public function kodDogrula(string $secret, string $kod): bool
    {
        $kod = preg_replace('/\D+/', '', $kod) ?? '';
        if (!preg_match('/^\d{6}$/', $kod)) {
            return false;
        }
        $counter = intdiv(time(), 30);
        for ($offset = -1; $offset <= 1; $offset++) {
            if (hash_equals($this->totp($secret, $counter + $offset), $kod)) {
                return true;
            }
        }
        return false;
    }

    public function kullaniciDogrula(int $kullaniciId, string $kod): bool
    {
        $kullanici = Kullanici::mfaIcinBul($kullaniciId);
        if (!$kullanici || (int) ($kullanici['mfa_enabled'] ?? 0) !== 1) {
            return false;
        }

        $secret = SecretBox::decrypt((string) $kullanici['mfa_secret_sifreli']);
        if ($this->kodDogrula($secret, $kod)) {
            return true;
        }
        return Kullanici::mfaKurtarmaKoduKullan($kullaniciId, strtoupper(trim($kod)));
    }

    public function etkinlestir(int $kullaniciId, string $secret): array
    {
        $kodlar = [];
        $hashler = [];
        for ($i = 0; $i < 10; $i++) {
            $kod = strtoupper(bin2hex(random_bytes(4)));
            $kod = substr($kod, 0, 4) . '-' . substr($kod, 4, 4);
            $kodlar[] = $kod;
            $hashler[] = password_hash($kod, PASSWORD_DEFAULT);
        }
        Kullanici::mfaEtkinlestir(
            $kullaniciId,
            SecretBox::encrypt($secret),
            SecretBox::encrypt(json_encode($hashler, JSON_THROW_ON_ERROR))
        );
        return $kodlar;
    }

    public function otpauthUri(array $kullanici, string $secret): string
    {
        $issuer = (string) Config::get('MFA_ISSUER', 'Oyun Evleri');
        $label = $issuer . ':' . (string) ($kullanici['eposta'] ?? 'kullanici');
        return 'otpauth://totp/' . rawurlencode($label) . '?secret=' . rawurlencode($secret)
            . '&issuer=' . rawurlencode($issuer) . '&algorithm=SHA1&digits=6&period=30';
    }

    private function totp(string $secret, int $counter): string
    {
        $key = $this->base32Decode($secret);
        $binaryCounter = pack('N2', ($counter >> 32) & 0xffffffff, $counter & 0xffffffff);
        $hash = hash_hmac('sha1', $binaryCounter, $key, true);
        $offset = ord($hash[19]) & 0x0f;
        $value = ((ord($hash[$offset]) & 0x7f) << 24)
            | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8)
            | (ord($hash[$offset + 3]) & 0xff);
        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $data): string
    {
        $bits = '';
        foreach (str_split($data) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        $encoded = '';
        foreach (str_split($bits, 5) as $chunk) {
            $encoded .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }
        return $encoded;
    }

    private function base32Decode(string $encoded): string
    {
        $encoded = strtoupper(preg_replace('/[^A-Z2-7]/', '', $encoded) ?? '');
        $bits = '';
        foreach (str_split($encoded) as $char) {
            $position = strpos(self::ALPHABET, $char);
            if ($position === false) {
                throw new \InvalidArgumentException('MFA anahtarı geçersiz.');
            }
            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }
        $decoded = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $decoded .= chr(bindec($byte));
            }
        }
        return $decoded;
    }
}
