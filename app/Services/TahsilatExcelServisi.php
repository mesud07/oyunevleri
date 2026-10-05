<?php

declare(strict_types=1);

namespace App\Services;

use ZipArchive;

final class TahsilatExcelServisi
{
    private const YONTEM_ADLARI = [
        'nakit' => 'Nakit',
        'kredi_karti' => 'Kredi Kartı',
        'havale_eft' => 'Havale / EFT',
        'banka_havalesi' => 'Havale / EFT',
        'havale' => 'Havale / EFT',
        'odeme_baglantisi' => 'Ödeme Bağlantısı',
        'diger' => 'Diğer',
    ];

    public function olustur(array $analiz, array $tahsilatlar, string $kurumAdi): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new \RuntimeException('Excel raporu için ZIP eklentisi etkin değil.');
        }

        $ay = (string) ($analiz['ay'] ?? date('Y-m'));
        $dosyaEki = (string) ($analiz['dosya_eki'] ?? str_replace('-', '', $ay));
        $gecici = tempnam(sys_get_temp_dir(), 'talya-tahsilat-');
        if ($gecici === false) {
            throw new \RuntimeException('Geçici Excel dosyası oluşturulamadı.');
        }

        $zip = new ZipArchive();
        if ($zip->open($gecici, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($gecici);
            throw new \RuntimeException('Excel paketi açılamadı.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('docProps/app.xml', $this->appProps());
        $zip->addFromString('docProps/core.xml', $this->coreProps($ay));
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->ozetSayfasi($analiz, $kurumAdi));
        $zip->addFromString('xl/worksheets/sheet2.xml', $this->tahsilatSayfasi($analiz, $tahsilatlar));
        $zip->close();

        $icerik = file_get_contents($gecici);
        @unlink($gecici);
        if (!is_string($icerik) || !str_starts_with($icerik, 'PK')) {
            throw new \RuntimeException('Excel raporu paketlenemedi.');
        }

        return [
            'name' => 'tahsilatlar-' . preg_replace('/[^a-z0-9-]/i', '', $dosyaEki) . '.xlsx',
            'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'content' => $icerik,
        ];
    }

    private function ozetSayfasi(array $analiz, string $kurumAdi): string
    {
        $rows = [];
        $rows[] = '<row r="1" ht="30" customHeight="1">'
            . $this->inlineCell('A1', $this->temiz($kurumAdi) . ' Tahsilat Raporu', 1)
            . '</row>';
        $rows[] = '<row r="2" ht="22" customHeight="1">'
            . $this->inlineCell('A2', (string) ($analiz['ay_etiketi'] ?? ''), 2)
            . '</row>';

        $basliklar = ['Ödeme Türü', 'İşlem Adedi', 'Tahsilat Tutarı', 'Toplam Payı'];
        $baslikHucreleri = '';
        foreach ($basliklar as $index => $baslik) {
            $baslikHucreleri .= $this->inlineCell($this->kolonAdi($index + 1) . '4', $baslik, 3);
        }
        $rows[] = '<row r="4" ht="26" customHeight="1">' . $baslikHucreleri . '</row>';

        $satirNo = 5;
        foreach (($analiz['yontemler'] ?? []) as $yontem) {
            $rows[] = '<row r="' . $satirNo . '" ht="22" customHeight="1">'
                . $this->inlineCell('A' . $satirNo, (string) ($yontem['ad'] ?? ''), 4)
                . $this->numberCell('B' . $satirNo, (int) ($yontem['adet'] ?? 0), 5)
                . $this->numberCell('C' . $satirNo, (float) ($yontem['tutar'] ?? 0), 6)
                . $this->numberCell('D' . $satirNo, ((float) ($yontem['yuzde'] ?? 0)) / 100, 7)
                . '</row>';
            $satirNo++;
        }

        $toplamSatiri = $satirNo;
        $rows[] = '<row r="' . $toplamSatiri . '" ht="24" customHeight="1">'
            . $this->inlineCell('A' . $toplamSatiri, 'Toplam', 8)
            . $this->numberCell('B' . $toplamSatiri, (int) ($analiz['tahsilat_adedi'] ?? 0), 9)
            . $this->numberCell('C' . $toplamSatiri, (float) ($analiz['toplam_tahsilat'] ?? 0), 10)
            . $this->numberCell('D' . $toplamSatiri, (float) ($analiz['toplam_tahsilat'] ?? 0) > 0 ? 1 : 0, 11)
            . '</row>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols><col min="1" max="1" width="29" customWidth="1"/><col min="2" max="2" width="16" customWidth="1"/><col min="3" max="3" width="21" customWidth="1"/><col min="4" max="4" width="17" customWidth="1"/></cols>'
            . '<sheetData>' . implode('', $rows) . '</sheetData>'
            . '<mergeCells count="2"><mergeCell ref="A1:D1"/><mergeCell ref="A2:D2"/></mergeCells>'
            . '<autoFilter ref="A4:D' . ($toplamSatiri - 1) . '"/>'
            . '<pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.2" footer="0.2"/>'
            . '<pageSetup orientation="portrait" fitToWidth="1" fitToHeight="1" paperSize="9"/>'
            . '</worksheet>';
    }

    private function tahsilatSayfasi(array $analiz, array $tahsilatlar): string
    {
        $rows = [];
        $rows[] = '<row r="1" ht="28" customHeight="1">'
            . $this->inlineCell('A1', (string) ($analiz['ay_etiketi'] ?? '') . ' Tahsilat Detayı', 1)
            . '</row>';

        $basliklar = ['Tarih', 'Öğrenci', 'Veli', 'Paket', 'Ödeme Türü', 'Tutar', 'Makbuz No', 'Kasa', 'Açıklama'];
        $baslikHucreleri = '';
        foreach ($basliklar as $index => $baslik) {
            $baslikHucreleri .= $this->inlineCell($this->kolonAdi($index + 1) . '3', $baslik, 3);
        }
        $rows[] = '<row r="3" ht="26" customHeight="1">' . $baslikHucreleri . '</row>';

        $satirNo = 4;
        foreach ($tahsilatlar as $tahsilat) {
            $tarih = (string) ($tahsilat['tarih'] ?? '');
            $rows[] = '<row r="' . $satirNo . '" ht="22" customHeight="1">'
                . ($tarih !== '' ? $this->numberCell('A' . $satirNo, $this->excelTarihi($tarih), 12) : $this->inlineCell('A' . $satirNo, '', 4))
                . $this->inlineCell('B' . $satirNo, (string) ($tahsilat['ogrenci'] ?? ''), 4)
                . $this->inlineCell('C' . $satirNo, (string) ($tahsilat['veli'] ?? '-'), 4)
                . $this->inlineCell('D' . $satirNo, (string) ($tahsilat['paket_adi'] ?? ''), 4)
                . $this->inlineCell('E' . $satirNo, $this->yontemAdi((string) ($tahsilat['yontem'] ?? 'diger')), 4)
                . $this->numberCell('F' . $satirNo, (float) ($tahsilat['tutar'] ?? 0), 6)
                . $this->inlineCell('G' . $satirNo, (string) ($tahsilat['makbuz_numarasi'] ?? ''), 4)
                . $this->inlineCell('H' . $satirNo, (string) ($tahsilat['kasa'] ?? '-'), 4)
                . $this->inlineCell('I' . $satirNo, (string) ($tahsilat['aciklama'] ?? ''), 4)
                . '</row>';
            $satirNo++;
        }
        $sonSatir = max(3, $satirNo - 1);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="3" topLeftCell="A4" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols><col min="1" max="1" width="13" customWidth="1"/><col min="2" max="5" width="24" customWidth="1"/><col min="6" max="6" width="17" customWidth="1"/><col min="7" max="8" width="18" customWidth="1"/><col min="9" max="9" width="38" customWidth="1"/></cols>'
            . '<sheetData>' . implode('', $rows) . '</sheetData>'
            . '<mergeCells count="1"><mergeCell ref="A1:I1"/></mergeCells>'
            . '<autoFilter ref="A3:I' . $sonSatir . '"/>'
            . '<pageMargins left="0.3" right="0.3" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>'
            . '<pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0" paperSize="9"/>'
            . '</worksheet>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="3"><numFmt numFmtId="164" formatCode="#,##0.00 &quot;TL&quot;"/><numFmt numFmtId="165" formatCode="0.0%"/><numFmt numFmtId="166" formatCode="dd/mm/yyyy"/></numFmts>'
            . '<fonts count="5"><font><sz val="10"/><name val="Aptos"/></font><font><b/><sz val="15"/><color rgb="FFFFFFFF"/><name val="Aptos Display"/></font><font><i/><sz val="10"/><color rgb="FF526579"/><name val="Aptos"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Aptos"/></font><font><b/><sz val="10"/><name val="Aptos"/></font></fonts>'
            . '<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF176B87"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFE8F3F8"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="2"><border/><border><bottom style="thin"><color rgb="FFD5E3EC"/></bottom></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="13">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="4" fillId="3" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="4" fillId="3" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="right"/></xf>'
            . '<xf numFmtId="164" fontId="4" fillId="3" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right"/></xf>'
            . '<xf numFmtId="165" fontId="4" fillId="3" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right"/></xf>'
            . '<xf numFmtId="166" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><calcPr calcId="191029" fullCalcOnLoad="1" forceFullCalc="1"/><sheets><sheet name="Özet" sheetId="1" r:id="rId1"/><sheet name="Tahsilatlar" sheetId="2" r:id="rId2"/></sheets></workbook>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function appProps(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Oyun Evleri</Application></Properties>';
    }

    private function coreProps(string $ay): string
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator>Oyun Evleri</dc:creator><dc:title>' . $this->xml($ay . ' Tahsilat Raporu') . '</dc:title><dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created></cp:coreProperties>';
    }

    private function inlineCell(string $ref, string $value, int $style): string
    {
        return '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . $this->xml($value) . '</t></is></c>';
    }

    private function numberCell(string $ref, float|int $value, int $style): string
    {
        return '<c r="' . $ref . '" s="' . $style . '"><v>' . $value . '</v></c>';
    }

    private function excelTarihi(string $tarih): int
    {
        $zaman = strtotime($tarih . ' 00:00:00 UTC');
        return $zaman === false ? 0 : (int) floor($zaman / 86400) + 25569;
    }

    private function yontemAdi(string $kod): string
    {
        return self::YONTEM_ADLARI[$kod] ?? 'Diğer';
    }

    private function kolonAdi(int $sira): string
    {
        $ad = '';
        while ($sira > 0) {
            $sira--;
            $ad = chr(65 + ($sira % 26)) . $ad;
            $sira = intdiv($sira, 26);
        }
        return $ad;
    }

    private function temiz(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    private function xml(string $value): string
    {
        $value = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value) ?? '';
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
