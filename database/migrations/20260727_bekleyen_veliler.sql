SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS bekleyen_veliler (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ogrenci_id BIGINT UNSIGNED NULL,
  ogrenci_ad_soyad VARCHAR(160) NOT NULL,
  ogrenci_dogum_tarihi DATE NULL,
  veli_ad_soyad VARCHAR(160) NOT NULL,
  veli_telefon VARCHAR(32) NOT NULL,
  veli_eposta VARCHAR(160) NULL,
  beklenen_gun VARCHAR(20) NULL,
  ay_grubu VARCHAR(80) NULL,
  zaman_tercihi ENUM('hafta_ici','hafta_sonu','farketmez') NOT NULL DEFAULT 'farketmez',
  durum ENUM('bekliyor','iletisime_gecildi','kayda_donustu','iptal') NOT NULL DEFAULT 'bekliyor',
  notlar TEXT NULL,
  olusturan_kullanici_id BIGINT UNSIGNED NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncellenme_tarihi DATETIME NULL,
  INDEX idx_bekleyen_veliler_ogrenci (ogrenci_id),
  INDEX idx_bekleyen_veliler_durum (durum),
  INDEX idx_bekleyen_veliler_telefon (veli_telefon),
  INDEX idx_bekleyen_veliler_gun (beklenen_gun),
  CONSTRAINT fk_bekleyen_veliler_kullanici FOREIGN KEY (olusturan_kullanici_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO rol_yetkileri (rol_id, yetki)
SELECT r.id, y.yetki
FROM roller r
JOIN (
  SELECT 'bekleyen_veli_listele' AS yetki UNION ALL
  SELECT 'bekleyen_veli_ekle'
) y
WHERE r.kod IN ('kurucu', 'yonetici', 'resepsiyon')
  AND NOT EXISTS (
    SELECT 1
    FROM rol_yetkileri ry
    WHERE ry.rol_id = r.id AND ry.yetki = y.yetki
  );
