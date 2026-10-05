import fs from 'node:fs/promises';
import { SpreadsheetFile, Workbook } from '@oai/artifact-tool';

const projectRoot = process.cwd();
const outputDir = `${projectRoot}/outputs/ogrenci-toplu-import`;
const publicDir = `${projectRoot}/public/assets/templates`;
const fileName = 'ogrenci-toplu-import-sablonu.xlsx';
const font = 'Arial';

const workbook = Workbook.create();
const students = workbook.worksheets.add('Öğrenciler');
const instructions = workbook.worksheets.add('Açıklamalar');

const headers = [
  'Öğrenci Adı *',
  'Öğrenci Soyadı *',
  'TC Kimlik No',
  'Doğum Tarihi',
  'Cinsiyet',
  'Kayıt Tarihi',
  'Veli Adı *',
  'Veli Soyadı *',
  'Veli Telefonu *',
  'Veli Yedek Telefonu',
  'Veli E-posta',
  'Yakınlık',
  'İl',
  'İlçe',
  'Adres',
  'Acil Durum Kişisi',
  'Acil Durum Telefonu',
  'Sağlık Bilgisi',
  'Alerji Bilgisi',
  'Öğrenci Notu',
];

students.showGridLines = false;
students.tabColor = '#2563EB';
students.getRange('A1:T1').values = [headers];
students.getRange('A1:T501').format.font = { name: font, size: 10, color: '#172033' };
students.getRange('A1:T1').format = {
  fill: '#1E3A8A',
  font: { name: font, size: 10, bold: true, color: '#FFFFFF' },
  horizontalAlignment: 'center',
  verticalAlignment: 'center',
  wrapText: true,
  borders: { preset: 'inside', style: 'thin', color: '#FFFFFF' },
};
students.getRange('A1:T1').format.rowHeight = 42;
students.getRange('A2:T501').format.verticalAlignment = 'center';
students.getRange('A2:T501').format.rowHeight = 21;
students.getRange('A2:B501').format.fill = '#FFF7D6';
students.getRange('G2:I501').format.fill = '#FFF7D6';
students.getRange('C2:C501').format.numberFormat = '@';
students.getRange('I2:J501').format.numberFormat = '@';
students.getRange('Q2:Q501').format.numberFormat = '@';
students.getRange('D2:D501').format.numberFormat = 'dd.mm.yyyy';
students.getRange('F2:F501').format.numberFormat = 'dd.mm.yyyy';
students.getRange('A2:T501').format.borders = {
  bottom: { style: 'thin', color: '#E2E8F0' },
};
students.freezePanes.freezeRows(1);
students.dataValidations.add({
  range: 'E2:E501',
  rule: { type: 'list', values: ['Kız', 'Erkek', 'Belirtilmedi'] },
});

const widths = [18, 20, 16, 15, 14, 15, 18, 20, 18, 20, 25, 14, 15, 18, 34, 22, 21, 28, 28, 30];
widths.forEach((width, index) => {
  students.getRangeByIndexes(0, index, 501, 1).format.columnWidth = width;
});

instructions.showGridLines = false;
instructions.tabColor = '#60A5FA';
instructions.getRange('A1').values = [['Toplu öğrenci aktarım şablonu']];
instructions.getRange('A1:B1').format = {
  font: { name: font, size: 16, bold: true, color: '#172033' },
  verticalAlignment: 'center',
};
instructions.getRange('A1:F1').format.rowHeight = 30;
instructions.getRange('A3:B8').values = [
  ['Adım', 'Açıklama'],
  ['1', 'Öğrenciler sayfasında her öğrenci için bir satır doldurun.'],
  ['2', 'Başlık satırını ve sütun adlarını değiştirmeyin.'],
  ['3', 'Yıldız (*) bulunan Öğrenci Adı, Öğrenci Soyadı, Veli Adı, Veli Soyadı ve Veli Telefonu alanları zorunludur.'],
  ['4', 'Tarihleri GG.AA.YYYY biçiminde girin. Örnek: 15.09.2021'],
  ['5', 'Dosyayı .xlsx biçiminde kaydedip Öğrenciler sayfasındaki Toplu Öğrenci Aktar düğmesiyle yükleyin.'],
];
instructions.getRange('A3:B3').format = {
  fill: '#1E3A8A',
  font: { name: font, size: 10, bold: true, color: '#FFFFFF' },
  horizontalAlignment: 'center',
  verticalAlignment: 'center',
};
instructions.getRange('A4:A8').format = {
  fill: '#DBEAFE',
  font: { name: font, size: 10, bold: true, color: '#1E3A8A' },
  horizontalAlignment: 'center',
  verticalAlignment: 'center',
};
instructions.getRange('B4:B8').format = {
  font: { name: font, size: 10, color: '#172033' },
  verticalAlignment: 'center',
  wrapText: true,
};
instructions.getRange('A3:B8').format.borders = { preset: 'all', style: 'thin', color: '#CBD5E1' };
instructions.getRange('A10:B16').values = [
  ['Alan', 'Kabul edilen değer / örnek'],
  ['Cinsiyet', 'Kız, Erkek veya Belirtilmedi'],
  ['Veli Telefonu', '0532 123 45 67 veya 5321234567'],
  ['TC Kimlik No', '11 rakam; hücre metin biçimindedir'],
  ['Doğum / Kayıt Tarihi', '15.09.2021 veya 2021-09-15'],
  ['Aynı veli telefonu', 'Kardeş öğrenciler için kullanılabilir'],
  ['Boş satırlar', 'Aktarım sırasında yok sayılır'],
];
instructions.getRange('A10:B10').format = {
  fill: '#334155',
  font: { name: font, size: 10, bold: true, color: '#FFFFFF' },
  horizontalAlignment: 'center',
  verticalAlignment: 'center',
};
instructions.getRange('A11:A16').format = {
  fill: '#F1F5F9',
  font: { name: font, size: 10, bold: true, color: '#334155' },
};
instructions.getRange('A11:B16').format.font = { name: font, size: 10, color: '#172033' };
instructions.getRange('A11:A16').format.font = { name: font, size: 10, bold: true, color: '#334155' };
instructions.getRange('A10:B16').format.borders = { preset: 'all', style: 'thin', color: '#CBD5E1' };
instructions.getRange('B4:B16').format.wrapText = true;
instructions.getRange('A1:B16').format.verticalAlignment = 'center';
instructions.getRange('A:A').format.columnWidth = 24;
instructions.getRange('B:B').format.columnWidth = 78;
instructions.getRange('A3:B16').format.autofitRows();

workbook.recalculate();

const tableCheck = await workbook.inspect({
  kind: 'table',
  range: 'Öğrenciler!A1:T4',
  include: 'values,formulas',
  tableMaxRows: 4,
  tableMaxCols: 20,
});
console.log(tableCheck.ndjson);

const errors = await workbook.inspect({
  kind: 'match',
  searchTerm: '#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!|#SPILL!|#CALC!',
  options: { useRegex: true, maxResults: 100 },
  summary: 'final formula error scan',
});
console.log(errors.ndjson);

const studentsPreview = await workbook.render({ sheetName: 'Öğrenciler', range: 'A1:T10', scale: 1, format: 'png' });
const instructionsPreview = await workbook.render({ sheetName: 'Açıklamalar', range: 'A1:B16', scale: 1.5, format: 'png' });
await fs.writeFile('/tmp/ogrenci-import-students.png', new Uint8Array(await studentsPreview.arrayBuffer()));
await fs.writeFile('/tmp/ogrenci-import-instructions.png', new Uint8Array(await instructionsPreview.arrayBuffer()));

await fs.mkdir(outputDir, { recursive: true });
await fs.mkdir(publicDir, { recursive: true });
const output = await SpreadsheetFile.exportXlsx(workbook);
await output.save(`${outputDir}/${fileName}`);
await fs.copyFile(`${outputDir}/${fileName}`, `${publicDir}/${fileName}`);
await fs.rm(`${outputDir}/${fileName}.inspect.ndjson`, { force: true });
