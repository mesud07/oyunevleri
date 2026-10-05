-- Kurum yöneticileri kendi kurumlarının NetGSM, otomasyon, şablon ve rapor
-- ayarlarını yönetebilir. Ayar ve SMS tabloları kurum_id ile izole edilir.
INSERT IGNORE INTO rol_yetkileri (rol_id, yetki, olusturulma_tarihi)
SELECT r.id, y.yetki, NOW()
FROM roller r
JOIN (
  SELECT 'sms_sablon_yonet' AS yetki
  UNION ALL SELECT 'sms_tekrar_gonder'
  UNION ALL SELECT 'sms_ayar_yonet'
  UNION ALL SELECT 'sms_rapor_goruntule'
) y
WHERE r.kod = 'yonetici';
