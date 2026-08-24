<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class OnamFormu extends Model
{
    public const SABLON_GORSEL_ICERIK = 'gorsel_icerik_kullanim';
    public const FORM_ADI = 'Görsel İçerik Kullanım Onam Formu';

    public static function ogrenciListesi(int $ogrenciId): array
    {
        $stmt = self::db()->prepare(
            'SELECT ofr.id, ofr.form_adi, ofr.belge_turu, ofr.durum, ofr.form_tarihi,
                    ofr.olusturulma_tarihi, ofr.personel_ad_soyad,
                    CONCAT(COALESCE(k.ad, ""), " ", COALESCE(k.soyad, "")) AS olusturan
             FROM ogrenci_onam_formlari ofr
             LEFT JOIN kullanicilar k ON k.id = ofr.olusturan_kullanici_id
             WHERE ofr.kurum_id = :kurum_id AND ofr.ogrenci_id = :ogrenci_id
             ORDER BY ofr.olusturulma_tarihi DESC, ofr.id DESC'
        );
        $stmt->execute(['kurum_id' => self::kurumId(), 'ogrenci_id' => $ogrenciId]);
        return $stmt->fetchAll();
    }

    public static function olustur(array $veri): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO ogrenci_onam_formlari (
                kurum_id, ogrenci_id, veli_id, sablon_kodu, form_adi, belge_turu, durum,
                ogrenci_ad_soyad, ogrenci_tc_kimlik_no, ogrenci_dogum_tarihi, ogrenci_telefon,
                veli_ad_soyad, veli_tc_kimlik_no, veli_yakinlik,
                personel_unvan, personel_ad_soyad, form_tarihi, olusturan_kullanici_id,
                olusturulma_tarihi
             ) VALUES (
                :kurum_id, :ogrenci_id, :veli_id, :sablon_kodu, :form_adi, :belge_turu, "olusturuldu",
                :ogrenci_ad_soyad, :ogrenci_tc_kimlik_no, :ogrenci_dogum_tarihi, :ogrenci_telefon,
                :veli_ad_soyad, :veli_tc_kimlik_no, :veli_yakinlik,
                :personel_unvan, :personel_ad_soyad, :form_tarihi, :olusturan_kullanici_id,
                NOW()
             )'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'ogrenci_id' => (int) $veri['ogrenci_id'],
            'veli_id' => !empty($veri['veli_id']) ? (int) $veri['veli_id'] : null,
            'sablon_kodu' => self::SABLON_GORSEL_ICERIK,
            'form_adi' => self::FORM_ADI,
            'belge_turu' => 'fiziksel',
            'ogrenci_ad_soyad' => $veri['ogrenci_ad_soyad'],
            'ogrenci_tc_kimlik_no' => $veri['ogrenci_tc_kimlik_no'] ?: null,
            'ogrenci_dogum_tarihi' => $veri['ogrenci_dogum_tarihi'] ?: null,
            'ogrenci_telefon' => $veri['ogrenci_telefon'] ?: null,
            'veli_ad_soyad' => $veri['veli_ad_soyad'],
            'veli_tc_kimlik_no' => $veri['veli_tc_kimlik_no'] ?: null,
            'veli_yakinlik' => $veri['veli_yakinlik'] ?: null,
            'personel_unvan' => $veri['personel_unvan'],
            'personel_ad_soyad' => $veri['personel_ad_soyad'],
            'form_tarihi' => $veri['form_tarihi'],
            'olusturan_kullanici_id' => !empty($veri['olusturan_kullanici_id']) ? (int) $veri['olusturan_kullanici_id'] : null,
        ]);

        return (int) self::db()->lastInsertId();
    }

    public static function bul(int $id): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT ofr.*, k.ad AS kurum_adi, k.kod AS kurum_kodu, k.logo_yolu AS kurum_logo_yolu
             FROM ogrenci_onam_formlari ofr
             INNER JOIN kurumlar k ON k.id = ofr.kurum_id
             WHERE ofr.id = :id AND ofr.kurum_id = :kurum_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $form = $stmt->fetch();
        return $form ?: null;
    }
}
