DELIMITER //

DROP PROCEDURE IF EXISTS talya_ogrenci_adres_kolonu_ekle//
CREATE PROCEDURE talya_ogrenci_adres_kolonu_ekle(IN kolon_adi VARCHAR(64), IN kolon_tanimi TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ogrenciler' AND COLUMN_NAME = kolon_adi
    ) THEN
        SET @sql := CONCAT('ALTER TABLE ogrenciler ADD COLUMN `', kolon_adi, '` ', kolon_tanimi);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//

DELIMITER ;

CALL talya_ogrenci_adres_kolonu_ekle('il', 'VARCHAR(100) NULL');
CALL talya_ogrenci_adres_kolonu_ekle('ilce', 'VARCHAR(100) NULL');
CALL talya_ogrenci_adres_kolonu_ekle('adres', 'TEXT NULL');
CALL talya_ogrenci_adres_kolonu_ekle('adres_enlem', 'DECIMAL(10,7) NULL');
CALL talya_ogrenci_adres_kolonu_ekle('adres_boylam', 'DECIMAL(10,7) NULL');
CALL talya_ogrenci_adres_kolonu_ekle('adres_konum_dogrulandi', 'TINYINT(1) NOT NULL DEFAULT 0');
CALL talya_ogrenci_adres_kolonu_ekle('adres_konum_guncellenme_tarihi', 'DATETIME NULL');

DROP PROCEDURE IF EXISTS talya_ogrenci_adres_kolonu_ekle;

SET @indeks_var := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ogrenciler' AND INDEX_NAME = 'idx_ogrenciler_adres_konum'
);
SET @sql := IF(@indeks_var = 0,
    'ALTER TABLE ogrenciler ADD INDEX idx_ogrenciler_adres_konum (kurum_id, adres_konum_dogrulandi)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
