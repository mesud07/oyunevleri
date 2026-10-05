<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Veritabani;

final class CocukIsletmesiAktarimServisi
{
    private const ENDPOINTS = [
        'https://overpass-api.de/api/interpreter',
        'https://overpass.kumi.systems/api/interpreter',
    ];
    private const ANTALYA_BBOX = '36.7000,30.4000,37.2000,31.1000';

    public function aktar(): int
    {
        $veri = $this->indir();
        $elemanlar = is_array($veri['elements'] ?? null) ? $veri['elements'] : [];
        $db = Veritabani::baglan();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                'INSERT INTO cocuk_isletmeleri
                    (osm_turu, osm_id, ad, kategori, adres, ilce, telefon, web_sitesi,
                     enlem, boylam, kaynak, aktif, kaynak_guncellenme_tarihi)
                 VALUES
                    (:osm_turu, :osm_id, :ad, :kategori, :adres, :ilce, :telefon, :web_sitesi,
                     :enlem, :boylam, "openstreetmap", 1, NOW())
                 ON DUPLICATE KEY UPDATE
                    ad = VALUES(ad), kategori = VALUES(kategori), adres = VALUES(adres),
                    ilce = VALUES(ilce), telefon = VALUES(telefon), web_sitesi = VALUES(web_sitesi),
                    enlem = VALUES(enlem), boylam = VALUES(boylam), aktif = 1,
                    kaynak_guncellenme_tarihi = NOW()'
            );
            $adet = 0;
            $gorulen = [];
            foreach ($elemanlar as $eleman) {
                $kayit = $this->donustur($eleman);
                if ($kayit === null) {
                    continue;
                }
                $anahtar = $kayit['osm_turu'] . ':' . $kayit['osm_id'];
                if (isset($gorulen[$anahtar])) {
                    continue;
                }
                $gorulen[$anahtar] = true;
                $stmt->execute($kayit);
                $adet++;
            }
            $log = $db->prepare(
                'INSERT INTO cocuk_isletme_aktarimlari (kaynak, kayit_sayisi) VALUES ("openstreetmap", :adet)'
            );
            $log->execute(['adet' => $adet]);
            $db->commit();
            return $adet;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    private function indir(): array
    {
        $bbox = trim((string) Config::get('COCUK_ISLETMELERI_BBOX', self::ANTALYA_BBOX));
        if (!preg_match('/^-?\d+(?:\.\d+)?,-?\d+(?:\.\d+)?,-?\d+(?:\.\d+)?,-?\d+(?:\.\d+)?$/', $bbox)) {
            throw new \RuntimeException('Çocuk işletmeleri harita sınırı geçersiz.');
        }
        $sorgular = [
            '[out:json][timeout:75];('
                . 'nwr["amenity"~"^(school|kindergarten|childcare)$"](' . $bbox . ');'
                . 'nwr["education"~"^(preschool|kindergarten)$"](' . $bbox . ');'
                . 'nwr["social_facility"~"^(day_care|childcare)$"](' . $bbox . ');'
                . ');out center tags qt;',
            '[out:json][timeout:75];('
                . 'nwr["leisure"="playground"](' . $bbox . ');'
                . 'nwr["leisure"="indoor_playground"](' . $bbox . ');'
                . ');out center tags qt;',
            '[out:json][timeout:75];('
                . 'nwr["leisure"="amusement_arcade"]["name"~"oyun|çocuk|cocuk|kids",i](' . $bbox . ');'
                . 'nwr["amenity"="community_centre"]["name"~"oyun|çocuk|cocuk|kids|etkinlik",i](' . $bbox . ');'
                . 'nwr["tourism"="theme_park"]["name"~"oyun|çocuk|cocuk|kids",i](' . $bbox . ');'
                . ');out center tags qt;',
        ];

        $elemanlar = [];
        foreach ($sorgular as $sorgu) {
            $veri = $this->sorguyuIndir($sorgu);
            if (is_array($veri['elements'] ?? null)) {
                array_push($elemanlar, ...$veri['elements']);
            }
        }

        return ['elements' => $elemanlar];
    }

    private function sorguyuIndir(string $sorgu): array
    {
        $sonHata = '';
        foreach (self::ENDPOINTS as $endpoint) {
            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query(['data' => $sorgu]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 90,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/x-www-form-urlencoded',
                    'User-Agent: TalyaKidsAdresHaritasi/1.0',
                ],
            ]);
            $ham = curl_exec($ch);
            $durum = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $hata = curl_error($ch);
            if (is_string($ham) && $durum === 200) {
                $veri = json_decode($ham, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($veri)) {
                    return $veri;
                }
            }
            $sonHata = $hata ?: 'HTTP ' . $durum;
        }
        throw new \RuntimeException('OpenStreetMap işletme verisi alınamadı: ' . $sonHata);
    }

    private function donustur(array $eleman): ?array
    {
        $etiket = is_array($eleman['tags'] ?? null) ? $eleman['tags'] : [];
        $ad = trim((string) ($etiket['name'] ?? ''));
        $enlem = $eleman['lat'] ?? $eleman['center']['lat'] ?? null;
        $boylam = $eleman['lon'] ?? $eleman['center']['lon'] ?? null;
        if (!is_numeric($enlem) || !is_numeric($boylam)) {
            return null;
        }
        $kategori = self::kategoriBelirle($etiket, $ad);
        if ($ad === '') {
            $ad = match ($kategori) {
                'cocuk_parki' => 'Çocuk Oyun Alanı',
                'oyun_evi' => 'Çocuk Oyun Evi',
                default => '',
            };
        }
        if ($ad === '') {
            return null;
        }
        $adres = trim(implode(' ', array_filter([
            $etiket['addr:street'] ?? '', $etiket['addr:housenumber'] ?? '',
        ])));
        if ($adres === '') {
            $adres = trim((string) ($etiket['addr:full'] ?? ''));
        }
        return [
            'osm_turu' => (string) ($eleman['type'] ?? 'node'),
            'osm_id' => (int) ($eleman['id'] ?? 0),
            'ad' => mb_substr($ad, 0, 255),
            'kategori' => $kategori,
            'adres' => $adres !== '' ? mb_substr($adres, 0, 500) : null,
            'ilce' => mb_substr(trim((string) ($etiket['addr:district'] ?? $etiket['addr:suburb'] ?? '')), 0, 100) ?: null,
            'telefon' => mb_substr(trim((string) ($etiket['contact:phone'] ?? $etiket['phone'] ?? '')), 0, 100) ?: null,
            'web_sitesi' => mb_substr(trim((string) ($etiket['contact:website'] ?? $etiket['website'] ?? '')), 0, 500) ?: null,
            'enlem' => number_format((float) $enlem, 7, '.', ''),
            'boylam' => number_format((float) $boylam, 7, '.', ''),
        ];
    }

    public static function kategoriBelirle(array $etiket, string $ad): string
    {
        $amenity = strtolower((string) ($etiket['amenity'] ?? ''));
        $education = strtolower((string) ($etiket['education'] ?? ''));
        $socialFacility = strtolower((string) ($etiket['social_facility'] ?? ''));
        $leisure = strtolower((string) ($etiket['leisure'] ?? ''));
        $aranan = mb_strtolower($ad, 'UTF-8');
        if ($amenity === 'kindergarten' || in_array($education, ['preschool', 'kindergarten'], true) || str_contains($aranan, 'anaokul')) {
            return 'anaokulu';
        }
        if ($amenity === 'childcare' || in_array($socialFacility, ['day_care', 'childcare'], true) || str_contains($aranan, 'kreş') || str_contains($aranan, 'kres')) {
            return 'kres';
        }
        if ($amenity === 'school') {
            return 'okul';
        }
        if ($leisure === 'indoor_playground' || str_contains($aranan, 'oyun evi') || ($etiket['indoor'] ?? '') === 'yes') {
            return 'oyun_evi';
        }
        if ($leisure === 'playground' || isset($etiket['playground'])) {
            return 'cocuk_parki';
        }
        if (str_contains($aranan, 'oyun') || str_contains($aranan, 'çocuk') || str_contains($aranan, 'cocuk') || str_contains($aranan, 'kids')) {
            return 'oyun_evi';
        }
        return 'diger';
    }
}
