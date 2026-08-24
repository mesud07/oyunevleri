<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Model;
use App\Models\OgrenciEtkinlikKaydi;
use App\Models\TelafiHakki;
final class Randevu extends Model
{
    public static function ozet(): array
    {
        $stmt = self::db()->prepare(
            'SELECT durum, COUNT(*) AS adet
             FROM randevular
             WHERE kurum_id = :kurum_id AND durum IN ("planlandi", "geldi", "gelmedi")
             GROUP BY durum'
        );
        $stmt->execute(self::kurumParam());

        $ozet = [
            'planlandi' => 0,
            'geldi' => 0,
            'gelmedi' => 0,
        ];

        foreach ($stmt->fetchAll() as $row) {
            $durum = (string) $row['durum'];
            if (array_key_exists($durum, $ozet)) {
                $ozet[$durum] = (int) $row['adet'];
            }
        }

        return $ozet;
    }

    public static function liste(): array
    {
        $stmt = self::db()->prepare(
            'SELECT r.id, r.telafi_hakki_id, r.katilim_yaniti, r.katilim_yanit_tarihi,
                    CONCAT(o.ad, " ", o.soyad) AS ogrenci, r.tarih, r.baslangic_saati, r.bitis_saati,
                    COALESCE(g.ad, "Cocuk Etkinlik ve Oyun Evi") AS grup, r.tur, r.hak_kaynagi, r.durum, r.aciklama,
                    kr.tarih AS telafi_kaynak_tarih, kr.baslangic_saati AS telafi_kaynak_saat
             FROM randevular r
             INNER JOIN ogrenciler o ON o.id = r.ogrenci_id AND o.kurum_id = r.kurum_id
             LEFT JOIN gruplar g ON g.id = r.grup_id AND g.kurum_id = r.kurum_id
             LEFT JOIN telafi_haklari th ON th.id = r.telafi_hakki_id AND th.kurum_id = r.kurum_id
             LEFT JOIN randevular kr ON kr.id = th.kaynak_randevu_id AND kr.kurum_id = r.kurum_id
             WHERE r.kurum_id = :kurum_id
             ORDER BY r.tarih DESC, r.baslangic_saati DESC
             LIMIT 100'
        );
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function ekle(array $veri): int
    {
        $db = self::db();
        $paket = !empty($veri['paket_id']) ? Paket::idIleBul((int) $veri['paket_id']) : null;
        $ogrenciId = $paket ? (int) $paket['ogrenci_id'] : (int) $veri['ogrenci_id'];
        if ($ogrenciId < 1) {
            throw new \InvalidArgumentException('Randevu icin gecerli bir ogrenci bulunamadi.');
        }
        if ((int) ($veri['olusturan_kullanici_id'] ?? 0) < 1) {
            throw new \InvalidArgumentException('Randevuyu olusturan kullanici bulunamadi.');
        }

        $baslangic = $veri['baslangic_saati'];
        $sure = max(15, (int) $veri['sure_dakika']);
        $bitis = date('H:i:s', strtotime($baslangic . ' +' . $sure . ' minutes'));
        $grupId = !empty($veri['grup_id']) ? (int) $veri['grup_id'] : Grup::randevuIcinGrupBul((string) $veri['tarih'], (string) $baslangic);

        try {
            $db->beginTransaction();
            $stmt = $db->prepare(
                'INSERT INTO randevular
                 (kurum_id, ogrenci_id, grup_id, paket_id, ogretmen_id, tarih, baslangic_saati, bitis_saati, tur, hak_kaynagi, durum, aciklama, olusturan_kullanici_id, olusturulma_tarihi)
                 VALUES
                 (:kurum_id, :ogrenci_id, :grup_id, :paket_id, :ogretmen_id, :tarih, :baslangic_saati, :bitis_saati, :tur, :hak_kaynagi, :durum, :aciklama, :olusturan_kullanici_id, NOW())'
            );
            $durum = (string) ($veri['durum'] ?? 'planlandi');
            $stmt->execute([
                'kurum_id' => self::kurumId(),
                'ogrenci_id' => $ogrenciId,
                'grup_id' => $grupId > 0 ? $grupId : null,
                'paket_id' => $veri['paket_id'] ?: null,
                'ogretmen_id' => $veri['ogretmen_id'] ?: null,
                'tarih' => $veri['tarih'],
                'baslangic_saati' => $baslangic,
                'bitis_saati' => $bitis,
                'tur' => $veri['tur'],
                'hak_kaynagi' => $veri['hak_kaynagi'],
                'durum' => $durum,
                'aciklama' => $veri['aciklama'] ?: null,
                'olusturan_kullanici_id' => $veri['olusturan_kullanici_id'],
            ]);

            $id = (int) $db->lastInsertId();
            if ($grupId > 0) {
                Grup::ogrenciAtaTarihle($grupId, $ogrenciId, (string) $veri['tarih']);
            }
            self::randevuHakkiniIsle($id, $durum, (int) ($veri['olusturan_kullanici_id'] ?? 0));
            OgrenciEtkinlikKaydi::randevudanSenkronize($id);
            self::paketSonDersGuncelle((int) ($veri['paket_id'] ?? 0));
            $db->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function idIleBul(int $id): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT r.*, CONCAT(o.ad, " ", o.soyad) AS ogrenci,
                    COALESCE(v.telefon, o.acil_durum_telefon, "") AS telefon,
                    COALESCE(g.ad, "Cocuk Etkinlik ve Oyun Evi") AS grup,
                    COALESCE(CONCAT(uz.ad, " ", uz.soyad), CONCAT(ol.ad, " ", ol.soyad), "-") AS uzman,
                    COALESCE(CONCAT(ol.ad, " ", ol.soyad), "-") AS olusturan,
                    COALESCE(p.paket_adi, r.tur) AS paket_adi,
                    kr.tarih AS telafi_kaynak_tarih,
                    kr.baslangic_saati AS telafi_kaynak_saat,
                    COALESCE(kp.paket_adi, kr.tur) AS telafi_kaynak_paket_adi
             FROM randevular r
             INNER JOIN ogrenciler o ON o.id = r.ogrenci_id AND o.kurum_id = r.kurum_id
             LEFT JOIN ogrenci_velileri ov ON ov.ogrenci_id = o.id AND ov.birincil_mi = 1 AND ov.kurum_id = r.kurum_id
             LEFT JOIN veliler v ON v.id = ov.veli_id AND v.kurum_id = r.kurum_id
             LEFT JOIN gruplar g ON g.id = r.grup_id AND g.kurum_id = r.kurum_id
             LEFT JOIN kullanicilar uz ON uz.id = r.ogretmen_id AND uz.kurum_id = r.kurum_id
             LEFT JOIN kullanicilar ol ON ol.id = r.olusturan_kullanici_id AND ol.kurum_id = r.kurum_id
             LEFT JOIN paketler p ON p.id = r.paket_id AND p.kurum_id = r.kurum_id
             LEFT JOIN telafi_haklari th ON th.id = r.telafi_hakki_id AND th.kurum_id = r.kurum_id
             LEFT JOIN randevular kr ON kr.id = th.kaynak_randevu_id AND kr.kurum_id = r.kurum_id
             LEFT JOIN paketler kp ON kp.id = kr.paket_id AND kp.kurum_id = r.kurum_id
             WHERE r.id = :id AND r.kurum_id = :kurum_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $randevu = $stmt->fetch();
        return $randevu ?: null;
    }

    public static function katilimTokeni(int $id): ?string
    {
        $stmt = self::db()->prepare('SELECT katilim_token FROM randevular WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $token = (string) ($stmt->fetchColumn() ?: '');
        if ($token !== '') {
            return $token;
        }

        $token = bin2hex(random_bytes(24));
        $stmt = self::db()->prepare('UPDATE randevular SET katilim_token = :token WHERE id = :id');
        $stmt->execute(['id' => $id, 'token' => $token]);
        return $stmt->rowCount() > 0 ? $token : null;
    }

    public static function katilimTokenIleBul(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{48,80}$/', $token)) {
            return null;
        }

        $stmt = self::db()->prepare(
            'SELECT r.*, CONCAT(o.ad, " ", o.soyad) AS ogrenci,
                    COALESCE(CONCAT(uz.ad, " ", uz.soyad), "-") AS uzman,
                    COALESCE(g.ad, "Cocuk Etkinlik ve Oyun Evi") AS grup,
                    COALESCE(p.paket_adi, r.tur) AS paket_adi
             FROM randevular r
             INNER JOIN ogrenciler o ON o.id = r.ogrenci_id
             LEFT JOIN gruplar g ON g.id = r.grup_id
             LEFT JOIN kullanicilar uz ON uz.id = r.ogretmen_id
             LEFT JOIN paketler p ON p.id = r.paket_id
             WHERE r.katilim_token = :token
             LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $randevu = $stmt->fetch();
        return $randevu ?: null;
    }

    public static function katilimYanitiKaydet(string $token, string $yanit): bool
    {
        if (!in_array($yanit, ['katilacagim', 'katilamayacagim'], true)) {
            return false;
        }

        $stmt = self::db()->prepare(
            'UPDATE randevular
             SET katilim_yaniti = :yanit, katilim_yanit_tarihi = NOW()
             WHERE katilim_token = :token'
        );
        $stmt->execute(['token' => $token, 'yanit' => $yanit]);
        return $stmt->rowCount() > 0;
    }

    public static function guncelle(int $id, array $veri): bool
    {
        $mevcut = self::idIleBul($id);
        if (!$mevcut) {
            return false;
        }

        $db = self::db();
        $baslangic = self::saatDegeri((string) ($veri['baslangic_saati'] ?? $mevcut['baslangic_saati']));
        $sure = max(15, (int) ($veri['sure_dakika'] ?? self::sureDakika((string) $mevcut['baslangic_saati'], (string) $mevcut['bitis_saati'])));
        $bitis = date('H:i:s', strtotime($baslangic . ' +' . $sure . ' minutes'));
        $tarih = (string) ($veri['tarih'] ?? $mevcut['tarih']);
        $grupId = Grup::randevuIcinGrupBul($tarih, $baslangic);

        try {
            $db->beginTransaction();
            $stmt = $db->prepare(
                'UPDATE randevular
                 SET tarih = :tarih,
                     grup_id = :grup_id,
                     baslangic_saati = :baslangic_saati,
                     bitis_saati = :bitis_saati,
                     tur = :tur,
                     hak_kaynagi = :hak_kaynagi,
                     durum = :durum,
                     aciklama = :aciklama
                 WHERE id = :id AND kurum_id = :kurum_id'
            );

            $durum = (string) ($veri['durum'] ?? $mevcut['durum']);
            $basarili = $stmt->execute([
                'id' => $id,
                'kurum_id' => self::kurumId(),
                'tarih' => $tarih,
                'grup_id' => $grupId > 0 ? $grupId : null,
                'baslangic_saati' => $baslangic,
                'bitis_saati' => $bitis,
                'tur' => $veri['tur'] ?? $mevcut['tur'],
                'hak_kaynagi' => $veri['hak_kaynagi'] ?? $mevcut['hak_kaynagi'],
                'durum' => $durum,
                'aciklama' => ($veri['aciklama'] ?? $mevcut['aciklama']) ?: null,
            ]);

            self::randevuHakkiniGeriAl($id);
            self::randevuHakkiniIsle($id, $durum, (int) ($veri['isleyen_kullanici_id'] ?? 0));
            OgrenciEtkinlikKaydi::randevudanSenkronize($id);
            if ($grupId > 0) {
                Grup::ogrenciAtaTarihle($grupId, (int) $mevcut['ogrenci_id'], $tarih);
            }
            self::paketSonDersGuncelle((int) ($mevcut['paket_id'] ?? 0));
            $db->commit();
            return $basarili;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function durumDegistir(array $idler, string $durum, int $kullaniciId = 0): int
    {
        $idler = self::temizIdler($idler);
        if ($idler === [] || !self::durumGecerliMi($durum)) {
            return 0;
        }

        $db = self::db();
        try {
            $db->beginTransaction();
            foreach ($idler as $id) {
                self::randevuHakkiniGeriAl((int) $id);
            }
            $yerTutucular = implode(',', array_fill(0, count($idler), '?'));
            $stmt = $db->prepare("UPDATE randevular SET durum = ? WHERE kurum_id = ? AND id IN ($yerTutucular)");
            $stmt->execute(array_merge([$durum, self::kurumId()], $idler));
            foreach ($idler as $id) {
                self::randevuHakkiniIsle((int) $id, $durum, $kullaniciId);
                OgrenciEtkinlikKaydi::randevudanSenkronize((int) $id);
            }
            $db->commit();
            return count($idler);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function topluGuncelle(array $idler, array $veri): int
    {
        $idler = self::temizIdler($idler);
        if ($idler === []) {
            return 0;
        }

        $alanlar = [];
        $parametreler = [];
        if (!empty($veri['tarih'])) {
            $alanlar[] = 'tarih = ?';
            $parametreler[] = $veri['tarih'];
        }
        if (!empty($veri['baslangic_saati'])) {
            $baslangic = self::saatDegeri((string) $veri['baslangic_saati']);
            $sure = max(15, (int) ($veri['sure_dakika'] ?? 45));
            $alanlar[] = 'baslangic_saati = ?';
            $alanlar[] = 'bitis_saati = ?';
            $parametreler[] = $baslangic;
            $parametreler[] = date('H:i:s', strtotime($baslangic . ' +' . $sure . ' minutes'));
        }
        if (!empty($veri['durum']) && self::durumGecerliMi((string) $veri['durum'])) {
            $alanlar[] = 'durum = ?';
            $parametreler[] = $veri['durum'];
        }
        if (array_key_exists('aciklama', $veri) && trim((string) $veri['aciklama']) !== '') {
            $alanlar[] = 'aciklama = ?';
            $parametreler[] = trim((string) $veri['aciklama']);
        }
        if ($alanlar === []) {
            return 0;
        }

        $paketIdleri = self::randevuPaketIdleri($idler);
        $yeniDurum = !empty($veri['durum']) && self::durumGecerliMi((string) $veri['durum']) ? (string) $veri['durum'] : '';
        $yerTutucular = implode(',', array_fill(0, count($idler), '?'));
        $db = self::db();
        try {
            $db->beginTransaction();
            if ($yeniDurum !== '') {
                foreach ($idler as $id) {
                    self::randevuHakkiniGeriAl((int) $id);
                }
            }

            $stmt = $db->prepare('UPDATE randevular SET ' . implode(', ', $alanlar) . " WHERE kurum_id = ? AND id IN ($yerTutucular)");
            $stmt->execute(array_merge($parametreler, [self::kurumId()], $idler));

            if ($yeniDurum !== '') {
                foreach ($idler as $id) {
                    self::randevuHakkiniIsle((int) $id, $yeniDurum, 0);
                    OgrenciEtkinlikKaydi::randevudanSenkronize((int) $id);
                }
            }
            foreach ($paketIdleri as $paketId) {
                self::paketSonDersGuncelle($paketId);
            }
            $db->commit();
            return $yeniDurum !== '' ? count($idler) : $stmt->rowCount();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function sil(array $idler): int
    {
        $idler = self::temizIdler($idler);
        if ($idler === []) {
            return 0;
        }

        $randevular = self::randevulariGetir($idler);
        if ($randevular === []) {
            return 0;
        }

        $paketIdleri = [];
        foreach ($randevular as $randevu) {
            if (!empty($randevu['paket_id'])) {
                $paketIdleri[] = (int) $randevu['paket_id'];
            }
        }
        $paketIdleri = array_values(array_unique($paketIdleri));
        $yerTutucular = implode(',', array_fill(0, count($idler), '?'));

        $db = self::db();
        try {
            $db->beginTransaction();

            foreach ($randevular as $randevu) {
                if (!empty($randevu['telafi_hakki_id'])) {
                    self::randevuHakkiniGeriAl((int) $randevu['id']);
                    continue;
                }

                self::randevuHakkiniGeriAl((int) $randevu['id']);
            }

            $db->prepare("DELETE FROM yoklamalar WHERE kurum_id = ? AND randevu_id IN ($yerTutucular)")->execute(array_merge([self::kurumId()], $idler));
            $stmt = $db->prepare("DELETE FROM randevular WHERE kurum_id = ? AND id IN ($yerTutucular)");
            $stmt->execute(array_merge([self::kurumId()], $idler));
            $adet = $stmt->rowCount();

            foreach ($paketIdleri as $paketId) {
                self::paketSonDersGuncelle($paketId);
            }

            $db->commit();
            return $adet;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function takvim(string $ay): array
    {
        $baslangic = \DateTimeImmutable::createFromFormat('Y-m-d', $ay . '-01') ?: new \DateTimeImmutable('first day of this month');
        $ilkGun = $baslangic->format('Y-m-01');
        $sonGun = $baslangic->format('Y-m-t');

        return self::takvimAralik($ilkGun, $sonGun);
    }

    public static function takvimAralik(string $ilkGun, string $sonGun): array
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ilkGun) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sonGun)) {
            $baslangic = new \DateTimeImmutable('first day of this month');
            $ilkGun = $baslangic->format('Y-m-01');
            $sonGun = $baslangic->format('Y-m-t');
        }

        $stmt = self::db()->prepare(
            'SELECT r.id, r.ogrenci_id, r.telafi_hakki_id, r.katilim_yaniti, r.katilim_yanit_tarihi,
                    r.tarih, r.baslangic_saati, r.bitis_saati, r.durum, r.tur,
                    CONCAT(o.ad, " ", o.soyad) AS ogrenci,
                    COALESCE(g.ad, "Cocuk Etkinlik ve Oyun Evi") AS grup,
                    kr.tarih AS telafi_kaynak_tarih,
                    kr.baslangic_saati AS telafi_kaynak_saat
             FROM randevular r
             INNER JOIN ogrenciler o ON o.id = r.ogrenci_id AND o.kurum_id = r.kurum_id
             LEFT JOIN gruplar g ON g.id = r.grup_id AND g.kurum_id = r.kurum_id
             LEFT JOIN telafi_haklari th ON th.id = r.telafi_hakki_id AND th.kurum_id = r.kurum_id
             LEFT JOIN randevular kr ON kr.id = th.kaynak_randevu_id AND kr.kurum_id = r.kurum_id
             WHERE r.kurum_id = :kurum_id AND r.tarih BETWEEN :ilk AND :son
             ORDER BY r.tarih ASC, r.baslangic_saati ASC'
        );
        $stmt->execute(['ilk' => $ilkGun, 'son' => $sonGun, 'kurum_id' => self::kurumId()]);
        $randevular = $stmt->fetchAll();

        return array_merge($randevular, self::yenilemeHatirlatmaTakvimi($ilkGun, $sonGun));
    }

    private static function yenilemeHatirlatmaTakvimi(string $ilkGun, string $sonGun): array
    {
        $oncekiIlk = (new \DateTimeImmutable($ilkGun))->modify('-7 days')->format('Y-m-d');
        $oncekiSon = (new \DateTimeImmutable($sonGun))->modify('-7 days')->format('Y-m-d');

        $stmt = self::db()->prepare(
            'SELECT
                    r.id AS kaynak_randevu_id,
                    r.ogrenci_id,
                    DATE_ADD(r.tarih, INTERVAL 7 DAY) AS tarih,
                    r.baslangic_saati,
                    r.bitis_saati,
                    CONCAT(o.ad, " ", o.soyad) AS ogrenci,
                    COALESCE(g.ad, "Cocuk Etkinlik ve Oyun Evi") AS grup,
                    COALESCE(p.paket_adi, r.tur, "Ders") AS tur,
                    r.tarih AS onceki_randevu_tarihi,
                    CASE
                        WHEN p.id IS NOT NULL
                         AND p.tahmini_son_ders_tarihi = r.tarih
                         AND p.toplam_normal_hak > 1
                        THEN "son_ders_yenileme"
                        ELSE "onceki_hafta_yok"
                    END AS hatirlatma_turu
             FROM randevular r
             INNER JOIN ogrenciler o ON o.id = r.ogrenci_id AND o.kurum_id = r.kurum_id
             LEFT JOIN gruplar g ON g.id = r.grup_id AND g.kurum_id = r.kurum_id
             LEFT JOIN paketler p ON p.id = r.paket_id AND p.kurum_id = r.kurum_id AND p.paket_durumu <> "iptal"
             WHERE r.kurum_id = :kurum_id
               AND r.tarih BETWEEN :onceki_ilk AND :onceki_son
               AND r.telafi_hakki_id IS NULL
               AND COALESCE(r.durum, "planlandi") IN ("geldi", "tamamlandi")
               AND (
                    (p.id IS NOT NULL AND p.tahmini_son_ders_tarihi = r.tarih AND p.toplam_normal_hak > 1)
                    OR r.durum IN ("geldi", "tamamlandi")
               )
               AND NOT EXISTS (
                    SELECT 1
                    FROM randevular rr
                    WHERE rr.ogrenci_id = r.ogrenci_id
                      AND rr.kurum_id = r.kurum_id
                      AND rr.tarih BETWEEN
                          DATE_SUB(DATE_ADD(r.tarih, INTERVAL 7 DAY), INTERVAL WEEKDAY(DATE_ADD(r.tarih, INTERVAL 7 DAY)) DAY)
                          AND DATE_ADD(
                              DATE_SUB(DATE_ADD(r.tarih, INTERVAL 7 DAY), INTERVAL WEEKDAY(DATE_ADD(r.tarih, INTERVAL 7 DAY)) DAY),
                              INTERVAL 6 DAY
                          )
                      AND COALESCE(rr.durum, "planlandi") NOT IN ("iptal", "kurum_iptali")
               )
             ORDER BY tarih ASC, r.baslangic_saati ASC, hatirlatma_turu ASC'
        );
        $stmt->execute([
            'onceki_ilk' => $oncekiIlk,
            'onceki_son' => $oncekiSon,
            'kurum_id' => self::kurumId(),
        ]);

        $hatirlatmalar = [];
        foreach ($stmt->fetchAll() as $row) {
            $hedefTarih = (string) ($row['tarih'] ?? '');
            if ($hedefTarih < $ilkGun || $hedefTarih > $sonGun) {
                continue;
            }

            $anahtar = (int) $row['ogrenci_id'] . '|' . $hedefTarih;
            $mevcut = $hatirlatmalar[$anahtar] ?? null;
            if ($mevcut && (string) $mevcut['hatirlatma_turu'] === 'son_ders_yenileme') {
                continue;
            }

            $hatirlatmalar[$anahtar] = [
                'id' => 'yenileme-' . (int) $row['kaynak_randevu_id'],
                'ogrenci_id' => (int) $row['ogrenci_id'],
                'telafi_hakki_id' => null,
                'katilim_yaniti' => null,
                'katilim_yanit_tarihi' => null,
                'tarih' => $hedefTarih,
                'baslangic_saati' => $row['baslangic_saati'],
                'bitis_saati' => $row['bitis_saati'],
                'durum' => 'yenileme_hatirlatma',
                'tur' => $row['tur'],
                'ogrenci' => $row['ogrenci'],
                'grup' => $row['grup'],
                'telafi_kaynak_tarih' => null,
                'telafi_kaynak_saat' => null,
                'takvim_turu' => 'yenileme_hatirlatma',
                'hatirlatma_turu' => $row['hatirlatma_turu'],
                'onceki_randevu_tarihi' => $row['onceki_randevu_tarihi'],
            ];
        }

        return array_values($hatirlatmalar);
    }

    public static function durumlar(): array
    {
        return ['planlandi', 'geldi', 'gelmedi', 'mazeretli_gelmedi', 'gec_iptal', 'kurum_iptali', 'ertelendi', 'tamamlandi'];
    }

    public static function durumGecerliMi(string $durum): bool
    {
        return in_array($durum, self::durumlar(), true);
    }

    private static function randevuHakkiniGeriAl(int $randevuId): void
    {
        $db = self::db();
        $stmt = $db->prepare('SELECT * FROM hak_hareketleri WHERE randevu_id = :randevu_id ORDER BY id ASC');
        $stmt->execute(['randevu_id' => $randevuId]);
        $hareketler = $stmt->fetchAll();

        foreach ($hareketler as $hareket) {
            $paketId = (int) ($hareket['paket_id'] ?? 0);
            $miktar = abs((int) ($hareket['miktar'] ?? 0));
            if ($paketId < 1 || $miktar < 1) {
                continue;
            }

            if (($hareket['hak_turu'] ?? '') === 'normal') {
                $guncelle = $db->prepare(
                    'UPDATE paketler
                     SET kullanilan_normal_hak = GREATEST(0, kullanilan_normal_hak - :miktar_kullanilan),
                         kalan_normal_hak = LEAST(toplam_normal_hak, kalan_normal_hak + :miktar_kalan)
                     WHERE id = :id'
                );
                $guncelle->execute(['id' => $paketId, 'miktar_kullanilan' => $miktar, 'miktar_kalan' => $miktar]);
            }

            if (($hareket['hak_turu'] ?? '') === 'telafi' && !self::kaynakRandevununPlanlanmisTelafisiVarMi($randevuId)) {
                $guncelle = $db->prepare(
                    'UPDATE paketler
                     SET kullanilan_telafi_hak = GREATEST(0, kullanilan_telafi_hak - :miktar_kullanilan),
                         kalan_telafi_hak = LEAST(toplam_telafi_hak, kalan_telafi_hak + :miktar_kalan)
                     WHERE id = :id'
                );
                $guncelle->execute(['id' => $paketId, 'miktar_kullanilan' => $miktar, 'miktar_kalan' => $miktar]);
            }
        }

        $telafiStmt = $db->prepare('SELECT id FROM telafi_haklari WHERE kaynak_randevu_id = :randevu_id');
        $telafiStmt->execute(['randevu_id' => $randevuId]);
        $telafiIdleri = array_values(array_map('intval', array_column($telafiStmt->fetchAll(), 'id')));
        if ($telafiIdleri !== []) {
            $yerTutucular = implode(',', array_fill(0, count($telafiIdleri), '?'));
            $db->prepare("UPDATE telafi_haklari SET durum = 'iptal' WHERE id IN ($yerTutucular) AND durum = 'planlanmayi_bekliyor'")->execute($telafiIdleri);
        }

        $sil = $db->prepare('DELETE FROM hak_hareketleri WHERE randevu_id = :randevu_id');
        $sil->execute(['randevu_id' => $randevuId]);

        $telafiStmt = $db->prepare('SELECT telafi_hakki_id FROM randevular WHERE id = :id LIMIT 1');
        $telafiStmt->execute(['id' => $randevuId]);
        $telafiHakkiId = (int) $telafiStmt->fetchColumn();
        if ($telafiHakkiId > 0) {
            self::telafiRandevusunuGeriAl($telafiHakkiId);
        }
    }

    private static function telafiRandevusunuGeriAl(int $telafiHakkiId): void
    {
        if ($telafiHakkiId < 1) {
            return;
        }

        $stmt = self::db()->prepare(
            'UPDATE telafi_haklari
             SET durum = "planlanmayi_bekliyor"
             WHERE id = :id
               AND durum IN ("planlandi", "kullanildi")'
        );
        $stmt->execute(['id' => $telafiHakkiId]);
    }

    private static function randevuHakkiniIsle(int $randevuId, string $durum, int $kullaniciId): void
    {
        if (!in_array($durum, ['geldi', 'tamamlandi', 'gelmedi', 'mazeretli_gelmedi', 'gec_iptal', 'kurum_iptali'], true)) {
            return;
        }

        $db = self::db();
        $hareketVarMi = $db->prepare('SELECT COUNT(*) FROM hak_hareketleri WHERE randevu_id = :randevu_id');
        $hareketVarMi->execute(['randevu_id' => $randevuId]);
        if ((int) $hareketVarMi->fetchColumn() > 0) {
            return;
        }

        $stmt = $db->prepare('SELECT * FROM randevular WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $randevuId]);
        $randevu = $stmt->fetch();
        if (!$randevu) {
            return;
        }

        if (!empty($randevu['telafi_hakki_id'])) {
            if (in_array($durum, ['geldi', 'tamamlandi'], true)) {
                TelafiHakki::tamamla((int) $randevu['telafi_hakki_id']);

                if (!empty($randevu['paket_id'])) {
                    $paketStmt = $db->prepare('SELECT * FROM paketler WHERE id = :id FOR UPDATE');
                    $paketStmt->execute(['id' => $randevu['paket_id']]);
                    $paket = $paketStmt->fetch();
                    if ($paket && $paket['paket_durumu'] === 'aktif') {
                        self::normalHakDus(
                            $paket,
                            $randevu,
                            'telafi_randevusu_geldi',
                            'Telafi randevusuna geldi, normal ders hakki dusuldu.',
                            $kullaniciId
                        );
                    }
                }
            }
            return;
        }

        if ($durum === 'kurum_iptali' && empty($randevu['paket_id'])) {
            if (self::kaynakRandevununPlanlanmisTelafisiVarMi($randevuId)) {
                self::telafiTekrariEngelle($randevu, null, 'kurum_iptali_telafi_tekrar_engellendi', $kullaniciId);
                return;
            }
            self::kurumIptaliTelafiOlustur(null, $randevu, $kullaniciId);
            return;
        }

        if (empty($randevu['paket_id'])) {
            return;
        }

        $paketStmt = $db->prepare('SELECT * FROM paketler WHERE id = :id FOR UPDATE');
        $paketStmt->execute(['id' => $randevu['paket_id']]);
        $paket = $paketStmt->fetch();
        if (!$paket || $paket['paket_durumu'] !== 'aktif') {
            return;
        }

        if ($durum === 'kurum_iptali') {
            if (self::kaynakRandevununPlanlanmisTelafisiVarMi($randevuId)) {
                self::telafiTekrariEngelle($randevu, $paket, 'kurum_iptali_telafi_tekrar_engellendi', $kullaniciId);
                return;
            }
            self::kurumIptaliTelafiOlustur($paket, $randevu, $kullaniciId);
            return;
        }

        if (in_array($durum, ['geldi', 'tamamlandi'], true)) {
            self::normalHakDus($paket, $randevu, 'randevu_geldi', 'Randevuya geldi, normal ders hakki dusuldu.', $kullaniciId);
            return;
        }

        if (self::kaynakRandevununPlanlanmisTelafisiVarMi($randevuId)) {
            self::telafiTekrariEngelle($randevu, $paket, 'gelmedi_telafi_tekrar_engellendi', $kullaniciId);
            return;
        }

        if ((int) $paket['kalan_telafi_hak'] > 0) {
            self::telafiHakKullan($paket, $randevu, $kullaniciId);
            return;
        }

        self::normalHakDus($paket, $randevu, 'telafi_hakki_bitti_gelmedi', 'Telafi hakki olmadigi icin gelmeyen randevu normal haktan dusuldu.', $kullaniciId);
    }

    private static function kurumIptaliTelafiOlustur(?array $paket, array $randevu, int $kullaniciId): void
    {
        $db = self::db();
        $telafi = $db->prepare(
            'INSERT INTO telafi_haklari (ogrenci_id, paket_id, kaynak_randevu_id, durum, son_kullanim_tarihi, aciklama, olusturulma_tarihi)
             VALUES (:ogrenci_id, :paket_id, :kaynak_randevu_id, "planlanmayi_bekliyor", DATE_ADD(:tarih, INTERVAL 30 DAY), :aciklama, NOW())'
        );
        $telafi->execute([
            'ogrenci_id' => $randevu['ogrenci_id'],
            'paket_id' => !empty($paket['id']) ? (int) $paket['id'] : null,
            'kaynak_randevu_id' => $randevu['id'],
            'tarih' => $randevu['tarih'],
            'aciklama' => 'Kurum iptali nedeniyle hak dusmeden telafi planlama hakki olusturuldu.',
        ]);

        self::hakHareketiEkle([
            'ogrenci_id' => (int) $randevu['ogrenci_id'],
            'paket_id' => !empty($paket['id']) ? (int) $paket['id'] : null,
            'randevu_id' => (int) $randevu['id'],
            'hareket_turu' => 'kurum_iptali_telafi_olusturuldu',
            'hak_turu' => 'telafi',
            'miktar' => 0,
            'onceki_kalan' => (int) ($paket['kalan_telafi_hak'] ?? 0),
            'sonraki_kalan' => (int) ($paket['kalan_telafi_hak'] ?? 0),
            'aciklama' => 'Kurum iptali nedeniyle telafi olusturuldu; ogrencinin telafi hakki dusulmedi.',
            'olusturan_kullanici_id' => $kullaniciId ?: null,
        ]);
    }

    private static function telafiHakKullan(array $paket, array $randevu, int $kullaniciId): void
    {
        $db = self::db();
        $onceki = (int) $paket['kalan_telafi_hak'];
        $sonraki = max(0, $onceki - 1);

        $guncelle = $db->prepare(
            'UPDATE paketler
             SET kullanilan_telafi_hak = kullanilan_telafi_hak + 1,
                 kalan_telafi_hak = :kalan
             WHERE id = :id'
        );
        $guncelle->execute(['id' => $paket['id'], 'kalan' => $sonraki]);

        $telafi = $db->prepare(
            'INSERT INTO telafi_haklari (ogrenci_id, paket_id, kaynak_randevu_id, durum, son_kullanim_tarihi, aciklama, olusturulma_tarihi)
             VALUES (:ogrenci_id, :paket_id, :kaynak_randevu_id, "planlanmayi_bekliyor", DATE_ADD(:tarih, INTERVAL 30 DAY), :aciklama, NOW())'
        );
        $telafi->execute([
            'ogrenci_id' => $randevu['ogrenci_id'],
            'paket_id' => $paket['id'],
            'kaynak_randevu_id' => $randevu['id'],
            'tarih' => $randevu['tarih'],
            'aciklama' => 'Gelmeyen randevu icin telafi hakki kullanildi.',
        ]);

        self::hakHareketiEkle([
            'ogrenci_id' => (int) $randevu['ogrenci_id'],
            'paket_id' => (int) $paket['id'],
            'randevu_id' => (int) $randevu['id'],
            'hareket_turu' => 'gelmedi_telafi_kullanildi',
            'hak_turu' => 'telafi',
            'miktar' => -1,
            'onceki_kalan' => $onceki,
            'sonraki_kalan' => $sonraki,
            'aciklama' => 'Ogrenci gelmedi, telafi hakki kullanildi ve telafi planlama hakki olusturuldu.',
            'olusturan_kullanici_id' => $kullaniciId ?: null,
        ]);
    }

    private static function kaynakRandevununPlanlanmisTelafisiVarMi(int $randevuId): bool
    {
        $stmt = self::db()->prepare(
            'SELECT COUNT(*)
             FROM telafi_haklari th
             LEFT JOIN randevular tr ON tr.telafi_hakki_id = th.id
             WHERE th.kaynak_randevu_id = :randevu_id
               AND (th.durum IN ("planlandi", "kullanildi") OR tr.id IS NOT NULL)'
        );
        $stmt->execute(['randevu_id' => $randevuId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private static function telafiTekrariEngelle(array $randevu, ?array $paket, string $hareketTuru, int $kullaniciId): void
    {
        $kalan = (int) ($paket['kalan_telafi_hak'] ?? 0);
        self::hakHareketiEkle([
            'ogrenci_id' => (int) $randevu['ogrenci_id'],
            'paket_id' => !empty($paket['id']) ? (int) $paket['id'] : null,
            'randevu_id' => (int) $randevu['id'],
            'hareket_turu' => $hareketTuru,
            'hak_turu' => 'telafi',
            'miktar' => 0,
            'onceki_kalan' => $kalan,
            'sonraki_kalan' => $kalan,
            'aciklama' => 'Bu kaynak randevu icin planlanmis telafi bulundugundan ikinci telafi hakki olusturulmadi.',
            'olusturan_kullanici_id' => $kullaniciId ?: null,
        ]);
    }

    private static function normalHakDus(array $paket, array $randevu, string $hareketTuru, string $aciklama, int $kullaniciId): void
    {
        $db = self::db();
        $onceki = (int) $paket['kalan_normal_hak'];
        if ($onceki <= 0) {
            self::hakHareketiEkle([
                'ogrenci_id' => (int) $randevu['ogrenci_id'],
                'paket_id' => (int) $paket['id'],
                'randevu_id' => (int) $randevu['id'],
                'hareket_turu' => 'normal_hak_yok',
                'hak_turu' => 'normal',
                'miktar' => 0,
                'onceki_kalan' => 0,
                'sonraki_kalan' => 0,
                'aciklama' => 'Dusulecek normal ders hakki bulunamadi.',
                'olusturan_kullanici_id' => $kullaniciId ?: null,
            ]);
            return;
        }
        $sonraki = max(0, $onceki - 1);

        $guncelle = $db->prepare(
            'UPDATE paketler
             SET kullanilan_normal_hak = kullanilan_normal_hak + 1,
                 kalan_normal_hak = :kalan
             WHERE id = :id'
        );
        $guncelle->execute(['id' => $paket['id'], 'kalan' => $sonraki]);

        self::hakHareketiEkle([
            'ogrenci_id' => (int) $randevu['ogrenci_id'],
            'paket_id' => (int) $paket['id'],
            'randevu_id' => (int) $randevu['id'],
            'hareket_turu' => $hareketTuru,
            'hak_turu' => 'normal',
            'miktar' => -1,
            'onceki_kalan' => $onceki,
            'sonraki_kalan' => $sonraki,
            'aciklama' => $aciklama,
            'olusturan_kullanici_id' => $kullaniciId ?: null,
        ]);
    }

    private static function hakHareketiEkle(array $veri): void
    {
        $stmt = self::db()->prepare(
            'INSERT INTO hak_hareketleri
             (ogrenci_id, paket_id, randevu_id, hareket_turu, hak_turu, miktar, onceki_kalan, sonraki_kalan, aciklama, olusturan_kullanici_id, olusturulma_tarihi)
             VALUES
             (:ogrenci_id, :paket_id, :randevu_id, :hareket_turu, :hak_turu, :miktar, :onceki_kalan, :sonraki_kalan, :aciklama, :olusturan_kullanici_id, NOW())'
        );
        $stmt->execute($veri);
    }

    private static function randevuPaketIdleri(array $idler): array
    {
        $idler = self::temizIdler($idler);
        if ($idler === []) {
            return [];
        }

        $yerTutucular = implode(',', array_fill(0, count($idler), '?'));
        $stmt = self::db()->prepare("SELECT DISTINCT paket_id FROM randevular WHERE id IN ($yerTutucular) AND paket_id IS NOT NULL");
        $stmt->execute($idler);
        return array_values(array_map('intval', array_column($stmt->fetchAll(), 'paket_id')));
    }

    private static function randevulariGetir(array $idler): array
    {
        $idler = self::temizIdler($idler);
        if ($idler === []) {
            return [];
        }

        $yerTutucular = implode(',', array_fill(0, count($idler), '?'));
        $stmt = self::db()->prepare("SELECT id, paket_id, telafi_hakki_id FROM randevular WHERE id IN ($yerTutucular)");
        $stmt->execute($idler);
        return $stmt->fetchAll();
    }

    private static function paketSonDersGuncelle(int $paketId): void
    {
        if ($paketId <= 0) {
            return;
        }

        $stmt = self::db()->prepare('SELECT MAX(tarih) FROM randevular WHERE paket_id = :paket_id');
        $stmt->execute(['paket_id' => $paketId]);
        $sonTarih = $stmt->fetchColumn() ?: null;

        $guncelle = self::db()->prepare('UPDATE paketler SET tahmini_son_ders_tarihi = :tarih WHERE id = :id');
        $guncelle->execute([
            'id' => $paketId,
            'tarih' => $sonTarih,
        ]);
    }

    private static function temizIdler(array $idler): array
    {
        $temiz = [];
        foreach ($idler as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $temiz[] = $id;
            }
        }
        return array_values(array_unique($temiz));
    }

    private static function saatDegeri(string $saat): string
    {
        if (preg_match('/^\d{2}:\d{2}$/', $saat)) {
            return $saat . ':00';
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $saat)) {
            return $saat;
        }
        return '09:00:00';
    }

    private static function sureDakika(string $baslangic, string $bitis): int
    {
        $fark = (strtotime($bitis) ?: 0) - (strtotime($baslangic) ?: 0);
        return max(15, (int) round($fark / 60));
    }
}
