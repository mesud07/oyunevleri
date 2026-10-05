<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class FaturaArsivi extends Model
{
    public static function formatIcin(int $faturaId, string $format): ?array
    {
        $stmt=self::db()->prepare('SELECT * FROM fatura_arsiv_dosyalari WHERE kurum_id=:kurum_id AND fatura_id=:fatura_id AND format=:format LIMIT 1');
        $stmt->execute(['kurum_id'=>self::kurumId(),'fatura_id'=>$faturaId,'format'=>$format]);
        $row=$stmt->fetch();
        return $row?:null;
    }

    public static function kaydet(int $faturaId,string $format,string $relativePath,string $sha256,int $size): void
    {
        $stmt=self::db()->prepare(
            'INSERT INTO fatura_arsiv_dosyalari (kurum_id,fatura_id,format,goreli_yol,sha256,dosya_boyutu)
             VALUES (:kurum_id,:fatura_id,:format,:yol,:sha256,:boyut)
             ON DUPLICATE KEY UPDATE goreli_yol=VALUES(goreli_yol),sha256=VALUES(sha256),dosya_boyutu=VALUES(dosya_boyutu),guncellenme_tarihi=NOW()'
        );
        $stmt->execute(['kurum_id'=>self::kurumId(),'fatura_id'=>$faturaId,'format'=>$format,'yol'=>$relativePath,'sha256'=>$sha256,'boyut'=>$size]);
    }

    public static function durum(int $faturaId): array
    {
        $stmt=self::db()->prepare('SELECT format,goreli_yol,sha256,dosya_boyutu,COALESCE(guncellenme_tarihi,olusturulma_tarihi) AS arsivlenme_tarihi FROM fatura_arsiv_dosyalari WHERE kurum_id=:kurum_id AND fatura_id=:fatura_id');
        $stmt->execute(['kurum_id'=>self::kurumId(),'fatura_id'=>$faturaId]);
        $result=['pdf'=>null,'xml'=>null,'tamamlandi'=>false];
        foreach($stmt->fetchAll() as $row)$result[(string)$row['format']]=$row;
        $result['tamamlandi']=is_array($result['pdf'])&&is_array($result['xml']);
        return $result;
    }

    public static function eksikFaturaIdleri(int $limit=50): array
    {
        $limit=max(1,min(200,$limit));
        $stmt=self::db()->prepare(
            'SELECT f.id
             FROM faturalar f
             LEFT JOIN fatura_arsiv_dosyalari pdf ON pdf.fatura_id=f.id AND pdf.kurum_id=f.kurum_id AND pdf.format="pdf"
             LEFT JOIN fatura_arsiv_dosyalari xml ON xml.fatura_id=f.id AND xml.kurum_id=f.kurum_id AND xml.format="xml"
             WHERE f.kurum_id=:kurum_id AND f.provider="nes" AND f.ettn<>""
               AND f.yerel_durum IN ("gonderildi","isleniyor","basarili","reddedildi")
               AND (pdf.id IS NULL OR xml.id IS NULL)
             ORDER BY f.id ASC LIMIT '.$limit
        );
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}
