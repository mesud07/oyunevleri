ALTER TABLE kullanicilar
  ADD COLUMN mfa_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER sistem_yoneticisi,
  ADD COLUMN mfa_secret_sifreli TEXT NULL AFTER mfa_enabled,
  ADD COLUMN mfa_kurtarma_kodlari_sifreli TEXT NULL AFTER mfa_secret_sifreli;
