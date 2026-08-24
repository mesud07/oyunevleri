<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Model;
final class TelafiHakki extends Model
{
    public static function bekleyenler(int $ogrenciId = 0): array
    {
        $sql = 'SELECT th.id, th.ogrenci_id, th.paket_id, th.kaynak_randevu_id, th.durum,
                       th.son_kullanim_tarihi, th.aciklama, th.olusturulma_tarihi,
                       CONCAT(o.ad, " ", o.soyad) AS ogrenci,
                       COALESCE(p.paket_adi, "Paket") AS paket_adi,
                       kr.tarih AS kaynak_tarih,
                       kr.baslangic_saati AS kaynak_saat
                FROM telafi_haklari th
                INNER JOIN ogrenciler o ON o.id = th.ogrenci_id AND o.kurum_id = th.kurum_id
                LEFT JOIN paketler p ON p.id = th.paket_id AND p.kurum_id = th.kurum_id
                LEFT JOIN randevular kr ON kr.id = th.kaynak_randevu_id AND kr.kurum_id = th.kurum_id
                WHERE th.kurum_id = :kurum_id
                  AND th.durum = "planlanmayi_bekliyor"';
        $params = ['kurum_id' => self::kurumId()];
        if ($ogrenciId > 0) {
            $sql .= ' AND th.ogrenci_id = :ogrenci_id';
            $params['ogrenci_id'] = $ogrenciId;
        }
        $sql .= ' ORDER BY th.olusturulma_tarihi DESC, th.id DESC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function planla(int $id, array $veri): int
    {
        $db = self::db();
        try {
            $db->beginTransaction();

            $stmt = $db->prepare('SELECT * FROM telafi_haklari WHERE id = :id AND kurum_id = :kurum_id AND durum = "planlanmayi_bekliyor" FOR UPDATE');
            $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
            $telafi = $stmt->fetch();
            if (!$telafi) {
                $db->rollBack();
                return 0;
            }

            $baslangic = self::saatDegeri((string) $veri['baslangic_saati']);
            $sure = max(15, (int) ($veri['sure_dakika'] ?? 45));
            $bitis = date('H:i:s', strtotime($baslangic . ' +' . $sure . ' minutes'));

            $ekle = $db->prepare(
                'INSERT INTO randevular
                 (kurum_id, ogrenci_id, grup_id, paket_id, telafi_hakki_id, ogretmen_id, tarih, baslangic_saati, bitis_saati, tur, hak_kaynagi, durum, aciklama, olusturan_kullanici_id, olusturulma_tarihi)
                 VALUES
                 (:kurum_id, :ogrenci_id, :grup_id, :paket_id, :telafi_hakki_id, :ogretmen_id, :tarih, :baslangic_saati, :bitis_saati, "Telafi dersi", "Paket telafi hakki", "planlandi", :aciklama, :olusturan_kullanici_id, NOW())'
            );
            $ekle->execute([
                'kurum_id' => self::kurumId(),
                'ogrenci_id' => (int) $telafi['ogrenci_id'],
                'grup_id' => !empty($veri['grup_id']) ? (int) $veri['grup_id'] : null,
                'paket_id' => !empty($telafi['paket_id']) ? (int) $telafi['paket_id'] : null,
                'telafi_hakki_id' => $id,
                'ogretmen_id' => !empty($veri['ogretmen_id']) ? (int) $veri['ogretmen_id'] : null,
                'tarih' => $veri['tarih'],
                'baslangic_saati' => $baslangic,
                'bitis_saati' => $bitis,
                'aciklama' => trim((string) ($veri['aciklama'] ?? 'Telafi hakki ile olusturuldu.')) ?: null,
                'olusturan_kullanici_id' => (int) ($veri['olusturan_kullanici_id'] ?? 0),
            ]);

            $randevuId = (int) $db->lastInsertId();
            $guncelle = $db->prepare('UPDATE telafi_haklari SET durum = "planlandi" WHERE id = :id AND kurum_id = :kurum_id');
            $guncelle->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
            Paket::sonDersTarihiGuncelle((int) ($telafi['paket_id'] ?? 0));

            $db->commit();
            return $randevuId;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function tamamla(int $id): void
    {
        if ($id < 1) {
            return;
        }
        $stmt = self::db()->prepare('UPDATE telafi_haklari SET durum = "kullanildi" WHERE id = :id AND kurum_id = :kurum_id AND durum IN ("planlandi","planlanmayi_bekliyor")');
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
    }

    private static function saatDegeri(string $saat): string
    {
        $saat = substr($saat, 0, 5);
        if (!preg_match('/^\d{2}:\d{2}$/', $saat)) {
            return '15:00:00';
        }
        return $saat . ':00';
    }
}
