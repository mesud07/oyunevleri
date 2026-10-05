SET @beklenen_odeme_tarihi_kolonu := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'paketler'
    AND COLUMN_NAME = 'beklenen_odeme_tarihi'
);

SET @sql := IF(
  @beklenen_odeme_tarihi_kolonu = 0,
  'ALTER TABLE paketler ADD COLUMN beklenen_odeme_tarihi DATE NULL AFTER tahsilat_notu',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

