<?php

declare(strict_types=1);

if(PHP_SAPI!=='cli'){http_response_code(403);exit('Forbidden');}
require dirname(__DIR__).'/bootstrap.php';

use App\Core\Session;
use App\Models\Fatura;
use App\Models\KurumEntegrasyonu;
use App\Services\MysoftInvoiceService;

$lockPath=BASE_PATH.'/storage/mysoft-fatura-senkronizasyonu.lock';
$lock=fopen($lockPath,'c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB)){echo "Mysoft senkronizasyonu zaten calisiyor.\n";exit(0);}
$results=[];
foreach(KurumEntegrasyonu::aktifMysoftListesi() as $kurumId){
    Session::set('kurum_id',(int)$kurumId);
    try{
        $service=new MysoftInvoiceService(KurumEntegrasyonu::etkinMysoft((int)$kurumId));
        $sync=$service->senkronize(date('Y-m-d',strtotime('-7 days')),date('Y-m-d'),100);
        $status=0;foreach(Fatura::kesinlesmemis(25) as $invoiceId){$service->durumGuncelle((int)$invoiceId);$status++;}
        $results[]=['kurum_id'=>(int)$kurumId,'basarili'=>true,'senkronizasyon'=>$sync,'durum_guncellenen'=>$status];
    }catch(Throwable $e){$results[]=['kurum_id'=>(int)$kurumId,'basarili'=>false,'mesaj'=>$e->getMessage()];}
}
echo json_encode($results,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
