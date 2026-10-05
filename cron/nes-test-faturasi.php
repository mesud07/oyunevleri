<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Config;
use App\Core\Session;
use App\Exceptions\NesApiException;
use App\Models\KurumEntegrasyonu;
use App\Services\NesClient;
use App\Services\NesUblBuilder;

if (Config::get('APP_ENV', 'production') === 'production' || Config::get('ALLOW_NES_TEST_TOOLS', 'false') !== 'true') {
    throw new RuntimeException('NES test araci canli ortamda veya acik izin olmadan calistirilamaz.');
}
$kurumId = max(0, (int) Config::get('CLI_KURUM_ID', '0'));
if ($kurumId < 1) {
    throw new RuntimeException('CLI_KURUM_ID zorunludur.');
}
Session::set('kurum_id', $kurumId);

$integration = KurumEntegrasyonu::etkinNes() ?? throw new RuntimeException('Aktif NES entegrasyonu bulunamadi.');
if (($integration['ortam'] ?? '') !== 'test' || !str_contains((string) $integration['base_url'], 'apitest.nes.com.tr')) {
    throw new RuntimeException('Bu komut yalnizca NES test ortaminda calisir.');
}

$uuidData = random_bytes(16);
$uuidData[6] = chr((ord($uuidData[6]) & 0x0f) | 0x40);
$uuidData[8] = chr((ord($uuidData[8]) & 0x3f) | 0x80);
$uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($uuidData), 4));
$number = 'PZ1';

$xml = (new NesUblBuilder())->build(
    ['profile'=>'EARSIVFATURA','number'=>$number,'uuid'=>$uuid,'date'=>date('Y-m-d'),'time'=>date('H:i:s'),'note'=>'Talya Kids NES test faturasıdır. Mali değeri yoktur.'],
    ['id'=>(string)$integration['tenant_identifier_number'],'name'=>(string)$integration['firma_unvani'],'tax_office'=>(string)$integration['vergi_dairesi'],'address'=>(string)$integration['adres'],'city'=>(string)$integration['il'],'district'=>(string)$integration['ilce'],'postal_code'=>(string)($integration['posta_kodu']??''),'country'=>(string)($integration['ulke']??'TÜRKİYE'),'email'=>(string)($integration['eposta']??''),'phone'=>(string)($integration['telefon']??'')],
    ['id'=>'10000000146','name'=>'Test Müşteri','tax_office'=>'','address'=>'Test Adresi','city'=>'İstanbul','district'=>'Üsküdar'],
    ['name'=>'Test Hizmeti','net'=>'0.91','vat'=>'0.09','gross'=>'1.00','vat_rate'=>'10.00']
);

try {
    $result = (new NesClient($integration))->upload('/earchive/v1/uploads/document', $xml, [
        'IsDirectSend'=>'true','PreviewType'=>'None','SourceApp'=>'TalyaKids',
        'SourceAppRecordId'=>'test-'.$uuid,'AutoSaveCompany'=>'false',
    ]);
} catch (NesApiException $e) {
    fwrite(STDERR, json_encode(['http'=>$e->httpStatus,'code'=>$e->nesCode,'message'=>$e->getMessage(),'response'=>$e->response], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . PHP_EOL);
    exit(1);
}

echo json_encode(['uuid'=>$result['uuid']??$uuid,'document_number'=>$result['documentNumber']??$number], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . PHP_EOL;
