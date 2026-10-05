ALTER TABLE kullanicilar
  ADD COLUMN oturum_surumu INT UNSIGNED NOT NULL DEFAULT 1 AFTER mfa_kurtarma_kodlari_sifreli;
