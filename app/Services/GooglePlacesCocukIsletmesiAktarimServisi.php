<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Veritabani;

final class GooglePlacesCocukIsletmesiAktarimServisi
{
    private const ENDPOINT = 'https://places.googleapis.com/v1/places:searchText';
    private const ILCELER = ['Muratpaşa', 'Kepez', 'Konyaaltı', 'Döşemealtı', 'Aksu'];
    private const ARAMALAR = [
        'okul' => 'okul',
        'anaokulu' => 'anaokulu',
        'kres' => 'kreş',
        'oyun_evi' => 'çocuk oyun evi',
        'cocuk_parki' => 'çocuk oyun alanı',
    ];

    public function aktar(): int
    {
        $anahtar = trim((string) Config::get('GOOGLE_PLACES_API_KEY', Config::get('GOOGLE_MAPS_API_KEY', '')));
        if ($anahtar === '') {
            throw new \RuntimeException('Google Places API anahtarı tanımlı değil.');
        }

        $kayitlar = [];
        foreach (self::ILCELER as $ilce) {
            foreach (self::ARAMALAR as $kategori => $arama) {
                foreach ($this->ara($anahtar, $arama . ' ' . $ilce . ' Antalya') as $place) {
                    $id = trim((string) ($place['id'] ?? ''));
                    $konum = $place['location'] ?? [];
                    $ad = trim((string) ($place['displayName']['text'] ?? ''));
                    if ($id === '' || $ad === '' || !is_numeric($konum['latitude'] ?? null) || !is_numeric($konum['longitude'] ?? null)) {
                        continue;
                    }
                    $enlem = (float) $konum['latitude'];
                    $boylam = (float) $konum['longitude'];
                    if ($enlem < 36.70 || $enlem > 37.20 || $boylam < 30.40 || $boylam > 31.10) {
                        continue;
                    }
                    $kayitlar[$id] = [
                        'kaynak_kimligi' => $id,
                        'osm_id' => 1000000000000000000 + (int) hexdec(substr(hash('sha256', $id), 0, 15)),
                        'ad' => mb_substr($ad, 0, 255),
                        'kategori' => $this->kategoriBelirle($place, $kategori),
                        'adres' => mb_substr(trim((string) ($place['formattedAddress'] ?? '')), 0, 500) ?: null,
                        'ilce' => $ilce,
                        'enlem' => number_format($enlem, 7, '.', ''),
                        'boylam' => number_format($boylam, 7, '.', ''),
                    ];
                }
                usleep(120000);
            }
        }

        return $this->kaydet(array_values($kayitlar));
    }

    private function ara(string $anahtar, string $sorgu): array
    {
        $govde = json_encode([
            'textQuery' => $sorgu,
            'pageSize' => 20,
            'languageCode' => 'tr',
            'regionCode' => 'TR',
            'locationRestriction' => [
                'rectangle' => [
                    'low' => ['latitude' => 36.70, 'longitude' => 30.40],
                    'high' => ['latitude' => 37.20, 'longitude' => 31.10],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $referer = rtrim((string) Config::get('APP_URL', 'http://localhost:8080'), '/') . '/';
        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $govde,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Referer: ' . $referer,
                'X-Goog-Api-Key: ' . $anahtar,
                'X-Goog-FieldMask: places.id,places.displayName,places.formattedAddress,places.location,places.types',
            ],
        ]);
        $ham = curl_exec($ch);
        $durum = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (!is_string($ham) || $durum !== 200) {
            $hata = json_decode((string) $ham, true);
            throw new \RuntimeException((string) ($hata['error']['message'] ?? curl_error($ch) ?: 'Google Places sorgusu başarısız.'));
        }
        $veri = json_decode($ham, true, 512, JSON_THROW_ON_ERROR);
        return is_array($veri['places'] ?? null) ? $veri['places'] : [];
    }

    private function kaydet(array $kayitlar): int
    {
        $db = Veritabani::baglan();
        $db->beginTransaction();
        try {
            $db->exec('UPDATE cocuk_isletmeleri SET aktif = 0 WHERE kaynak = "google_places"');
            $stmt = $db->prepare(
                'INSERT INTO cocuk_isletmeleri
                    (osm_turu, osm_id, kaynak_kimligi, ad, kategori, adres, ilce, enlem, boylam,
                     kaynak, aktif, kaynak_guncellenme_tarihi, onbellek_son_tarihi)
                 VALUES
                    ("node", :osm_id, :kaynak_kimligi, :ad, :kategori, :adres, :ilce, :enlem, :boylam,
                     "google_places", 1, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))
                 ON DUPLICATE KEY UPDATE
                    kaynak_kimligi = VALUES(kaynak_kimligi), ad = VALUES(ad), kategori = VALUES(kategori),
                    adres = VALUES(adres), ilce = VALUES(ilce), enlem = VALUES(enlem), boylam = VALUES(boylam),
                    kaynak = "google_places", aktif = 1, kaynak_guncellenme_tarihi = NOW(),
                    onbellek_son_tarihi = DATE_ADD(NOW(), INTERVAL 30 DAY)'
            );
            foreach ($kayitlar as $kayit) {
                $stmt->execute($kayit);
            }
            $log = $db->prepare('INSERT INTO cocuk_isletme_aktarimlari (kaynak, kayit_sayisi) VALUES ("google_places", :adet)');
            $log->execute(['adet' => count($kayitlar)]);
            $db->commit();
            return count($kayitlar);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    private function kategoriBelirle(array $place, string $varsayilan): string
    {
        $ad = mb_strtolower((string) ($place['displayName']['text'] ?? ''), 'UTF-8');
        $turler = array_map('strtolower', is_array($place['types'] ?? null) ? $place['types'] : []);
        if (str_contains($ad, 'anaokul') || in_array('preschool', $turler, true)) return 'anaokulu';
        if (str_contains($ad, 'kreş') || str_contains($ad, 'kres') || in_array('child_care_agency', $turler, true)) return 'kres';
        if (str_contains($ad, 'oyun evi') || in_array('amusement_center', $turler, true)) return 'oyun_evi';
        if (in_array('playground', $turler, true)) return 'cocuk_parki';
        return $varsayilan;
    }
}
