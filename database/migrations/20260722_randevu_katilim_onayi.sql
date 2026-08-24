SET NAMES utf8mb4;

ALTER TABLE randevular
  ADD COLUMN katilim_token VARCHAR(80) NULL AFTER otomatik_gelmedi_islendi,
  ADD COLUMN katilim_yaniti ENUM('katilacagim','katilamayacagim') NULL AFTER katilim_token,
  ADD COLUMN katilim_yanit_tarihi DATETIME NULL AFTER katilim_yaniti,
  ADD UNIQUE KEY uq_randevu_katilim_token (katilim_token);

UPDATE sms_sablonlari
SET mesaj = CONCAT(mesaj, ' Katilim durumunuzu bildirmek icin: {katilim_linki}')
WHERE anahtar IN ('randevu_hatirlatma', 'tanisma_dersi_hatirlatma', 'veli_gorusmesi_hatirlatma', 'workshop_hatirlatma')
  AND mesaj NOT LIKE '%{katilim_linki}%';
