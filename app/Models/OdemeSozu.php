<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Model;
final class OdemeSozu extends Model
{
    public static function liste(): array
    {
        $stmt = self::db()->query(
            'SELECT os.id, CONCAT(o.ad, " ", o.soyad) AS ogrenci, p.paket_adi, os.soz_verilen_tutar,
                    os.soz_verilen_tarih, os.hatirlatma_tarihi, os.durum
             FROM odeme_sozleri os
             INNER JOIN ogrenciler o ON o.id = os.ogrenci_id
             INNER JOIN paketler p ON p.id = os.paket_id
             ORDER BY os.soz_verilen_tarih ASC, os.id DESC
             LIMIT 100'
        );
        return $stmt->fetchAll();
    }

    public static function ekle(array $veri): int
    {
        $paket = Paket::idIleBul((int) $veri['paket_id']);
        if (!$paket) {
            throw new \RuntimeException('Paket bulunamadi.');
        }

        $stmt = self::db()->prepare(
            'INSERT INTO odeme_sozleri
             (ogrenci_id, veli_id, paket_id, soz_verilen_tutar, soz_verilen_tarih, hatirlatma_tarihi, durum, aciklama, olusturan_kullanici_id, olusturulma_tarihi)
             VALUES
             (:ogrenci_id, :veli_id, :paket_id, :soz_verilen_tutar, :soz_verilen_tarih, :hatirlatma_tarihi, "bekleniyor", :aciklama, :olusturan_kullanici_id, NOW())'
        );
        $stmt->execute([
            'ogrenci_id' => (int) $paket['ogrenci_id'],
            'veli_id' => $veri['veli_id'] ?: null,
            'paket_id' => $veri['paket_id'],
            'soz_verilen_tutar' => (float) $veri['soz_verilen_tutar'],
            'soz_verilen_tarih' => $veri['soz_verilen_tarih'],
            'hatirlatma_tarihi' => $veri['hatirlatma_tarihi'] ?: null,
            'aciklama' => $veri['aciklama'] ?: null,
            'olusturan_kullanici_id' => $veri['olusturan_kullanici_id'],
        ]);
        return (int) self::db()->lastInsertId();
    }
}
