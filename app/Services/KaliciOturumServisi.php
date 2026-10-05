<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Request;
use App\Core\Veritabani;
use App\Models\Kullanici;

final class KaliciOturumServisi
{
    private static bool $tabloHazir = false;

    public function olustur(array $kullanici): void
    {
        try {
            $this->tabloyuHazirla();
            $this->mevcutBelirteciSil(false);

            $secici = bin2hex(random_bytes(16));
            $dogrulayici = bin2hex(random_bytes(32));
            $sonaErme = time() + ($this->gecerlilikGunu() * 86400);
            $db = Veritabani::baglan();
            $stmt = $db->prepare(
                'INSERT INTO kalici_oturumlar
                 (kurum_id, kullanici_id, secici, dogrulayici_hash, oturum_surumu, sona_erme_tarihi, son_kullanim_tarihi, olusturulma_tarihi)
                 VALUES (:kurum_id, :kullanici_id, :secici, :dogrulayici_hash, :oturum_surumu, :sona_erme_tarihi, NOW(), NOW())'
            );
            $stmt->execute([
                'kurum_id' => (int) $kullanici['kurum_id'],
                'kullanici_id' => (int) $kullanici['id'],
                'secici' => $secici,
                'dogrulayici_hash' => hash('sha256', $dogrulayici),
                'oturum_surumu' => (int) ($kullanici['oturum_surumu'] ?? 1),
                'sona_erme_tarihi' => date('Y-m-d H:i:s', $sonaErme),
            ]);
            $this->fazlaBelirtecleriTemizle((int) $kullanici['id']);
            $this->cookieYaz($secici . '.' . $dogrulayici, $sonaErme);
        } catch (\Throwable $e) {
            error_log('Kalici oturum olusturulamadi: ' . get_class($e) . ' ' . $e->getMessage());
            $this->cookieTemizle();
        }
    }

    public function kullaniciyiGetir(): ?array
    {
        $cookie = $_COOKIE[$this->cookieAdi()] ?? null;
        $parcalar = self::cookieAyir(is_string($cookie) ? $cookie : '');
        if ($parcalar === null) {
            if ($cookie !== null) {
                $this->cookieTemizle();
            }
            return null;
        }

        [$secici, $dogrulayici] = $parcalar;
        $db = Veritabani::baglan();
        try {
            $this->tabloyuHazirla();
            $db->beginTransaction();
            $stmt = $db->prepare(
                'SELECT id, kurum_id, kullanici_id, dogrulayici_hash, oturum_surumu,
                        UNIX_TIMESTAMP(sona_erme_tarihi) AS sona_erme
                 FROM kalici_oturumlar
                 WHERE secici = :secici
                 LIMIT 1 FOR UPDATE'
            );
            $stmt->execute(['secici' => $secici]);
            $kayit = $stmt->fetch();

            if (!$kayit || (int) ($kayit['sona_erme'] ?? 0) <= time()) {
                if ($kayit) {
                    $this->idIleSil((int) $kayit['id'], $db);
                }
                $db->commit();
                $this->cookieTemizle();
                return null;
            }

            $gelenHash = hash('sha256', $dogrulayici);
            if (!hash_equals((string) $kayit['dogrulayici_hash'], $gelenHash)) {
                $this->idIleSil((int) $kayit['id'], $db);
                $db->commit();
                $this->cookieTemizle();
                return null;
            }

            $kullanici = Kullanici::idIleBul((int) $kayit['kullanici_id']);
            if (!$kullanici
                || (int) $kullanici['kurum_id'] !== (int) $kayit['kurum_id']
                || (int) ($kullanici['oturum_surumu'] ?? 0) !== (int) $kayit['oturum_surumu']) {
                $this->idIleSil((int) $kayit['id'], $db);
                $db->commit();
                $this->cookieTemizle();
                return null;
            }

            // Uzun ömürlü anahtar her otomatik girişte tek kullanımlık olarak döndürülür.
            $yeniSecici = bin2hex(random_bytes(16));
            $yeniDogrulayici = bin2hex(random_bytes(32));
            $yenile = $db->prepare(
                'UPDATE kalici_oturumlar
                 SET secici = :secici, dogrulayici_hash = :dogrulayici_hash, son_kullanim_tarihi = NOW()
                 WHERE id = :id'
            );
            $yenile->execute([
                'id' => (int) $kayit['id'],
                'secici' => $yeniSecici,
                'dogrulayici_hash' => hash('sha256', $yeniDogrulayici),
            ]);
            $db->commit();
            $this->cookieYaz($yeniSecici . '.' . $yeniDogrulayici, (int) $kayit['sona_erme']);

            return $kullanici;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Kalici oturum dogrulanamadi: ' . get_class($e) . ' ' . $e->getMessage());
            $this->cookieTemizle();
            return null;
        }
    }

    public function iptalEt(): void
    {
        try {
            $this->tabloyuHazirla();
            $this->mevcutBelirteciSil(false);
        } catch (\Throwable $e) {
            error_log('Kalici oturum iptal edilemedi: ' . get_class($e) . ' ' . $e->getMessage());
        }
        $this->cookieTemizle();
    }

    public static function cookieAyir(string $deger): ?array
    {
        if (!preg_match('/^([a-f0-9]{32})\.([a-f0-9]{64})$/D', $deger, $eslesme)) {
            return null;
        }
        return [$eslesme[1], $eslesme[2]];
    }

    private function tabloyuHazirla(): void
    {
        if (self::$tabloHazir) {
            return;
        }
        Veritabani::baglan()->exec(
            'CREATE TABLE IF NOT EXISTS kalici_oturumlar (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                kurum_id BIGINT UNSIGNED NOT NULL,
                kullanici_id BIGINT UNSIGNED NOT NULL,
                secici CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
                dogrulayici_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
                oturum_surumu INT UNSIGNED NOT NULL,
                sona_erme_tarihi DATETIME NOT NULL,
                son_kullanim_tarihi DATETIME NULL,
                olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_kalici_oturum_secici (secici),
                KEY idx_kalici_oturum_kullanici (kullanici_id, sona_erme_tarihi),
                KEY idx_kalici_oturum_sona_erme (sona_erme_tarihi),
                CONSTRAINT fk_kalici_oturum_kurum FOREIGN KEY (kurum_id) REFERENCES kurumlar(id) ON DELETE CASCADE,
                CONSTRAINT fk_kalici_oturum_kullanici FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        Veritabani::baglan()->exec('DELETE FROM kalici_oturumlar WHERE sona_erme_tarihi < NOW()');
        self::$tabloHazir = true;
    }

    private function mevcutBelirteciSil(bool $cookieTemizle = true): void
    {
        $cookie = $_COOKIE[$this->cookieAdi()] ?? '';
        $parcalar = self::cookieAyir(is_string($cookie) ? $cookie : '');
        if ($parcalar !== null) {
            $stmt = Veritabani::baglan()->prepare('DELETE FROM kalici_oturumlar WHERE secici = :secici');
            $stmt->execute(['secici' => $parcalar[0]]);
        }
        if ($cookieTemizle) {
            $this->cookieTemizle();
        }
    }

    private function fazlaBelirtecleriTemizle(int $kullaniciId): void
    {
        $db = Veritabani::baglan();
        $stmt = $db->prepare(
            'SELECT id FROM kalici_oturumlar
             WHERE kullanici_id = :kullanici_id
             ORDER BY son_kullanim_tarihi DESC, id DESC'
        );
        $stmt->execute(['kullanici_id' => $kullaniciId]);
        $idler = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: []);
        $silinecekler = array_slice($idler, 10);
        if (!$silinecekler) {
            return;
        }
        $yerTutucular = implode(',', array_fill(0, count($silinecekler), '?'));
        $sil = $db->prepare('DELETE FROM kalici_oturumlar WHERE id IN (' . $yerTutucular . ')');
        $sil->execute($silinecekler);
    }

    private function idIleSil(int $id, \PDO $db): void
    {
        $stmt = $db->prepare('DELETE FROM kalici_oturumlar WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function cookieYaz(string $deger, int $sonaErme): void
    {
        setcookie($this->cookieAdi(), $deger, [
            'expires' => $sonaErme,
            'path' => '/',
            'secure' => $this->guvenliCookieMi(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[$this->cookieAdi()] = $deger;
    }

    private function cookieTemizle(): void
    {
        setcookie($this->cookieAdi(), '', [
            'expires' => time() - 42000,
            'path' => '/',
            'secure' => $this->guvenliCookieMi(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[$this->cookieAdi()]);
    }

    private function cookieAdi(): string
    {
        $varsayilan = Config::get('APP_ENV') === 'production' ? '__Host-talya_remember' : 'talya_kids_remember';
        $ad = (string) Config::get('REMEMBER_COOKIE_NAME', $varsayilan);
        return preg_match('/^[A-Za-z0-9_-]{1,64}$/D', $ad) ? $ad : $varsayilan;
    }

    private function gecerlilikGunu(): int
    {
        return max(1, min(30, (int) Config::get('REMEMBER_LOGIN_DAYS', 30)));
    }

    private function guvenliCookieMi(): bool
    {
        return Config::get('APP_ENV') === 'production'
            || (Config::bool('SESSION_COOKIE_SECURE', false) && Request::isSecure());
    }
}
