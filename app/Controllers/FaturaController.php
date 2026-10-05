<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Exceptions\NesApiException;
use App\Models\Fatura;
use App\Models\FaturaArsivi;
use App\Models\FaturaProfili;
use App\Models\KurumEntegrasyonu;
use App\Services\NesInvoiceService;
use App\Services\FaturaEntegrasyonServisi;
use App\Services\LogServisi;
use App\Services\MfaServisi;
use App\Services\NesEndpointGuvenligi;
use App\Services\YetkiServisi;

final class FaturaController extends Controller
{
    public function sayfa(): void
    {
        if(!$this->sayfaYetkisi('odeme_listele'))return;
        $filter=['baslangic'=>trim((string)($_GET['baslangic']??'')),'bitis'=>trim((string)($_GET['bitis']??'')),'arama'=>trim((string)($_GET['arama']??'')),'belge_turu'=>trim((string)($_GET['belge_turu']??'')),'yerel_durum'=>trim((string)($_GET['yerel_durum']??''))];
        $page=max(1,(int)($_GET['sayfa']??1));
        $this->view('panel/faturalar',['baslik'=>'Faturalar','aktif'=>'faturalar','kullanici'=>Auth::user(),'csrf'=>Csrf::token(),'sonuc'=>Fatura::liste($filter,$page,25),'filtre'=>$filter,'entegrasyon'=>KurumEntegrasyonu::nesAyarlariGoster()],'panel');
    }

    public function detay(): void
    {
        if(!$this->sayfaYetkisi('odeme_listele'))return;
        $fatura=Fatura::idIleBul((int)($_GET['id']??0));if(!$fatura){http_response_code(404);require BASE_PATH.'/resources/views/errors/404.php';return;}
        $this->view('panel/fatura-detay',['baslik'=>'Fatura Detayi','aktif'=>'faturalar','kullanici'=>Auth::user(),'csrf'=>Csrf::token(),'fatura'=>$fatura,'arsiv'=>FaturaArsivi::durum((int)$fatura['id'])],'panel');
    }

    public function ayarlar(): void
    {
        if(!$this->sayfaYetkisi('fatura_entegrasyon_yonet'))return;
        $this->view('panel/mysoft-ayarlari',['baslik'=>'NES Fatura Entegrasyonu','aktif'=>'mysoft-ayarlari','kullanici'=>Auth::user(),'csrf'=>Csrf::token(),'nes'=>KurumEntegrasyonu::nesAyarlariGoster()],'panel');
    }

    public function taslakOnizleme(): void
    {
        if(!$this->sayfaYetkisi('odeme_listele'))return;
        $fatura=Fatura::idIleBul((int)($_GET['id']??0));
        if(!$fatura){http_response_code(404);require BASE_PATH.'/resources/views/errors/404.php';return;}
        if(($fatura['yerel_durum']??'')!=='taslak'){
            Response::redirect('/panel/faturalar/dosya?id='.(int)$fatura['id'].'&format=pdf');
            return;
        }
        $this->view('panel/fatura-taslak-onizleme',[
            'baslik'=>'Geçersiz Fatura Taslak Önizlemesi',
            'fatura'=>$fatura,
        ],'ana');
    }

    public function odemeHazirlik(): void
    {
        $payment=Fatura::odemeBaglami((int)(($GLOBALS['talya_ajax_data']['odeme_id']??0)));
        if(!$payment){Response::json(['basari'=>false,'mesaj'=>'Tahsilat bulunamadi.','hatalar'=>[]],404);return;}
        $profile=FaturaProfili::veliIcin((int)($payment['fatura_veli_id']??0));
        Response::json(['basari'=>true,'mesaj'=>'Fatura bilgileri hazir.','veri'=>['odeme'=>$payment,'profil'=>$profile,'mevcut_fatura'=>Fatura::odemeIcin((int)$payment['id'])]]);
    }

    public function olustur(): void
    {
        $d=$GLOBALS['talya_ajax_data']??[];
        try{$invoice=(new FaturaEntegrasyonServisi())->olustur((int)($d['odeme_id']??0),$d,(string)($d['kdv_orani']??''));Response::json(['basari'=>true,'mesaj'=>'Fatura taslağı NES sisteminde oluşturuldu.','veri'=>$invoice],201);}
        catch(\Throwable $e){$this->hata($e);}
    }

    public function onayla(): void
    {
        try{$invoice=(new FaturaEntegrasyonServisi())->onayla((int)(($GLOBALS['talya_ajax_data']['id']??0)));Response::json(['basari'=>true,'mesaj'=>'Fatura onaylandı ve NES üzerinden resmileştirilmek üzere gönderildi.','veri'=>$invoice]);}catch(\Throwable $e){$this->hata($e);}
    }

    public function topluOnayla(): void
    {
        $d=$GLOBALS['talya_ajax_data']??[];
        try{
            if(($d['onay']??'')!=='TOPLU_ONAYLA')throw new \InvalidArgumentException('Toplu onay işlemi için son kullanıcı onayı alınmalıdır.');
            $sonuc=(new FaturaEntegrasyonServisi())->topluOnayla($this->topluIdler($d['ids']??[]));
            $this->topluJson('onaylandı',$sonuc);
        }catch(\Throwable $e){$this->hata($e);}
    }

    public function arsivle(): void
    {
        try{$status=(new FaturaEntegrasyonServisi())->arsivle((int)(($GLOBALS['talya_ajax_data']['id']??0)));$complete=(bool)($status['tamamlandi']??false);Response::json(['basari'=>$complete,'mesaj'=>$complete?'Faturanın PDF ve XML dosyaları yerel sunucuda arşivlendi.':'Yerel arşiv henüz tamamlanamadı. NES belgeyi hazırladıktan sonra tekrar deneyin.','veri'=>$status],$complete?200:422);}catch(\Throwable $e){$this->hata($e);}
    }

    public function durumGuncelle(): void
    {
        try{$invoice=(new FaturaEntegrasyonServisi())->durumGuncelle((int)(($GLOBALS['talya_ajax_data']['id']??0)));Response::json(['basari'=>true,'mesaj'=>'Fatura durumu guncellendi.','veri'=>$invoice]);}catch(\Throwable $e){$this->hata($e);}
    }

    public function iptal(): void
    {
        $d=$GLOBALS['talya_ajax_data']??[];
        try{$invoice=(new FaturaEntegrasyonServisi())->iptal((int)($d['id']??0),trim((string)($d['cancel_date']??'')),trim((string)($d['cancel_type']??'')),trim((string)($d['cancel_note']??'')));Response::json(['basari'=>true,'mesaj'=>'e-Arşiv iptal işlemi ilgili entegratöre iletildi.','veri'=>$invoice]);}catch(\Throwable $e){$this->hata($e);}
    }

    public function topluIptal(): void
    {
        $d=$GLOBALS['talya_ajax_data']??[];
        try{
            if(($d['onay']??'')!=='TOPLU_IPTAL')throw new \InvalidArgumentException('Toplu iptal işlemi için son kullanıcı onayı alınmalıdır.');
            $sonuc=(new FaturaEntegrasyonServisi())->topluIptal(
                $this->topluIdler($d['ids']??[]),
                trim((string)($d['cancel_date']??'')),
                trim((string)($d['cancel_type']??'')),
                trim((string)($d['cancel_note']??''))
            );
            $this->topluJson('iptal edildi',$sonuc);
        }catch(\Throwable $e){$this->hata($e);}
    }

    public function nesAyarKaydet(): void
    {
        $d=$GLOBALS['talya_ajax_data']??[];foreach(['base_url','ortam','tenant_identifier_number','firma_unvani','adres','il','ilce'] as $field)if(trim((string)($d[$field]??''))===''){Response::json(['basari'=>false,'mesaj'=>'NES için zorunlu alanlar eksik.','hatalar'=>[$field=>'Zorunlu alan.']],422);return;}
        try{$d['base_url']=NesEndpointGuvenligi::dogrula((string)$d['base_url'],(string)$d['ortam']);}catch(\InvalidArgumentException $e){Response::json(['basari'=>false,'mesaj'=>$e->getMessage(),'hatalar'=>['base_url'=>'İzin verilmeyen servis adresi.']],422);return;}
        $tenant=preg_replace('/\D+/','',(string)$d['tenant_identifier_number']);if(!in_array(strlen($tenant),[10,11],true)){Response::json(['basari'=>false,'mesaj'=>'Kurum VKN/TCKN 10 veya 11 haneli olmalıdır.','hatalar'=>['tenant_identifier_number'=>'Geçersiz kimlik numarası.']],422);return;}
        $current=KurumEntegrasyonu::nesAyarlariGoster();if((int)($d['aktif']??0)===1&&trim((string)($d['api_key']??''))===''&&!$current['api_key_var']){Response::json(['basari'=>false,'mesaj'=>'NES entegrasyonunu etkinleştirmek için API anahtarı gereklidir.','hatalar'=>['api_key'=>'Zorunlu alan.']],422);return;}
        try{$id=KurumEntegrasyonu::nesKaydet($d);(new LogServisi())->yaz('nes_ayarlari_guncellendi','NES entegrasyon ayarları güncellendi.',['entegrasyon_id'=>$id,'ortam'=>(string)$d['ortam'],'aktif'=>(int)($d['aktif']??0),'api_anahtari_degisti'=>trim((string)($d['api_key']??''))!=='']);Response::json(['basari'=>true,'mesaj'=>'NES ayarları kaydedildi.','veri'=>['id'=>$id]]);}catch(\Throwable $e){$this->hata($e);}
    }

    public function nesBaglantiTest(): void
    {
        try{$result=(new NesInvoiceService())->baglantiTesti();(new LogServisi())->yaz('nes_baglanti_testi','NES bağlantı testi başarılı.');Response::json(['basari'=>true,'mesaj'=>'NES bağlantısı başarılı.','veri'=>$result]);}catch(\Throwable $e){$this->hata($e);}
    }

    public function dosya(): void
    {
        if(!$this->sayfaYetkisi('odeme_listele'))return;
        try{$id=(int)($_GET['id']??0);$format=(string)($_GET['format']??'pdf');$invoice=Fatura::idIleBul($id);$file=(new FaturaEntegrasyonServisi())->belge($id,$format);$content=(string)$file['content'];$name=(string)$file['name'];if($invoice&&($invoice['yerel_durum']??'')==='taslak'&&strtolower($format)==='pdf'){$name='GECERSIZ-TASLAK-'.basename($name);header('X-Talya-Document-Status: draft-invalid');}header('X-Talya-Document-Source: '.((string)($file['source']??'nes')));(new LogServisi())->yaz('fatura_dosyasi_goruntulendi','Fatura dosyası görüntülendi.',['fatura_id'=>$id,'format'=>strtolower($format),'indir'=>(string)($_GET['indir']??'')==='1']);while(ob_get_level()>0)ob_end_clean();header('Content-Type: '.$file['mime']);header('X-Content-Type-Options: nosniff');header('Cache-Control: private, no-store');header('Content-Disposition: '.(((string)($_GET['indir']??''))==='1'?'attachment':'inline').'; filename="'.basename($name).'"');header('Content-Length: '.strlen($content));echo $content;}catch(\Throwable $e){error_log('Fatura dosyası hatası: '.get_class($e).' #'.(int)($_GET['id']??0));http_response_code(422);echo 'Fatura dosyası şu anda görüntülenemiyor.';}
    }

    public function topluIndir(): void
    {
        if(!$this->sayfaYetkisi('odeme_listele'))return;
        $geciciDosya=null;
        try{
            if(!Csrf::dogrula((string)($_POST['csrf']??'')))throw new \RuntimeException('Güvenlik doğrulaması başarısız.',419);
            if(($_POST['onay']??'')!=='TOPLU_INDIR')throw new \InvalidArgumentException('Toplu indirme için son kullanıcı onayı alınmalıdır.');
            $kullanici=Auth::user();
            if(!$kullanici||!(new MfaServisi())->yuksekRiskDogrulandiMi($kullanici))throw new \RuntimeException('Bu işlem için iki aşamalı doğrulamayı yenilemeniz gerekir.',403);
            $idler=$this->topluIdler($_POST['ids']??[]);
            if(!class_exists(\ZipArchive::class))throw new \RuntimeException('Sunucuda ZIP desteği etkin değil.');
            $geciciDosya=tempnam(sys_get_temp_dir(),'talya-faturalar-');
            if($geciciDosya===false)throw new \RuntimeException('Geçici ZIP dosyası oluşturulamadı.');
            $zip=new \ZipArchive();
            if($zip->open($geciciDosya,\ZipArchive::CREATE|\ZipArchive::OVERWRITE)!==true)throw new \RuntimeException('ZIP arşivi açılamadı.');
            $eklenen=0;$hatalar=[];$servis=new FaturaEntegrasyonServisi();
            try{
                foreach($idler as $id){
                    $fatura=Fatura::idIleBul($id);
                    $etiket=$fatura?(string)($fatura['fatura_no']?:$fatura['ettn']):'#'.$id;
                    if(!$fatura||($fatura['provider']??'')!=='nes'||!in_array((string)($fatura['yerel_durum']??''),['gonderildi','isleniyor','basarili','iptal'],true)){$hatalar[]=$etiket.': Kesilmiş ve indirilebilir bir NES faturası değil.';continue;}
                    $temel=preg_replace('/[^A-Za-z0-9._-]+/','_',trim($etiket))?:'fatura-'.$id;
                    foreach(['pdf','xml'] as $format){
                        try{$dosya=$servis->belge($id,$format);$zip->addFromString(str_pad((string)$id,6,'0',STR_PAD_LEFT).'_'.$temel.'.'.$format,(string)$dosya['content']);$eklenen++;}
                        catch(\Throwable $e){$hatalar[]=$etiket.' ('.strtoupper($format).'): '.$e->getMessage();}
                    }
                }
                if($hatalar)$zip->addFromString('HATALAR.txt',implode("\n",$hatalar)."\n");
            }finally{$zip->close();}
            if($eklenen===0)throw new \RuntimeException('Seçilen kayıtlardan indirilebilir fatura dosyası hazırlanamadı.');
            $boyut=filesize($geciciDosya);
            (new LogServisi())->yaz('faturalar_toplu_indirildi','Fatura dosyaları toplu olarak indirildi.',['fatura_idleri'=>$idler,'dosya_sayisi'=>$eklenen]);
            while(ob_get_level()>0)ob_end_clean();
            header('Content-Type: application/zip');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: private, no-store');
            header('Content-Disposition: attachment; filename="faturalar-'.date('Ymd-His').'.zip"');
            if($boyut!==false)header('Content-Length: '.$boyut);
            readfile($geciciDosya);
        }catch(\Throwable $e){
            if(!headers_sent())http_response_code(in_array((int)$e->getCode(),[403,419],true)?(int)$e->getCode():422);
            echo e($e->getMessage());
        }finally{
            if(is_string($geciciDosya)&&is_file($geciciDosya))@unlink($geciciDosya);
        }
    }

    private function hata(\Throwable $e): void
    {
        $api=$e instanceof NesApiException;$http=$api?$e->httpStatus:0;$status=$http>=500?502:422;$code=$e instanceof NesApiException?$e->nesCode:null;
        $referans=bin2hex(random_bytes(4));
        error_log('Fatura/NES hata ['.$referans.'] '.get_class($e).': '.preg_replace('/[\r\n]+/',' ',mb_substr($e->getMessage(),0,500)));
        $kullaniciMesaji=$api?'NES işlemi tamamlanamadı.':($e instanceof \InvalidArgumentException?$e->getMessage():'Fatura işlemi tamamlanamadı.');
        Response::json(['basari'=>false,'mesaj'=>$kullaniciMesaji.' Destek kodu: '.$referans,'hata_kodu'=>$code,'hatalar'=>[]],$status);
    }

    private function topluIdler(mixed $ham): array
    {
        if(is_string($ham)){$cozulmus=json_decode($ham,true);$ham=is_array($cozulmus)?$cozulmus:[];}
        if(!is_array($ham))$ham=[];
        $idler=array_values(array_unique(array_filter(array_map('intval',$ham),static fn(int $id): bool=>$id>0)));
        if(!$idler)throw new \InvalidArgumentException('En az bir fatura seçmelisiniz.');
        if(count($idler)>50)throw new \InvalidArgumentException('Tek seferde en fazla 50 fatura işleme alınabilir.');
        return $idler;
    }

    private function topluJson(string $eylem,array $sonuc): void
    {
        $basarili=(int)($sonuc['basarili']??0);$basarisiz=(int)($sonuc['basarisiz']??0);
        $mesaj=$basarili.' fatura '.$eylem.'.';
        if($basarisiz>0){$ilkHata='';foreach(($sonuc['sonuclar']??[]) as $satir)if(empty($satir['basarili'])){$ilkHata=' İlk hata: '.($satir['etiket']??'Fatura').' - '.($satir['mesaj']??'İşlem başarısız.');break;}$mesaj.=' '.$basarisiz.' fatura işlenemedi.'.$ilkHata;}
        Response::json(['basari'=>$basarili>0,'mesaj'=>$mesaj,'veri'=>$sonuc],$basarili>0?200:422);
    }

    private function sayfaYetkisi(string $yetki): bool
    {
        if(!Auth::check()){Response::redirect('/giris');return false;}
        if(!(new YetkiServisi())->izinliMi($yetki)){http_response_code(403);require BASE_PATH.'/resources/views/errors/403.php';return false;}
        return true;
    }

}
