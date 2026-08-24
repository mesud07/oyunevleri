CREATE TABLE IF NOT EXISTS theme_activity_groups (
    activity_id BIGINT UNSIGNED NOT NULL,
    group_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (activity_id, group_id),
    CONSTRAINT fk_theme_activity_groups_activity
        FOREIGN KEY (activity_id) REFERENCES theme_activities (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_theme_activity_groups_group
        FOREIGN KEY (group_id) REFERENCES gruplar (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @sar_source_type_col := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'student_activity_records'
    AND COLUMN_NAME = 'source_type'
);
SET @sql := IF(
  @sar_source_type_col = 0,
  'ALTER TABLE student_activity_records ADD COLUMN source_type VARCHAR(30) NOT NULL DEFAULT ''manual'' AFTER completed_at',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sar_randevu_col := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'student_activity_records'
    AND COLUMN_NAME = 'randevu_id'
);
SET @sql := IF(
  @sar_randevu_col = 0,
  'ALTER TABLE student_activity_records ADD COLUMN randevu_id BIGINT UNSIGNED NULL AFTER source_type',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sar_randevu_idx := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'student_activity_records'
    AND INDEX_NAME = 'idx_student_activity_records_randevu'
);
SET @sql := IF(
  @sar_randevu_idx = 0,
  'CREATE INDEX idx_student_activity_records_randevu ON student_activity_records (randevu_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sar_source_idx := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'student_activity_records'
    AND INDEX_NAME = 'idx_student_activity_records_source'
);
SET @sql := IF(
  @sar_source_idx = 0,
  'CREATE INDEX idx_student_activity_records_source ON student_activity_records (source_type)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sar_randevu_fk := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'student_activity_records'
    AND CONSTRAINT_NAME = 'fk_student_activity_records_randevu'
);
SET @sql := IF(
  @sar_randevu_fk = 0,
  'ALTER TABLE student_activity_records ADD CONSTRAINT fk_student_activity_records_randevu FOREIGN KEY (randevu_id) REFERENCES randevular (id) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
