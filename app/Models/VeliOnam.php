<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class VeliOnam extends Model
{
    private const ONAM_METNI = "PROGRAM KURALLARI\n"
        . "• Çocuk hasta olduğunda programa gönderilmemelidir.\n"
        . "• Düzenli katılım, çocuğun uyumu açısından önemlidir.\n"
        . "• Ders sırasında telefon kullanımı minimumda tutulmalı; fotoğraf/video çekimleri diğer çocukların mahremiyetini ve ders akışını etkilemeyecek şekilde yalnızca kendi çocuğunuzu kapsamalıdır.\n"
        . "• Öğretmenlerimizin ders sırasında çektiği çocuğunuza ait görüntüleri ders sonunda isteyebilirsiniz.\n\n"
        . "PROGRAM KATILIM ONAMI\n"
        . "“Çocuğumun, uzman eşliğinde yürütülen oyun grubu çalışmalarına katılmasını kabul ediyorum. Bu çalışmaların çocukların sosyal, duygusal ve gelişimsel becerilerini desteklemeye yönelik grup etkinlikleri olduğunu biliyorum.”\n\n"
        . "Devam ve telafi sürecine ilişkin olarak; haftada 1 gün (aylık 4 ders) katılım sağlayan öğrenciler için ayda 1 telafi hakkı, haftada 2 gün (aylık 8 ders) katılım sağlayan öğrenciler için ise ayda 2 telafi hakkı bulunmaktadır.\n\n"
        . "Telafi dersleri, mümkün olması durumunda aynı hafta içerisinde farklı bir grupta planlanır. Uygunluk sağlanamaması halinde telafi, bir sonraki hafta içerisinde gerçekleştirilir. Belirtilen telafi haklarının aşılması durumunda dersler programdan düşülerek ilerler.";

    public static function ayar(): array
    {
        $kurumId = self::kurumId();
        $stmt = self::db()->prepare('SELECT * FROM onam_form_ayarlari WHERE kurum_id = :kurum_id LIMIT 1');
        $stmt->execute(['kurum_id' => $kurumId]);
        $ayar = $stmt->fetch();
        if ($ayar) {
            return $ayar;
        }

        $token = bin2hex(random_bytes(24));
        $ekle = self::db()->prepare(
            'INSERT INTO onam_form_ayarlari
                (kurum_id, public_token, baslik, aciklama, onam_metni, form_surumu, aktif)
             VALUES
                (:kurum_id, :public_token, :baslik, :aciklama, :onam_metni, 2, 1)'
        );
        $ekle->execute([
            'kurum_id' => $kurumId,
            'public_token' => $token,
            'baslik' => 'Oyun Grubu Katılımcı Bilgi ve Veli Onam Formu',
            'aciklama' => 'Çocuğunuza ve veliye ait bilgileri eksiksiz doldurunuz. Sağlık ve izin tercihlerinizi belirttikten sonra formu dijital olarak onaylayabilirsiniz.',
            'onam_metni' => self::ONAM_METNI,
        ]);

        $stmt->execute(['kurum_id' => $kurumId]);
        return $stmt->fetch() ?: [];
    }

    public static function tokenIleAyar(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{48}$/', $token)) {
            return null;
        }
        $stmt = self::db()->prepare(
            'SELECT a.*, k.ad AS kurum_adi
             FROM onam_form_ayarlari a
             INNER JOIN kurumlar k ON k.id = a.kurum_id AND k.aktif = 1
             WHERE a.public_token = :token AND a.aktif = 1
             LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $ayar = $stmt->fetch();
        return $ayar ?: null;
    }

    public static function onamMetniGuncelle(string $onamMetni): array
    {
        $onamMetni = trim($onamMetni);
        if ($onamMetni === '') {
            throw new \InvalidArgumentException('Program kuralları ve katılım onamı metni boş bırakılamaz.');
        }

        $ayar = self::ayar();
        $degisti = trim((string) ($ayar['onam_metni'] ?? '')) !== $onamMetni;
        if ($degisti) {
            $stmt = self::db()->prepare(
                'UPDATE onam_form_ayarlari
                 SET onam_metni = :onam_metni,
                     form_surumu = form_surumu + 1,
                     guncellenme_tarihi = NOW()
                 WHERE id = :id AND kurum_id = :kurum_id'
            );
            $stmt->execute([
                'onam_metni' => $onamMetni,
                'id' => (int) $ayar['id'],
                'kurum_id' => self::kurumId(),
            ]);
        }

        return self::ayar();
    }

    public static function liste(): array
    {
        self::eslesmeleriGuncelle();
        $stmt = self::db()->prepare(
            'SELECT ok.id, ok.veli_id, ok.ogrenci_id, ok.veli_ad_soyad, ok.veli_telefon,
                    ok.veli_eposta, ok.ogrenci_ad_soyad, ok.ogrenci_dogum_tarihi,
                    ok.form_surumu, ok.onay_tarihi,
                    CASE WHEN ok.veli_id IS NULL THEN 0 ELSE 1 END AS veli_eslesti,
                    CASE WHEN ok.ogrenci_id IS NULL THEN 0 ELSE 1 END AS ogrenci_eslesti
             FROM veli_onam_kayitlari ok
             WHERE ok.kurum_id = :kurum_id
             ORDER BY ok.onay_tarihi DESC, ok.id DESC
             LIMIT 500'
        );
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function kaydet(array $ayar, array $veri, array $istemci): int
    {
        $kurumId = (int) $ayar['kurum_id'];
        $telefon = self::telefonNormalize((string) $veri['veli_telefon']);
        if ($telefon === null) {
            throw new \InvalidArgumentException('Geçerli bir cep telefonu numarası girin.');
        }

        $eslesme = self::eslesmeBul($kurumId, $telefon, (string) $veri['ogrenci_ad_soyad']);
        $veliId = $eslesme['veli_id'];
        $ogrenciId = $eslesme['ogrenci_id'];

        $db = self::db();
        $stmt = $db->prepare(
            'INSERT INTO veli_onam_kayitlari
                (kurum_id, onam_form_ayari_id, veli_id, ogrenci_id, veli_ad_soyad, veli_telefon,
                 veli_eposta, ogrenci_ad_soyad, ogrenci_dogum_tarihi, form_surumu,
                 form_verisi_json, onam_metni, dijital_onay, ip_hash, tarayici_bilgisi, onay_tarihi)
             VALUES
                (:kurum_id, :ayar_id, :veli_id, :ogrenci_id, :veli_ad_soyad, :veli_telefon,
                 :veli_eposta, :ogrenci_ad_soyad, :ogrenci_dogum_tarihi, :form_surumu,
                 :form_verisi_json, :onam_metni, 1, :ip_hash, :tarayici_bilgisi, NOW())'
        );
        $stmt->execute([
            'kurum_id' => $kurumId,
            'ayar_id' => (int) $ayar['id'],
            'veli_id' => $veliId ?: null,
            'ogrenci_id' => $ogrenciId ?: null,
            'veli_ad_soyad' => trim((string) $veri['veli_ad_soyad']),
            'veli_telefon' => self::telefonGoster($telefon),
            'veli_eposta' => trim((string) ($veri['veli_eposta'] ?? '')) ?: null,
            'ogrenci_ad_soyad' => trim((string) $veri['ogrenci_ad_soyad']),
            'ogrenci_dogum_tarihi' => trim((string) ($veri['ogrenci_dogum_tarihi'] ?? '')) ?: null,
            'form_surumu' => (int) $ayar['form_surumu'],
            'form_verisi_json' => json_encode($veri['ek_bilgiler'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'onam_metni' => (string) $ayar['onam_metni'],
            'ip_hash' => (string) ($istemci['ip_hash'] ?? ''),
            'tarayici_bilgisi' => mb_substr((string) ($istemci['tarayici'] ?? ''), 0, 500),
        ]);
        $onamId = (int) $db->lastInsertId();
        if ($ogrenciId > 0) {
            self::ogrenciBilgileriniGuncelle($kurumId, $ogrenciId, $veliId, $veri);
        }
        return $onamId;
    }

    public static function bul(int $id): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT ok.*, a.baslik, k.ad AS kurum_adi
             FROM veli_onam_kayitlari ok
             INNER JOIN onam_form_ayarlari a ON a.id = ok.onam_form_ayari_id AND a.kurum_id = ok.kurum_id
             INNER JOIN kurumlar k ON k.id = ok.kurum_id
             WHERE ok.id = :id AND ok.kurum_id = :kurum_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $kayit = $stmt->fetch();
        return $kayit ?: null;
    }

    public static function telefonNormalize(string $telefon): ?string
    {
        $rakamlar = preg_replace('/\D+/', '', $telefon) ?? '';
        if (str_starts_with($rakamlar, '90') && strlen($rakamlar) === 12) {
            $rakamlar = substr($rakamlar, 2);
        }
        if (str_starts_with($rakamlar, '0')) {
            $rakamlar = substr($rakamlar, 1);
        }
        return preg_match('/^5\d{9}$/', $rakamlar) ? $rakamlar : null;
    }

    public static function ogrenciyeBagla(int $onamId, int $ogrenciId): bool
    {
        if ($onamId < 1 || $ogrenciId < 1) {
            return false;
        }

        $stmt = self::db()->prepare(
            'UPDATE veli_onam_kayitlari ok
             INNER JOIN ogrenciler o ON o.id = :ogrenci_id AND o.kurum_id = ok.kurum_id
             LEFT JOIN ogrenci_velileri ov ON ov.ogrenci_id = o.id AND ov.kurum_id = o.kurum_id AND ov.birincil_mi = 1
             SET ok.ogrenci_id = o.id,
                 ok.veli_id = COALESCE(ok.veli_id, ov.veli_id)
             WHERE ok.id = :onam_id AND ok.kurum_id = :kurum_id'
        );
        $stmt->execute([
            'ogrenci_id' => $ogrenciId,
            'onam_id' => $onamId,
            'kurum_id' => self::kurumId(),
        ]);
        $aktarildi = self::onamKaydiniOgrenciyeAktar($onamId, self::kurumId());
        return $stmt->rowCount() > 0 || $aktarildi;
    }

    private static function eslesmeleriGuncelle(): void
    {
        $kurumId = self::kurumId();
        if ($kurumId < 1) {
            return;
        }

        $stmt = self::db()->prepare(
            'SELECT id, veli_telefon, ogrenci_ad_soyad
             FROM veli_onam_kayitlari
             WHERE kurum_id = :kurum_id AND (veli_id IS NULL OR ogrenci_id IS NULL)
             ORDER BY id DESC
             LIMIT 500'
        );
        $stmt->execute(['kurum_id' => $kurumId]);

        $guncelle = self::db()->prepare(
            'UPDATE veli_onam_kayitlari
             SET veli_id = COALESCE(veli_id, :veli_id),
                 ogrenci_id = COALESCE(ogrenci_id, :ogrenci_id)
             WHERE id = :id AND kurum_id = :kurum_id'
        );
        foreach ($stmt->fetchAll() as $kayit) {
            $telefon = self::telefonNormalize((string) $kayit['veli_telefon']);
            if ($telefon === null) {
                continue;
            }
            $eslesme = self::eslesmeBul($kurumId, $telefon, (string) $kayit['ogrenci_ad_soyad']);
            if ($eslesme['veli_id'] < 1 && $eslesme['ogrenci_id'] < 1) {
                continue;
            }
            $guncelle->execute([
                'veli_id' => $eslesme['veli_id'] ?: null,
                'ogrenci_id' => $eslesme['ogrenci_id'] ?: null,
                'id' => (int) $kayit['id'],
                'kurum_id' => $kurumId,
            ]);
            if ($eslesme['ogrenci_id'] > 0) {
                self::onamKaydiniOgrenciyeAktar((int) $kayit['id'], $kurumId);
            }
        }
    }

    private static function onamKaydiniOgrenciyeAktar(int $onamId, int $kurumId): bool
    {
        $stmt = self::db()->prepare(
            'SELECT veli_id, ogrenci_id, veli_ad_soyad, veli_telefon, veli_eposta,
                    ogrenci_ad_soyad, ogrenci_dogum_tarihi, form_verisi_json
             FROM veli_onam_kayitlari
             WHERE id = :id AND kurum_id = :kurum_id AND ogrenci_id IS NOT NULL
             LIMIT 1'
        );
        $stmt->execute(['id' => $onamId, 'kurum_id' => $kurumId]);
        $onam = $stmt->fetch();
        if (!$onam) {
            return false;
        }

        $ekBilgiler = json_decode((string) ($onam['form_verisi_json'] ?? ''), true);
        $ekBilgiler = is_array($ekBilgiler) ? $ekBilgiler : [];
        self::ogrenciBilgileriniGuncelle($kurumId, (int) $onam['ogrenci_id'], (int) ($onam['veli_id'] ?? 0), [
            'veli_ad_soyad' => (string) $onam['veli_ad_soyad'],
            'veli_telefon' => (string) $onam['veli_telefon'],
            'veli_eposta' => (string) ($onam['veli_eposta'] ?? ''),
            'ogrenci_ad_soyad' => (string) $onam['ogrenci_ad_soyad'],
            'ogrenci_dogum_tarihi' => (string) ($onam['ogrenci_dogum_tarihi'] ?? ''),
            'ek_bilgiler' => $ekBilgiler,
        ]);
        return true;
    }

    private static function ogrenciBilgileriniGuncelle(int $kurumId, int $ogrenciId, int $veliId, array $veri): void
    {
        $ek = is_array($veri['ek_bilgiler'] ?? null) ? $veri['ek_bilgiler'] : [];
        $evetHayir = static fn(string $deger): string => $deger === 'evet' || $deger === 'var' ? 'Var' : 'Yok';
        $saglikSatirlari = [];
        if (($ek['uzman_destegi'] ?? '') !== '') {
            $saglikSatirlari[] = 'Uzman desteği: ' . $evetHayir((string) $ek['uzman_destegi'])
                . (!empty($ek['uzman_aciklama']) ? ' - ' . trim((string) $ek['uzman_aciklama']) : '');
        }
        if (($ek['tani_durumu'] ?? '') !== '') {
            $saglikSatirlari[] = 'Tanı/gelişimsel farklılık: ' . $evetHayir((string) $ek['tani_durumu'])
                . (!empty($ek['tani_aciklama']) ? ' - ' . trim((string) $ek['tani_aciklama']) : '');
        }
        if (($ek['ilac_durumu'] ?? '') !== '') {
            $saglikSatirlari[] = 'Düzenli ilaç: ' . $evetHayir((string) $ek['ilac_durumu'])
                . (!empty($ek['ilac_aciklama']) ? ' - ' . trim((string) $ek['ilac_aciklama']) : '');
        }
        if (!empty($ek['ozel_saglik_durumu'])) {
            $saglikSatirlari[] = 'Özel sağlık durumu: ' . trim((string) $ek['ozel_saglik_durumu']);
        }
        $oyunGrubuNotu = '';
        if (($ek['oyun_grubu_deneyimi'] ?? '') !== '') {
            $oyunGrubuNotu = 'Oyun grubu deneyimi: ' . $evetHayir((string) $ek['oyun_grubu_deneyimi'])
                . (!empty($ek['oyun_grubu_aciklama']) ? ' - ' . trim((string) $ek['oyun_grubu_aciklama']) : '');
        }

        $il = trim((string) ($ek['il'] ?? ''));
        $ilce = trim((string) ($ek['ilce'] ?? ''));
        $adres = trim((string) ($ek['ev_adresi'] ?? ''));
        $ogrenciStmt = self::db()->prepare(
            'UPDATE ogrenciler
             SET dogum_tarihi = COALESCE(NULLIF(:dogum_tarihi, ""), dogum_tarihi),
                 saglik_bilgisi = COALESCE(NULLIF(:saglik_bilgisi, ""), saglik_bilgisi),
                 alerji_bilgisi = COALESCE(NULLIF(:alerji_bilgisi, ""), alerji_bilgisi),
                 ozel_durum_notu = COALESCE(NULLIF(:ozel_durum_notu, ""), ozel_durum_notu),
                 il = COALESCE(NULLIF(:il, ""), il),
                 ilce = COALESCE(NULLIF(:ilce, ""), ilce),
                 adres = COALESCE(NULLIF(:adres, ""), adres),
                 adres_enlem = CASE WHEN :adres_var = 1 THEN NULL ELSE adres_enlem END,
                 adres_boylam = CASE WHEN :adres_var_2 = 1 THEN NULL ELSE adres_boylam END,
                 adres_konum_dogrulandi = CASE WHEN :adres_var_3 = 1 THEN 0 ELSE adres_konum_dogrulandi END,
                 adres_konum_guncellenme_tarihi = CASE WHEN :adres_var_4 = 1 THEN NULL ELSE adres_konum_guncellenme_tarihi END
             WHERE id = :ogrenci_id AND kurum_id = :kurum_id'
        );
        $adresVar = ($il !== '' || $ilce !== '' || $adres !== '') ? 1 : 0;
        $ogrenciStmt->execute([
            'dogum_tarihi' => trim((string) ($veri['ogrenci_dogum_tarihi'] ?? '')),
            'saglik_bilgisi' => implode("\n", $saglikSatirlari),
            'alerji_bilgisi' => trim((string) ($ek['alerji_durumu'] ?? '')),
            'ozel_durum_notu' => $oyunGrubuNotu,
            'il' => $il,
            'ilce' => $ilce,
            'adres' => $adres,
            'adres_var' => $adresVar,
            'adres_var_2' => $adresVar,
            'adres_var_3' => $adresVar,
            'adres_var_4' => $adresVar,
            'ogrenci_id' => $ogrenciId,
            'kurum_id' => $kurumId,
        ]);

        if ($veliId < 1) {
            return;
        }
        $veliStmt = self::db()->prepare(
            'UPDATE veliler
             SET eposta = COALESCE(NULLIF(:eposta, ""), eposta),
                 il = COALESCE(NULLIF(:il, ""), il),
                 ilce = COALESCE(NULLIF(:ilce, ""), ilce),
                 adres = COALESCE(NULLIF(:adres, ""), adres)
             WHERE id = :veli_id AND kurum_id = :kurum_id'
        );
        $veliStmt->execute([
            'eposta' => trim((string) ($veri['veli_eposta'] ?? '')),
            'il' => $il,
            'ilce' => $ilce,
            'adres' => $adres,
            'veli_id' => $veliId,
            'kurum_id' => $kurumId,
        ]);
    }

    private static function eslesmeBul(int $kurumId, string $telefon, string $ogrenciAdSoyad): array
    {
        $veliId = self::veliIdBul($kurumId, $telefon);
        $ogrenciId = $veliId > 0 ? self::ogrenciIdBul($kurumId, $veliId, $ogrenciAdSoyad) : 0;
        if ($ogrenciId > 0) {
            return ['veli_id' => $veliId, 'ogrenci_id' => $ogrenciId];
        }

        $stmt = self::db()->prepare(
            'SELECT o.id, CONCAT(o.ad, " ", o.soyad) AS ad_soyad
             FROM ogrenciler o
             WHERE o.kurum_id = :kurum_id
               AND (RIGHT(REGEXP_REPLACE(COALESCE(o.acil_durum_telefon, ""), "[^0-9]", ""), 10) = :telefon_acil
                OR RIGHT(REGEXP_REPLACE(COALESCE(o.vasi_telefon, ""), "[^0-9]", ""), 10) = :telefon_vasi)'
        );
        $stmt->execute([
            'kurum_id' => $kurumId,
            'telefon_acil' => $telefon,
            'telefon_vasi' => $telefon,
        ]);
        $aranan = self::adNormalize($ogrenciAdSoyad);
        foreach ($stmt->fetchAll() as $ogrenci) {
            if (self::adNormalize((string) $ogrenci['ad_soyad']) === $aranan) {
                return ['veli_id' => $veliId, 'ogrenci_id' => (int) $ogrenci['id']];
            }
        }

        return ['veli_id' => $veliId, 'ogrenci_id' => 0];
    }

    private static function veliIdBul(int $kurumId, string $telefon): int
    {
        $stmt = self::db()->prepare(
            'SELECT id FROM veliler
             WHERE kurum_id = :kurum_id
               AND (RIGHT(REGEXP_REPLACE(telefon, "[^0-9]", ""), 10) = :telefon
                OR RIGHT(REGEXP_REPLACE(COALESCE(yedek_telefon, ""), "[^0-9]", ""), 10) = :telefon_yedek)
             LIMIT 1'
        );
        $stmt->execute(['kurum_id' => $kurumId, 'telefon' => $telefon, 'telefon_yedek' => $telefon]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private static function ogrenciIdBul(int $kurumId, int $veliId, string $adSoyad): int
    {
        $stmt = self::db()->prepare(
            'SELECT o.id, CONCAT(o.ad, " ", o.soyad) AS ad_soyad
             FROM ogrenci_velileri ov
             INNER JOIN ogrenciler o ON o.id = ov.ogrenci_id AND o.kurum_id = ov.kurum_id
             WHERE ov.veli_id = :veli_id AND ov.kurum_id = :kurum_id'
        );
        $stmt->execute(['veli_id' => $veliId, 'kurum_id' => $kurumId]);
        $aranan = self::adNormalize($adSoyad);
        foreach ($stmt->fetchAll() as $ogrenci) {
            if (self::adNormalize((string) $ogrenci['ad_soyad']) === $aranan) {
                return (int) $ogrenci['id'];
            }
        }
        return 0;
    }

    private static function adNormalize(string $ad): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($ad)) ?? '', 'UTF-8');
    }

    private static function telefonGoster(string $telefon): string
    {
        return '0(' . substr($telefon, 0, 3) . ') ' . substr($telefon, 3, 3) . ' ' . substr($telefon, 6, 2) . ' ' . substr($telefon, 8, 2);
    }
}
