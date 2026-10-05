SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS fatura_arsiv_dosyalari (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kurum_id BIGINT UNSIGNED NOT NULL,
  fatura_id BIGINT UNSIGNED NOT NULL,
  format ENUM('pdf','xml') NOT NULL,
  goreli_yol VARCHAR(500) NOT NULL,
  sha256 CHAR(64) NOT NULL,
  dosya_boyutu BIGINT UNSIGNED NOT NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_fatura_arsiv_format (kurum_id, fatura_id, format),
  KEY idx_fatura_arsiv_fatura (fatura_id),
  CONSTRAINT fk_fatura_arsiv_kurum FOREIGN KEY (kurum_id) REFERENCES kurumlar(id) ON DELETE CASCADE,
  CONSTRAINT fk_fatura_arsiv_fatura FOREIGN KEY (fatura_id) REFERENCES faturalar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
