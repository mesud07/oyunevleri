<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Personel;
use ZipArchive;

final class PuantajExcelServisi
{
    public function olustur(array $aylik, string $kurumAdi): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new \RuntimeException('Excel raporu için ZIP eklentisi etkin değil.');
        }

        $ay = (string) ($aylik['ay'] ?? date('Y-m'));
        $gecici = tempnam(sys_get_temp_dir(), 'talya-puantaj-');
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
        $zip->addFromString('docProps/core.xml', $this->coreProps());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->puantajSayfasi($aylik, $kurumAdi));
        $zip->addFromString('xl/worksheets/sheet2.xml', $this->hareketSayfasi($aylik));
        $zip->addFromString('xl/worksheets/sheet3.xml', $this->bordroSayfasi($aylik));
        $zip->close();

        $icerik = file_get_contents($gecici);
        @unlink($gecici);
        if (!is_string($icerik) || !str_starts_with($icerik, 'PK')) {
            throw new \RuntimeException('Excel raporu paketlenemedi.');
        }

        return [
            'name' => 'personel-puantaj-' . str_replace('-', '', $ay) . '.xlsx',
            'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'content' => $icerik,
        ];
    }

    private function puantajSayfasi(array $aylik, string $kurumAdi): string
    {
        $ay = (string) $aylik['ay'];
        $gunSayisi = (int) date('t', strtotime($ay . '-01'));
        $personeller = $aylik['personeller'] ?? [];
        $kayitlar = $aylik['kayitlar'] ?? [];
        $ilkGunKolon = 3;
        $sonGunKolon = $ilkGunKolon + $gunSayisi - 1;
        $ozetler = [
            ['Ç.G.S', 'X'], ['R.G.S', 'R'], ['İ.G.S', 'İ'], ['R.T.S', 'RT'],
            ['H.S.S', 'H'], ['D.G.S', 'D'], ['TOPLAM', null],
        ];
        $sonKolon = $sonGunKolon + count($ozetler);
        $rows = [];
        $ayEtiketi = $this->ayEtiketi($ay);
        $rows[] = '<row r="1" ht="28" customHeight="1">'
            . $this->inlineCell('A1', 'AİT OLDUĞU AY: ' . $ayEtiketi, 1)
            . $this->inlineCell($this->kolonAdi($ilkGunKolon) . '1', $this->temiz($kurumAdi) . ' ' . $ayEtiketi . ' Puantaj Tablosu', 1)
            . '</row>';

        $header2 = $this->inlineCell('A2', 'T.C. KİMLİK NO', 2) . $this->inlineCell('B2', 'ADI SOYADI', 2);
        $header3 = $this->inlineCell('A3', '', 2) . $this->inlineCell('B3', '', 2);
        for ($gun = 1; $gun <= $gunSayisi; $gun++) {
            $tarih = sprintf('%s-%02d', $ay, $gun);
            $haftaSonu = (int) date('N', strtotime($tarih)) >= 6;
            $kolon = $this->kolonAdi($ilkGunKolon + $gun - 1);
            $header2 .= $this->inlineCell($kolon . '2', date('d.m.Y', strtotime($tarih)), $haftaSonu ? 4 : 3);
            $header3 .= $this->inlineCell($kolon . '3', $this->gunKisa($tarih), $haftaSonu ? 4 : 3);
        }
        foreach ($ozetler as $index => $ozet) {
            $kolon = $this->kolonAdi($sonGunKolon + $index + 1);
            $header2 .= $this->inlineCell($kolon . '2', $ozet[0], 8);
            $header3 .= $this->inlineCell($kolon . '3', '', 8);
        }
        $rows[] = '<row r="2" ht="92" customHeight="1">' . $header2 . '</row>';
        $rows[] = '<row r="3" ht="24" customHeight="1">' . $header3 . '</row>';

        foreach ($personeller as $index => $personel) {
            $rowNo = $index + 4;
            $personelId = (int) $personel['id'];
            $cells = $this->inlineCell('A' . $rowNo, (string) ($personel['tc_kimlik_no'] ?? ''), 5)
                . $this->inlineCell('B' . $rowNo, trim((string) $personel['ad'] . ' ' . (string) $personel['soyad']), 5);
            for ($gun = 1; $gun <= $gunSayisi; $gun++) {
                $tarih = sprintf('%s-%02d', $ay, $gun);
                $haftaSonu = (int) date('N', strtotime($tarih)) >= 6;
                $kayit = $kayitlar[$personelId][$tarih] ?? null;
                $kod = $kayit ? (Personel::DURUMLAR[(string) $kayit['durum']]['kod'] ?? '') : ($haftaSonu ? 'H' : '');
                $kolon = $this->kolonAdi($ilkGunKolon + $gun - 1);
                $cells .= $this->inlineCell($kolon . $rowNo, $kod, $haftaSonu ? 7 : 6);
            }
            $gunAraligi = $this->kolonAdi($ilkGunKolon) . $rowNo . ':' . $this->kolonAdi($sonGunKolon) . $rowNo;
            foreach ($ozetler as $ozetIndex => $ozet) {
                $kolon = $this->kolonAdi($sonGunKolon + $ozetIndex + 1);
                $formula = $ozet[1] === null
                    ? 'COUNTA(' . $gunAraligi . ')'
                    : 'COUNTIF(' . $gunAraligi . ',&quot;' . $this->xml((string) $ozet[1]) . '&quot;)';
                $cells .= $this->formulaCell($kolon . $rowNo, $formula, 9);
            }
            $rows[] = '<row r="' . $rowNo . '" ht="22" customHeight="1">' . $cells . '</row>';
        }

        $mergeTitleStart = $this->kolonAdi($ilkGunKolon) . '1';
        $mergeTitleEnd = $this->kolonAdi($sonKolon) . '1';
        $cols = '<cols><col min="1" max="1" width="16" customWidth="1"/><col min="2" max="2" width="26" customWidth="1"/>'
            . '<col min="3" max="' . $sonGunKolon . '" width="4.2" customWidth="1"/>'
            . '<col min="' . ($sonGunKolon + 1) . '" max="' . $sonKolon . '" width="6" customWidth="1"/></cols>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane xSplit="2" ySplit="3" topLeftCell="C4" activePane="bottomRight" state="frozen"/></sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>' . $cols
            . '<sheetData>' . implode('', $rows) . '</sheetData>'
            . '<mergeCells count="4"><mergeCell ref="A1:B1"/><mergeCell ref="' . $mergeTitleStart . ':' . $mergeTitleEnd . '"/><mergeCell ref="A2:A3"/><mergeCell ref="B2:B3"/></mergeCells>'
            . '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>'
            . '<pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0" paperSize="9"/>'
            . '</worksheet>';
    }

    private function hareketSayfasi(array $aylik): string
    {
        $rows = [];
        $headers = ['Tarih', 'T.C. Kimlik No', 'Personel', 'Durum', 'Giriş', 'Çıkış', 'Mola (dk)', 'Çalışma (saat)', 'Açıklama'];
        $headerCells = '';
        foreach ($headers as $index => $header) {
            $headerCells .= $this->inlineCell($this->kolonAdi($index + 1) . '1', $header, 10);
        }
        $rows[] = '<row r="1" ht="26" customHeight="1">' . $headerCells . '</row>';
        $rowNo = 2;
        foreach (($aylik['personeller'] ?? []) as $personel) {
            $personelId = (int) $personel['id'];
            foreach (($aylik['kayitlar'][$personelId] ?? []) as $tarih => $kayit) {
                $giris = substr((string) ($kayit['giris_saati'] ?? ''), 0, 5);
                $cikis = substr((string) ($kayit['cikis_saati'] ?? ''), 0, 5);
                $mola = (int) ($kayit['mola_dakika'] ?? 0);
                $saat = '';
                if ($giris !== '' && $cikis !== '') {
                    $dakika = max(0, (int) round((strtotime($cikis) - strtotime($giris)) / 60) - $mola);
                    $saat = round($dakika / 60, 2);
                }
                $cells = $this->inlineCell('A' . $rowNo, date('d.m.Y', strtotime((string) $tarih)), 11)
                    . $this->inlineCell('B' . $rowNo, (string) ($personel['tc_kimlik_no'] ?? ''), 11)
                    . $this->inlineCell('C' . $rowNo, trim((string) $personel['ad'] . ' ' . (string) $personel['soyad']), 11)
                    . $this->inlineCell('D' . $rowNo, Personel::DURUMLAR[(string) $kayit['durum']]['ad'] ?? (string) $kayit['durum'], 11)
                    . $this->inlineCell('E' . $rowNo, $giris, 11)
                    . $this->inlineCell('F' . $rowNo, $cikis, 11)
                    . $this->numberCell('G' . $rowNo, $mola, 11)
                    . ($saat === '' ? $this->inlineCell('H' . $rowNo, '', 12) : $this->numberCell('H' . $rowNo, (float) $saat, 12))
                    . $this->inlineCell('I' . $rowNo, (string) ($kayit['aciklama'] ?? ''), 11);
                $rows[] = '<row r="' . $rowNo . '" ht="21" customHeight="1">' . $cells . '</row>';
                $rowNo++;
            }
        }
        $lastRow = max(1, $rowNo - 1);
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols><col min="1" max="1" width="13" customWidth="1"/><col min="2" max="2" width="16" customWidth="1"/><col min="3" max="4" width="22" customWidth="1"/><col min="5" max="6" width="10" customWidth="1"/><col min="7" max="8" width="14" customWidth="1"/><col min="9" max="9" width="38" customWidth="1"/></cols>'
            . '<sheetData>' . implode('', $rows) . '</sheetData><autoFilter ref="A1:I' . $lastRow . '"/>'
            . '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>'
            . '</worksheet>';
    }

    private function bordroSayfasi(array $aylik): string
    {
        $bordro = $aylik['bordro'] ?? ['destekleniyor' => false, 'satirlar' => []];
        $headers = [
            'Personel', 'Aylık Brüt', 'SGK Gün', 'Brüt Hakediş', 'Çalışan SGK', 'Çalışan İşsizlik',
            'Gelir Vergisi', 'Damga Vergisi', 'Net Ödeme', 'İşveren SGK', 'İşveren İşsizlik',
            'Toplam SGK', 'İşveren Maliyeti', 'SGK Teşviki',
        ];
        $rows = [];
        $rows[] = '<row r="1" ht="28" customHeight="1">' . $this->inlineCell('A1', $this->ayEtiketi((string) ($aylik['ay'] ?? date('Y-m'))) . ' Maaş ve SGK Tahmini', 1) . '</row>';
        $headerCells = '';
        foreach ($headers as $index => $header) {
            $headerCells .= $this->inlineCell($this->kolonAdi($index + 1) . '2', $header, 10);
        }
        $rows[] = '<row r="2" ht="30" customHeight="1">' . $headerCells . '</row>';

        $rowNo = 3;
        if (empty($bordro['destekleniyor'])) {
            $rows[] = '<row r="3">' . $this->inlineCell('A3', 'Seçilen yıl için bordro oranları tanımlı değildir.', 11) . '</row>';
            $rowNo++;
        } else {
            foreach (($bordro['satirlar'] ?? []) as $satir) {
                $cells = $this->inlineCell('A' . $rowNo, (string) ($satir['ad_soyad'] ?? ''), 11)
                    . $this->numberCell('B' . $rowNo, (float) ($satir['aylik_brut_ucret'] ?? 0), 12)
                    . $this->numberCell('C' . $rowNo, (int) ($satir['sgk_gunu'] ?? 0), 11)
                    . $this->numberCell('D' . $rowNo, (float) ($satir['brut_hakedis'] ?? 0), 12)
                    . $this->numberCell('E' . $rowNo, (float) ($satir['sgk_calisan'] ?? 0), 12)
                    . $this->numberCell('F' . $rowNo, (float) ($satir['issizlik_calisan'] ?? 0), 12)
                    . $this->numberCell('G' . $rowNo, (float) ($satir['gelir_vergisi'] ?? 0), 12)
                    . $this->numberCell('H' . $rowNo, (float) ($satir['damga_vergisi'] ?? 0), 12)
                    . $this->numberCell('I' . $rowNo, (float) ($satir['net_odeme'] ?? 0), 12)
                    . $this->numberCell('J' . $rowNo, (float) ($satir['sgk_isveren'] ?? 0), 12)
                    . $this->numberCell('K' . $rowNo, (float) ($satir['issizlik_isveren'] ?? 0), 12)
                    . $this->numberCell('L' . $rowNo, (float) ($satir['sgk_toplam'] ?? 0), 12)
                    . $this->numberCell('M' . $rowNo, (float) ($satir['isveren_maliyeti'] ?? 0), 12)
                    . $this->inlineCell('N' . $rowNo, (string) ($satir['sgk_tesvik_adi'] ?? ''), 11);
                $rows[] = '<row r="' . $rowNo . '" ht="22" customHeight="1">' . $cells . '</row>';
                $rowNo++;
            }
        }
        $lastRow = max(2, $rowNo - 1);
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="2" topLeftCell="A3" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols><col min="1" max="1" width="28" customWidth="1"/><col min="2" max="13" width="16" customWidth="1"/><col min="14" max="14" width="34" customWidth="1"/></cols>'
            . '<sheetData>' . implode('', $rows) . '</sheetData><mergeCells count="1"><mergeCell ref="A1:N1"/></mergeCells><autoFilter ref="A2:N' . $lastRow . '"/>'
            . '<pageMargins left="0.3" right="0.3" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>'
            . '<pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0" paperSize="9"/>'
            . '</worksheet>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="4"><font><sz val="10"/><name val="Aptos"/></font><font><b/><sz val="14"/><color rgb="FFFFFFFF"/><name val="Aptos Display"/></font><font><b/><sz val="10"/><name val="Aptos"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Aptos"/></font></fonts>'
            . '<fills count="6"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF176B87"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFE8F3F8"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFFD966"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFDAE9F1"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="2"><border/><border><left style="thin"><color rgb="FF9FB3C0"/></left><right style="thin"><color rgb="FF9FB3C0"/></right><top style="thin"><color rgb="FF9FB3C0"/></top><bottom style="thin"><color rgb="FF9FB3C0"/></bottom></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="13">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" textRotation="90"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" textRotation="90"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" textRotation="90"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="2" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><calcPr calcId="191029" fullCalcOnLoad="1" forceFullCalc="1"/><sheets><sheet name="Puantaj" sheetId="1" r:id="rId1"/><sheet name="Giriş Çıkış" sheetId="2" r:id="rId2"/><sheet name="Maaş ve SGK" sheetId="3" r:id="rId3"/></sheets></workbook>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/><Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function appProps(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Oyun Evleri</Application></Properties>';
    }

    private function coreProps(): string
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator>Oyun Evleri</dc:creator><dc:title>Personel Puantaj Raporu</dc:title><dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created></cp:coreProperties>';
    }

    private function inlineCell(string $ref, string $value, int $style): string
    {
        return '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . $this->xml($value) . '</t></is></c>';
    }

    private function numberCell(string $ref, float|int $value, int $style): string
    {
        return '<c r="' . $ref . '" s="' . $style . '"><v>' . $value . '</v></c>';
    }

    private function formulaCell(string $ref, string $formula, int $style): string
    {
        return '<c r="' . $ref . '" s="' . $style . '"><f>' . $formula . '</f><v>0</v></c>';
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

    private function gunKisa(string $tarih): string
    {
        return ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'][(int) date('N', strtotime($tarih)) - 1];
    }

    private function ayEtiketi(string $ay): string
    {
        $aylar = [1 => 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
        return $aylar[(int) substr($ay, 5, 2)] . ' ' . substr($ay, 0, 4);
    }

    private function temiz(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
