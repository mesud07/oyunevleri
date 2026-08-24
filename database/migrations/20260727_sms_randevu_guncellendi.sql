SET NAMES utf8mb4;

INSERT INTO sms_sablonlari (anahtar, baslik, mesaj, aktif, otomatik_gonderim, aciklama)
VALUES (
    'randevu_guncellendi',
    'Randevu Guncellendi',
    'Sayin {veli_adi}, {ogrenci_adi} randevusu guncellendi. Eski: {eski_tarih} {eski_saat}. Yeni: {tarih} {saat}. {paket_adi}. {kurum_adi}',
    1,
    1,
    'Randevu duzenleme ekraninda SMS secenegi isaretlenirse gonderilir.'
)
ON DUPLICATE KEY UPDATE
    baslik = VALUES(baslik),
    mesaj = VALUES(mesaj),
    aktif = VALUES(aktif),
    otomatik_gonderim = VALUES(otomatik_gonderim),
    aciklama = VALUES(aciklama);
