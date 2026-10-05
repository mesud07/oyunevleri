<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;

final class GozlemRaporuServisi
{
    private const BOLUM_ANAHTARLARI = [
        'genel_gozlem',
        'etkinliklere_katilim',
        'yonerge_ve_grup_uyumu',
        'cocuk_gelisimci_gorusu',
        'psikolojik_danisman_gorusu',
        'genel_degerlendirme',
    ];

    public function olustur(array $ogrenci, array $notlar, string $baslangic, string $bitis): array
    {
        if (!$notlar) {
            throw new \InvalidArgumentException('Secilen donemde bu ogrenciye ait gunluk not bulunamadi.');
        }

        $apiKey = trim((string) Config::get('OPENAI_API_KEY', ''));
        if ($apiKey === '') {
            throw new \RuntimeException('Yapay zeka servisi henuz yapilandirilmamis. OPENAI_API_KEY tanimlanmalidir.');
        }

        $ogrenciAdi = trim((string) ($ogrenci['ad'] ?? '') . ' ' . (string) ($ogrenci['soyad'] ?? ''));
        $notMetinleri = array_map(function (array $not) use ($ogrenci): array {
            return [
                'tarih' => (string) ($not['tarih'] ?? ''),
                'kategori' => (string) ($not['kategori'] ?? 'Genel'),
                'not' => $this->anonimlestir(
                    mb_substr(trim((string) ($not['not_metni'] ?? '')), 0, 2000),
                    [(string) ($ogrenci['ad'] ?? ''), (string) ($ogrenci['soyad'] ?? '')]
                ),
                'grup' => (string) ($not['grup'] ?? ''),
            ];
        }, array_slice($notlar, 0, 150));

        $yasGrubu = $this->yasGrubu((string) ($ogrenci['dogum_tarihi'] ?? ''), $bitis);
        $girdi = [
            'ogrenci_adi' => 'Ogrenci',
            'yas_grubu' => $yasGrubu,
            'gozlem_baslangici' => $baslangic,
            'gozlem_bitisi' => $bitis,
            'gunluk_notlar' => $notMetinleri,
        ];

        $schemaProperties = [];
        foreach (self::BOLUM_ANAHTARLARI as $anahtar) {
            $schemaProperties[$anahtar] = ['type' => 'string'];
        }

        $payload = [
            'model' => (string) Config::get('OPENAI_MODEL', 'gpt-4.1-mini'),
            'store' => false,
            'instructions' => implode("\n", [
                'Sen Oyun Evleri icin okul oncesi gelisim gozlem raporu yazan uzman bir asistansin.',
                'Yalnizca verilen gunluk notlardaki gozlemlere dayan; bilgi, tani veya gelisimsel sonuc uydurma.',
                'Turkce, sicak, profesyonel, kapsayici ve veliye uygun bir dil kullan.',
                'Cocugu etiketleme; zorlandigi anlari gelisim surecinin dogal parcasi olarak, somut ve destekleyici dille anlat.',
                'Her bolum 1-3 kisa paragraf olsun. Gereksiz tekrar yapma.',
                'Cocuk gelisimci ve psikolojik danisman bolumlerinde tibbi tani koyma; gozleme dayali, uygulanabilir destek onerileri ver.',
                'Cikti yalnizca istenen JSON semasina uysun.',
            ]),
            'input' => [[
                'role' => 'user',
                'content' => [[
                    'type' => 'input_text',
                    'text' => json_encode($girdi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]],
            ]],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'gozlem_raporu',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => $schemaProperties,
                        'required' => self::BOLUM_ANAHTARLARI,
                        'additionalProperties' => false,
                    ],
                ],
            ],
        ];

        $yanit = $this->istek($apiKey, $payload);
        $metin = trim((string) ($yanit['output_text'] ?? ''));
        if ($metin === '') {
            foreach (($yanit['output'] ?? []) as $cikti) {
                foreach (($cikti['content'] ?? []) as $icerik) {
                    if (($icerik['type'] ?? '') === 'output_text' && isset($icerik['text'])) {
                        $metin .= (string) $icerik['text'];
                    }
                }
            }
        }

        $bolumler = json_decode($metin, true);
        if (!is_array($bolumler)) {
            throw new \RuntimeException('Yapay zeka raporu beklenen bicimde dondurmedi. Lutfen tekrar deneyin.');
        }
        foreach (self::BOLUM_ANAHTARLARI as $anahtar) {
            $bolumler[$anahtar] = mb_substr(trim((string) ($bolumler[$anahtar] ?? '')), 0, 6000);
            if ($bolumler[$anahtar] === '') {
                throw new \RuntimeException('Yapay zeka raporunda eksik bolum bulundu. Lutfen tekrar deneyin.');
            }
        }

        return [
            'ogrenci_id' => (int) ($ogrenci['id'] ?? 0),
            'ogrenci_adi' => $ogrenciAdi,
            'yas_grubu' => $yasGrubu,
            'baslangic' => $baslangic,
            'bitis' => $bitis,
            'not_sayisi' => count($notlar),
            'bolumler' => $bolumler,
        ];
    }

    private function istek(string $apiKey, array $payload): array
    {
        $ch = curl_init('https://api.openai.com/v1/responses');
        if ($ch === false) {
            throw new \RuntimeException('Yapay zeka baglantisi baslatilamadi.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_CONNECTTIMEOUT => max(3, (int) Config::get('OPENAI_CONNECT_TIMEOUT', 10)),
            CURLOPT_TIMEOUT => max(15, (int) Config::get('OPENAI_TIMEOUT', 60)),
        ]);

        $govde = curl_exec($ch);
        $durum = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlHatasi = curl_error($ch);
        curl_close($ch);

        if (!is_string($govde) || $govde === '') {
            throw new \RuntimeException('Yapay zeka servisine ulasilamadi' . ($curlHatasi !== '' ? ': ' . $curlHatasi : '.'));
        }

        $yanit = json_decode($govde, true);
        if (!is_array($yanit)) {
            throw new \RuntimeException('Yapay zeka servisi gecersiz yanit verdi.');
        }
        if ($durum < 200 || $durum >= 300) {
            $mesaj = (string) ($yanit['error']['message'] ?? 'Yapay zeka raporu olusturulamadi.');
            throw new \RuntimeException(mb_substr($mesaj, 0, 300));
        }

        return $yanit;
    }

    public function yasGrubu(string $dogumTarihi, string $referans): string
    {
        if ($dogumTarihi === '') {
            return 'Yas bilgisi belirtilmemis';
        }
        try {
            $dogum = new \DateTimeImmutable($dogumTarihi);
            $tarih = new \DateTimeImmutable($referans);
            if ($dogum > $tarih) {
                return 'Yas bilgisi belirtilmemis';
            }
            $fark = $dogum->diff($tarih);
            $ay = ((int) $fark->y * 12) + (int) $fark->m;
            $alt = intdiv(max(0, $ay - 1), 12) * 12 + 1;
            $ust = $alt + 11;
            return $alt . '-' . $ust . ' Ay';
        } catch (\Throwable $e) {
            return 'Yas bilgisi belirtilmemis';
        }
    }

    private function anonimlestir(string $metin, array $adParcalari): string
    {
        foreach ($adParcalari as $ad) {
            $ad = trim($ad);
            if (mb_strlen($ad) >= 2) {
                $metin = (string) preg_replace('/\b' . preg_quote($ad, '/') . '\b/iu', 'Ogrenci', $metin);
            }
        }
        $metin = (string) preg_replace('/\b[1-9][0-9]{10}\b/u', '[kimlik bilgisi gizlendi]', $metin);
        $metin = (string) preg_replace('/\b(?:\+?90\s*)?(?:0?5\d{2})[\s.-]*\d{3}[\s.-]*\d{2}[\s.-]*\d{2}\b/u', '[telefon gizlendi]', $metin);
        $metin = (string) preg_replace('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/iu', '[eposta gizlendi]', $metin);
        return $metin;
    }
}
