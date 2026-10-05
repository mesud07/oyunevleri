ALTER TABLE bekleyen_veliler
  MODIFY COLUMN durum ENUM(
    'yeni_talep','ilk_gorusme_yapilacak','bilgi_verildi','uygun_grup_bekliyor',
    'veli_donusu_bekleniyor','tekrar_aranacak','kayit_olmaya_hazir','kayit_oldu',
    'vazgecti','ulasilamadi','yas_uygun_degil','saatler_uymadi','diger',
    'bekliyor','iletisime_gecildi','katilmadi','kayda_donustu','iptal'
  ) NOT NULL DEFAULT 'yeni_talep',
  ADD COLUMN next_follow_up_at DATETIME NULL AFTER durum,
  ADD COLUMN next_action_type ENUM(
    'telefonla_ara','whatsapp_gonder','veli_donusunu_bekle','grup_kontrol_et','kayit_icin_ara','diger'
  ) NULL AFTER next_follow_up_at,
  ADD COLUMN next_action_note VARCHAR(500) NULL AFTER next_action_type,
  ADD KEY idx_bekleyen_veliler_crm_takip (kurum_id, durum, next_follow_up_at);

UPDATE bekleyen_veliler
SET durum = CASE durum
  WHEN 'bekliyor' THEN 'yeni_talep'
  WHEN 'iletisime_gecildi' THEN 'bilgi_verildi'
  WHEN 'katilmadi' THEN 'vazgecti'
  WHEN 'kayda_donustu' THEN 'kayit_oldu'
  WHEN 'iptal' THEN 'vazgecti'
  ELSE durum
END;

UPDATE bekleyen_veliler bv
INNER JOIN (
  SELECT bekleyen_veli_id, MAX(sonraki_takip_tarihi) AS takip_tarihi
  FROM bekleyen_veli_gorusmeleri
  WHERE sonraki_takip_tarihi IS NOT NULL
  GROUP BY bekleyen_veli_id
) bg ON bg.bekleyen_veli_id = bv.id
SET bv.next_follow_up_at = CONCAT(bg.takip_tarihi, ' 09:00:00'),
    bv.next_action_type = 'telefonla_ara'
WHERE bv.next_follow_up_at IS NULL;

ALTER TABLE bekleyen_veli_gorusmeleri
  MODIFY COLUMN sonuc ENUM(
    'goruldu','bilgi_verildi','veli_donecek','tekrar_aranacak','kayit_istiyor','uygun_grup_yok',
    'randevu_planlandi','kararsiz','ulasilamadi','katilmadi','olumsuz','diger'
  ) NOT NULL DEFAULT 'bilgi_verildi';

