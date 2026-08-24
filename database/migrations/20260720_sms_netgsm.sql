SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS sms_sablonlari (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  anahtar VARCHAR(100) NOT NULL UNIQUE,
  baslik VARCHAR(190) NOT NULL,
  mesaj TEXT NOT NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  otomatik_gonderim TINYINT(1) NOT NULL DEFAULT 0,
  aciklama TEXT NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sms_kayitlari (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sablon_anahtari VARCHAR(100) NULL,
  olay_tipi VARCHAR(100) NOT NULL DEFAULT 'manuel_sms',
  alici_tipi ENUM('veli','ogrenci','manuel') NOT NULL DEFAULT 'manuel',
  alici_id BIGINT UNSIGNED NULL,
  ogrenci_id BIGINT UNSIGNED NULL,
  veli_id BIGINT UNSIGNED NULL,
  grup_id BIGINT UNSIGNED NULL,
  randevu_id BIGINT UNSIGNED NULL,
  odeme_id BIGINT UNSIGNED NULL,
  odeme_sozu_id BIGINT UNSIGNED NULL,
  telefon_orijinal VARCHAR(60) NULL,
  telefon VARCHAR(20) NOT NULL,
  mesaj TEXT NOT NULL,
  parca_sayisi SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  durum VARCHAR(30) NOT NULL DEFAULT 'bekliyor',
  mukerrer_anahtari VARCHAR(190) NULL,
  deneme_sayisi SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  sonraki_deneme_tarihi DATETIME NULL,
  provider VARCHAR(50) NOT NULL DEFAULT 'netgsm',
  provider_islem_no VARCHAR(120) NULL,
  provider_cevabi TEXT NULL,
  hata_mesaji TEXT NULL,
  gonderilme_tarihi DATETIME NULL,
  teslim_tarihi DATETIME NULL,
  iptal_tarihi DATETIME NULL,
  iptal_eden_kullanici_id BIGINT UNSIGNED NULL,
  olusturan_kullanici_id BIGINT UNSIGNED NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY sms_mukerrer_unique (mukerrer_anahtari),
  INDEX idx_sms_durum_deneme (durum, sonraki_deneme_tarihi),
  INDEX idx_sms_telefon (telefon),
  INDEX idx_sms_ogrenci (ogrenci_id),
  INDEX idx_sms_randevu (randevu_id),
  INDEX idx_sms_odeme (odeme_id),
  CONSTRAINT fk_sms_ogrenci FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id) ON DELETE SET NULL,
  CONSTRAINT fk_sms_veli FOREIGN KEY (veli_id) REFERENCES veliler(id) ON DELETE SET NULL,
  CONSTRAINT fk_sms_grup FOREIGN KEY (grup_id) REFERENCES gruplar(id) ON DELETE SET NULL,
  CONSTRAINT fk_sms_randevu FOREIGN KEY (randevu_id) REFERENCES randevular(id) ON DELETE SET NULL,
  CONSTRAINT fk_sms_odeme FOREIGN KEY (odeme_id) REFERENCES odemeler(id) ON DELETE SET NULL,
  CONSTRAINT fk_sms_odeme_sozu FOREIGN KEY (odeme_sozu_id) REFERENCES odeme_sozleri(id) ON DELETE SET NULL,
  CONSTRAINT fk_sms_iptal_kullanici FOREIGN KEY (iptal_eden_kullanici_id) REFERENCES kullanicilar(id) ON DELETE SET NULL,
  CONSTRAINT fk_sms_olusturan_kullanici FOREIGN KEY (olusturan_kullanici_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sms_olay_kayitlari (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sms_kaydi_id BIGINT UNSIGNED NOT NULL,
  eski_durum VARCHAR(30) NULL,
  yeni_durum VARCHAR(30) NOT NULL,
  mesaj TEXT NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sms_olay_sms FOREIGN KEY (sms_kaydi_id) REFERENCES sms_kayitlari(id) ON DELETE CASCADE,
  INDEX idx_sms_olay_sms (sms_kaydi_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO sms_sablonlari (anahtar, baslik, mesaj, aktif, otomatik_gonderim, aciklama) VALUES
('randevu_olusturuldu', 'Randevu Olusturuldu', 'Sayin {veli_adi}, {ogrenci_adi} icin {tarih} {saat} tarihinde {paket_adi} randevusu olusturulmustur. {kurum_adi}', 1, 1, 'Randevu olusturulunca kuyruga eklenir.'),
('randevu_hatirlatma', 'Randevu Hatirlatma', 'Sayin {veli_adi}, {ogrenci_adi} icin {tarih} {saat} tarihinde {paket_adi} randevunuz bulunmaktadir. {kurum_adi}', 1, 1, 'Standart randevu hatirlatmasi.'),
('tanisma_dersi_hatirlatma', 'Tanisma Dersi Hatirlatma', 'Sayin {veli_adi}, {ogrenci_adi} icin tanisma dersiniz {tarih} {saat} tarihinde planlanmistir. {kurum_adi}', 1, 1, 'Tek derslik tanisma hatirlatmasi.'),
('veli_gorusmesi_hatirlatma', 'Veli Gorusmesi Hatirlatma', 'Sayin {veli_adi}, veli gorusmeniz {tarih} {saat} tarihinde planlanmistir. {kurum_adi}', 1, 1, 'Veli gorusmesi hatirlatmasi.'),
('workshop_hatirlatma', 'Workshop Hatirlatma', 'Sayin {veli_adi}, {ogrenci_adi} icin workshop etkinligi {tarih} {saat} tarihinde planlanmistir. {kurum_adi}', 1, 1, 'Workshop hatirlatmasi.'),
('odeme_alindi', 'Odeme Alindi', 'Sayin {veli_adi}, {ogrenci_adi} icin {odeme_tutari} tutarindaki odemeniz alinmistir. Kalan borc: {kalan_borc}. {kurum_adi}', 1, 1, 'Tahsilat sonrasi bilgilendirme.'),
('odeme_sozu_hatirlatma', 'Odeme Sozu Hatirlatma', 'Sayin {veli_adi}, {ogrenci_adi} icin {odeme_sozu_tarihi} tarihli {odeme_tutari} odeme sozunuz bulunmaktadir. {kurum_adi}', 1, 1, 'Odeme sozu hatirlatmasi.'),
('geciken_odeme', 'Geciken Odeme', 'Sayin {veli_adi}, {ogrenci_adi} icin {kalan_borc} tutarinda gecikmis odeme gorunmektedir. {kurum_adi}', 1, 0, 'Manuel veya rapordan gonderilebilir.'),
('paket_bitiyor', 'Paket Bitiyor', 'Sayin {veli_adi}, {ogrenci_adi} icin {paket_adi} paketiniz {tarih} tarihinde bitiyor. Yenileme ucreti: {odeme_tutari}. {kurum_adi}', 1, 0, 'Paket yenileme bilgilendirmesi.'),
('manuel_sms', 'Manuel SMS', '{mesaj}', 1, 0, 'Manuel SMS metni.')
ON DUPLICATE KEY UPDATE
  baslik = VALUES(baslik),
  mesaj = VALUES(mesaj),
  aktif = VALUES(aktif),
  otomatik_gonderim = VALUES(otomatik_gonderim),
  aciklama = VALUES(aciklama);

INSERT IGNORE INTO rol_yetkileri (rol_id, yetki)
SELECT r.id, y.yetki
FROM roller r
JOIN (
  SELECT 'sms_goruntule' AS yetki UNION ALL SELECT 'sms_gonder' UNION ALL SELECT 'sms_toplu_gonder' UNION ALL
  SELECT 'sms_sablon_yonet' UNION ALL SELECT 'sms_tekrar_gonder' UNION ALL SELECT 'sms_ayar_yonet' UNION ALL SELECT 'sms_rapor_goruntule'
) y
WHERE r.kod = 'kurucu';

INSERT IGNORE INTO rol_yetkileri (rol_id, yetki)
SELECT r.id, y.yetki
FROM roller r
JOIN (
  SELECT 'sms_goruntule' AS yetki UNION ALL SELECT 'sms_gonder' UNION ALL SELECT 'sms_toplu_gonder'
) y
WHERE r.kod = 'yonetici';

INSERT IGNORE INTO rol_yetkileri (rol_id, yetki)
SELECT r.id, y.yetki
FROM roller r
JOIN (
  SELECT 'sms_goruntule' AS yetki UNION ALL SELECT 'sms_gonder'
) y
WHERE r.kod IN ('muhasebe', 'resepsiyon');
