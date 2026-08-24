CREATE TABLE IF NOT EXISTS hizmetler (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hizmet_adi VARCHAR(190) NOT NULL,
  ucret DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  haftalik_katilim_sayisi TINYINT UNSIGNED NOT NULL DEFAULT 1,
  toplam_normal_hak INT UNSIGNED NOT NULL DEFAULT 4,
  toplam_telafi_hak INT UNSIGNED NOT NULL DEFAULT 1,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO hizmetler (hizmet_adi, ucret, haftalik_katilim_sayisi, toplam_normal_hak, toplam_telafi_hak)
SELECT 'Oyun Grubu 4 Seans (24-36 Ay)', 4000.00, 1, 4, 1
WHERE NOT EXISTS (SELECT 1 FROM hizmetler WHERE hizmet_adi = 'Oyun Grubu 4 Seans (24-36 Ay)');
