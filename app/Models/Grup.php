<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Model;
final class Grup extends Model
{
    public static function liste(): array
    {
        [$haftaBaslangic, $haftaBitis] = self::mevcutHaftaAraligi();
        $stmt = self::db()->prepare(
            'SELECT g.id, g.ad, g.yas_araligi, g.kontenjan, g.aktif, g.durum, g.aciklama,
                    COUNT(DISTINCT rw.ogrenci_id) AS ogrenci_sayisi
             FROM gruplar g
             LEFT JOIN randevular rw
               ON rw.grup_id = g.id
              AND rw.kurum_id = g.kurum_id
              AND rw.tarih BETWEEN :hafta_baslangic AND :hafta_bitis
              AND COALESCE(rw.durum, "planlandi") NOT IN ("iptal", "kurum_iptali")
             WHERE g.kurum_id = :kurum_id
             GROUP BY g.id
             ORDER BY g.aktif DESC, g.ad ASC
             LIMIT 100'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'hafta_baslangic' => $haftaBaslangic,
            'hafta_bitis' => $haftaBitis,
        ]);
        return $stmt->fetchAll();
    }

    public static function secenekler(): array
    {
        $stmt = self::db()->prepare(
            'SELECT id, ad, yas_araligi, aktif
             FROM gruplar
             WHERE kurum_id = :kurum_id
             ORDER BY aktif DESC, ad ASC, id ASC'
        );
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function programListe(): array
    {
        [$haftaBaslangic, $haftaBitis] = self::mevcutHaftaAraligi();
        $stmt = self::db()->prepare(
            'SELECT dp.id, dp.grup_id, dp.gun, dp.baslangic_saati, dp.bitis_saati,
                    g.yas_araligi, g.ad AS program_adi, COALESCE(g.durum, "durum_yok") AS durum,
                    g.kontenjan, g.aktif,
                    COUNT(DISTINCT rw.ogrenci_id) AS ogrenci_sayisi
             FROM ders_programlari dp
             INNER JOIN gruplar g ON g.id = dp.grup_id AND g.kurum_id = dp.kurum_id
             LEFT JOIN randevular rw
               ON rw.grup_id = g.id
              AND rw.kurum_id = g.kurum_id
              AND rw.tarih BETWEEN :hafta_baslangic AND :hafta_bitis
              AND WEEKDAY(rw.tarih) + 1 = dp.gun
              AND rw.baslangic_saati >= dp.baslangic_saati
              AND rw.baslangic_saati < dp.bitis_saati
              AND COALESCE(rw.durum, "planlandi") NOT IN ("iptal", "kurum_iptali")
             WHERE dp.kurum_id = :kurum_id
             GROUP BY dp.id
             ORDER BY dp.gun ASC, dp.baslangic_saati ASC, dp.bitis_saati ASC, g.ad ASC'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'hafta_baslangic' => $haftaBaslangic,
            'hafta_bitis' => $haftaBitis,
        ]);
        return $stmt->fetchAll();
    }

    private static function mevcutHaftaAraligi(?string $referansTarihi = null): array
    {
        $referans = new \DateTimeImmutable($referansTarihi ?: date('Y-m-d'));
        $baslangic = $referans->modify('-' . ((int) $referans->format('N') - 1) . ' days');
        $bitis = $baslangic->modify('+6 days');

        return [$baslangic->format('Y-m-d'), $bitis->format('Y-m-d')];
    }

    private static function ayYasi(?string $dogumTarihi, string $referansTarihi): ?int
    {
        if (!$dogumTarihi) {
            return null;
        }

        try {
            $dogum = new \DateTimeImmutable($dogumTarihi);
            $referans = new \DateTimeImmutable($referansTarihi);
        } catch (\Throwable $e) {
            return null;
        }

        if ($dogum > $referans) {
            return null;
        }

        $ay = (((int) $referans->format('Y') - (int) $dogum->format('Y')) * 12)
            + ((int) $referans->format('m') - (int) $dogum->format('m'));

        if ((int) $referans->format('d') < (int) $dogum->format('d')) {
            $ay--;
        }

        return max(0, $ay);
    }

    public static function bosKontenjanTakvimi(?string $baslangicTarihi = null, ?string $bitisTarihi = null): array
    {
        $baslangic = new \DateTimeImmutable($baslangicTarihi ?: date('Y-m-d', strtotime('monday this week')));
        $bitis = new \DateTimeImmutable($bitisTarihi ?: date('Y-m-d', strtotime('sunday this week')));
        if ($bitis < $baslangic) {
            [$baslangic, $bitis] = [$bitis, $baslangic];
        }

        $programStmt = self::db()->prepare(
            'SELECT dp.id, dp.grup_id, dp.gun, dp.baslangic_saati, dp.bitis_saati,
                    g.ad AS program_adi, g.yas_araligi, COALESCE(g.durum, "durum_yok") AS durum,
                    g.kontenjan
             FROM ders_programlari dp
             INNER JOIN gruplar g ON g.id = dp.grup_id AND g.kurum_id = dp.kurum_id
             WHERE dp.kurum_id = :kurum_id
               AND dp.aktif = 1
             ORDER BY dp.gun ASC, dp.baslangic_saati ASC, g.ad ASC'
        );
        $programStmt->execute(self::kurumParam());
        $programlar = $programStmt->fetchAll();

        $randevuStmt = self::db()->prepare(
            'SELECT eslesen.grup_id, r.tarih, r.ogrenci_id, CONCAT(o.ad, " ", o.soyad) AS ogrenci, o.dogum_tarihi
             FROM randevular r
             INNER JOIN (
                 SELECT r2.id AS randevu_id, MIN(dp.grup_id) AS grup_id
                 FROM randevular r2
                 INNER JOIN ders_programlari dp
                   ON dp.aktif = 1
                  AND dp.kurum_id = r2.kurum_id
                  AND dp.gun = WEEKDAY(r2.tarih) + 1
                  AND r2.baslangic_saati >= dp.baslangic_saati
                  AND r2.baslangic_saati < dp.bitis_saati
                 INNER JOIN gruplar g
                   ON g.id = dp.grup_id
                  AND g.kurum_id = dp.kurum_id
                  AND g.aktif = 1
                 WHERE r2.tarih BETWEEN :baslangic_match AND :bitis_match
                   AND r2.kurum_id = :kurum_id_match
                   AND COALESCE(r2.durum, "planlandi") NOT IN ("iptal", "kurum_iptali")
                 GROUP BY r2.id
             ) eslesen ON eslesen.randevu_id = r.id
             INNER JOIN ogrenciler o ON o.id = r.ogrenci_id AND o.kurum_id = r.kurum_id
             WHERE r.tarih BETWEEN :baslangic AND :bitis
               AND r.kurum_id = :kurum_id
             ORDER BY o.ad ASC, o.soyad ASC'
        );
        $randevuStmt->execute([
            'baslangic_match' => $baslangic->format('Y-m-d'),
            'bitis_match' => $bitis->format('Y-m-d'),
            'kurum_id_match' => self::kurumId(),
            'baslangic' => $baslangic->format('Y-m-d'),
            'bitis' => $bitis->format('Y-m-d'),
            'kurum_id' => self::kurumId(),
        ]);

        $randevular = [];
        foreach ($randevuStmt->fetchAll() as $randevu) {
            $randevular[(int) $randevu['grup_id']][(string) $randevu['tarih']][(int) $randevu['ogrenci_id']] = $randevu;
        }

        $oncekiHaftaBaslangic = $baslangic->modify('-7 days');
        $oncekiHaftaBitis = $bitis->modify('-7 days');
        $sonDersiBitenStmt = self::db()->prepare(
            'SELECT eslesen.grup_id,
                    DATE_ADD(r.tarih, INTERVAL 7 DAY) AS hedef_tarih,
                    r.ogrenci_id,
                    CONCAT(o.ad, " ", o.soyad) AS ogrenci,
                    r.tarih AS son_ders_tarihi
             FROM randevular r
             INNER JOIN paketler p
               ON p.id = r.paket_id
              AND p.kurum_id = r.kurum_id
              AND p.tahmini_son_ders_tarihi = r.tarih
              AND p.toplam_normal_hak > 1
              AND p.paket_durumu <> "iptal"
             INNER JOIN (
                 SELECT r2.id AS randevu_id, MIN(dp.grup_id) AS grup_id
                 FROM randevular r2
                 INNER JOIN ders_programlari dp
                   ON dp.aktif = 1
                  AND dp.kurum_id = r2.kurum_id
                  AND dp.gun = WEEKDAY(r2.tarih) + 1
                  AND r2.baslangic_saati >= dp.baslangic_saati
                  AND r2.baslangic_saati < dp.bitis_saati
                 INNER JOIN gruplar g
                   ON g.id = dp.grup_id
                  AND g.kurum_id = dp.kurum_id
                  AND g.aktif = 1
                 WHERE r2.tarih BETWEEN :onceki_baslangic_match AND :onceki_bitis_match
                   AND r2.kurum_id = :kurum_id_match
                   AND r2.paket_id IS NOT NULL
                   AND r2.telafi_hakki_id IS NULL
                   AND COALESCE(r2.durum, "planlandi") NOT IN ("iptal", "kurum_iptali")
                 GROUP BY r2.id
             ) eslesen ON eslesen.randevu_id = r.id
             INNER JOIN ogrenciler o ON o.id = r.ogrenci_id AND o.kurum_id = r.kurum_id
             WHERE r.tarih BETWEEN :onceki_baslangic AND :onceki_bitis
               AND r.kurum_id = :kurum_id
               AND r.telafi_hakki_id IS NULL
               AND COALESCE(r.durum, "planlandi") NOT IN ("iptal", "kurum_iptali")
             ORDER BY o.ad ASC, o.soyad ASC'
        );
        $sonDersiBitenStmt->execute([
            'onceki_baslangic_match' => $oncekiHaftaBaslangic->format('Y-m-d'),
            'onceki_bitis_match' => $oncekiHaftaBitis->format('Y-m-d'),
            'kurum_id_match' => self::kurumId(),
            'onceki_baslangic' => $oncekiHaftaBaslangic->format('Y-m-d'),
            'onceki_bitis' => $oncekiHaftaBitis->format('Y-m-d'),
            'kurum_id' => self::kurumId(),
        ]);

        $oncekiHaftaDersiBitenler = [];
        foreach ($sonDersiBitenStmt->fetchAll() as $randevu) {
            $oncekiHaftaDersiBitenler[(int) $randevu['grup_id']][(string) $randevu['hedef_tarih']][(int) $randevu['ogrenci_id']] = $randevu;
        }

        $sonuc = [];
        $period = new \DatePeriod($baslangic, new \DateInterval('P1D'), $bitis->modify('+1 day'));
        foreach ($period as $gun) {
            $gunNo = (int) $gun->format('N');
            $tarih = $gun->format('Y-m-d');
            foreach ($programlar as $program) {
                if ((int) $program['gun'] !== $gunNo) {
                    continue;
                }

                $aktifUyeler = array_values($randevular[(int) $program['grup_id']][$tarih] ?? []);
                $sonDersiBitenUyeler = array_values(array_filter(
                    $oncekiHaftaDersiBitenler[(int) $program['grup_id']][$tarih] ?? [],
                    static fn(array $uye): bool => !isset($randevular[(int) $program['grup_id']][$tarih][(int) $uye['ogrenci_id']])
                ));

                $kontenjan = (int) ($program['kontenjan'] ?? 0);
                $ogrenciSayisi = count($aktifUyeler);
                $fazlaKontenjan = max($ogrenciSayisi - $kontenjan, 0);
                $ayYaslari = array_values(array_filter(
                    array_map(static fn(array $uye): ?int => self::ayYasi((string) ($uye['dogum_tarihi'] ?? ''), $tarih), $aktifUyeler),
                    static fn(?int $ay): bool => $ay !== null
                ));
                $ortalamaAy = $ayYaslari
                    ? round(array_sum($ayYaslari) / count($ayYaslari), 1)
                    : null;
                $sonuc[] = [
                    'id' => (int) $program['id'],
                    'grup_id' => (int) $program['grup_id'],
                    'gun' => $gunNo,
                    'tarih' => $tarih,
                    'baslangic_saati' => $program['baslangic_saati'],
                    'bitis_saati' => $program['bitis_saati'],
                    'program_adi' => $program['program_adi'],
                    'yas_araligi' => $program['yas_araligi'],
                    'durum' => $program['durum'],
                    'kontenjan' => $kontenjan,
                    'ogrenci_sayisi' => $ogrenciSayisi,
                    'bos_kontenjan' => max($kontenjan - $ogrenciSayisi, 0),
                    'fazla_kontenjan' => $fazlaKontenjan,
                    'ortalama_ay' => $ortalamaAy,
                    'ogrenciler' => implode('||', array_map(static fn(array $uye): string => (string) $uye['ogrenci'], $aktifUyeler)),
                    'gecen_hafta_dersi_biten_sayisi' => count($sonDersiBitenUyeler),
                    'gecen_hafta_dersi_bitenler' => implode('||', array_map(static fn(array $uye): string => (string) $uye['ogrenci'], $sonDersiBitenUyeler)),
                ];
            }
        }

        usort($sonuc, static function (array $a, array $b): int {
            return [$a['tarih'], $a['baslangic_saati'], $a['program_adi']] <=> [$b['tarih'], $b['baslangic_saati'], $b['program_adi']];
        });

        return $sonuc;
    }

    public static function randevulariSenkronizeEt(): array
    {
        $db = self::db();
        try {
            $db->beginTransaction();

            $onceStmt = $db->prepare('SELECT COUNT(*) FROM randevular WHERE kurum_id = :kurum_id AND grup_id IS NOT NULL');
            $onceStmt->execute(self::kurumParam());
            $once = (int) $onceStmt->fetchColumn();

            $temizleStmt = $db->prepare('UPDATE randevular SET grup_id = NULL WHERE kurum_id = :kurum_id');
            $temizleStmt->execute(self::kurumParam());

            $eslestirStmt = $db->prepare(
                'UPDATE randevular r
                 INNER JOIN (
                     SELECT r2.id AS randevu_id, MIN(dp.grup_id) AS grup_id
                     FROM randevular r2
                     INNER JOIN ders_programlari dp
                       ON dp.aktif = 1
                      AND dp.kurum_id = r2.kurum_id
                      AND dp.gun = WEEKDAY(r2.tarih) + 1
                      AND r2.baslangic_saati >= dp.baslangic_saati
                      AND r2.baslangic_saati < dp.bitis_saati
                     INNER JOIN gruplar g
                       ON g.id = dp.grup_id
                      AND g.kurum_id = dp.kurum_id
                      AND g.aktif = 1
                     WHERE r2.kurum_id = :kurum_id_sub
                       AND COALESCE(r2.durum, "planlandi") NOT IN ("iptal", "kurum_iptali")
                     GROUP BY r2.id
                 ) eslesen ON eslesen.randevu_id = r.id
                 SET r.grup_id = eslesen.grup_id
                 WHERE r.kurum_id = :kurum_id'
            );
            $eslestirStmt->execute([
                'kurum_id_sub' => self::kurumId(),
                'kurum_id' => self::kurumId(),
            ]);
            $eslestir = $eslestirStmt->rowCount();

            $uyelikStmt = $db->prepare(
                'INSERT INTO grup_ogrencileri (kurum_id, grup_id, ogrenci_id, baslangic_tarihi, bitis_tarihi, aktif)
                 SELECT r.kurum_id, r.grup_id, r.ogrenci_id, MIN(r.tarih), MAX(r.tarih), 1
                 FROM randevular r
                 WHERE r.kurum_id = :kurum_id
                   AND r.grup_id IS NOT NULL
                   AND COALESCE(r.durum, "planlandi") NOT IN ("iptal", "kurum_iptali")
                 GROUP BY r.kurum_id, r.grup_id, r.ogrenci_id
                 ON DUPLICATE KEY UPDATE
                    baslangic_tarihi = VALUES(baslangic_tarihi),
                    bitis_tarihi = VALUES(bitis_tarihi),
                    aktif = 1'
            );
            $uyelikStmt->execute(self::kurumParam());
            $uyelik = $uyelikStmt->rowCount();

            $sonStmt = $db->prepare('SELECT COUNT(*) FROM randevular WHERE kurum_id = :kurum_id AND grup_id IS NOT NULL');
            $sonStmt->execute(self::kurumParam());
            $son = (int) $sonStmt->fetchColumn();

            $db->commit();

            return [
                'once_eslesen' => $once,
                'son_eslesen' => $son,
                'guncellenen_randevu' => (int) $eslestir,
                'guncellenen_uyelik' => (int) $uyelik,
            ];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function varsayilanProgramHazirla(): void
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM ders_programlari WHERE kurum_id = :kurum_id');
        $stmt->execute(self::kurumParam());
        $adet = (int) $stmt->fetchColumn();
        if ($adet > 0) {
            return;
        }

        $satirlar = [
            [1, '10:00', '11:00', '30-50 Ay', 'Yarim Gun'],
            [3, '10:00', '11:00', '30-50 Ay', 'Yarim Gun'],
            [5, '10:00', '11:00', '30-50 Ay', 'Yarim Gun'],
            [6, '10:00', '11:00', 'Ilk Adim Grubu', 'Ilk Adim Grubu'],
            [1, '15:00', '16:00', '36-38 Ay', '1.Grup'],
            [3, '15:00', '16:00', '25-36 Ay', '2.Grup'],
            [4, '15:00', '16:00', '36-38 Ay', '1.Grup'],
            [5, '15:00', '16:00', '25-36 Ay', '2.Grup'],
            [6, '15:00', '16:00', '25-36 Ay', '3.Grup'],
            [2, '16:00', '17:00', '25-36 Ay', '1.Grup'],
        ];

        foreach ($satirlar as [$gun, $baslangic, $bitis, $yas, $program]) {
            self::programEkle([
                'gun' => $gun,
                'baslangic_saati' => $baslangic,
                'bitis_saati' => $bitis,
                'yas_araligi' => $yas,
                'program_adi' => $program,
                'durum' => 'durum_yok',
                'kontenjan' => 8,
            ]);
        }
    }

    public static function ekle(array $veri): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO gruplar (kurum_id, ad, yas_araligi, kontenjan, aktif, aciklama, olusturulma_tarihi)
             VALUES (:kurum_id, :ad, :yas_araligi, :kontenjan, :aktif, :aciklama, NOW())'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'ad' => $veri['ad'],
            'yas_araligi' => $veri['yas_araligi'] ?: null,
            'kontenjan' => (int) ($veri['kontenjan'] ?: 8),
            'aktif' => (int) ($veri['aktif'] ?? 1),
            'aciklama' => $veri['aciklama'] ?: null,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function programEkle(array $veri): int
    {
        $db = self::db();
        try {
            $db->beginTransaction();

            $grupStmt = $db->prepare(
                'INSERT INTO gruplar (kurum_id, ad, yas_araligi, kontenjan, aktif, durum, aciklama, olusturulma_tarihi)
                 VALUES (:kurum_id, :ad, :yas_araligi, :kontenjan, :aktif, :durum, NULL, NOW())'
            );
            $grupStmt->execute([
                'kurum_id' => self::kurumId(),
                'ad' => $veri['program_adi'],
                'yas_araligi' => $veri['yas_araligi'] ?: null,
                'kontenjan' => (int) ($veri['kontenjan'] ?: 8),
                'aktif' => self::durumAktifMi((string) $veri['durum']) ? 1 : 0,
                'durum' => $veri['durum'] ?: 'durum_yok',
            ]);
            $grupId = (int) $db->lastInsertId();

            $programStmt = $db->prepare(
                'INSERT INTO ders_programlari (kurum_id, grup_id, gun, baslangic_saati, bitis_saati, aktif)
                 VALUES (:kurum_id, :grup_id, :gun, :baslangic_saati, :bitis_saati, 1)'
            );
            $programStmt->execute([
                'kurum_id' => self::kurumId(),
                'grup_id' => $grupId,
                'gun' => (int) $veri['gun'],
                'baslangic_saati' => self::saat((string) $veri['baslangic_saati']),
                'bitis_saati' => self::saat((string) $veri['bitis_saati']),
            ]);
            $id = (int) $db->lastInsertId();

            $db->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function programGuncelle(int $id, array $veri): bool
    {
        $mevcut = self::programBul($id);
        if (!$mevcut) {
            return false;
        }

        $db = self::db();
        try {
            $db->beginTransaction();

            $programStmt = $db->prepare(
                'UPDATE ders_programlari
                 SET gun = :gun, baslangic_saati = :baslangic_saati, bitis_saati = :bitis_saati, aktif = 1
                 WHERE id = :id AND kurum_id = :kurum_id'
            );
            $programStmt->execute([
                'id' => $id,
                'kurum_id' => self::kurumId(),
                'gun' => (int) $veri['gun'],
                'baslangic_saati' => self::saat((string) $veri['baslangic_saati']),
                'bitis_saati' => self::saat((string) $veri['bitis_saati']),
            ]);

            $grupStmt = $db->prepare(
                'UPDATE gruplar
                 SET ad = :ad, yas_araligi = :yas_araligi, kontenjan = :kontenjan, aktif = :aktif, durum = :durum
                 WHERE id = :id AND kurum_id = :kurum_id'
            );
            $grupStmt->execute([
                'id' => (int) $mevcut['grup_id'],
                'kurum_id' => self::kurumId(),
                'ad' => $veri['program_adi'],
                'yas_araligi' => $veri['yas_araligi'] ?: null,
                'kontenjan' => (int) ($veri['kontenjan'] ?: 8),
                'aktif' => self::durumAktifMi((string) $veri['durum']) ? 1 : 0,
                'durum' => $veri['durum'] ?: 'durum_yok',
            ]);

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function programSil(int $id): bool
    {
        $mevcut = self::programBul($id);
        if (!$mevcut) {
            return false;
        }

        $db = self::db();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare('DELETE FROM ders_programlari WHERE id = :id AND kurum_id = :kurum_id');
            $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);

            $sayStmt = $db->prepare('SELECT COUNT(*) FROM ders_programlari WHERE grup_id = :grup_id AND kurum_id = :kurum_id');
            $sayStmt->execute(['grup_id' => $mevcut['grup_id'], 'kurum_id' => self::kurumId()]);
            if ((int) $sayStmt->fetchColumn() === 0) {
                $pasif = $db->prepare('UPDATE gruplar SET aktif = 0 WHERE id = :id AND kurum_id = :kurum_id');
                $pasif->execute(['id' => $mevcut['grup_id'], 'kurum_id' => self::kurumId()]);
            }

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function grupOgrencileri(int $grupId): array
    {
        [$haftaBaslangic, $haftaBitis] = self::mevcutHaftaAraligi();
        $stmt = self::db()->prepare(
            'SELECT MIN(r.id) AS id, r.ogrenci_id, CONCAT(o.ad, " ", o.soyad) AS ad_soyad, MIN(r.tarih) AS baslangic_tarihi
             FROM randevular r
             INNER JOIN ogrenciler o ON o.id = r.ogrenci_id AND o.kurum_id = r.kurum_id
             WHERE r.grup_id = :grup_id
               AND r.kurum_id = :kurum_id
               AND r.tarih BETWEEN :hafta_baslangic AND :hafta_bitis
               AND COALESCE(r.durum, "planlandi") NOT IN ("iptal", "kurum_iptali")
             GROUP BY r.ogrenci_id, o.ad, o.soyad
             ORDER BY o.ad, o.soyad'
        );
        $stmt->execute([
            'grup_id' => $grupId,
            'kurum_id' => self::kurumId(),
            'hafta_baslangic' => $haftaBaslangic,
            'hafta_bitis' => $haftaBitis,
        ]);
        return $stmt->fetchAll();
    }

    public static function ogrenciAta(int $grupId, int $ogrenciId): bool
    {
        if ($grupId < 1 || $ogrenciId < 1) {
            return false;
        }

        return self::ogrenciAtaTarihle($grupId, $ogrenciId, date('Y-m-d'));
    }

    public static function ogrenciAtaCoklu(array $grupIds, int $ogrenciId): int
    {
        if ($ogrenciId < 1) {
            return 0;
        }

        $grupIds = array_values(array_unique(array_filter(array_map('intval', $grupIds), static fn(int $id): bool => $id > 0)));
        if (!$grupIds) {
            return 0;
        }

        $eklenen = 0;
        $db = self::db();
        try {
            $db->beginTransaction();
            foreach ($grupIds as $grupId) {
                if (self::ogrenciAtaTarihle($grupId, $ogrenciId, date('Y-m-d'))) {
                    $eklenen++;
                }
            }
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        return $eklenen;
    }

    public static function ogrenciAtaTarihle(int $grupId, int $ogrenciId, string $baslangicTarihi): bool
    {
        if ($grupId < 1 || $ogrenciId < 1) {
            return false;
        }

        self::grupOgrencileriTablosuHazirla();

        $stmt = self::db()->prepare(
            'INSERT INTO grup_ogrencileri (kurum_id, grup_id, ogrenci_id, baslangic_tarihi, bitis_tarihi, aktif)
             VALUES (:kurum_id, :grup_id, :ogrenci_id, :baslangic_tarihi, NULL, 1)
             ON DUPLICATE KEY UPDATE bitis_tarihi = NULL, aktif = 1'
        );
        return $stmt->execute([
            'kurum_id' => self::kurumId(),
            'grup_id' => $grupId,
            'ogrenci_id' => $ogrenciId,
            'baslangic_tarihi' => $baslangicTarihi,
        ]);
    }

    public static function randevuIcinGrupBul(string $tarih, string $baslangicSaati): int
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $tarih);
        if (!$date) {
            return 0;
        }

        $stmt = self::db()->prepare(
            'SELECT dp.grup_id
             FROM ders_programlari dp
             INNER JOIN gruplar g ON g.id = dp.grup_id AND g.kurum_id = dp.kurum_id
             WHERE dp.aktif = 1
               AND dp.kurum_id = :kurum_id
               AND g.aktif = 1
               AND dp.gun = :gun
               AND :baslangic_saati_basla >= dp.baslangic_saati
               AND :baslangic_saati_bitir < dp.bitis_saati
             ORDER BY (dp.baslangic_saati = :baslangic_saati_sirala) DESC, dp.baslangic_saati DESC, dp.id ASC
             LIMIT 1'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'gun' => (int) $date->format('N'),
            'baslangic_saati_basla' => self::saat($baslangicSaati),
            'baslangic_saati_bitir' => self::saat($baslangicSaati),
            'baslangic_saati_sirala' => self::saat($baslangicSaati),
        ]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    public static function aylikTakip(int $grupId, string $ay): array
    {
        if ($grupId < 1 || !preg_match('/^\d{4}-\d{2}$/', $ay)) {
            return [];
        }

        $ilkGun = $ay . '-01';
        $sonGun = (new \DateTimeImmutable($ilkGun))->format('Y-m-t');
        $stmt = self::db()->prepare(
            'SELECT r.id, r.ogrenci_id, CONCAT(o.ad, " ", o.soyad) AS ogrenci,
                    r.tarih, r.baslangic_saati, r.durum, r.tur, r.telafi_hakki_id,
                    COALESCE(p.paket_adi, r.tur) AS paket_adi,
                    p.toplam_normal_hak,
                    p.tahmini_son_ders_tarihi,
                    CASE
                        WHEN r.paket_id IS NULL THEN NULL
                        ELSE (
                            SELECT COUNT(*)
                            FROM randevular rx
                            WHERE rx.paket_id = r.paket_id
                              AND rx.kurum_id = r.kurum_id
                              AND rx.telafi_hakki_id IS NULL
                              AND (rx.tarih < r.tarih OR (rx.tarih = r.tarih AND rx.baslangic_saati <= r.baslangic_saati))
                        )
                    END AS ders_sirasi
             FROM randevular r
             INNER JOIN ogrenciler o ON o.id = r.ogrenci_id AND o.kurum_id = r.kurum_id
             LEFT JOIN paketler p ON p.id = r.paket_id AND p.kurum_id = r.kurum_id
             WHERE r.grup_id = :grup_id
               AND r.kurum_id = :kurum_id
               AND r.tarih BETWEEN :ilk AND :son
             ORDER BY r.tarih ASC, r.baslangic_saati ASC, o.ad ASC, o.soyad ASC'
        );
        $stmt->execute([
            'grup_id' => $grupId,
            'kurum_id' => self::kurumId(),
            'ilk' => $ilkGun,
            'son' => $sonGun,
        ]);
        return $stmt->fetchAll();
    }

    public static function ogrenciCikar(int $grupId, int $ogrenciId): bool
    {
        $stmt = self::db()->prepare('DELETE FROM grup_ogrencileri WHERE kurum_id = :kurum_id AND grup_id = :grup_id AND ogrenci_id = :ogrenci_id');
        return $stmt->execute([
            'kurum_id' => self::kurumId(),
            'grup_id' => $grupId,
            'ogrenci_id' => $ogrenciId,
        ]);
    }

    private static function programBul(int $id): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT dp.*, g.id AS grup_id
             FROM ders_programlari dp
             INNER JOIN gruplar g ON g.id = dp.grup_id AND g.kurum_id = dp.kurum_id
             WHERE dp.id = :id
               AND dp.kurum_id = :kurum_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $program = $stmt->fetch();
        return $program ?: null;
    }

    private static function grupOgrencileriTablosuHazirla(): void
    {
        $db = self::db();
        self::kolonEkle($db, 'grup_ogrencileri', 'baslangic_tarihi', 'baslangic_tarihi DATE NULL AFTER ogrenci_id');
        self::kolonEkle($db, 'grup_ogrencileri', 'bitis_tarihi', 'bitis_tarihi DATE NULL AFTER baslangic_tarihi');
        self::kolonEkle($db, 'grup_ogrencileri', 'aktif', 'aktif TINYINT(1) NOT NULL DEFAULT 1 AFTER bitis_tarihi');
        self::kolonEkle($db, 'grup_ogrencileri', 'kurum_id', 'kurum_id INT UNSIGNED NOT NULL DEFAULT 1 FIRST');
    }

    private static function kolonEkle(\PDO $db, string $tablo, string $kolon, string $tanim): void
    {
        $stmt = $db->query('SHOW COLUMNS FROM `' . $tablo . '` LIKE ' . $db->quote($kolon));
        if ($stmt->fetch()) {
            return;
        }

        try {
            $db->exec('ALTER TABLE `' . $tablo . '` ADD COLUMN ' . $tanim);
        } catch (\Throwable $e) {
            if (stripos($e->getMessage(), 'Duplicate column') === false) {
                throw $e;
            }
        }
    }

    private static function saat(string $saat): string
    {
        if (preg_match('/^\d{2}:\d{2}$/', $saat)) {
            return $saat . ':00';
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $saat)) {
            return $saat;
        }
        return '10:00:00';
    }

    private static function durumAktifMi(string $durum): bool
    {
        return !in_array($durum, ['doldu'], true);
    }
}
