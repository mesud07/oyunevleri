<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NesApiException;
use App\Exceptions\NesValidationException;
use App\Models\Fatura;
use App\Models\FaturaProfili;
use App\Models\Cari;
use App\Models\KurumEntegrasyonu;

final class NesInvoiceService
{
    private array $integration;
    private NesClient $client;

    public function __construct(?array $integration=null)
    {
        $this->integration=$integration??KurumEntegrasyonu::etkinNes()??throw new NesValidationException('Bu kurum icin aktif NES entegrasyonu bulunamadi.');
        $this->client=new NesClient($this->integration);
    }

    public function baglantiTesti(): array
    {
        $credits=$this->client->get('/general/v1/management/creditsummary');
        $eInvoiceSeries=$this->client->get('/einvoice/v1/definitions/series');
        $eArchiveSeries=$this->client->get('/earchive/v1/definitions/series');
        return ['provider'=>'nes','ortam'=>$this->integration['ortam'],'kontorler'=>$credits,'efatura_serileri'=>$eInvoiceSeries,'earsiv_serileri'=>$eArchiveSeries];
    }

    public function odemedenFaturaOlustur(int $odemeId,array $data,string $vatRate): array
    {
        if(Fatura::odemeIcin($odemeId))throw new NesValidationException('Bu tahsilat icin daha once fatura kaydi olusturulmus.');
        $payment=Fatura::odemeBaglami($odemeId);
        if(!$payment||(int)$payment['iptal']===1)throw new NesValidationException('Aktif tahsilat kaydi bulunamadi.');
        $profile=$this->profil($data);$veliId=(int)($payment['fatura_veli_id']??0);
        if($veliId<1)throw new NesValidationException('Tahsilata bagli fatura alicisi/veli bulunamadi.');
        FaturaProfili::kaydet($veliId,$profile);
        $taxpayer=$this->mukellef($profile['vkn_tckn']);$documentType=$taxpayer?'EFATURA':'EARSIVFATURA';$invoiceProfile=$taxpayer?(string)$this->integration['varsayilan_efatura_profili']:'EARSIVFATURA';
        $rate=$payment['kdv_orani']!==null?(float)$payment['kdv_orani']:(float)str_replace(',','.',$vatRate);if($rate<0||$rate>100)throw new NesValidationException('KDV orani 0 ile 100 arasinda olmalidir.');
        $gross=(float)$payment['tutar'];$net=$rate>0?round($gross/(1+$rate/100),2):$gross;$vat=round($gross-$net,2);$uuid=$this->uuid4();$now=new \DateTimeImmutable();
        $module=$documentType==='EFATURA'?'einvoice':'earchive';$prefix=$documentType==='EFATURA'?($this->integration['efatura_prefix']??'TLF'):($this->integration['earsiv_prefix']??'TLA');$number=$this->belgeNumarasi($module,(string)$prefix,$odemeId,$now);
        $customerName=$profile['profil_turu']==='kurumsal'?$profile['unvan']:trim($profile['ad'].' '.$profile['soyad']);
        $xml=(new NesUblBuilder())->build(['profile'=>$invoiceProfile,'number'=>$number,'uuid'=>$uuid,'date'=>(string)$payment['tarih'],'time'=>$now->format('H:i:s'),'note'=>(string)($data['not']??'')],['id'=>(string)$this->integration['tenant_identifier_number'],'name'=>(string)$this->integration['firma_unvani'],'tax_office'=>(string)($this->integration['vergi_dairesi']??''),'address'=>(string)$this->integration['adres'],'city'=>(string)$this->integration['il'],'district'=>(string)$this->integration['ilce'],'postal_code'=>(string)($this->integration['posta_kodu']??''),'country'=>(string)($this->integration['ulke']??'TÜRKİYE'),'email'=>(string)($this->integration['eposta']??''),'phone'=>(string)($this->integration['telefon']??'')],['id'=>$profile['vkn_tckn'],'name'=>$customerName,'tax_office'=>$profile['vergi_dairesi'],'address'=>$profile['adres'],'city'=>$profile['il'],'district'=>$profile['ilce'],'country'=>$profile['ulke'],'email'=>$profile['eposta'],'phone'=>$profile['telefon']],['name'=>(string)$payment['paket_adi'],'net'=>number_format($net,2,'.',''),'vat'=>number_format($vat,2,'.',''),'gross'=>number_format($gross,2,'.',''),'vat_rate'=>number_format($rate,2,'.','')]);
        $receiverAlias='';if($taxpayer)foreach(($taxpayer['aliases']??[]) as $alias)if(strcasecmp((string)($alias['type']??''),'Pk')===0){$receiverAlias=(string)$alias['alias'];break;}
        $fields=['IsDirectSend'=>'false','PreviewType'=>'None','SourceApp'=>'TalyaKids','SourceAppRecordId'=>'odeme-'.$odemeId,'AutoSaveCompany'=>'true'];if($documentType==='EFATURA'){$fields['SenderAlias']=(string)($this->integration['gb_alias']??'');if($receiverAlias!=='')$fields['ReceiverAlias']=$receiverAlias;}
        $response=$this->client->upload('/'.$module.'/v1/uploads/document',$xml,$fields);
        if(empty($response['uuid']))throw new NesApiException('NES cevabinda belge UUID bilgisi bulunamadi.',null,200,$response,true);
        $localId=Fatura::taslakOlustur(['entegrasyon_id'=>(int)$this->integration['id'],'odeme_id'=>$odemeId,'veli_id'=>$veliId,'ogrenci_id'=>(int)$payment['ogrenci_id'],'belge_turu'=>$documentType,'profil'=>$invoiceProfile,'ettn'=>(string)$response['uuid'],'referans_anahtari'=>'TALYA-ODEME-'.$odemeId,'fatura_tarihi'=>(string)$payment['tarih'],'fatura_saati'=>$now->format('H:i:s'),'ara_toplam'=>number_format($net,2,'.',''),'kdv_toplami'=>number_format($vat,2,'.',''),'genel_toplam'=>number_format($gross,2,'.',''),'alici_adi'=>$customerName,'alici_vkn_tckn'=>$profile['vkn_tckn']],['aciklama'=>(string)$payment['paket_adi'],'miktar'=>'1.0000','birim_kodu'=>'C62','birim_fiyat'=>number_format($net,4,'.',''),'kdv_orani'=>number_format($rate,4,'.',''),'kdv_tutari'=>number_format($vat,2,'.',''),'satir_toplami'=>number_format($gross,2,'.','')]);
        Fatura::nesTaslakBasarili($localId,$response,$response);return Fatura::idIleBul($localId)??[];
    }

    public function caridenFaturaOlustur(int $cariId,array $data): array
    {
        $cari=Cari::idIleBul($cariId)??throw new NesValidationException('Cari bulunamadı.');$description=trim((string)($data['aciklama']??''));if($description==='')throw new NesValidationException('Fatura açıklaması zorunludur.');$gross=(float)str_replace(',','.',(string)($data['tutar']??0));$rate=(float)str_replace(',','.',(string)($data['kdv_orani']??0));if($gross<=0)throw new NesValidationException('Fatura tutarı sıfırdan büyük olmalıdır.');if($rate<0||$rate>100)throw new NesValidationException('KDV oranı 0 ile 100 arasında olmalıdır.');
        $date=trim((string)($data['tarih']??date('Y-m-d')));if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))throw new NesValidationException('Fatura tarihi geçersiz.');$profile=['profil_turu'=>(string)$cari['profil_turu'],'ad'=>(string)($cari['ad']??''),'soyad'=>(string)($cari['soyad']??''),'unvan'=>(string)($cari['unvan']??''),'vkn_tckn'=>(string)$cari['vkn_tckn'],'vergi_dairesi'=>(string)($cari['vergi_dairesi']??''),'adres'=>(string)$cari['adres'],'il'=>(string)$cari['il'],'ilce'=>(string)$cari['ilce'],'ulke'=>(string)$cari['ulke'],'eposta'=>(string)($cari['eposta']??''),'telefon'=>(string)($cari['telefon']??'')];
        $taxpayer=$this->mukellef($profile['vkn_tckn']);$documentType=$taxpayer?'EFATURA':'EARSIVFATURA';$invoiceProfile=$taxpayer?(string)$this->integration['varsayilan_efatura_profili']:'EARSIVFATURA';$net=$rate>0?round($gross/(1+$rate/100),2):$gross;$vat=round($gross-$net,2);$uuid=$this->uuid4();$now=new \DateTimeImmutable();$module=$documentType==='EFATURA'?'einvoice':'earchive';$prefix=$documentType==='EFATURA'?($this->integration['efatura_prefix']??'TLF'):($this->integration['earsiv_prefix']??'TLA');$number=$this->belgeNumarasi($module,(string)$prefix,random_int(1,999999999),$now);$customerName=$profile['profil_turu']==='kurumsal'?$profile['unvan']:trim($profile['ad'].' '.$profile['soyad']);
        $xml=(new NesUblBuilder())->build(['profile'=>$invoiceProfile,'number'=>$number,'uuid'=>$uuid,'date'=>$date,'time'=>$now->format('H:i:s'),'note'=>(string)($data['not']??'')],['id'=>(string)$this->integration['tenant_identifier_number'],'name'=>(string)$this->integration['firma_unvani'],'tax_office'=>(string)($this->integration['vergi_dairesi']??''),'address'=>(string)$this->integration['adres'],'city'=>(string)$this->integration['il'],'district'=>(string)$this->integration['ilce'],'postal_code'=>(string)($this->integration['posta_kodu']??''),'country'=>(string)($this->integration['ulke']??'TÜRKİYE'),'email'=>(string)($this->integration['eposta']??''),'phone'=>(string)($this->integration['telefon']??'')],['id'=>$profile['vkn_tckn'],'name'=>$customerName,'tax_office'=>$profile['vergi_dairesi'],'address'=>$profile['adres'],'city'=>$profile['il'],'district'=>$profile['ilce'],'country'=>$profile['ulke'],'email'=>$profile['eposta'],'phone'=>$profile['telefon']],['name'=>$description,'net'=>number_format($net,2,'.',''),'vat'=>number_format($vat,2,'.',''),'gross'=>number_format($gross,2,'.',''),'vat_rate'=>number_format($rate,2,'.','')]);
        $receiverAlias='';if($taxpayer)foreach(($taxpayer['aliases']??[]) as $alias)if(strcasecmp((string)($alias['type']??''),'Pk')===0){$receiverAlias=(string)$alias['alias'];break;}$fields=['IsDirectSend'=>'false','PreviewType'=>'None','SourceApp'=>'TalyaKids','SourceAppRecordId'=>'cari-'.$cariId.'-'.$uuid,'AutoSaveCompany'=>'true'];if($documentType==='EFATURA'){$fields['SenderAlias']=(string)($this->integration['gb_alias']??'');if($receiverAlias!=='')$fields['ReceiverAlias']=$receiverAlias;}$response=$this->client->upload('/'.$module.'/v1/uploads/document',$xml,$fields);if(empty($response['uuid']))throw new NesApiException('NES cevabında belge UUID bilgisi bulunamadı.',null,200,$response,true);
        $localId=Fatura::taslakOlustur(['entegrasyon_id'=>(int)$this->integration['id'],'odeme_id'=>null,'veli_id'=>$cari['veli_id']??null,'ogrenci_id'=>null,'cari_id'=>$cariId,'belge_turu'=>$documentType,'profil'=>$invoiceProfile,'ettn'=>(string)$response['uuid'],'referans_anahtari'=>'TALYA-CARI-'.$cariId.'-'.$uuid,'fatura_tarihi'=>$date,'fatura_saati'=>$now->format('H:i:s'),'ara_toplam'=>number_format($net,2,'.',''),'kdv_toplami'=>number_format($vat,2,'.',''),'genel_toplam'=>number_format($gross,2,'.',''),'alici_adi'=>$customerName,'alici_vkn_tckn'=>$profile['vkn_tckn']],['aciklama'=>$description,'miktar'=>'1.0000','birim_kodu'=>'C62','birim_fiyat'=>number_format($net,4,'.',''),'kdv_orani'=>number_format($rate,4,'.',''),'kdv_tutari'=>number_format($vat,2,'.',''),'satir_toplami'=>number_format($gross,2,'.','')]);Fatura::nesTaslakBasarili($localId,$response,$response);return Fatura::idIleBul($localId)??[];
    }

    public function taslakOnayla(int $id): array
    {
        $invoice=Fatura::idIleBul($id)??throw new NesValidationException('Fatura bulunamadi.');if(($invoice['yerel_durum']??'')!=='taslak')throw new NesValidationException('Yalnızca taslak faturalar onaylanabilir.');$module=$invoice['belge_turu']==='EFATURA'?'einvoice':'earchive';$response=$this->client->post('/'.$module.'/v1/uploads/draft/send',[(string)$invoice['ettn']]);$result=is_array($response[0]??null)?$response[0]:[];if(!$result||!($result['succeeded']??false)){$message=trim((string)($result['message']??''));$detail=$result['unProcessableEntityDetail']??$result['validationProblemDetail']??null;throw new NesApiException($message?:'NES taslak faturayı onaylayamadı.',null,422,is_array($detail)?$detail:$response,false);}Fatura::nesOnayBasarili($id,$result,$response);$this->arsivle($id);return Fatura::idIleBul($id)??[];
    }

    public function durumGuncelle(int $id): array
    {
        $invoice=Fatura::idIleBul($id)??throw new NesValidationException('Fatura bulunamadi.');if(($invoice['yerel_durum']??'')==='iptal'){$this->arsivle($id);return $invoice;}$module=$invoice['belge_turu']==='EFATURA'?'einvoice/v1/outgoing/invoices':'earchive/v1/invoices';$response=$this->client->get('/'.$module.'/'.rawurlencode((string)$invoice['ettn']));$status=(string)($response['outgoingStatus']??$response['archiveDocumentStatus']??$response['recordStatus']??'Unknown');Fatura::providerDurumKaydet($id,'nes',$status,$response);$this->arsivle($id);return Fatura::idIleBul($id)??[];
    }

    public function belge(int $id,string $format): array
    {
        $invoice=Fatura::idIleBul($id)??throw new NesValidationException('Fatura bulunamadi.');$format=strtolower($format);if(!in_array($format,['pdf','xml'],true))throw new NesValidationException('Gecersiz belge formati.');$archive=new FaturaArsivServisi();if(($invoice['yerel_durum']??'')!=='taslak'){$local=$archive->oku($invoice,$format);if($local)return $local;}$module=$invoice['belge_turu']==='EFATURA'?'einvoice/v1/outgoing/invoices':'earchive/v1/invoices';$file=$this->client->binary('/'.$module.'/'.rawurlencode((string)$invoice['ettn']).'/'.$format);$content=(string)($file['content']??'');if($content==='')throw new NesApiException('NES belge içeriğini boş döndürdü.');if(($invoice['yerel_durum']??'')!=='taslak')return $archive->kaydet($invoice,$format,$content);return ['content'=>$content,'mime'=>$format==='pdf'?'application/pdf':'application/xml','name'=>($invoice['fatura_no']?:$invoice['ettn']).'.'.$format,'source'=>'nes'];
    }

    public function arsivle(int $id): array
    {
        $invoice=Fatura::idIleBul($id)??throw new NesValidationException('Fatura bulunamadi.');
        if(($invoice['yerel_durum']??'')==='taslak')return ['tamamlandi'=>false,'hatalar'=>['Taslak belgeler yerel resmî fatura arşivine alınmaz.']];
        $errors=[];
        foreach(['pdf','xml'] as $format){try{$this->belge($id,$format);}catch(\Throwable $e){$errors[$format]=$e->getMessage();}}
        $status=\App\Models\FaturaArsivi::durum($id);
        $status['hatalar']=$errors;
        return $status;
    }

    public function eArsivIptal(int $id): array
    {
        $invoice=Fatura::idIleBul($id)??throw new NesValidationException('Fatura bulunamadi.');if($invoice['belge_turu']!=='EARSIVFATURA')throw new NesValidationException('Bu iptal işlemi yalnızca e-Arşiv faturalar içindir.');if(($invoice['yerel_durum']??'')==='taslak')throw new NesValidationException('Taslak fatura iptal edilemez.');if(($invoice['yerel_durum']??'')==='iptal')throw new NesValidationException('Fatura zaten iptal edilmiş.');$this->arsivle($id);$response=$this->client->post('/earchive/v1/invoices/cancel',['uuids'=>[(string)$invoice['ettn']]]);Fatura::providerDurumKaydet($id,'nes','Canceled',['uuid'=>(string)$invoice['ettn'],'canceled'=>true,'response'=>$response]);return Fatura::idIleBul($id)??[];
    }

    private function mukellef(string $id): ?array {try{return $this->client->get('/einvoice/v1/users/'.rawurlencode($id).'/All');}catch(NesApiException $e){if($e->httpStatus===404)return null;throw $e;}}
    private function belgeNumarasi(string $module,string $prefix,int $odemeId,\DateTimeImmutable $now): string {$prefix=strtoupper(str_pad(substr(trim($prefix),0,3),3,'X'));$series=$this->client->get('/'.$module.'/v1/definitions/series/'.rawurlencode($prefix).'?year='.$now->format('Y'));if((bool)($series['isPortal']??false))return $prefix;return $prefix.$now->format('Y').str_pad((string)$odemeId,9,'0',STR_PAD_LEFT);}
    private function profil(array $d): array {$type=(string)($d['profil_turu']??'bireysel');$id=preg_replace('/\D+/','',(string)($d['vkn_tckn']??''));if(!in_array($type,['bireysel','kurumsal'],true)||($type==='bireysel'&&strlen($id)!==11)||($type==='kurumsal'&&strlen($id)!==10))throw new NesValidationException('VKN/TCKN fatura profiliyle uyumlu degil.');foreach(['adres','il','ilce'] as $f)if(trim((string)($d[$f]??''))==='')throw new NesValidationException('Fatura profili icin adres, il ve ilce zorunludur.');if($type==='kurumsal'&&trim((string)($d['unvan']??''))==='')throw new NesValidationException('Kurumsal faturada firma unvani zorunludur.');if($type==='bireysel'&&(trim((string)($d['ad']??''))===''||trim((string)($d['soyad']??''))===''))throw new NesValidationException('Bireysel faturada ad ve soyad zorunludur.');return ['profil_turu'=>$type,'ad'=>trim((string)($d['ad']??'')),'soyad'=>trim((string)($d['soyad']??'')),'unvan'=>trim((string)($d['unvan']??'')),'vkn_tckn'=>$id,'vergi_dairesi'=>trim((string)($d['vergi_dairesi']??'')),'adres'=>trim((string)$d['adres']),'il'=>trim((string)$d['il']),'ilce'=>trim((string)$d['ilce']),'ulke'=>'TÜRKİYE','eposta'=>trim((string)($d['eposta']??'')),'telefon'=>trim((string)($d['telefon']??''))];}
    private function uuid4(): string {$d=random_bytes(16);$d[6]=chr((ord($d[6])&15)|64);$d[8]=chr((ord($d[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
}
