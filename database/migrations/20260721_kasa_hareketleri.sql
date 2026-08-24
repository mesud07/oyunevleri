CREATE TABLE IF NOT EXISTS kasa_hareketleri (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kasa_id BIGINT UNSIGNED NOT NULL,
  tarih DATE NOT NULL,
  tur ENUM('giris','cikis') NOT NULL DEFAULT 'giris',
  tutar DECIMAL(14,2) NOT NULL,
  aciklama VARCHAR(255) NULL,
  olusturan_kullanici_id BIGINT UNSIGNED NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_kasa_hareketleri_kasa (kasa_id),
  INDEX idx_kasa_hareketleri_tarih (tarih),
  CONSTRAINT fk_kasa_hareketleri_kasa FOREIGN KEY (kasa_id) REFERENCES kasalar(id) ON DELETE CASCADE,
  CONSTRAINT fk_kasa_hareketleri_kullanici FOREIGN KEY (olusturan_kullanici_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
