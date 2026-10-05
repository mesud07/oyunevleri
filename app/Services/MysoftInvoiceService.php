<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\MysoftApiException;
use App\Exceptions\MysoftValidationException;
use App\Models\Fatura;
use App\Models\FaturaProfili;
use App\Models\KurumEntegrasyonu;

final class MysoftInvoiceService
{
    private array $integration;
    private MysoftClient $client;

    public function __construct(?array $integration = null)
    {
        $this->integration=$integration??KurumEntegrasyonu::etkinMysoft()??throw new MysoftValidationException('Bu kurum icin aktif Mysoft entegrasyonu bulunamadi.');
        $this->client=new MysoftClient($this->integration);
    }

    public function baglantiTesti(): array
    {
        $response=$this->client->get('/api/Tenant/getTenantWithIdentifier',['identifierNumber'=>$this->tenant()]);
        $credits=$this->client->post('/api/Tenant/getCreditInfo',['identifierNumber'=>$this->tenant(),'productTypeList'=>[1,2],'isGetAllCreditInfo'=>false]);
        $numbers=$this->client->get('/api/Tenant/getDocumentNumberList',['vknTckn'=>$this->tenant()]);
        $sets=$this->client->get('/api/Tenant/getNumaratorSetList',['vknTckn'=>$this->tenant()]);
        $configuredSet=trim((string)($this->integration['numerator_set_code']??''));
        $availableSets=is_array($sets['data']??null)?$sets['data']:[];
        $setValid=$configuredSet==='';
        foreach($availableSets as $set){if(is_array($set)&&(string)($set['numeratorSetCode']??'')===$configuredSet){$setValid=true;break;}}
        return [
            'firma'=>$response['data'][0]??null,
            'kontorler'=>is_array($credits['data']??null)?$credits['data']:[],
            'numaratorler'=>is_array($numbers['data']??null)?$numbers['data']:[],
            'numarator_setleri'=>$availableSets,
            'yapilandirilan_set_gecerli'=>$setValid,
            'uyari'=>$setValid?null:'Yapilandirilan numarator set kodu Mysoft hesabinda bulunamadi.',
            'mesaj'=>$response['message']??'Mysoft baglantisi basarili.',
        ];
    }

    public function mukellefSorgula(string $vknTckn): array
    {
        try {
            $response=$this->client->get('/api/GeneralCard/getGibAccountModel',['vknTckn'=>$vknTckn]);
            $account=is_array($response['data']??null)?$response['data']:null;
            $active=$account!==null&&!((bool)($account['isPassive']??false));
            return ['efatura'=>$active,'hesap'=>$account,'pk_alias'=>$active?$this->pkAlias($account):null];
        } catch(MysoftApiException $e) {
            if($e->httpStatus===404||in_array(strtoupper((string)$e->mysoftCode),['NOTFOUND','404'],true))return ['efatura'=>false,'hesap'=>null,'pk_alias'=>null];
            throw $e;
        }
    }

    public function odemedenFaturaOlustur(int $odemeId,array $profileData,string $vatRate): array
    {
        $existing=Fatura::odemeIcin($odemeId);
        if($existing)throw new MysoftValidationException('Bu tahsilat icin daha once fatura kaydi olusturulmus.');
        $payment=Fatura::odemeBaglami($odemeId);
        if(!$payment||((int)$payment['iptal'])===1)throw new MysoftValidationException('Aktif tahsilat kaydi bulunamadi.');
        $veliId=(int)($payment['fatura_veli_id']??0);
        if($veliId<1)throw new MysoftValidationException('Tahsilata bagli fatura alicisi/veli bulunamadi.');
        $profile=$this->profiliDogrula($profileData);
        FaturaProfili::kaydet($veliId,$profile);
        $gib=$this->mukellefSorgula($profile['vkn_tckn']);
        $documentType=$gib['efatura']?'EFATURA':'EARSIVFATURA';
        $invoiceProfile=$gib['efatura']?(string)$this->integration['varsayilan_efatura_profili']:'EARSIVFATURA';
        $resolvedVat=$payment['kdv_orani']!==null?(string)$payment['kdv_orani']:$vatRate;
        $vatBp=$this->oranBasisPoint($resolvedVat);
        $grossCents=$this->paraCent((string)$payment['tutar']);
        $netCents=$vatBp>0?(int)round($grossCents*10000/(10000+$vatBp)):$grossCents;
        $vatCents=$grossCents-$netCents;
        $ettn=$this->uuid4();$now=new \DateTimeImmutable('now');
        $reference='TALYA-ODEME-'.$odemeId;
        $accountName=$profile['profil_turu']==='kurumsal'?$profile['unvan']:trim($profile['ad'].' '.$profile['soyad']);
        $localId=Fatura::taslakOlustur([
            'entegrasyon_id'=>(int)$this->integration['id'],'odeme_id'=>$odemeId,'veli_id'=>$veliId,'ogrenci_id'=>(int)$payment['ogrenci_id'],
            'belge_turu'=>$documentType,'profil'=>$invoiceProfile,'ettn'=>$ettn,'referans_anahtari'=>$reference,'fatura_tarihi'=>(string)$payment['tarih'],'fatura_saati'=>$now->format('H:i:s'),
            'ara_toplam'=>$this->decimal($netCents),'kdv_toplami'=>$this->decimal($vatCents),'genel_toplam'=>$this->decimal($grossCents),'alici_adi'=>$accountName,'alici_vkn_tckn'=>$profile['vkn_tckn'],
        ],['aciklama'=>(string)$payment['paket_adi'],'miktar'=>'1.0000','birim_kodu'=>'C62','birim_fiyat'=>$this->decimal($netCents,4),'kdv_orani'=>$this->rateDecimal($vatBp),'kdv_tutari'=>$this->decimal($vatCents),'satir_toplami'=>$this->decimal($grossCents)]);
        $prefix=$documentType==='EFATURA'?($this->integration['efatura_prefix']??null):($this->integration['earsiv_prefix']??null);
        $payload=[
            'eDocumentType'=>$documentType,'profile'=>$invoiceProfile,'invoiceType'=>'SATIS','ettn'=>$ettn,
            'docDate'=>$this->mysoftDate((string)$payment['tarih']),'docTime'=>$now->format('m/d/Y H:i:s'),'currencyCode'=>'TRY','currencyRate'=>1,
            'tenantIdentifierNumber'=>$this->tenant(),'referanceKey'=>$reference,'isManuelCalculation'=>true,'isThrowExceptionOnEDocumentTypeChange'=>true,
            'invoiceAccount'=>array_filter([
                'vknTckn'=>$profile['vkn_tckn'],'accountName'=>$accountName,'taxOfficeName'=>$profile['vergi_dairesi']?:null,'countryName'=>$profile['ulke'],
                'cityName'=>$profile['il'],'citySubdivision'=>$profile['ilce'],'streetName'=>$profile['adres'],'telephone1'=>$profile['telefon']?:null,'email1'=>$profile['eposta']?:null,
                'personInfo'=>$profile['profil_turu']==='bireysel'?['firstName'=>$profile['ad'],'familyName'=>$profile['soyad']]:null,
            ],static fn($v)=>$v!==null&&$v!==''),
            'invoiceDetail'=>[['productName'=>(string)$payment['paket_adi'],'unitCode'=>'C62','qty'=>1,'unitPriceTra'=>$netCents/100,'amtTra'=>$netCents/100,'vatRate'=>$vatBp/100,'amtVatTra'=>$vatCents/100,'taxableAmtTra'=>$netCents/100]],
            'invoiceCalculation'=>['lineExtensionAmount'=>$netCents/100,'taxExclusiveAmount'=>$netCents/100,'taxInclusiveAmount'=>$grossCents/100,'payableRoundingAmount'=>0,'payableAmount'=>$grossCents/100,'allowanceTotalAmount'=>0,'chargeTotalAmount'=>0],
        ];
        if(!empty($this->integration['numerator_set_code']))$payload['numeratorSetCode']=$this->integration['numerator_set_code'];elseif($prefix)$payload['prefix']=$prefix;
        if(!empty($this->integration['gb_alias']))$payload['gbAlias']=$this->integration['gb_alias'];
        if($documentType==='EFATURA'&&!empty($gib['pk_alias']))$payload['pkAlias']=$gib['pk_alias'];
        if($documentType==='EARSIVFATURA')$payload['senderType']='ELEKTRONIK';
        try {
            $response=$this->client->post('/api/InvoiceOutbox/invoiceOutbox',$payload);
            $result=is_array($response['data']??null)?$response['data']:[];
            if(empty($result['invoiceETTN'])||empty($result['docNo']))throw new MysoftApiException('Mysoft fatura cevabinda ETTN veya fatura numarasi bulunamadi.',(string)($response['errorCode']??''),200,$response);
            Fatura::gonderimBasarili($localId,$result,$response);
            return Fatura::idIleBul($localId)??[];
        } catch(MysoftApiException $e) {
            Fatura::hataKaydet($localId,$e->getMessage(),$e->mysoftCode,$e->httpStatus,$e->response);throw $e;
        }
    }

    public function durumGuncelle(int $invoiceId): array
    {
        $invoice=Fatura::idIleBul($invoiceId)??throw new MysoftValidationException('Fatura bulunamadi.');
        $response=$this->client->get('/api/InvoiceOutbox/getInvoiceOutboxStatus',['invoiceETTN'=>$invoice['ettn'],'tenantIdentifierNumber'=>$this->tenant()]);
        $status=is_array($response['data']??null)?$response['data']:[];Fatura::durumKaydet($invoiceId,$status);return Fatura::idIleBul($invoiceId)??[];
    }

    public function eArsivIptal(int $invoiceId,string $date,string $type,string $note): array
    {
        $invoice=Fatura::idIleBul($invoiceId)??throw new MysoftValidationException('Fatura bulunamadi.');
        if($invoice['belge_turu']!=='EARSIVFATURA')throw new MysoftValidationException('Bu iptal islemi yalnizca e-Arsiv faturalar icindir.');
        $cancelDate=\DateTimeImmutable::createFromFormat('!Y-m-d',$date);$dateErrors=\DateTimeImmutable::getLastErrors();if(!$cancelDate||($dateErrors!==false&&($dateErrors['warning_count']>0||$dateErrors['error_count']>0))||$cancelDate->format('Y-m-d')!==$date)throw new MysoftValidationException('Gecerli bir iptal tarihi secilmelidir.');
        if($note==='')throw new MysoftValidationException('Iptal aciklamasi zorunludur.');
        $allowed=['GIB','NOTER','KEP','TAAHHUTLUMEKTUP','PORTAL'];if(!in_array($type,$allowed,true))throw new MysoftValidationException('Gecersiz e-Arsiv iptal tipi.');
        $this->client->get('/api/InvoiceOutbox/cancelEArchiveInvoice',['invoiceETTN'=>$invoice['ettn'],'cancelDate'=>$date,'cancelType'=>$type,'cancelNote'=>$note,'tenantIdentifierNumber'=>$this->tenant()]);
        return $this->durumGuncelle($invoiceId);
    }

    public function belge(int $invoiceId,string $format): array
    {
        $invoice=Fatura::idIleBul($invoiceId)??throw new MysoftValidationException('Fatura bulunamadi.');
        $format=strtolower($format);if(!in_array($format,['pdf','xml'],true))throw new MysoftValidationException('Gecersiz belge formati.');
        $dir=BASE_PATH.'/storage/faturalar/'.(int)$invoice['kurum_id'];if(!is_dir($dir))@mkdir($dir,0700,true);
        $cache=$dir.'/'.$invoice['ettn'].'.'.$format;if(is_file($cache))return ['path'=>$cache,'mime'=>$format==='pdf'?'application/pdf':'application/xml','name'=>($invoice['fatura_no']?:$invoice['ettn']).'.'.$format];
        $endpoint=$format==='pdf'?'/api/InvoiceOutbox/getInvoiceOutboxPdfAsZip':'/api/InvoiceOutbox/getInvoiceOutboxXMLAsZip';
        $response=$this->client->get($endpoint,['invoiceETTN'=>$invoice['ettn'],'tenantIdentifierNumber'=>$this->tenant()]);
        $zip=base64_decode((string)($response['data']??''),true);if($zip===false||!str_starts_with($zip,"PK"))throw new MysoftApiException('Mysoft belge cevabi beklenen Base64 ZIP formatinda degil.',(string)($response['errorCode']??''),200,$response);
        $tmp=tempnam(sys_get_temp_dir(),'talya-mysoft-');file_put_contents($tmp,$zip);$archive=new \ZipArchive();if($archive->open($tmp)!==true){@unlink($tmp);throw new MysoftApiException('Mysoft ZIP belgesi acilamadi.');}
        $content=null;for($i=0;$i<$archive->numFiles;$i++){ $name=$archive->getNameIndex($i);if($name&&strtolower(pathinfo($name,PATHINFO_EXTENSION))===$format){$content=$archive->getFromIndex($i);break;}}
        $archive->close();@unlink($tmp);if(!is_string($content))throw new MysoftApiException('Mysoft ZIP icinde '.$format.' dosyasi bulunamadi.');
        file_put_contents($cache,$content,LOCK_EX);chmod($cache,0600);return ['path'=>$cache,'mime'=>$format==='pdf'?'application/pdf':'application/xml','name'=>($invoice['fatura_no']?:$invoice['ettn']).'.'.$format];
    }

    public function senkronize(string $start,string $end,int $limit=500): array
    {
        $max=max(1,min(2000,$limit));$after=0;$lastAfter=0;$count=0;$skipped=0;$processed=0;$pages=0;$seen=[];
        do {
            $pageLimit=min(100,$max-$processed);
            $response=$this->client->post('/api/InvoiceOutbox/getInvoiceOutboxList',['afterValue'=>$after,'limit'=>$pageLimit,'tenantIdentifierNumber'=>$this->tenant(),'startDate'=>$start,'endDate'=>$end,'isUseDocDate'=>true,'cessionStatus'=>0]);
            $entries=is_array($response['data']??null)?$response['data']:[];$pages++;
            foreach($entries as $entry){$processed++;$ettn=$this->listedEttn($entry);if(!$ettn){$skipped++;continue;}if(isset($seen[$ettn]))continue;$seen[$ettn]=true;$existing=Fatura::ettnIleBul($ettn);if($existing){$this->durumGuncelle((int)$existing['id']);continue;}$modelResponse=$this->client->get('/api/InvoiceOutbox/getInvoiceOutboxModel',['invoiceETTN'=>$ettn,'tenantIdentifierNumber'=>$this->tenant()]);$model=$modelResponse['data']??null;if(!is_array($model)){$skipped++;continue;}$customer=$model['customerInfo']??[];$money=$model['legalMonetaryTotal']??[];$taxTotal=$model['taxTotal']??[];$tax=0.0;foreach($taxTotal as $t)$tax+=(float)($t['taxAmount']??0);
            $customerName=trim((string)($customer['partyName']??''))?:trim((string)($customer['customerName']??'').' '.(string)($customer['customerSurname']??''));
            $syncedId=Fatura::disKaynakKaydet(['entegrasyon_id'=>(int)$this->integration['id'],'belge_turu'=>(string)($model['profileId']==='EARSIVFATURA'?'EARSIVFATURA':'EFATURA'),'profil'=>(string)($model['profileId']??'TEMELFATURA'),'fatura_tipi'=>(string)($model['invoiceTypeCode']??'SATIS'),'ettn'=>$ettn,'fatura_no'=>(string)($model['docNo']??''),'tarih'=>$this->isoDate((string)($model['docDate']??date('Y-m-d'))),'para'=>(string)($model['documentCurrencyCode']??'TRY'),'ara'=>(float)($money['taxExclusiveAmount']??0),'kdv'=>$tax,'toplam'=>(float)($money['payableAmount']??$money['taxInclusiveAmount']??0),'alici'=>$customerName?:'-','vkn'=>(string)($customer['identifierNumber']??'-'),'durum'=>null,'cevap'=>json_encode($modelResponse,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);Fatura::disKalemleriKaydet($syncedId,is_array($model['detailList']??null)?$model['detailList']:[]);$this->durumGuncelle($syncedId);$count++;}
            $next=(int)($response['afterValue']??0);
            $lastAfter=$next;
            if($entries===[]||$next<=$after||$processed>=$max)break;
            $after=$next;
        } while(true);
        return ['eklenen'=>$count,'atlanmis'=>$skipped,'incelenen'=>$processed,'sayfa'=>$pages,'after_value'=>$lastAfter];
    }

    private function tenant(): string{return (string)$this->integration['tenant_identifier_number'];}
    private function pkAlias(array $account): ?string{foreach(($account['gibAccountAliasList']??[]) as $alias)if((int)($alias['gibDocumentType']??0)===1&&(int)($alias['aliasType']??0)===1&&empty($alias['aliasDeleteDate']))return (string)$alias['alias'];return null;}
    private function profiliDogrula(array $d): array{$type=(string)($d['profil_turu']??'bireysel');if(!in_array($type,['bireysel','kurumsal'],true))throw new MysoftValidationException('Gecersiz fatura profili.');$vkn=preg_replace('/\D+/','',(string)($d['vkn_tckn']??''));if($type==='bireysel'&&strlen($vkn)!==11)throw new MysoftValidationException('Bireysel faturada TCKN 11 haneli olmalidir.');if($type==='kurumsal'&&strlen($vkn)!==10)throw new MysoftValidationException('Kurumsal faturada VKN 10 haneli olmalidir.');foreach(['adres','il','ilce'] as $f)if(trim((string)($d[$f]??''))==='')throw new MysoftValidationException('Fatura profili icin adres, il ve ilce zorunludur.');if($type==='kurumsal'&&trim((string)($d['unvan']??''))==='')throw new MysoftValidationException('Kurumsal faturada firma unvani zorunludur.');if($type==='bireysel'&&(trim((string)($d['ad']??''))===''||trim((string)($d['soyad']??''))===''))throw new MysoftValidationException('Bireysel faturada ad ve soyad zorunludur.');return ['profil_turu'=>$type,'ad'=>trim((string)($d['ad']??'')),'soyad'=>trim((string)($d['soyad']??'')),'unvan'=>trim((string)($d['unvan']??'')),'vkn_tckn'=>$vkn,'vergi_dairesi'=>trim((string)($d['vergi_dairesi']??'')),'adres'=>trim((string)$d['adres']),'il'=>trim((string)$d['il']),'ilce'=>trim((string)$d['ilce']),'ulke'=>trim((string)($d['ulke']??'TÜRKİYE'))?:'TÜRKİYE','eposta'=>trim((string)($d['eposta']??'')),'telefon'=>trim((string)($d['telefon']??''))];}
    private function oranBasisPoint(string $rate): int{$normalized=str_replace(',','.',trim($rate));if(!is_numeric($normalized))throw new MysoftValidationException('KDV orani gecersiz.');$bp=(int)round((float)$normalized*100);if($bp<0||$bp>10000)throw new MysoftValidationException('KDV orani 0 ile 100 arasinda olmalidir.');return $bp;}
    private function paraCent(string $value): int{$value=trim($value);if(!preg_match('/^(\d+)(?:\.(\d{1,2}))?$/',$value,$m))throw new MysoftValidationException('Tahsilat tutari gecersiz.');$fraction=str_pad((string)($m[2]??''),2,'0');return ((int)$m[1]*100)+(int)$fraction;}
    private function decimal(int $cents,int $scale=2): string{return number_format($cents/100,$scale,'.','');}
    private function rateDecimal(int $bp): string{return number_format($bp/100,4,'.','');}
    private function uuid4(): string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
    private function mysoftDate(string $date): string{return (new \DateTimeImmutable($date))->format('m/d/Y 00:00:00');}
    private function isoDate(string $date): string{try{return (new \DateTimeImmutable($date))->format('Y-m-d');}catch(\Throwable){return date('Y-m-d');}}
    private function listedEttn(mixed $entry): ?string{if(!is_string($entry))return null;if(preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',$entry))return strtolower($entry);$json=json_decode($entry,true);$value=is_array($json)?($json['invoiceETTN']??null):null;return is_string($value)&&preg_match('/^[0-9a-f-]{36}$/i',$value)?strtolower($value):null;}
}
