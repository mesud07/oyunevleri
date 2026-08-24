SET NAMES utf8mb4;

UPDATE sms_sablonlari
SET mesaj = 'Sayin {veli_adi}, {ogrenci_adi} icin {paket_adi} randevulariniz olusturuldu: {randevu_listesi}. {kurum_adi}'
WHERE anahtar = 'randevu_olusturuldu';
