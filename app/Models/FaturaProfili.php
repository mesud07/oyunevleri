<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class FaturaProfili extends Model
{
    public static function veliIcin(int $veliId): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM fatura_profilleri WHERE kurum_id=:kurum_id AND veli_id=:veli_id AND aktif=1 LIMIT 1');
        $stmt->execute(['kurum_id' => self::kurumId(), 'veli_id' => $veliId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function kaydet(int $veliId, array $data): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO fatura_profilleri
             (kurum_id, veli_id, profil_turu, ad, soyad, unvan, vkn_tckn, vergi_dairesi, adres, il, ilce, ulke, eposta, telefon, aktif)
             VALUES (:kurum_id,:veli_id,:profil_turu,:ad,:soyad,:unvan,:vkn_tckn,:vergi_dairesi,:adres,:il,:ilce,:ulke,:eposta,:telefon,1)
             ON DUPLICATE KEY UPDATE profil_turu=VALUES(profil_turu),ad=VALUES(ad),soyad=VALUES(soyad),unvan=VALUES(unvan),vkn_tckn=VALUES(vkn_tckn),vergi_dairesi=VALUES(vergi_dairesi),adres=VALUES(adres),il=VALUES(il),ilce=VALUES(ilce),ulke=VALUES(ulke),eposta=VALUES(eposta),telefon=VALUES(telefon),aktif=1'
        );
        $stmt->execute([
            'kurum_id'=>self::kurumId(),'veli_id'=>$veliId,'profil_turu'=>$data['profil_turu'],
            'ad'=>trim((string)($data['ad']??''))?:null,'soyad'=>trim((string)($data['soyad']??''))?:null,
            'unvan'=>trim((string)($data['unvan']??''))?:null,'vkn_tckn'=>preg_replace('/\D+/', '', (string)$data['vkn_tckn']),
            'vergi_dairesi'=>trim((string)($data['vergi_dairesi']??''))?:null,'adres'=>trim((string)$data['adres']),
            'il'=>trim((string)$data['il']),'ilce'=>trim((string)$data['ilce']),'ulke'=>trim((string)($data['ulke']??'TÜRKİYE'))?:'TÜRKİYE',
            'eposta'=>trim((string)($data['eposta']??''))?:null,'telefon'=>trim((string)($data['telefon']??''))?:null,
        ]);
        $row = self::veliIcin($veliId);
        return (int) ($row['id'] ?? 0);
    }
}
