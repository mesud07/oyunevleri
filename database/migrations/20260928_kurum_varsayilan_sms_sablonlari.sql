SET NAMES utf8mb4;

-- Her kurum eksik standart SMS sablonlarini alir. INSERT IGNORE sayesinde
-- kurumun daha once ozellestirdigi sablonlar degistirilmez.
INSERT IGNORE INTO sms_sablonlari
  (kurum_id, anahtar, baslik, mesaj, aktif, otomatik_gonderim, onay_durumu, aciklama)
SELECT k.id, s.anahtar, s.baslik, s.mesaj, 1, s.otomatik_gonderim, 'kullanilabilir', s.aciklama
FROM kurumlar k
CROSS JOIN (
  SELECT 'randevu_olusturuldu' anahtar, 'Randevu Olusturuldu' baslik, 'Sayin {veli_adi}, {ogrenci_adi} icin {paket_adi} randevulariniz olusturuldu: {randevu_listesi}. {kurum_adi}' mesaj, 1 otomatik_gonderim, 'Randevu olusturulunca kuyruga eklenir.' aciklama
  UNION ALL SELECT 'randevu_hatirlatma', 'Randevu Hatirlatma', 'Sayin {veli_adi}, {ogrenci_adi} icin {tarih} {saat} tarihinde {paket_adi} randevunuz bulunmaktadir. Katilim durumunuz: {katilim_linki} {kurum_adi}', 1, 'Standart randevu hatirlatmasi.'
  UNION ALL SELECT 'tanisma_dersi_hatirlatma', 'Tanisma Dersi Hatirlatma', 'Sayin {veli_adi}, {ogrenci_adi} icin tanisma dersiniz {tarih} {saat} tarihinde planlanmistir. Katilim durumunuz: {katilim_linki} {kurum_adi}', 1, 'Tek derslik tanisma hatirlatmasi.'
  UNION ALL SELECT 'veli_gorusmesi_hatirlatma', 'Veli Gorusmesi Hatirlatma', 'Sayin {veli_adi}, veli gorusmeniz {tarih} {saat} tarihinde planlanmistir. Katilim durumunuz: {katilim_linki} {kurum_adi}', 1, 'Veli gorusmesi hatirlatmasi.'
  UNION ALL SELECT 'workshop_hatirlatma', 'Workshop Hatirlatma', 'Sayin {veli_adi}, {ogrenci_adi} icin workshop etkinligi {tarih} {saat} tarihinde planlanmistir. Katilim durumunuz: {katilim_linki} {kurum_adi}', 1, 'Workshop hatirlatmasi.'
  UNION ALL SELECT 'odeme_alindi', 'Odeme Alindi', 'Sayin {veli_adi}, {ogrenci_adi} icin {odeme_tutari} tutarindaki odemeniz alinmistir. Kalan borc: {kalan_borc}. {kurum_adi}', 1, 'Tahsilat sonrasi bilgilendirme.'
  UNION ALL SELECT 'odeme_sozu_hatirlatma', 'Odeme Sozu Hatirlatma', 'Sayin {veli_adi}, {ogrenci_adi} icin {odeme_sozu_tarihi} tarihli {odeme_tutari} odeme sozunuz bulunmaktadir. {kurum_adi}', 1, 'Odeme sozu hatirlatmasi.'
  UNION ALL SELECT 'geciken_odeme', 'Geciken Odeme', 'Sayin {veli_adi}, {ogrenci_adi} icin {kalan_borc} tutarinda gecikmis odeme gorunmektedir. {kurum_adi}', 0, 'Manuel veya rapordan gonderilebilir.'
  UNION ALL SELECT 'paket_bitiyor', 'Paket Bitiyor', 'Sayin {veli_adi}, {ogrenci_adi} icin {paket_adi} paketiniz {tarih} tarihinde bitiyor. Kayit yenileme icin bizimle iletisime gecebilirsiniz. {kurum_adi}', 0, 'Paket yenileme bilgilendirmesi.'
  UNION ALL SELECT 'manuel_sms', 'Manuel SMS', '{mesaj}', 0, 'Manuel SMS metni.'
  UNION ALL SELECT 'randevu_guncellendi', 'Randevu Guncellendi', 'Sayin {veli_adi}, {ogrenci_adi} icin {eski_tarih} {eski_saat} randevunuz {tarih} {saat} olarak guncellenmistir. {paket_adi} - {kurum_adi}', 1, 'Randevu guncellendiginde veliye gonderilir.'
  UNION ALL SELECT 'dogum_gunu', 'Dogum Gunu', 'Sayin {veli_adi}, {ogrenci_adi} icin mutlu ve saglikli yaslar dileriz. {kurum_adi}', 1, 'Dogum gunu olan aktif ogrenciler icin otomatik SMS.'
  UNION ALL SELECT 'telafi_dersi_olusturuldu', 'Telafi Dersi Olusturuldu', 'Sayin {veli_adi}, {ogrenci_adi} icin {kaynak_tarih} {kaynak_saat} tarihli dersin telafisi {tarih} {saat} olarak planlanmistir. {kurum_adi}', 1, 'Telafi dersi planlandiginda veliye gonderilir.'
) s;

-- {kurum_adi} degiskeni her kurumda kendi resmi adini kullansin.
INSERT IGNORE INTO ayarlar (kurum_id, anahtar, deger, aciklama)
SELECT id, 'kurum_adi', ad, 'SMS ve panel metinlerinde kullanilan kurum adi.'
FROM kurumlar;
