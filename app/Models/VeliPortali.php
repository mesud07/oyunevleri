<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class VeliPortali extends Model
{
    public static function telefonlaBul(string $telefon, int $kurumId): array
    {
        $telefon = self::telefonNormalize($telefon);
        if ($telefon === null) {
            return [
                'gecerli' => false,
                'veliler' => [],
                'cocuklar' => [],
            ];
        }

        $veliler = self::velileriBul($telefon, $kurumId);
        if ($veliler === []) {
            return [
                'gecerli' => true,
                'veliler' => [],
                'cocuklar' => [],
            ];
        }

        $cocuklar = self::cocuklariGetir(array_column($veliler, 'id'), $kurumId);
        foreach ($cocuklar as &$cocuk) {
            $ogrenciId = (int) $cocuk['id'];
            $cocuk['randevular'] = self::randevular($ogrenciId, $kurumId);
        }
        unset($cocuk);

        return [
            'gecerli' => true,
            'veliler' => $veliler,
            'cocuklar' => $cocuklar,
        ];
    }

    private static function telefonNormalize(string $telefon): ?string
    {
        $rakamlar = preg_replace('/\D+/', '', $telefon) ?? '';
        if (strpos($rakamlar, '0090') === 0) {
            $rakamlar = substr($rakamlar, 4);
        }
        if (strpos($rakamlar, '90') === 0 && strlen($rakamlar) === 12) {
            $rakamlar = substr($rakamlar, 2);
        }
        if (strpos($rakamlar, '0') === 0) {
            $rakamlar = substr($rakamlar, 1);
        }

        return preg_match('/^5\d{9}$/', $rakamlar) ? $rakamlar : null;
    }

    private static function velileriBul(string $telefon, int $kurumId): array
    {
        $stmt = self::db()->prepare(
            'SELECT id, ad, soyad, telefon
             FROM veliler
             WHERE kurum_id = ?
               AND (RIGHT(REGEXP_REPLACE(COALESCE(telefon, ""), "[^0-9]", ""), 10) = ?
                OR RIGHT(REGEXP_REPLACE(COALESCE(yedek_telefon, ""), "[^0-9]", ""), 10) = ?)
             ORDER BY ad, soyad'
        );
        $stmt->execute([$kurumId, $telefon, $telefon]);
        return $stmt->fetchAll();
    }

    private static function cocuklariGetir(array $veliIdleri, int $kurumId): array
    {
        $veliIdleri = array_values(array_unique(array_map('intval', $veliIdleri)));
        if ($veliIdleri === []) {
            return [];
        }

        $yerTutucular = implode(',', array_fill(0, count($veliIdleri), '?'));
        $stmt = self::db()->prepare(
            "SELECT DISTINCT o.id, CONCAT(o.ad, ' ', o.soyad) AS ad_soyad,
                    o.dogum_tarihi, o.durum
             FROM ogrenciler o
             INNER JOIN ogrenci_velileri ov ON ov.ogrenci_id = o.id AND ov.kurum_id = o.kurum_id
             WHERE o.kurum_id = ? AND ov.veli_id IN ($yerTutucular)
             ORDER BY ad_soyad"
        );
        $stmt->execute([$kurumId, ...$veliIdleri]);
        return $stmt->fetchAll();
    }

    private static function randevular(int $ogrenciId, int $kurumId): array
    {
        $stmt = self::db()->prepare(
            'SELECT r.id, r.tarih, r.baslangic_saati, r.bitis_saati, r.tur, r.durum,
                    r.telafi_hakki_id,
                    COALESCE(g.ad, "Cocuk Etkinlik ve Oyun Evi") AS grup,
                    COALESCE(p.paket_adi, r.tur) AS paket_adi,
                    kr.tarih AS telafi_kaynak_tarih,
                    kr.baslangic_saati AS telafi_kaynak_saat
             FROM randevular r
             LEFT JOIN gruplar g ON g.id = r.grup_id AND g.kurum_id = r.kurum_id
             LEFT JOIN paketler p ON p.id = r.paket_id AND p.kurum_id = r.kurum_id
             LEFT JOIN telafi_haklari th ON th.id = r.telafi_hakki_id AND th.kurum_id = r.kurum_id
             LEFT JOIN randevular kr ON kr.id = th.kaynak_randevu_id AND kr.kurum_id = r.kurum_id
             WHERE r.ogrenci_id = :ogrenci_id
               AND r.kurum_id = :kurum_id
               AND r.tarih >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             ORDER BY r.tarih ASC, r.baslangic_saati ASC
             LIMIT 50'
        );
        $stmt->execute(['ogrenci_id' => $ogrenciId, 'kurum_id' => $kurumId]);
        return $stmt->fetchAll();
    }

}
