INSERT IGNORE INTO rol_yetkileri (rol_id, yetki)
SELECT id, 'kullanici_yonet'
FROM roller
WHERE kod = 'kurucu';
