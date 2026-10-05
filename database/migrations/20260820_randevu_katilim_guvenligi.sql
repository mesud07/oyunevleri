ALTER TABLE randevular
  ADD COLUMN katilim_token_hash CHAR(64) NULL AFTER katilim_token,
  ADD COLUMN katilim_token_son_kullanim DATETIME NULL AFTER katilim_token_hash,
  ADD COLUMN katilim_token_iptal_tarihi DATETIME NULL AFTER katilim_token_son_kullanim,
  ADD UNIQUE KEY uq_randevu_katilim_token_hash (katilim_token_hash);

UPDATE randevular
SET katilim_token_hash = SHA2(katilim_token, 256),
    katilim_token_son_kullanim = TIMESTAMP(tarih, baslangic_saati),
    katilim_token = NULL
WHERE katilim_token IS NOT NULL AND katilim_token <> '';
