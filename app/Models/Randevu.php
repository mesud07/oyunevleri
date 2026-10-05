<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Model;
use App\Models\TelafiHakki;
final class Randevu extends Model
{
    private static bool $performansIndeksleriHazir = false;

    public static function ozet(): array
    {
        self::performansIndeksleriniHazirla();
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
        self::performansIndeksleriniHazirla();
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
        $mevcutStmt = self::db()->prepare(
            'SELECT katilim_token_hash, katilim_token_iptal_tarihi
             FROM randevular
             WHERE id = :id AND kurum_id = :kurum_id
             LIMIT 1'
        );
        $mevcutStmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $mevcut = $mevcutStmt->fetch();
        if (!$mevcut || !empty($mevcut['katilim_token_iptal_tarihi'])) {
            return null;
        }

        $mevcutHash = trim((string) ($mevcut['katilim_token_hash'] ?? ''));
        if ($mevcutHash !== '') {
            // Hatirlatma cron'u ayni randevuyu tekrar tarayabilir. Daha once veliye
            // gonderilen tokeni asla degistirme; aksi halde eski SMS linki bozulur.
            $gonderilmisToken = self::katilimTokeniniSmsKaydindanBul($id, $mevcutHash)
                ?? self::katilimTokeniniSmsKaydindanBul($id);
            if ($gonderilmisToken !== null) {
                return $gonderilmisToken;
            }

            // Hash olusturulmus ancak token hicbir SMS'e yazilmamissa veliye ulasmis
            // korunacak bir link yoktur. Bu durum, link degiskeninin kullanilmadigi
            // bir sablon icin erkenden token uretilmesinden kaynaklanabilir. Hatirlatma
            // SMS'inin bos linkle gitmemesi icin sadece bu yetim hash'i guvenle yenile.
            return self::yetimKatilimTokeniniYenile($id, $mevcutHash);
        }

        $token = bin2hex(random_bytes(24));
        $stmt = self::db()->prepare(
            'UPDATE randevular
             SET katilim_token = NULL,
                 katilim_token_hash = :token_hash,
                 katilim_token_son_kullanim = TIMESTAMP(tarih, baslangic_saati),
                 katilim_token_iptal_tarihi = NULL
             WHERE id = :id AND kurum_id = :kurum_id
               AND katilim_token_hash IS NULL'
        );
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId(), 'token_hash' => hash('sha256', $token)]);
        if ($stmt->rowCount() > 0) {
            return $token;
        }

        // Eszamanli iki islemden digeri token urettiyse onun gonderim kaydini kullan.
        $mevcutStmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $mevcut = $mevcutStmt->fetch();
        return $mevcut
            ? self::katilimTokeniniSmsKaydindanBul($id, (string) ($mevcut['katilim_token_hash'] ?? ''))
            : null;
    }

    private static function yetimKatilimTokeniniYenile(int $id, string $mevcutHash): ?string
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $mevcutHash)) {
            return null;
        }

        $token = bin2hex(random_bytes(24));
        $stmt = self::db()->prepare(
            'UPDATE randevular
             SET katilim_token = NULL,
                 katilim_token_hash = :yeni_hash,
                 katilim_token_son_kullanim = TIMESTAMP(tarih, baslangic_saati),
                 katilim_token_iptal_tarihi = NULL
             WHERE id = :id AND kurum_id = :kurum_id
               AND katilim_token_hash = :mevcut_hash
               AND katilim_token_iptal_tarihi IS NULL'
        );
        $stmt->execute([
            'id' => $id,
            'kurum_id' => self::kurumId(),
            'yeni_hash' => hash('sha256', $token),
            'mevcut_hash' => $mevcutHash,
        ]);
        if ($stmt->rowCount() > 0) {
            return $token;
        }

        // Eszamanli islem hash'i degistirdiyse yalnizca SMS'e kaydedilmis tokeni
        // kullan; yeni bir token daha uretip diger islemin linkini gecersiz kilma.
        return self::katilimTokeniniSmsKaydindanBul($id);
    }

    public static function katilimTokenIleBul(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{48,80}$/', $token)) {
            return null;
        }

        $select = 'SELECT r.*, CONCAT(o.ad, " ", o.soyad) AS ogrenci,
                    COALESCE(CONCAT(uz.ad, " ", uz.soyad), "-") AS uzman,
                    COALESCE(g.ad, "Cocuk Etkinlik ve Oyun Evi") AS grup,
                    COALESCE(p.paket_adi, r.tur) AS paket_adi,
                    (TIMESTAMP(r.tarih, r.baslangic_saati) > NOW()) AS katilim_acik
             FROM randevular r
             INNER JOIN ogrenciler o ON o.id = r.ogrenci_id AND o.kurum_id = r.kurum_id
             LEFT JOIN gruplar g ON g.id = r.grup_id AND g.kurum_id = r.kurum_id
             LEFT JOIN kullanicilar uz ON uz.id = r.ogretmen_id AND uz.kurum_id = r.kurum_id
             LEFT JOIN paketler p ON p.id = r.paket_id AND p.kurum_id = r.kurum_id ';
        $stmt = self::db()->prepare(
            $select .
            'WHERE r.katilim_token_hash = :token_hash
               AND r.katilim_token_iptal_tarihi IS NULL
             LIMIT 1'
        );
        $stmt->execute(['token_hash' => hash('sha256', $token)]);
        $randevu = $stmt->fetch();
        if ($randevu) {
            return $randevu;
        }

        // Eski cron surumu token hash'ini her calismada degistiriyordu. SMS'te
        // gercekten gonderildigi kayitli olan eski linkleri de randevuya bagla.
        $stmt = self::db()->prepare(
            $select .
            'INNER JOIN sms_kayitlari sk
                    ON sk.randevu_id = r.id AND sk.kurum_id = r.kurum_id
             WHERE sk.mesaj LIKE :link_deseni
               AND r.katilim_token_iptal_tarihi IS NULL
             ORDER BY sk.id DESC
             LIMIT 1'
        );
        $stmt->execute(['link_deseni' => '%?t=' . strtolower($token) . '%']);
        $randevu = $stmt->fetch();
        return $randevu ?: null;
    }

    public static function katilimYanitiKaydet(string $token, string $yanit): bool
    {
        if (!preg_match('/^[a-f0-9]{48,80}$/', $token)
            || !in_array($yanit, ['katilacagim', 'katilamayacagim'], true)) {
            return false;
        }

        $stmt = self::db()->prepare(
            'UPDATE randevular r
             SET r.katilim_yaniti = :yanit, r.katilim_yanit_tarihi = NOW()
             WHERE r.katilim_token_iptal_tarihi IS NULL
               AND TIMESTAMP(r.tarih, r.baslangic_saati) > NOW()
               AND (
                    r.katilim_token_hash = :token_hash
                    OR EXISTS (
                        SELECT 1
                        FROM sms_kayitlari sk
                        WHERE sk.randevu_id = r.id
                          AND sk.kurum_id = r.kurum_id
                          AND sk.mesaj LIKE :link_deseni
                    )
               )'
        );
        $stmt->execute([
            'token_hash' => hash('sha256', $token),
            'link_deseni' => '%?t=' . strtolower($token) . '%',
            'yanit' => $yanit,
        ]);
        return $stmt->rowCount() > 0;
    }

    private static function katilimTokeniniSmsKaydindanBul(int $randevuId, ?string $beklenenHash = null): ?string
    {
        if ($beklenenHash !== null && !preg_match('/^[a-f0-9]{64}$/', $beklenenHash)) {
            return null;
        }

        $stmt = self::db()->prepare(
            'SELECT mesaj
             FROM sms_kayitlari
             WHERE kurum_id = :kurum_id
               AND randevu_id = :randevu_id
               AND mesaj LIKE :link_deseni
             ORDER BY id DESC
             LIMIT 20'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'randevu_id' => $randevuId,
            'link_deseni' => '%/randevu-katilim?t=%',
        ]);

        foreach ($stmt->fetchAll() as $kayit) {
            if (!preg_match_all('/[?&]t=([a-f0-9]{48,80})/i', (string) ($kayit['mesaj'] ?? ''), $eslesmeler)) {
                continue;
            }
            foreach ($eslesmeler[1] as $aday) {
                $aday = strtolower((string) $aday);
                if ($beklenenHash === null || hash_equals($beklenenHash, hash('sha256', $aday))) {
                    return $aday;
                }
            }
        }

        // Mevcut veli linkini korumak icin hash'i yenileme. Kayit bulunamiyorsa
        // yeni bir link uretmek eski SMS'i gecersiz kilacagindan null donulur.
        return null;
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
        $idler = array_values(array_map('intval', array_column(self::randevulariGetir($idler), 'id')));
        if ($idler === []) {
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
        $idler = array_values(array_map('intval', array_column(self::randevulariGetir($idler), 'id')));
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
        self::performansIndeksleriniHazirla();
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
               AND r.durum IN ("geldi", "tamamlandi")
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
                      AND rr.durum NOT IN ("iptal", "kurum_iptali")
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

    /**
     * Paylasimli sunucuda SQL migration komutu calistirilamadigi icin randevu
     * sorgularinin ihtiyac duydugu indeksleri ilk kullanimda bir kez hazirlar.
     * Basari bilgisi storage altinda tutulur; sonraki isteklerde veritabanina
     * sema kontrol sorgusu gonderilmez.
     */
    private static function performansIndeksleriniHazirla(): void
    {
        if (self::$performansIndeksleriHazir) {
            return;
        }
        self::$performansIndeksleriHazir = true;

        $storage = defined('BASE_PATH') ? BASE_PATH . '/storage' : '';
        if ($storage === '') {
            return;
        }
        if (!is_dir($storage)) {
            @mkdir($storage, 0770, true);
        }

        $surumDosyasi = $storage . '/randevu-performans-index-v1.ready';
        if (is_file($surumDosyasi)) {
            return;
        }

        $hataDosyasi = $storage . '/randevu-performans-index-v1.failed';
        if (is_file($hataDosyasi) && time() - (int) filemtime($hataDosyasi) < 3600) {
            return;
        }

        $kilit = @fopen($storage . '/randevu-performans-index.lock', 'c');
        if (!$kilit || !flock($kilit, LOCK_EX)) {
            if (is_resource($kilit)) {
                fclose($kilit);
            }
            return;
        }

        try {
            if (is_file($surumDosyasi)) {
                return;
            }

            $db = self::db();
            $indeksler = [
                'idx_randevular_kurum_tarih_saat' => '(kurum_id, tarih, baslangic_saati)',
                'idx_randevular_kurum_durum' => '(kurum_id, durum)',
                'idx_randevular_kurum_ogrenci_tarih_durum' => '(kurum_id, ogrenci_id, tarih, durum)',
            ];

            foreach ($indeksler as $ad => $kolonlar) {
                $stmt = $db->query("SHOW INDEX FROM randevular WHERE Key_name = " . $db->quote($ad));
                if ($stmt && $stmt->fetch()) {
                    continue;
                }
                $db->exec("ALTER TABLE randevular ADD INDEX `{$ad}` {$kolonlar}");
            }

            @file_put_contents($surumDosyasi, date(DATE_ATOM), LOCK_EX);
            @chmod($surumDosyasi, 0640);
            if (is_file($hataDosyasi)) {
                @unlink($hataDosyasi);
            }
        } catch (\Throwable $e) {
            @file_put_contents($hataDosyasi, date(DATE_ATOM), LOCK_EX);
            error_log('[randevu-performans-index] ' . $e->getMessage());
        } finally {
            flock($kilit, LOCK_UN);
            fclose($kilit);
        }
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
        $stmt = $db->prepare('SELECT * FROM hak_hareketleri WHERE randevu_id = :randevu_id AND kurum_id = :kurum_id ORDER BY id ASC');
        $stmt->execute(['randevu_id' => $randevuId, 'kurum_id' => self::kurumId()]);
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
                     WHERE id = :id AND kurum_id = :kurum_id'
                );
                $guncelle->execute(['id' => $paketId, 'kurum_id' => self::kurumId(), 'miktar_kullanilan' => $miktar, 'miktar_kalan' => $miktar]);
            }

            if (($hareket['hak_turu'] ?? '') === 'telafi' && !self::kaynakRandevununPlanlanmisTelafisiVarMi($randevuId)) {
                $guncelle = $db->prepare(
                    'UPDATE paketler
                     SET kullanilan_telafi_hak = GREATEST(0, kullanilan_telafi_hak - :miktar_kullanilan),
                         kalan_telafi_hak = LEAST(toplam_telafi_hak, kalan_telafi_hak + :miktar_kalan)
                     WHERE id = :id AND kurum_id = :kurum_id'
                );
                $guncelle->execute(['id' => $paketId, 'kurum_id' => self::kurumId(), 'miktar_kullanilan' => $miktar, 'miktar_kalan' => $miktar]);
            }
        }

        $telafiStmt = $db->prepare('SELECT id FROM telafi_haklari WHERE kaynak_randevu_id = :randevu_id AND kurum_id = :kurum_id');
        $telafiStmt->execute(['randevu_id' => $randevuId, 'kurum_id' => self::kurumId()]);
        $telafiIdleri = array_values(array_map('intval', array_column($telafiStmt->fetchAll(), 'id')));
        if ($telafiIdleri !== []) {
            $yerTutucular = implode(',', array_fill(0, count($telafiIdleri), '?'));
            $db->prepare("UPDATE telafi_haklari SET durum = 'iptal' WHERE kurum_id = ? AND id IN ($yerTutucular) AND durum = 'planlanmayi_bekliyor'")->execute(array_merge([self::kurumId()], $telafiIdleri));
        }

        $sil = $db->prepare('DELETE FROM hak_hareketleri WHERE randevu_id = :randevu_id AND kurum_id = :kurum_id');
        $sil->execute(['randevu_id' => $randevuId, 'kurum_id' => self::kurumId()]);

        $telafiStmt = $db->prepare('SELECT telafi_hakki_id FROM randevular WHERE id = :id AND kurum_id = :kurum_id LIMIT 1');
        $telafiStmt->execute(['id' => $randevuId, 'kurum_id' => self::kurumId()]);
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
               AND kurum_id = :kurum_id
               AND durum IN ("planlandi", "kullanildi")'
        );
        $stmt->execute(['id' => $telafiHakkiId, 'kurum_id' => self::kurumId()]);
    }

    private static function randevuHakkiniIsle(int $randevuId, string $durum, int $kullaniciId): void
    {
        if (!in_array($durum, ['geldi', 'tamamlandi', 'gelmedi', 'mazeretli_gelmedi', 'gec_iptal', 'kurum_iptali'], true)) {
            return;
        }

        $db = self::db();
        $hareketVarMi = $db->prepare('SELECT COUNT(*) FROM hak_hareketleri WHERE randevu_id = :randevu_id AND kurum_id = :kurum_id');
        $hareketVarMi->execute(['randevu_id' => $randevuId, 'kurum_id' => self::kurumId()]);
        if ((int) $hareketVarMi->fetchColumn() > 0) {
            return;
        }

        $stmt = $db->prepare('SELECT * FROM randevular WHERE id = :id AND kurum_id = :kurum_id LIMIT 1');
        $stmt->execute(['id' => $randevuId, 'kurum_id' => self::kurumId()]);
        $randevu = $stmt->fetch();
        if (!$randevu) {
            return;
        }

        if (!empty($randevu['telafi_hakki_id'])) {
            if (in_array($durum, ['geldi', 'tamamlandi'], true)) {
                TelafiHakki::tamamla((int) $randevu['telafi_hakki_id']);

                if (!empty($randevu['paket_id'])) {
                    $paketStmt = $db->prepare('SELECT * FROM paketler WHERE id = :id AND kurum_id = :kurum_id FOR UPDATE');
                    $paketStmt->execute(['id' => $randevu['paket_id'], 'kurum_id' => self::kurumId()]);
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

            $ogrenciIptali = in_array($durum, ['gelmedi', 'mazeretli_gelmedi', 'gec_iptal'], true)
                || ($durum === 'kurum_iptali' && ($randevu['katilim_yaniti'] ?? null) === 'katilamayacagim');
            if (
                $ogrenciIptali
                && self::kurumIptaliKaynakliTelafiMi((int) $randevu['telafi_hakki_id'])
            ) {
                // Kurumun iptal ettigi ders icin verilen ucretsiz telafi bu randevuyla
                // tuketilir. Ogrencinin paket telafi hakki varsa iptal edilen telafi
                // dersi, yeni telafi kaydinin kaynagi olur.
                TelafiHakki::tamamla((int) $randevu['telafi_hakki_id']);

                if (empty($randevu['paket_id'])) {
                    return;
                }

                $paketStmt = $db->prepare('SELECT * FROM paketler WHERE id = :id AND kurum_id = :kurum_id FOR UPDATE');
                $paketStmt->execute(['id' => $randevu['paket_id'], 'kurum_id' => self::kurumId()]);
                $paket = $paketStmt->fetch();
                if (!$paket || $paket['paket_durumu'] !== 'aktif') {
                    return;
                }

                if (self::kaynakRandevununPlanlanmisTelafisiVarMi($randevuId)) {
                    self::telafiTekrariEngelle(
                        $randevu,
                        $paket,
                        'kurum_iptali_telafisi_tekrar_engellendi',
                        $kullaniciId
                    );
                    return;
                }

                if ((int) $paket['kalan_telafi_hak'] > 0) {
                    self::telafiHakKullan(
                        $paket,
                        $randevu,
                        $kullaniciId,
                        'kurum_iptali_telafisi_ogrenci_iptali',
                        'Kurum iptali telafi dersinin ogrenci tarafindan iptali icin telafi hakki kullanildi.',
                        'Kurum iptali telafi dersi ogrenci tarafindan iptal edildi; telafi hakki kullanildi ve yeni telafi planlama hakki olusturuldu.'
                    );
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

        $paketStmt = $db->prepare('SELECT * FROM paketler WHERE id = :id AND kurum_id = :kurum_id FOR UPDATE');
        $paketStmt->execute(['id' => $randevu['paket_id'], 'kurum_id' => self::kurumId()]);
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
            'INSERT INTO telafi_haklari (kurum_id, ogrenci_id, paket_id, kaynak_randevu_id, durum, son_kullanim_tarihi, aciklama, olusturulma_tarihi)
             VALUES (:kurum_id, :ogrenci_id, :paket_id, :kaynak_randevu_id, "planlanmayi_bekliyor", DATE_ADD(:tarih, INTERVAL 30 DAY), :aciklama, NOW())'
        );
        $telafi->execute([
            'kurum_id' => self::kurumId(),
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

    private static function telafiHakKullan(
        array $paket,
        array $randevu,
        int $kullaniciId,
        string $hareketTuru = 'gelmedi_telafi_kullanildi',
        string $telafiAciklama = 'Gelmeyen randevu icin telafi hakki kullanildi.',
        string $hareketAciklama = 'Ogrenci gelmedi, telafi hakki kullanildi ve telafi planlama hakki olusturuldu.'
    ): void
    {
        $db = self::db();
        $onceki = (int) $paket['kalan_telafi_hak'];
        $sonraki = max(0, $onceki - 1);

        $guncelle = $db->prepare(
            'UPDATE paketler
             SET kullanilan_telafi_hak = kullanilan_telafi_hak + 1,
                 kalan_telafi_hak = :kalan
             WHERE id = :id AND kurum_id = :kurum_id'
        );
        $guncelle->execute(['id' => $paket['id'], 'kurum_id' => self::kurumId(), 'kalan' => $sonraki]);

        $telafi = $db->prepare(
            'INSERT INTO telafi_haklari (kurum_id, ogrenci_id, paket_id, kaynak_randevu_id, durum, son_kullanim_tarihi, aciklama, olusturulma_tarihi)
             VALUES (:kurum_id, :ogrenci_id, :paket_id, :kaynak_randevu_id, "planlanmayi_bekliyor", DATE_ADD(:tarih, INTERVAL 30 DAY), :aciklama, NOW())'
        );
        $telafi->execute([
            'kurum_id' => self::kurumId(),
            'ogrenci_id' => $randevu['ogrenci_id'],
            'paket_id' => $paket['id'],
            'kaynak_randevu_id' => $randevu['id'],
            'tarih' => $randevu['tarih'],
            'aciklama' => $telafiAciklama,
        ]);

        self::hakHareketiEkle([
            'ogrenci_id' => (int) $randevu['ogrenci_id'],
            'paket_id' => (int) $paket['id'],
            'randevu_id' => (int) $randevu['id'],
            'hareket_turu' => $hareketTuru,
            'hak_turu' => 'telafi',
            'miktar' => -1,
            'onceki_kalan' => $onceki,
            'sonraki_kalan' => $sonraki,
            'aciklama' => $hareketAciklama,
            'olusturan_kullanici_id' => $kullaniciId ?: null,
        ]);
    }

    private static function kurumIptaliKaynakliTelafiMi(int $telafiHakkiId): bool
    {
        if ($telafiHakkiId < 1) {
            return false;
        }

        $stmt = self::db()->prepare(
            'SELECT COUNT(*)
             FROM telafi_haklari th
             INNER JOIN randevular kr ON kr.id = th.kaynak_randevu_id AND kr.kurum_id = th.kurum_id
             WHERE th.id = :telafi_hakki_id
               AND th.kurum_id = :kurum_id
               AND kr.durum = "kurum_iptali"'
        );
        $stmt->execute([
            'telafi_hakki_id' => $telafiHakkiId,
            'kurum_id' => self::kurumId(),
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private static function kaynakRandevununPlanlanmisTelafisiVarMi(int $randevuId): bool
    {
        $stmt = self::db()->prepare(
            'SELECT COUNT(*)
             FROM telafi_haklari th
             LEFT JOIN randevular tr ON tr.telafi_hakki_id = th.id AND tr.kurum_id = th.kurum_id
             WHERE th.kaynak_randevu_id = :randevu_id
               AND th.kurum_id = :kurum_id
               AND (th.durum IN ("planlandi", "kullanildi") OR tr.id IS NOT NULL)'
        );
        $stmt->execute(['randevu_id' => $randevuId, 'kurum_id' => self::kurumId()]);
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
             WHERE id = :id AND kurum_id = :kurum_id'
        );
        $guncelle->execute(['id' => $paket['id'], 'kurum_id' => self::kurumId(), 'kalan' => $sonraki]);

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
             (kurum_id, ogrenci_id, paket_id, randevu_id, hareket_turu, hak_turu, miktar, onceki_kalan, sonraki_kalan, aciklama, olusturan_kullanici_id, olusturulma_tarihi)
             VALUES
             (:kurum_id, :ogrenci_id, :paket_id, :randevu_id, :hareket_turu, :hak_turu, :miktar, :onceki_kalan, :sonraki_kalan, :aciklama, :olusturan_kullanici_id, NOW())'
        );
        $veri['kurum_id'] = self::kurumId();
        $stmt->execute($veri);
    }

    private static function randevuPaketIdleri(array $idler): array
    {
        $idler = self::temizIdler($idler);
        if ($idler === []) {
            return [];
        }

        $yerTutucular = implode(',', array_fill(0, count($idler), '?'));
        $stmt = self::db()->prepare("SELECT DISTINCT paket_id FROM randevular WHERE kurum_id = ? AND id IN ($yerTutucular) AND paket_id IS NOT NULL");
        $stmt->execute(array_merge([self::kurumId()], $idler));
        return array_values(array_map('intval', array_column($stmt->fetchAll(), 'paket_id')));
    }

    private static function randevulariGetir(array $idler): array
    {
        $idler = self::temizIdler($idler);
        if ($idler === []) {
            return [];
        }

        $yerTutucular = implode(',', array_fill(0, count($idler), '?'));
        $stmt = self::db()->prepare("SELECT id, paket_id, telafi_hakki_id FROM randevular WHERE kurum_id = ? AND id IN ($yerTutucular)");
        $stmt->execute(array_merge([self::kurumId()], $idler));
        return $stmt->fetchAll();
    }

    private static function paketSonDersGuncelle(int $paketId): void
    {
        if ($paketId <= 0) {
            return;
        }

        $stmt = self::db()->prepare('SELECT MAX(tarih) FROM randevular WHERE paket_id = :paket_id AND kurum_id = :kurum_id AND durum NOT IN ("kurum_iptali", "ertelendi")');
        $stmt->execute(['paket_id' => $paketId, 'kurum_id' => self::kurumId()]);
        $sonTarih = $stmt->fetchColumn() ?: null;

        $guncelle = self::db()->prepare('UPDATE paketler SET tahmini_son_ders_tarihi = :tarih WHERE id = :id AND kurum_id = :kurum_id');
        $guncelle->execute([
            'id' => $paketId,
            'kurum_id' => self::kurumId(),
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
