<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Kurum extends Model
{
    public static function varsayilanId(): int
    {
        $stmt = self::db()->prepare('SELECT id FROM kurumlar WHERE kod = :kod LIMIT 1');
        $stmt->execute(['kod' => 'TALYA']);
        return (int) ($stmt->fetchColumn() ?: 1);
    }

    public static function kodIleBul(string $kod): ?array
    {
        $kod = strtoupper(trim($kod) !== '' ? trim($kod) : 'TALYA');
        $stmt = self::db()->prepare(
            'SELECT id, ad, kod, logo_yolu, veli_portal_anahtari, aktif
             FROM kurumlar
             WHERE kod = :kod AND aktif = 1
             LIMIT 1'
        );
        $stmt->execute(['kod' => $kod]);
        $kurum = $stmt->fetch();
        return $kurum ?: null;
    }

    public static function liste(): array
    {
        return self::db()->query(
            'SELECT k.id, k.ad, k.kod, k.logo_yolu, k.veli_portal_anahtari, k.aktif, k.olusturulma_tarihi,
                    COUNT(DISTINCT ku.id) AS kullanici_sayisi,
                    COUNT(DISTINCT CASE WHEN r.kod = "kurucu" AND ku.aktif = 1 THEN ku.id END) AS kurucu_sayisi,
                    COUNT(DISTINCT o.id) AS ogrenci_sayisi
             FROM kurumlar k
             LEFT JOIN kullanicilar ku ON ku.kurum_id = k.id
             LEFT JOIN roller r ON r.id = ku.rol_id
             LEFT JOIN ogrenciler o ON o.kurum_id = k.id
             GROUP BY k.id
             ORDER BY k.aktif DESC, k.ad ASC'
        )->fetchAll();
    }

    public static function idIleBul(int $id): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT id, ad, kod, logo_yolu, veli_portal_anahtari, aktif, olusturulma_tarihi
             FROM kurumlar
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $kurum = $stmt->fetch();
        return $kurum ?: null;
    }

    public static function kodVarMi(string $kod, int $haricId = 0): bool
    {
        $stmt = self::db()->prepare(
            'SELECT 1 FROM kurumlar WHERE kod = :kod AND id <> :id LIMIT 1'
        );
        $stmt->execute(['kod' => strtoupper(trim($kod)), 'id' => $haricId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function logoGuncelle(int $id, string $logoYolu): ?string
    {
        $kurum = self::idIleBul($id);
        if (!$kurum) {
            return null;
        }

        $stmt = self::db()->prepare('UPDATE kurumlar SET logo_yolu = :logo_yolu WHERE id = :id');
        $stmt->execute(['id' => $id, 'logo_yolu' => $logoYolu]);
        return !empty($kurum['logo_yolu']) ? (string) $kurum['logo_yolu'] : '';
    }

    public static function veliPortalAnahtariIleBul(string $anahtar): ?array
    {
        $anahtar = strtolower(trim($anahtar));
        if (!preg_match('/^[a-f0-9]{32}$/', $anahtar)) {
            return null;
        }

        $stmt = self::db()->prepare(
            'SELECT id, ad, kod, logo_yolu, veli_portal_anahtari
             FROM kurumlar
             WHERE veli_portal_anahtari = :anahtar AND aktif = 1
             LIMIT 1'
        );
        $stmt->execute(['anahtar' => $anahtar]);
        $kurum = $stmt->fetch();
        return $kurum ?: null;
    }

    public static function veliPortalAnahtari(int $kurumId): string
    {
        $stmt = self::db()->prepare('SELECT veli_portal_anahtari FROM kurumlar WHERE id = :id AND aktif = 1 LIMIT 1');
        $stmt->execute(['id' => $kurumId]);
        $anahtar = strtolower(trim((string) ($stmt->fetchColumn() ?: '')));
        if (preg_match('/^[a-f0-9]{32}$/', $anahtar)) {
            return $anahtar;
        }

        $yeniAnahtar = bin2hex(random_bytes(16));
        $guncelle = self::db()->prepare(
            'UPDATE kurumlar
             SET veli_portal_anahtari = :anahtar
             WHERE id = :id AND aktif = 1
               AND (veli_portal_anahtari IS NULL OR veli_portal_anahtari = "")'
        );
        $guncelle->execute(['id' => $kurumId, 'anahtar' => $yeniAnahtar]);

        $stmt->execute(['id' => $kurumId]);
        $anahtar = strtolower(trim((string) ($stmt->fetchColumn() ?: '')));
        return preg_match('/^[a-f0-9]{32}$/', $anahtar) ? $anahtar : '';
    }

    public static function kurucuVarMi(int $kurumId): bool
    {
        $stmt = self::db()->prepare(
            'SELECT 1
             FROM kullanicilar k
             INNER JOIN roller r ON r.id = k.rol_id
             WHERE k.kurum_id = :kurum_id AND r.kod = "kurucu" AND k.aktif = 1
             LIMIT 1'
        );
        $stmt->execute(['kurum_id' => $kurumId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function kurucuIleKaydet(int $id, array $veri, ?array $kurucu): array
    {
        $db = self::db();
        try {
            $db->beginTransaction();

            if ($id > 0) {
                $stmt = $db->prepare(
                    'UPDATE kurumlar SET ad = :ad, kod = :kod, aktif = :aktif WHERE id = :id'
                );
                $stmt->execute([
                    'id' => $id,
                    'ad' => trim((string) $veri['ad']),
                    'kod' => strtoupper(trim((string) $veri['kod'])),
                    'aktif' => (int) ($veri['aktif'] ?? 0) === 1 ? 1 : 0,
                ]);
                $kurumId = $id;
            } else {
                $stmt = $db->prepare(
                    'INSERT INTO kurumlar (ad, kod, veli_portal_anahtari, aktif, olusturulma_tarihi)
                     VALUES (:ad, :kod, :veli_portal_anahtari, :aktif, NOW())'
                );
                $stmt->execute([
                    'ad' => trim((string) $veri['ad']),
                    'kod' => strtoupper(trim((string) $veri['kod'])),
                    'veli_portal_anahtari' => bin2hex(random_bytes(16)),
                    'aktif' => (int) ($veri['aktif'] ?? 0) === 1 ? 1 : 0,
                ]);
                $kurumId = (int) $db->lastInsertId();
            }

            $kurucuId = 0;
            if ($kurucu !== null) {
                $rolStmt = $db->query('SELECT id FROM roller WHERE kod = "kurucu" LIMIT 1');
                $rolId = (int) $rolStmt->fetchColumn();
                if ($rolId < 1) {
                    throw new \RuntimeException('Kurucu rolu bulunamadi.');
                }

                $kontrol = $db->prepare(
                    'SELECT 1 FROM kullanicilar WHERE kurum_id = :kurum_id AND eposta = :eposta LIMIT 1'
                );
                $kontrol->execute(['kurum_id' => $kurumId, 'eposta' => $kurucu['eposta']]);
                if ($kontrol->fetchColumn()) {
                    throw new \RuntimeException('Bu kullanici adi kurumda zaten kullaniliyor.');
                }

                $ekle = $db->prepare(
                    'INSERT INTO kullanicilar
                     (kurum_id, rol_id, ad, soyad, eposta, telefon, sifre, aktif, sistem_yoneticisi, olusturulma_tarihi)
                     VALUES
                     (:kurum_id, :rol_id, :ad, :soyad, :eposta, :telefon, :sifre, 1, 0, NOW())'
                );
                $ekle->execute([
                    'kurum_id' => $kurumId,
                    'rol_id' => $rolId,
                    'ad' => trim((string) $kurucu['ad']),
                    'soyad' => trim((string) $kurucu['soyad']),
                    'eposta' => trim((string) $kurucu['eposta']),
                    'telefon' => trim((string) ($kurucu['telefon'] ?? '')) ?: null,
                    'sifre' => password_hash((string) $kurucu['sifre'], PASSWORD_DEFAULT),
                ]);
                $kurucuId = (int) $db->lastInsertId();
            }

            $db->commit();
            return ['kurum_id' => $kurumId, 'kurucu_id' => $kurucuId];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function kaydet(int $id, array $veri): int
    {
        $db = self::db();
        $kod = strtoupper(trim((string) $veri['kod']));
        $aktif = (int) ($veri['aktif'] ?? 0) === 1 ? 1 : 0;

        if ($id > 0) {
            $stmt = $db->prepare(
                'UPDATE kurumlar
                 SET ad = :ad, kod = :kod, aktif = :aktif
                 WHERE id = :id'
            );
            $stmt->execute([
                'id' => $id,
                'ad' => trim((string) $veri['ad']),
                'kod' => $kod,
                'aktif' => $aktif,
            ]);
            return $id;
        }

        $stmt = $db->prepare(
            'INSERT INTO kurumlar (ad, kod, veli_portal_anahtari, aktif, olusturulma_tarihi)
             VALUES (:ad, :kod, :veli_portal_anahtari, :aktif, NOW())'
        );
        $stmt->execute([
            'ad' => trim((string) $veri['ad']),
            'kod' => $kod,
            'veli_portal_anahtari' => bin2hex(random_bytes(16)),
            'aktif' => $aktif,
        ]);
        return (int) $db->lastInsertId();
    }
}
