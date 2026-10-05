SET NAMES utf8mb4;

ALTER TABLE personeller
  ADD COLUMN aylik_brut_ucret DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER pozisyon,
  ADD COLUMN sgk_tesvik_turu VARCHAR(30) NOT NULL DEFAULT 'diger_2_puan' AFTER aylik_brut_ucret;
