ALTER TABLE cocuk_isletmeleri
  ADD COLUMN kaynak_kimligi VARCHAR(255) NULL AFTER osm_id,
  ADD COLUMN onbellek_son_tarihi DATETIME NULL AFTER kaynak_guncellenme_tarihi,
  ADD UNIQUE KEY uq_cocuk_isletmeleri_kaynak (kaynak, kaynak_kimligi);

UPDATE cocuk_isletmeleri
SET kaynak_kimligi = CONCAT(osm_turu, ':', osm_id)
WHERE kaynak = 'openstreetmap' AND kaynak_kimligi IS NULL;
