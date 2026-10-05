SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS personeller (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kurum_id BIGINT UNSIGNED NOT NULL,
  kullanici_id BIGINT UNSIGNED NULL,
  tc_kimlik_no VARCHAR(11) NULL,
  ad VARCHAR(120) NOT NULL,
  soyad VARCHAR(120) NOT NULL,
  pozisyon VARCHAR(160) NULL,
  aylik_brut_ucret DECIMAL(12,2) NOT NULL DEFAULT 0,
  sgk_tesvik_turu VARCHAR(30) NOT NULL DEFAULT 'diger_2_puan',
  ise_giris_tarihi DATE NULL,
  isten_cikis_tarihi DATE NULL,
  varsayilan_giris_saati TIME NULL,
  varsayilan_cikis_saati TIME NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  notlar TEXT NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_personel_tc (kurum_id, tc_kimlik_no),
  UNIQUE KEY uq_personel_kullanici (kurum_id, kullanici_id),
  KEY idx_personel_aktif (kurum_id, aktif, ad, soyad),
  CONSTRAINT fk_personel_kurum FOREIGN KEY (kurum_id) REFERENCES kurumlar(id) ON DELETE CASCADE,
  CONSTRAINT fk_personel_kullanici FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS personel_puantajlari (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kurum_id BIGINT UNSIGNED NOT NULL,
  personel_id BIGINT UNSIGNED NOT NULL,
  tarih DATE NOT NULL,
  durum VARCHAR(30) NOT NULL DEFAULT 'calisti',
  giris_saati TIME NULL,
  cikis_saati TIME NULL,
  mola_dakika SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  aciklama VARCHAR(500) NULL,
  kaydeden_kullanici_id BIGINT UNSIGNED NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_personel_puantaj (kurum_id, personel_id, tarih),
  KEY idx_puantaj_tarih (kurum_id, tarih, durum),
  CONSTRAINT fk_puantaj_kurum FOREIGN KEY (kurum_id) REFERENCES kurumlar(id) ON DELETE CASCADE,
  CONSTRAINT fk_puantaj_personel FOREIGN KEY (personel_id) REFERENCES personeller(id) ON DELETE CASCADE,
  CONSTRAINT fk_puantaj_kaydeden FOREIGN KEY (kaydeden_kullanici_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO rol_yetkileri (rol_id, yetki, olusturulma_tarihi)
SELECT id, 'personel_listele', NOW() FROM roller WHERE kod IN ('yonetici', 'muhasebe');

INSERT IGNORE INTO rol_yetkileri (rol_id, yetki, olusturulma_tarihi)
SELECT id, 'personel_yonet', NOW() FROM roller WHERE kod = 'yonetici';
