<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Model;
use App\Models\Gider;

final class Rapor extends Model
{
    public static function genelBakisVerisi(): array
    {
        $db = self::db();
        $kurumId = self::kurumId();
        $stmt = $db->prepare(
            'SELECT
                (SELECT COUNT(DISTINCT o.id)
                 FROM ogrenciler o
                 INNER JOIN grup_ogrencileri go ON go.ogrenci_id = o.id AND go.kurum_id = o.kurum_id
                 INNER JOIN gruplar g ON g.id = go.grup_id AND g.kurum_id = o.kurum_id
                 WHERE o.kurum_id = :kurum_id_ogrenci
                   AND o.durum = "aktif"
                   AND go.aktif = 1
                   AND (go.bitis_tarihi IS NULL OR go.bitis_tarihi >= CURDATE())
                   AND g.aktif = 1) AS aktif_ogrenci,
                (SELECT COUNT(*)
                 FROM randevular
                 WHERE kurum_id = :kurum_id_randevu AND tarih = CURDATE()) AS bugunku_randevu,
                (SELECT COALESCE(SUM(tutar), 0)
                 FROM odemeler
                 WHERE kurum_id = :kurum_id_aylik AND iptal = 0
                   AND tarih >= DATE_FORMAT(CURDATE(), "%Y-%m-01")
                   AND tarih <= LAST_DAY(CURDATE())) AS bu_ay_tahsilat,
                COALESCE(SUM(GREATEST(p.net_paket_tutari - COALESCE(od.tahsilat, 0), 0)), 0) AS bekleyen_alacak,
                COALESCE(SUM(CASE WHEN p.net_paket_tutari - COALESCE(od.tahsilat, 0) > 0 THEN 1 ELSE 0 END), 0) AS borclu_paket_sayisi
             FROM paketler p
             LEFT JOIN (
                SELECT paket_id, SUM(tutar) AS tahsilat
                FROM odemeler
                WHERE kurum_id = :kurum_id_odeme AND iptal = 0
                GROUP BY paket_id
             ) od ON od.paket_id = p.id
             WHERE p.kurum_id = :kurum_id_paket AND p.paket_durumu = "aktif"'
        );
        $stmt->execute([
            'kurum_id_ogrenci' => $kurumId,
            'kurum_id_randevu' => $kurumId,
            'kurum_id_aylik' => $kurumId,
            'kurum_id_odeme' => $kurumId,
            'kurum_id_paket' => $kurumId,
        ]);
        $ozet = $stmt->fetch() ?: [];

        return [
            'ozet' => [
                'aktif_ogrenci' => (int) ($ozet['aktif_ogrenci'] ?? 0),
                'bugunku_randevu' => (int) ($ozet['bugunku_randevu'] ?? 0),
                'bu_ay_tahsilat' => (float) ($ozet['bu_ay_tahsilat'] ?? 0),
                'bekleyen_alacak' => (float) ($ozet['bekleyen_alacak'] ?? 0),
            ],
            'borclu_paket_sayisi' => (int) ($ozet['borclu_paket_sayisi'] ?? 0),
            'bugun_son_dersler' => self::bugunSonDersiOlanlar(),
            'kayit_yenileme_takvimi' => self::kayitYenilemeTakvimi(),
        ];
    }

    public static function ozet(): array
    {
        return [
            'aktif_ogrenci' => self::aktifGrupOgrenciSayisi(),
            'aktif_grup' => (int) self::tekDeger('SELECT COUNT(*) FROM gruplar WHERE kurum_id = :kurum_id AND aktif = 1'),
            'aktif_paket' => (int) self::tekDeger('SELECT COUNT(*) FROM paketler WHERE kurum_id = :kurum_id AND paket_durumu = "aktif"'),
            'bugunku_randevu' => (int) self::tekDeger('SELECT COUNT(*) FROM randevular WHERE kurum_id = :kurum_id AND tarih = CURDATE()'),
            'bu_ay_tahsilat' => (float) self::tekDeger('SELECT COALESCE(SUM(tutar), 0) FROM odemeler WHERE kurum_id = :kurum_id AND iptal = 0 AND tarih >= DATE_FORMAT(CURDATE(), "%Y-%m-01") AND tarih <= LAST_DAY(CURDATE())'),
            'bekleyen_alacak' => (float) self::tekDeger(
                'SELECT COALESCE(SUM(p.net_paket_tutari), 0) - COALESCE(SUM(od.tahsilat), 0)
                 FROM paketler p
                 LEFT JOIN (
                    SELECT paket_id, SUM(tutar) AS tahsilat
                    FROM odemeler
                    WHERE kurum_id = :kurum_id_odeme AND iptal = 0
                    GROUP BY paket_id
                 ) od ON od.paket_id = p.id
                 WHERE p.kurum_id = :kurum_id AND p.paket_durumu = "aktif"',
                ['kurum_id_odeme' => self::kurumId()]
            ),
            'geciken_soz' => (int) self::tekDeger('SELECT COUNT(*) FROM odeme_sozleri WHERE kurum_id = :kurum_id AND durum IN ("bekleniyor","gecikti","bugun_odenecek") AND soz_verilen_tarih < CURDATE()'),
            'gelmeyen_randevu' => (int) self::tekDeger('SELECT COUNT(*) FROM randevular WHERE kurum_id = :kurum_id AND durum IN ("gelmedi","mazeretli_gelmedi","gec_iptal") AND tarih >= DATE_FORMAT(CURDATE(), "%Y-%m-01") AND tarih <= LAST_DAY(CURDATE())'),
            'yaklasan_tahsilat_beklentisi' => self::tahsilatBeklentisiToplami('BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)'),
            'gecikmis_tahsilat_beklentisi' => self::tahsilatBeklentisiToplami('< CURDATE()'),
            'kayit_yenileme_bakiyesi' => self::kayitYenilemeBakiyesi(),
            'yapilacak_odeme_30_gun' => Gider::ozet()['otuz_gun'] ?? 0,
        ];
    }

    public static function aktifGrupOgrenciSayisi(): int
    {
        $stmt = self::db()->prepare(
            'SELECT COUNT(DISTINCT o.id)
             FROM ogrenciler o
             INNER JOIN grup_ogrencileri go ON go.ogrenci_id = o.id AND go.kurum_id = o.kurum_id
             INNER JOIN gruplar g ON g.id = go.grup_id AND g.kurum_id = o.kurum_id
             WHERE o.kurum_id = :kurum_id
               AND o.durum = "aktif"
               AND go.aktif = 1
               AND (go.bitis_tarihi IS NULL OR go.bitis_tarihi >= CURDATE())
               AND g.aktif = 1'
        );
        $stmt->execute(self::kurumParam());
        return (int) $stmt->fetchColumn();
    }

    private static function tekDeger(string $sql, array $params = [])
    {
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['kurum_id' => self::kurumId()] + $params);
        return $stmt->fetchColumn();
    }

    public static function aylikTahsilat(): array
    {
        $stmt = self::db()->prepare(
            'SELECT DATE_FORMAT(tarih, "%Y-%m") AS ay, COALESCE(SUM(tutar), 0) AS tahsilat
             FROM odemeler
             WHERE kurum_id = :kurum_id AND iptal = 0 AND tarih >= DATE_SUB(DATE_FORMAT(CURDATE(), "%Y-%m-01"), INTERVAL 5 MONTH)
             GROUP BY DATE_FORMAT(tarih, "%Y-%m")
             ORDER BY ay ASC'
        );
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function randevuDurumDagilimi(): array
    {
        $stmt = self::db()->prepare(
            'SELECT durum, COUNT(*) AS adet
             FROM randevular
             WHERE kurum_id = :kurum_id AND tarih >= DATE_FORMAT(CURDATE(), "%Y-%m-01") AND tarih <= LAST_DAY(CURDATE())
             GROUP BY durum
             ORDER BY adet DESC'
        );
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function paketPerformansi(): array
    {
        $stmt = self::db()->prepare(
            'SELECT p.paket_adi,
                    COUNT(*) AS paket_sayisi,
                    COALESCE(SUM(p.net_paket_tutari), 0) AS ciro,
                    COALESCE(SUM(p.kalan_normal_hak + p.kalan_telafi_hak), 0) AS kalan_hak
             FROM paketler p
             WHERE p.kurum_id = :kurum_id
             GROUP BY p.paket_adi
             ORDER BY ciro DESC
             LIMIT 10'
        );
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function borcluPaketler(): array
    {
        $stmt = self::db()->prepare(
            'SELECT p.id, CONCAT(o.ad, " ", o.soyad) AS ogrenci, p.paket_adi,
                    p.net_paket_tutari,
                    COALESCE(SUM(CASE WHEN od.iptal = 0 THEN od.tutar ELSE 0 END), 0) AS tahsilat,
                    p.net_paket_tutari - COALESCE(SUM(CASE WHEN od.iptal = 0 THEN od.tutar ELSE 0 END), 0) AS kalan
             FROM paketler p
             INNER JOIN ogrenciler o ON o.id = p.ogrenci_id AND o.kurum_id = p.kurum_id
             LEFT JOIN odemeler od ON od.paket_id = p.id AND od.kurum_id = p.kurum_id
             WHERE p.kurum_id = :kurum_id AND p.paket_durumu = "aktif"
             GROUP BY p.id
             HAVING kalan > 0
             ORDER BY kalan DESC
             LIMIT 10'
        );
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function yaklasanRandevular(): array
    {
        $stmt = self::db()->prepare(
            'SELECT r.tarih, r.baslangic_saati, CONCAT(o.ad, " ", o.soyad) AS ogrenci,
                    COALESCE(g.ad, "Cocuk Etkinlik ve Oyun Evi") AS grup, r.tur, r.durum
             FROM randevular r
             INNER JOIN ogrenciler o ON o.id = r.ogrenci_id AND o.kurum_id = r.kurum_id
             LEFT JOIN gruplar g ON g.id = r.grup_id AND g.kurum_id = r.kurum_id
             WHERE r.kurum_id = :kurum_id AND r.tarih >= CURDATE()
             ORDER BY r.tarih ASC, r.baslangic_saati ASC
             LIMIT 10'
        );
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function bugunSonDersiOlanlar(): array
    {
        $stmt = self::db()->prepare(
            'SELECT p.id AS paket_id, o.id AS ogrenci_id, CONCAT(o.ad, " ", o.soyad) AS ogrenci,
                    p.paket_adi, p.tahmini_son_ders_tarihi, p.kalan_normal_hak, p.kalan_telafi_hak,
                    p.net_paket_tutari AS yenileme_ucreti,
                    COALESCE(MAX(CASE WHEN ov.birincil_mi = 1 THEN v.id END), MAX(v.id), 0) AS veli_id,
                    COALESCE(MAX(CASE WHEN ov.birincil_mi = 1 THEN v.telefon END), MAX(v.telefon), "") AS telefon,
                    COALESCE(MAX(CASE WHEN ov.birincil_mi = 1 THEN CONCAT(v.ad, " ", v.soyad) END), MAX(CONCAT(v.ad, " ", v.soyad)), "") AS veli_adi,
                    MAX(r.baslangic_saati) AS son_ders_saati
             FROM paketler p
             INNER JOIN ogrenciler o ON o.id = p.ogrenci_id AND o.kurum_id = p.kurum_id
             LEFT JOIN ogrenci_velileri ov ON ov.ogrenci_id = o.id AND ov.kurum_id = p.kurum_id
             LEFT JOIN veliler v ON v.id = ov.veli_id AND v.kurum_id = p.kurum_id
             LEFT JOIN randevular r ON r.paket_id = p.id AND r.kurum_id = p.kurum_id AND r.tarih = p.tahmini_son_ders_tarihi
             WHERE p.kurum_id = :kurum_id
                AND p.paket_durumu <> "iptal"
                AND p.tahmini_son_ders_tarihi = CURDATE()
                AND p.paket_adi NOT LIKE "%Tanisma%"
                AND p.paket_adi NOT LIKE "%Tanışma%"
             GROUP BY p.id
             ORDER BY son_ders_saati ASC, o.ad ASC, o.soyad ASC'
        );
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function grupKontenjanlari(): array
    {
        $db = self::db();
        $stmt = $db->prepare(
            'SELECT g.id AS grup_id, dp.id AS program_id, g.ad AS program_adi, g.yas_araligi,
                    g.kontenjan, COALESCE(g.durum, "durum_yok") AS durum,
                    dp.gun, dp.baslangic_saati, dp.bitis_saati,
                    COUNT(DISTINCT go.ogrenci_id) AS ogrenci_sayisi,
                    MIN(CASE
                        WHEN p.paket_durumu = "aktif" AND p.tahmini_son_ders_tarihi >= CURDATE()
                        THEN p.tahmini_son_ders_tarihi
                        ELSE NULL
                    END) AS en_erken_musait_tarih
             FROM gruplar g
             LEFT JOIN ders_programlari dp ON dp.grup_id = g.id AND dp.kurum_id = g.kurum_id AND dp.aktif = 1
             LEFT JOIN grup_ogrencileri go ON go.grup_id = g.id AND go.kurum_id = g.kurum_id AND go.aktif = 1
             LEFT JOIN paketler p ON p.ogrenci_id = go.ogrenci_id AND p.kurum_id = g.kurum_id
             WHERE g.kurum_id = :kurum_id AND (g.aktif = 1 OR g.durum IN ("kayit_acik", "yeni_grup", "kontenjan_sinirli", "doldu"))
             GROUP BY g.id, dp.id
             ORDER BY dp.gun ASC, dp.baslangic_saati ASC, g.ad ASC'
        );
        $stmt->execute(self::kurumParam());
        $satirlar = $stmt->fetchAll();

        $grupIdleri = array_values(array_unique(array_map('intval', array_column($satirlar, 'grup_id'))));
        $ogrenciler = self::grupOgrenciBitisleri($grupIdleri);

        return array_map(static function (array $satir) use ($ogrenciler): array {
            [$minAy, $maxAy] = self::yasAraligiAy((string) ($satir['yas_araligi'] ?? ''));
            $kontenjan = max(0, (int) ($satir['kontenjan'] ?? 0));
            $ogrenciSayisi = (int) ($satir['ogrenci_sayisi'] ?? 0);
            $musaitKontenjan = max(0, $kontenjan - $ogrenciSayisi);
            $durum = (string) ($satir['durum'] ?? 'durum_yok');
            $doluMu = $musaitKontenjan < 1 || $durum === 'doldu';
            $grupOgrencileri = $ogrenciler[(int) ($satir['grup_id'] ?? 0)] ?? [];
            $enErkenOgrenci = $grupOgrencileri[0] ?? null;

            return array_merge($satir, [
                'yas_min_ay' => $minAy,
                'yas_max_ay' => $maxAy,
                'musait_kontenjan' => $musaitKontenjan,
                'kontenjan_durumu' => $doluMu ? 'dolu' : ($musaitKontenjan <= 2 ? 'sinirli' : 'musait'),
                'en_erken_musait_tarih' => $doluMu ? ($satir['en_erken_musait_tarih'] ?: null) : date('Y-m-d'),
                'en_erken_ogrenci' => $doluMu && $enErkenOgrenci ? $enErkenOgrenci['ogrenci'] : null,
                'grup_ogrencileri' => $grupOgrencileri,
            ]);
        }, $satirlar);
    }

    private static function grupOgrenciBitisleri(array $grupIdleri): array
    {
        $grupIdleri = array_values(array_filter(array_map('intval', $grupIdleri)));
        if ($grupIdleri === []) {
            return [];
        }

        $yerTutucular = implode(',', array_fill(0, count($grupIdleri), '?'));
        $stmt = self::db()->prepare(
            "SELECT *
             FROM (
                SELECT go.grup_id, go.ogrenci_id, CONCAT(o.ad, ' ', o.soyad) AS ogrenci,
                        MIN(CASE WHEN p.paket_durumu = 'aktif' THEN p.tahmini_son_ders_tarihi ELSE NULL END) AS bitis_tarihi,
                        MIN(CASE WHEN p.paket_durumu = 'aktif' AND p.tahmini_son_ders_tarihi >= CURDATE() THEN p.tahmini_son_ders_tarihi ELSE NULL END) AS musaitlik_tarihi,
                        MIN(CASE WHEN p.paket_durumu = 'aktif' THEN p.kalan_normal_hak ELSE NULL END) AS kalan_ders,
                        MIN(CASE WHEN p.paket_durumu = 'aktif' THEN p.kalan_telafi_hak ELSE NULL END) AS kalan_telafi,
                        MIN(CASE WHEN p.paket_durumu = 'aktif' THEN p.paket_adi ELSE NULL END) AS paket_adi
                 FROM grup_ogrencileri go
                 INNER JOIN ogrenciler o ON o.id = go.ogrenci_id AND o.kurum_id = go.kurum_id
                 LEFT JOIN paketler p ON p.ogrenci_id = go.ogrenci_id AND p.kurum_id = go.kurum_id AND p.paket_durumu = 'aktif'
                 WHERE go.kurum_id = ?
                   AND go.aktif = 1
                   AND go.grup_id IN ($yerTutucular)
                 GROUP BY go.grup_id, go.ogrenci_id, o.ad, o.soyad
             ) AS grup_bitisleri
             ORDER BY ISNULL(musaitlik_tarihi) ASC, musaitlik_tarihi ASC, ISNULL(bitis_tarihi) ASC, bitis_tarihi ASC, ogrenci ASC"
        );
        $stmt->execute(array_merge([self::kurumId()], $grupIdleri));

        $sonuc = [];
        foreach ($stmt->fetchAll() as $row) {
            $sonuc[(int) $row['grup_id']][] = [
                'ogrenci_id' => (int) $row['ogrenci_id'],
                'ogrenci' => $row['ogrenci'],
                'bitis_tarihi' => $row['bitis_tarihi'],
                'musaitlik_tarihi' => $row['musaitlik_tarihi'],
                'kalan_ders' => $row['kalan_ders'],
                'kalan_telafi' => $row['kalan_telafi'],
                'paket_adi' => $row['paket_adi'],
            ];
        }

        return $sonuc;
    }

    public static function kapasiteGelirRaporu(?string $hafta = null): array
    {
        $db = self::db();
        try {
            $referansTarih = $hafta && preg_match('/^\d{4}-\d{2}-\d{2}$/', $hafta)
                ? new \DateTimeImmutable($hafta)
                : new \DateTimeImmutable('today');
        } catch (\Throwable $e) {
            $referansTarih = new \DateTimeImmutable('today');
        }
        $haftaBaslangici = $referansTarih->modify('monday this week')->format('Y-m-d');
        $haftaBitisi = $referansTarih->modify('sunday this week')->format('Y-m-d');
        $kapasiteStmt = $db->prepare(
            'SELECT COUNT(*) AS grup_satiri,
                    COALESCE(SUM(x.kontenjan), 0) AS maksimum_ogrenci,
                    COALESCE(SUM(x.ogrenci_sayisi), 0) AS mevcut_kayit,
                    (
                        SELECT COUNT(DISTINCT go3.ogrenci_id)
                        FROM ders_programlari dp3
                        INNER JOIN gruplar g3 ON g3.id = dp3.grup_id AND g3.kurum_id = dp3.kurum_id
                        INNER JOIN grup_ogrencileri go3 ON go3.grup_id = g3.id AND go3.kurum_id = dp3.kurum_id AND go3.aktif = 1
                        WHERE dp3.kurum_id = :kurum_id_sub
                          AND dp3.aktif = 1
                          AND (g3.aktif = 1 OR g3.durum IN ("kayit_acik", "yeni_grup", "kontenjan_sinirli", "doldu"))
                    ) AS tekil_ogrenci
             FROM (
                SELECT dp.id AS program_id, g.kontenjan,
                       COUNT(DISTINCT go.ogrenci_id) AS ogrenci_sayisi
                FROM ders_programlari dp
                INNER JOIN gruplar g ON g.id = dp.grup_id AND g.kurum_id = dp.kurum_id
                LEFT JOIN grup_ogrencileri go ON go.grup_id = g.id AND go.kurum_id = dp.kurum_id AND go.aktif = 1
                WHERE dp.kurum_id = :kurum_id
                  AND dp.aktif = 1
                  AND (g.aktif = 1 OR g.durum IN ("kayit_acik", "yeni_grup", "kontenjan_sinirli", "doldu"))
                GROUP BY dp.id
             ) x'
        );
        $kapasiteStmt->execute(['kurum_id' => self::kurumId(), 'kurum_id_sub' => self::kurumId()]);
        $kapasite = $kapasiteStmt->fetch() ?: [];

        $ortalamaStmt = $db->prepare(
            'SELECT x.kategori,
                    x.haftalik_katilim,
                    x.ortalamaya_dahil,
                    COUNT(*) AS ogrenci_sayisi,
                    COALESCE(SUM(x.net_paket_tutari), 0) AS toplam_gelir,
                    COALESCE(AVG(x.net_paket_tutari), 0) AS ortalama_gelir
             FROM (
                SELECT p.id,
                       p.net_paket_tutari,
                       COALESCE(NULLIF(p.haftalik_katilim_sayisi, 0), 1) AS haftalik_katilim,
                       CASE
                           WHEN p.toplam_normal_hak <= 1
                             OR p.paket_adi LIKE "%Tanisma%"
                             OR p.paket_adi LIKE "%Tanışma%"
                             OR p.paket_adi LIKE "%Tek Ders%"
                             OR p.paket_adi LIKE "%tek ders%"
                           THEN "Tanisma / Tek Ders"
                           ELSE CONCAT("Haftada ", COALESCE(NULLIF(p.haftalik_katilim_sayisi, 0), 1), " Gun")
                       END AS kategori,
                       CASE
                           WHEN p.toplam_normal_hak <= 1
                             OR p.paket_adi LIKE "%Tanisma%"
                             OR p.paket_adi LIKE "%Tanışma%"
                             OR p.paket_adi LIKE "%Tek Ders%"
                             OR p.paket_adi LIKE "%tek ders%"
                           THEN 0
                           ELSE 1
                       END AS ortalamaya_dahil
                FROM paketler p
                INNER JOIN (
                    SELECT ogrenci_id, MAX(id) AS son_paket_id
                    FROM paketler
                    WHERE kurum_id = :kurum_id_sub AND paket_durumu = "aktif"
                    GROUP BY ogrenci_id
                ) sp ON sp.son_paket_id = p.id
                WHERE p.kurum_id = :kurum_id
             ) x
             GROUP BY x.kategori, x.haftalik_katilim, x.ortalamaya_dahil
             ORDER BY x.ortalamaya_dahil DESC, x.haftalik_katilim ASC, x.kategori ASC'
        );
        $ortalamaStmt->execute(['kurum_id' => self::kurumId(), 'kurum_id_sub' => self::kurumId()]);
        $ortalamaDagilimi = $ortalamaStmt->fetchAll();

        $dahilOgrenci = 0;
        $dahilToplam = 0.0;
        $haricOgrenci = 0;
        $haricToplam = 0.0;
        foreach ($ortalamaDagilimi as $row) {
            if ((int) ($row['ortalamaya_dahil'] ?? 0) === 1) {
                $dahilOgrenci += (int) ($row['ogrenci_sayisi'] ?? 0);
                $dahilToplam += (float) ($row['toplam_gelir'] ?? 0);
                continue;
            }
            $haricOgrenci += (int) ($row['ogrenci_sayisi'] ?? 0);
            $haricToplam += (float) ($row['toplam_gelir'] ?? 0);
        }

        $gelir = [
            'paketli_ogrenci' => $dahilOgrenci,
            'mevcut_gelir' => $dahilToplam,
            'ortalama_ogrenci_geliri' => $dahilOgrenci > 0 ? $dahilToplam / $dahilOgrenci : 0,
        ];

        $ortalama = (float) ($gelir['ortalama_ogrenci_geliri'] ?? 0);
        if ($ortalama <= 0) {
            $ortalamaFallbackStmt = $db->prepare(
                'SELECT COALESCE(AVG(p.net_paket_tutari), 0)
                 FROM paketler p
                 INNER JOIN (
                    SELECT ogrenci_id, MAX(id) AS son_paket_id
                    FROM paketler
                    WHERE kurum_id = :kurum_id_sub
                      AND paket_durumu <> "iptal"
                      AND toplam_normal_hak > 1
                    GROUP BY ogrenci_id
                 ) sp ON sp.son_paket_id = p.id'
            );
            $ortalamaFallbackStmt->execute(['kurum_id_sub' => self::kurumId()]);
            $ortalama = (float) $ortalamaFallbackStmt->fetchColumn();
        }

        $maksimum = (int) ($kapasite['maksimum_ogrenci'] ?? 0);
        $ortalamaDersStmt = $db->prepare(
            'SELECT COALESCE(AVG(p.net_paket_tutari / NULLIF(p.toplam_normal_hak, 0)), 0)
             FROM paketler p
             INNER JOIN (
                SELECT ogrenci_id, MAX(id) AS son_paket_id
                FROM paketler
                WHERE kurum_id = :kurum_id_sub AND paket_durumu = "aktif" AND toplam_normal_hak > 0
                GROUP BY ogrenci_id
             ) sp ON sp.son_paket_id = p.id'
        );
        $ortalamaDersStmt->execute(['kurum_id_sub' => self::kurumId()]);
        $ortalamaDersGeliri = (float) $ortalamaDersStmt->fetchColumn();

        $programStmt = $db->prepare(
            'SELECT dp.id AS program_id, g.ad AS program_adi, g.yas_araligi,
                    g.kontenjan, COALESCE(g.durum, "durum_yok") AS durum,
                    dp.gun, dp.baslangic_saati, dp.bitis_saati,
                    COUNT(DISTINCT r.ogrenci_id) AS ogrenci_sayisi,
                    COUNT(DISTINCT CASE
                        WHEN p.id IS NOT NULL AND p.toplam_normal_hak > 0 THEN r.ogrenci_id
                        ELSE NULL
                    END) AS gelire_dahil_ogrenci,
                    COALESCE(SUM(
                        CASE
                            WHEN p.id IS NOT NULL AND p.toplam_normal_hak > 0
                            THEN p.net_paket_tutari / p.toplam_normal_hak
                            ELSE 0
                        END
                    ), 0) AS tahmini_gelir
             FROM ders_programlari dp
             INNER JOIN gruplar g ON g.id = dp.grup_id AND g.kurum_id = dp.kurum_id
             LEFT JOIN randevular r ON r.grup_id = g.id
                AND r.kurum_id = dp.kurum_id
                AND r.tarih BETWEEN :hafta_baslangici AND :hafta_bitisi
                AND WEEKDAY(r.tarih) + 1 = dp.gun
                AND r.baslangic_saati = dp.baslangic_saati
                AND COALESCE(r.durum, "planlandi") NOT IN ("iptal", "kurum_iptali", "ertelendi")
             LEFT JOIN paketler p ON p.id = r.paket_id AND p.kurum_id = dp.kurum_id AND p.paket_durumu <> "iptal"
             WHERE dp.kurum_id = :kurum_id
               AND dp.aktif = 1
               AND (g.aktif = 1 OR g.durum IN ("kayit_acik", "yeni_grup", "kontenjan_sinirli", "doldu"))
             GROUP BY dp.id
             ORDER BY dp.gun ASC, dp.baslangic_saati ASC, g.ad ASC'
        );
        $programStmt->execute([
            'hafta_baslangici' => $haftaBaslangici,
            'hafta_bitisi' => $haftaBitisi,
            'kurum_id' => self::kurumId(),
        ]);
        $programlar = $programStmt->fetchAll();

        $programlar = array_map(static function (array $row) use ($ortalamaDersGeliri): array {
            $kontenjan = (int) ($row['kontenjan'] ?? 0);
            $ogrenciSayisi = (int) ($row['ogrenci_sayisi'] ?? 0);
            $gelireDahilOgrenci = (int) ($row['gelire_dahil_ogrenci'] ?? 0);
            $tahminiGelir = (float) ($row['tahmini_gelir'] ?? 0);
            $grupOrtalamasi = $gelireDahilOgrenci > 0
                ? $tahminiGelir / $gelireDahilOgrenci
                : $ortalamaDersGeliri;
            return array_merge($row, [
                'bos_kontenjan' => max(0, $kontenjan - $ogrenciSayisi),
                'doluluk_orani' => $kontenjan > 0 ? min(999, ($ogrenciSayisi / $kontenjan) * 100) : 0,
                'tahmini_gelir' => $tahminiGelir,
                'ortalama_ders_geliri' => $grupOrtalamasi,
                'maksimum_gelir' => $kontenjan * $grupOrtalamasi,
            ]);
        }, $programlar);

        $mevcutDersSayisi = array_sum(array_map(
            static fn(array $row): int => (int) ($row['ogrenci_sayisi'] ?? 0),
            $programlar
        ));
        $gelireDahilDersSayisi = array_sum(array_map(
            static fn(array $row): int => (int) ($row['gelire_dahil_ogrenci'] ?? 0),
            $programlar
        ));
        $haftalikTahminiGelir = array_sum(array_map(
            static fn(array $row): float => (float) ($row['tahmini_gelir'] ?? 0),
            $programlar
        ));
        $dersBasiOrtalama = $gelireDahilDersSayisi > 0
            ? $haftalikTahminiGelir / $gelireDahilDersSayisi
            : $ortalamaDersGeliri;
        $bosKontenjan = max(0, $maksimum - $mevcutDersSayisi);
        $doluluk = $maksimum > 0 ? min(999, ($mevcutDersSayisi / $maksimum) * 100) : 0;

        $senaryoSayilari = array_values(array_unique(array_filter([
            $mevcutDersSayisi,
            (int) ceil($maksimum * 0.5),
            (int) ceil($maksimum * 0.75),
            (int) ceil($maksimum * 0.9),
            $maksimum,
        ], static fn(int $deger): bool => $deger > 0)));
        sort($senaryoSayilari);

        return [
            'grup_satiri' => (int) ($kapasite['grup_satiri'] ?? 0),
            'maksimum_ogrenci' => $maksimum,
            'maksimum_ders_sayisi' => $maksimum,
            'mevcut_kayit' => $mevcutDersSayisi,
            'mevcut_ders_sayisi' => $mevcutDersSayisi,
            'dolu_kontenjan' => $mevcutDersSayisi,
            'tekil_ogrenci' => (int) ($kapasite['tekil_ogrenci'] ?? 0),
            'paketli_ogrenci' => (int) ($gelir['paketli_ogrenci'] ?? 0),
            'mevcut_gelir' => $haftalikTahminiGelir,
            'ortalama_ogrenci_geliri' => $ortalama,
            'ortalama_ders_geliri' => $dersBasiOrtalama,
            'maksimum_gelir' => $maksimum * $dersBasiOrtalama,
            'bos_kontenjan' => $bosKontenjan,
            'bos_kontenjan_gelir_potansiyeli' => $bosKontenjan * $dersBasiOrtalama,
            'doluluk_orani' => $doluluk,
            'senaryolar' => array_map(static fn(int $adet): array => [
                'ogrenci_sayisi' => $adet,
                'tahmini_gelir' => $adet * $dersBasiOrtalama,
                'doluluk_orani' => $maksimum > 0 ? min(999, ($adet / $maksimum) * 100) : 0,
            ], $senaryoSayilari),
            'programlar' => $programlar,
            'ortalama_detay' => [
                'dahil_ogrenci' => $dahilOgrenci,
                'dahil_toplam' => $dahilToplam,
                'haric_ogrenci' => $haricOgrenci,
                'haric_toplam' => $haricToplam,
            ],
            'ortalama_dagilimi' => $ortalamaDagilimi,
            'hafta_baslangici' => $haftaBaslangici,
            'hafta_bitisi' => $haftaBitisi,
        ];
    }

    public static function odemeSozleri(): array
    {
        $stmt = self::db()->prepare(
            'SELECT os.soz_verilen_tarih, CONCAT(o.ad, " ", o.soyad) AS ogrenci,
                    os.soz_verilen_tutar, os.durum
             FROM odeme_sozleri os
             INNER JOIN ogrenciler o ON o.id = os.ogrenci_id AND o.kurum_id = os.kurum_id
             WHERE os.kurum_id = :kurum_id AND os.durum IN ("bekleniyor","bugun_odenecek","gecikti","yeni_tarih_verildi")
             ORDER BY os.soz_verilen_tarih ASC
             LIMIT 10'
        );
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function kayitYenilemeleri(): array
    {
        $stmt = self::db()->prepare(
            'SELECT p.id AS paket_id, CONCAT(o.ad, " ", o.soyad) AS ogrenci, p.paket_adi,
                    p.tahmini_son_ders_tarihi, p.kalan_normal_hak, p.kalan_telafi_hak, p.yenileme_durumu,
                    p.net_paket_tutari AS paket_ucreti,
                    0 AS mevcut_tahsilat,
                    p.net_paket_tutari AS yenileme_ucreti,
                    "odeme_bekliyor" AS odeme_durumu
             FROM paketler p
             INNER JOIN ogrenciler o ON o.id = p.ogrenci_id AND o.kurum_id = p.kurum_id
             INNER JOIN (
                SELECT ogrenci_id, MAX(id) AS son_paket_id
                FROM paketler
                WHERE kurum_id = :kurum_id_sub AND paket_durumu = "aktif" AND toplam_normal_hak > 1
                GROUP BY ogrenci_id
             ) sp ON sp.son_paket_id = p.id
             WHERE p.kurum_id = :kurum_id
                AND p.paket_durumu <> "iptal"
                AND p.toplam_normal_hak > 1
                AND p.tahmini_son_ders_tarihi IS NOT NULL
                AND p.tahmini_son_ders_tarihi >= CURDATE()
             ORDER BY p.tahmini_son_ders_tarihi ASC, o.ad ASC, o.soyad ASC
             LIMIT 100'
        );
        $stmt->execute(['kurum_id' => self::kurumId(), 'kurum_id_sub' => self::kurumId()]);
        $satirlar = $stmt->fetchAll();

        $haftalar = [];
        foreach ($satirlar as $satir) {
            $zaman = strtotime((string) $satir['tahmini_son_ders_tarihi']);
            $anahtar = $zaman ? date('o-W', $zaman) : 'belirsiz';
            if (!isset($haftalar[$anahtar])) {
                $haftalar[$anahtar] = [
                    'hafta' => $zaman ? date('W', $zaman) . '. Hafta' : 'Belirsiz',
                    'tarih_araligi' => self::haftaAraligi($zaman),
                    'gelecek_bakiye' => 0.0,
                    'kayitlar' => [],
                ];
            }
            $haftalar[$anahtar]['gelecek_bakiye'] += (float) ($satir['yenileme_ucreti'] ?? 0);
            $haftalar[$anahtar]['kayitlar'][] = $satir;
        }

        return array_values($haftalar);
    }

    public static function kayitYenilemeBakiyesi(): float
    {
        return (float) self::tekDeger(
            'SELECT COALESCE(SUM(p.net_paket_tutari), 0)
             FROM paketler p
             INNER JOIN (
                SELECT ogrenci_id, MAX(id) AS son_paket_id
                FROM paketler
                WHERE kurum_id = :kurum_id_sub AND paket_durumu = "aktif" AND toplam_normal_hak > 1
                GROUP BY ogrenci_id
             ) sp ON sp.son_paket_id = p.id
             WHERE p.kurum_id = :kurum_id
                AND p.paket_durumu <> "iptal"
                AND p.toplam_normal_hak > 1
                AND p.tahmini_son_ders_tarihi IS NOT NULL
                AND p.tahmini_son_ders_tarihi >= CURDATE()',
            ['kurum_id_sub' => self::kurumId()]
        );
    }

    public static function kayitYenilemeTakvimi(?int $gun = null): array
    {
        $tarihKosulu = '';
        if ($gun !== null) {
            $gun = max(1, min(365, $gun));
            $tarihKosulu = ' AND p.tahmini_son_ders_tarihi <= DATE_ADD(CURDATE(), INTERVAL :gun DAY)';
        }

        $stmt = self::db()->prepare(
            'SELECT p.id AS paket_id, o.id AS ogrenci_id, p.tahmini_son_ders_tarihi AS tarih,
                    CONCAT(o.ad, " ", o.soyad) AS ogrenci, p.paket_adi,
                    p.net_paket_tutari AS paket_ucreti,
                    0 AS mevcut_tahsilat,
                    p.net_paket_tutari AS yenileme_ucreti,
                    p.kalan_normal_hak, p.kalan_telafi_hak, p.yenileme_durumu,
                    "odeme_bekliyor" AS odeme_durumu
             FROM paketler p
             INNER JOIN ogrenciler o ON o.id = p.ogrenci_id AND o.kurum_id = p.kurum_id
             INNER JOIN (
                SELECT ogrenci_id, MAX(id) AS son_paket_id
                FROM paketler
                WHERE kurum_id = :kurum_id_sub AND paket_durumu = "aktif" AND toplam_normal_hak > 1
                GROUP BY ogrenci_id
             ) sp ON sp.son_paket_id = p.id
             WHERE p.kurum_id = :kurum_id
                AND p.paket_durumu <> "iptal"
                AND p.toplam_normal_hak > 1
                AND p.tahmini_son_ders_tarihi IS NOT NULL
                AND p.tahmini_son_ders_tarihi >= CURDATE()' . $tarihKosulu . '
             ORDER BY p.tahmini_son_ders_tarihi ASC, o.ad ASC, o.soyad ASC'
        );
        if ($gun !== null) {
            $stmt->bindValue('gun', $gun, \PDO::PARAM_INT);
        }
        $stmt->bindValue('kurum_id', self::kurumId(), \PDO::PARAM_INT);
        $stmt->bindValue('kurum_id_sub', self::kurumId(), \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private static function haftaAraligi($zaman): string
    {
        if (!$zaman) {
            return '-';
        }

        $tarih = new \DateTimeImmutable(date('Y-m-d', $zaman));
        $baslangic = $tarih->modify('monday this week');
        $bitis = $tarih->modify('sunday this week');
        return $baslangic->format('d.m.Y') . ' - ' . $bitis->format('d.m.Y');
    }

    public static function yaklasanTahsilatlar(): array
    {
        return self::tahsilatBeklentileri('BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)', 'ASC');
    }

    public static function gecikmisTahsilatlar(): array
    {
        return self::tahsilatBeklentileri('< CURDATE()', 'ASC');
    }

    private static function tahsilatBeklentileri(string $tarihKosulu, string $siralama): array
    {
        $stmt = self::db()->prepare(
            'SELECT p.id AS paket_id, CONCAT(o.ad, " ", o.soyad) AS ogrenci, p.paket_adi,
                    COALESCE(ir.ilk_randevu_tarihi, p.baslangic_tarihi) AS odeme_vade_tarihi,
                    p.tahmini_son_ders_tarihi AS kayit_yenileme_tarihi,
                    p.kalan_normal_hak AS kalan_ders,
                    p.kalan_telafi_hak AS kalan_telafi,
                    p.net_paket_tutari AS paket_tutari,
                    COALESCE(od.tahsilat, 0) AS mevcut_tahsilat,
                    p.net_paket_tutari - COALESCE(od.tahsilat, 0) AS mevcut_kalan_borc,
                    p.net_paket_tutari - COALESCE(od.tahsilat, 0) AS beklenen_tahsilat
             FROM paketler p
             INNER JOIN ogrenciler o ON o.id = p.ogrenci_id AND o.kurum_id = p.kurum_id
             INNER JOIN (
                SELECT ogrenci_id, MAX(id) AS son_paket_id
                FROM paketler
                WHERE kurum_id = :kurum_id_sp AND paket_durumu = "aktif"
                GROUP BY ogrenci_id
             ) sp ON sp.son_paket_id = p.id
             LEFT JOIN (
                SELECT paket_id, MIN(tarih) AS ilk_randevu_tarihi
                FROM randevular
                WHERE kurum_id = :kurum_id_ir AND paket_id IS NOT NULL
                GROUP BY paket_id
             ) ir ON ir.paket_id = p.id
             LEFT JOIN (
                SELECT paket_id, SUM(tutar) AS tahsilat
                FROM odemeler
                WHERE kurum_id = :kurum_id_od AND iptal = 0
                GROUP BY paket_id
             ) od ON od.paket_id = p.id
             WHERE p.kurum_id = :kurum_id
                AND COALESCE(ir.ilk_randevu_tarihi, p.baslangic_tarihi) ' . $tarihKosulu . '
                AND p.net_paket_tutari - COALESCE(od.tahsilat, 0) > 0
             ORDER BY odeme_vade_tarihi ' . $siralama . ', mevcut_kalan_borc DESC
             LIMIT 30'
        );
        $kurumId = self::kurumId();
        $stmt->execute([
            'kurum_id' => $kurumId,
            'kurum_id_sp' => $kurumId,
            'kurum_id_ir' => $kurumId,
            'kurum_id_od' => $kurumId,
        ]);
        return $stmt->fetchAll();
    }

    private static function tahsilatBeklentisiToplami(string $tarihKosulu): float
    {
        $stmt = self::db()->prepare(
            'SELECT COALESCE(SUM(x.kalan_borc), 0)
             FROM (
                SELECT p.id,
                       p.net_paket_tutari - COALESCE(od.tahsilat, 0) AS kalan_borc
                FROM paketler p
                INNER JOIN (
                    SELECT ogrenci_id, MAX(id) AS son_paket_id
                    FROM paketler
                    WHERE kurum_id = :kurum_id_sp AND paket_durumu = "aktif"
                    GROUP BY ogrenci_id
                ) sp ON sp.son_paket_id = p.id
                LEFT JOIN (
                    SELECT paket_id, MIN(tarih) AS ilk_randevu_tarihi
                    FROM randevular
                    WHERE kurum_id = :kurum_id_ir AND paket_id IS NOT NULL
                    GROUP BY paket_id
                ) ir ON ir.paket_id = p.id
                LEFT JOIN (
                    SELECT paket_id, SUM(tutar) AS tahsilat
                    FROM odemeler
                    WHERE kurum_id = :kurum_id_od AND iptal = 0
                    GROUP BY paket_id
                ) od ON od.paket_id = p.id
                WHERE p.kurum_id = :kurum_id
                  AND COALESCE(ir.ilk_randevu_tarihi, p.baslangic_tarihi) ' . $tarihKosulu . '
             ) x
             WHERE x.kalan_borc > 0'
        );
        $kurumId = self::kurumId();
        $stmt->execute([
            'kurum_id' => $kurumId,
            'kurum_id_sp' => $kurumId,
            'kurum_id_ir' => $kurumId,
            'kurum_id_od' => $kurumId,
        ]);
        return (float) $stmt->fetchColumn();
    }

    public static function sayfaVerisi(?string $kapasiteHaftasi = null): array
    {
        return [
            'ozet' => self::raporSayfaOzeti(),
            'aylik_tahsilat' => self::aylikTahsilat(),
            'randevu_durumlari' => self::randevuDurumDagilimi(),
            'paket_performansi' => self::paketPerformansi(),
            'kapasite_gelir' => self::kapasiteGelirRaporu($kapasiteHaftasi),
            'kayit_yenileme_takvimi' => self::kayitYenilemeTakvimi(),
        ];
    }

    private static function raporSayfaOzeti(): array
    {
        $kurumId = self::kurumId();
        $stmt = self::db()->prepare(
            'SELECT
                (SELECT COALESCE(SUM(tutar), 0)
                 FROM odemeler
                 WHERE kurum_id = :kurum_id_aylik AND iptal = 0
                   AND tarih >= DATE_FORMAT(CURDATE(), "%Y-%m-01")
                   AND tarih <= LAST_DAY(CURDATE())) AS bu_ay_tahsilat,
                (SELECT COUNT(*)
                 FROM randevular
                 WHERE kurum_id = :kurum_id_randevu
                   AND durum IN ("gelmedi", "mazeretli_gelmedi", "gec_iptal")
                   AND tarih >= DATE_FORMAT(CURDATE(), "%Y-%m-01")
                   AND tarih <= LAST_DAY(CURDATE())) AS gelmeyen_randevu,
                COALESCE(SUM(GREATEST(p.net_paket_tutari - COALESCE(od.tahsilat, 0), 0)), 0) AS bekleyen_alacak
             FROM paketler p
             LEFT JOIN (
                SELECT paket_id, SUM(tutar) AS tahsilat
                FROM odemeler
                WHERE kurum_id = :kurum_id_odeme AND iptal = 0
                GROUP BY paket_id
             ) od ON od.paket_id = p.id
             WHERE p.kurum_id = :kurum_id_paket AND p.paket_durumu = "aktif"'
        );
        $stmt->execute([
            'kurum_id_aylik' => $kurumId,
            'kurum_id_randevu' => $kurumId,
            'kurum_id_odeme' => $kurumId,
            'kurum_id_paket' => $kurumId,
        ]);
        $ozet = $stmt->fetch() ?: [];

        return [
            'bu_ay_tahsilat' => (float) ($ozet['bu_ay_tahsilat'] ?? 0),
            'bekleyen_alacak' => (float) ($ozet['bekleyen_alacak'] ?? 0),
            'gelmeyen_randevu' => (int) ($ozet['gelmeyen_randevu'] ?? 0),
            'yaklasan_tahsilat_beklentisi' => self::tahsilatBeklentisiToplami('BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)'),
            'gecikmis_tahsilat_beklentisi' => self::tahsilatBeklentisiToplami('< CURDATE()'),
            'kayit_yenileme_bakiyesi' => self::kayitYenilemeBakiyesi(),
        ];
    }

    private static function yasAraligiAy(string $deger): array
    {
        if (preg_match('/(\d+)\s*-\s*(\d+)\s*Ay/u', $deger, $eslesme)) {
            return [(int) $eslesme[1], (int) $eslesme[2]];
        }

        if (preg_match('/(\d+)\s*Ay/u', $deger, $eslesme)) {
            $ay = (int) $eslesme[1];
            return [$ay, $ay];
        }

        return [null, null];
    }
}
