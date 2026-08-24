INSERT INTO ayarlar (anahtar, deger, aciklama) VALUES
('sms_appointment_reminder_enabled', '1', 'Randevu hatirlatma SMS otomasyonu aktiflik bilgisi.'),
('sms_appointment_reminder_days_before', '1', 'Randevudan kac gun once hatirlatma SMS kuyruga alinacak.'),
('sms_appointment_reminder_time', '14:00', 'Hatirlatma SMS kuyruga alma saati.')
ON DUPLICATE KEY UPDATE deger = deger;
