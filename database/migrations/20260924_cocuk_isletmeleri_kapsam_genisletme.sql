ALTER TABLE cocuk_isletmeleri
  MODIFY kategori ENUM('okul','anaokulu','kres','oyun_evi','cocuk_parki','diger') NOT NULL DEFAULT 'diger';
