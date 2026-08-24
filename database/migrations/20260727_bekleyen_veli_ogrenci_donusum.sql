SET NAMES utf8mb4;

SET @kolon_var := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'bekleyen_veliler'
    AND COLUMN_NAME = 'ogrenci_id'
);

SET @sql := IF(
  @kolon_var = 0,
  'ALTER TABLE bekleyen_veliler ADD COLUMN ogrenci_id BIGINT UNSIGNED NULL AFTER id',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_var := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'bekleyen_veliler'
    AND INDEX_NAME = 'idx_bekleyen_veliler_ogrenci'
);

SET @sql := IF(
  @index_var = 0,
  'CREATE INDEX idx_bekleyen_veliler_ogrenci ON bekleyen_veliler (ogrenci_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
