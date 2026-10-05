<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMXPath;
use ZipArchive;

final class OgrenciTopluImportServisi
{
    private const MAKSIMUM_SATIR = 500;
    private const MAKSIMUM_ACILMIS_BOYUT = 15_000_000;

    private const SUTUNLAR = [
        'ogrenciadi' => 'ogrenci_adi',
        'ogrencisoyadi' => 'ogrenci_soyadi',
        'tckimlikno' => 'ogrenci_tc_kimlik_no',
        'dogumtarihi' => 'ogrenci_dogum_tarihi',
        'cinsiyet' => 'ogrenci_cinsiyet',
        'kayittarihi' => 'ogrenci_kayit_tarihi',
        'veliadi' => 'veli_adi',
        'velisoyadi' => 'veli_soyadi',
        'velitelefonu' => 'veli_telefon',
        'veliyedektelefonu' => 'veli_yedek_telefon',
        'velieposta' => 'veli_eposta',
        'yakinlik' => 'veli_yakinlik',
        'il' => 'il',
        'ilce' => 'ilce',
        'adres' => 'adres',
        'acildurumkisisi' => 'acil_durum_kisi',
        'acildurumtelefonu' => 'acil_durum_telefon',
        'saglikbilgisi' => 'saglik_bilgisi',
        'alerjibilgisi' => 'alerji_bilgisi',
        'ogrencinotu' => 'ogrenci_notu',
    ];

    private const ZORUNLU_SUTUNLAR = [
        'ogrenci_adi', 'ogrenci_soyadi', 'veli_adi', 'veli_soyadi', 'veli_telefon',
    ];

    public function oku(string $dosyaYolu): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new \RuntimeException('Excel aktarımı için ZIP eklentisi etkin değil.');
        }

        $zip = new ZipArchive();
        if ($zip->open($dosyaYolu) !== true) {
            throw new \InvalidArgumentException('Excel dosyası açılamadı. Şablonu yeniden indirip deneyin.');
        }

        try {
            $this->paketiDogrula($zip);
            $paylasilanMetinler = $this->paylasilanMetinleriOku($zip);
            $sayfaXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if (!is_string($sayfaXml) || $sayfaXml === '') {
                throw new \InvalidArgumentException('Excel dosyasında Öğrenciler sayfası bulunamadı.');
            }
            $satirlar = $this->sayfayiOku($sayfaXml, $paylasilanMetinler);
        } finally {
            $zip->close();
        }

        return $this->satirlariHazirla($satirlar);
    }

    private function paketiDogrula(ZipArchive $zip): void
    {
        if ($zip->numFiles < 1 || $zip->numFiles > 250) {
            throw new \InvalidArgumentException('Excel dosyasının içeriği geçersiz veya çok büyük.');
        }

        $toplamBoyut = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $bilgi = $zip->statIndex($i);
            if (!is_array($bilgi)) {
                continue;
            }
            $ad = (string) ($bilgi['name'] ?? '');
            if ($ad === '' || str_contains($ad, '../') || str_starts_with($ad, '/')) {
                throw new \InvalidArgumentException('Excel dosyasında güvenli olmayan içerik bulundu.');
            }
            $toplamBoyut += (int) ($bilgi['size'] ?? 0);
            if ($toplamBoyut > self::MAKSIMUM_ACILMIS_BOYUT) {
                throw new \InvalidArgumentException('Excel dosyasının açılmış boyutu izin verilen sınırı aşıyor.');
            }
        }
    }

    private function paylasilanMetinleriOku(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if (!is_string($xml) || $xml === '') {
            return [];
        }

        [$dom, $xpath] = $this->xmlHazirla($xml);
        $sonuc = [];
        foreach ($xpath->query('//x:si') ?: [] as $dugum) {
            $metin = '';
            foreach ($xpath->query('.//x:t', $dugum) ?: [] as $parca) {
                $metin .= $parca->textContent;
            }
            $sonuc[] = $metin;
        }
        unset($dom);
        return $sonuc;
    }

    private function sayfayiOku(string $xml, array $paylasilanMetinler): array
    {
        [$dom, $xpath] = $this->xmlHazirla($xml);
        $satirlar = [];
        foreach ($xpath->query('//x:sheetData/x:row') ?: [] as $satirDugumu) {
            $satirNo = (int) $satirDugumu->attributes?->getNamedItem('r')?->nodeValue;
            if ($satirNo < 1 || $satirNo > self::MAKSIMUM_SATIR + 20) {
                continue;
            }
            $hucreler = [];
            foreach ($xpath->query('./x:c', $satirDugumu) ?: [] as $hucre) {
                $referans = (string) $hucre->attributes?->getNamedItem('r')?->nodeValue;
                if (!preg_match('/^([A-Z]+)\d+$/', $referans, $eslesme)) {
                    continue;
                }
                $kolon = $this->kolonNumarasi($eslesme[1]);
                if ($kolon < 1 || $kolon > 80) {
                    continue;
                }
                $tur = (string) $hucre->attributes?->getNamedItem('t')?->nodeValue;
                if ($tur === 'inlineStr') {
                    $deger = '';
                    foreach ($xpath->query('.//x:is//x:t', $hucre) ?: [] as $parca) {
                        $deger .= $parca->textContent;
                    }
                } else {
                    $v = $xpath->query('./x:v', $hucre)?->item(0)?->textContent ?? '';
                    $deger = $tur === 's' ? (string) ($paylasilanMetinler[(int) $v] ?? '') : (string) $v;
                }
                $hucreler[$kolon] = trim($deger);
            }
            if ($hucreler !== []) {
                $satirlar[$satirNo] = $hucreler;
            }
        }
        unset($dom);
        return $satirlar;
    }

    private function satirlariHazirla(array $satirlar): array
    {
        $baslikSatiri = 0;
        $sutunHaritasi = [];
        foreach ($satirlar as $satirNo => $hucreler) {
            if ($satirNo > 10) {
                break;
            }
            foreach ($hucreler as $kolon => $deger) {
                $anahtar = self::SUTUNLAR[$this->baslikNormalize($deger)] ?? null;
                if ($anahtar !== null) {
                    $sutunHaritasi[$kolon] = $anahtar;
                }
            }
            if (in_array('ogrenci_adi', $sutunHaritasi, true) && in_array('veli_telefon', $sutunHaritasi, true)) {
                $baslikSatiri = $satirNo;
                break;
            }
            $sutunHaritasi = [];
        }

        if ($baslikSatiri === 0) {
            throw new \InvalidArgumentException('Şablon başlıkları bulunamadı. Lütfen uygulamadan indirilen Excel şablonunu kullanın.');
        }
        $eksik = array_diff(self::ZORUNLU_SUTUNLAR, array_values($sutunHaritasi));
        if ($eksik !== []) {
            throw new \InvalidArgumentException('Şablondaki zorunlu sütunlardan biri eksik. Şablonu yeniden indirin.');
        }

        $kayitlar = [];
        $hatalar = [];
        $gorulenler = [];
        foreach ($satirlar as $satirNo => $hucreler) {
            if ($satirNo <= $baslikSatiri) {
                continue;
            }
            if (count($kayitlar) + count($hatalar) >= self::MAKSIMUM_SATIR) {
                $hatalar[] = ['satir' => $satirNo, 'mesaj' => 'Tek dosyada en fazla ' . self::MAKSIMUM_SATIR . ' öğrenci aktarılabilir.'];
                break;
            }
            $veri = [];
            foreach ($sutunHaritasi as $kolon => $alan) {
                $veri[$alan] = trim((string) ($hucreler[$kolon] ?? ''));
            }
            if ($this->bosSatirMi($veri)) {
                continue;
            }

            $sonuc = $this->kaydiDogrula($veri);
            if ($sonuc['hatalar'] !== []) {
                $hatalar[] = ['satir' => $satirNo, 'mesaj' => implode(' ', $sonuc['hatalar'])];
                continue;
            }

            $kayit = $sonuc['kayit'];
            $benzersiz = $kayit['ogrenci_tc_kimlik_no'] !== ''
                ? 'tc:' . $kayit['ogrenci_tc_kimlik_no']
                : 'ad:' . $this->metinNormalize($kayit['ogrenci_adi'] . ' ' . $kayit['ogrenci_soyadi'])
                    . '|' . $kayit['ogrenci_dogum_tarihi'] . '|' . preg_replace('/\D+/', '', $kayit['veli_telefon']);
            if (isset($gorulenler[$benzersiz])) {
                $hatalar[] = ['satir' => $satirNo, 'mesaj' => 'Aynı öğrenci dosyada birden fazla kez yer alıyor (ilk satır: ' . $gorulenler[$benzersiz] . ').'];
                continue;
            }
            $gorulenler[$benzersiz] = $satirNo;
            $kayit['satir'] = $satirNo;
            $kayitlar[] = $kayit;
        }

        if ($kayitlar === [] && $hatalar === []) {
            throw new \InvalidArgumentException('Excel dosyasında aktarılacak öğrenci satırı bulunamadı.');
        }

        return ['kayitlar' => $kayitlar, 'hatalar' => $hatalar];
    }

    private function kaydiDogrula(array $veri): array
    {
        $hatalar = [];
        foreach (self::ZORUNLU_SUTUNLAR as $alan) {
            if (trim((string) ($veri[$alan] ?? '')) === '') {
                $hatalar[] = $this->alanEtiketi($alan) . ' zorunludur.';
            }
        }

        foreach (['ogrenci_adi', 'ogrenci_soyadi', 'veli_adi', 'veli_soyadi'] as $alan) {
            if (mb_strlen((string) ($veri[$alan] ?? '')) > 100) {
                $hatalar[] = $this->alanEtiketi($alan) . ' en fazla 100 karakter olabilir.';
            }
        }

        $tc = $this->sayisalMetin((string) ($veri['ogrenci_tc_kimlik_no'] ?? ''));
        if ($tc !== '' && !preg_match('/^\d{11}$/', $tc)) {
            $hatalar[] = 'TC Kimlik No 11 rakam olmalıdır.';
        }

        $dogum = $this->tarihNormalize((string) ($veri['ogrenci_dogum_tarihi'] ?? ''));
        if (($veri['ogrenci_dogum_tarihi'] ?? '') !== '' && $dogum === null) {
            $hatalar[] = 'Doğum Tarihi geçersizdir. GG.AA.YYYY veya YYYY-AA-GG kullanın.';
        } elseif ($dogum !== null && $dogum > date('Y-m-d')) {
            $hatalar[] = 'Doğum Tarihi gelecekte olamaz.';
        }

        $kayitTarihi = $this->tarihNormalize((string) ($veri['ogrenci_kayit_tarihi'] ?? ''));
        if (($veri['ogrenci_kayit_tarihi'] ?? '') !== '' && $kayitTarihi === null) {
            $hatalar[] = 'Kayıt Tarihi geçersizdir. GG.AA.YYYY veya YYYY-AA-GG kullanın.';
        }

        $cinsiyet = $this->cinsiyetNormalize((string) ($veri['ogrenci_cinsiyet'] ?? ''));
        if ($cinsiyet === null) {
            $hatalar[] = 'Cinsiyet Kız, Erkek veya Belirtilmedi olmalıdır.';
        }

        $telefon = $this->telefonNormalize((string) ($veri['veli_telefon'] ?? ''));
        if ($telefon === null) {
            $hatalar[] = 'Veli Telefonu 10 haneli geçerli bir Türkiye telefonu olmalıdır.';
        }
        $yedekTelefon = $this->telefonNormalize((string) ($veri['veli_yedek_telefon'] ?? ''), true);
        if ($yedekTelefon === null) {
            $hatalar[] = 'Veli Yedek Telefonu geçersizdir.';
        }
        $acilTelefon = $this->telefonNormalize((string) ($veri['acil_durum_telefon'] ?? ''), true);
        if ($acilTelefon === null) {
            $hatalar[] = 'Acil Durum Telefonu geçersizdir.';
        }

        $eposta = trim((string) ($veri['veli_eposta'] ?? ''));
        if ($eposta !== '' && (mb_strlen($eposta) > 190 || filter_var($eposta, FILTER_VALIDATE_EMAIL) === false)) {
            $hatalar[] = 'Veli E-posta adresi geçersizdir.';
        }

        if ($hatalar !== []) {
            return ['kayit' => [], 'hatalar' => $hatalar];
        }

        return [
            'hatalar' => [],
            'kayit' => [
                'ogrenci_adi' => trim((string) $veri['ogrenci_adi']),
                'ogrenci_soyadi' => trim((string) $veri['ogrenci_soyadi']),
                'ogrenci_tc_kimlik_no' => $tc,
                'ogrenci_dogum_tarihi' => $dogum ?? '',
                'ogrenci_cinsiyet' => $cinsiyet ?? 'belirtilmedi',
                'ogrenci_kayit_tarihi' => $kayitTarihi ?? date('Y-m-d'),
                'veli_adi' => trim((string) $veri['veli_adi']),
                'veli_soyadi' => trim((string) $veri['veli_soyadi']),
                'veli_telefon' => $telefon ?? '',
                'veli_yedek_telefon' => $yedekTelefon ?? '',
                'veli_eposta' => $eposta,
                'veli_yakinlik' => mb_substr(trim((string) ($veri['veli_yakinlik'] ?? '')), 0, 50),
                'il' => mb_substr(trim((string) ($veri['il'] ?? '')) ?: 'Antalya', 0, 100),
                'ilce' => mb_substr(trim((string) ($veri['ilce'] ?? '')), 0, 100),
                'adres' => mb_substr(trim((string) ($veri['adres'] ?? '')), 0, 1000),
                'acil_durum_kisi' => mb_substr(trim((string) ($veri['acil_durum_kisi'] ?? '')), 0, 190),
                'acil_durum_telefon' => $acilTelefon ?? '',
                'saglik_bilgisi' => mb_substr(trim((string) ($veri['saglik_bilgisi'] ?? '')), 0, 2000),
                'alerji_bilgisi' => mb_substr(trim((string) ($veri['alerji_bilgisi'] ?? '')), 0, 2000),
                'ogrenci_notu' => mb_substr(trim((string) ($veri['ogrenci_notu'] ?? '')), 0, 2000),
            ],
        ];
    }

    private function tarihNormalize(string $deger): ?string
    {
        $deger = trim($deger);
        if ($deger === '') {
            return null;
        }
        if (is_numeric($deger)) {
            $seri = (float) $deger;
            if ($seri >= 1 && $seri <= 100000) {
                return (new \DateTimeImmutable('1899-12-30'))->modify('+' . (int) floor($seri) . ' days')->format('Y-m-d');
            }
        }
        foreach (['!Y-m-d', '!d.m.Y', '!d/m/Y'] as $format) {
            $tarih = \DateTimeImmutable::createFromFormat($format, $deger);
            $beklenen = str_replace(['!Y-m-d', '!d.m.Y', '!d/m/Y'], ['Y-m-d', 'd.m.Y', 'd/m/Y'], $format);
            if ($tarih !== false && $tarih->format($beklenen) === $deger) {
                return $tarih->format('Y-m-d');
            }
        }
        return null;
    }

    private function cinsiyetNormalize(string $deger): ?string
    {
        $deger = $this->metinNormalize($deger === '' ? 'belirtilmedi' : $deger);
        return match ($deger) {
            'kiz' => 'kiz',
            'erkek' => 'erkek',
            'belirtilmedi' => 'belirtilmedi',
            default => null,
        };
    }

    private function telefonNormalize(string $deger, bool $bosOlabilir = false): ?string
    {
        $rakamlar = $this->sayisalMetin($deger);
        if ($rakamlar === '') {
            return $bosOlabilir ? '' : null;
        }
        if (str_starts_with($rakamlar, '90') && strlen($rakamlar) === 12) {
            $rakamlar = substr($rakamlar, 2);
        }
        if (str_starts_with($rakamlar, '0')) {
            $rakamlar = substr($rakamlar, 1);
        }
        if (!preg_match('/^\d{10}$/', $rakamlar)) {
            return null;
        }
        return '0(' . substr($rakamlar, 0, 3) . ') ' . substr($rakamlar, 3, 3) . ' ' . substr($rakamlar, 6, 2) . ' ' . substr($rakamlar, 8, 2);
    }

    private function sayisalMetin(string $deger): string
    {
        $deger = trim($deger);
        if (preg_match('/^[0-9]+(?:\.0+)?$/', $deger)) {
            return preg_replace('/\.0+$/', '', $deger) ?? '';
        }
        if (is_numeric($deger) && str_contains(strtolower($deger), 'e')) {
            return sprintf('%.0f', (float) $deger);
        }
        return preg_replace('/\D+/', '', $deger) ?? '';
    }

    private function bosSatirMi(array $veri): bool
    {
        foreach ($veri as $deger) {
            if (trim((string) $deger) !== '') {
                return false;
            }
        }
        return true;
    }

    private function alanEtiketi(string $alan): string
    {
        return [
            'ogrenci_adi' => 'Öğrenci Adı',
            'ogrenci_soyadi' => 'Öğrenci Soyadı',
            'veli_adi' => 'Veli Adı',
            'veli_soyadi' => 'Veli Soyadı',
            'veli_telefon' => 'Veli Telefonu',
        ][$alan] ?? $alan;
    }

    private function baslikNormalize(string $deger): string
    {
        $deger = str_replace('*', '', $deger);
        return preg_replace('/[^a-z0-9]+/', '', $this->metinNormalize($deger)) ?? '';
    }

    private function metinNormalize(string $deger): string
    {
        return strtr(mb_strtolower(trim($deger), 'UTF-8'), [
            'ı' => 'i', 'ş' => 's', 'ğ' => 'g', 'ü' => 'u', 'ö' => 'o', 'ç' => 'c',
        ]);
    }

    private function kolonNumarasi(string $harfler): int
    {
        $sonuc = 0;
        foreach (str_split($harfler) as $harf) {
            $sonuc = ($sonuc * 26) + (ord($harf) - 64);
        }
        return $sonuc;
    }

    private function xmlHazirla(string $xml): array
    {
        $onceki = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $basarili = $dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($onceki);
        if (!$basarili) {
            throw new \InvalidArgumentException('Excel dosyasındaki XML içeriği okunamadı.');
        }
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        return [$dom, $xpath];
    }
}
