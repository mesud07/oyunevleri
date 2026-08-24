<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Model;
final class Veli extends Model
{
    public static function liste(): array
    {
        self::iletisimReferansiKolonunuHazirla();
        $stmt = self::db()->prepare(
            'SELECT id, ad, soyad, telefon, eposta, yakinlik, iletisim_referansi, olusturulma_tarihi
             FROM veliler
             WHERE kurum_id = :kurum_id
             ORDER BY olusturulma_tarihi DESC, id DESC
             LIMIT 100'
        );
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function ekle(array $veri): int
    {
        self::iletisimReferansiKolonunuHazirla();
        $mevcutId = self::telefonIleId((string) $veri['telefon']);
        if ($mevcutId > 0) {
            $stmt = self::db()->prepare(
                'UPDATE veliler
                 SET ad = COALESCE(NULLIF(:ad, ""), ad),
                     soyad = COALESCE(NULLIF(:soyad, ""), soyad),
                     eposta = COALESCE(NULLIF(:eposta, ""), eposta),
                     yakinlik = COALESCE(NULLIF(:yakinlik, ""), yakinlik),
                     adres = COALESCE(NULLIF(:adres, ""), adres),
                     iletisim_referansi = COALESCE(NULLIF(:iletisim_referansi, ""), iletisim_referansi),
                     notlar = COALESCE(NULLIF(:notlar, ""), notlar)
                 WHERE id = :id AND kurum_id = :kurum_id'
            );
            $stmt->execute([
                'id' => $mevcutId,
                'kurum_id' => self::kurumId(),
                'ad' => $veri['ad'],
                'soyad' => $veri['soyad'],
                'eposta' => $veri['eposta'] ?: '',
                'yakinlik' => $veri['yakinlik'] ?: '',
                'adres' => $veri['adres'] ?: '',
                'iletisim_referansi' => $veri['iletisim_referansi'] ?: '',
                'notlar' => $veri['notlar'] ?: '',
            ]);
            return $mevcutId;
        }

        $stmt = self::db()->prepare(
            'INSERT INTO veliler (kurum_id, ad, soyad, telefon, eposta, yakinlik, adres, iletisim_referansi, notlar, olusturulma_tarihi)
             VALUES (:kurum_id, :ad, :soyad, :telefon, :eposta, :yakinlik, :adres, :iletisim_referansi, :notlar, NOW())'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'ad' => $veri['ad'],
            'soyad' => $veri['soyad'],
            'telefon' => $veri['telefon'],
            'eposta' => $veri['eposta'] ?: null,
            'yakinlik' => $veri['yakinlik'] ?: null,
            'adres' => $veri['adres'] ?: null,
            'iletisim_referansi' => $veri['iletisim_referansi'] ?: null,
            'notlar' => $veri['notlar'] ?: null,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function iletisimReferansiKolonunuHazirla(): void
    {
        static $hazir = false;
        if ($hazir) {
            return;
        }

        $stmt = self::db()->prepare(
            'SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = "veliler"
               AND COLUMN_NAME = "iletisim_referansi"'
        );
        $stmt->execute();
        if ((int) $stmt->fetchColumn() === 0) {
            self::db()->exec('ALTER TABLE veliler ADD COLUMN iletisim_referansi VARCHAR(190) NULL AFTER adres');
        }
        $hazir = true;
    }

    private static function telefonIleId(string $telefon): int
    {
        $rakamlar = preg_replace('/\D+/', '', $telefon) ?? '';
        if ($rakamlar === '') {
            return 0;
        }
        $rakamlar = str_starts_with($rakamlar, '0') ? substr($rakamlar, 1) : $rakamlar;

        $stmt = self::db()->prepare(
            'SELECT id
             FROM veliler
             WHERE kurum_id = :kurum_id
               AND TRIM(LEADING "0" FROM REGEXP_REPLACE(telefon, "[^0-9]", "")) = :telefon
             LIMIT 1'
        );
        $stmt->execute(['telefon' => $rakamlar, 'kurum_id' => self::kurumId()]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }
}
