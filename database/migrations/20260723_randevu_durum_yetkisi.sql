INSERT IGNORE INTO rol_yetkileri (rol_id, yetki)
SELECT id, 'randevu_durum_degistir'
FROM roller
WHERE kod IN ('kurucu', 'yonetici', 'ogretmen', 'resepsiyon');
