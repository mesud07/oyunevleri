SET NAMES utf8mb4;

INSERT INTO sms_sablonlari (anahtar, baslik, mesaj, aktif, otomatik_gonderim, aciklama)
VALUES (
  'telafi_dersi_olusturuldu',
  'Telafi Dersi Olusturuldu',
  'Sayin {veli_adi}, {ogrenci_adi} icin telafi dersiniz {tarih} saat {saat} olarak planlanmistir. Kaynak ders: {kaynak_tarih} {kaynak_saat}. {kurum_adi}',
  1,
  1,
  'Telafi dersi planlandiginda veliye gonderilir.'
)
ON DUPLICATE KEY UPDATE
  baslik = VALUES(baslik),
  mesaj = VALUES(mesaj),
  aktif = VALUES(aktif),
  otomatik_gonderim = VALUES(otomatik_gonderim),
  aciklama = VALUES(aciklama);
