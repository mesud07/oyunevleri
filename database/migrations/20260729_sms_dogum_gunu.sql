INSERT INTO sms_sablonlari (anahtar, baslik, mesaj, aktif, otomatik_gonderim, aciklama)
VALUES (
  'dogum_gunu',
  'Dogum Gunu Kutlama',
  'Sayin {veli_adi}, {ogrenci_adi} icin mutlu yaslar dileriz. Yeni yasi saglik, oyun ve mutlulukla dolsun. {kurum_adi}',
  1,
  1,
  'Dogum gunu olan aktif ogrenciler icin otomatik SMS.'
)
ON DUPLICATE KEY UPDATE
  baslik = VALUES(baslik),
  mesaj = VALUES(mesaj),
  aktif = VALUES(aktif),
  otomatik_gonderim = VALUES(otomatik_gonderim),
  aciklama = VALUES(aciklama);

INSERT INTO ayarlar (anahtar, deger, aciklama) VALUES
('sms_birthday_message_enabled', '1', 'Dogum gunu SMS otomasyonu aktiflik bilgisi.'),
('sms_birthday_message_time', '09:00', 'Dogum gunu SMS kuyruga alma saati.')
ON DUPLICATE KEY UPDATE
  deger = VALUES(deger),
  aciklama = VALUES(aciklama);
