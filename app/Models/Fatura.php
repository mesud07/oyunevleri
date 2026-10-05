<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

final class Fatura extends Model
{
    public static function odemeBaglami(int $odemeId): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT od.*, p.paket_adi, p.kdv_orani, o.ad AS ogrenci_ad, o.soyad AS ogrenci_soyad,
                    COALESCE(od.veli_id, ov.veli_id) AS fatura_veli_id,
                    v.ad AS veli_ad, v.soyad AS veli_soyad, v.tc_kimlik_no, v.telefon, v.eposta, v.il, v.ilce, v.adres
             FROM odemeler od
             INNER JOIN paketler p ON p.id=od.paket_id AND p.kurum_id=od.kurum_id
             INNER JOIN ogrenciler o ON o.id=od.ogrenci_id AND o.kurum_id=od.kurum_id
             LEFT JOIN ogrenci_velileri ov ON ov.ogrenci_id=o.id AND ov.kurum_id=o.kurum_id AND ov.birincil_mi=1
             LEFT JOIN veliler v ON v.id=COALESCE(od.veli_id, ov.veli_id) AND v.kurum_id=od.kurum_id
             WHERE od.id=:id AND od.kurum_id=:kurum_id LIMIT 1'
        );
        $stmt->execute(['id'=>$odemeId,'kurum_id'=>self::kurumId()]);
        $row=$stmt->fetch();
        return $row ?: null;
    }

    public static function odemeIcin(int $odemeId): ?array
    {
        $stmt=self::db()->prepare('SELECT * FROM faturalar WHERE kurum_id=:kurum_id AND odeme_id=:odeme_id LIMIT 1');
        $stmt->execute(['kurum_id'=>self::kurumId(),'odeme_id'=>$odemeId]);
        $row=$stmt->fetch();
        return $row ?: null;
    }

    public static function taslakOlustur(array $data, array $kalem): int
    {
        $db=self::db();
        try {
            $db->beginTransaction();
            $stmt=$db->prepare(
                'INSERT INTO faturalar
                 (kurum_id,entegrasyon_id,odeme_id,veli_id,ogrenci_id,cari_id,kaynak,belge_turu,profil,fatura_tipi,ettn,referans_anahtari,fatura_tarihi,fatura_saati,para_birimi,ara_toplam,kdv_toplami,genel_toplam,alici_adi,alici_vkn_tckn,yerel_durum)
                 VALUES (:kurum_id,:entegrasyon_id,:odeme_id,:veli_id,:ogrenci_id,:cari_id,"application",:belge_turu,:profil,"SATIS",:ettn,:referans,:fatura_tarihi,:fatura_saati,"TRY",:ara_toplam,:kdv,:toplam,:alici,:vkn,"olusturuluyor")'
            );
            $stmt->execute([
                'kurum_id'=>self::kurumId(),'entegrasyon_id'=>$data['entegrasyon_id'],'odeme_id'=>$data['odeme_id'],
                'veli_id'=>$data['veli_id']??null,'ogrenci_id'=>$data['ogrenci_id']??null,'cari_id'=>$data['cari_id']??null,'belge_turu'=>$data['belge_turu'],'profil'=>$data['profil'],
                'ettn'=>$data['ettn'],'referans'=>$data['referans_anahtari'],'fatura_tarihi'=>$data['fatura_tarihi'],'fatura_saati'=>$data['fatura_saati'],
                'ara_toplam'=>$data['ara_toplam'],'kdv'=>$data['kdv_toplami'],'toplam'=>$data['genel_toplam'],'alici'=>$data['alici_adi'],'vkn'=>$data['alici_vkn_tckn'],
            ]);
            $id=(int)$db->lastInsertId();
            $item=$db->prepare('INSERT INTO fatura_kalemleri (kurum_id,fatura_id,aciklama,miktar,birim_kodu,birim_fiyat,kdv_orani,kdv_tutari,satir_toplami) VALUES (:kurum_id,:fatura_id,:aciklama,:miktar,:birim,:birim_fiyat,:kdv_orani,:kdv_tutari,:toplam)');
            $item->execute(['kurum_id'=>self::kurumId(),'fatura_id'=>$id,'aciklama'=>$kalem['aciklama'],'miktar'=>$kalem['miktar'],'birim'=>$kalem['birim_kodu'],'birim_fiyat'=>$kalem['birim_fiyat'],'kdv_orani'=>$kalem['kdv_orani'],'kdv_tutari'=>$kalem['kdv_tutari'],'toplam'=>$kalem['satir_toplami']]);
            $db->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    public static function gonderimBasarili(int $id, array $result, array $response): void
    {
        self::guncelle($id, [
            'provider'=>'mysoft','provider_invoice_id'=>(string)($result['invoiceId']??''),'mysoft_invoice_id'=>(int)($result['invoiceId']??0)?:null,'ettn'=>(string)($result['invoiceETTN']??''),
            'fatura_no'=>(string)($result['docNo']??''),'yerel_durum'=>'gonderildi','mysoft_durum'=>null,
            'hata_kodu'=>null,'hata_mesaji'=>null,'son_http_durumu'=>200,'son_cevap'=>json_encode($response,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'gonderilme_tarihi'=>date('Y-m-d H:i:s'),
        ]);
    }

    public static function nesGonderimBasarili(int $id,array $result,array $response,bool $fallback=false): void
    {
        self::guncelle($id,['provider'=>'nes','provider_invoice_id'=>(string)($result['uuid']??''),'ettn'=>(string)($result['uuid']??''),'fatura_no'=>(string)($result['documentNumber']??''),'yerel_durum'=>'gonderildi','provider_durum'=>'Waiting','fallback_kullanildi'=>$fallback?1:0,'hata_kodu'=>null,'hata_mesaji'=>null,'son_http_durumu'=>200,'son_cevap'=>json_encode($response,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'gonderilme_tarihi'=>date('Y-m-d H:i:s')]);
    }

    public static function nesTaslakBasarili(int $id,array $result,array $response): void
    {
        self::guncelle($id,['provider'=>'nes','provider_invoice_id'=>(string)($result['uuid']??''),'ettn'=>(string)($result['uuid']??''),'fatura_no'=>(string)($result['documentNumber']??''),'yerel_durum'=>'taslak','provider_durum'=>'Draft','hata_kodu'=>null,'hata_mesaji'=>null,'son_http_durumu'=>200,'son_cevap'=>json_encode($response,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'gonderilme_tarihi'=>null]);
    }

    public static function nesOnayBasarili(int $id,array $result,array $response): void
    {
        $document=is_array($result['uploadDocumentResponse']??null)?$result['uploadDocumentResponse']:[];
        $fields=['yerel_durum'=>'gonderildi','provider_durum'=>'Waiting','hata_kodu'=>null,'hata_mesaji'=>null,'son_http_durumu'=>200,'son_cevap'=>json_encode($response,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'gonderilme_tarihi'=>date('Y-m-d H:i:s')];
        if(trim((string)($document['documentNumber']??''))!=='')$fields['fatura_no']=(string)$document['documentNumber'];
        self::guncelle($id,$fields);
    }

    public static function providerAta(int $id,string $provider,bool $fallback=false): void
    {
        self::guncelle($id,['provider'=>$provider,'fallback_kullanildi'=>$fallback?1:0]);
    }

    public static function entegrasyonDenemesi(int $odemeId,string $provider,bool $basarili,?string $message=null,?string $code=null,int $http=0): void
    {
        $stmt=self::db()->prepare('INSERT INTO fatura_entegrasyon_denemeleri (kurum_id,odeme_id,provider,basarili,http_durumu,hata_kodu,hata_mesaji) VALUES (:kurum_id,:odeme_id,:provider,:basarili,:http,:kod,:mesaj)');
        $stmt->execute(['kurum_id'=>self::kurumId(),'odeme_id'=>$odemeId,'provider'=>$provider,'basarili'=>$basarili?1:0,'http'=>$http?:null,'kod'=>$code,'mesaj'=>$message]);
    }

    public static function hataKaydet(int $id, string $message, ?string $code=null, int $http=0, ?array $response=null): void
    {
        self::guncelle($id,['yerel_durum'=>'hatali','hata_kodu'=>$code,'hata_mesaji'=>$message,'son_http_durumu'=>$http?:null,'son_cevap'=>$response?json_encode($response,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):null]);
    }

    public static function durumKaydet(int $id, array $status): void
    {
        $original=(string)($status['invoiceStatusText']??'');
        $local=match($original){'IPTAL_EDILDI'=>'iptal','HATA'=>'hatali','RED'=>'reddedildi','KABUL','ONAYLANDI','ALICIYA_ULASTI'=>'basarili',default=>'isleniyor'};
        self::guncelle($id,['mysoft_invoice_id'=>(int)($status['id']??0)?:null,'fatura_no'=>(string)($status['docNo']??''),'mysoft_durum'=>$original,'yerel_durum'=>$local,'hata_mesaji'=>(string)($status['declineReason']??'')?:null,'durum_sorgulama_tarihi'=>date('Y-m-d H:i:s'),'iptal_tarihi'=>$local==='iptal'?date('Y-m-d H:i:s'):null]);
    }

    public static function providerDurumKaydet(int $id,string $provider,string $status,array $response): void
    {
        $upper=strtoupper($status);$local=match(true){str_contains($upper,'DRAFT')||str_contains($upper,'TASLAK')=>'taslak',str_contains($upper,'CANCEL')||str_contains($upper,'IPTAL')=>'iptal',str_contains($upper,'ERROR')||str_contains($upper,'HATA')||str_contains($upper,'PARSEERROR')||str_contains($upper,'FILENOTFOUND')=>'hatali',str_contains($upper,'REJECT')||str_contains($upper,'RED')=>'reddedildi',$upper==='SIGNED'||str_contains($upper,'SUCCE')||str_contains($upper,'ACCEPT')||str_contains($upper,'KABUL')||str_contains($upper,'SUCCESSFULLY')=>'basarili',default=>'isleniyor'};
        self::guncelle($id,['provider'=>$provider,'provider_durum'=>$status,'yerel_durum'=>$local,'son_cevap'=>json_encode($response,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'durum_sorgulama_tarihi'=>date('Y-m-d H:i:s'),'iptal_tarihi'=>$local==='iptal'?date('Y-m-d H:i:s'):null]);
    }

    public static function idIleBul(int $id): ?array
    {
        $stmt=self::db()->prepare('SELECT f.*, CONCAT(o.ad," ",o.soyad) AS ogrenci FROM faturalar f LEFT JOIN ogrenciler o ON o.id=f.ogrenci_id AND o.kurum_id=f.kurum_id WHERE f.id=:id AND f.kurum_id=:kurum_id LIMIT 1');
        $stmt->execute(['id'=>$id,'kurum_id'=>self::kurumId()]);
        $row=$stmt->fetch();
        if (!$row) return null;
        $items=self::db()->prepare('SELECT * FROM fatura_kalemleri WHERE fatura_id=:id AND kurum_id=:kurum_id ORDER BY id');
        $items->execute(['id'=>$id,'kurum_id'=>self::kurumId()]);
        $row['kalemler']=$items->fetchAll();
        return $row;
    }

    public static function ettnIleBul(string $ettn): ?array
    {
        $stmt=self::db()->prepare('SELECT * FROM faturalar WHERE kurum_id=:kurum_id AND ettn=:ettn LIMIT 1');
        $stmt->execute(['kurum_id'=>self::kurumId(),'ettn'=>$ettn]);
        $row=$stmt->fetch(); return $row?:null;
    }

    public static function liste(array $filter, int $page=1, int $limit=25): array
    {
        $where=['kurum_id=:kurum_id']; $params=['kurum_id'=>self::kurumId()];
        foreach (['belge_turu','yerel_durum'] as $field) if (!empty($filter[$field])) { $where[]="$field=:$field"; $params[$field]=$filter[$field]; }
        if (!empty($filter['baslangic'])) {$where[]='fatura_tarihi>=:baslangic';$params['baslangic']=$filter['baslangic'];}
        if (!empty($filter['bitis'])) {$where[]='fatura_tarihi<=:bitis';$params['bitis']=$filter['bitis'];}
        if (!empty($filter['arama'])) {$where[]='(fatura_no LIKE :arama OR ettn LIKE :arama OR alici_adi LIKE :arama OR alici_vkn_tckn LIKE :arama)';$params['arama']='%'.$filter['arama'].'%';}
        $sql=implode(' AND ',$where);
        $count=self::db()->prepare("SELECT COUNT(*) FROM faturalar WHERE $sql");$count->execute($params);$total=(int)$count->fetchColumn();
        $offset=($page-1)*$limit;
        $stmt=self::db()->prepare("SELECT * FROM faturalar WHERE $sql ORDER BY fatura_tarihi DESC,id DESC LIMIT $limit OFFSET $offset");$stmt->execute($params);
        return ['kayitlar'=>$stmt->fetchAll(),'sayfalama'=>['sayfa'=>$page,'limit'=>$limit,'toplam'=>$total,'toplam_sayfa'=>max(1,(int)ceil($total/$limit))]];
    }

    public static function kesinlesmemis(int $limit=50): array
    {
        $stmt=self::db()->prepare('SELECT id FROM faturalar WHERE kurum_id=:kurum_id AND yerel_durum IN ("gonderildi","isleniyor") ORDER BY COALESCE(durum_sorgulama_tarihi,"2000-01-01") ASC LIMIT '.max(1,min(200,$limit)));
        $stmt->execute(self::kurumParam()); return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function disKaynakKaydet(array $data): int
    {
        $existing=self::ettnIleBul((string)$data['ettn']); if ($existing) return (int)$existing['id'];
        $stmt=self::db()->prepare('INSERT INTO faturalar (kurum_id,entegrasyon_id,kaynak,belge_turu,profil,fatura_tipi,ettn,fatura_no,fatura_tarihi,para_birimi,ara_toplam,kdv_toplami,genel_toplam,alici_adi,alici_vkn_tckn,yerel_durum,mysoft_durum,son_cevap) VALUES (:kurum_id,:entegrasyon_id,"mysoft",:belge_turu,:profil,:fatura_tipi,:ettn,:fatura_no,:tarih,:para,:ara,:kdv,:toplam,:alici,:vkn,"isleniyor",:durum,:cevap)');
        $stmt->execute(['kurum_id'=>self::kurumId()]+$data); return (int)self::db()->lastInsertId();
    }

    public static function disKalemleriKaydet(int $faturaId, array $details): void
    {
        $stmt=self::db()->prepare('INSERT INTO fatura_kalemleri (kurum_id,fatura_id,aciklama,miktar,birim_kodu,birim_fiyat,kdv_orani,kdv_tutari,satir_toplami) VALUES (:kurum_id,:fatura_id,:aciklama,:miktar,:birim,:birim_fiyat,:kdv_orani,:kdv_tutari,:toplam)');
        foreach($details as $detail){
            if(!is_array($detail))continue;$taxTotal=is_array($detail['taxTotal']??null)?$detail['taxTotal']:[];$taxAmount=(float)($taxTotal['taxAmount']??0);$subtotals=$taxTotal['taxSubtotalList']??[];$vatRate=0.0;
            foreach($subtotals as $subtotal){if((string)($subtotal['taxTypeCode']??'')==='0015'||mb_strtoupper((string)($subtotal['taxName']??''))==='KDV'){$vatRate=(float)($subtotal['percent']??0);break;}}
            $item=is_array($detail['detailItem']??null)?$detail['detailItem']:[];$line=(float)($detail['lineExtensionAmount']??0);
            $stmt->execute(['kurum_id'=>self::kurumId(),'fatura_id'=>$faturaId,'aciklama'=>(string)($item['itemName']??$item['itemDescription']??'Fatura kalemi'),'miktar'=>(float)($detail['invoicedQuantity']??0),'birim'=>(string)($detail['unitCode']??'C62'),'birim_fiyat'=>(float)($detail['unitPrice']??0),'kdv_orani'=>$vatRate,'kdv_tutari'=>$taxAmount,'toplam'=>$line+$taxAmount]);
        }
    }

    private static function guncelle(int $id,array $fields): void
    {
        $allowed=['provider','provider_invoice_id','provider_durum','fallback_kullanildi','mysoft_invoice_id','ettn','fatura_no','yerel_durum','mysoft_durum','hata_kodu','hata_mesaji','son_http_durumu','son_cevap','gonderilme_tarihi','durum_sorgulama_tarihi','iptal_tarihi'];
        $set=[];$params=['id'=>$id,'kurum_id'=>self::kurumId()];
        foreach($fields as $key=>$value){if(in_array($key,$allowed,true)){$set[]="$key=:$key";$params[$key]=$value;}}
        if(!$set)return;
        $stmt=self::db()->prepare('UPDATE faturalar SET '.implode(',',$set).' WHERE id=:id AND kurum_id=:kurum_id');$stmt->execute($params);
    }
}
