<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class BekleyenVeli extends Model
{
    private static bool $schemaHazir = false;

    public static function liste(): array
    {
        self::ensureSchema();

        $stmt = self::db()->prepare(
            'SELECT id, ogrenci_id, ogrenci_ad_soyad, ogrenci_dogum_tarihi, veli_ad_soyad, veli_telefon, veli_eposta,
                    beklenen_gun, ay_grubu, zaman_tercihi, durum, next_follow_up_at, next_action_type,
                    next_action_note, notlar, olusturulma_tarihi, guncellenme_tarihi,
                    GREATEST(0, DATEDIFF(CURDATE(), DATE(olusturulma_tarihi))) AS listeye_ekleneli_gun,
                    (SELECT COUNT(*) FROM bekleyen_veli_gorusmeleri bg
                     WHERE bg.bekleyen_veli_id = bekleyen_veliler.id AND bg.kurum_id = bekleyen_veliler.kurum_id) AS gorusme_sayisi,
                    (SELECT COUNT(*) FROM bekleyen_veli_gorusmeleri bg
                     WHERE bg.bekleyen_veli_id = bekleyen_veliler.id AND bg.kurum_id = bekleyen_veliler.kurum_id
                       AND bg.kanal <> "diger") AS iletisim_sayisi,
                    (SELECT bg.ozet FROM bekleyen_veli_gorusmeleri bg
                     WHERE bg.bekleyen_veli_id = bekleyen_veliler.id AND bg.kurum_id = bekleyen_veliler.kurum_id
                       AND bg.kanal <> "diger"
                     ORDER BY bg.gorusme_tarihi DESC, bg.id DESC LIMIT 1) AS son_gorusme_ozeti,
                    (SELECT bg.gorusme_tarihi FROM bekleyen_veli_gorusmeleri bg
                     WHERE bg.bekleyen_veli_id = bekleyen_veliler.id AND bg.kurum_id = bekleyen_veliler.kurum_id
                       AND bg.kanal <> "diger"
                     ORDER BY bg.gorusme_tarihi DESC, bg.id DESC LIMIT 1) AS son_gorusme_tarihi,
                    (SELECT bg.sonuc FROM bekleyen_veli_gorusmeleri bg
                     WHERE bg.bekleyen_veli_id = bekleyen_veliler.id AND bg.kurum_id = bekleyen_veliler.kurum_id
                       AND bg.kanal <> "diger"
                     ORDER BY bg.gorusme_tarihi DESC, bg.id DESC LIMIT 1) AS son_gorusme_sonucu,
                    DATE(next_follow_up_at) AS sonraki_takip_tarihi,
                    CASE
                      WHEN next_follow_up_at IS NULL THEN "takip_yok"
                      WHEN next_follow_up_at < NOW() THEN "gecikmis"
                      WHEN DATE(next_follow_up_at) = CURDATE() THEN "bugun"
                      WHEN DATE(next_follow_up_at) = DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN "yarin"
                      ELSE "ileri_tarih"
                    END AS takip_durumu
             FROM bekleyen_veliler
             WHERE kurum_id = :kurum_id
             ORDER BY CASE
                        WHEN next_follow_up_at IS NOT NULL AND next_follow_up_at < NOW() THEN 0
                        WHEN next_follow_up_at IS NOT NULL AND DATE(next_follow_up_at) = CURDATE() THEN 1
                        ELSE 2
                      END,
                      FIELD(durum, "tekrar_aranacak", "yeni_talep", "ilk_gorusme_yapilacak", "bekliyor", "bilgi_verildi", "uygun_grup_bekliyor", "veli_donusu_bekleniyor", "kayit_olmaya_hazir"),
                      olusturulma_tarihi DESC,
                      id DESC
             LIMIT 300'
        );
        $stmt->execute(self::kurumParam());

        return self::guncelRandevulariEkle(
            self::uygunKontenjanlariEkle(self::gruplariEkle(self::ayYaslariniEkle($stmt->fetchAll())))
        );
    }

    public static function ekle(array $veri): int
    {
        self::ensureSchema();

        $stmt = self::db()->prepare(
            'INSERT INTO bekleyen_veliler
                (kurum_id, ogrenci_ad_soyad, ogrenci_dogum_tarihi, veli_ad_soyad, veli_telefon, veli_eposta,
                 beklenen_gun, ay_grubu, zaman_tercihi, durum, notlar, olusturan_kullanici_id, olusturulma_tarihi)
             VALUES
                (:kurum_id, :ogrenci_ad_soyad, :ogrenci_dogum_tarihi, :veli_ad_soyad, :veli_telefon, :veli_eposta,
                 :beklenen_gun, :ay_grubu, :zaman_tercihi, "yeni_talep", :notlar, :olusturan_kullanici_id, NOW())'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'ogrenci_ad_soyad' => $veri['ogrenci_ad_soyad'],
            'ogrenci_dogum_tarihi' => $veri['ogrenci_dogum_tarihi'] ?: null,
            'veli_ad_soyad' => $veri['veli_ad_soyad'],
            'veli_telefon' => $veri['veli_telefon'],
            'veli_eposta' => $veri['veli_eposta'] ?: null,
            'beklenen_gun' => $veri['beklenen_gun'] ?: null,
            'ay_grubu' => $veri['ay_grubu'] ?: null,
            'zaman_tercihi' => $veri['zaman_tercihi'] ?: 'farketmez',
            'notlar' => $veri['notlar'] ?: null,
            'olusturan_kullanici_id' => $veri['olusturan_kullanici_id'] ?: null,
        ]);

        $id = (int) self::db()->lastInsertId();
        self::tarihceEkle($id, 'diger', 'Bekleme listesine eklendi.', 'diger', null, (int) ($veri['olusturan_kullanici_id'] ?? 0));
        return $id;
    }

    public static function ogrencidenEkle(int $ogrenciId, int $kullaniciId = 0): array
    {
        self::ensureSchema();
        $db = self::db();
        $db->beginTransaction();

        try {
            $ogrenciStmt = $db->prepare(
                'SELECT id, ad, soyad, dogum_tarihi, durum
                 FROM ogrenciler
                 WHERE id = :id AND kurum_id = :kurum_id
                 LIMIT 1
                 FOR UPDATE'
            );
            $ogrenciStmt->execute(['id' => $ogrenciId, 'kurum_id' => self::kurumId()]);
            $ogrenci = $ogrenciStmt->fetch();
            if (!$ogrenci) {
                throw new \DomainException('Öğrenci bulunamadı.');
            }
            if ((string) ($ogrenci['durum'] ?? '') !== 'pasif') {
                throw new \DomainException('Yalnızca ayrılmış (pasif) öğrenciler bekleyen veli listesine eklenebilir.');
            }

            $mevcutStmt = $db->prepare(
                'SELECT id
                 FROM bekleyen_veliler
                 WHERE kurum_id = :kurum_id
                   AND ogrenci_id = :ogrenci_id
                   AND durum NOT IN ("kayda_donustu", "iptal")
                   AND durum NOT IN ("kayit_oldu", "vazgecti", "ulasilamadi", "katilmadi", "yas_uygun_degil", "saatler_uymadi", "diger")
                 ORDER BY id DESC
                 LIMIT 1'
            );
            $mevcutStmt->execute(['kurum_id' => self::kurumId(), 'ogrenci_id' => $ogrenciId]);
            $mevcutId = (int) ($mevcutStmt->fetchColumn() ?: 0);
            if ($mevcutId > 0) {
                $db->commit();
                return ['id' => $mevcutId, 'yeni' => false];
            }

            $veliStmt = $db->prepare(
                'SELECT v.ad, v.soyad, v.telefon, v.eposta
                 FROM ogrenci_velileri ov
                 INNER JOIN veliler v ON v.id = ov.veli_id AND v.kurum_id = ov.kurum_id
                 WHERE ov.ogrenci_id = :ogrenci_id AND ov.kurum_id = :kurum_id
                 ORDER BY ov.birincil_mi DESC, ov.id ASC
                 LIMIT 1'
            );
            $veliStmt->execute(['ogrenci_id' => $ogrenciId, 'kurum_id' => self::kurumId()]);
            $veli = $veliStmt->fetch();
            if (!$veli || trim((string) ($veli['telefon'] ?? '')) === '') {
                throw new \DomainException('Öğrencinin telefon numarası bulunan bir velisi olmadan bekleme kaydı oluşturulamaz.');
            }

            $ekle = $db->prepare(
                'INSERT INTO bekleyen_veliler
                    (kurum_id, ogrenci_id, ogrenci_ad_soyad, ogrenci_dogum_tarihi, veli_ad_soyad,
                     veli_telefon, veli_eposta, zaman_tercihi, durum, notlar,
                     olusturan_kullanici_id, olusturulma_tarihi)
                 VALUES
                    (:kurum_id, :ogrenci_id, :ogrenci_ad_soyad, :ogrenci_dogum_tarihi, :veli_ad_soyad,
                     :veli_telefon, :veli_eposta, "farketmez", "yeni_talep", :notlar,
                     :kullanici_id, NOW())'
            );
            $ekle->execute([
                'kurum_id' => self::kurumId(),
                'ogrenci_id' => $ogrenciId,
                'ogrenci_ad_soyad' => trim((string) $ogrenci['ad'] . ' ' . (string) $ogrenci['soyad']),
                'ogrenci_dogum_tarihi' => $ogrenci['dogum_tarihi'] ?: null,
                'veli_ad_soyad' => trim((string) $veli['ad'] . ' ' . (string) $veli['soyad']),
                'veli_telefon' => (string) $veli['telefon'],
                'veli_eposta' => $veli['eposta'] ?: null,
                'notlar' => 'Daha önce ayrılan öğrenci yeniden katılmak istiyor.',
                'kullanici_id' => $kullaniciId ?: null,
            ]);
            $bekleyenVeliId = (int) $db->lastInsertId();

            $sonGrupStmt = $db->prepare(
                'SELECT go.grup_id
                 FROM grup_ogrencileri go
                 INNER JOIN gruplar g ON g.id = go.grup_id AND g.kurum_id = go.kurum_id
                 WHERE go.ogrenci_id = :ogrenci_id
                   AND go.kurum_id = :kurum_id
                   AND g.aktif = 1
                 ORDER BY go.baslangic_tarihi DESC, go.id DESC
                 LIMIT 1'
            );
            $sonGrupStmt->execute(['ogrenci_id' => $ogrenciId, 'kurum_id' => self::kurumId()]);
            $sonGrupId = (int) ($sonGrupStmt->fetchColumn() ?: 0);
            if ($sonGrupId > 0) {
                $grupEkle = $db->prepare(
                    'INSERT INTO bekleyen_veli_gruplari
                        (kurum_id, bekleyen_veli_id, grup_id, olusturan_kullanici_id)
                     VALUES (:kurum_id, :bekleyen_veli_id, :grup_id, :kullanici_id)'
                );
                $grupEkle->execute([
                    'kurum_id' => self::kurumId(),
                    'bekleyen_veli_id' => $bekleyenVeliId,
                    'grup_id' => $sonGrupId,
                    'kullanici_id' => $kullaniciId ?: null,
                ]);
            }

            self::tarihceEkle(
                $bekleyenVeliId,
                'diger',
                'Ayrılmış öğrenci yeniden katılım talebiyle bekleyen veli listesine eklendi.',
                'diger',
                null,
                $kullaniciId
            );
            $db->commit();

            return ['id' => $bekleyenVeliId, 'yeni' => true];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function guncelle(int $id, array $veri, int $kullaniciId = 0): bool
    {
        self::ensureSchema();
        $stmt = self::db()->prepare(
            'UPDATE bekleyen_veliler
             SET ogrenci_ad_soyad = :ogrenci_ad_soyad,
                 ogrenci_dogum_tarihi = :ogrenci_dogum_tarihi,
                 veli_ad_soyad = :veli_ad_soyad,
                 veli_telefon = :veli_telefon,
                 veli_eposta = :veli_eposta,
                 ay_grubu = :ay_grubu,
                 zaman_tercihi = :zaman_tercihi,
                 notlar = :notlar,
                 guncellenme_tarihi = NOW()
             WHERE id = :id AND kurum_id = :kurum_id
               AND durum NOT IN ("kayda_donustu", "kayit_oldu", "iptal", "vazgecti", "ulasilamadi", "katilmadi", "yas_uygun_degil", "saatler_uymadi", "diger")'
        );
        $stmt->execute([
            'ogrenci_ad_soyad' => $veri['ogrenci_ad_soyad'],
            'ogrenci_dogum_tarihi' => $veri['ogrenci_dogum_tarihi'] ?: null,
            'veli_ad_soyad' => $veri['veli_ad_soyad'],
            'veli_telefon' => $veri['veli_telefon'],
            'veli_eposta' => $veri['veli_eposta'] ?: null,
            'ay_grubu' => $veri['ay_grubu'] ?: null,
            'zaman_tercihi' => $veri['zaman_tercihi'],
            'notlar' => $veri['notlar'] ?: null,
            'id' => $id,
            'kurum_id' => self::kurumId(),
        ]);
        if ($stmt->rowCount() < 1 && !self::bul($id)) {
            return false;
        }
        self::tarihceEkle($id, 'diger', 'Bekleyen veli ve öğrenci bilgileri güncellendi.', 'diger', null, $kullaniciId);
        return true;
    }

    public static function durumGuncelle(int $id, string $durum, int $kullaniciId = 0): bool
    {
        self::ensureSchema();

        if (!in_array($durum, self::durumlar(), true)) {
            return false;
        }

        $db = self::db();
        $db->beginTransaction();
        try {
            $bul = $db->prepare('SELECT durum FROM bekleyen_veliler WHERE id = :id AND kurum_id = :kurum_id FOR UPDATE');
            $bul->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
            $eskiDurum = $bul->fetchColumn();
            if ($eskiDurum === false) {
                $db->rollBack();
                return false;
            }
            if ((string) $eskiDurum !== $durum) {
                $stmt = $db->prepare(
                    'UPDATE bekleyen_veliler
                     SET durum = :durum,
                         next_follow_up_at = IF(:kapali = 1, NULL, next_follow_up_at),
                         next_action_type = IF(:kapali_aksiyon = 1, NULL, next_action_type),
                         next_action_note = IF(:kapali_not = 1, NULL, next_action_note),
                         guncellenme_tarihi = NOW()
                     WHERE id = :id AND kurum_id = :kurum_id'
                );
                $kapali = self::kapaliDurumMu($durum) ? 1 : 0;
                $stmt->execute(['id' => $id, 'durum' => $durum, 'kurum_id' => self::kurumId(), 'kapali' => $kapali, 'kapali_aksiyon' => $kapali, 'kapali_not' => $kapali]);
                self::tarihceEkle($id, 'diger', 'Durum değiştirildi: ' . self::durumEtiketi((string) $eskiDurum) . ' → ' . self::durumEtiketi($durum) . '.', self::durumSonucu($durum), null, $kullaniciId);
            }
            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    public static function sil(int $id): bool
    {
        self::ensureSchema();

        $stmt = self::db()->prepare('DELETE FROM bekleyen_veliler WHERE id = :id AND kurum_id = :kurum_id');
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);

        return $stmt->rowCount() > 0;
    }

    public static function bul(int $id): ?array
    {
        self::ensureSchema();

        $stmt = self::db()->prepare(
            'SELECT id, ogrenci_id, ogrenci_ad_soyad, ogrenci_dogum_tarihi, veli_ad_soyad, veli_telefon, veli_eposta,
                    beklenen_gun, ay_grubu, zaman_tercihi, durum, next_follow_up_at, next_action_type,
                    next_action_note, notlar, olusturulma_tarihi, guncellenme_tarihi,
                    GREATEST(0, DATEDIFF(CURDATE(), DATE(olusturulma_tarihi))) AS listeye_ekleneli_gun,
                    (SELECT COUNT(*) FROM bekleyen_veli_gorusmeleri bg
                     WHERE bg.bekleyen_veli_id = bekleyen_veliler.id AND bg.kurum_id = bekleyen_veliler.kurum_id
                       AND bg.kanal <> "diger") AS iletisim_sayisi,
                    (SELECT bg.ozet FROM bekleyen_veli_gorusmeleri bg
                     WHERE bg.bekleyen_veli_id = bekleyen_veliler.id AND bg.kurum_id = bekleyen_veliler.kurum_id
                       AND bg.kanal <> "diger"
                     ORDER BY bg.gorusme_tarihi DESC, bg.id DESC LIMIT 1) AS son_gorusme_ozeti,
                    (SELECT bg.gorusme_tarihi FROM bekleyen_veli_gorusmeleri bg
                     WHERE bg.bekleyen_veli_id = bekleyen_veliler.id AND bg.kurum_id = bekleyen_veliler.kurum_id
                       AND bg.kanal <> "diger"
                     ORDER BY bg.gorusme_tarihi DESC, bg.id DESC LIMIT 1) AS son_gorusme_tarihi,
                    CASE
                      WHEN next_follow_up_at IS NULL THEN "takip_yok"
                      WHEN next_follow_up_at < NOW() THEN "gecikmis"
                      WHEN DATE(next_follow_up_at) = CURDATE() THEN "bugun"
                      WHEN DATE(next_follow_up_at) = DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN "yarin"
                      ELSE "ileri_tarih"
                    END AS takip_durumu
             FROM bekleyen_veliler
             WHERE id = :id
               AND kurum_id = :kurum_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $kayit = $stmt->fetch();

        if (!$kayit) return null;
        $kayit = self::ayYasiniEkle($kayit);
        return self::guncelRandevulariEkle(self::uygunKontenjanlariEkle(self::gruplariEkle([$kayit])))[0];
    }

    public static function gorusmeler(int $id): array
    {
        self::ensureSchema();
        if (!self::bul($id)) {
            return [];
        }
        $stmt = self::db()->prepare(
            'SELECT bg.id, bg.gorusme_tarihi, bg.kanal, bg.ozet, bg.sonuc,
                    bg.sonraki_takip_tarihi, bg.olusturulma_tarihi,
                    COALESCE(CONCAT(k.ad, " ", k.soyad), "-") AS kaydeden
             FROM bekleyen_veli_gorusmeleri bg
             LEFT JOIN kullanicilar k ON k.id = bg.olusturan_kullanici_id AND k.kurum_id = bg.kurum_id
             WHERE bg.bekleyen_veli_id = :id AND bg.kurum_id = :kurum_id
             ORDER BY bg.gorusme_tarihi DESC, bg.id DESC'
        );
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        return $stmt->fetchAll();
    }

    public static function gorusmeEkle(int $id, array $veri): int
    {
        self::ensureSchema();
        if (!self::bul($id)) {
            return 0;
        }
        $db = self::db();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                'INSERT INTO bekleyen_veli_gorusmeleri
                    (kurum_id, bekleyen_veli_id, gorusme_tarihi, kanal, ozet, sonuc,
                     sonraki_takip_tarihi, olusturan_kullanici_id)
                 VALUES
                    (:kurum_id, :bekleyen_veli_id, :gorusme_tarihi, :kanal, :ozet, :sonuc,
                     :sonraki_takip_tarihi, :olusturan_kullanici_id)'
            );
            $stmt->execute([
                'kurum_id' => self::kurumId(),
                'bekleyen_veli_id' => $id,
                'gorusme_tarihi' => $veri['gorusme_tarihi'],
                'kanal' => $veri['kanal'],
                'ozet' => $veri['ozet'],
                'sonuc' => $veri['sonuc'],
                'sonraki_takip_tarihi' => $veri['sonraki_takip_tarihi'] ?: null,
                'olusturan_kullanici_id' => $veri['olusturan_kullanici_id'] ?: null,
            ]);
            $gorusmeId = (int) $db->lastInsertId();
            $yeniDurum = match ((string) $veri['sonuc']) {
                'goruldu' => 'bilgi_verildi',
                'bilgi_verildi' => 'bilgi_verildi',
                'veli_donecek' => 'veli_donusu_bekleniyor',
                'tekrar_aranacak' => 'tekrar_aranacak',
                'kayit_istiyor' => 'kayit_olmaya_hazir',
                'uygun_grup_yok' => 'uygun_grup_bekliyor',
                'ulasilamadi' => 'ulasilamadi',
                'katilmadi' => 'vazgecti',
                default => 'bilgi_verildi',
            };
            $durumStmt = $db->prepare(
                'UPDATE bekleyen_veliler
                 SET durum = IF(durum IN ("kayda_donustu", "kayit_oldu", "iptal", "vazgecti", "ulasilamadi", "katilmadi", "yas_uygun_degil", "saatler_uymadi", "diger"), durum, :durum),
                     next_follow_up_at = :next_follow_up_at,
                     next_action_type = :next_action_type,
                     next_action_note = :next_action_note,
                     guncellenme_tarihi = NOW()
                 WHERE id = :id AND kurum_id = :kurum_id'
            );
            $takipAcik = !self::kapaliDurumMu($yeniDurum);
            $durumStmt->execute([
                'id' => $id,
                'kurum_id' => self::kurumId(),
                'durum' => $yeniDurum,
                'next_follow_up_at' => $takipAcik ? ($veri['next_follow_up_at'] ?: null) : null,
                'next_action_type' => $takipAcik ? ($veri['next_action_type'] ?: null) : null,
                'next_action_note' => $takipAcik ? ($veri['next_action_note'] ?: null) : null,
            ]);
            $db->commit();
            return $gorusmeId;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function ogrenciyeDonustur(int $id, int $kullaniciId = 0): int
    {
        $kayit = self::bul($id);
        if (!$kayit) {
            return 0;
        }

        $mevcutOgrenciId = (int) ($kayit['ogrenci_id'] ?? 0);
        if ($mevcutOgrenciId > 0) {
            self::donusumBaginiGuncelle($id, $mevcutOgrenciId, $kullaniciId);
            return $mevcutOgrenciId;
        }

        [$ogrenciAd, $ogrenciSoyad] = self::adSoyadAyir((string) $kayit['ogrenci_ad_soyad']);
        [$veliAd, $veliSoyad] = self::adSoyadAyir((string) $kayit['veli_ad_soyad']);

        if ($ogrenciAd === '' || $veliAd === '') {
            return 0;
        }

        $notlar = array_filter([
            'Bekleyen veli listesinden aktif ogrenciye aktarildi.',
            $kayit['beklenen_gun'] ? 'Bekledigi gun: ' . $kayit['beklenen_gun'] : '',
            $kayit['ay_grubu'] ? 'Ay grubu: ' . $kayit['ay_grubu'] : '',
            $kayit['zaman_tercihi'] ? 'Zaman tercihi: ' . $kayit['zaman_tercihi'] : '',
            $kayit['notlar'] ? 'Not: ' . $kayit['notlar'] : '',
        ]);

        $ogrenciId = Ogrenci::veliIleEkle([
            'ogrenci' => [
                'ad' => $ogrenciAd,
                'soyad' => $ogrenciSoyad ?: '-',
                'tc_kimlik_no' => '',
                'dogum_tarihi' => (string) ($kayit['ogrenci_dogum_tarihi'] ?? ''),
                'cinsiyet' => 'belirtilmedi',
                'kayit_tarihi' => date('Y-m-d'),
                'acil_durum_kisi' => '',
                'acil_durum_telefon' => '',
                'saglik_bilgisi' => '',
                'alerji_bilgisi' => '',
                'ozel_durum_notu' => implode("\n", $notlar),
                'vasi_ad_soyad' => '',
                'vasi_tc_kimlik_no' => '',
                'vasi_telefon' => '',
                'yonetici_notu' => '',
                'ogretmen_notu' => '',
            ],
            'veli' => [
                'ad' => $veliAd,
                'soyad' => $veliSoyad ?: '-',
                'tc_kimlik_no' => '',
                'telefon_ulke' => 'Turkiye',
                'telefon' => (string) $kayit['veli_telefon'],
                'yedek_telefon' => '',
                'eposta' => (string) ($kayit['veli_eposta'] ?? ''),
                'yakinlik' => '',
                'il' => '',
                'ilce' => '',
                'adres' => '',
                'notlar' => (string) ($kayit['notlar'] ?? ''),
            ],
        ]);

        self::donusumBaginiGuncelle($id, $ogrenciId, $kullaniciId);

        return $ogrenciId;
    }

    public static function takipGuncelle(int $id, array $veri, int $kullaniciId = 0): bool
    {
        self::ensureSchema();
        $kayit = self::bul($id);
        if (!$kayit || self::kapaliDurumMu((string) $kayit['durum']) || !in_array((string) ($veri['next_action_type'] ?? ''), self::aksiyonTipleri(), true)) {
            return false;
        }
        $stmt = self::db()->prepare(
            'UPDATE bekleyen_veliler
             SET next_follow_up_at = :takip, next_action_type = :aksiyon, next_action_note = :not, guncellenme_tarihi = NOW()
             WHERE id = :id AND kurum_id = :kurum_id'
        );
        $stmt->execute([
            'takip' => $veri['next_follow_up_at'],
            'aksiyon' => $veri['next_action_type'],
            'not' => $veri['next_action_note'] ?: null,
            'id' => $id,
            'kurum_id' => self::kurumId(),
        ]);
        self::tarihceEkle($id, 'diger', 'Sonraki aksiyon planlandı: ' . self::aksiyonEtiketi((string) $veri['next_action_type']) . '.', 'diger', substr((string) $veri['next_follow_up_at'], 0, 10), $kullaniciId);
        return true;
    }

    public static function randevuyaBagla(int $id, int $ogrenciId, int $kullaniciId = 0): bool
    {
        if ($id < 1 || $ogrenciId < 1) return false;
        $stmt = self::db()->prepare(
            'UPDATE bekleyen_veliler
             SET ogrenci_id = :ogrenci_id, durum = "kayit_oldu", next_follow_up_at = NULL,
                 next_action_type = NULL, next_action_note = NULL, guncellenme_tarihi = NOW()
             WHERE id = :id AND kurum_id = :kurum_id'
        );
        $stmt->execute(['ogrenci_id' => $ogrenciId, 'id' => $id, 'kurum_id' => self::kurumId()]);
        if ($stmt->rowCount() < 1 && !self::bul($id)) return false;
        self::tarihceEkle($id, 'diger', 'Bekleyen veli için randevu oluşturuldu.', 'diger', null, $kullaniciId);
        return true;
    }

    public static function durumlar(): array
    {
        return [
            'yeni_talep', 'ilk_gorusme_yapilacak', 'bilgi_verildi', 'uygun_grup_bekliyor',
            'veli_donusu_bekleniyor', 'tekrar_aranacak', 'kayit_olmaya_hazir', 'kayit_oldu',
            'vazgecti', 'ulasilamadi', 'yas_uygun_degil', 'saatler_uymadi', 'diger',
            'bekliyor', 'iletisime_gecildi', 'katilmadi', 'kayda_donustu', 'iptal',
        ];
    }

    public static function aksiyonTipleri(): array
    {
        return ['telefonla_ara', 'whatsapp_gonder', 'veli_donusunu_bekle', 'grup_kontrol_et', 'kayit_icin_ara', 'diger'];
    }

    public static function grupSecenekleri(int $id): array
    {
        self::ensureSchema();
        $seciliStmt = self::db()->prepare(
            'SELECT grup_id
             FROM bekleyen_veli_gruplari
             WHERE bekleyen_veli_id = :bekleyen_veli_id AND kurum_id = :kurum_id'
        );
        $seciliStmt->execute(['bekleyen_veli_id' => $id, 'kurum_id' => self::kurumId()]);
        $seciliIdler = array_fill_keys(array_map('intval', $seciliStmt->fetchAll(\PDO::FETCH_COLUMN)), true);

        return array_values(array_map(
            static function (array $grup) use ($seciliIdler): array {
                $grup['secili'] = isset($seciliIdler[(int) $grup['id']]) ? 1 : 0;
                return $grup;
            },
            array_filter(Grup::secenekler(), static fn(array $grup): bool => (int) ($grup['aktif'] ?? 0) === 1)
        ));
    }

    public static function gruplariGuncelle(int $id, array $grupIdleri, int $kullaniciId = 0): bool
    {
        self::ensureSchema();
        if (!self::bul($id)) return false;
        $grupIdleri = array_values(array_unique(array_filter(array_map('intval', $grupIdleri), static fn(int $grupId): bool => $grupId > 0)));
        $db = self::db();
        $db->beginTransaction();
        try {
            $mevcutStmt = $db->prepare('SELECT grup_id FROM bekleyen_veli_gruplari WHERE bekleyen_veli_id = :id AND kurum_id = :kurum_id');
            $mevcutStmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
            $eski = array_values(array_map('intval', $mevcutStmt->fetchAll(\PDO::FETCH_COLUMN)));

            $gecerli = [];
            if ($grupIdleri) {
                $yer = implode(',', array_fill(0, count($grupIdleri), '?'));
                $kontrol = $db->prepare("SELECT id FROM gruplar WHERE kurum_id = ? AND aktif = 1 AND id IN ({$yer})");
                $kontrol->execute([self::kurumId(), ...$grupIdleri]);
                $gecerli = array_values(array_map('intval', $kontrol->fetchAll(\PDO::FETCH_COLUMN)));
            }
            sort($eski); sort($gecerli);
            if ($eski !== $gecerli) {
                $sil = $db->prepare('DELETE FROM bekleyen_veli_gruplari WHERE bekleyen_veli_id = :id AND kurum_id = :kurum_id');
                $sil->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
                $ekle = $db->prepare('INSERT INTO bekleyen_veli_gruplari (kurum_id, bekleyen_veli_id, grup_id, olusturan_kullanici_id) VALUES (:kurum_id, :veli_id, :grup_id, :kullanici_id)');
                foreach ($gecerli as $grupId) {
                    $ekle->execute(['kurum_id' => self::kurumId(), 'veli_id' => $id, 'grup_id' => $grupId, 'kullanici_id' => $kullaniciId ?: null]);
                }
                $adlar = [];
                if ($gecerli) {
                    $yer = implode(',', array_fill(0, count($gecerli), '?'));
                    $adStmt = $db->prepare("SELECT ad FROM gruplar WHERE kurum_id = ? AND id IN ({$yer}) ORDER BY ad");
                    $adStmt->execute([self::kurumId(), ...$gecerli]);
                    $adlar = $adStmt->fetchAll(\PDO::FETCH_COLUMN);
                }
                self::tarihceEkle($id, 'diger', 'Beklenen gruplar güncellendi: ' . ($adlar ? implode(', ', $adlar) : 'Grup seçimi kaldırıldı') . '.', 'diger', null, $kullaniciId);
            }
            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    public static function grubaGoreListe(int $grupId): array
    {
        self::ensureSchema();
        $stmt = self::db()->prepare(
            'SELECT bv.id, bv.ogrenci_ad_soyad, bv.ogrenci_dogum_tarihi, bv.veli_ad_soyad,
                    bv.veli_telefon, bv.durum, bv.notlar,
                    (SELECT COUNT(*) FROM bekleyen_veli_gorusmeleri bg WHERE bg.bekleyen_veli_id = bv.id AND bg.kurum_id = bv.kurum_id) AS gorusme_sayisi,
                    (SELECT bg.ozet FROM bekleyen_veli_gorusmeleri bg WHERE bg.bekleyen_veli_id = bv.id AND bg.kurum_id = bv.kurum_id ORDER BY bg.gorusme_tarihi DESC, bg.id DESC LIMIT 1) AS son_gorusme_ozeti,
                    (SELECT bg.gorusme_tarihi FROM bekleyen_veli_gorusmeleri bg WHERE bg.bekleyen_veli_id = bv.id AND bg.kurum_id = bv.kurum_id ORDER BY bg.gorusme_tarihi DESC, bg.id DESC LIMIT 1) AS son_gorusme_tarihi
             FROM bekleyen_veli_gruplari bvg
             INNER JOIN bekleyen_veliler bv ON bv.id = bvg.bekleyen_veli_id AND bv.kurum_id = bvg.kurum_id
             WHERE bvg.grup_id = :grup_id AND bvg.kurum_id = :kurum_id
               AND bv.durum NOT IN ("kayda_donustu", "kayit_oldu", "iptal", "vazgecti", "ulasilamadi", "katilmadi", "yas_uygun_degil", "saatler_uymadi", "diger")
             ORDER BY FIELD(bv.durum, "bekliyor", "ulasilamadi", "iletisime_gecildi", "bilgi_verildi", "katilmadi"), bv.olusturulma_tarihi ASC'
        );
        $stmt->execute(['grup_id' => $grupId, 'kurum_id' => self::kurumId()]);
        return self::ayYaslariniEkle($stmt->fetchAll());
    }

    private static function donusumBaginiGuncelle(int $id, int $ogrenciId, int $kullaniciId = 0): void
    {
        $stmt = self::db()->prepare(
            'UPDATE bekleyen_veliler
             SET ogrenci_id = :ogrenci_id, durum = "kayit_oldu", next_follow_up_at = NULL,
                 next_action_type = NULL, next_action_note = NULL, guncellenme_tarihi = NOW()
             WHERE id = :id AND kurum_id = :kurum_id'
        );
        $stmt->execute(['id' => $id, 'ogrenci_id' => $ogrenciId, 'kurum_id' => self::kurumId()]);
        self::tarihceEkle($id, 'diger', 'Bekleyen veli aktif öğrenci kaydına dönüştürüldü.', 'diger', null, $kullaniciId);
    }

    private static function adSoyadAyir(string $adSoyad): array
    {
        $parcalar = preg_split('/\s+/', trim($adSoyad)) ?: [];
        $parcalar = array_values(array_filter($parcalar));

        if (count($parcalar) <= 1) {
            return [$parcalar[0] ?? '', ''];
        }

        $soyad = array_pop($parcalar);
        return [implode(' ', $parcalar), $soyad];
    }

    private static function ayYaslariniEkle(array $kayitlar): array
    {
        foreach ($kayitlar as $index => $kayit) {
            $kayitlar[$index] = self::ayYasiniEkle($kayit);
        }

        return $kayitlar;
    }

    private static function ayYasiniEkle(array $kayit): array
    {
        $kayit['ogrenci_ay_yasi'] = self::ayYasi($kayit['ogrenci_dogum_tarihi'] ?? null);

        return $kayit;
    }

    private static function ayYasi(?string $dogumTarihi): ?int
    {
        if (!$dogumTarihi) {
            return null;
        }

        try {
            $dogum = new \DateTimeImmutable($dogumTarihi);
            $bugun = new \DateTimeImmutable(date('Y-m-d'));
        } catch (\Throwable $e) {
            return null;
        }

        if ($dogum > $bugun) {
            return null;
        }

        $ay = (((int) $bugun->format('Y') - (int) $dogum->format('Y')) * 12)
            + ((int) $bugun->format('m') - (int) $dogum->format('m'));

        if ((int) $bugun->format('d') < (int) $dogum->format('d')) {
            $ay--;
        }

        return max(0, $ay);
    }

    private static function gruplariEkle(array $kayitlar): array
    {
        if (!$kayitlar) return $kayitlar;
        $idler = array_values(array_map('intval', array_column($kayitlar, 'id')));
        $yer = implode(',', array_fill(0, count($idler), '?'));
        $stmt = self::db()->prepare(
            "SELECT bvg.bekleyen_veli_id, g.id AS grup_id, g.ad AS grup_adi
             FROM bekleyen_veli_gruplari bvg
             INNER JOIN gruplar g ON g.id = bvg.grup_id AND g.kurum_id = bvg.kurum_id
             WHERE bvg.kurum_id = ? AND bvg.bekleyen_veli_id IN ({$yer})
             ORDER BY g.ad"
        );
        $stmt->execute([self::kurumId(), ...$idler]);
        $gruplar = [];
        foreach ($stmt->fetchAll() as $grup) {
            $gruplar[(int) $grup['bekleyen_veli_id']][] = ['id' => (int) $grup['grup_id'], 'ad' => (string) $grup['grup_adi']];
        }
        foreach ($kayitlar as &$kayit) {
            $kayit['gruplar'] = $gruplar[(int) $kayit['id']] ?? [];
            $kayit['grup_idleri'] = array_column($kayit['gruplar'], 'id');
            $kayit['grup_adlari'] = implode(', ', array_column($kayit['gruplar'], 'ad'));
        }
        unset($kayit);
        return $kayitlar;
    }

    private static function uygunKontenjanlariEkle(array $kayitlar): array
    {
        if (!$kayitlar) return $kayitlar;

        $stmt = self::db()->prepare(
            'SELECT g.id, g.ad, g.yas_araligi, g.kontenjan,
                    COALESCE(doluluk.ogrenci_sayisi, 0) AS ogrenci_sayisi,
                    dp.gun, dp.baslangic_saati, dp.bitis_saati
             FROM gruplar g
             LEFT JOIN ders_programlari dp
               ON dp.grup_id = g.id AND dp.kurum_id = g.kurum_id AND dp.aktif = 1
             LEFT JOIN (
               SELECT grup_id, COUNT(DISTINCT ogrenci_id) AS ogrenci_sayisi
               FROM randevular
               WHERE kurum_id = :kurum_doluluk
                 AND TIMESTAMP(tarih, baslangic_saati) >= NOW()
                 AND TIMESTAMP(tarih, baslangic_saati) < DATE_ADD(NOW(), INTERVAL 7 DAY)
                 AND COALESCE(durum, "planlandi") NOT IN ("iptal", "kurum_iptali")
               GROUP BY grup_id
             ) doluluk ON doluluk.grup_id = g.id
             WHERE g.kurum_id = :kurum_id AND g.aktif = 1
             ORDER BY g.ad, dp.gun, dp.baslangic_saati'
        );
        $stmt->execute(['kurum_doluluk' => self::kurumId(), 'kurum_id' => self::kurumId()]);

        $gunler = [1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe', 5 => 'Cuma', 6 => 'Cumartesi', 7 => 'Pazar'];
        $gruplar = [];
        foreach ($stmt->fetchAll() as $satir) {
            $grupId = (int) $satir['id'];
            if (!isset($gruplar[$grupId])) {
                $kontenjan = (int) ($satir['kontenjan'] ?? 0);
                $ogrenciSayisi = (int) ($satir['ogrenci_sayisi'] ?? 0);
                $gruplar[$grupId] = [
                    'id' => $grupId,
                    'ad' => (string) $satir['ad'],
                    'yas_araligi' => (string) ($satir['yas_araligi'] ?? ''),
                    'kontenjan' => $kontenjan,
                    'ogrenci_sayisi' => $ogrenciSayisi,
                    'bos_kontenjan' => max(0, $kontenjan - $ogrenciSayisi),
                    'programlar' => [],
                ];
            }
            if ($satir['gun'] !== null) {
                $gun = (int) $satir['gun'];
                $gruplar[$grupId]['programlar'][] = [
                    'gun' => $gun,
                    'gun_adi' => $gunler[$gun] ?? '-',
                    'baslangic_saati' => substr((string) $satir['baslangic_saati'], 0, 5),
                    'bitis_saati' => substr((string) $satir['bitis_saati'], 0, 5),
                ];
            }
        }

        foreach ($kayitlar as &$kayit) {
            $secili = array_fill_keys(array_map('intval', $kayit['grup_idleri'] ?? []), true);
            $kayit['gruplar'] = array_values(array_filter(array_map(
                static fn(int $id): ?array => $gruplar[$id] ?? null,
                array_keys($secili)
            )));
            $kayit['grup_adlari'] = implode(', ', array_column($kayit['gruplar'], 'ad'));

            $ayYasi = isset($kayit['ogrenci_ay_yasi']) ? (int) $kayit['ogrenci_ay_yasi'] : null;
            $beklenenGun = mb_strtolower(trim((string) ($kayit['beklenen_gun'] ?? '')), 'UTF-8');
            $zamanTercihi = (string) ($kayit['zaman_tercihi'] ?? 'farketmez');
            $uygun = [];
            foreach ($gruplar as $grup) {
                if ((int) $grup['bos_kontenjan'] < 1) continue;
                $dogrudanSecili = isset($secili[(int) $grup['id']]);
                $yasUyumlu = $ayYasi !== null && self::yasAraliginda($ayYasi, (string) $grup['yas_araligi']);
                if (!$dogrudanSecili && !$yasUyumlu) continue;

                $programlar = $grup['programlar'];
                $gunUyumlu = $beklenenGun === '' || array_filter($programlar, static fn(array $program): bool => str_contains(mb_strtolower((string) $program['gun_adi'], 'UTF-8'), $beklenenGun));
                $zamanUyumlu = $zamanTercihi === 'farketmez' || array_filter($programlar, static function (array $program) use ($zamanTercihi): bool {
                    $haftaSonu = (int) $program['gun'] >= 6;
                    return $zamanTercihi === 'hafta_sonu' ? $haftaSonu : !$haftaSonu;
                });
                if (!$dogrudanSecili && (!$gunUyumlu || !$zamanUyumlu)) continue;
                $grup['dogrudan_secili'] = $dogrudanSecili ? 1 : 0;
                $grup['program_ozeti'] = $programlar
                    ? implode(' · ', array_map(static fn(array $p): string => $p['gun_adi'] . ' ' . $p['baslangic_saati'], $programlar))
                    : 'Program saati tanımlı değil';
                $uygun[] = $grup;
            }
            usort($uygun, static function (array $a, array $b): int {
                $sonuc = (int) $b['dogrudan_secili'] <=> (int) $a['dogrudan_secili'];
                if ($sonuc !== 0) return $sonuc;
                $sonuc = (int) $b['bos_kontenjan'] <=> (int) $a['bos_kontenjan'];
                return $sonuc !== 0 ? $sonuc : strcasecmp((string) $a['ad'], (string) $b['ad']);
            });
            $kayit['uygun_gruplar'] = array_slice($uygun, 0, 8);
            $kayit['uygun_grup_var'] = $uygun ? 1 : 0;
            $kayit['whatsapp_telefon'] = self::whatsappTelefon((string) ($kayit['veli_telefon'] ?? ''));
        }
        unset($kayit);
        return $kayitlar;
    }

    private static function guncelRandevulariEkle(array $kayitlar): array
    {
        if (!$kayitlar) return $kayitlar;

        $ogrenciIdleri = array_values(array_unique(array_filter(
            array_map('intval', array_column($kayitlar, 'ogrenci_id')),
            static fn(int $ogrenciId): bool => $ogrenciId > 0
        )));
        $randevular = [];

        if ($ogrenciIdleri) {
            $yer = implode(',', array_fill(0, count($ogrenciIdleri), '?'));
            $stmt = self::db()->prepare(
                "SELECT r.id, r.ogrenci_id, r.tarih, r.baslangic_saati, r.bitis_saati, r.durum,
                        COALESCE(g.ad, r.tur) AS grup_adi
                 FROM randevular r
                 LEFT JOIN gruplar g ON g.id = r.grup_id AND g.kurum_id = r.kurum_id
                 WHERE r.kurum_id = ?
                   AND r.ogrenci_id IN ({$yer})
                   AND r.tarih >= CURDATE()
                   AND COALESCE(r.durum, \"planlandi\") NOT IN
                       (\"gelmedi\", \"mazeretli_gelmedi\", \"gec_iptal\", \"kurum_iptali\", \"ertelendi\")
                 ORDER BY r.ogrenci_id, r.tarih, r.baslangic_saati, r.id"
            );
            $stmt->execute([self::kurumId(), ...$ogrenciIdleri]);
            foreach ($stmt->fetchAll() as $randevu) {
                $ogrenciId = (int) $randevu['ogrenci_id'];
                if (isset($randevular[$ogrenciId])) continue;
                $randevu['id'] = (int) $randevu['id'];
                $randevu['ogrenci_id'] = $ogrenciId;
                $randevu['baslangic_saati'] = substr((string) $randevu['baslangic_saati'], 0, 5);
                $randevu['bitis_saati'] = substr((string) $randevu['bitis_saati'], 0, 5);
                $randevular[$ogrenciId] = $randevu;
            }
        }

        foreach ($kayitlar as &$kayit) {
            $randevu = $randevular[(int) ($kayit['ogrenci_id'] ?? 0)] ?? null;
            $kayit['guncel_randevu'] = $randevu;
            $kayit['guncel_randevu_var'] = $randevu ? 1 : 0;
            $kayit['daha_once_arandi'] = (int) ($kayit['iletisim_sayisi'] ?? 0) > 0 ? 1 : 0;
        }
        unset($kayit);

        return $kayitlar;
    }

    private static function yasAraliginda(int $ay, string $aralik): bool
    {
        preg_match_all('/\d+/', $aralik, $eslesmeler);
        $sayilar = array_map('intval', $eslesmeler[0] ?? []);
        if (!$sayilar) return false;
        $alt = $sayilar[0];
        $ust = $sayilar[1] ?? $sayilar[0];
        return $ay >= min($alt, $ust) && $ay <= max($alt, $ust);
    }

    private static function whatsappTelefon(string $telefon): string
    {
        $rakamlar = preg_replace('/\D+/', '', $telefon) ?? '';
        if (str_starts_with($rakamlar, '90')) return $rakamlar;
        if (str_starts_with($rakamlar, '0')) $rakamlar = substr($rakamlar, 1);
        return strlen($rakamlar) === 10 ? '90' . $rakamlar : $rakamlar;
    }

    private static function tarihceEkle(int $id, string $kanal, string $ozet, string $sonuc, ?string $takipTarihi, int $kullaniciId): void
    {
        $stmt = self::db()->prepare(
            'INSERT INTO bekleyen_veli_gorusmeleri
             (kurum_id, bekleyen_veli_id, gorusme_tarihi, kanal, ozet, sonuc, sonraki_takip_tarihi, olusturan_kullanici_id)
             VALUES (:kurum_id, :veli_id, NOW(), :kanal, :ozet, :sonuc, :takip, :kullanici_id)'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(), 'veli_id' => $id, 'kanal' => $kanal,
            'ozet' => $ozet, 'sonuc' => $sonuc, 'takip' => $takipTarihi,
            'kullanici_id' => $kullaniciId ?: null,
        ]);
    }

    private static function durumEtiketi(string $durum): string
    {
        return [
            'yeni_talep' => 'Yeni Talep', 'ilk_gorusme_yapilacak' => 'İlk Görüşme Yapılacak',
            'bilgi_verildi' => 'Bilgi Verildi', 'ulasilamadi' => 'Ulaşılamadı',
            'uygun_grup_bekliyor' => 'Uygun Grup Bekliyor', 'veli_donusu_bekleniyor' => 'Veli Dönüşü Bekleniyor',
            'tekrar_aranacak' => 'Tekrar Aranacak', 'kayit_olmaya_hazir' => 'Kayıt Olmaya Hazır',
            'kayit_oldu' => 'Kayıt Oldu', 'vazgecti' => 'Vazgeçti',
            'yas_uygun_degil' => 'Yaş Uygun Değil', 'saatler_uymadi' => 'Saatler Uymadı', 'diger' => 'Diğer',
            'bekliyor' => 'Yeni Talep', 'iletisime_gecildi' => 'Bilgi Verildi',
            'katilmadi' => 'Vazgeçti', 'kayda_donustu' => 'Kayıt Oldu', 'iptal' => 'Vazgeçti',
        ][$durum] ?? $durum;
    }

    private static function aksiyonEtiketi(string $aksiyon): string
    {
        return [
            'telefonla_ara' => 'Telefonla Ara', 'whatsapp_gonder' => 'WhatsApp Gönder',
            'veli_donusunu_bekle' => 'Veli Dönüşünü Bekle', 'grup_kontrol_et' => 'Grup Kontrol Et',
            'kayit_icin_ara' => 'Kayıt İçin Ara', 'diger' => 'Diğer',
        ][$aksiyon] ?? $aksiyon;
    }

    private static function durumSonucu(string $durum): string
    {
        return match ($durum) {
            'bilgi_verildi' => 'bilgi_verildi',
            'ulasilamadi' => 'ulasilamadi',
            'katilmadi' => 'katilmadi',
            default => 'diger',
        };
    }

    private static function kapaliDurumMu(string $durum): bool
    {
        return in_array($durum, ['kayit_oldu', 'kayda_donustu', 'vazgecti', 'ulasilamadi', 'yas_uygun_degil', 'saatler_uymadi', 'diger', 'iptal', 'katilmadi'], true);
    }

    private static function ensureSchema(): void
    {
        if (self::$schemaHazir) return;
        $db = self::db();
        $db->exec(
            'CREATE TABLE IF NOT EXISTS bekleyen_veliler (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              kurum_id INT UNSIGNED NOT NULL DEFAULT 1,
              ogrenci_id BIGINT UNSIGNED NULL,
              ogrenci_ad_soyad VARCHAR(160) NOT NULL,
              ogrenci_dogum_tarihi DATE NULL,
              veli_ad_soyad VARCHAR(160) NOT NULL,
              veli_telefon VARCHAR(32) NOT NULL,
              veli_eposta VARCHAR(160) NULL,
              beklenen_gun VARCHAR(20) NULL,
              ay_grubu VARCHAR(80) NULL,
              zaman_tercihi ENUM("hafta_ici","hafta_sonu","farketmez") NOT NULL DEFAULT "farketmez",
              durum ENUM("yeni_talep","ilk_gorusme_yapilacak","bilgi_verildi","uygun_grup_bekliyor","veli_donusu_bekleniyor","tekrar_aranacak","kayit_olmaya_hazir","kayit_oldu","vazgecti","ulasilamadi","yas_uygun_degil","saatler_uymadi","diger","bekliyor","iletisime_gecildi","katilmadi","kayda_donustu","iptal") NOT NULL DEFAULT "yeni_talep",
              next_follow_up_at DATETIME NULL,
              next_action_type ENUM("telefonla_ara","whatsapp_gonder","veli_donusunu_bekle","grup_kontrol_et","kayit_icin_ara","diger") NULL,
              next_action_note VARCHAR(500) NULL,
              notlar TEXT NULL,
              olusturan_kullanici_id BIGINT UNSIGNED NULL,
              olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              guncellenme_tarihi DATETIME NULL,
              INDEX idx_bekleyen_veliler_ogrenci (ogrenci_id),
              INDEX idx_bekleyen_veliler_durum (durum),
              INDEX idx_bekleyen_veliler_telefon (veli_telefon),
              INDEX idx_bekleyen_veliler_gun (beklenen_gun)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $db->exec(
            'CREATE TABLE IF NOT EXISTS bekleyen_veli_gorusmeleri (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              kurum_id BIGINT UNSIGNED NOT NULL,
              bekleyen_veli_id BIGINT UNSIGNED NOT NULL,
              gorusme_tarihi DATETIME NOT NULL,
              kanal ENUM("telefon","whatsapp","yuz_yuze","sms","diger") NOT NULL DEFAULT "telefon",
              ozet TEXT NOT NULL,
              sonuc ENUM("bilgi_verildi","tekrar_aranacak","randevu_planlandi","kararsiz","ulasilamadi","katilmadi","olumsuz","diger") NOT NULL DEFAULT "bilgi_verildi",
              sonraki_takip_tarihi DATE NULL,
              olusturan_kullanici_id BIGINT UNSIGNED NULL,
              olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              KEY idx_bekleyen_veli_gorusme_tarihce (kurum_id, bekleyen_veli_id, gorusme_tarihi),
              KEY idx_bekleyen_veli_gorusme_takip (kurum_id, sonraki_takip_tarihi)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $db->exec(
            'CREATE TABLE IF NOT EXISTS bekleyen_veli_gruplari (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              kurum_id BIGINT UNSIGNED NOT NULL,
              bekleyen_veli_id BIGINT UNSIGNED NOT NULL,
              grup_id BIGINT UNSIGNED NOT NULL,
              olusturan_kullanici_id BIGINT UNSIGNED NULL,
              olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY uq_bekleyen_veli_grubu (kurum_id, bekleyen_veli_id, grup_id),
              KEY idx_bekleyen_veli_grubu_grup (kurum_id, grup_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        if (!self::kolonVarMi('ogrenci_id')) {
            $db->exec('ALTER TABLE bekleyen_veliler ADD COLUMN ogrenci_id BIGINT UNSIGNED NULL AFTER id');
        }
        if (!self::kolonVarMi('kurum_id')) {
            $db->exec('ALTER TABLE bekleyen_veliler ADD COLUMN kurum_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER id');
        }
        $crmKolonlari = [
            'next_follow_up_at' => 'DATETIME NULL AFTER durum',
            'next_action_type' => 'ENUM("telefonla_ara","whatsapp_gonder","veli_donusunu_bekle","grup_kontrol_et","kayit_icin_ara","diger") NULL AFTER next_follow_up_at',
            'next_action_note' => 'VARCHAR(500) NULL AFTER next_action_type',
        ];
        foreach ($crmKolonlari as $kolon => $tanim) {
            if (!self::kolonVarMi($kolon)) $db->exec("ALTER TABLE bekleyen_veliler ADD COLUMN {$kolon} {$tanim}");
        }
        if (!str_contains(self::kolonTuru('bekleyen_veliler', 'durum'), 'yeni_talep')) {
            $db->exec(
                'ALTER TABLE bekleyen_veliler MODIFY COLUMN durum ENUM(
              "yeni_talep","ilk_gorusme_yapilacak","bilgi_verildi","uygun_grup_bekliyor",
              "veli_donusu_bekleniyor","tekrar_aranacak","kayit_olmaya_hazir","kayit_oldu",
              "vazgecti","ulasilamadi","yas_uygun_degil","saatler_uymadi","diger",
              "bekliyor","iletisime_gecildi","katilmadi","kayda_donustu","iptal"
            ) NOT NULL DEFAULT "yeni_talep"'
            );
        }
        if (!str_contains(self::kolonTuru('bekleyen_veli_gorusmeleri', 'sonuc'), 'veli_donecek')) {
            $db->exec(
                'ALTER TABLE bekleyen_veli_gorusmeleri MODIFY COLUMN sonuc ENUM(
              "goruldu","bilgi_verildi","veli_donecek","tekrar_aranacak","kayit_istiyor","uygun_grup_yok",
              "randevu_planlandi","kararsiz","ulasilamadi","katilmadi","olumsuz","diger"
            ) NOT NULL DEFAULT "bilgi_verildi"'
            );
        }
        self::$schemaHazir = true;
    }

    private static function kolonVarMi(string $kolon): bool
    {
        $stmt = self::db()->prepare(
            'SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = "bekleyen_veliler"
               AND COLUMN_NAME = :kolon'
        );
        $stmt->execute(['kolon' => $kolon]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private static function kolonTuru(string $tablo, string $kolon): string
    {
        $stmt = self::db()->prepare(
            'SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tablo AND COLUMN_NAME = :kolon LIMIT 1'
        );
        $stmt->execute(['tablo' => $tablo, 'kolon' => $kolon]);
        return (string) ($stmt->fetchColumn() ?: '');
    }
}
