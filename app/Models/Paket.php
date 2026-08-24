<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Model;
final class Paket extends Model
{
    public static function liste(): array
    {
        $stmt = self::db()->prepare(
            'SELECT p.id, CONCAT(o.ad, " ", o.soyad) AS ogrenci, p.paket_sira_no, p.paket_adi,
                    p.haftalik_katilim_sayisi, p.kalan_normal_hak, p.kalan_telafi_hak,
                    p.tahmini_son_ders_tarihi, p.net_paket_tutari,
                    COALESCE(SUM(CASE WHEN od.iptal = 0 THEN od.tutar ELSE 0 END), 0) AS tahsilat,
                    p.net_paket_tutari - COALESCE(SUM(CASE WHEN od.iptal = 0 THEN od.tutar ELSE 0 END), 0) AS kalan_borc,
                    p.paket_durumu
             FROM paketler p
             INNER JOIN ogrenciler o ON o.id = p.ogrenci_id
             LEFT JOIN odemeler od ON od.paket_id = p.id
             WHERE p.kurum_id = :kurum_id
             GROUP BY p.id
             ORDER BY p.olusturulma_tarihi DESC, p.id DESC
             LIMIT 100'
        );
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function secenekler(): array
    {
        $stmt = self::db()->prepare(
            'SELECT p.id, p.ogrenci_id, CONCAT(o.ad, " ", o.soyad, " - ", p.paket_adi, " #", p.paket_sira_no) AS etiket
             FROM paketler p
             INNER JOIN ogrenciler o ON o.id = p.ogrenci_id
             WHERE p.kurum_id = :kurum_id AND p.paket_durumu = "aktif"
             ORDER BY o.ad, o.soyad, p.paket_sira_no DESC'
        );
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function idIleBul(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM paketler WHERE id = :id AND kurum_id = :kurum_id LIMIT 1');
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $paket = $stmt->fetch();
        return $paket ?: null;
    }

    public static function sonDersTarihiGuncelle(int $paketId): void
    {
        if ($paketId < 1) {
            return;
        }

        $stmt = self::db()->prepare('SELECT MAX(tarih) FROM randevular WHERE paket_id = :paket_id AND kurum_id = :kurum_id');
        $stmt->execute(['paket_id' => $paketId, 'kurum_id' => self::kurumId()]);
        $sonTarih = $stmt->fetchColumn() ?: null;

        $guncelle = self::db()->prepare('UPDATE paketler SET tahmini_son_ders_tarihi = :tarih WHERE id = :id AND kurum_id = :kurum_id');
        $guncelle->execute([
            'id' => $paketId,
            'kurum_id' => self::kurumId(),
            'tarih' => $sonTarih,
        ]);
    }

    public static function tumSonDersTarihleriniGuncelle(): int
    {
        $stmt = self::db()->prepare('SELECT id FROM paketler WHERE kurum_id = :kurum_id');
        $stmt->execute(self::kurumParam());
        $paketler = $stmt->fetchAll();
        foreach ($paketler as $paket) {
            self::sonDersTarihiGuncelle((int) $paket['id']);
        }
        return count($paketler);
    }

    public static function sil(int $id): bool
    {
        if ($id < 1 || !self::idIleBul($id)) {
            return false;
        }

        $db = self::db();
        try {
            $db->beginTransaction();

            $randevuStmt = $db->prepare('SELECT id FROM randevular WHERE paket_id = :paket_id');
            $randevuStmt->execute(['paket_id' => $id]);
            $randevuIdleri = array_values(array_map('intval', array_column($randevuStmt->fetchAll(), 'id')));

            if ($randevuIdleri !== []) {
                $yerTutucular = implode(',', array_fill(0, count($randevuIdleri), '?'));
                $db->prepare("DELETE FROM yoklamalar WHERE randevu_id IN ($yerTutucular)")->execute($randevuIdleri);
                $db->prepare("DELETE FROM randevular WHERE id IN ($yerTutucular)")->execute($randevuIdleri);
            }

            $telafiStmt = $db->prepare('SELECT id FROM telafi_haklari WHERE paket_id = :paket_id');
            $telafiStmt->execute(['paket_id' => $id]);
            $telafiIdleri = array_values(array_map('intval', array_column($telafiStmt->fetchAll(), 'id')));
            if ($telafiIdleri !== []) {
                $yerTutucular = implode(',', array_fill(0, count($telafiIdleri), '?'));
                $db->prepare("DELETE FROM telafi_onerileri WHERE telafi_hakki_id IN ($yerTutucular)")->execute($telafiIdleri);
            }

            $db->prepare('DELETE FROM telafi_haklari WHERE paket_id = :paket_id')->execute(['paket_id' => $id]);
            $db->prepare('DELETE FROM hak_hareketleri WHERE paket_id = :paket_id')->execute(['paket_id' => $id]);
            $db->prepare('DELETE FROM odeme_sozleri WHERE paket_id = :paket_id')->execute(['paket_id' => $id]);
            $db->prepare('DELETE FROM odemeler WHERE paket_id = :paket_id')->execute(['paket_id' => $id]);
            $stmt = $db->prepare('DELETE FROM paketler WHERE id = :id');
            $stmt->execute(['id' => $id]);

            $db->commit();
            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function ekle(array $veri): int
    {
        $db = self::db();
        self::paketTablosuGerekliKolonlariHazirla($db);
        self::randevuTablosuGerekliKolonlariHazirla($db);
        self::hakHareketleriTablosuGerekliKolonlariHazirla($db);
        if ((int) ($veri['ogrenci_id'] ?? 0) < 1) {
            throw new \InvalidArgumentException('Paket icin gecerli bir ogrenci bulunamadi.');
        }
        if ((int) ($veri['olusturan_kullanici_id'] ?? 0) < 1) {
            throw new \InvalidArgumentException('Paketi olusturan kullanici bulunamadi.');
        }

        try {
            $db->beginTransaction();

            $siraStmt = $db->prepare('SELECT COALESCE(MAX(paket_sira_no), 0) + 1 FROM paketler WHERE ogrenci_id = :ogrenci_id AND kurum_id = :kurum_id');
            $siraStmt->execute(['ogrenci_id' => $veri['ogrenci_id'], 'kurum_id' => self::kurumId()]);
            $sira = (int) $siraStmt->fetchColumn();

            $haftalik = max(1, (int) $veri['haftalik_katilim_sayisi']);
            $normalHak = (int) ($veri['toplam_normal_hak'] ?: ($haftalik === 1 ? 4 : 8));
            $telafiHak = (int) ($veri['toplam_telafi_hak'] ?: ($haftalik === 1 ? 1 : 2));
            $tanismaIlkDersSayilsin = !empty($veri['tanisma_dersi_ilk_ders_sayilsin']) && $normalHak > 0;
            $baslangicKullanilanNormalHak = $tanismaIlkDersSayilsin ? 1 : 0;
            $baslangicKalanNormalHak = max(0, $normalHak - $baslangicKullanilanNormalHak);
            $listeFiyat = (float) $veri['liste_fiyati'];
            $indirim = max(0, (float) ($veri['indirim_tutari'] ?? 0));
            $net = max(0, $listeFiyat - $indirim);

            $stmt = $db->prepare(
                'INSERT INTO paketler
                 (kurum_id, ogrenci_id, paket_sira_no, paket_adi, haftalik_katilim_sayisi, toplam_normal_hak, toplam_telafi_hak,
                  kullanilan_normal_hak, kullanilan_telafi_hak, kalan_normal_hak, kalan_telafi_hak, baslangic_tarihi, tahmini_son_ders_tarihi, liste_fiyati,
                  indirim_turu, indirim_tutari, indirim_aciklama, net_paket_tutari, paket_durumu, yenileme_durumu,
                  yonetici_notu, olusturan_kullanici_id, olusturulma_tarihi)
                 VALUES
                 (:kurum_id, :ogrenci_id, :paket_sira_no, :paket_adi, :haftalik_katilim_sayisi, :toplam_normal_hak, :toplam_telafi_hak,
                  :kullanilan_normal_hak, 0, :kalan_normal_hak, :kalan_telafi_hak, :baslangic_tarihi, NULL, :liste_fiyati,
                  :indirim_turu, :indirim_tutari, :indirim_aciklama, :net_paket_tutari, "aktif", "belirsiz",
                  :yonetici_notu, :olusturan_kullanici_id, NOW())'
            );
            $stmt->execute([
                'kurum_id' => self::kurumId(),
                'ogrenci_id' => $veri['ogrenci_id'],
                'paket_sira_no' => $sira,
                'paket_adi' => $veri['paket_adi'],
                'haftalik_katilim_sayisi' => $haftalik,
                'toplam_normal_hak' => $normalHak,
                'toplam_telafi_hak' => $telafiHak,
                'kullanilan_normal_hak' => $baslangicKullanilanNormalHak,
                'kalan_normal_hak' => $baslangicKalanNormalHak,
                'kalan_telafi_hak' => $telafiHak,
                'baslangic_tarihi' => $veri['baslangic_tarihi'],
                'liste_fiyati' => $listeFiyat,
                'indirim_turu' => $veri['indirim_turu'] ?: null,
                'indirim_tutari' => $indirim,
                'indirim_aciklama' => $veri['indirim_aciklama'] ?: null,
                'net_paket_tutari' => $net,
                'yonetici_notu' => $veri['yonetici_notu'] ?: null,
                'olusturan_kullanici_id' => $veri['olusturan_kullanici_id'],
            ]);

            $id = (int) $db->lastInsertId();
            if ($tanismaIlkDersSayilsin) {
                self::tanismaDersiHakHareketiEkle(
                    $id,
                    (int) $veri['ogrenci_id'],
                    $normalHak,
                    $baslangicKalanNormalHak,
                    (int) ($veri['olusturan_kullanici_id'] ?? 0)
                );
            }

            $sonDersTarihi = self::paketRandevulariniOlustur($id, $veri, $baslangicKalanNormalHak);

            if ($sonDersTarihi === null) {
                $sonDersTarihi = self::ogrencininSonRandevuTarihi((int) $veri['ogrenci_id']);
            }

            $guncelle = $db->prepare('UPDATE paketler SET tahmini_son_ders_tarihi = :tarih WHERE id = :id AND kurum_id = :kurum_id');
            $guncelle->execute([
                'id' => $id,
                'kurum_id' => self::kurumId(),
                'tarih' => $sonDersTarihi,
            ]);

            $db->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function tutarGuncelle(int $paketId, float $yeniTutar, int $kullaniciId): bool
    {
        $paket = self::idIleBul($paketId);
        if (!$paket) {
            throw new \RuntimeException('Guncellenecek paket bulunamadi.');
        }
        if ($yeniTutar < 0) {
            throw new \RuntimeException('Paket tutari sifirdan kucuk olamaz.');
        }

        $oncekiTutar = (float) $paket['net_paket_tutari'];
        $not = trim((string) ($paket['yonetici_notu'] ?? ''));
        $not .= ($not !== '' ? "\n" : '')
            . date('Y-m-d H:i')
            . ' tarihinde tahsilat sirasinda paket toplam tutari '
            . number_format($oncekiTutar, 2, ',', '.')
            . ' TL -> '
            . number_format($yeniTutar, 2, ',', '.')
            . ' TL olarak guncellendi'
            . ($kullaniciId > 0 ? ' (kullanici #' . $kullaniciId . ')' : '')
            . '.';

        $stmt = self::db()->prepare(
            'UPDATE paketler
             SET net_paket_tutari = :net_paket_tutari,
                 yonetici_notu = :yonetici_notu
             WHERE id = :id AND kurum_id = :kurum_id'
        );
        $stmt->execute([
            'id' => $paketId,
            'kurum_id' => self::kurumId(),
            'net_paket_tutari' => $yeniTutar,
            'yonetici_notu' => $not,
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function odemeYapilmadiKapat(int $paketId, int $kullaniciId, string $neden = ''): ?array
    {
        $paket = self::idIleBul($paketId);
        if (!$paket) {
            return null;
        }

        $stmt = self::db()->prepare(
            'SELECT COALESCE(SUM(CASE WHEN iptal = 0 THEN tutar ELSE 0 END), 0)
             FROM odemeler
             WHERE paket_id = :paket_id AND kurum_id = :kurum_id'
        );
        $stmt->execute(['paket_id' => $paketId, 'kurum_id' => self::kurumId()]);

        $tahsilat = round((float) $stmt->fetchColumn(), 2);
        $oncekiTutar = round((float) $paket['net_paket_tutari'], 2);
        $kapatilanBorc = round($oncekiTutar - $tahsilat, 2);

        if ($kapatilanBorc <= 0) {
            return [
                'id' => $paketId,
                'degisti' => false,
                'onceki_paket_tutari' => $oncekiTutar,
                'yeni_paket_tutari' => $oncekiTutar,
                'tahsilat' => $tahsilat,
                'kapatilan_borc' => 0.0,
                'mesaj' => 'Paket icin kapatilacak borc bulunamadi.',
            ];
        }

        $not = trim((string) ($paket['yonetici_notu'] ?? ''));
        $temizNeden = trim($neden);
        $not .= ($not !== '' ? "\n" : '')
            . date('Y-m-d H:i')
            . ' tarihinde odeme yapilmadi olarak kapatildi. Onceki toplam: '
            . number_format($oncekiTutar, 2, ',', '.')
            . ' TL, tahsil edilen: '
            . number_format($tahsilat, 2, ',', '.')
            . ' TL, kapatilan borc: '
            . number_format($kapatilanBorc, 2, ',', '.')
            . ' TL'
            . ($temizNeden !== '' ? '. Neden: ' . $temizNeden : '')
            . ($kullaniciId > 0 ? ' (kullanici #' . $kullaniciId . ')' : '')
            . '.';

        $yeniTutar = max(0.0, $tahsilat);
        $update = self::db()->prepare(
            'UPDATE paketler
             SET net_paket_tutari = :net_paket_tutari,
                 yonetici_notu = :yonetici_notu
             WHERE id = :id AND kurum_id = :kurum_id'
        );
        $update->execute([
            'id' => $paketId,
            'kurum_id' => self::kurumId(),
            'net_paket_tutari' => $yeniTutar,
            'yonetici_notu' => $not,
        ]);

        return [
            'id' => $paketId,
            'degisti' => true,
            'onceki_paket_tutari' => $oncekiTutar,
            'yeni_paket_tutari' => $yeniTutar,
            'tahsilat' => $tahsilat,
            'kapatilan_borc' => $kapatilanBorc,
            'mesaj' => 'Paket borcu odeme yapilmadi olarak kapatildi.',
        ];
    }

    public static function tahsilattaHizmetDonustur(int $eskiPaketId, int $hizmetId, int $kullaniciId, array $secenekler = []): int
    {
        $db = self::db();
        self::paketTablosuGerekliKolonlariHazirla($db);
        self::randevuTablosuGerekliKolonlariHazirla($db);
        self::hakHareketleriTablosuGerekliKolonlariHazirla($db);
        $eskiPaket = self::idIleBul($eskiPaketId);
        if (!$eskiPaket || (string) $eskiPaket['paket_durumu'] !== 'aktif') {
            throw new \RuntimeException('Donusturulecek aktif paket bulunamadi.');
        }

        $hizmet = Hizmet::idIleBul($hizmetId);
        if (!$hizmet || (int) $hizmet['aktif'] !== 1) {
            throw new \RuntimeException('Yeni hizmet secilmelidir.');
        }

        $normalHak = (int) ($secenekler['normal_hak'] ?? 0);
        if ($normalHak < 1) {
            $normalHak = max(1, (int) ($hizmet['toplam_normal_hak'] ?? 1));
        }
        $telafiHak = max(0, (int) ($hizmet['toplam_telafi_hak'] ?? 0));
        $baslangicTarihi = (string) ($secenekler['baslangic_tarihi'] ?? date('Y-m-d'));
        $tarih = \DateTimeImmutable::createFromFormat('Y-m-d', $baslangicTarihi);
        $tarihHatalari = \DateTimeImmutable::getLastErrors();
        if (!$tarih || ($tarihHatalari !== false && ($tarihHatalari['warning_count'] > 0 || $tarihHatalari['error_count'] > 0))) {
            throw new \RuntimeException('Gecerli bir donusum baslangic tarihi secilmelidir.');
        }
        $baslangicTarihi = $tarih->format('Y-m-d');

        try {
            $db->beginTransaction();

            $gelecekStmt = $db->prepare(
                'SELECT *
                 FROM randevular
                 WHERE paket_id = :paket_id
                   AND tarih >= :baslangic_tarihi
                   AND durum IN ("planlandi", "ertelendi")
                 ORDER BY tarih ASC, baslangic_saati ASC, id ASC
                 LIMIT ' . $normalHak
            );
            $gelecekStmt->execute([
                'paket_id' => $eskiPaketId,
                'baslangic_tarihi' => $baslangicTarihi,
            ]);
            $tasinarakOlusturulacakRandevular = $gelecekStmt->fetchAll();
            if ($tasinarakOlusturulacakRandevular === []) {
                throw new \RuntimeException('Yeni hizmete tasinacak planli randevu bulunamadi.');
            }

            $siraStmt = $db->prepare('SELECT COALESCE(MAX(paket_sira_no), 0) + 1 FROM paketler WHERE ogrenci_id = :ogrenci_id');
            $siraStmt->execute(['ogrenci_id' => $eskiPaket['ogrenci_id']]);
            $sira = (int) $siraStmt->fetchColumn();

            $listeFiyat = (float) $hizmet['ucret'];
            $paketStmt = $db->prepare(
                'INSERT INTO paketler
                 (ogrenci_id, paket_sira_no, paket_adi, haftalik_katilim_sayisi, toplam_normal_hak, toplam_telafi_hak,
                  kullanilan_normal_hak, kullanilan_telafi_hak, kalan_normal_hak, kalan_telafi_hak, baslangic_tarihi, tahmini_son_ders_tarihi,
                  liste_fiyati, indirim_turu, indirim_tutari, indirim_aciklama, net_paket_tutari, paket_durumu,
                  yenileme_durumu, yonetici_notu, olusturan_kullanici_id, olusturulma_tarihi)
                 VALUES
                 (:ogrenci_id, :paket_sira_no, :paket_adi, :haftalik_katilim_sayisi, :toplam_normal_hak, :toplam_telafi_hak,
                  0, 0, :kalan_normal_hak, :kalan_telafi_hak, :baslangic_tarihi, NULL,
                  :liste_fiyati, NULL, 0, NULL, :net_paket_tutari, "aktif",
                  "belirsiz", :yonetici_notu, :olusturan_kullanici_id, NOW())'
            );
            $paketStmt->execute([
                'ogrenci_id' => $eskiPaket['ogrenci_id'],
                'paket_sira_no' => $sira,
                'paket_adi' => $hizmet['hizmet_adi'],
                'haftalik_katilim_sayisi' => max(1, (int) $hizmet['haftalik_katilim_sayisi']),
                'toplam_normal_hak' => $normalHak,
                'toplam_telafi_hak' => $telafiHak,
                'kalan_normal_hak' => $normalHak,
                'kalan_telafi_hak' => $telafiHak,
                'baslangic_tarihi' => (string) $tasinarakOlusturulacakRandevular[0]['tarih'],
                'liste_fiyati' => $listeFiyat,
                'net_paket_tutari' => $listeFiyat,
                'yonetici_notu' => 'Tahsilat sirasinda #' . $eskiPaketId . ' paketinden hizmet degisimi ile olusturuldu.',
                'olusturan_kullanici_id' => $kullaniciId ?: null,
            ]);
            $yeniPaketId = (int) $db->lastInsertId();

            $randevuStmt = $db->prepare(
                'INSERT INTO randevular
                 (ogrenci_id, grup_id, paket_id, ogretmen_id, tarih, baslangic_saati, bitis_saati, tur, hak_kaynagi, durum, aciklama, olusturan_kullanici_id, olusturulma_tarihi)
                 VALUES
                 (:ogrenci_id, :grup_id, :paket_id, :ogretmen_id, :tarih, :baslangic_saati, :bitis_saati, "Normal ders", "Aktif paket", "planlandi", :aciklama, :olusturan_kullanici_id, NOW())'
            );

            $eskiRandevuIdleri = [];
            foreach ($tasinarakOlusturulacakRandevular as $randevu) {
                $grupId = Grup::randevuIcinGrupBul((string) $randevu['tarih'], (string) $randevu['baslangic_saati']);
                if ($grupId < 1) {
                    $grupId = (int) ($randevu['grup_id'] ?? 0);
                }
                $bitisSaati = (string) ($randevu['bitis_saati'] ?: self::bitisSaati((string) $randevu['baslangic_saati']));
                $randevuStmt->execute([
                    'ogrenci_id' => $eskiPaket['ogrenci_id'],
                    'grup_id' => $grupId > 0 ? $grupId : null,
                    'paket_id' => $yeniPaketId,
                    'ogretmen_id' => $randevu['ogretmen_id'] ?: null,
                    'tarih' => $randevu['tarih'],
                    'baslangic_saati' => $randevu['baslangic_saati'],
                    'bitis_saati' => $bitisSaati,
                    'aciklama' => 'Tahsilat sirasinda hizmet degisimi ile olusturuldu.',
                    'olusturan_kullanici_id' => $kullaniciId ?: null,
                ]);
                if ($grupId > 0) {
                    Grup::ogrenciAtaTarihle($grupId, (int) $eskiPaket['ogrenci_id'], (string) $randevu['tarih']);
                }
                $eskiRandevuIdleri[] = (int) $randevu['id'];
            }

            if ($eskiRandevuIdleri !== []) {
                $yerTutucular = implode(',', array_fill(0, count($eskiRandevuIdleri), '?'));
                $db->prepare("DELETE FROM yoklamalar WHERE randevu_id IN ($yerTutucular)")->execute($eskiRandevuIdleri);
                $db->prepare("DELETE FROM hak_hareketleri WHERE randevu_id IN ($yerTutucular)")->execute($eskiRandevuIdleri);
                $db->prepare("DELETE FROM randevular WHERE id IN ($yerTutucular)")->execute($eskiRandevuIdleri);
            }

            $not = trim((string) ($eskiPaket['yonetici_notu'] ?? ''));
            $not .= ($not !== '' ? "\n" : '') . date('Y-m-d H:i') . ' tarihinde tahsilat sirasinda #' . $yeniPaketId . ' paketine donusturuldu.';
            $eskiPaketStmt = $db->prepare(
                'UPDATE paketler
                 SET paket_durumu = "tamamlandi",
                     yenileme_durumu = "yenilenmeyecek",
                     yonetici_notu = :yonetici_notu
                 WHERE id = :id'
            );
            $eskiPaketStmt->execute([
                'id' => $eskiPaketId,
                'yonetici_notu' => $not,
            ]);

            $db->commit();

            self::sonDersTarihiGuncelle($eskiPaketId);
            self::sonDersTarihiGuncelle($yeniPaketId);

            return $yeniPaketId;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    private static function tanismaDersiHakHareketiEkle(int $paketId, int $ogrenciId, int $oncekiKalan, int $sonrakiKalan, int $kullaniciId): void
    {
        $stmt = self::db()->prepare(
            'INSERT INTO hak_hareketleri
             (kurum_id, ogrenci_id, paket_id, randevu_id, hareket_turu, hak_turu, miktar, onceki_kalan, sonraki_kalan, aciklama, olusturan_kullanici_id, olusturulma_tarihi)
             VALUES
             (:kurum_id, :ogrenci_id, :paket_id, NULL, "tanisma_dersi_ilk_ders_sayildi", "normal", -1, :onceki_kalan, :sonraki_kalan, :aciklama, :olusturan_kullanici_id, NOW())'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'ogrenci_id' => $ogrenciId,
            'paket_id' => $paketId,
            'onceki_kalan' => $oncekiKalan,
            'sonraki_kalan' => $sonrakiKalan,
            'aciklama' => 'Tanisma dersi paketin ilk normal dersi olarak sayildi.',
            'olusturan_kullanici_id' => $kullaniciId ?: null,
        ]);
    }

    private static function paketTablosuGerekliKolonlariHazirla(?\PDO $db = null): void
    {
        $db ??= self::db();
        self::kolonEkle($db, 'paketler', 'paket_sira_no', 'paket_sira_no INT UNSIGNED NOT NULL DEFAULT 1 AFTER ogrenci_id');
        self::kolonEkle($db, 'paketler', 'haftalik_katilim_sayisi', 'haftalik_katilim_sayisi INT UNSIGNED NOT NULL DEFAULT 1 AFTER paket_adi');
        self::kolonEkle($db, 'paketler', 'toplam_normal_hak', 'toplam_normal_hak INT UNSIGNED NOT NULL DEFAULT 4 AFTER haftalik_katilim_sayisi');
        self::kolonEkle($db, 'paketler', 'toplam_telafi_hak', 'toplam_telafi_hak INT UNSIGNED NOT NULL DEFAULT 0 AFTER toplam_normal_hak');
        self::kolonEkle($db, 'paketler', 'kullanilan_normal_hak', 'kullanilan_normal_hak INT UNSIGNED NOT NULL DEFAULT 0 AFTER toplam_telafi_hak');
        self::kolonEkle($db, 'paketler', 'kullanilan_telafi_hak', 'kullanilan_telafi_hak INT UNSIGNED NOT NULL DEFAULT 0 AFTER kullanilan_normal_hak');
        self::kolonEkle($db, 'paketler', 'kalan_normal_hak', 'kalan_normal_hak INT UNSIGNED NOT NULL DEFAULT 0 AFTER kullanilan_telafi_hak');
        self::kolonEkle($db, 'paketler', 'kalan_telafi_hak', 'kalan_telafi_hak INT UNSIGNED NOT NULL DEFAULT 0 AFTER kalan_normal_hak');
        self::kolonEkle($db, 'paketler', 'tahmini_son_ders_tarihi', 'tahmini_son_ders_tarihi DATE NULL AFTER baslangic_tarihi');
        self::kolonEkle($db, 'paketler', 'liste_fiyati', 'liste_fiyati DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER tahmini_son_ders_tarihi');
        self::kolonEkle($db, 'paketler', 'indirim_turu', 'indirim_turu VARCHAR(30) NULL AFTER liste_fiyati');
        self::kolonEkle($db, 'paketler', 'indirim_tutari', 'indirim_tutari DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER indirim_turu');
        self::kolonEkle($db, 'paketler', 'indirim_aciklama', 'indirim_aciklama TEXT NULL AFTER indirim_tutari');
        self::kolonEkle($db, 'paketler', 'net_paket_tutari', 'net_paket_tutari DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER indirim_aciklama');
        self::kolonEkle($db, 'paketler', 'paket_durumu', 'paket_durumu VARCHAR(30) NOT NULL DEFAULT \'aktif\' AFTER net_paket_tutari');
        self::kolonEkle($db, 'paketler', 'yenileme_durumu', 'yenileme_durumu VARCHAR(30) NOT NULL DEFAULT \'belirsiz\' AFTER paket_durumu');
        self::kolonEkle($db, 'paketler', 'yonetici_notu', 'yonetici_notu TEXT NULL AFTER yenileme_durumu');
    }

    private static function randevuTablosuGerekliKolonlariHazirla(?\PDO $db = null): void
    {
        $db ??= self::db();
        self::kolonEkle($db, 'randevular', 'veli_id', 'veli_id BIGINT UNSIGNED NULL AFTER ogrenci_id');
        self::kolonEkle($db, 'randevular', 'grup_id', 'grup_id BIGINT UNSIGNED NULL AFTER veli_id');
        self::kolonEkle($db, 'randevular', 'paket_id', 'paket_id BIGINT UNSIGNED NULL AFTER grup_id');
        self::kolonEkle($db, 'randevular', 'paket_disi_hak_id', 'paket_disi_hak_id BIGINT UNSIGNED NULL AFTER paket_id');
        self::kolonEkle($db, 'randevular', 'telafi_hakki_id', 'telafi_hakki_id BIGINT UNSIGNED NULL AFTER paket_disi_hak_id');
        self::kolonEkle($db, 'randevular', 'ogretmen_id', 'ogretmen_id BIGINT UNSIGNED NULL AFTER telafi_hakki_id');
        self::kolonEkle($db, 'randevular', 'tarih', 'tarih DATE NULL AFTER ogretmen_id');
        self::kolonEkle($db, 'randevular', 'baslangic_saati', 'baslangic_saati TIME NULL AFTER tarih');
        self::kolonEkle($db, 'randevular', 'bitis_saati', 'bitis_saati TIME NULL AFTER baslangic_saati');
        self::kolonEkle($db, 'randevular', 'tur', 'tur VARCHAR(100) NOT NULL DEFAULT \'Genel Randevu\' AFTER bitis_saati');
        self::kolonEkle($db, 'randevular', 'hak_kaynagi', 'hak_kaynagi VARCHAR(60) NULL AFTER tur');
        self::kolonEkle($db, 'randevular', 'durum', 'durum VARCHAR(30) NOT NULL DEFAULT \'planlandi\' AFTER hak_kaynagi');
        self::kolonEkle($db, 'randevular', 'otomatik_gelmedi_islendi', 'otomatik_gelmedi_islendi TINYINT(1) NOT NULL DEFAULT 0 AFTER durum');
        self::kolonEkle($db, 'randevular', 'katilim_token', 'katilim_token VARCHAR(80) NULL AFTER otomatik_gelmedi_islendi');
        self::kolonEkle($db, 'randevular', 'katilim_yaniti', 'katilim_yaniti VARCHAR(30) NULL AFTER katilim_token');
        self::kolonEkle($db, 'randevular', 'katilim_yanit_tarihi', 'katilim_yanit_tarihi DATETIME NULL AFTER katilim_yaniti');
        self::kolonEkle($db, 'randevular', 'aciklama', 'aciklama TEXT NULL AFTER katilim_yanit_tarihi');
        self::kolonEkle($db, 'randevular', 'olusturan_kullanici_id', 'olusturan_kullanici_id BIGINT UNSIGNED NULL AFTER aciklama');
        self::kolonEkle($db, 'randevular', 'olusturulma_tarihi', 'olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER olusturan_kullanici_id');
    }

    private static function hakHareketleriTablosuGerekliKolonlariHazirla(?\PDO $db = null): void
    {
        $db ??= self::db();
        self::kolonEkle($db, 'hak_hareketleri', 'randevu_id', 'randevu_id BIGINT UNSIGNED NULL AFTER paket_id');
        self::kolonEkle($db, 'hak_hareketleri', 'hak_turu', 'hak_turu VARCHAR(30) NOT NULL DEFAULT \'normal\' AFTER hareket_turu');
        self::kolonEkle($db, 'hak_hareketleri', 'onceki_kalan', 'onceki_kalan INT NOT NULL DEFAULT 0 AFTER miktar');
        self::kolonEkle($db, 'hak_hareketleri', 'sonraki_kalan', 'sonraki_kalan INT NOT NULL DEFAULT 0 AFTER onceki_kalan');
        self::kolonEkle($db, 'hak_hareketleri', 'olusturan_kullanici_id', 'olusturan_kullanici_id BIGINT UNSIGNED NULL AFTER aciklama');
        self::kolonEkle($db, 'hak_hareketleri', 'olusturulma_tarihi', 'olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER olusturan_kullanici_id');
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

    private static function paketRandevulariniOlustur(int $paketId, array $veri, int $normalHak): ?string
    {
        $gunler = self::programGunleri($veri['program_gunleri'] ?? []);
        if ($normalHak < 1 || $gunler === []) {
            return null;
        }

        $stmt = self::db()->prepare(
            'INSERT INTO randevular
             (kurum_id, ogrenci_id, grup_id, paket_id, ogretmen_id, tarih, baslangic_saati, bitis_saati, tur, hak_kaynagi, durum, aciklama, olusturan_kullanici_id, olusturulma_tarihi)
             VALUES
             (:kurum_id, :ogrenci_id, :grup_id, :paket_id, NULL, :tarih, :baslangic_saati, :bitis_saati, "Normal ders", "Aktif paket", "planlandi", :aciklama, :olusturan_kullanici_id, NOW())'
        );

        $sonTarih = null;
        foreach (self::randevuPlaniniHesapla((string) $veri['baslangic_tarihi'], $gunler, $veri['program_saatleri'] ?? [], $normalHak) as $randevu) {
            $grupId = Grup::randevuIcinGrupBul($randevu['tarih'], $randevu['saat']);
            $stmt->execute([
                'kurum_id' => self::kurumId(),
                'ogrenci_id' => $veri['ogrenci_id'],
                'grup_id' => $grupId > 0 ? $grupId : null,
                'paket_id' => $paketId,
                'tarih' => $randevu['tarih'],
                'baslangic_saati' => $randevu['saat'],
                'bitis_saati' => self::bitisSaati($randevu['saat']),
                'aciklama' => 'Paket tanimlama ile otomatik olusturuldu.',
                'olusturan_kullanici_id' => $veri['olusturan_kullanici_id'],
            ]);
            if ($grupId > 0) {
                Grup::ogrenciAtaTarihle($grupId, (int) $veri['ogrenci_id'], $randevu['tarih']);
            }
            $sonTarih = $randevu['tarih'];
        }

        return $sonTarih;
    }

    private static function randevuPlaniniHesapla(string $baslangicTarihi, array $gunler, array $saatler, int $adet): array
    {
        $plan = [];
        $cursor = new \DateTimeImmutable($baslangicTarihi);
        $guard = 0;

        while (count($plan) < $adet && $guard < 730) {
            $gun = (int) $cursor->format('N');
            if (in_array($gun, $gunler, true)) {
                $plan[] = [
                    'tarih' => $cursor->format('Y-m-d'),
                    'saat' => self::programSaati($saatler, $gun),
                ];
            }

            $cursor = $cursor->modify('+1 day');
            $guard++;
        }

        return $plan;
    }

    private static function programGunleri(array $gunler): array
    {
        $gecerli = [];
        foreach ($gunler as $gun) {
            $gun = (int) $gun;
            if ($gun >= 1 && $gun <= 7) {
                $gecerli[] = $gun;
            }
        }

        $gecerli = array_values(array_unique($gecerli));
        sort($gecerli);
        return $gecerli;
    }

    private static function programSaati(array $saatler, int $gun): string
    {
        $saat = (string) ($saatler['program_saat_' . $gun] ?? '15:00');
        if (!preg_match('/^\d{2}:\d{2}$/', $saat)) {
            return '15:00:00';
        }

        return $saat . ':00';
    }

    private static function bitisSaati(string $baslangicSaati): string
    {
        return date('H:i:s', strtotime($baslangicSaati . ' +45 minutes'));
    }

    private static function ogrencininSonRandevuTarihi(int $ogrenciId): ?string
    {
        $stmt = self::db()->prepare('SELECT MAX(tarih) FROM randevular WHERE ogrenci_id = :ogrenci_id AND kurum_id = :kurum_id');
        $stmt->execute(['ogrenci_id' => $ogrenciId, 'kurum_id' => self::kurumId()]);
        $tarih = $stmt->fetchColumn();
        return $tarih ? (string) $tarih : null;
    }
}
