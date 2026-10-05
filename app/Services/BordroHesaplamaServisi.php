<?php

declare(strict_types=1);

namespace App\Services;

final class BordroHesaplamaServisi
{
    public const TESVIKLER = [
        'diger_2_puan' => ['ad' => 'Diğer sektörler — 2 puan indirimli', 'sgk_isveren_orani' => 19.75],
        'tesviksiz' => ['ad' => 'Teşviksiz', 'sgk_isveren_orani' => 21.75],
        'imalat_5_puan' => ['ad' => 'İmalat — 5 puan indirimli', 'sgk_isveren_orani' => 16.75],
    ];

    /**
     * 2026 oranları:
     * https://www.sgk.gov.tr/Content/Post/c7812ea8-5087-413f-aeb5-d3c1d153e11a/Isveren-Prim-Oranlari-2026-01-13-04-52-38
     * https://www.sgk.gov.tr/Content/Post/2e0c9e1a-2cfe-4456-af10-49d3de0c58ba/Prime-Esas-Kazanc-Miktarlari-2026-01-14-10-35-39
     * https://www.gib.gov.tr/vergi-konulari/1_bireysel/11_ucret_geliri/11
     */
    private const MEVZUAT = [
        2026 => [
            'asgari_brut' => 33030.00,
            'sgk_gunluk_alt' => 1101.00,
            'sgk_gunluk_ust' => 9909.00,
            'sgk_calisan_orani' => 14.00,
            'issizlik_calisan_orani' => 1.00,
            'issizlik_isveren_orani' => 2.00,
            'damga_vergisi_orani' => 0.759,
        ],
    ];

    private const UCRETSIZ_DURUMLAR = ['raporlu', 'devamsiz'];

    public function hesapla(string $ay, array $personeller, array $yillikKayitlar): array
    {
        $yil = (int) substr($ay, 0, 4);
        $secilenAy = (int) substr($ay, 5, 2);
        $oranlar = self::MEVZUAT[$yil] ?? null;
        $bosToplamlar = [
            'brut_hakedis' => 0.0,
            'net_odeme' => 0.0,
            'sgk_toplam' => 0.0,
            'vergi_toplam' => 0.0,
            'isveren_maliyeti' => 0.0,
        ];
        if ($oranlar === null || $secilenAy < 1 || $secilenAy > 12) {
            return ['destekleniyor' => false, 'ay' => $ay, 'yil' => $yil, 'oranlar' => [], 'satirlar' => [], 'toplamlar' => $bosToplamlar];
        }

        $satirlar = [];
        $toplamlar = $bosToplamlar;
        foreach ($personeller as $personel) {
            $personelId = (int) ($personel['id'] ?? 0);
            $aylikBrut = round(max(0, (float) ($personel['aylik_brut_ucret'] ?? 0)), 2);
            $tesvikTuru = (string) ($personel['sgk_tesvik_turu'] ?? 'diger_2_puan');
            if (!isset(self::TESVIKLER[$tesvikTuru])) {
                $tesvikTuru = 'diger_2_puan';
            }

            $kumulatifMatrah = 0.0;
            $kumulatifAsgariMatrah = 0.0;
            $secilen = $this->bosSatir($personel, $aylikBrut, $tesvikTuru);
            for ($ayNo = 1; $ayNo <= $secilenAy; $ayNo++) {
                $hesapAyi = sprintf('%04d-%02d', $yil, $ayNo);
                $sgkGunu = $this->sgkGunSayisi($personel, $hesapAyi, $yillikKayitlar[$personelId] ?? []);
                $ayHesabi = $this->ayiHesapla(
                    $aylikBrut,
                    $sgkGunu,
                    $tesvikTuru,
                    $oranlar,
                    $kumulatifMatrah,
                    $kumulatifAsgariMatrah
                );
                $kumulatifMatrah += $ayHesabi['gelir_vergisi_matrahi'];
                $kumulatifAsgariMatrah += $ayHesabi['asgari_ucret_vergi_matrahi'];
                if ($ayNo === $secilenAy) {
                    $secilen = array_merge($secilen, $ayHesabi, [
                        'kumulatif_vergi_matrahi' => round($kumulatifMatrah, 2),
                    ]);
                }
            }

            $satirlar[] = $secilen;
            foreach (array_keys($toplamlar) as $alan) {
                $toplamlar[$alan] += (float) ($secilen[$alan] ?? 0);
            }
        }

        foreach ($toplamlar as $alan => $tutar) {
            $toplamlar[$alan] = round($tutar, 2);
        }
        return ['destekleniyor' => true, 'ay' => $ay, 'yil' => $yil, 'oranlar' => $oranlar, 'satirlar' => $satirlar, 'toplamlar' => $toplamlar];
    }

    private function ayiHesapla(float $aylikBrut, int $sgkGunu, string $tesvikTuru, array $oranlar, float $oncekiMatrah, float $oncekiAsgariMatrah): array
    {
        if ($aylikBrut <= 0 || $sgkGunu <= 0) {
            return $this->bosHesap($sgkGunu);
        }

        $brutHakedis = round(($aylikBrut / 30) * $sgkGunu, 2);
        $primeEsasKazanc = round(min(
            max($brutHakedis, (float) $oranlar['sgk_gunluk_alt'] * $sgkGunu),
            (float) $oranlar['sgk_gunluk_ust'] * $sgkGunu
        ), 2);
        $sgkCalisan = round($primeEsasKazanc * ((float) $oranlar['sgk_calisan_orani'] / 100), 2);
        $issizlikCalisan = round($primeEsasKazanc * ((float) $oranlar['issizlik_calisan_orani'] / 100), 2);
        $vergiMatrahi = round(max(0, $brutHakedis - $sgkCalisan - $issizlikCalisan), 2);
        $asgariBrutIstisna = round(min($brutHakedis, (float) $oranlar['sgk_gunluk_alt'] * $sgkGunu), 2);
        $asgariVergiMatrahi = round($asgariBrutIstisna * (1 - (((float) $oranlar['sgk_calisan_orani'] + (float) $oranlar['issizlik_calisan_orani']) / 100)), 2);

        $hesaplananGelirVergisi = round($this->kumulatifVergi($oncekiMatrah + $vergiMatrahi) - $this->kumulatifVergi($oncekiMatrah), 2);
        $asgariUcretIstisnasi = round($this->kumulatifVergi($oncekiAsgariMatrah + $asgariVergiMatrahi) - $this->kumulatifVergi($oncekiAsgariMatrah), 2);
        $gelirVergisi = round(max(0, $hesaplananGelirVergisi - min($hesaplananGelirVergisi, $asgariUcretIstisnasi)), 2);
        $damgaVergisiOrani = (float) $oranlar['damga_vergisi_orani'] / 100;
        $damgaVergisi = round(max(0, ($brutHakedis - $asgariBrutIstisna) * $damgaVergisiOrani), 2);

        $sgkIsverenOrani = (float) self::TESVIKLER[$tesvikTuru]['sgk_isveren_orani'];
        $sgkIsveren = round($primeEsasKazanc * ($sgkIsverenOrani / 100), 2);
        $issizlikIsveren = round($primeEsasKazanc * ((float) $oranlar['issizlik_isveren_orani'] / 100), 2);
        $sgkToplam = round($sgkCalisan + $issizlikCalisan + $sgkIsveren + $issizlikIsveren, 2);
        $netOdeme = round($brutHakedis - $sgkCalisan - $issizlikCalisan - $gelirVergisi - $damgaVergisi, 2);
        $isverenMaliyeti = round($brutHakedis + $sgkIsveren + $issizlikIsveren, 2);

        return [
            'sgk_gunu' => $sgkGunu,
            'brut_hakedis' => $brutHakedis,
            'prime_esas_kazanc' => $primeEsasKazanc,
            'sgk_calisan' => $sgkCalisan,
            'issizlik_calisan' => $issizlikCalisan,
            'gelir_vergisi_matrahi' => $vergiMatrahi,
            'asgari_ucret_vergi_matrahi' => $asgariVergiMatrahi,
            'gelir_vergisi' => $gelirVergisi,
            'damga_vergisi' => $damgaVergisi,
            'vergi_toplam' => round($gelirVergisi + $damgaVergisi, 2),
            'net_odeme' => $netOdeme,
            'sgk_isveren_orani' => $sgkIsverenOrani,
            'sgk_isveren' => $sgkIsveren,
            'issizlik_isveren' => $issizlikIsveren,
            'sgk_toplam' => $sgkToplam,
            'isveren_maliyeti' => $isverenMaliyeti,
        ];
    }

    private function sgkGunSayisi(array $personel, string $ay, array $personelKayitlari): int
    {
        $ayBaslangici = $ay . '-01';
        $ayBitisi = date('Y-m-t', strtotime($ayBaslangici));
        $iseGiris = (string) ($personel['ise_giris_tarihi'] ?? '');
        $istenCikis = (string) ($personel['isten_cikis_tarihi'] ?? '');
        $baslangic = $iseGiris !== '' && $iseGiris > $ayBaslangici ? $iseGiris : $ayBaslangici;
        $bitis = $istenCikis !== '' && $istenCikis < $ayBitisi ? $istenCikis : $ayBitisi;
        if ($baslangic > $bitis || ($iseGiris !== '' && $iseGiris > $ayBitisi) || ($istenCikis !== '' && $istenCikis < $ayBaslangici)) {
            return 0;
        }

        $tamAy = $baslangic === $ayBaslangici && $bitis === $ayBitisi;
        $gun = $tamAy ? 30 : min(30, ((int) floor((strtotime($bitis) - strtotime($baslangic)) / 86400)) + 1);
        $ucretsizGun = 0;
        foreach ($personelKayitlari as $tarih => $kayit) {
            if ($tarih < $baslangic || $tarih > $bitis || !str_starts_with((string) $tarih, $ay . '-')) {
                continue;
            }
            if (in_array((string) ($kayit['durum'] ?? ''), self::UCRETSIZ_DURUMLAR, true)) {
                $ucretsizGun++;
            }
        }
        return max(0, $gun - $ucretsizGun);
    }

    private function kumulatifVergi(float $matrah): float
    {
        $matrah = max(0, $matrah);
        if ($matrah <= 190000) {
            return $matrah * 0.15;
        }
        if ($matrah <= 400000) {
            return 28500 + (($matrah - 190000) * 0.20);
        }
        if ($matrah <= 1500000) {
            return 70500 + (($matrah - 400000) * 0.27);
        }
        if ($matrah <= 5300000) {
            return 367500 + (($matrah - 1500000) * 0.35);
        }
        return 1697500 + (($matrah - 5300000) * 0.40);
    }

    private function bosSatir(array $personel, float $aylikBrut, string $tesvikTuru): array
    {
        return array_merge([
            'personel_id' => (int) ($personel['id'] ?? 0),
            'ad_soyad' => trim((string) ($personel['ad'] ?? '') . ' ' . (string) ($personel['soyad'] ?? '')),
            'aylik_brut_ucret' => $aylikBrut,
            'sgk_tesvik_turu' => $tesvikTuru,
            'sgk_tesvik_adi' => (string) self::TESVIKLER[$tesvikTuru]['ad'],
            'kumulatif_vergi_matrahi' => 0.0,
        ], $this->bosHesap(0));
    }

    private function bosHesap(int $sgkGunu): array
    {
        return [
            'sgk_gunu' => $sgkGunu, 'brut_hakedis' => 0.0, 'prime_esas_kazanc' => 0.0,
            'sgk_calisan' => 0.0, 'issizlik_calisan' => 0.0, 'gelir_vergisi_matrahi' => 0.0,
            'asgari_ucret_vergi_matrahi' => 0.0, 'gelir_vergisi' => 0.0, 'damga_vergisi' => 0.0,
            'vergi_toplam' => 0.0, 'net_odeme' => 0.0, 'sgk_isveren_orani' => 0.0,
            'sgk_isveren' => 0.0, 'issizlik_isveren' => 0.0, 'sgk_toplam' => 0.0,
            'isveren_maliyeti' => 0.0,
        ];
    }
}
