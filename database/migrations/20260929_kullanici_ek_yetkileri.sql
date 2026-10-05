CREATE TABLE IF NOT EXISTS kullanici_ek_yetkileri (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kurum_id BIGINT UNSIGNED NOT NULL,
  kullanici_id BIGINT UNSIGNED NOT NULL,
  yetki VARCHAR(100) NOT NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_kullanici_ek_yetki (kurum_id, kullanici_id, yetki),
  KEY idx_kullanici_ek_yetkileri_kullanici (kullanici_id),
  CONSTRAINT fk_kullanici_ek_yetkileri_kurum FOREIGN KEY (kurum_id) REFERENCES kurumlar(id) ON DELETE CASCADE,
  CONSTRAINT fk_kullanici_ek_yetkileri_kullanici FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE ry
FROM rol_yetkileri ry
INNER JOIN roller r ON r.id = ry.rol_id
WHERE r.kod = 'ogretmen' AND ry.yetki = 'ogrenci_ekle';
