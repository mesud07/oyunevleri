SET @tahsilat_notu_kolonu := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'paketler'
    AND COLUMN_NAME = 'tahsilat_notu'
);

SET @sql := IF(
  @tahsilat_notu_kolonu = 0,
  'ALTER TABLE paketler ADD COLUMN tahsilat_notu TEXT NULL AFTER net_paket_tutari',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
