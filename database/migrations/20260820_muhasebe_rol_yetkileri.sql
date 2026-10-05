SET NAMES utf8mb4;

DELETE ry
FROM rol_yetkileri ry
INNER JOIN roller r ON r.id = ry.rol_id
WHERE r.kod = 'muhasebe';

INSERT INTO rol_yetkileri (rol_id, yetki, olusturulma_tarihi)
SELECT r.id, y.yetki, NOW()
FROM roller r
JOIN (
  SELECT 'paket_listele' AS yetki
  UNION ALL SELECT 'odeme_listele'
  UNION ALL SELECT 'odeme_ekle'
  UNION ALL SELECT 'rapor_ozet'
) y
WHERE r.kod = 'muhasebe';

INSERT IGNORE INTO rol_yetkileri (rol_id, yetki, olusturulma_tarihi)
SELECT id, 'fatura_entegrasyon_yonet', NOW()
FROM roller
WHERE kod = 'kurucu';
