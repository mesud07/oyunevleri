SET NAMES utf8mb4;

ALTER TABLE sms_sablonlari
  ADD COLUMN onay_durumu VARCHAR(30) NOT NULL DEFAULT 'kullanilabilir' AFTER otomatik_gonderim,
  ADD COLUMN onay_notu TEXT NULL AFTER onay_durumu,
  ADD COLUMN son_onay_tarihi DATETIME NULL AFTER onay_notu,
  ADD COLUMN netgsm_onay_id VARCHAR(120) NULL AFTER son_onay_tarihi;

UPDATE sms_sablonlari
SET onay_durumu = 'kullanilabilir'
WHERE onay_durumu IS NULL OR onay_durumu = '';
