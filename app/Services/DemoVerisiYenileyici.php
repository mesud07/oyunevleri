<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Veritabani;
use DateTimeImmutable;
use PDO;
use RuntimeException;
use Throwable;

final class DemoVerisiYenileyici
{
    private const KURUM_KODU = 'DEMO';
    private const ACIKLAMA_ON_EKI = 'Otomatik aylik demo';

    public static function yenile(?DateTimeImmutable $bugun = null): array
    {
        $bugun ??= new DateTimeImmutable('today');
        $db = Veritabani::baglan();
        $kilitAlindi = (int) $db->query("SELECT GET_LOCK('oyunevleri_demo_verisi_yenile', 0)")->fetchColumn() === 1;
        if (!$kilitAlindi) {
            return ['durum' => 'kilitli'];
        }

        try {
            $kurum = self::kurumVeKullanici($db);
            if ($kurum === null) {
                return ['durum' => 'demo_kurumu_yok'];
            }

            $ay = $bugun->format('Y-m');
            $marker = self::ACIKLAMA_ON_EKI . ': ' . $ay;
            $kontrol = $db->prepare(
                'SELECT COUNT(*) FROM randevular WHERE kurum_id = :kurum_id AND aciklama = :aciklama'
            );
            $kontrol->execute(['kurum_id' => $kurum['kurum_id'], 'aciklama' => $marker]);
            if ((int) $kontrol->fetchColumn() > 0) {
                return ['durum' => 'zaten_guncel', 'ay' => $ay];
            }

            $db->beginTransaction();
            try {
                $sonuc = self::ayiOlustur($db, $kurum, $bugun, $marker);
                $db->commit();
            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                throw $e;
            }

            return ['durum' => 'olusturuldu', 'ay' => $ay] + $sonuc;
        } finally {
            $db->query("SELECT RELEASE_LOCK('oyunevleri_demo_verisi_yenile')");
        }
    }

    private static function kurumVeKullanici(PDO $db): ?array
    {
        $stmt = $db->prepare(
            'SELECT k.id AS kurum_id, u.id AS kullanici_id
             FROM kurumlar k
             INNER JOIN kullanicilar u ON u.kurum_id = k.id AND u.aktif = 1
             WHERE k.kod = :kod AND k.aktif = 1
             ORDER BY u.id ASC
             LIMIT 1'
        );
        $stmt->execute(['kod' => self::KURUM_KODU]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private static function ayiOlustur(PDO $db, array $kurum, DateTimeImmutable $bugun, string $marker): array
    {
        $kurumId = (int) $kurum['kurum_id'];
        $kullaniciId = (int) $kurum['kullanici_id'];
        $ogrenciler = self::ogrenciler($db, $kurumId);
        if ($ogrenciler === []) {
            throw new RuntimeException('DEMO kurumu icin uygun aktif ogrenci ve paket bulunamadi.');
        }

        $randevuEkle = $db->prepare(
            'INSERT INTO randevular
                (kurum_id, ogrenci_id, veli_id, grup_id, paket_id, ogretmen_id, tarih,
                 baslangic_saati, bitis_saati, tur, hak_kaynagi, durum, aciklama,
                 olusturan_kullanici_id, olusturulma_tarihi)
             VALUES
                (:kurum_id, :ogrenci_id, :veli_id, :grup_id, :paket_id, :ogretmen_id, :tarih,
                 :baslangic, :bitis, "Normal ders", "Aktif paket", :durum, :aciklama,
                 :kullanici_id, NOW())'
        );

        $ayinIlkGunu = $bugun->modify('first day of this month');
        $ayinSonGunu = $bugun->modify('last day of this month');
        $randevuSayisi = 0;
        $ogrenciSayisi = count($ogrenciler);
        for ($tarih = $ayinIlkGunu; $tarih <= $ayinSonGunu; $tarih = $tarih->modify('+1 day')) {
            $gunlukAdet = (int) $tarih->format('N') === 7 ? 2 : 4;
            for ($i = 0; $i < $gunlukAdet; $i++) {
                $index = (((int) $tarih->format('j') - 1) * 4 + $i) % $ogrenciSayisi;
                $ogrenci = $ogrenciler[$index];
                $baslangic = sprintf('%02d:00:00', 10 + ($i * 2));
                $bitis = sprintf('%02d:00:00', 11 + ($i * 2));
                $durum = 'planlandi';
                if ($tarih < $bugun) {
                    $rastgele = abs(crc32($tarih->format('Y-m-d') . '-' . $ogrenci['ogrenci_id'])) % 10;
                    $durum = $rastgele < 8 ? 'geldi' : ($rastgele === 8 ? 'gelmedi' : 'mazeretli_gelmedi');
                }

                $randevuEkle->execute([
                    'kurum_id' => $kurumId,
                    'ogrenci_id' => (int) $ogrenci['ogrenci_id'],
                    'veli_id' => (int) ($ogrenci['veli_id'] ?? 0) ?: null,
                    'grup_id' => (int) ($ogrenci['grup_id'] ?? 0) ?: null,
                    'paket_id' => (int) $ogrenci['paket_id'],
                    'ogretmen_id' => (int) ($ogrenci['ogretmen_id'] ?? 0) ?: $kullaniciId,
                    'tarih' => $tarih->format('Y-m-d'),
                    'baslangic' => $baslangic,
                    'bitis' => $bitis,
                    'durum' => $durum,
                    'aciklama' => $marker,
                    'kullanici_id' => $kullaniciId,
                ]);
                $randevuSayisi++;
            }
        }

        $kasaId = self::kasaId($db, $kurumId);
        $odemeEkle = $db->prepare(
            'INSERT INTO odemeler
                (kurum_id, ogrenci_id, veli_id, paket_id, tarih, tutar, yontem, kasa_id,
                 makbuz_numarasi, aciklama, alan_kullanici_id, iptal, olusturulma_tarihi)
             VALUES
                (:kurum_id, :ogrenci_id, :veli_id, :paket_id, :tarih, :tutar, :yontem, :kasa_id,
                 :makbuz, :aciklama, :kullanici_id, 0, NOW())'
        );
        $yontemler = ['nakit', 'kredi_karti', 'havale_eft', 'odeme_baglantisi'];
        $odemeSayisi = min(10, $ogrenciSayisi);
        for ($i = 0; $i < $odemeSayisi; $i++) {
            $ogrenci = $ogrenciler[$i];
            $odemeEkle->execute([
                'kurum_id' => $kurumId,
                'ogrenci_id' => (int) $ogrenci['ogrenci_id'],
                'veli_id' => (int) ($ogrenci['veli_id'] ?? 0) ?: null,
                'paket_id' => (int) $ogrenci['paket_id'],
                'tarih' => $bugun->format('Y-m-d'),
                'tutar' => 1250 + (($i % 4) * 500),
                'yontem' => $yontemler[$i % count($yontemler)],
                'kasa_id' => $kasaId,
                'makbuz' => sprintf('DEMO-AYLIK-%s-%02d', $bugun->format('Ym'), $i + 1),
                'aciklama' => $marker . ' tahsilati',
                'kullanici_id' => $kullaniciId,
            ]);
        }

        $giderler = [
            ['Kurum Kirasi', 'Kira', 42000, 'banka_havalesi'],
            ['Etkinlik Malzemeleri', 'Malzeme', 8750, 'kredi_karti'],
            ['Temizlik Hizmeti', 'Temizlik', 6800, 'banka_havalesi'],
            ['Elektrik, Su ve Internet', 'Fatura', 5900, 'otomatik_odeme'],
        ];
        $giderEkle = $db->prepare(
            'INSERT INTO giderler
                (kurum_id, tarih, tedarikci, kategori, aciklama, tutar, odeme_turu, kasa_id,
                 durum, odeme_tarihi, olusturan_kullanici_id, olusturulma_tarihi)
             VALUES
                (:kurum_id, :tarih, :tedarikci, :kategori, :aciklama, :tutar, :odeme_turu, :kasa_id,
                 "odendi", :odeme_tarihi, :kullanici_id, NOW())'
        );
        foreach ($giderler as [$tedarikci, $kategori, $tutar, $odemeTuru]) {
            $giderEkle->execute([
                'kurum_id' => $kurumId,
                'tarih' => $bugun->format('Y-m-d'),
                'tedarikci' => $tedarikci,
                'kategori' => $kategori,
                'aciklama' => $marker . ' gideri',
                'tutar' => $tutar,
                'odeme_turu' => $odemeTuru,
                'kasa_id' => $kasaId,
                'odeme_tarihi' => $bugun->format('Y-m-d'),
                'kullanici_id' => $kullaniciId,
            ]);
        }

        $paketGuncelle = $db->prepare(
            'UPDATE paketler
             SET tahmini_son_ders_tarihi = :son_tarih,
                 beklenen_odeme_tarihi = :odeme_tarihi
             WHERE kurum_id = :kurum_id AND paket_durumu = "aktif"'
        );
        $paketGuncelle->execute([
            'son_tarih' => $bugun->modify('last day of next month')->format('Y-m-d'),
            'odeme_tarihi' => $bugun->modify('+20 days')->format('Y-m-d'),
            'kurum_id' => $kurumId,
        ]);

        return [
            'randevu' => $randevuSayisi,
            'tahsilat' => $odemeSayisi,
            'gider' => count($giderler),
        ];
    }

    private static function ogrenciler(PDO $db, int $kurumId): array
    {
        $stmt = $db->prepare(
            'SELECT o.id AS ogrenci_id,
                    go.grup_id,
                    g.ogretmen_id,
                    p.id AS paket_id,
                    (SELECT ov.veli_id
                     FROM ogrenci_velileri ov
                     WHERE ov.kurum_id = o.kurum_id AND ov.ogrenci_id = o.id
                     ORDER BY ov.birincil_mi DESC, ov.id ASC
                     LIMIT 1) AS veli_id
             FROM ogrenciler o
             INNER JOIN grup_ogrencileri go
                ON go.kurum_id = o.kurum_id AND go.ogrenci_id = o.id AND go.aktif = 1
             INNER JOIN gruplar g
                ON g.kurum_id = go.kurum_id AND g.id = go.grup_id AND g.aktif = 1
             INNER JOIN paketler p
                ON p.id = (
                    SELECT p2.id FROM paketler p2
                    WHERE p2.kurum_id = o.kurum_id
                      AND p2.ogrenci_id = o.id
                      AND p2.paket_durumu = "aktif"
                    ORDER BY p2.id DESC LIMIT 1
                )
             WHERE o.kurum_id = :kurum_id AND o.durum = "aktif"
             GROUP BY o.id, go.grup_id, g.ogretmen_id, p.id
             ORDER BY o.id ASC
             LIMIT 24'
        );
        $stmt->execute(['kurum_id' => $kurumId]);

        return $stmt->fetchAll();
    }

    private static function kasaId(PDO $db, int $kurumId): ?int
    {
        $stmt = $db->prepare(
            'SELECT id FROM kasalar WHERE kurum_id = :kurum_id AND aktif = 1 ORDER BY id ASC LIMIT 1'
        );
        $stmt->execute(['kurum_id' => $kurumId]);
        $id = (int) ($stmt->fetchColumn() ?: 0);

        return $id > 0 ? $id : null;
    }
}
