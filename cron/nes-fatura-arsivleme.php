<?php

declare(strict_types=1);

if(PHP_SAPI!=='cli'){http_response_code(403);exit('Forbidden');}
require dirname(__DIR__).'/bootstrap.php';

use App\Core\Session;
use App\Models\FaturaArsivi;
use App\Models\KurumEntegrasyonu;
use App\Services\NesInvoiceService;

$lockPath=BASE_PATH.'/storage/nes-fatura-arsivleme.lock';
$lock=fopen($lockPath,'c');
if(!$lock||!flock($lock,LOCK_EX|LOCK_NB)){echo "NES fatura arşivleme zaten çalışıyor.\n";exit(0);}

$results=[];
foreach(KurumEntegrasyonu::aktifNesListesi() as $kurumId){
    Session::set('kurum_id',(int)$kurumId);
    $service=new NesInvoiceService(KurumEntegrasyonu::etkinNes((int)$kurumId));
    foreach(FaturaArsivi::eksikFaturaIdleri(100) as $invoiceId){
        try {
            $status=$service->arsivle((int)$invoiceId);
            $results[]=['kurum_id'=>(int)$kurumId,'fatura_id'=>(int)$invoiceId,'basarili'=>(bool)($status['tamamlandi']??false),'hatalar'=>$status['hatalar']??[]];
        } catch(Throwable $e) {
            $results[]=['kurum_id'=>(int)$kurumId,'fatura_id'=>(int)$invoiceId,'basarili'=>false,'hatalar'=>[$e->getMessage()]];
        }
    }
}

echo json_encode($results,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
