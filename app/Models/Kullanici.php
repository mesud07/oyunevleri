<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Kullanici extends Model
{
    public static function liste(): array
    {
        $stmt = self::db()->prepare(
             'SELECT k.id, k.ad, k.soyad, k.eposta, k.telefon, k.aktif, k.sistem_yoneticisi, k.son_giris_tarihi,
                    k.kurum_id, ku.ad AS kurum_adi, ku.kod AS kurum_kodu,
                    r.id AS rol_id, r.kod AS rol_kodu, r.ad AS rol_adi
             FROM kullanicilar k
             INNER JOIN kurumlar ku ON ku.id = k.kurum_id
             INNER JOIN roller r ON r.id = k.rol_id
             WHERE k.kurum_id = :kurum_id
             ORDER BY k.aktif DESC, k.ad ASC, k.soyad ASC'
        );
        $stmt->execute(['kurum_id' => self::kurumId()]);
        return $stmt->fetchAll();
    }

    public static function roller(): array
    {
        return self::db()->query(
            'SELECT id, kod, ad
             FROM roller
             ORDER BY FIELD(kod, "kurucu", "yonetici", "resepsiyon", "ogretmen", "muhasebe"), ad ASC'
        )->fetchAll();
    }

    public static function rollerDetayli(): array
    {
        $roller = self::db()->query(
            'SELECT r.id, r.kod, r.ad,
                    GROUP_CONCAT(ry.yetki ORDER BY ry.yetki SEPARATOR ",") AS yetkiler
             FROM roller r
             LEFT JOIN rol_yetkileri ry ON ry.rol_id = r.id
             GROUP BY r.id
             ORDER BY FIELD(r.kod, "kurucu", "yonetici", "resepsiyon", "ogretmen", "muhasebe"), r.ad ASC'
        )->fetchAll();

        return array_map(static function (array $rol): array {
            $rol['yetkiler'] = $rol['yetkiler'] ? explode(',', (string) $rol['yetkiler']) : [];
            return $rol;
        }, $roller);
    }

    public static function yetkiSecenekleri(): array
    {
        return [
            ['kod' => 'ogrenci_listele', 'ad' => 'Ogrenciler menusu ve ogrenci listesi', 'grup' => 'Ogrenci', 'kategori' => 'menu', 'bolum' => 'Ogrenci Islemleri', 'aciklama' => 'Ogrenciler ekranini ve ogrenci profillerini gorebilir.'],
            ['kod' => 'veli_listele', 'ad' => 'Veliler / bagli veli bilgileri', 'grup' => 'Ogrenci', 'kategori' => 'menu', 'bolum' => 'Ogrenci Islemleri', 'aciklama' => 'Veli bilgilerini ve bagli iletisim alanlarini gorebilir.'],
            ['kod' => 'ogrenci_ekle', 'ad' => 'Ogrenci ekle, duzenle, sil', 'grup' => 'Ogrenci', 'kategori' => 'islem', 'bolum' => 'Ogrenci Islemleri', 'aciklama' => 'Yeni ogrenci ekleme ve mevcut ogrenci duzenleme yetkisi verir.'],
            ['kod' => 'veli_ekle', 'ad' => 'Veli ekle, duzenle', 'grup' => 'Ogrenci', 'kategori' => 'islem', 'bolum' => 'Ogrenci Islemleri', 'aciklama' => 'Veli ekleme ve guncelleme islemlerini acar.'],

            ['kod' => 'grup_listele', 'ad' => 'Program / gruplar menusu', 'grup' => 'Gruplar', 'kategori' => 'menu', 'bolum' => 'Program', 'aciklama' => 'Haftalik program ve grup listelerini gorur.'],
            ['kod' => 'randevu_listele', 'ad' => 'Randevular menusu', 'grup' => 'Randevu', 'kategori' => 'menu', 'bolum' => 'Program', 'aciklama' => 'Randevu takvimi ve liste ekranlarini acabilir.'],
            ['kod' => 'grup_ekle', 'ad' => 'Grup programi ve atama yonet', 'grup' => 'Gruplar', 'kategori' => 'islem', 'bolum' => 'Program', 'aciklama' => 'Grup olusturma, program duzenleme ve ogrenci atama islemleri.'],
            ['kod' => 'randevu_ekle', 'ad' => 'Randevu ekle, duzenle, sil', 'grup' => 'Randevu', 'kategori' => 'islem', 'bolum' => 'Program', 'aciklama' => 'Yeni randevu olusturma ve mevcut randevulari duzenleme yetkisi.'],
            ['kod' => 'randevu_durum_degistir', 'ad' => 'Randevu durumunu degistir', 'grup' => 'Randevu', 'kategori' => 'islem', 'bolum' => 'Program', 'aciklama' => 'Planlandi, geldi, gelmedi gibi durum guncelleme yetkisi.'],

            ['kod' => 'paket_listele', 'ad' => 'Finans menusu / paketler', 'grup' => 'Hizmet', 'kategori' => 'menu', 'bolum' => 'Finans', 'aciklama' => 'Paketler ve finans alt ekranlarini gorur.'],
            ['kod' => 'odeme_listele', 'ad' => 'Tahsilat, gider, kasa ekranlari', 'grup' => 'Finans', 'kategori' => 'menu', 'bolum' => 'Finans', 'aciklama' => 'Tahsilat listesi, giderler ve kasa ekranlarini acabilir.'],
            ['kod' => 'rapor_ozet', 'ad' => 'Raporlar ve finans analizleri', 'grup' => 'Rapor', 'kategori' => 'menu', 'bolum' => 'Finans', 'aciklama' => 'Raporlar ve gelir gider analiz sayfalarini gorur.'],
            ['kod' => 'paket_ekle', 'ad' => 'Paket ekle, duzenle', 'grup' => 'Hizmet', 'kategori' => 'islem', 'bolum' => 'Finans', 'aciklama' => 'Paket olusturma ve paket bilgilerini duzenleme yetkisi.'],
            ['kod' => 'odeme_ekle', 'ad' => 'Tahsilat, gider ve kasa islemleri', 'grup' => 'Finans', 'kategori' => 'islem', 'bolum' => 'Finans', 'aciklama' => 'Tahsilat yapma, gider ekleme ve kasa hareketleri olusturma.'],

            ['kod' => 'tema_yonet', 'ad' => 'Icerik ve Takip menusu', 'grup' => 'Tema', 'kategori' => 'menu', 'bolum' => 'Icerik ve Takip', 'aciklama' => 'Haftalik temalar ve ilgili icerik ekranlarini acabilir.'],
            ['kod' => 'yoklama_listele', 'ad' => 'Gunluk kayitlar / yoklama goruntuleme', 'grup' => 'Yoklama', 'kategori' => 'menu', 'bolum' => 'Icerik ve Takip', 'aciklama' => 'Gunluk kayit ve yoklama liste ekranlarini gorebilir.'],

            ['kod' => 'sms_goruntule', 'ad' => 'SMS Yonetimi menusu', 'grup' => 'SMS', 'kategori' => 'menu', 'bolum' => 'SMS', 'aciklama' => 'SMS kayitlari ve gonderim ekranlarini gorebilir.'],
            ['kod' => 'sms_rapor_goruntule', 'ad' => 'SMS Raporlari menusu', 'grup' => 'SMS', 'kategori' => 'menu', 'bolum' => 'SMS', 'aciklama' => 'SMS rapor ekranlarini gorebilir.'],
            ['kod' => 'sms_gonder', 'ad' => 'Tekli SMS gonder, iptal et', 'grup' => 'SMS', 'kategori' => 'islem', 'bolum' => 'SMS', 'aciklama' => 'Tekli SMS gonderme ve iptal etme yetkisi.'],
            ['kod' => 'sms_toplu_gonder', 'ad' => 'Toplu SMS gonder', 'grup' => 'SMS', 'kategori' => 'islem', 'bolum' => 'SMS', 'aciklama' => 'Toplu SMS olusturma ve kuyruga ekleme.'],
            ['kod' => 'sms_tekrar_gonder', 'ad' => 'SMS tekrar gonder', 'grup' => 'SMS', 'kategori' => 'islem', 'bolum' => 'SMS', 'aciklama' => 'Basarisiz veya bekleyen SMSleri tekrar gonderebilir.'],
            ['kod' => 'sms_sablon_yonet', 'ad' => 'SMS sablonlarini yonet', 'grup' => 'SMS', 'kategori' => 'islem', 'bolum' => 'SMS', 'aciklama' => 'Sablon ekleme, duzenleme ve onay sureci.'],
            ['kod' => 'sms_ayar_yonet', 'ad' => 'SMS ayarlarina erisim', 'grup' => 'SMS', 'kategori' => 'islem', 'bolum' => 'SMS', 'aciklama' => 'NetGSM ayarlari ve otomasyon ayarlari uzerinde degisiklik yapabilir.'],

            ['kod' => 'kullanici_yonet', 'ad' => 'Yonetim menusu ve kullanici yetkileri', 'grup' => 'Yonetim', 'kategori' => 'menu', 'bolum' => 'Yonetim', 'aciklama' => 'Kullanicilar, roller ve yetki tanim ekranlarina erisim verir.'],
        ];
    }

    public static function rolKaydet(int $id, array $veri): int
    {
        $db = self::db();
        $yetkiKodlari = array_column(self::yetkiSecenekleri(), 'kod');
        $yetkiler = array_values(array_intersect($yetkiKodlari, array_map('strval', $veri['yetkiler'] ?? [])));
        try {
            $db->beginTransaction();
            if ($id > 0) {
                $stmt = $db->prepare('UPDATE roller SET ad = :ad WHERE id = :id');
                $stmt->execute(['id' => $id, 'ad' => $veri['ad']]);
                $rolId = $id;
            } else {
                $stmt = $db->prepare('INSERT INTO roller (kod, ad, olusturulma_tarihi) VALUES (:kod, :ad, NOW())');
                $stmt->execute(['kod' => $veri['kod'], 'ad' => $veri['ad']]);
                $rolId = (int) $db->lastInsertId();
            }

            $sil = $db->prepare('DELETE FROM rol_yetkileri WHERE rol_id = :rol_id');
            $sil->execute(['rol_id' => $rolId]);

            $ekle = $db->prepare('INSERT INTO rol_yetkileri (rol_id, yetki, olusturulma_tarihi) VALUES (:rol_id, :yetki, NOW())');
            foreach ($yetkiler as $yetki) {
                $ekle->execute(['rol_id' => $rolId, 'yetki' => $yetki]);
            }
            $db->commit();
            return $rolId;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function rolKoduVarMi(string $kod, int $haricId = 0): bool
    {
        $stmt = self::db()->prepare('SELECT 1 FROM roller WHERE kod = :kod AND id <> :id LIMIT 1');
        $stmt->execute(['kod' => $kod, 'id' => $haricId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function ekle(array $veri): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO kullanicilar (kurum_id, rol_id, ad, soyad, eposta, telefon, sifre, aktif, olusturulma_tarihi)
             VALUES (:kurum_id, :rol_id, :ad, :soyad, :eposta, :telefon, :sifre, :aktif, NOW())'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'rol_id' => (int) $veri['rol_id'],
            'ad' => $veri['ad'],
            'soyad' => $veri['soyad'],
            'eposta' => $veri['eposta'],
            'telefon' => $veri['telefon'] ?: null,
            'sifre' => password_hash((string) $veri['sifre'], PASSWORD_DEFAULT),
            'aktif' => (int) ($veri['aktif'] ?? 1),
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function guncelle(int $id, array $veri): bool
    {
        $alanlar = [
            'rol_id = :rol_id',
            'ad = :ad',
            'soyad = :soyad',
            'eposta = :eposta',
            'telefon = :telefon',
            'aktif = :aktif',
        ];
        $params = [
            'id' => $id,
            'rol_id' => (int) $veri['rol_id'],
            'ad' => $veri['ad'],
            'soyad' => $veri['soyad'],
            'eposta' => $veri['eposta'],
            'telefon' => $veri['telefon'] ?: null,
            'aktif' => (int) ($veri['aktif'] ?? 1),
        ];
        if (!empty($veri['sifre'])) {
            $alanlar[] = 'sifre = :sifre';
            $params['sifre'] = password_hash((string) $veri['sifre'], PASSWORD_DEFAULT);
        }

        $stmt = self::db()->prepare('UPDATE kullanicilar SET ' . implode(', ', $alanlar) . ' WHERE id = :id AND kurum_id = :kurum_id');
        $params['kurum_id'] = self::kurumId();
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public static function epostaVarMi(string $eposta, int $haricId = 0): bool
    {
        $stmt = self::db()->prepare(
            'SELECT 1 FROM kullanicilar WHERE kurum_id = :kurum_id AND eposta = :eposta AND id <> :id LIMIT 1'
        );
        $stmt->execute(['kurum_id' => self::kurumId(), 'eposta' => $eposta, 'id' => $haricId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function rolVarMi(int $rolId): bool
    {
        $stmt = self::db()->prepare('SELECT 1 FROM roller WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $rolId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function epostaIleBul(string $eposta, string $kurumKodu = 'TALYA'): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT k.*, ku.ad AS kurum_adi, ku.kod AS kurum_kodu, r.kod AS rol_kodu, r.ad AS rol_adi
             FROM kullanicilar k
             INNER JOIN kurumlar ku ON ku.id = k.kurum_id
             INNER JOIN roller r ON r.id = k.rol_id
             WHERE ku.kod = :kurum_kodu
               AND ku.aktif = 1
               AND k.eposta = :eposta
               AND k.aktif = 1
             LIMIT 1'
        );
        $stmt->execute([
            'kurum_kodu' => strtoupper(trim($kurumKodu) !== '' ? trim($kurumKodu) : 'TALYA'),
            'eposta' => $eposta,
        ]);
        $kullanici = $stmt->fetch();
        return $kullanici ?: null;
    }

    public static function idIleBul(int $id): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT k.id, k.kurum_id, k.ad, k.soyad, k.eposta, k.telefon, k.aktif, k.sistem_yoneticisi,
                    ku.ad AS kurum_adi, ku.kod AS kurum_kodu,
                    r.kod AS rol_kodu, r.ad AS rol_adi
             FROM kullanicilar k
             INNER JOIN kurumlar ku ON ku.id = k.kurum_id
             INNER JOIN roller r ON r.id = k.rol_id
             WHERE k.id = :id AND k.aktif = 1 AND ku.aktif = 1
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $kullanici = $stmt->fetch();
        return $kullanici ?: null;
    }

    public static function sonGirisGuncelle(int $id): void
    {
        $stmt = self::db()->prepare(
            'UPDATE kullanicilar SET son_giris_tarihi = NOW() WHERE id = :id AND kurum_id = :kurum_id'
        );
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
    }
}
