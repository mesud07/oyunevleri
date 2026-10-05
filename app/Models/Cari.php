<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Cari extends Model
{
    public static function liste(string $arama = ''): array
    {
        $sql = 'SELECT c.*, CONCAT(v.ad, " ", v.soyad) AS veli_adi,
                       (SELECT COUNT(*) FROM faturalar f
                        WHERE f.cari_id = c.id AND f.kurum_id = c.kurum_id) AS fatura_sayisi
                FROM cariler c
                LEFT JOIN veliler v ON v.id = c.veli_id AND v.kurum_id = c.kurum_id
                WHERE c.kurum_id = :kurum_id AND c.aktif = 1';
        $params = self::kurumParam();

        if ($arama !== '') {
            $sql .= ' AND (c.vkn_tckn LIKE :arama OR c.unvan LIKE :arama
                           OR CONCAT(c.ad, " ", c.soyad) LIKE :arama)';
            $params['arama'] = '%' . $arama . '%';
        }

        $sql .= ' ORDER BY COALESCE(c.unvan, CONCAT(c.ad, " ", c.soyad)), c.id DESC LIMIT 200';
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function idIleBul(int $id): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM cariler WHERE id = :id AND kurum_id = :kurum_id AND aktif = 1 LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function veliAra(string $arama): array
    {
        $tckn = preg_replace('/\D+/', '', $arama);
        $stmt = self::db()->prepare(
            'SELECT id, ad, soyad, tc_kimlik_no, telefon, eposta, il, ilce, adres
             FROM veliler
             WHERE kurum_id = :kurum_id
               AND (CONCAT(ad, " ", soyad) LIKE :arama
                    OR REPLACE(REPLACE(REPLACE(tc_kimlik_no, " ", ""), "-", ""), ".", "") = :tckn)
             ORDER BY ad, soyad
             LIMIT 20'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'arama' => '%' . $arama . '%',
            'tckn' => $tckn,
        ]);
        return $stmt->fetchAll();
    }

    public static function kaydet(array $data): int
    {
        $muhtelifMusteri = !empty($data['muhtelif_musteri']);
        if ($muhtelifMusteri) {
            $data = array_merge($data, [
                'veli_id' => null,
                'profil_turu' => 'bireysel',
                'vkn_tckn' => '11111111111',
                'il' => 'Antalya',
                'ilce' => 'Muratpaşa',
                'adres' => 'Muratpaşa / Antalya',
            ]);
        }

        $vergiNo = preg_replace('/\D+/', '', (string) ($data['vkn_tckn'] ?? ''));
        $tur = (string) ($data['profil_turu'] ?? 'bireysel');

        if (!in_array($tur, ['bireysel', 'kurumsal'], true)
            || ($tur === 'bireysel' && strlen($vergiNo) !== 11)
            || ($tur === 'kurumsal' && strlen($vergiNo) !== 10)) {
            throw new \RuntimeException('VKN/TCKN cari türüyle uyumlu değil.');
        }

        foreach (['adres', 'il', 'ilce'] as $alan) {
            if (trim((string) ($data[$alan] ?? '')) === '') {
                throw new \RuntimeException('Adres, il ve ilçe zorunludur.');
            }
        }

        $veliId = $tur === 'bireysel' ? (int) ($data['veli_id'] ?? 0) : 0;
        if ($veliId > 0) {
            $stmt = self::db()->prepare(
                'SELECT id FROM veliler WHERE id = :id AND kurum_id = :kurum_id LIMIT 1'
            );
            $stmt->execute(['id' => $veliId, 'kurum_id' => self::kurumId()]);
            if (!$stmt->fetchColumn()) {
                throw new \RuntimeException('Seçilen veli bu kuruma ait değil.');
            }
        } elseif ($tur === 'bireysel' && !$muhtelifMusteri) {
            $stmt = self::db()->prepare(
                'SELECT id FROM veliler
                 WHERE kurum_id = :kurum_id
                   AND REPLACE(REPLACE(REPLACE(tc_kimlik_no, " ", ""), "-", ""), ".", "") = :tckn
                 LIMIT 1'
            );
            $stmt->execute(['kurum_id' => self::kurumId(), 'tckn' => $vergiNo]);
            $veliId = (int) ($stmt->fetchColumn() ?: 0);
        }

        $params = [
            'kurum_id' => self::kurumId(),
            'veli_id' => $veliId ?: null,
            'profil_turu' => $tur,
            'ad' => trim((string) ($data['ad'] ?? '')) ?: null,
            'soyad' => trim((string) ($data['soyad'] ?? '')) ?: null,
            'unvan' => trim((string) ($data['unvan'] ?? '')) ?: null,
            'vkn_tckn' => $vergiNo,
            'vergi_dairesi' => trim((string) ($data['vergi_dairesi'] ?? '')) ?: null,
            'adres' => trim((string) $data['adres']),
            'il' => trim((string) $data['il']),
            'ilce' => trim((string) $data['ilce']),
            'ulke' => trim((string) ($data['ulke'] ?? 'TÜRKİYE')) ?: 'TÜRKİYE',
            'eposta' => trim((string) ($data['eposta'] ?? '')) ?: null,
            'telefon' => trim((string) ($data['telefon'] ?? '')) ?: null,
            'notlar' => trim((string) ($data['notlar'] ?? '')) ?: null,
        ];

        if ($tur === 'bireysel' && (!$params['ad'] || !$params['soyad'])) {
            throw new \RuntimeException('Bireysel cari için ad ve soyad zorunludur.');
        }
        if ($tur === 'kurumsal' && !$params['unvan']) {
            throw new \RuntimeException('Kurumsal cari için ünvan zorunludur.');
        }

        $vergiNoIle = self::cariIdBul('vkn_tckn', $vergiNo);
        $veliIle = $veliId > 0 ? self::cariIdBul('veli_id', (string) $veliId) : 0;
        if ($vergiNoIle > 0 && $veliIle > 0 && $vergiNoIle !== $veliIle) {
            throw new \RuntimeException('Bu TCKN ve veli farklı cari kayıtlarına bağlı. Kayıtları kontrol edin.');
        }

        $mevcutId = $vergiNoIle ?: $veliIle;
        if ($mevcutId > 0) {
            $params['id'] = $mevcutId;
            $stmt = self::db()->prepare(
                'UPDATE cariler SET veli_id = :veli_id, profil_turu = :profil_turu,
                    ad = :ad, soyad = :soyad, unvan = :unvan, vkn_tckn = :vkn_tckn,
                    vergi_dairesi = :vergi_dairesi, adres = :adres, il = :il, ilce = :ilce,
                    ulke = :ulke, eposta = :eposta, telefon = :telefon, notlar = :notlar, aktif = 1
                 WHERE id = :id AND kurum_id = :kurum_id'
            );
            $stmt->execute($params);
            return $mevcutId;
        }

        $stmt = self::db()->prepare(
            'INSERT INTO cariler
                (kurum_id, veli_id, profil_turu, ad, soyad, unvan, vkn_tckn, vergi_dairesi,
                 adres, il, ilce, ulke, eposta, telefon, notlar)
             VALUES
                (:kurum_id, :veli_id, :profil_turu, :ad, :soyad, :unvan, :vkn_tckn, :vergi_dairesi,
                 :adres, :il, :ilce, :ulke, :eposta, :telefon, :notlar)'
        );
        $stmt->execute($params);
        return (int) self::db()->lastInsertId();
    }

    private static function cariIdBul(string $alan, string $deger): int
    {
        if (!in_array($alan, ['vkn_tckn', 'veli_id'], true)) {
            return 0;
        }
        $stmt = self::db()->prepare(
            "SELECT id FROM cariler WHERE kurum_id = :kurum_id AND {$alan} = :deger LIMIT 1"
        );
        $stmt->execute(['kurum_id' => self::kurumId(), 'deger' => $deger]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }
}
