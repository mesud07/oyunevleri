<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;
use Throwable;

final class SmsKaydi extends Model
{
    public const KUYRUK_DURUMLARI = ['bekliyor', 'tekrar_bekliyor'];

    public static function olustur(array $veri): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO sms_kayitlari
             (kurum_id, sablon_anahtari, olay_tipi, alici_tipi, alici_id, ogrenci_id, veli_id, grup_id, randevu_id, odeme_id, odeme_sozu_id,
              telefon_orijinal, telefon, mesaj, parca_sayisi, durum, mukerrer_anahtari, provider, olusturan_kullanici_id, hata_mesaji)
             VALUES
             (:kurum_id, :sablon_anahtari, :olay_tipi, :alici_tipi, :alici_id, :ogrenci_id, :veli_id, :grup_id, :randevu_id, :odeme_id, :odeme_sozu_id,
              :telefon_orijinal, :telefon, :mesaj, :parca_sayisi, :durum, :mukerrer_anahtari, :provider, :olusturan_kullanici_id, :hata_mesaji)'
        );

        try {
            $stmt->execute([
                'kurum_id' => self::kurumId(),
                'sablon_anahtari' => $veri['sablon_anahtari'] ?? null,
                'olay_tipi' => $veri['olay_tipi'] ?? 'manuel_sms',
                'alici_tipi' => $veri['alici_tipi'] ?? 'manuel',
                'alici_id' => $veri['alici_id'] ?? null,
                'ogrenci_id' => $veri['ogrenci_id'] ?? null,
                'veli_id' => $veri['veli_id'] ?? null,
                'grup_id' => $veri['grup_id'] ?? null,
                'randevu_id' => $veri['randevu_id'] ?? null,
                'odeme_id' => $veri['odeme_id'] ?? null,
                'odeme_sozu_id' => $veri['odeme_sozu_id'] ?? null,
                'telefon_orijinal' => $veri['telefon_orijinal'] ?? null,
                'telefon' => $veri['telefon'],
                'mesaj' => $veri['mesaj'],
                'parca_sayisi' => (int) ($veri['parca_sayisi'] ?? 1),
                'durum' => $veri['durum'] ?? 'bekliyor',
                'mukerrer_anahtari' => $veri['mukerrer_anahtari'] ?? null,
                'provider' => $veri['provider'] ?? 'netgsm',
                'olusturan_kullanici_id' => $veri['olusturan_kullanici_id'] ?? null,
                'hata_mesaji' => $veri['hata_mesaji'] ?? null,
            ]);
            $id = (int) self::db()->lastInsertId();
            self::olayEkle($id, null, (string) ($veri['durum'] ?? 'bekliyor'), 'Kuyruk kaydi olusturuldu.');
            return $id;
        } catch (Throwable $e) {
            if (($veri['mukerrer_anahtari'] ?? '') !== '' && str_contains($e->getMessage(), 'Duplicate')) {
                $stmt = self::db()->prepare('SELECT id FROM sms_kayitlari WHERE kurum_id = :kurum_id AND mukerrer_anahtari = :anahtar LIMIT 1');
                $stmt->execute(['kurum_id' => self::kurumId(), 'anahtar' => $veri['mukerrer_anahtari']]);
                return (int) $stmt->fetchColumn();
            }
            throw $e;
        }
    }

    public static function liste(array $filtre = []): array
    {
        $where = ['sk.kurum_id = :kurum_id'];
        $params = ['kurum_id' => self::kurumId()];
        $limit = min(100, max(10, (int) ($filtre['limit'] ?? 20)));
        $sayfa = max(1, (int) ($filtre['sayfa'] ?? 1));
        $offset = ($sayfa - 1) * $limit;
        if (!empty($filtre['durum'])) {
            $where[] = 'sk.durum = :durum';
            $params['durum'] = $filtre['durum'];
        }
        if (!empty($filtre['q'])) {
            $where[] = '(sk.telefon LIKE :q OR sk.mesaj LIKE :q OR CONCAT(o.ad, " ", o.soyad) LIKE :q OR CONCAT(v.ad, " ", v.soyad) LIKE :q)';
            $params['q'] = '%' . $filtre['q'] . '%';
        }
        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        $countStmt = self::db()->prepare(
            'SELECT COUNT(*)
             FROM sms_kayitlari sk
             LEFT JOIN ogrenciler o ON o.id = sk.ogrenci_id AND o.kurum_id = sk.kurum_id
             LEFT JOIN veliler v ON v.id = sk.veli_id AND v.kurum_id = sk.kurum_id' . $whereSql
        );
        $countStmt->execute($params);
        $toplam = (int) $countStmt->fetchColumn();

        $sql = 'SELECT sk.*, CONCAT(o.ad, " ", o.soyad) AS ogrenci, CONCAT(v.ad, " ", v.soyad) AS veli
                FROM sms_kayitlari sk
                LEFT JOIN ogrenciler o ON o.id = sk.ogrenci_id AND o.kurum_id = sk.kurum_id
                LEFT JOIN veliler v ON v.id = sk.veli_id AND v.kurum_id = sk.kurum_id' . $whereSql . '
                ORDER BY sk.olusturulma_tarihi DESC, sk.id DESC
                LIMIT :limit OFFSET :offset';
        $stmt = self::db()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'kayitlar' => $stmt->fetchAll(),
            'sayfalama' => [
                'sayfa' => $sayfa,
                'limit' => $limit,
                'toplam' => $toplam,
                'toplam_sayfa' => max(1, (int) ceil($toplam / $limit)),
            ],
        ];
    }

    public static function idIleBul(int $id): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT sk.*, CONCAT(o.ad, " ", o.soyad) AS ogrenci, CONCAT(v.ad, " ", v.soyad) AS veli
             FROM sms_kayitlari sk
             LEFT JOIN ogrenciler o ON o.id = sk.ogrenci_id AND o.kurum_id = sk.kurum_id
             LEFT JOIN veliler v ON v.id = sk.veli_id AND v.kurum_id = sk.kurum_id
             WHERE sk.id = :id AND sk.kurum_id = :kurum_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $kayit = $stmt->fetch();
        return $kayit ?: null;
    }

    public static function raporListe(array $filtre = []): array
    {
        [$where, $params] = self::raporKosullari($filtre);
        $limit = min(100, max(10, (int) ($filtre['limit'] ?? 20)));
        $sayfa = max(1, (int) ($filtre['sayfa'] ?? 1));
        $offset = ($sayfa - 1) * $limit;
        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        $countStmt = self::db()->prepare(
            'SELECT COUNT(*)
             FROM sms_kayitlari sk
             LEFT JOIN ogrenciler o ON o.id = sk.ogrenci_id AND o.kurum_id = sk.kurum_id
             LEFT JOIN veliler v ON v.id = sk.veli_id AND v.kurum_id = sk.kurum_id' . $whereSql
        );
        $countStmt->execute($params);
        $toplam = (int) $countStmt->fetchColumn();

        $sql = 'SELECT sk.*, CONCAT(o.ad, " ", o.soyad) AS ogrenci, CONCAT(v.ad, " ", v.soyad) AS veli
                FROM sms_kayitlari sk
                LEFT JOIN ogrenciler o ON o.id = sk.ogrenci_id AND o.kurum_id = sk.kurum_id
                LEFT JOIN veliler v ON v.id = sk.veli_id AND v.kurum_id = sk.kurum_id' . $whereSql . '
                ORDER BY COALESCE(sk.gonderilme_tarihi, sk.olusturulma_tarihi) DESC, sk.id DESC
                LIMIT :limit OFFSET :offset';
        $stmt = self::db()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'kayitlar' => $stmt->fetchAll(),
            'ozet' => self::raporOzet($filtre),
            'sayfalama' => [
                'sayfa' => $sayfa,
                'limit' => $limit,
                'toplam' => $toplam,
                'toplam_sayfa' => max(1, (int) ceil($toplam / $limit)),
            ],
        ];
    }

    private static function raporOzet(array $filtre): array
    {
        [$where, $params] = self::raporKosullari($filtre);
        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $stmt = self::db()->prepare(
            'SELECT
                COUNT(*) AS toplam,
                SUM(CASE WHEN sk.durum = "teslim_edildi" THEN 1 ELSE 0 END) AS teslim_edildi,
                SUM(CASE WHEN sk.durum = "gonderildi" THEN 1 ELSE 0 END) AS gonderildi,
                SUM(CASE WHEN sk.durum IN ("bekliyor", "tekrar_bekliyor", "isleniyor") THEN 1 ELSE 0 END) AS kuyruk,
                SUM(CASE WHEN sk.durum = "basarisiz" THEN 1 ELSE 0 END) AS basarisiz
             FROM sms_kayitlari sk
             LEFT JOIN ogrenciler o ON o.id = sk.ogrenci_id AND o.kurum_id = sk.kurum_id
             LEFT JOIN veliler v ON v.id = sk.veli_id AND v.kurum_id = sk.kurum_id' . $whereSql
        );
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];
        return [
            'toplam' => (int) ($row['toplam'] ?? 0),
            'teslim_edildi' => (int) ($row['teslim_edildi'] ?? 0),
            'gonderildi' => (int) ($row['gonderildi'] ?? 0),
            'kuyruk' => (int) ($row['kuyruk'] ?? 0),
            'basarisiz' => (int) ($row['basarisiz'] ?? 0),
        ];
    }

    private static function raporKosullari(array $filtre): array
    {
        $where = ['sk.kurum_id = :kurum_id'];
        $params = ['kurum_id' => self::kurumId()];
        if (!empty($filtre['durum'])) {
            $where[] = 'sk.durum = :durum';
            $params['durum'] = $filtre['durum'];
        }
        if (!empty($filtre['olay_tipi'])) {
            $where[] = 'sk.olay_tipi = :olay_tipi';
            $params['olay_tipi'] = $filtre['olay_tipi'];
        }
        if (!empty($filtre['ogrenci_id'])) {
            $where[] = 'sk.ogrenci_id = :ogrenci_id';
            $params['ogrenci_id'] = (int) $filtre['ogrenci_id'];
        }
        if (!empty($filtre['q'])) {
            $where[] = '(sk.telefon LIKE :q OR sk.mesaj LIKE :q OR CONCAT(o.ad, " ", o.soyad) LIKE :q OR CONCAT(v.ad, " ", v.soyad) LIKE :q)';
            $params['q'] = '%' . $filtre['q'] . '%';
        }
        if (!empty($filtre['baslangic'])) {
            $where[] = 'DATE(COALESCE(sk.gonderilme_tarihi, sk.olusturulma_tarihi)) >= :baslangic';
            $params['baslangic'] = $filtre['baslangic'];
        }
        if (!empty($filtre['bitis'])) {
            $where[] = 'DATE(COALESCE(sk.gonderilme_tarihi, sk.olusturulma_tarihi)) <= :bitis';
            $params['bitis'] = $filtre['bitis'];
        }
        return [$where, $params];
    }

    public static function sahiplen(int $limit = 50): array
    {
        $db = self::db();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                "SELECT id FROM sms_kayitlari
                 WHERE kurum_id = :kurum_id
                   AND durum IN ('bekliyor', 'tekrar_bekliyor')
                   AND (sonraki_deneme_tarihi IS NULL OR sonraki_deneme_tarihi <= NOW())
                 ORDER BY olusturulma_tarihi ASC, id ASC
                 LIMIT :limit
                 FOR UPDATE"
            );
            $stmt->bindValue('kurum_id', self::kurumId(), PDO::PARAM_INT);
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $ids = array_map('intval', array_column($stmt->fetchAll(), 'id'));
            if ($ids === []) {
                $db->commit();
                return [];
            }
            $yer = implode(',', array_fill(0, count($ids), '?'));
            $parametreler = array_merge([self::kurumId()], $ids);
            $db->prepare("UPDATE sms_kayitlari SET durum = 'isleniyor', deneme_sayisi = deneme_sayisi + 1 WHERE kurum_id = ? AND id IN ($yer)")->execute($parametreler);
            $stmt = $db->prepare("SELECT * FROM sms_kayitlari WHERE kurum_id = ? AND id IN ($yer) ORDER BY olusturulma_tarihi ASC, id ASC");
            $stmt->execute($parametreler);
            $kayitlar = $stmt->fetchAll();
            $db->commit();
            foreach ($ids as $id) {
                self::olayEkle($id, 'bekliyor', 'isleniyor', 'Kuyruk islemi basladi.');
            }
            return $kayitlar;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function gonderildi(int $id, ?string $providerIslemNo, string $cevap): void
    {
        self::durumGuncelle($id, 'gonderildi', [
            'provider_islem_no' => $providerIslemNo,
            'provider_cevabi' => $cevap,
            'hata_mesaji' => null,
            'gonderilme_tarihi' => date('Y-m-d H:i:s'),
            'sonraki_deneme_tarihi' => null,
        ]);
    }

    public static function teslimEdildi(int $id, string $cevap): void
    {
        self::durumGuncelle($id, 'teslim_edildi', [
            'provider_cevabi' => $cevap,
            'teslim_tarihi' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function basarisiz(int $id, string $hata, string $cevap = ''): void
    {
        self::durumGuncelle($id, 'basarisiz', [
            'hata_mesaji' => $hata,
            'provider_cevabi' => $cevap,
            'sonraki_deneme_tarihi' => null,
        ]);
    }

    public static function tekrarBekliyor(int $id, string $hata, int $dakika, string $cevap = ''): void
    {
        self::durumGuncelle($id, 'tekrar_bekliyor', [
            'hata_mesaji' => $hata,
            'provider_cevabi' => $cevap,
            'sonraki_deneme_tarihi' => date('Y-m-d H:i:s', time() + ($dakika * 60)),
        ]);
    }

    public static function iptal(int $id, int $kullaniciId): bool
    {
        $stmt = self::db()->prepare(
            "UPDATE sms_kayitlari
             SET durum = 'iptal', iptal_tarihi = NOW(), iptal_eden_kullanici_id = :kullanici_id
             WHERE id = :id AND kurum_id = :kurum_id AND durum IN ('bekliyor', 'tekrar_bekliyor')"
        );
        $stmt->execute(['id' => $id, 'kullanici_id' => $kullaniciId ?: null, 'kurum_id' => self::kurumId()]);
        if ($stmt->rowCount() > 0) {
            self::olayEkle($id, null, 'iptal', 'SMS kuyrugu iptal edildi.');
            return true;
        }
        return false;
    }

    public static function tekrarGonder(int $id): bool
    {
        $stmt = self::db()->prepare(
            "UPDATE sms_kayitlari
             SET durum = 'bekliyor', sonraki_deneme_tarihi = NULL, hata_mesaji = NULL
             WHERE id = :id AND kurum_id = :kurum_id AND durum IN ('basarisiz', 'iptal', 'tekrar_bekliyor')"
        );
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        if ($stmt->rowCount() > 0) {
            self::olayEkle($id, null, 'bekliyor', 'SMS tekrar kuyruga alindi.');
            return true;
        }
        return false;
    }

    public static function sablonlar(bool $sadeceAktif = false): array
    {
        $sql = 'SELECT * FROM sms_sablonlari WHERE kurum_id = :kurum_id';
        if ($sadeceAktif) {
            $sql .= ' AND aktif = 1';
        }
        $sql .= ' ORDER BY baslik ASC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function sablonBul(string $anahtar): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM sms_sablonlari WHERE kurum_id = :kurum_id AND anahtar = :anahtar LIMIT 1');
        $stmt->execute(['kurum_id' => self::kurumId(), 'anahtar' => $anahtar]);
        $sablon = $stmt->fetch();
        return $sablon ?: null;
    }

    public static function sablonKaydet(array $veri): int
    {
        $id = (int) ($veri['id'] ?? 0);
        if ($id > 0) {
            $stmt = self::db()->prepare(
                'UPDATE sms_sablonlari
                 SET baslik = :baslik, mesaj = :mesaj, aktif = :aktif,
                     otomatik_gonderim = :otomatik_gonderim, onay_durumu = :onay_durumu,
                     onay_notu = :onay_notu, aciklama = :aciklama
                 WHERE id = :id AND kurum_id = :kurum_id'
            );
            $stmt->execute([
                'id' => $id,
                'kurum_id' => self::kurumId(),
                'baslik' => $veri['baslik'],
                'mesaj' => $veri['mesaj'],
                'aktif' => (int) ($veri['aktif'] ?? 1),
                'otomatik_gonderim' => (int) ($veri['otomatik_gonderim'] ?? 0),
                'onay_durumu' => $veri['onay_durumu'] ?? 'incelemede',
                'onay_notu' => $veri['onay_notu'] ?? 'Sablon incelemeye alindi.',
                'aciklama' => $veri['aciklama'] ?? null,
            ]);
            return $id;
        }

        $stmt = self::db()->prepare(
            'INSERT INTO sms_sablonlari (kurum_id, anahtar, baslik, mesaj, aktif, otomatik_gonderim, onay_durumu, onay_notu, aciklama)
             VALUES (:kurum_id, :anahtar, :baslik, :mesaj, :aktif, :otomatik_gonderim, :onay_durumu, :onay_notu, :aciklama)
             ON DUPLICATE KEY UPDATE baslik = VALUES(baslik), mesaj = VALUES(mesaj), aktif = VALUES(aktif),
                                     otomatik_gonderim = VALUES(otomatik_gonderim), onay_durumu = VALUES(onay_durumu),
                                     onay_notu = VALUES(onay_notu), aciklama = VALUES(aciklama)'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'anahtar' => $veri['anahtar'],
            'baslik' => $veri['baslik'],
            'mesaj' => $veri['mesaj'],
            'aktif' => (int) ($veri['aktif'] ?? 1),
            'otomatik_gonderim' => (int) ($veri['otomatik_gonderim'] ?? 0),
            'onay_durumu' => $veri['onay_durumu'] ?? 'incelemede',
            'onay_notu' => $veri['onay_notu'] ?? 'Sablon incelemeye alindi.',
            'aciklama' => $veri['aciklama'] ?? null,
        ]);
        $idStmt = self::db()->prepare('SELECT id FROM sms_sablonlari WHERE kurum_id = :kurum_id AND anahtar = :anahtar LIMIT 1');
        $idStmt->execute(['kurum_id' => self::kurumId(), 'anahtar' => $veri['anahtar']]);
        return (int) $idStmt->fetchColumn();
    }

    public static function sablonDurumDegistir(string $anahtar, bool $aktif): bool
    {
        $stmt = self::db()->prepare('UPDATE sms_sablonlari SET aktif = :aktif WHERE kurum_id = :kurum_id AND anahtar = :anahtar');
        $stmt->execute(['anahtar' => $anahtar, 'aktif' => $aktif ? 1 : 0, 'kurum_id' => self::kurumId()]);
        return $stmt->rowCount() > 0;
    }

    public static function sablonOnayla(string $anahtar): bool
    {
        $stmt = self::db()->prepare(
            "UPDATE sms_sablonlari
             SET onay_durumu = 'kullanilabilir', onay_notu = NULL, son_onay_tarihi = NOW(), aktif = 1
             WHERE kurum_id = :kurum_id AND anahtar = :anahtar"
        );
        $stmt->execute(['anahtar' => $anahtar, 'kurum_id' => self::kurumId()]);
        return $stmt->rowCount() > 0;
    }

    public static function sablonReddet(string $anahtar, string $not = ''): bool
    {
        $stmt = self::db()->prepare(
            "UPDATE sms_sablonlari
             SET onay_durumu = 'reddedildi', onay_notu = :onay_notu, aktif = 0
             WHERE kurum_id = :kurum_id AND anahtar = :anahtar"
        );
        $stmt->execute([
            'anahtar' => $anahtar,
            'kurum_id' => self::kurumId(),
            'onay_notu' => $not ?: 'Sablon reddedildi.',
        ]);
        return $stmt->rowCount() > 0;
    }

    private static function durumGuncelle(int $id, string $durum, array $alanlar): void
    {
        $mevcut = self::idIleBul($id);
        $set = ['durum = :durum'];
        $params = ['id' => $id, 'durum' => $durum, 'kurum_id' => self::kurumId()];
        foreach ($alanlar as $alan => $deger) {
            $set[] = "$alan = :$alan";
            $params[$alan] = $deger;
        }
        $stmt = self::db()->prepare('UPDATE sms_kayitlari SET ' . implode(', ', $set) . ' WHERE id = :id AND kurum_id = :kurum_id');
        $stmt->execute($params);
        self::olayEkle($id, $mevcut['durum'] ?? null, $durum, $alanlar['hata_mesaji'] ?? $alanlar['provider_cevabi'] ?? null);
    }

    private static function olayEkle(int $id, ?string $eskiDurum, string $yeniDurum, ?string $mesaj = null): void
    {
        try {
            $stmt = self::db()->prepare(
                'INSERT INTO sms_olay_kayitlari (sms_kaydi_id, eski_durum, yeni_durum, mesaj)
                 VALUES (:sms_kaydi_id, :eski_durum, :yeni_durum, :mesaj)'
            );
            $stmt->execute([
                'sms_kaydi_id' => $id,
                'eski_durum' => $eskiDurum,
                'yeni_durum' => $yeniDurum,
                'mesaj' => $mesaj,
            ]);
        } catch (Throwable $e) {
            error_log('SMS olay kaydi yazilamadi: ' . $e->getMessage());
        }
    }
}
