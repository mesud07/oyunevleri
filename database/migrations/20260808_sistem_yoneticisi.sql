SET NAMES utf8mb4;

DELIMITER //

DROP PROCEDURE IF EXISTS talya_sistem_yoneticisi_semasi//
CREATE PROCEDURE talya_sistem_yoneticisi_semasi()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'kullanicilar'
      AND COLUMN_NAME = 'sistem_yoneticisi'
  ) THEN
    ALTER TABLE kullanicilar
      ADD COLUMN sistem_yoneticisi TINYINT(1) NOT NULL DEFAULT 0 AFTER aktif;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'kullanicilar'
      AND INDEX_NAME = 'idx_kullanicilar_sistem_yoneticisi'
  ) THEN
    ALTER TABLE kullanicilar
      ADD INDEX idx_kullanicilar_sistem_yoneticisi (sistem_yoneticisi, aktif);
  END IF;
END//

DELIMITER ;

CALL talya_sistem_yoneticisi_semasi();
DROP PROCEDURE IF EXISTS talya_sistem_yoneticisi_semasi;

SET @talya_kurum_id := (SELECT id FROM kurumlar WHERE kod = 'TALYA' LIMIT 1);
SET @kurucu_rol_id := (SELECT id FROM roller WHERE kod = 'kurucu' LIMIT 1);

UPDATE kullanicilar
SET sistem_yoneticisi = 1
WHERE kurum_id = @talya_kurum_id
  AND rol_id = @kurucu_rol_id
  AND aktif = 1
ORDER BY id ASC
LIMIT 1;
