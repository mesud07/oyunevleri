CREATE TABLE IF NOT EXISTS kasalar (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ad VARCHAR(150) NOT NULL,
  tur ENUM('nakit','banka','altin','diger') NOT NULL DEFAULT 'nakit',
  para_birimi VARCHAR(10) NOT NULL DEFAULT 'TRY',
  acilis_bakiyesi DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  aciklama VARCHAR(255) NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  olusturan_kullanici_id BIGINT UNSIGNED NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_kasalar_aktif (aktif),
  INDEX idx_kasalar_tur (tur),
  CONSTRAINT fk_kasalar_kullanici FOREIGN KEY (olusturan_kullanici_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE odemeler
  ADD COLUMN kasa_id BIGINT UNSIGNED NULL AFTER yontem,
  ADD INDEX idx_odemeler_kasa (kasa_id),
  ADD CONSTRAINT fk_odemeler_kasa FOREIGN KEY (kasa_id) REFERENCES kasalar(id) ON DELETE SET NULL;

ALTER TABLE giderler
  ADD COLUMN kasa_id BIGINT UNSIGNED NULL AFTER odeme_turu,
  ADD INDEX idx_giderler_kasa (kasa_id),
  ADD CONSTRAINT fk_giderler_kasa FOREIGN KEY (kasa_id) REFERENCES kasalar(id) ON DELETE SET NULL;

INSERT INTO kasalar (ad, tur, para_birimi, acilis_bakiyesi, aciklama, aktif)
SELECT 'Nakit Kasa', 'nakit', 'TRY', 0.00, 'Varsayilan nakit kasa', 1
WHERE NOT EXISTS (SELECT 1 FROM kasalar WHERE ad = 'Nakit Kasa' LIMIT 1);
