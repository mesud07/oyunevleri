UPDATE bekleyen_veliler
SET durum = CASE
  WHEN durum IN ('yeni_talep','ilk_gorusme_yapilacak','tekrar_aranacak','uygun_grup_bekliyor','veli_donusu_bekleniyor') THEN 'bekliyor'
  WHEN durum = 'kayit_olmaya_hazir' THEN 'iletisime_gecildi'
  WHEN durum = 'kayit_oldu' THEN 'kayda_donustu'
  WHEN durum IN ('vazgecti','yas_uygun_degil','saatler_uymadi','diger') THEN 'iptal'
  ELSE durum
END;

ALTER TABLE bekleyen_veliler
  DROP KEY idx_bekleyen_veliler_crm_takip,
  DROP COLUMN next_action_note,
  DROP COLUMN next_action_type,
  DROP COLUMN next_follow_up_at,
  MODIFY COLUMN durum ENUM(
    'bekliyor','iletisime_gecildi','bilgi_verildi','ulasilamadi','katilmadi','kayda_donustu','iptal'
  ) NOT NULL DEFAULT 'bekliyor';

UPDATE bekleyen_veli_gorusmeleri
SET sonuc = CASE sonuc
  WHEN 'goruldu' THEN 'bilgi_verildi'
  WHEN 'veli_donecek' THEN 'kararsiz'
  WHEN 'kayit_istiyor' THEN 'randevu_planlandi'
  WHEN 'uygun_grup_yok' THEN 'diger'
  ELSE sonuc
END;

ALTER TABLE bekleyen_veli_gorusmeleri
  MODIFY COLUMN sonuc ENUM(
    'bilgi_verildi','tekrar_aranacak','randevu_planlandi','kararsiz','ulasilamadi','katilmadi','olumsuz','diger'
  ) NOT NULL DEFAULT 'bilgi_verildi';

