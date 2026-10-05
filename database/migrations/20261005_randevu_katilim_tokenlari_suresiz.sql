CREATE TABLE IF NOT EXISTS randevu_katilim_tokenlari (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kurum_id BIGINT UNSIGNED NOT NULL,
  randevu_id BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_randevu_katilim_tokenlari_hash (token_hash),
  UNIQUE KEY uq_randevu_katilim_tokenlari_kurum_id (kurum_id, id),
  KEY idx_randevu_katilim_tokenlari_randevu (kurum_id, randevu_id),
  CONSTRAINT fk_randevu_katilim_tokenlari_kurum
    FOREIGN KEY (kurum_id) REFERENCES kurumlar (id) ON DELETE CASCADE,
  CONSTRAINT fk_randevu_katilim_tokenlari_randevu
    FOREIGN KEY (kurum_id, randevu_id) REFERENCES randevular (kurum_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO randevu_katilim_tokenlari
  (kurum_id, randevu_id, token_hash, olusturulma_tarihi)
SELECT kurum_id, id, katilim_token_hash, COALESCE(olusturulma_tarihi, NOW())
FROM randevular
WHERE katilim_token_hash IS NOT NULL AND katilim_token_hash <> '';
