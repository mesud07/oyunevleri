<?php

declare(strict_types=1);

namespace App\Services;

final class TahsilatAnalizServisi
{
    public const KDV_YONTEMLERI = ['nakit', 'kredi_karti', 'havale_eft', 'odeme_baglantisi', 'diger'];
    public const VARSAYILAN_KDV_YONTEMLERI = ['kredi_karti', 'havale_eft', 'odeme_baglantisi', 'diger'];
    public const DONEMLER = ['ay', '3ay', '6ay', 'bu_yil', '1yil'];

    private const YONTEMLER = [
        'nakit' => ['ad' => 'Nakit', 'renk' => '#16a085'],
        'kredi_karti' => ['ad' => 'Kredi Kartı', 'renk' => '#7c5ce7'],
        'havale_eft' => ['ad' => 'Havale / EFT', 'renk' => '#2980d9'],
        'odeme_baglantisi' => ['ad' => 'Ödeme Bağlantısı', 'renk' => '#d04fa3'],
        'diger' => ['ad' => 'Diğer', 'renk' => '#f39c4a'],
    ];

    public function olustur(string $ay, array $veriler, ?array $kdvYontemleri = null, array $aralik = []): array
    {
        $ayTarihi = \DateTimeImmutable::createFromFormat('!Y-m', $ay);
        if (!$ayTarihi || $ayTarihi->format('Y-m') !== $ay) {
            throw new \InvalidArgumentException('Analiz ayi gecersiz.');
        }

        $kdvYontemleri = self::kdvYontemleriniNormalize($kdvYontemleri ?? self::VARSAYILAN_KDV_YONTEMLERI);
        $yontemler = [];
        foreach (self::YONTEMLER as $kod => $tanim) {
            $yontemler[$kod] = array_merge($tanim, [
                'kod' => $kod,
                'adet' => 0,
                'tutar' => 0.0,
                'kdv' => 0.0,
                'kdv_dahil' => in_array($kod, $kdvYontemleri, true),
                'yuzde' => 0.0,
            ]);
        }

        $toplam = 0.0;
        $kdv = 0.0;
        $adet = 0;
        $kdvBelirsizAdet = 0;
        foreach (($veriler['yontemler'] ?? []) as $satir) {
            $kod = (string) ($satir['kategori'] ?? 'diger');
            if (!isset($yontemler[$kod])) {
                $kod = 'diger';
            }
            $brut = max(0.0, (float) ($satir['brut'] ?? 0));
            $kdvDahil = in_array($kod, $kdvYontemleri, true);
            $satirKdv = $kdvDahil ? max(0.0, (float) ($satir['kdv'] ?? 0)) : 0.0;
            $satirAdet = max(0, (int) ($satir['adet'] ?? 0));
            $yontemler[$kod]['tutar'] += $brut;
            $yontemler[$kod]['kdv'] += $satirKdv;
            $yontemler[$kod]['adet'] += $satirAdet;
            $toplam += $brut;
            $kdv += $satirKdv;
            $adet += $satirAdet;
            if ($kdvDahil) {
                $kdvBelirsizAdet += max(0, (int) ($satir['kdv_belirsiz_adet'] ?? 0));
            }
        }

        foreach ($yontemler as &$yontem) {
            $yontem['tutar'] = round((float) $yontem['tutar'], 2);
            $yontem['kdv'] = round((float) $yontem['kdv'], 2);
            $yontem['yuzde'] = $toplam > 0 ? round(((float) $yontem['tutar'] / $toplam) * 100, 1) : 0.0;
        }
        unset($yontem);

        $varsayilanAralik = self::donemAraligi($ay, 'ay');
        $aralik = array_replace($varsayilanAralik, $aralik);

        return [
            'ay' => $ay,
            'ay_etiketi' => (string) $aralik['etiket'],
            'analiz_donemi' => (string) $aralik['donem'],
            'baslangic' => (string) $aralik['baslangic'],
            'bitis' => (string) $aralik['bitis'],
            'dosya_eki' => (string) $aralik['dosya_eki'],
            'yil' => (int) $ayTarihi->format('Y'),
            'toplam_tahsilat' => round($toplam, 2),
            'tahsilat_adedi' => $adet,
            'ortalama_tahsilat' => $adet > 0 ? round($toplam / $adet, 2) : 0.0,
            'kdv' => round($kdv, 2),
            'kdv_haric_gelir' => round(max(0.0, $toplam - $kdv), 2),
            'kdv_belirsiz_adet' => $kdvBelirsizAdet,
            'kdv_yontemleri' => $kdvYontemleri,
            'yontemler' => array_values($yontemler),
        ];
    }

    public static function kdvYontemleriniNormalize(array $yontemler): array
    {
        $secili = array_values(array_unique(array_map('strval', $yontemler)));
        return array_values(array_filter(
            self::KDV_YONTEMLERI,
            static fn(string $yontem): bool => in_array($yontem, $secili, true)
        ));
    }

    public static function donemAraligi(string $ay, string $donem): array
    {
        $bitisAyi = \DateTimeImmutable::createFromFormat('!Y-m', $ay);
        if (!$bitisAyi || $bitisAyi->format('Y-m') !== $ay) {
            throw new \InvalidArgumentException('Analiz ayi gecersiz.');
        }
        $donem = in_array($donem, self::DONEMLER, true) ? $donem : 'ay';
        $bitis = $bitisAyi->modify('last day of this month');
        $baslangic = match ($donem) {
            '3ay' => $bitisAyi->modify('-2 months'),
            '6ay' => $bitisAyi->modify('-5 months'),
            'bu_yil' => $bitisAyi->setDate((int) $bitisAyi->format('Y'), 1, 1),
            '1yil' => $bitisAyi->modify('-11 months'),
            default => $bitisAyi,
        };
        $etiket = $donem === 'ay'
            ? self::ayEtiketi($bitisAyi)
            : date('d.m.Y', $baslangic->getTimestamp()) . ' – ' . date('d.m.Y', $bitis->getTimestamp());

        return [
            'donem' => $donem,
            'baslangic' => $baslangic->format('Y-m-d'),
            'bitis' => $bitis->format('Y-m-d'),
            'etiket' => $etiket,
            'dosya_eki' => $donem === 'ay'
                ? $bitisAyi->format('Ym')
                : $baslangic->format('Ymd') . '-' . $bitis->format('Ymd'),
        ];
    }

    private static function ayEtiketi(\DateTimeImmutable $tarih): string
    {
        $aylar = [1 => 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
        return ($aylar[(int) $tarih->format('n')] ?? '') . ' ' . $tarih->format('Y');
    }
}
