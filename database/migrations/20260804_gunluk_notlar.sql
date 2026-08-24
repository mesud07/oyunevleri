CREATE TABLE IF NOT EXISTS gunluk_notlar (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ogrenci_id BIGINT UNSIGNED NOT NULL,
  randevu_id BIGINT UNSIGNED NULL,
  tarih DATE NOT NULL,
  kategori VARCHAR(80) NOT NULL DEFAULT 'Genel',
  not_metni TEXT NOT NULL,
  olusturan_kullanici_id BIGINT UNSIGNED NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_gunluk_notlar_tarih (tarih),
  INDEX idx_gunluk_notlar_ogrenci (ogrenci_id, tarih),
  INDEX idx_gunluk_notlar_randevu (randevu_id),
  CONSTRAINT fk_gunluk_notlar_ogrenci FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id) ON DELETE CASCADE,
  CONSTRAINT fk_gunluk_notlar_randevu FOREIGN KEY (randevu_id) REFERENCES randevular(id) ON DELETE SET NULL,
  CONSTRAINT fk_gunluk_notlar_kullanici FOREIGN KEY (olusturan_kullanici_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
