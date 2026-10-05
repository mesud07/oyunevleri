SET NAMES utf8mb4;

ALTER TABLE kurum_entegrasyonlari
  ADD COLUMN adres_basligi VARCHAR(100) NULL AFTER vergi_dairesi,
  ADD COLUMN posta_kodu VARCHAR(20) NULL AFTER ilce,
  ADD COLUMN ulke VARCHAR(100) NULL AFTER posta_kodu,
  ADD COLUMN eposta VARCHAR(190) NULL AFTER ulke,
  ADD COLUMN telefon VARCHAR(40) NULL AFTER eposta,
  ADD COLUMN web_sitesi VARCHAR(255) NULL AFTER telefon;
