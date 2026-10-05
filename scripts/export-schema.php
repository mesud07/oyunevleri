<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Veritabani;
$kok = dirname(__DIR__);
require $kok . '/app/Core/Config.php';
require $kok . '/app/Core/Veritabani.php';

Config::load($kok . '/.env');
$db = Veritabani::baglan();
$yalnizYapi = in_array('--structure-only', $argv, true);
$hedef = $kok . '/database/schema.sql';
foreach ($argv as $arguman) {
    if (str_starts_with($arguman, '--output=')) {
        $aday = substr($arguman, strlen('--output='));
        $hedef = str_starts_with($aday, '/') ? $aday : $kok . '/' . $aday;
    }
}

$tablolar = $db->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(\PDO::FETCH_COLUMN);
sort($tablolar, SORT_STRING);

// Kurum, rol ve yaş grubu gibi temel tabloları önce yazmak okunabilirliği artırır.
$oncelik = ['kurumlar', 'roller', 'rol_yetkileri', 'age_groups'];
usort($tablolar, static function (string $a, string $b) use ($oncelik): int {
    $aSira = array_search($a, $oncelik, true);
    $bSira = array_search($b, $oncelik, true);
    $aSira = $aSira === false ? PHP_INT_MAX : $aSira;
    $bSira = $bSira === false ? PHP_INT_MAX : $bSira;
    return $aSira === $bSira ? strcmp($a, $b) : $aSira <=> $bSira;
});

$sql = "-- Canlı şemadan üretilen temiz kurulum dosyası.\n";
$sql .= "-- Müşteri, kullanıcı, ödeme ve entegrasyon sırrı içermez.\n\n";
$sql .= "SET NAMES utf8mb4;\nSET time_zone = '+03:00';\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($tablolar as $tablo) {
    $stmt = $db->query('SHOW CREATE TABLE `' . str_replace('`', '``', $tablo) . '`');
    $create = (string) ($stmt->fetch(\PDO::FETCH_NUM)[1] ?? '');
    $create = preg_replace('/\sAUTO_INCREMENT=\d+\b/', '', $create) ?? $create;
    $create = preg_replace('/^CREATE TABLE /', 'CREATE TABLE IF NOT EXISTS ', $create) ?? $create;
    $sql .= $create . ";\n\n";
}

$sql .= "SET FOREIGN_KEY_CHECKS = 1;\n\n";

if ($yalnizYapi) {
    $sql .= "-- Toplam " . count($tablolar) . " tablo; yalnız yapı dışa aktarımı.\n";
} else {
$sql .= <<<'SQL'
-- Güvenli başlangıç verileri: yeni kurulumda ilk kurum ve ortak tanımlar.
INSERT INTO kurumlar (ad, kod, aktif, olusturulma_tarihi)
VALUES ('Talya Kids', 'TALYA', 1, NOW())
ON DUPLICATE KEY UPDATE ad = VALUES(ad), aktif = VALUES(aktif);

SET @varsayilan_kurum_id := (SELECT id FROM kurumlar WHERE kod = 'TALYA' LIMIT 1);

INSERT INTO roller (kod, ad) VALUES
('kurucu', 'Kurucu'),
('yonetici', 'Yonetici'),
('ogretmen', 'Ogretmen'),
('muhasebe', 'Muhasebe'),
('resepsiyon', 'Resepsiyon')
ON DUPLICATE KEY UPDATE ad = VALUES(ad);

INSERT IGNORE INTO rol_yetkileri (rol_id, yetki)
SELECT r.id, y.yetki FROM roller r JOIN (
  SELECT 'ogrenci_listele' yetki UNION ALL SELECT 'ogrenci_ekle' UNION ALL SELECT 'veli_listele' UNION ALL SELECT 'veli_ekle'
  UNION ALL SELECT 'grup_listele' UNION ALL SELECT 'grup_ekle' UNION ALL SELECT 'paket_listele' UNION ALL SELECT 'paket_ekle'
  UNION ALL SELECT 'odeme_listele' UNION ALL SELECT 'odeme_ekle' UNION ALL SELECT 'randevu_listele' UNION ALL SELECT 'randevu_ekle'
  UNION ALL SELECT 'randevu_durum_degistir' UNION ALL SELECT 'yoklama_listele' UNION ALL SELECT 'rapor_ozet'
  UNION ALL SELECT 'sms_goruntule' UNION ALL SELECT 'sms_gonder' UNION ALL SELECT 'sms_toplu_gonder'
  UNION ALL SELECT 'sms_sablon_yonet' UNION ALL SELECT 'sms_tekrar_gonder' UNION ALL SELECT 'sms_ayar_yonet'
  UNION ALL SELECT 'sms_rapor_goruntule' UNION ALL SELECT 'kullanici_yonet' UNION ALL SELECT 'tema_yonet'
  UNION ALL SELECT 'personel_listele' UNION ALL SELECT 'personel_yonet' UNION ALL SELECT 'fatura_entegrasyon_yonet'
) y WHERE r.kod = 'kurucu';

INSERT IGNORE INTO rol_yetkileri (rol_id, yetki)
SELECT r.id, y.yetki FROM roller r JOIN (
  SELECT 'ogrenci_listele' yetki UNION ALL SELECT 'ogrenci_ekle' UNION ALL SELECT 'veli_listele' UNION ALL SELECT 'veli_ekle'
  UNION ALL SELECT 'grup_listele' UNION ALL SELECT 'grup_ekle' UNION ALL SELECT 'paket_listele' UNION ALL SELECT 'paket_ekle'
  UNION ALL SELECT 'odeme_listele' UNION ALL SELECT 'odeme_ekle' UNION ALL SELECT 'randevu_listele' UNION ALL SELECT 'randevu_ekle'
  UNION ALL SELECT 'randevu_durum_degistir' UNION ALL SELECT 'yoklama_listele' UNION ALL SELECT 'rapor_ozet'
  UNION ALL SELECT 'sms_goruntule' UNION ALL SELECT 'sms_gonder' UNION ALL SELECT 'sms_toplu_gonder'
  UNION ALL SELECT 'tema_yonet' UNION ALL SELECT 'personel_listele' UNION ALL SELECT 'personel_yonet'
) y WHERE r.kod = 'yonetici';

INSERT IGNORE INTO rol_yetkileri (rol_id, yetki)
SELECT r.id, y.yetki FROM roller r JOIN (
  SELECT 'ogrenci_listele' yetki UNION ALL SELECT 'ogrenci_ekle' UNION ALL SELECT 'veli_listele'
  UNION ALL SELECT 'grup_listele' UNION ALL SELECT 'randevu_listele' UNION ALL SELECT 'randevu_ekle'
  UNION ALL SELECT 'randevu_durum_degistir' UNION ALL SELECT 'yoklama_listele' UNION ALL SELECT 'tema_yonet'
) y WHERE r.kod = 'ogretmen';

INSERT IGNORE INTO rol_yetkileri (rol_id, yetki)
SELECT r.id, y.yetki FROM roller r JOIN (
  SELECT 'paket_listele' yetki UNION ALL SELECT 'odeme_listele' UNION ALL SELECT 'odeme_ekle'
  UNION ALL SELECT 'rapor_ozet' UNION ALL SELECT 'personel_listele'
) y WHERE r.kod = 'muhasebe';

INSERT IGNORE INTO rol_yetkileri (rol_id, yetki)
SELECT r.id, y.yetki FROM roller r JOIN (
  SELECT 'ogrenci_listele' yetki UNION ALL SELECT 'ogrenci_ekle' UNION ALL SELECT 'veli_listele' UNION ALL SELECT 'veli_ekle'
  UNION ALL SELECT 'grup_listele' UNION ALL SELECT 'randevu_listele' UNION ALL SELECT 'randevu_ekle'
  UNION ALL SELECT 'randevu_durum_degistir' UNION ALL SELECT 'sms_goruntule' UNION ALL SELECT 'sms_gonder'
) y WHERE r.kod = 'resepsiyon';

INSERT INTO age_groups (name, sort_order, created_at, updated_at) VALUES
('18-24 Ay', 10, NOW(), NOW()),
('25-36 Ay', 20, NOW(), NOW()),
('37-48 Ay', 30, NOW(), NOW())
ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order), updated_at = NOW();

INSERT INTO ayarlar (kurum_id, anahtar, deger, aciklama) VALUES
(@varsayilan_kurum_id, 'otomatik_gelmedi_bekleme_dakika', '60', 'Planli randevular icin otomatik gelmedi bekleme suresi.'),
(@varsayilan_kurum_id, 'kurum_adi', 'Talya Kids', 'Panelde kullanilan kurum adi.'),
(@varsayilan_kurum_id, 'sms_appointment_reminder_enabled', '1', 'Randevu hatirlatma SMS otomasyonu aktiflik bilgisi.'),
(@varsayilan_kurum_id, 'sms_appointment_reminder_days_before', '1', 'Randevudan kac gun once hatirlatma SMS kuyruga alinacak.'),
(@varsayilan_kurum_id, 'sms_appointment_reminder_time', '14:00', 'Hatirlatma SMS kuyruga alma saati.'),
(@varsayilan_kurum_id, 'sms_birthday_message_enabled', '1', 'Dogum gunu SMS otomasyonu aktiflik bilgisi.'),
(@varsayilan_kurum_id, 'sms_birthday_message_time', '09:00', 'Dogum gunu SMS kuyruga alma saati.')
ON DUPLICATE KEY UPDATE deger = VALUES(deger), aciklama = VALUES(aciklama);

INSERT INTO activity_templates (kurum_id, title, description, is_active, created_at, updated_at) VALUES
(@varsayilan_kurum_id, 'Parmak Boyasi', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Kesme ve Yapistirma', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Duyusal Oyun', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Renk Eslestirme', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Nesne Eslestirme', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Puzzle Calismasi', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Muzik ve Ritim', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Hikaye Zamani', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Blok Calismasi', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Serbest Boyama', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Renk Avi', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Doku Kesfi', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Buyuk-Kucuk Eslestirme', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Dolu-Bos Kavrami', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Ince Motor Calismasi', NULL, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE is_active = VALUES(is_active), updated_at = NOW();
SQL;
}

if (file_put_contents($hedef, $sql . "\n") === false) {
    throw new RuntimeException('Şema dosyası yazılamadı.');
}

fwrite(STDOUT, count($tablolar) . ' tablo ile ' . $hedef . " güncellendi.\n");
