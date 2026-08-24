SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS kurumlar (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ad VARCHAR(190) NOT NULL,
  kod VARCHAR(80) NOT NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_kurumlar_kod (kod)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO kurumlar (ad, kod, aktif, olusturulma_tarihi)
VALUES ('Oyun Evleri Yönetim Sistemi', 'TALYA', 1, NOW())
ON DUPLICATE KEY UPDATE ad = VALUES(ad), aktif = VALUES(aktif);

SET @varsayilan_kurum_id := (SELECT id FROM kurumlar WHERE kod = 'TALYA' LIMIT 1);

DELIMITER //

DROP PROCEDURE IF EXISTS talya_kurum_ekle//
CREATE PROCEDURE talya_kurum_ekle(IN tablo_adi VARCHAR(64), IN indeks_adi VARCHAR(64))
BEGIN
  IF EXISTS (
    SELECT 1 FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tablo_adi
  ) THEN
    IF NOT EXISTS (
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tablo_adi AND COLUMN_NAME = 'kurum_id'
    ) THEN
      SET @sql := CONCAT('ALTER TABLE `', tablo_adi, '` ADD COLUMN `kurum_id` BIGINT UNSIGNED NULL');
      PREPARE stmt FROM @sql;
      EXECUTE stmt;
      DEALLOCATE PREPARE stmt;
    END IF;

    SET @sql := CONCAT('UPDATE `', tablo_adi, '` SET `kurum_id` = ', @varsayilan_kurum_id, ' WHERE `kurum_id` IS NULL');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @sql := CONCAT('ALTER TABLE `', tablo_adi, '` MODIFY `kurum_id` BIGINT UNSIGNED NOT NULL DEFAULT ', @varsayilan_kurum_id);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    IF NOT EXISTS (
      SELECT 1 FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tablo_adi AND INDEX_NAME = indeks_adi
    ) THEN
      SET @sql := CONCAT('ALTER TABLE `', tablo_adi, '` ADD INDEX `', indeks_adi, '` (`kurum_id`)');
      PREPARE stmt FROM @sql;
      EXECUTE stmt;
      DEALLOCATE PREPARE stmt;
    END IF;
  END IF;
END//

DROP PROCEDURE IF EXISTS talya_kurum_unique//
CREATE PROCEDURE talya_kurum_unique(
  IN tablo_adi VARCHAR(64),
  IN eski_indeks VARCHAR(64),
  IN yeni_indeks VARCHAR(64),
  IN kolonlar VARCHAR(255)
)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tablo_adi AND INDEX_NAME = yeni_indeks
  ) THEN
    IF EXISTS (
      SELECT 1 FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tablo_adi AND INDEX_NAME = eski_indeks
    ) THEN
      SET @sql := CONCAT('ALTER TABLE `', tablo_adi, '` DROP INDEX `', eski_indeks, '`');
      PREPARE stmt FROM @sql;
      EXECUTE stmt;
      DEALLOCATE PREPARE stmt;
    END IF;

    SET @sql := CONCAT(
      'ALTER TABLE `', tablo_adi, '` ADD UNIQUE KEY `', yeni_indeks, '` (`kurum_id`, ', kolonlar, ')'
    );
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END//

DELIMITER ;

CALL talya_kurum_ekle('kullanicilar', 'idx_kullanicilar_kurum');
CALL talya_kurum_ekle('veliler', 'idx_veliler_kurum');
CALL talya_kurum_ekle('bekleyen_veliler', 'idx_bekleyen_veliler_kurum');
CALL talya_kurum_ekle('ogrenciler', 'idx_ogrenciler_kurum');
CALL talya_kurum_ekle('ogrenci_velileri', 'idx_ogrenci_velileri_kurum');
CALL talya_kurum_ekle('ogrenci_kara_liste', 'idx_ogrenci_kara_liste_kurum');
CALL talya_kurum_ekle('gruplar', 'idx_gruplar_kurum');
CALL talya_kurum_ekle('grup_ogrencileri', 'idx_grup_ogrencileri_kurum');
CALL talya_kurum_ekle('hizmetler', 'idx_hizmetler_kurum');
CALL talya_kurum_ekle('paketler', 'idx_paketler_kurum');
CALL talya_kurum_ekle('paket_disi_haklar', 'idx_paket_disi_haklar_kurum');
CALL talya_kurum_ekle('randevular', 'idx_randevular_kurum');
CALL talya_kurum_ekle('yoklamalar', 'idx_yoklamalar_kurum');
CALL talya_kurum_ekle('telafi_haklari', 'idx_telafi_haklari_kurum');
CALL talya_kurum_ekle('telafi_onerileri', 'idx_telafi_onerileri_kurum');
CALL talya_kurum_ekle('hak_hareketleri', 'idx_hak_hareketleri_kurum');
CALL talya_kurum_ekle('odemeler', 'idx_odemeler_kurum');
CALL talya_kurum_ekle('odeme_sozleri', 'idx_odeme_sozleri_kurum');
CALL talya_kurum_ekle('kasalar', 'idx_kasalar_kurum');
CALL talya_kurum_ekle('kasa_hareketleri', 'idx_kasa_hareketleri_kurum');
CALL talya_kurum_ekle('giderler', 'idx_giderler_kurum');
CALL talya_kurum_ekle('ders_programlari', 'idx_ders_programlari_kurum');
CALL talya_kurum_ekle('gunluk_notlar', 'idx_gunluk_notlar_kurum');
CALL talya_kurum_ekle('sms_sablonlari', 'idx_sms_sablonlari_kurum');
CALL talya_kurum_ekle('sms_kayitlari', 'idx_sms_kayitlari_kurum');
CALL talya_kurum_ekle('sms_olay_kayitlari', 'idx_sms_olay_kayitlari_kurum');
CALL talya_kurum_ekle('ayarlar', 'idx_ayarlar_kurum');
CALL talya_kurum_ekle('bildirimler', 'idx_bildirimler_kurum');
CALL talya_kurum_ekle('islem_kayitlari', 'idx_islem_kayitlari_kurum');
CALL talya_kurum_ekle('weekly_themes', 'idx_weekly_themes_kurum');
CALL talya_kurum_ekle('weekly_theme_age_groups', 'idx_weekly_theme_age_groups_kurum');
CALL talya_kurum_ekle('activity_templates', 'idx_activity_templates_kurum');
CALL talya_kurum_ekle('theme_activities', 'idx_theme_activities_kurum');
CALL talya_kurum_ekle('theme_activity_groups', 'idx_theme_activity_groups_kurum');
CALL talya_kurum_ekle('theme_group_presets', 'idx_theme_group_presets_kurum');
CALL talya_kurum_ekle('theme_group_preset_groups', 'idx_theme_group_preset_groups_kurum');
CALL talya_kurum_ekle('student_activity_records', 'idx_student_activity_records_kurum');

CALL talya_kurum_unique('kullanicilar', 'eposta', 'uq_kullanicilar_kurum_eposta', '`eposta`');
CALL talya_kurum_unique('sms_sablonlari', 'anahtar', 'uq_sms_sablonlari_kurum_anahtar', '`anahtar`');
CALL talya_kurum_unique('ayarlar', 'anahtar', 'uq_ayarlar_kurum_anahtar', '`anahtar`');
CALL talya_kurum_unique('activity_templates', 'uq_activity_templates_title', 'uq_activity_templates_kurum_title', '`title`');
CALL talya_kurum_unique('theme_group_presets', 'uq_theme_group_presets_title', 'uq_theme_group_presets_kurum_title', '`title`');

DROP PROCEDURE IF EXISTS talya_kurum_unique;
DROP PROCEDURE IF EXISTS talya_kurum_ekle;
