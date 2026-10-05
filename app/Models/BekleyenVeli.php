<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class BekleyenVeli extends Model
{
    public static function liste(): array
    {
        self::ensureSchema();

        $stmt = self::db()->prepare(
            'SELECT id, ogrenci_id, ogrenci_ad_soyad, ogrenci_dogum_tarihi, veli_ad_soyad, veli_telefon, veli_eposta,
                    beklenen_gun, ay_grubu, zaman_tercihi, durum, notlar, olusturulma_tarihi,
                    GREATEST(0, DATEDIFF(CURDATE(), DATE(olusturulma_tarihi))) AS listeye_ekleneli_gun,
                    (SELECT COUNT(*) FROM bekleyen_veli_gorusmeleri bg
                     WHERE bg.bekleyen_veli_id = bekleyen_veliler.id AND bg.kurum_id = bekleyen_veliler.kurum_id) AS gorusme_sayisi,
                    (SELECT bg.ozet FROM bekleyen_veli_gorusmeleri bg
                     WHERE bg.bekleyen_veli_id = bekleyen_veliler.id AND bg.kurum_id = bekleyen_veliler.kurum_id
                     ORDER BY bg.gorusme_tarihi DESC, bg.id DESC LIMIT 1) AS son_gorusme_ozeti,
                    (SELECT bg.gorusme_tarihi FROM bekleyen_veli_gorusmeleri bg
                     WHERE bg.bekleyen_veli_id = bekleyen_veliler.id AND bg.kurum_id = bekleyen_veliler.kurum_id
                     ORDER BY bg.gorusme_tarihi DESC, bg.id DESC LIMIT 1) AS son_gorusme_tarihi,
                    (SELECT MIN(bg.sonraki_takip_tarihi) FROM bekleyen_veli_gorusmeleri bg
                     WHERE bg.bekleyen_veli_id = bekleyen_veliler.id AND bg.kurum_id = bekleyen_veliler.kurum_id
                       AND bg.sonraki_takip_tarihi >= CURDATE()) AS sonraki_takip_tarihi
             FROM bekleyen_veliler
             WHERE kurum_id = :kurum_id
             ORDER BY FIELD(durum, "bekliyor", "ulasilamadi", "iletisime_gecildi", "bilgi_verildi", "katilmadi", "kayda_donustu", "iptal"),
                      olusturulma_tarihi DESC,
                      id DESC
             LIMIT 300'
        );
        $stmt->execute(self::kurumParam());

        return self::gruplariEkle(self::ayYaslariniEkle($stmt->fetchAll()));
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
                 :beklenen_gun, :ay_grubu, :zaman_tercihi, "bekliyor", :notlar, :olusturan_kullanici_id, NOW())'
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

        return (int) self::db()->lastInsertId();
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
               AND durum NOT IN ("kayda_donustu", "iptal")'
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
                $stmt = $db->prepare('UPDATE bekleyen_veliler SET durum = :durum, guncellenme_tarihi = NOW() WHERE id = :id AND kurum_id = :kurum_id');
                $stmt->execute(['id' => $id, 'durum' => $durum, 'kurum_id' => self::kurumId()]);
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
                    beklenen_gun, ay_grubu, zaman_tercihi, durum, notlar, olusturulma_tarihi
             FROM bekleyen_veliler
             WHERE id = :id
               AND kurum_id = :kurum_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $kayit = $stmt->fetch();

        if (!$kayit) return null;
        $kayit = self::ayYasiniEkle($kayit);
        return self::gruplariEkle([$kayit])[0];
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
                'bilgi_verildi' => 'bilgi_verildi',
                'ulasilamadi' => 'ulasilamadi',
                'katilmadi' => 'katilmadi',
                default => 'iletisime_gecildi',
            };
            $durumStmt = $db->prepare(
                'UPDATE bekleyen_veliler
                 SET durum = IF(durum IN ("kayda_donustu", "iptal"), durum, :durum), guncellenme_tarihi = NOW()
                 WHERE id = :id AND kurum_id = :kurum_id'
            );
            $durumStmt->execute(['id' => $id, 'kurum_id' => self::kurumId(), 'durum' => $yeniDurum]);
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

    public static function durumlar(): array
    {
        return ['bekliyor', 'iletisime_gecildi', 'bilgi_verildi', 'ulasilamadi', 'katilmadi', 'kayda_donustu', 'iptal'];
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
               AND bv.durum NOT IN ("kayda_donustu", "iptal")
             ORDER BY FIELD(bv.durum, "bekliyor", "ulasilamadi", "iletisime_gecildi", "bilgi_verildi", "katilmadi"), bv.olusturulma_tarihi ASC'
        );
        $stmt->execute(['grup_id' => $grupId, 'kurum_id' => self::kurumId()]);
        return self::ayYaslariniEkle($stmt->fetchAll());
    }

    private static function donusumBaginiGuncelle(int $id, int $ogrenciId, int $kullaniciId = 0): void
    {
        $stmt = self::db()->prepare(
            'UPDATE bekleyen_veliler
             SET ogrenci_id = :ogrenci_id, durum = "kayda_donustu", guncellenme_tarihi = NOW()
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
            'bekliyor' => 'Bekliyor', 'iletisime_gecildi' => 'İletişime Geçildi',
            'bilgi_verildi' => 'Bilgi Verildi', 'ulasilamadi' => 'Ulaşılamadı',
            'katilmadi' => 'Katılmadı', 'kayda_donustu' => 'Kayda Dönüştü', 'iptal' => 'İptal',
        ][$durum] ?? $durum;
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

    private static function ensureSchema(): void
    {
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
              durum ENUM("bekliyor","iletisime_gecildi","bilgi_verildi","ulasilamadi","katilmadi","kayda_donustu","iptal") NOT NULL DEFAULT "bekliyor",
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
}
