<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Model;
final class Ogrenci extends Model
{
    public static function liste(string $arama = '', int $sayfa = 1, int $limit = 20): array
    {
        $karaListeVar = OgrenciKaraListe::tabloVarMi();
        $karaListeSelect = $karaListeVar ? 'MAX(CASE WHEN okl.id IS NULL THEN 0 ELSE 1 END) AS kara_liste_aktif,' : '0 AS kara_liste_aktif,';
        $karaListeJoin = $karaListeVar ? 'LEFT JOIN ogrenci_kara_liste okl ON okl.ogrenci_id = o.id AND okl.aktif = 1' : '';
        $where = 'WHERE o.kurum_id = :kurum_id';
        $params = ['kurum_id' => self::kurumId()];
        $sayfa = max(1, $sayfa);
        $limit = max(10, min(100, $limit));
        $offset = ($sayfa - 1) * $limit;
        $arama = trim($arama);
        if ($arama !== '') {
            $where .= ' AND (CONCAT(o.ad, " ", o.soyad) LIKE :arama_ogrenci
                    OR CONCAT(v.ad, " ", v.soyad) LIKE :arama_veli
                    OR o.durum LIKE :arama_durum';
            $params['arama_ogrenci'] = '%' . $arama . '%';
            $params['arama_veli'] = '%' . $arama . '%';
            $params['arama_durum'] = '%' . $arama . '%';

            $telefon = self::telefonRakamlari($arama);
            if ($telefon !== '') {
                $where .= ' OR TRIM(LEADING "0" FROM REGEXP_REPLACE(COALESCE(v.telefon, ""), "[^0-9]", "")) LIKE :telefon_veli
                    OR TRIM(LEADING "0" FROM REGEXP_REPLACE(COALESCE(v.yedek_telefon, ""), "[^0-9]", "")) LIKE :telefon_yedek
                    OR TRIM(LEADING "0" FROM REGEXP_REPLACE(COALESCE(o.acil_durum_telefon, ""), "[^0-9]", "")) LIKE :telefon_acil
                    OR TRIM(LEADING "0" FROM REGEXP_REPLACE(COALESCE(o.vasi_telefon, ""), "[^0-9]", "")) LIKE :telefon_vasi';
                $params['telefon_veli'] = '%' . $telefon . '%';
                $params['telefon_yedek'] = '%' . $telefon . '%';
                $params['telefon_acil'] = '%' . $telefon . '%';
                $params['telefon_vasi'] = '%' . $telefon . '%';
            }
            $where .= ')';
        }

        $sayStmt = self::db()->prepare(
            'SELECT COUNT(DISTINCT o.id)
             FROM ogrenciler o
             LEFT JOIN ogrenci_velileri ov ON ov.ogrenci_id = o.id AND ov.kurum_id = o.kurum_id
             LEFT JOIN veliler v ON v.id = ov.veli_id AND v.kurum_id = o.kurum_id
             ' . $karaListeJoin . '
             ' . $where
        );
        $sayStmt->execute($params);
        $toplam = (int) $sayStmt->fetchColumn();

        $stmt = self::db()->prepare(
            'SELECT o.id, o.ad, o.soyad, CONCAT(o.ad, " ", o.soyad) AS ad_soyad,
                    o.dogum_tarihi, o.cinsiyet, o.kayit_tarihi, o.durum, o.il, o.ilce, o.adres,
                    COALESCE(MAX(CASE WHEN ov.birincil_mi = 1 THEN v.telefon END), MAX(v.telefon), "") AS telefon,
                    MAX(CASE WHEN ov.birincil_mi = 1 THEN v.id END) AS birincil_veli_id,
                    COALESCE(MAX(CASE WHEN ov.birincil_mi = 1 THEN v.ad END), "") AS birincil_veli_ad,
                    COALESCE(MAX(CASE WHEN ov.birincil_mi = 1 THEN v.soyad END), "") AS birincil_veli_soyad,
                    COALESCE(MAX(CASE WHEN ov.birincil_mi = 1 THEN v.telefon END), "") AS birincil_veli_telefon,
                    ' . $karaListeSelect . '
                    GROUP_CONCAT(CONCAT(v.ad, " ", v.soyad) ORDER BY ov.birincil_mi DESC SEPARATOR ", ") AS veliler
             FROM ogrenciler o
             LEFT JOIN ogrenci_velileri ov ON ov.ogrenci_id = o.id AND ov.kurum_id = o.kurum_id
             LEFT JOIN veliler v ON v.id = ov.veli_id AND v.kurum_id = o.kurum_id
             ' . $karaListeJoin . '
             ' . $where . '
             GROUP BY o.id
             ORDER BY o.ad ASC, o.soyad ASC, o.id ASC
             LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $anahtar => $deger) {
            $stmt->bindValue($anahtar, $deger);
        }
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
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

    public static function telefonEslesmeleri(string $telefon): array
    {
        $rakamlar = self::telefonRakamlari($telefon);
        if ($rakamlar === '') {
            return [];
        }

        $stmt = self::db()->prepare(
            'SELECT o.id, CONCAT(o.ad, " ", o.soyad) AS ad_soyad, o.durum,
                    COALESCE(MAX(CASE WHEN ov.birincil_mi = 1 THEN v.telefon END), MAX(v.telefon), "") AS telefon,
                    GROUP_CONCAT(DISTINCT CONCAT(v.ad, " ", v.soyad) ORDER BY ov.birincil_mi DESC, v.ad ASC SEPARATOR ", ") AS veliler
             FROM ogrenciler o
             LEFT JOIN ogrenci_velileri ov ON ov.ogrenci_id = o.id AND ov.kurum_id = o.kurum_id
             LEFT JOIN veliler v ON v.id = ov.veli_id AND v.kurum_id = o.kurum_id
             WHERE o.kurum_id = :kurum_id
               AND (TRIM(LEADING "0" FROM REGEXP_REPLACE(COALESCE(v.telefon, ""), "[^0-9]", "")) = :telefon_veli
                OR TRIM(LEADING "0" FROM REGEXP_REPLACE(COALESCE(v.yedek_telefon, ""), "[^0-9]", "")) = :telefon_yedek
                OR TRIM(LEADING "0" FROM REGEXP_REPLACE(COALESCE(o.acil_durum_telefon, ""), "[^0-9]", "")) = :telefon_acil
                OR TRIM(LEADING "0" FROM REGEXP_REPLACE(COALESCE(o.vasi_telefon, ""), "[^0-9]", "")) = :telefon_vasi)
             GROUP BY o.id
             ORDER BY o.durum = "aktif" DESC, o.ad ASC, o.soyad ASC
             LIMIT 10'
        );
        $stmt->execute([
            'telefon_veli' => $rakamlar,
            'telefon_yedek' => $rakamlar,
            'telefon_acil' => $rakamlar,
            'telefon_vasi' => $rakamlar,
            'kurum_id' => self::kurumId(),
        ]);
        return $stmt->fetchAll();
    }

    public static function secenekler(): array
    {
        $stmt = self::db()->prepare(
            'SELECT id, CONCAT(ad, " ", soyad) AS ad_soyad
             FROM ogrenciler
             WHERE kurum_id = :kurum_id AND durum = "aktif"
             ORDER BY ad, soyad'
        );
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function smsAliciSecenekleri(array $ogrenciIdleri = []): array
    {
        $ogrenciIdleri = array_values(array_unique(array_filter(array_map('intval', $ogrenciIdleri), static fn(int $id): bool => $id > 0)));
        $where = 'WHERE o.kurum_id = ?';
        $parametreler = [self::kurumId()];
        if ($ogrenciIdleri !== []) {
            $where .= ' AND o.id IN (' . implode(',', array_fill(0, count($ogrenciIdleri), '?')) . ')';
            $parametreler = array_merge($parametreler, $ogrenciIdleri);
        }

        $stmt = self::db()->prepare(
            'SELECT o.id, CONCAT(o.ad, " ", o.soyad) AS ad_soyad, o.durum,
                    COALESCE(
                        NULLIF(MAX(CASE WHEN ov.birincil_mi = 1 THEN v.telefon END), ""),
                        NULLIF(MAX(v.telefon), ""),
                        NULLIF(o.acil_durum_telefon, ""),
                        ""
                    ) AS telefon,
                    COALESCE(MAX(CASE WHEN ov.birincil_mi = 1 THEN v.id END), MAX(v.id)) AS veli_id,
                    COALESCE(
                        NULLIF(MAX(CASE WHEN ov.birincil_mi = 1 THEN CONCAT(v.ad, " ", v.soyad) END), ""),
                        NULLIF(MAX(CONCAT(v.ad, " ", v.soyad)), ""),
                        o.acil_durum_kisi,
                        "Veli"
                    ) AS veli_adi
             FROM ogrenciler o
             LEFT JOIN ogrenci_velileri ov ON ov.ogrenci_id = o.id AND ov.kurum_id = o.kurum_id
             LEFT JOIN veliler v ON v.id = ov.veli_id AND v.kurum_id = o.kurum_id
             ' . $where . '
             GROUP BY o.id
             ORDER BY o.durum = "aktif" DESC, o.ad ASC, o.soyad ASC, o.id ASC'
        );
        $stmt->execute($parametreler);
        return $stmt->fetchAll();
    }

    public static function ozelNotListesi(string $arama = '', string $durum = 'aktif'): array
    {
        $where = [
            'kurum_id = :kurum_id',
            'profil_ozel_notu IS NOT NULL',
            'TRIM(profil_ozel_notu) <> ""',
        ];
        $params = ['kurum_id' => self::kurumId()];

        if (in_array($durum, ['aktif', 'pasif'], true)) {
            $where[] = 'durum = :durum';
            $params['durum'] = $durum;
        }

        $arama = trim($arama);
        if ($arama !== '') {
            $where[] = '(CONCAT(ad, " ", soyad) LIKE :arama_ad OR profil_ozel_notu LIKE :arama_not)';
            $params['arama_ad'] = '%' . $arama . '%';
            $params['arama_not'] = '%' . $arama . '%';
        }

        $stmt = self::db()->prepare(
            'SELECT id, ad, soyad, durum, profil_ozel_notu
             FROM ogrenciler
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY durum = "aktif" DESC, ad ASC, soyad ASC, id ASC
             LIMIT 500'
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function raporBilgisi(int $id): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT id, ad, soyad, dogum_tarihi
             FROM ogrenciler
             WHERE id = :id AND kurum_id = :kurum_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $ogrenci = $stmt->fetch();
        return $ogrenci ?: null;
    }

    public static function profil(int $id): ?array
    {
        $db = self::db();
        Paket::tahsilatNotuKolonunuHazirla();
        $stmt = $db->prepare(
            'SELECT id, ad, soyad, tc_kimlik_no, dogum_tarihi, cinsiyet, kayit_tarihi, durum,
                    acil_durum_kisi, acil_durum_telefon, saglik_bilgisi, alerji_bilgisi, ozel_durum_notu, profil_ozel_notu,
                    vasi_ad_soyad, vasi_tc_kimlik_no, vasi_telefon, yonetici_notu, ogretmen_notu,
                    il, ilce, adres, adres_enlem, adres_boylam, adres_konum_dogrulandi, adres_konum_guncellenme_tarihi
             FROM ogrenciler
             WHERE id = :id AND kurum_id = :kurum_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $ogrenci = $stmt->fetch();
        if (!$ogrenci) {
            return null;
        }

        $veliStmt = $db->prepare(
            'SELECT v.id, v.ad, v.soyad, v.tc_kimlik_no, v.telefon_ulke, v.telefon, v.yedek_telefon,
                    v.eposta, v.yakinlik, v.il, v.ilce, v.adres, v.notlar
             FROM ogrenci_velileri ov
             INNER JOIN veliler v ON v.id = ov.veli_id AND v.kurum_id = ov.kurum_id
             WHERE ov.ogrenci_id = :id AND ov.kurum_id = :kurum_id
             ORDER BY ov.birincil_mi DESC, v.ad ASC'
        );
        $veliStmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);

        $paketStmt = $db->prepare(
            'SELECT id, paket_adi, paket_sira_no, baslangic_tarihi, tahmini_son_ders_tarihi,
                    kalan_normal_hak, kalan_telafi_hak, net_paket_tutari, paket_durumu
             FROM paketler
             WHERE ogrenci_id = :id AND kurum_id = :kurum_id
             ORDER BY paket_sira_no DESC, id DESC
             LIMIT 10'
        );
        $paketStmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);

        $odemeOzetStmt = $db->prepare(
            'SELECT p.id AS paket_id, p.paket_adi, p.baslangic_tarihi, p.tahmini_son_ders_tarihi,
                    p.net_paket_tutari, p.tahsilat_notu, p.paket_durumu,
                    COALESCE(SUM(CASE WHEN od.iptal = 0 THEN od.tutar ELSE 0 END), 0) AS tahsilat,
                    p.net_paket_tutari - COALESCE(SUM(CASE WHEN od.iptal = 0 THEN od.tutar ELSE 0 END), 0) AS kalan_borc,
                    GROUP_CONCAT(CASE WHEN od.iptal = 0 THEN DATE_FORMAT(od.tarih, "%d.%m.%Y") END ORDER BY od.tarih ASC, od.id ASC SEPARATOR ", ") AS odeme_tarihleri
             FROM paketler p
             LEFT JOIN odemeler od ON od.paket_id = p.id AND od.kurum_id = p.kurum_id
             WHERE p.ogrenci_id = :id AND p.kurum_id = :kurum_id
             GROUP BY p.id, p.paket_adi, p.baslangic_tarihi, p.tahmini_son_ders_tarihi, p.net_paket_tutari, p.tahsilat_notu, p.paket_durumu, p.paket_sira_no
             ORDER BY p.paket_sira_no DESC, p.id DESC
             LIMIT 10'
        );
        $odemeOzetStmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);

        $faturaTablosu = (bool) $db->query(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'faturalar' LIMIT 1"
        )->fetchColumn();
        $faturaSelect = $faturaTablosu
            ? ', f.id AS fatura_id, f.fatura_no, f.belge_turu, f.yerel_durum AS fatura_yerel_durum, f.mysoft_durum AS fatura_mysoft_durum, f.provider_durum AS fatura_provider_durum, f.provider AS fatura_provider'
            : ', NULL AS fatura_id, NULL AS fatura_no, NULL AS belge_turu, NULL AS fatura_yerel_durum, NULL AS fatura_mysoft_durum, NULL AS fatura_provider_durum, NULL AS fatura_provider';
        $faturaJoin = $faturaTablosu
            ? 'LEFT JOIN faturalar f ON f.odeme_id = od.id AND f.kurum_id = od.kurum_id'
            : '';
        $odemeStmt = $db->prepare(
            'SELECT od.id, od.paket_id, od.tarih, od.tutar, od.yontem, od.makbuz_numarasi,
                    od.aciklama, od.iptal, p.paket_adi, COALESCE(k.ad, "-") AS kasa'
                    . $faturaSelect . '
             FROM odemeler od
             INNER JOIN paketler p ON p.id = od.paket_id AND p.kurum_id = od.kurum_id
             LEFT JOIN kasalar k ON k.id = od.kasa_id AND k.kurum_id = od.kurum_id
             ' . $faturaJoin . '
             WHERE od.ogrenci_id = :id AND od.kurum_id = :kurum_id
             ORDER BY od.tarih DESC, od.id DESC
             LIMIT 50'
        );
        $odemeStmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);

        $faturalar = [];
        if ($faturaTablosu) {
            $faturaStmt = $db->prepare(
                'SELECT id, fatura_no, ettn, fatura_tarihi, belge_turu, alici_adi,
                        genel_toplam, yerel_durum, mysoft_durum, provider_durum, provider, fallback_kullanildi, kaynak
                 FROM faturalar
                 WHERE ogrenci_id = :id AND kurum_id = :kurum_id
                 ORDER BY fatura_tarihi DESC, id DESC
                 LIMIT 50'
            );
            $faturaStmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
            $faturalar = $faturaStmt->fetchAll();
        }

        $randevuStmt = $db->prepare(
            'SELECT r.id, r.telafi_hakki_id, r.tarih, r.baslangic_saati, r.bitis_saati, r.tur, r.durum,
                    COALESCE(g.ad, "Cocuk Etkinlik ve Oyun Evi") AS grup,
                    COALESCE(p.paket_adi, r.tur) AS paket_adi,
                    kr.tarih AS telafi_kaynak_tarih,
                    kr.baslangic_saati AS telafi_kaynak_saat
             FROM randevular r
             LEFT JOIN gruplar g ON g.id = r.grup_id AND g.kurum_id = r.kurum_id
             LEFT JOIN paketler p ON p.id = r.paket_id AND p.kurum_id = r.kurum_id
             LEFT JOIN telafi_haklari th ON th.id = r.telafi_hakki_id AND th.kurum_id = r.kurum_id
             LEFT JOIN randevular kr ON kr.id = th.kaynak_randevu_id AND kr.kurum_id = r.kurum_id
             WHERE r.ogrenci_id = :id AND r.kurum_id = :kurum_id
             ORDER BY r.tarih DESC, r.baslangic_saati DESC
             LIMIT 50'
        );
        $randevuStmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);

        $onamStmt = $db->prepare(
            'SELECT id, veli_ad_soyad, veli_telefon, veli_eposta, ogrenci_ad_soyad,
                    ogrenci_dogum_tarihi, form_verisi_json, dijital_onay, onay_tarihi
             FROM veli_onam_kayitlari
             WHERE ogrenci_id = :id AND kurum_id = :kurum_id
             ORDER BY onay_tarihi DESC, id DESC
             LIMIT 1'
        );
        $onamStmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $veliOnam = $onamStmt->fetch() ?: null;
        if ($veliOnam) {
            $formVerisi = json_decode((string) ($veliOnam['form_verisi_json'] ?? ''), true);
            $veliOnam['form_verisi'] = is_array($formVerisi) ? $formVerisi : [];
        }

        return [
            'ogrenci' => $ogrenci,
            'veliler' => $veliStmt->fetchAll(),
            'paketler' => $paketStmt->fetchAll(),
            'odeme_ozeti' => $odemeOzetStmt->fetchAll(),
            'odemeler' => $odemeStmt->fetchAll(),
            'faturalar' => $faturalar,
            'fatura_entegrasyonu_hazir' => $faturaTablosu,
            'randevular' => $randevuStmt->fetchAll(),
            'gunluk_notlar' => GunlukKayit::ogrenciAkisi($id),
            'kara_liste_kayitlari' => OgrenciKaraListe::ogrenciKayitlari($id),
            'kara_liste_aktif' => OgrenciKaraListe::aktifKayit($id),
            'telafiler' => TelafiHakki::bekleyenler($id),
            'veli_onam' => $veliOnam,
        ];
    }

    public static function ozelNotGuncelle(int $id, string $not): bool
    {
        $stmt = self::db()->prepare(
            'UPDATE ogrenciler
             SET profil_ozel_notu = :profil_ozel_notu
             WHERE id = :id AND kurum_id = :kurum_id'
        );
        $stmt->execute([
            'id' => $id,
            'kurum_id' => self::kurumId(),
            'profil_ozel_notu' => $not !== '' ? $not : null,
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function hizliGuncelle(int $id, array $ogrenci, array $veli): bool
    {
        $db = self::db();
        try {
            $db->beginTransaction();

            $adresStmt = $db->prepare(
                'SELECT il, ilce, adres, adres_enlem, adres_boylam, adres_konum_dogrulandi, adres_konum_guncellenme_tarihi
                 FROM ogrenciler
                 WHERE id = :id AND kurum_id = :kurum_id
                 FOR UPDATE'
            );
            $adresStmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
            $mevcutAdres = $adresStmt->fetch() ?: [];
            $adresDegisti = trim((string) ($mevcutAdres['il'] ?? '')) !== $ogrenci['il']
                || trim((string) ($mevcutAdres['ilce'] ?? '')) !== $ogrenci['ilce']
                || trim((string) ($mevcutAdres['adres'] ?? '')) !== $ogrenci['adres'];

            $stmt = $db->prepare(
                'UPDATE ogrenciler
                 SET ad = :ad,
                     soyad = :soyad,
                     dogum_tarihi = :dogum_tarihi,
                     cinsiyet = :cinsiyet,
                     kayit_tarihi = :kayit_tarihi,
                     durum = :durum,
                     il = :il,
                     ilce = :ilce,
                     adres = :adres,
                     adres_enlem = :adres_enlem,
                     adres_boylam = :adres_boylam,
                     adres_konum_dogrulandi = :adres_konum_dogrulandi,
                     adres_konum_guncellenme_tarihi = :adres_konum_guncellenme_tarihi
                 WHERE id = :id AND kurum_id = :kurum_id'
            );
            $stmt->execute([
                'id' => $id,
                'kurum_id' => self::kurumId(),
                'ad' => $ogrenci['ad'],
                'soyad' => $ogrenci['soyad'],
                'dogum_tarihi' => $ogrenci['dogum_tarihi'] ?: null,
                'cinsiyet' => $ogrenci['cinsiyet'],
                'kayit_tarihi' => $ogrenci['kayit_tarihi'],
                'durum' => $ogrenci['durum'],
                'il' => $ogrenci['il'] ?: null,
                'ilce' => $ogrenci['ilce'] ?: null,
                'adres' => $ogrenci['adres'] ?: null,
                'adres_enlem' => $adresDegisti ? null : ($mevcutAdres['adres_enlem'] ?? null),
                'adres_boylam' => $adresDegisti ? null : ($mevcutAdres['adres_boylam'] ?? null),
                'adres_konum_dogrulandi' => $adresDegisti ? 0 : (int) ($mevcutAdres['adres_konum_dogrulandi'] ?? 0),
                'adres_konum_guncellenme_tarihi' => $adresDegisti ? null : ($mevcutAdres['adres_konum_guncellenme_tarihi'] ?? null),
            ]);

            $veliId = (int) ($veli['id'] ?? 0);
            if ($veliId > 0) {
                $veliStmt = $db->prepare(
                    'UPDATE veliler v
                     INNER JOIN ogrenci_velileri ov
                        ON ov.veli_id = v.id
                       AND ov.ogrenci_id = :ogrenci_id
                       AND ov.kurum_id = v.kurum_id
                       AND ov.birincil_mi = 1
                     SET v.ad = :ad,
                         v.soyad = :soyad,
                         v.telefon = :telefon
                     WHERE v.id = :veli_id AND v.kurum_id = :kurum_id'
                );
                $veliStmt->execute([
                    'ogrenci_id' => $id,
                    'veli_id' => $veliId,
                    'kurum_id' => self::kurumId(),
                    'ad' => $veli['ad'],
                    'soyad' => $veli['soyad'],
                    'telefon' => $veli['telefon'],
                ]);
            } elseif ($veli['ad'] !== '' && $veli['soyad'] !== '' && $veli['telefon'] !== '') {
                $yeniVeliId = self::veliBulVeyaKaydet($veli);
                $bagla = $db->prepare(
                    'INSERT IGNORE INTO ogrenci_velileri (kurum_id, ogrenci_id, veli_id, birincil_mi, acil_durum_mu)
                     VALUES (:kurum_id, :ogrenci_id, :veli_id, 1, 1)'
                );
                $bagla->execute([
                    'kurum_id' => self::kurumId(),
                    'ogrenci_id' => $id,
                    'veli_id' => $yeniVeliId,
                ]);
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

    public static function profilGuncelle(int $id, array $veri): bool
    {
        $db = self::db();
        try {
            $db->beginTransaction();

            $adresStmt = $db->prepare('SELECT il, ilce, adres, adres_enlem, adres_boylam, adres_konum_dogrulandi, adres_konum_guncellenme_tarihi FROM ogrenciler WHERE id = :id AND kurum_id = :kurum_id');
            $adresStmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
            $mevcutAdres = $adresStmt->fetch() ?: [];
            $adresDegisti = trim((string) ($mevcutAdres['il'] ?? '')) !== trim((string) ($veri['ogrenci']['il'] ?? ''))
                || trim((string) ($mevcutAdres['ilce'] ?? '')) !== trim((string) ($veri['ogrenci']['ilce'] ?? ''))
                || trim((string) ($mevcutAdres['adres'] ?? '')) !== trim((string) ($veri['ogrenci']['adres'] ?? ''));

            $stmt = $db->prepare(
                'UPDATE ogrenciler
                 SET ad = :ad,
                     soyad = :soyad,
                     tc_kimlik_no = :tc_kimlik_no,
                     dogum_tarihi = :dogum_tarihi,
                     cinsiyet = :cinsiyet,
                     kayit_tarihi = :kayit_tarihi,
                     durum = :durum,
                     acil_durum_kisi = :acil_durum_kisi,
                     acil_durum_telefon = :acil_durum_telefon,
                     saglik_bilgisi = :saglik_bilgisi,
                     alerji_bilgisi = :alerji_bilgisi,
                     ozel_durum_notu = :ozel_durum_notu,
                     vasi_ad_soyad = :vasi_ad_soyad,
                     vasi_tc_kimlik_no = :vasi_tc_kimlik_no,
                     vasi_telefon = :vasi_telefon,
                     yonetici_notu = :yonetici_notu,
                     ogretmen_notu = :ogretmen_notu,
                     il = :il,
                     ilce = :ilce,
                     adres = :adres,
                     adres_enlem = :adres_enlem,
                     adres_boylam = :adres_boylam,
                     adres_konum_dogrulandi = :adres_konum_dogrulandi,
                     adres_konum_guncellenme_tarihi = :adres_konum_guncellenme_tarihi
                 WHERE id = :id AND kurum_id = :kurum_id'
            );
            $stmt->execute([
                'id' => $id,
                'kurum_id' => self::kurumId(),
                'ad' => $veri['ogrenci']['ad'],
                'soyad' => $veri['ogrenci']['soyad'],
                'tc_kimlik_no' => $veri['ogrenci']['tc_kimlik_no'] ?: null,
                'dogum_tarihi' => $veri['ogrenci']['dogum_tarihi'] ?: null,
                'cinsiyet' => $veri['ogrenci']['cinsiyet'] ?: 'belirtilmedi',
                'kayit_tarihi' => $veri['ogrenci']['kayit_tarihi'] ?: date('Y-m-d'),
                'durum' => $veri['ogrenci']['durum'] ?: 'aktif',
                'acil_durum_kisi' => $veri['ogrenci']['acil_durum_kisi'] ?: null,
                'acil_durum_telefon' => $veri['ogrenci']['acil_durum_telefon'] ?: null,
                'saglik_bilgisi' => $veri['ogrenci']['saglik_bilgisi'] ?: null,
                'alerji_bilgisi' => $veri['ogrenci']['alerji_bilgisi'] ?: null,
                'ozel_durum_notu' => $veri['ogrenci']['ozel_durum_notu'] ?: null,
                'vasi_ad_soyad' => $veri['ogrenci']['vasi_ad_soyad'] ?: null,
                'vasi_tc_kimlik_no' => $veri['ogrenci']['vasi_tc_kimlik_no'] ?: null,
                'vasi_telefon' => $veri['ogrenci']['vasi_telefon'] ?: null,
                'yonetici_notu' => $veri['ogrenci']['yonetici_notu'] ?: null,
                'ogretmen_notu' => $veri['ogrenci']['ogretmen_notu'] ?: null,
                'il' => ($veri['ogrenci']['il'] ?? '') ?: null,
                'ilce' => ($veri['ogrenci']['ilce'] ?? '') ?: null,
                'adres' => ($veri['ogrenci']['adres'] ?? '') ?: null,
                'adres_enlem' => $adresDegisti ? null : ($mevcutAdres['adres_enlem'] ?? null),
                'adres_boylam' => $adresDegisti ? null : ($mevcutAdres['adres_boylam'] ?? null),
                'adres_konum_dogrulandi' => $adresDegisti ? 0 : (int) ($mevcutAdres['adres_konum_dogrulandi'] ?? 0),
                'adres_konum_guncellenme_tarihi' => $adresDegisti ? null : ($mevcutAdres['adres_konum_guncellenme_tarihi'] ?? null),
            ]);

            $veli = $veri['veli'] ?? [];
            $veliId = (int) ($veli['id'] ?? 0);
            if ($veliId > 0) {
                $veliStmt = $db->prepare(
                    'UPDATE veliler
                     SET ad = :ad,
                         soyad = :soyad,
                         tc_kimlik_no = :tc_kimlik_no,
                         telefon_ulke = :telefon_ulke,
                         telefon = :telefon,
                         yedek_telefon = :yedek_telefon,
                         eposta = :eposta,
                         yakinlik = :yakinlik,
                         il = :il,
                         ilce = :ilce,
                         adres = :adres,
                         notlar = :notlar
                     WHERE id = :id AND kurum_id = :kurum_id'
                );
                $veliStmt->execute([
                    'id' => $veliId,
                    'kurum_id' => self::kurumId(),
                    'ad' => $veli['ad'],
                    'soyad' => $veli['soyad'],
                    'tc_kimlik_no' => $veli['tc_kimlik_no'] ?: null,
                    'telefon_ulke' => $veli['telefon_ulke'] ?: 'Turkiye',
                    'telefon' => $veli['telefon'],
                    'yedek_telefon' => $veli['yedek_telefon'] ?: null,
                    'eposta' => $veli['eposta'] ?: null,
                    'yakinlik' => $veli['yakinlik'] ?: null,
                    'il' => $veli['il'] ?: null,
                    'ilce' => $veli['ilce'] ?: null,
                    'adres' => $veli['adres'] ?: null,
                    'notlar' => $veli['notlar'] ?: null,
                ]);
            } elseif (!empty($veli['ad']) && !empty($veli['soyad']) && !empty($veli['telefon'])) {
                $yeniVeliId = self::veliBulVeyaKaydet($veli);
                $bagla = $db->prepare(
                    'INSERT IGNORE INTO ogrenci_velileri (kurum_id, ogrenci_id, veli_id, birincil_mi, acil_durum_mu)
                     VALUES (:kurum_id, :ogrenci_id, :veli_id, 1, 1)'
                );
                $bagla->execute(['kurum_id' => self::kurumId(), 'ogrenci_id' => $id, 'veli_id' => $yeniVeliId]);
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

    public static function sil(int $id): bool
    {
        if ($id < 1 || !self::profil($id)) {
            return false;
        }

        $db = self::db();
        try {
            $db->beginTransaction();

            $veliStmt = $db->prepare('SELECT veli_id FROM ogrenci_velileri WHERE ogrenci_id = :ogrenci_id');
            $veliStmt->execute(['ogrenci_id' => $id]);
            $veliIdleri = array_values(array_map('intval', array_column($veliStmt->fetchAll(), 'veli_id')));

            $randevuStmt = $db->prepare('SELECT id FROM randevular WHERE ogrenci_id = :ogrenci_id');
            $randevuStmt->execute(['ogrenci_id' => $id]);
            $randevuIdleri = array_values(array_map('intval', array_column($randevuStmt->fetchAll(), 'id')));
            if ($randevuIdleri !== []) {
                $yerTutucular = implode(',', array_fill(0, count($randevuIdleri), '?'));
                $db->prepare("DELETE FROM yoklamalar WHERE randevu_id IN ($yerTutucular)")->execute($randevuIdleri);
                $db->prepare("DELETE FROM randevular WHERE id IN ($yerTutucular)")->execute($randevuIdleri);
            }

            $telafiStmt = $db->prepare('SELECT id FROM telafi_haklari WHERE ogrenci_id = :ogrenci_id');
            $telafiStmt->execute(['ogrenci_id' => $id]);
            $telafiIdleri = array_values(array_map('intval', array_column($telafiStmt->fetchAll(), 'id')));
            if ($telafiIdleri !== []) {
                $yerTutucular = implode(',', array_fill(0, count($telafiIdleri), '?'));
                $db->prepare("DELETE FROM telafi_onerileri WHERE telafi_hakki_id IN ($yerTutucular)")->execute($telafiIdleri);
            }

            $db->prepare('DELETE FROM telafi_haklari WHERE ogrenci_id = :ogrenci_id')->execute(['ogrenci_id' => $id]);
            $db->prepare('DELETE FROM yoklamalar WHERE ogrenci_id = :ogrenci_id')->execute(['ogrenci_id' => $id]);
            $db->prepare('DELETE FROM hak_hareketleri WHERE ogrenci_id = :ogrenci_id')->execute(['ogrenci_id' => $id]);
            $db->prepare('DELETE FROM odeme_sozleri WHERE ogrenci_id = :ogrenci_id')->execute(['ogrenci_id' => $id]);
            $db->prepare('DELETE FROM odemeler WHERE ogrenci_id = :ogrenci_id')->execute(['ogrenci_id' => $id]);
            $db->prepare('DELETE FROM paket_disi_haklar WHERE ogrenci_id = :ogrenci_id')->execute(['ogrenci_id' => $id]);
            $db->prepare('DELETE FROM grup_ogrencileri WHERE ogrenci_id = :ogrenci_id')->execute(['ogrenci_id' => $id]);
            $db->prepare('DELETE FROM paketler WHERE ogrenci_id = :ogrenci_id')->execute(['ogrenci_id' => $id]);
            $db->prepare('DELETE FROM ogrenci_velileri WHERE ogrenci_id = :ogrenci_id')->execute(['ogrenci_id' => $id]);

            $stmt = $db->prepare('DELETE FROM ogrenciler WHERE id = :id');
            $stmt->execute(['id' => $id]);

            foreach ($veliIdleri as $veliId) {
                $say = $db->prepare('SELECT COUNT(*) FROM ogrenci_velileri WHERE veli_id = :veli_id');
                $say->execute(['veli_id' => $veliId]);
                if ((int) $say->fetchColumn() === 0) {
                    $db->prepare('DELETE FROM veliler WHERE id = :id')->execute(['id' => $veliId]);
                }
            }

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
        $db->beginTransaction();

        $stmt = $db->prepare(
            'INSERT INTO ogrenciler
             (kurum_id, ad, soyad, dogum_tarihi, cinsiyet, kayit_tarihi, durum, acil_durum_kisi, acil_durum_telefon, saglik_bilgisi, alerji_bilgisi, ozel_durum_notu, yonetici_notu, ogretmen_notu, il, ilce, adres, olusturulma_tarihi)
             VALUES
             (:kurum_id, :ad, :soyad, :dogum_tarihi, :cinsiyet, :kayit_tarihi, :durum, :acil_durum_kisi, :acil_durum_telefon, :saglik_bilgisi, :alerji_bilgisi, :ozel_durum_notu, :yonetici_notu, :ogretmen_notu, :il, :ilce, :adres, NOW())'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'ad' => $veri['ad'],
            'soyad' => $veri['soyad'],
            'dogum_tarihi' => $veri['dogum_tarihi'] ?: null,
            'cinsiyet' => $veri['cinsiyet'] ?: 'belirtilmedi',
            'kayit_tarihi' => $veri['kayit_tarihi'] ?: date('Y-m-d'),
            'durum' => $veri['durum'] ?: 'aktif',
            'acil_durum_kisi' => $veri['acil_durum_kisi'] ?: null,
            'acil_durum_telefon' => $veri['acil_durum_telefon'] ?: null,
            'saglik_bilgisi' => $veri['saglik_bilgisi'] ?: null,
            'alerji_bilgisi' => $veri['alerji_bilgisi'] ?: null,
            'ozel_durum_notu' => $veri['ozel_durum_notu'] ?: null,
            'yonetici_notu' => $veri['yonetici_notu'] ?: null,
            'ogretmen_notu' => $veri['ogretmen_notu'] ?: null,
            'il' => ($veri['il'] ?? '') ?: null,
            'ilce' => ($veri['ilce'] ?? '') ?: null,
            'adres' => ($veri['adres'] ?? '') ?: null,
        ]);

        $ogrenciId = (int) $db->lastInsertId();
        $veliId = (int) ($veri['veli_id'] ?? 0);
        if ($veliId > 0) {
            $bagla = $db->prepare(
                'INSERT INTO ogrenci_velileri (kurum_id, ogrenci_id, veli_id, birincil_mi, acil_durum_mu)
                 VALUES (:kurum_id, :ogrenci_id, :veli_id, 1, 1)'
            );
            $bagla->execute(['kurum_id' => self::kurumId(), 'ogrenci_id' => $ogrenciId, 'veli_id' => $veliId]);
        }

        $db->commit();
        return $ogrenciId;
    }

    public static function veliIleEkle(array $veri): int
    {
        $db = self::db();
        try {
            $db->beginTransaction();

            $veli = $veri['veli'];
            $veliId = self::veliBulVeyaKaydet($veli);

            $ogrenci = $veri['ogrenci'];
            $ogrenciStmt = $db->prepare(
                'INSERT INTO ogrenciler
                 (kurum_id, ad, soyad, tc_kimlik_no, dogum_tarihi, cinsiyet, kayit_tarihi, durum, acil_durum_kisi, acil_durum_telefon, saglik_bilgisi, alerji_bilgisi, ozel_durum_notu, vasi_ad_soyad, vasi_tc_kimlik_no, vasi_telefon, yonetici_notu, ogretmen_notu, il, ilce, adres, olusturulma_tarihi)
                 VALUES
                 (:kurum_id, :ad, :soyad, :tc_kimlik_no, :dogum_tarihi, :cinsiyet, :kayit_tarihi, :durum, :acil_durum_kisi, :acil_durum_telefon, :saglik_bilgisi, :alerji_bilgisi, :ozel_durum_notu, :vasi_ad_soyad, :vasi_tc_kimlik_no, :vasi_telefon, :yonetici_notu, :ogretmen_notu, :il, :ilce, :adres, NOW())'
            );
            $ogrenciStmt->execute([
                'kurum_id' => self::kurumId(),
                'ad' => $ogrenci['ad'],
                'soyad' => $ogrenci['soyad'],
                'tc_kimlik_no' => $ogrenci['tc_kimlik_no'] ?: null,
                'dogum_tarihi' => $ogrenci['dogum_tarihi'] ?: null,
                'cinsiyet' => $ogrenci['cinsiyet'] ?: 'belirtilmedi',
                'kayit_tarihi' => $ogrenci['kayit_tarihi'] ?: date('Y-m-d'),
                'durum' => 'aktif',
                'acil_durum_kisi' => $ogrenci['acil_durum_kisi'] ?: null,
                'acil_durum_telefon' => $ogrenci['acil_durum_telefon'] ?: null,
                'saglik_bilgisi' => $ogrenci['saglik_bilgisi'] ?: null,
                'alerji_bilgisi' => $ogrenci['alerji_bilgisi'] ?: null,
                'ozel_durum_notu' => $ogrenci['ozel_durum_notu'] ?: null,
                'vasi_ad_soyad' => $ogrenci['vasi_ad_soyad'] ?: null,
                'vasi_tc_kimlik_no' => $ogrenci['vasi_tc_kimlik_no'] ?: null,
                'vasi_telefon' => $ogrenci['vasi_telefon'] ?: null,
                'yonetici_notu' => $ogrenci['yonetici_notu'] ?: null,
                'ogretmen_notu' => $ogrenci['ogretmen_notu'] ?: null,
                'il' => ($ogrenci['il'] ?? '') ?: null,
                'ilce' => ($ogrenci['ilce'] ?? '') ?: null,
                'adres' => ($ogrenci['adres'] ?? '') ?: null,
            ]);
            $ogrenciId = (int) $db->lastInsertId();

            $bagla = $db->prepare(
                'INSERT INTO ogrenci_velileri (kurum_id, ogrenci_id, veli_id, birincil_mi, acil_durum_mu)
                 VALUES (:kurum_id, :ogrenci_id, :veli_id, 1, 1)'
            );
            $bagla->execute(['kurum_id' => self::kurumId(), 'ogrenci_id' => $ogrenciId, 'veli_id' => $veliId]);

            $db->commit();
            return $ogrenciId;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function topluAktarimEslesmesiVarMi(
        string $tcKimlikNo,
        string $ad,
        string $soyad,
        string $dogumTarihi,
        string $veliTelefon
    ): bool {
        if ($tcKimlikNo !== '') {
            $stmt = self::db()->prepare(
                'SELECT 1 FROM ogrenciler
                 WHERE kurum_id = :kurum_id AND tc_kimlik_no = :tc_kimlik_no
                 LIMIT 1'
            );
            $stmt->execute(['kurum_id' => self::kurumId(), 'tc_kimlik_no' => $tcKimlikNo]);
            if ($stmt->fetchColumn()) {
                return true;
            }
        }

        $telefon = self::telefonRakamlari($veliTelefon);
        $stmt = self::db()->prepare(
            'SELECT 1
             FROM ogrenciler o
             INNER JOIN ogrenci_velileri ov ON ov.ogrenci_id = o.id AND ov.kurum_id = o.kurum_id
             INNER JOIN veliler v ON v.id = ov.veli_id AND v.kurum_id = o.kurum_id
             WHERE o.kurum_id = :kurum_id
               AND o.ad = :ad
               AND o.soyad = :soyad
               AND COALESCE(o.dogum_tarihi, "") = :dogum_tarihi
               AND TRIM(LEADING "0" FROM REGEXP_REPLACE(v.telefon, "[^0-9]", "")) = :telefon
             LIMIT 1'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'ad' => $ad,
            'soyad' => $soyad,
            'dogum_tarihi' => $dogumTarihi,
            'telefon' => $telefon,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    private static function telefonIleVeliId(string $telefon): int
    {
        $rakamlar = self::telefonRakamlari($telefon);
        if ($rakamlar === '') {
            return 0;
        }

        $stmt = self::db()->prepare(
            'SELECT id
             FROM veliler
             WHERE kurum_id = :kurum_id
               AND TRIM(LEADING "0" FROM REGEXP_REPLACE(telefon, "[^0-9]", "")) = :telefon
             LIMIT 1'
        );
        $stmt->execute(['telefon' => $rakamlar, 'kurum_id' => self::kurumId()]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private static function telefonRakamlari(string $telefon): string
    {
        $rakamlar = preg_replace('/\D+/', '', $telefon) ?? '';
        if (str_starts_with($rakamlar, '90') && strlen($rakamlar) === 12) {
            $rakamlar = substr($rakamlar, 2);
        }
        if (str_starts_with($rakamlar, '0')) {
            $rakamlar = substr($rakamlar, 1);
        }

        return $rakamlar;
    }

    private static function veliBulVeyaKaydet(array $veli): int
    {
        $db = self::db();
        $veliId = self::telefonIleVeliId((string) ($veli['telefon'] ?? ''));
        if ($veliId > 0) {
            $veliGuncelle = $db->prepare(
                'UPDATE veliler
                 SET ad = COALESCE(NULLIF(:ad, ""), ad),
                     soyad = COALESCE(NULLIF(:soyad, ""), soyad),
                     tc_kimlik_no = COALESCE(NULLIF(:tc_kimlik_no, ""), tc_kimlik_no),
                     telefon_ulke = COALESCE(NULLIF(:telefon_ulke, ""), telefon_ulke),
                     yedek_telefon = COALESCE(NULLIF(:yedek_telefon, ""), yedek_telefon),
                     eposta = COALESCE(NULLIF(:eposta, ""), eposta),
                     yakinlik = COALESCE(NULLIF(:yakinlik, ""), yakinlik),
                     il = COALESCE(NULLIF(:il, ""), il),
                     ilce = COALESCE(NULLIF(:ilce, ""), ilce),
                     adres = COALESCE(NULLIF(:adres, ""), adres),
                     notlar = COALESCE(NULLIF(:notlar, ""), notlar)
                 WHERE id = :id AND kurum_id = :kurum_id'
            );
            $veliGuncelle->execute([
                'id' => $veliId,
                'kurum_id' => self::kurumId(),
                'ad' => $veli['ad'] ?? '',
                'soyad' => $veli['soyad'] ?? '',
                'tc_kimlik_no' => $veli['tc_kimlik_no'] ?? '',
                'telefon_ulke' => $veli['telefon_ulke'] ?? 'Turkiye',
                'yedek_telefon' => $veli['yedek_telefon'] ?? '',
                'eposta' => $veli['eposta'] ?? '',
                'yakinlik' => $veli['yakinlik'] ?? '',
                'il' => $veli['il'] ?? '',
                'ilce' => $veli['ilce'] ?? '',
                'adres' => $veli['adres'] ?? '',
                'notlar' => $veli['notlar'] ?? '',
            ]);
            return $veliId;
        }

        $veliStmt = $db->prepare(
            'INSERT INTO veliler
             (kurum_id, ad, soyad, tc_kimlik_no, telefon_ulke, telefon, yedek_telefon, eposta, yakinlik, il, ilce, adres, notlar, olusturulma_tarihi)
             VALUES
             (:kurum_id, :ad, :soyad, :tc_kimlik_no, :telefon_ulke, :telefon, :yedek_telefon, :eposta, :yakinlik, :il, :ilce, :adres, :notlar, NOW())'
        );
        $veliStmt->execute([
            'kurum_id' => self::kurumId(),
            'ad' => $veli['ad'],
            'soyad' => $veli['soyad'],
            'tc_kimlik_no' => ($veli['tc_kimlik_no'] ?? '') ?: null,
            'telefon_ulke' => ($veli['telefon_ulke'] ?? '') ?: 'Turkiye',
            'telefon' => $veli['telefon'],
            'yedek_telefon' => ($veli['yedek_telefon'] ?? '') ?: null,
            'eposta' => ($veli['eposta'] ?? '') ?: null,
            'yakinlik' => ($veli['yakinlik'] ?? '') ?: null,
            'il' => ($veli['il'] ?? '') ?: null,
            'ilce' => ($veli['ilce'] ?? '') ?: null,
            'adres' => ($veli['adres'] ?? '') ?: null,
            'notlar' => ($veli['notlar'] ?? '') ?: null,
        ]);

        return (int) $db->lastInsertId();
    }
}
