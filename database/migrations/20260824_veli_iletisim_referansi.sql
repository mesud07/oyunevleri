ALTER TABLE veliler
    ADD COLUMN IF NOT EXISTS iletisim_referansi VARCHAR(190) NULL AFTER adres;

ALTER TABLE bekleyen_veliler
    ADD COLUMN IF NOT EXISTS iletisim_referansi VARCHAR(190) NULL AFTER ay_grubu;
