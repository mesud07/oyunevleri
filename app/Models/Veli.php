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
            'SELECT v.id, v.ad, v.soyad, v.telefon_ulke, v.telefon, v.yedek_telefon,
                    v.eposta, v.yakinlik, v.il, v.ilce, v.adres, v.iletisim_referansi,
                    v.notlar, v.olusturulma_tarihi,
                    COUNT(DISTINCT o.id) AS ogrenci_sayisi,
                    GROUP_CONCAT(DISTINCT CONCAT(o.ad, " ", o.soyad) ORDER BY o.ad, o.soyad SEPARATOR ", ") AS ogrenciler
             FROM veliler v
             LEFT JOIN ogrenci_velileri ov ON ov.veli_id = v.id AND ov.kurum_id = v.kurum_id
             LEFT JOIN ogrenciler o ON o.id = ov.ogrenci_id AND o.kurum_id = v.kurum_id
             WHERE v.kurum_id = :kurum_id
             GROUP BY v.id
             ORDER BY v.ad ASC, v.soyad ASC, v.id DESC
             LIMIT 1000'
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
                     telefon_ulke = COALESCE(NULLIF(:telefon_ulke, ""), telefon_ulke),
                     yedek_telefon = COALESCE(NULLIF(:yedek_telefon, ""), yedek_telefon),
                     eposta = COALESCE(NULLIF(:eposta, ""), eposta),
                     yakinlik = COALESCE(NULLIF(:yakinlik, ""), yakinlik),
                     il = COALESCE(NULLIF(:il, ""), il),
                     ilce = COALESCE(NULLIF(:ilce, ""), ilce),
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
                'telefon_ulke' => $veri['telefon_ulke'] ?: '',
                'yedek_telefon' => $veri['yedek_telefon'] ?: '',
                'eposta' => $veri['eposta'] ?: '',
                'yakinlik' => $veri['yakinlik'] ?: '',
                'il' => $veri['il'] ?: '',
                'ilce' => $veri['ilce'] ?: '',
                'adres' => $veri['adres'] ?: '',
                'iletisim_referansi' => $veri['iletisim_referansi'] ?: '',
                'notlar' => $veri['notlar'] ?: '',
            ]);
            return $mevcutId;
        }

        $stmt = self::db()->prepare(
            'INSERT INTO veliler
                (kurum_id, ad, soyad, telefon_ulke, telefon, yedek_telefon, eposta, yakinlik, il, ilce, adres, iletisim_referansi, notlar, olusturulma_tarihi)
             VALUES
                (:kurum_id, :ad, :soyad, :telefon_ulke, :telefon, :yedek_telefon, :eposta, :yakinlik, :il, :ilce, :adres, :iletisim_referansi, :notlar, NOW())'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'ad' => $veri['ad'],
            'soyad' => $veri['soyad'],
            'telefon_ulke' => $veri['telefon_ulke'] ?: null,
            'telefon' => $veri['telefon'],
            'yedek_telefon' => $veri['yedek_telefon'] ?: null,
            'eposta' => $veri['eposta'] ?: null,
            'yakinlik' => $veri['yakinlik'] ?: null,
            'il' => $veri['il'] ?: null,
            'ilce' => $veri['ilce'] ?: null,
            'adres' => $veri['adres'] ?: null,
            'iletisim_referansi' => $veri['iletisim_referansi'] ?: null,
            'notlar' => $veri['notlar'] ?: null,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function guncelle(int $id, array $veri): bool
    {
        self::iletisimReferansiKolonunuHazirla();
        $cakisanId = self::telefonIleId((string) $veri['telefon'], $id);
        if ($cakisanId > 0) {
            throw new \RuntimeException('Bu telefon numarası başka bir veli kaydında kullanılıyor.');
        }

        $stmt = self::db()->prepare(
            'UPDATE veliler
             SET ad = :ad, soyad = :soyad, telefon_ulke = :telefon_ulke,
                 telefon = :telefon, yedek_telefon = :yedek_telefon,
                 eposta = :eposta, yakinlik = :yakinlik, il = :il, ilce = :ilce,
                 adres = :adres, iletisim_referansi = :iletisim_referansi, notlar = :notlar
             WHERE id = :id AND kurum_id = :kurum_id'
        );
        $stmt->execute([
            'id' => $id,
            'kurum_id' => self::kurumId(),
            'ad' => $veri['ad'],
            'soyad' => $veri['soyad'],
            'telefon_ulke' => $veri['telefon_ulke'] ?: null,
            'telefon' => $veri['telefon'],
            'yedek_telefon' => $veri['yedek_telefon'] ?: null,
            'eposta' => $veri['eposta'] ?: null,
            'yakinlik' => $veri['yakinlik'] ?: null,
            'il' => $veri['il'] ?: null,
            'ilce' => $veri['ilce'] ?: null,
            'adres' => $veri['adres'] ?: null,
            'iletisim_referansi' => $veri['iletisim_referansi'] ?: null,
            'notlar' => $veri['notlar'] ?: null,
        ]);
        return $stmt->rowCount() > 0 || self::idKurumdaVar($id);
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

    private static function telefonIleId(string $telefon, int $haricId = 0): int
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
               AND id <> :haric_id
             LIMIT 1'
        );
        $stmt->execute(['telefon' => $rakamlar, 'kurum_id' => self::kurumId(), 'haric_id' => $haricId]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private static function idKurumdaVar(int $id): bool
    {
        $stmt = self::db()->prepare('SELECT 1 FROM veliler WHERE id = :id AND kurum_id = :kurum_id');
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        return (bool) $stmt->fetchColumn();
    }
}
