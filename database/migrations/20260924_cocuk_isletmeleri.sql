CREATE TABLE IF NOT EXISTS cocuk_isletmeleri (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  osm_turu ENUM('node','way','relation') NOT NULL,
  osm_id BIGINT UNSIGNED NOT NULL,
  kaynak_kimligi VARCHAR(255) NULL,
  ad VARCHAR(255) NOT NULL,
  kategori ENUM('okul','anaokulu','kres','oyun_evi','cocuk_parki','diger') NOT NULL DEFAULT 'diger',
  adres VARCHAR(500) NULL,
  ilce VARCHAR(100) NULL,
  telefon VARCHAR(100) NULL,
  web_sitesi VARCHAR(500) NULL,
  enlem DECIMAL(10,7) NOT NULL,
  boylam DECIMAL(10,7) NOT NULL,
  kaynak VARCHAR(30) NOT NULL DEFAULT 'openstreetmap',
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  kaynak_guncellenme_tarihi DATETIME NULL,
  onbellek_son_tarihi DATETIME NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cocuk_isletmeleri_osm (osm_turu, osm_id),
  UNIQUE KEY uq_cocuk_isletmeleri_kaynak (kaynak, kaynak_kimligi),
  KEY idx_cocuk_isletmeleri_kategori (aktif, kategori),
  KEY idx_cocuk_isletmeleri_konum (enlem, boylam)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cocuk_isletme_aktarimlari (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kaynak VARCHAR(30) NOT NULL DEFAULT 'openstreetmap',
  kayit_sayisi INT UNSIGNED NOT NULL DEFAULT 0,
  aktarim_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
