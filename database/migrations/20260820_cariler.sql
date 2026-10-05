SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS cariler (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kurum_id BIGINT UNSIGNED NOT NULL,
  veli_id BIGINT UNSIGNED NULL,
  profil_turu ENUM('bireysel','kurumsal') NOT NULL DEFAULT 'bireysel',
  ad VARCHAR(120) NULL,
  soyad VARCHAR(120) NULL,
  unvan VARCHAR(500) NULL,
  vkn_tckn VARCHAR(20) NOT NULL,
  vergi_dairesi VARCHAR(190) NULL,
  adres TEXT NOT NULL,
  il VARCHAR(100) NOT NULL,
  ilce VARCHAR(100) NOT NULL,
  ulke VARCHAR(100) NOT NULL DEFAULT 'TÜRKİYE',
  eposta VARCHAR(190) NULL,
  telefon VARCHAR(40) NULL,
  notlar TEXT NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cari_vkn (kurum_id,vkn_tckn),
  UNIQUE KEY uq_cari_veli (kurum_id,veli_id),
  KEY idx_cari_unvan (kurum_id,unvan),
  CONSTRAINT fk_cari_kurum FOREIGN KEY (kurum_id) REFERENCES kurumlar(id) ON DELETE CASCADE,
  CONSTRAINT fk_cari_veli FOREIGN KEY (veli_id) REFERENCES veliler(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE faturalar ADD COLUMN cari_id BIGINT UNSIGNED NULL AFTER ogrenci_id;
ALTER TABLE faturalar ADD KEY idx_fatura_cari (kurum_id,cari_id);
ALTER TABLE faturalar ADD CONSTRAINT fk_fatura_cari FOREIGN KEY (cari_id) REFERENCES cariler(id) ON DELETE SET NULL;
