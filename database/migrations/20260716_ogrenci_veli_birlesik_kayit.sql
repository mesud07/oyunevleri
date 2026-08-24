ALTER TABLE veliler
  ADD COLUMN tc_kimlik_no VARCHAR(20) NULL AFTER soyad,
  ADD COLUMN telefon_ulke VARCHAR(80) NULL AFTER tc_kimlik_no,
  ADD COLUMN yedek_telefon VARCHAR(40) NULL AFTER telefon,
  ADD COLUMN il VARCHAR(100) NULL AFTER yakinlik,
  ADD COLUMN ilce VARCHAR(100) NULL AFTER il;

ALTER TABLE ogrenciler
  ADD COLUMN tc_kimlik_no VARCHAR(20) NULL AFTER soyad,
  ADD COLUMN vasi_ad_soyad VARCHAR(190) NULL AFTER ozel_durum_notu,
  ADD COLUMN vasi_tc_kimlik_no VARCHAR(20) NULL AFTER vasi_ad_soyad,
  ADD COLUMN vasi_telefon VARCHAR(40) NULL AFTER vasi_tc_kimlik_no;
