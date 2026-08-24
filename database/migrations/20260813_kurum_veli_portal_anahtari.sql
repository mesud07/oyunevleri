ALTER TABLE kurumlar
    ADD COLUMN veli_portal_anahtari CHAR(32) NULL AFTER logo_yolu,
    ADD UNIQUE KEY uq_kurumlar_veli_portal_anahtari (veli_portal_anahtari);

UPDATE kurumlar
SET veli_portal_anahtari = LOWER(HEX(RANDOM_BYTES(16)))
WHERE veli_portal_anahtari IS NULL OR veli_portal_anahtari = '';
