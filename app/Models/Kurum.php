<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Services\KurumModuluServisi;

final class Kurum extends Model
{
    public static function varsayilanId(): int
    {
        $stmt = self::db()->prepare('SELECT id FROM kurumlar WHERE kod = :kod LIMIT 1');
        $stmt->execute(['kod' => 'TALYA']);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    public static function aktifIdler(): array
    {
        $stmt = self::db()->query('SELECT id FROM kurumlar WHERE aktif = 1 ORDER BY id ASC');
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
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
        $kurumlar = self::db()->query(
            'SELECT k.id, k.ad, k.kod, k.logo_yolu, k.veli_portal_anahtari, k.aktif, k.olusturulma_tarihi,
                    COUNT(DISTINCT ku.id) AS kullanici_sayisi,
                    COUNT(DISTINCT CASE WHEN r.kod = "kurucu" AND ku.aktif = 1 THEN ku.id END) AS kurucu_sayisi,
                    COUNT(DISTINCT o.id) AS ogrenci_sayisi,
                    MAX(f.id) AS kurucu_id, MAX(f.ad) AS kurucu_ad, MAX(f.soyad) AS kurucu_soyad,
                    MAX(f.eposta) AS kurucu_eposta, MAX(f.telefon) AS kurucu_telefon, MAX(f.aktif) AS kurucu_aktif,
                    MAX(m.id) AS mudur_id, MAX(m.ad) AS mudur_ad, MAX(m.soyad) AS mudur_soyad,
                    MAX(m.eposta) AS mudur_eposta, MAX(m.telefon) AS mudur_telefon, MAX(m.aktif) AS mudur_aktif
             FROM kurumlar k
             LEFT JOIN kullanicilar ku ON ku.kurum_id = k.id
             LEFT JOIN roller r ON r.id = ku.rol_id
             LEFT JOIN ogrenciler o ON o.kurum_id = k.id
             LEFT JOIN kullanicilar m ON m.id = (
                 SELECT km.id
                 FROM kullanicilar km
                 INNER JOIN roller rm ON rm.id = km.rol_id
                 WHERE km.kurum_id = k.id AND rm.kod = "yonetici"
                 ORDER BY km.aktif DESC, km.id ASC
                 LIMIT 1
             )
             LEFT JOIN kullanicilar f ON f.id = (
                 SELECT kf.id
                 FROM kullanicilar kf
                 INNER JOIN roller rf ON rf.id = kf.rol_id
                 WHERE kf.kurum_id = k.id AND rf.kod = "kurucu"
                 ORDER BY kf.aktif DESC, kf.id ASC
                 LIMIT 1
             )
             GROUP BY k.id
             ORDER BY k.aktif DESC, k.ad ASC'
        )->fetchAll();

        $modulServisi = new KurumModuluServisi();
        foreach ($kurumlar as &$kurum) {
            $kurum['moduller'] = $modulServisi->kurumIcin((int) $kurum['id']);
            $kurum['sayfalar'] = $modulServisi->sayfalarKurumIcin((int) $kurum['id']);
        }
        unset($kurum);
        return $kurumlar;
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

    public static function mudurIleBul(int $kurumId): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT k.id, k.ad, k.soyad, k.eposta, k.telefon, k.aktif
             FROM kullanicilar k
             INNER JOIN roller r ON r.id = k.rol_id
             WHERE k.kurum_id = :kurum_id AND r.kod = "yonetici"
             ORDER BY k.aktif DESC, k.id ASC
             LIMIT 1'
        );
        $stmt->execute(['kurum_id' => $kurumId]);
        $mudur = $stmt->fetch();
        return $mudur ?: null;
    }

    public static function kurucuIleKaydet(
        int $id,
        array $veri,
        ?array $kurucu,
        ?array $mudur = null,
        array $aktifModuller = [],
        array $aktifSayfalar = []
    ): array
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

                $ayarStmt = $db->prepare(
                    'INSERT INTO ayarlar (kurum_id, anahtar, deger, aciklama)
                     VALUES (:kurum_id, "kurum_adi", :deger, "SMS ve panel metinlerinde kullanilan kurum adi.")
                     ON DUPLICATE KEY UPDATE deger = VALUES(deger)'
                );
                $ayarStmt->execute(['kurum_id' => $kurumId, 'deger' => trim((string) $veri['ad'])]);
                SmsKaydi::varsayilanSablonlariEkle($kurumId, $db);
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

            $mudurId = self::mudurKaydet($db, $kurumId, $mudur);
            $modulServisi = new KurumModuluServisi();
            $modulServisi->kaydet($kurumId, $aktifModuller);
            $modulServisi->sayfalariKaydet($kurumId, $aktifSayfalar);

            $db->commit();
            return ['kurum_id' => $kurumId, 'kurucu_id' => $kurucuId, 'mudur_id' => $mudurId];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    private static function mudurKaydet(\PDO $db, int $kurumId, ?array $mudur): int
    {
        $mevcutStmt = $db->prepare(
            'SELECT k.id
             FROM kullanicilar k
             INNER JOIN roller r ON r.id = k.rol_id
             WHERE k.kurum_id = :kurum_id AND r.kod = "yonetici"
             ORDER BY k.aktif DESC, k.id ASC
             LIMIT 1'
        );
        $mevcutStmt->execute(['kurum_id' => $kurumId]);
        $mevcutId = (int) ($mevcutStmt->fetchColumn() ?: 0);
        if ($mudur === null) {
            return $mevcutId;
        }

        $rolId = (int) ($db->query('SELECT id FROM roller WHERE kod = "yonetici" LIMIT 1')->fetchColumn() ?: 0);
        if ($rolId < 1) {
            throw new \RuntimeException('Kurum müdürü rolü bulunamadı.');
        }

        $kontrol = $db->prepare(
            'SELECT 1 FROM kullanicilar WHERE kurum_id = :kurum_id AND eposta = :eposta AND id <> :id LIMIT 1'
        );
        $kontrol->execute([
            'kurum_id' => $kurumId,
            'eposta' => $mudur['eposta'],
            'id' => $mevcutId,
        ]);
        if ($kontrol->fetchColumn()) {
            throw new \RuntimeException('Kurum müdürü kullanıcı adı bu kurumda zaten kullanılıyor.');
        }

        if ($mevcutId > 0) {
            $alanlar = [
                'rol_id = :rol_id', 'ad = :ad', 'soyad = :soyad', 'eposta = :eposta',
                'telefon = :telefon', 'aktif = :aktif', 'sistem_yoneticisi = 0',
            ];
            $params = [
                'id' => $mevcutId,
                'rol_id' => $rolId,
                'ad' => $mudur['ad'],
                'soyad' => $mudur['soyad'],
                'eposta' => $mudur['eposta'],
                'telefon' => $mudur['telefon'] ?: null,
                'aktif' => (int) $mudur['aktif'],
            ];
            if ($mudur['sifre'] !== '') {
                $alanlar[] = 'sifre = :sifre';
                $params['sifre'] = password_hash($mudur['sifre'], PASSWORD_DEFAULT);
            }
            $alanlar[] = 'oturum_surumu = oturum_surumu + 1';
            $stmt = $db->prepare('UPDATE kullanicilar SET ' . implode(', ', $alanlar) . ' WHERE id = :id');
            $stmt->execute($params);
            return $mevcutId;
        }

        if ($mudur['sifre'] === '') {
            throw new \RuntimeException('Yeni kurum müdürü için şifre zorunludur.');
        }
        $stmt = $db->prepare(
            'INSERT INTO kullanicilar
             (kurum_id, rol_id, ad, soyad, eposta, telefon, sifre, aktif, sistem_yoneticisi, olusturulma_tarihi)
             VALUES (:kurum_id, :rol_id, :ad, :soyad, :eposta, :telefon, :sifre, :aktif, 0, NOW())'
        );
        $stmt->execute([
            'kurum_id' => $kurumId,
            'rol_id' => $rolId,
            'ad' => $mudur['ad'],
            'soyad' => $mudur['soyad'],
            'eposta' => $mudur['eposta'],
            'telefon' => $mudur['telefon'] ?: null,
            'sifre' => password_hash($mudur['sifre'], PASSWORD_DEFAULT),
            'aktif' => (int) $mudur['aktif'],
        ]);
        return (int) $db->lastInsertId();
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
