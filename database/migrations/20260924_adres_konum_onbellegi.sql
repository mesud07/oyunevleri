CREATE TABLE IF NOT EXISTS adres_konum_onbellegi (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kurum_id BIGINT UNSIGNED NOT NULL,
  adres_anahtari CHAR(64) NOT NULL,
  il VARCHAR(100) NULL,
  ilce VARCHAR(100) NULL,
  adres TEXT NULL,
  enlem DECIMAL(10,7) NOT NULL,
  boylam DECIMAL(10,7) NOT NULL,
  saglayici VARCHAR(30) NOT NULL DEFAULT 'google',
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  son_kullanilma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_adres_konum_onbellegi (kurum_id, adres_anahtari),
  KEY idx_adres_konum_onbellegi_kurum (kurum_id),
  CONSTRAINT fk_adres_konum_onbellegi_kurum
    FOREIGN KEY (kurum_id) REFERENCES kurumlar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
