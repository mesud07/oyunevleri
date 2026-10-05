<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;use App\Core\Controller;use App\Core\Csrf;use App\Core\Response;use App\Models\Cari;use App\Services\FaturaEntegrasyonServisi;
final class CariController extends Controller
{
    public function sayfa(): void {if(!Auth::check()){Response::redirect('/giris');return;}$q=trim((string)($_GET['q']??''));$this->view('panel/cariler',['baslik'=>'Cariler','aktif'=>'cariler','kullanici'=>Auth::user(),'csrf'=>Csrf::token(),'cariler'=>Cari::liste($q),'arama'=>$q],'panel');}
    public function veliAra(): void {$q=trim((string)(($GLOBALS['talya_ajax_data']['q']??'')));Response::json(['basari'=>true,'mesaj'=>'Veli kayıtları listelendi.','veri'=>strlen($q)>=2?Cari::veliAra($q):[]]);}
    public function kaydet(): void {try{$id=Cari::kaydet($GLOBALS['talya_ajax_data']??[]);Response::json(['basari'=>true,'mesaj'=>'Cari kaydedildi. Fatura bilgilerini girebilirsiniz.','veri'=>Cari::idIleBul($id)],201);}catch(\Throwable $e){Response::json(['basari'=>false,'mesaj'=>$e->getMessage(),'hatalar'=>[]],422);}}
    public function faturaOlustur(): void {try{$d=$GLOBALS['talya_ajax_data']??[];$f=(new FaturaEntegrasyonServisi())->caridenOlustur((int)($d['cari_id']??0),$d);Response::json(['basari'=>true,'mesaj'=>'Cari için fatura taslağı NES üzerinde oluşturuldu.','veri'=>$f],201);}catch(\Throwable $e){Response::json(['basari'=>false,'mesaj'=>$e->getMessage(),'hatalar'=>[]],422);}}
}
