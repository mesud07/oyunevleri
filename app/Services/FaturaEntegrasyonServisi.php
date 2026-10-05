<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Fatura;
use App\Models\KurumEntegrasyonu;

final class FaturaEntegrasyonServisi
{
    public function olustur(int $odemeId,array $data,string $vatRate): array
    {
        $nes=KurumEntegrasyonu::etkinNes()??throw new \RuntimeException('Aktif NES fatura entegrasyonu bulunamadi.');
        try {$invoice=(new NesInvoiceService($nes))->odemedenFaturaOlustur($odemeId,$data,$vatRate);Fatura::entegrasyonDenemesi($odemeId,'nes',true);(new LogServisi())->yaz('fatura_taslagi_olusturuldu','NES fatura taslağı oluşturuldu.',['odeme_id'=>$odemeId,'fatura_id'=>(int)($invoice['id']??0)]);return $invoice;}
        catch(\Throwable $e){Fatura::entegrasyonDenemesi($odemeId,'nes',false,'Entegrasyon işlemi başarısız.',property_exists($e,'nesCode')?$e->nesCode:null,property_exists($e,'httpStatus')?$e->httpStatus:0);(new LogServisi())->yaz('fatura_olusturma_basarisiz','NES fatura taslağı oluşturulamadı.',['odeme_id'=>$odemeId,'hata_tipi'=>get_class($e)]);throw $e;}
    }

    public function caridenOlustur(int $cariId,array $data): array
    {
        $nes=KurumEntegrasyonu::etkinNes()??throw new \RuntimeException('Aktif NES fatura entegrasyonu bulunamadı.');return (new NesInvoiceService($nes))->caridenFaturaOlustur($cariId,$data);
    }

    public function durumGuncelle(int $id): array
    {
        $this->nesFaturasi($id);return (new NesInvoiceService())->durumGuncelle($id);
    }

    public function belge(int $id,string $format): array
    {
        $this->nesFaturasi($id);return (new NesInvoiceService())->belge($id,$format);
    }

    public function onayla(int $id): array
    {
        $this->nesFaturasi($id);$sonuc=(new NesInvoiceService())->taslakOnayla($id);(new LogServisi())->yaz('fatura_onaylandi','Fatura taslağı onaylandı.',['fatura_id'=>$id]);return $sonuc;
    }

    public function topluOnayla(array $idler): array
    {
        return $this->topluCalistir($idler, fn(int $id): array => $this->onayla($id));
    }

    public function arsivle(int $id): array
    {
        $this->nesFaturasi($id);return (new NesInvoiceService())->arsivle($id);
    }

    public function iptal(int $id,string $date,string $type,string $note): array
    {
        $invoice=$this->nesFaturasi($id);
        $dateObject=\DateTimeImmutable::createFromFormat('!Y-m-d',$date);
        $dateErrors=\DateTimeImmutable::getLastErrors();
        if(!$dateObject||($dateErrors!==false&&($dateErrors['warning_count']>0||$dateErrors['error_count']>0))||$dateObject->format('Y-m-d')!==$date)throw new \InvalidArgumentException('Geçerli bir iptal tarihi seçilmelidir.');
        if(!in_array($type,['GIB','NOTER','KEP','TAAHHUTLUMEKTUP','PORTAL'],true))throw new \InvalidArgumentException('Geçersiz iptal tipi.');
        if(trim($note)==='')throw new \InvalidArgumentException('İptal açıklaması zorunludur.');
        if(($invoice['yerel_durum']??'')==='iptal')throw new \InvalidArgumentException('Fatura zaten iptal edilmiş.');
        if(!in_array((string)($invoice['yerel_durum']??''),['gonderildi','isleniyor','basarili'],true))throw new \InvalidArgumentException('Yalnızca kesilmiş e-Arşiv faturaları iptal edilebilir.');
        $sonuc=(new NesInvoiceService())->eArsivIptal($id);(new LogServisi())->yaz('fatura_iptal_edildi','e-Arşiv fatura iptal işlemi gönderildi.',['fatura_id'=>$id,'iptal_tipi'=>$type,'iptal_tarihi'=>$date]);return $sonuc;
    }

    public function topluIptal(array $idler,string $date,string $type,string $note): array
    {
        return $this->topluCalistir($idler, fn(int $id): array => $this->iptal($id,$date,$type,$note));
    }

    private function nesFaturasi(int $id): array
    {
        $invoice=Fatura::idIleBul($id)??throw new \RuntimeException('Fatura bulunamadi.');
        if(($invoice['provider']??'')!=='nes')throw new \RuntimeException('Bu eski belge NES üzerinde bulunmuyor.');
        return $invoice;
    }

    private function topluCalistir(array $idler,callable $islem): array
    {
        $sonuclar=[];$basarili=0;$basarisiz=0;
        foreach($idler as $id){
            $fatura=Fatura::idIleBul((int)$id);
            $etiket=$fatura?(string)($fatura['fatura_no']?:$fatura['ettn']):'#'.(int)$id;
            try{$islem((int)$id);$sonuclar[]=['id'=>(int)$id,'etiket'=>$etiket,'basarili'=>true,'mesaj'=>'Tamamlandı'];$basarili++;}
            catch(\Throwable $e){$sonuclar[]=['id'=>(int)$id,'etiket'=>$etiket,'basarili'=>false,'mesaj'=>$e->getMessage()];$basarisiz++;}
        }
        return ['toplam'=>count($idler),'basarili'=>$basarili,'basarisiz'=>$basarisiz,'sonuclar'=>$sonuclar];
    }
}
