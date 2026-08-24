<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class FinansRaporu extends Model
{
    public static function gelirGider(string $gorunum = 'aylik', ?string $baslangic = null, ?string $bitis = null): array
    {
        $gorunum = $gorunum === 'haftalik' ? 'haftalik' : 'aylik';
        [$baslangicTarihi, $bitisTarihi] = self::tarihAraligi($gorunum, $baslangic, $bitis);
        $donemler = self::donemler($gorunum, $baslangicTarihi, $bitisTarihi);
        $baslangic = $baslangicTarihi->format('Y-m-d');
        $bitis = $bitisTarihi->format('Y-m-d');
        $kurumId = self::kurumId();

        $gelirler = self::gunlukToplam(
            'SELECT tarih, COALESCE(SUM(tutar), 0) AS toplam
             FROM odemeler
             WHERE kurum_id = :kurum_id
               AND iptal = 0
               AND tarih BETWEEN :baslangic AND :bitis
             GROUP BY tarih',
            $kurumId,
            $baslangic,
            $bitis
        );
        $giderler = self::gunlukToplam(
            'SELECT COALESCE(odeme_tarihi, tarih) AS tarih, COALESCE(SUM(tutar), 0) AS toplam
             FROM giderler
             WHERE kurum_id = :kurum_id
               AND durum = "odendi"
               AND COALESCE(odeme_tarihi, tarih) BETWEEN :baslangic AND :bitis
             GROUP BY COALESCE(odeme_tarihi, tarih)',
            $kurumId,
            $baslangic,
            $bitis
        );

        $toplamGelir = 0.0;
        $toplamGider = 0.0;
        $karliDonem = 0;
        $zararDonem = 0;
        foreach ($donemler as &$donem) {
            $gelir = self::aralikToplami($gelirler, $donem['baslangic'], $donem['bitis']);
            $gider = self::aralikToplami($giderler, $donem['baslangic'], $donem['bitis']);
            $net = $gelir - $gider;
            $marj = $gelir > 0 ? ($net / $gelir) * 100 : null;
            $durum = $net > 0.005 ? 'kar' : ($net < -0.005 ? 'zarar' : 'basabas');
            $karliDonem += $durum === 'kar' ? 1 : 0;
            $zararDonem += $durum === 'zarar' ? 1 : 0;
            $toplamGelir += $gelir;
            $toplamGider += $gider;
            $donem['gelir'] = $gelir;
            $donem['gider'] = $gider;
            $donem['net'] = $net;
            $donem['kar_marji'] = $marj;
            $donem['durum'] = $durum;
        }
        unset($donem);

        $net = $toplamGelir - $toplamGider;
        $grafikMaksimum = max(1.0, ...array_map(
            static fn(array $donem): float => max((float) $donem['gelir'], (float) $donem['gider']),
            $donemler
        ));
        foreach ($donemler as &$donem) {
            $donem['gelir_yuzde'] = ((float) $donem['gelir'] / $grafikMaksimum) * 100;
            $donem['gider_yuzde'] = ((float) $donem['gider'] / $grafikMaksimum) * 100;
        }
        unset($donem);

        return [
            'gorunum' => $gorunum,
            'baslangic_tarihi' => $baslangic,
            'bitis_tarihi' => $bitis,
            'donemler' => $donemler,
            'toplam_gelir' => $toplamGelir,
            'toplam_gider' => $toplamGider,
            'net' => $net,
            'kar_marji' => $toplamGelir > 0 ? ($net / $toplamGelir) * 100 : null,
            'durum' => $net > 0.005 ? 'kar' : ($net < -0.005 ? 'zarar' : 'basabas'),
            'karli_donem' => $karliDonem,
            'zarar_donem' => $zararDonem,
            'son_donem' => $donemler[array_key_last($donemler)],
        ];
    }

    private static function tarihAraligi(string $gorunum, ?string $baslangic, ?string $bitis): array
    {
        $bugun = new \DateTimeImmutable('today');
        $baslangicTarihi = self::tarih($baslangic);
        $bitisTarihi = self::tarih($bitis);

        if (!$baslangicTarihi && !$bitisTarihi) {
            if ($gorunum === 'haftalik') {
                $bitisTarihi = $bugun->modify('sunday this week');
                $baslangicTarihi = $bitisTarihi->modify('-11 weeks')->modify('monday this week');
            } else {
                $bitisTarihi = $bugun->modify('last day of this month');
                $baslangicTarihi = $bitisTarihi->modify('first day of this month')->modify('-11 months');
            }
        } elseif (!$baslangicTarihi && $bitisTarihi) {
            $baslangicTarihi = $gorunum === 'haftalik'
                ? $bitisTarihi->modify('-11 weeks')->modify('monday this week')
                : $bitisTarihi->modify('first day of this month')->modify('-11 months');
        } elseif ($baslangicTarihi && !$bitisTarihi) {
            $bitisTarihi = $gorunum === 'haftalik'
                ? $baslangicTarihi->modify('+11 weeks')->modify('sunday this week')
                : $baslangicTarihi->modify('first day of this month')->modify('+11 months')->modify('last day of this month');
        }

        if ($baslangicTarihi > $bitisTarihi) {
            [$baslangicTarihi, $bitisTarihi] = [$bitisTarihi, $baslangicTarihi];
        }

        return [$baslangicTarihi, $bitisTarihi];
    }

    private static function donemler(string $gorunum, \DateTimeImmutable $seciliBaslangic, \DateTimeImmutable $seciliBitis): array
    {
        $aylar = [1 => 'Oca', 2 => 'Şub', 3 => 'Mar', 4 => 'Nis', 5 => 'May', 6 => 'Haz', 7 => 'Tem', 8 => 'Ağu', 9 => 'Eyl', 10 => 'Eki', 11 => 'Kas', 12 => 'Ara'];
        $donemler = [];

        if ($gorunum === 'haftalik') {
            $baslangic = $seciliBaslangic->modify('monday this week');
            while ($baslangic <= $seciliBitis) {
                $haftaBitis = $baslangic->modify('+6 days');
                $donemBaslangic = $baslangic < $seciliBaslangic ? $seciliBaslangic : $baslangic;
                $donemBitis = $haftaBitis > $seciliBitis ? $seciliBitis : $haftaBitis;
                $donemler[] = [
                    'anahtar' => $baslangic->format('o-W'),
                    'etiket' => $donemBaslangic->format('d.m') . ' - ' . $donemBitis->format('d.m'),
                    'baslangic' => $donemBaslangic->format('Y-m-d'),
                    'bitis' => $donemBitis->format('Y-m-d'),
                ];
                $baslangic = $baslangic->modify('+1 week');
            }
            return $donemler;
        }

        $baslangic = $seciliBaslangic->modify('first day of this month');
        while ($baslangic <= $seciliBitis) {
            $ayBitis = $baslangic->modify('last day of this month');
            $donemBaslangic = $baslangic < $seciliBaslangic ? $seciliBaslangic : $baslangic;
            $donemBitis = $ayBitis > $seciliBitis ? $seciliBitis : $ayBitis;
            $donemler[] = [
                'anahtar' => $baslangic->format('Y-m'),
                'etiket' => $aylar[(int) $baslangic->format('n')] . ' ' . $baslangic->format('Y'),
                'baslangic' => $donemBaslangic->format('Y-m-d'),
                'bitis' => $donemBitis->format('Y-m-d'),
            ];
            $baslangic = $baslangic->modify('+1 month');
        }
        return $donemler;
    }

    private static function gunlukToplam(string $sql, int $kurumId, string $baslangic, string $bitis): array
    {
        $stmt = self::db()->prepare($sql);
        $stmt->execute([
            'kurum_id' => $kurumId,
            'baslangic' => $baslangic,
            'bitis' => $bitis,
        ]);

        $sonuc = [];
        foreach ($stmt->fetchAll() as $row) {
            $sonuc[(string) $row['tarih']] = (float) $row['toplam'];
        }
        return $sonuc;
    }

    private static function aralikToplami(array $gunluk, string $baslangic, string $bitis): float
    {
        $toplam = 0.0;
        foreach ($gunluk as $tarih => $tutar) {
            if ($tarih >= $baslangic && $tarih <= $bitis) {
                $toplam += (float) $tutar;
            }
        }
        return $toplam;
    }

    private static function tarih(?string $tarih): ?\DateTimeImmutable
    {
        if (!$tarih) {
            return null;
        }
        $deger = \DateTimeImmutable::createFromFormat('!Y-m-d', trim($tarih));
        return $deger instanceof \DateTimeImmutable ? $deger : null;
    }
}
