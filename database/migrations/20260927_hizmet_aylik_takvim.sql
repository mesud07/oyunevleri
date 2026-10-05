SET @hak_hesaplama_turu_kolonu := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'hizmetler'
    AND COLUMN_NAME = 'hak_hesaplama_turu'
);

SET @sql := IF(
  @hak_hesaplama_turu_kolonu = 0,
  'ALTER TABLE hizmetler ADD COLUMN hak_hesaplama_turu ENUM(''sabit'',''aylik_takvim'') NOT NULL DEFAULT ''sabit'' AFTER toplam_telafi_hak',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
