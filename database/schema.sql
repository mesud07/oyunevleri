SET NAMES utf8mb4;
SET time_zone = '+03:00';

CREATE TABLE roller (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kod VARCHAR(50) NOT NULL UNIQUE,
  ad VARCHAR(100) NOT NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rol_yetkileri (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rol_id BIGINT UNSIGNED NOT NULL,
  yetki VARCHAR(100) NOT NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY rol_yetki_unique (rol_id, yetki),
  CONSTRAINT fk_rol_yetkileri_rol FOREIGN KEY (rol_id) REFERENCES roller(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE kullanicilar (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rol_id BIGINT UNSIGNED NOT NULL,
  ad VARCHAR(100) NOT NULL,
  soyad VARCHAR(100) NOT NULL,
  eposta VARCHAR(190) NOT NULL UNIQUE,
  telefon VARCHAR(40) NULL,
  sifre VARCHAR(255) NOT NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  son_giris_tarihi DATETIME NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_kullanicilar_rol FOREIGN KEY (rol_id) REFERENCES roller(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE veliler (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ad VARCHAR(100) NOT NULL,
  soyad VARCHAR(100) NOT NULL,
  tc_kimlik_no VARCHAR(20) NULL,
  telefon_ulke VARCHAR(80) NULL,
  telefon VARCHAR(40) NOT NULL,
  yedek_telefon VARCHAR(40) NULL,
  eposta VARCHAR(190) NULL,
  yakinlik VARCHAR(50) NULL,
  il VARCHAR(100) NULL,
  ilce VARCHAR(100) NULL,
  adres TEXT NULL,
  iletisim_referansi VARCHAR(190) NULL,
  notlar TEXT NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bekleyen_veliler (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ogrenci_id BIGINT UNSIGNED NULL,
  ogrenci_ad_soyad VARCHAR(160) NOT NULL,
  ogrenci_dogum_tarihi DATE NULL,
  veli_ad_soyad VARCHAR(160) NOT NULL,
  veli_telefon VARCHAR(32) NOT NULL,
  veli_eposta VARCHAR(160) NULL,
  beklenen_gun VARCHAR(20) NULL,
  ay_grubu VARCHAR(80) NULL,
  iletisim_referansi VARCHAR(190) NULL,
  zaman_tercihi ENUM('hafta_ici','hafta_sonu','farketmez') NOT NULL DEFAULT 'farketmez',
  durum ENUM('bekliyor','iletisime_gecildi','kayda_donustu','iptal') NOT NULL DEFAULT 'bekliyor',
  notlar TEXT NULL,
  olusturan_kullanici_id BIGINT UNSIGNED NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncellenme_tarihi DATETIME NULL,
  INDEX idx_bekleyen_veliler_ogrenci (ogrenci_id),
  INDEX idx_bekleyen_veliler_durum (durum),
  INDEX idx_bekleyen_veliler_telefon (veli_telefon),
  INDEX idx_bekleyen_veliler_gun (beklenen_gun),
  CONSTRAINT fk_bekleyen_veliler_kullanici FOREIGN KEY (olusturan_kullanici_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ogrenciler (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ad VARCHAR(100) NOT NULL,
  soyad VARCHAR(100) NOT NULL,
  tc_kimlik_no VARCHAR(20) NULL,
  dogum_tarihi DATE NULL,
  cinsiyet ENUM('kiz','erkek','belirtilmedi') NOT NULL DEFAULT 'belirtilmedi',
  fotograf VARCHAR(255) NULL,
  kayit_tarihi DATE NOT NULL,
  durum ENUM('aktif','pasif') NOT NULL DEFAULT 'aktif',
  acil_durum_kisi VARCHAR(190) NULL,
  acil_durum_telefon VARCHAR(40) NULL,
  saglik_bilgisi TEXT NULL,
  alerji_bilgisi TEXT NULL,
  ozel_durum_notu TEXT NULL,
  vasi_ad_soyad VARCHAR(190) NULL,
  vasi_tc_kimlik_no VARCHAR(20) NULL,
  vasi_telefon VARCHAR(40) NULL,
  yonetici_notu TEXT NULL,
  ogretmen_notu TEXT NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ogrenci_velileri (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ogrenci_id BIGINT UNSIGNED NOT NULL,
  veli_id BIGINT UNSIGNED NOT NULL,
  birincil_mi TINYINT(1) NOT NULL DEFAULT 0,
  acil_durum_mu TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY ogrenci_veli_unique (ogrenci_id, veli_id),
  CONSTRAINT fk_ogrenci_velileri_ogrenci FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id) ON DELETE CASCADE,
  CONSTRAINT fk_ogrenci_velileri_veli FOREIGN KEY (veli_id) REFERENCES veliler(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ogrenci_kara_liste (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ogrenci_id BIGINT UNSIGNED NOT NULL,
    kategori VARCHAR(80) NOT NULL,
    sebep TEXT NOT NULL,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    olusturan_kullanici_id BIGINT UNSIGNED NULL,
    olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    kaldirilma_tarihi DATETIME NULL,
    KEY idx_ogrenci_kara_liste_ogrenci (ogrenci_id),
    KEY idx_ogrenci_kara_liste_aktif (aktif),
    KEY idx_ogrenci_kara_liste_kategori (kategori),
    CONSTRAINT fk_ogrenci_kara_liste_ogrenci
        FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ogrenci_kara_liste_kullanici
        FOREIGN KEY (olusturan_kullanici_id) REFERENCES kullanicilar (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE gruplar (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ad VARCHAR(150) NOT NULL,
  yas_araligi VARCHAR(100) NULL,
  kontenjan INT UNSIGNED NOT NULL DEFAULT 8,
  ogretmen_id BIGINT UNSIGNED NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  durum VARCHAR(50) NULL DEFAULT 'durum_yok',
  aciklama TEXT NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_gruplar_ogretmen FOREIGN KEY (ogretmen_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE grup_ogrencileri (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  grup_id BIGINT UNSIGNED NOT NULL,
  ogrenci_id BIGINT UNSIGNED NOT NULL,
  baslangic_tarihi DATE NOT NULL,
  bitis_tarihi DATE NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY grup_ogrenci_aktif_unique (grup_id, ogrenci_id, aktif),
  CONSTRAINT fk_grup_ogrencileri_grup FOREIGN KEY (grup_id) REFERENCES gruplar(id) ON DELETE RESTRICT,
  CONSTRAINT fk_grup_ogrencileri_ogrenci FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ders_programlari (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  grup_id BIGINT UNSIGNED NOT NULL,
  gun TINYINT UNSIGNED NOT NULL,
  baslangic_saati TIME NOT NULL,
  bitis_saati TIME NOT NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_ders_programlari_grup FOREIGN KEY (grup_id) REFERENCES gruplar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hizmetler (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hizmet_adi VARCHAR(190) NOT NULL,
  ucret DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  haftalik_katilim_sayisi TINYINT UNSIGNED NOT NULL DEFAULT 1,
  toplam_normal_hak INT UNSIGNED NOT NULL DEFAULT 4,
  toplam_telafi_hak INT UNSIGNED NOT NULL DEFAULT 1,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE paketler (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ogrenci_id BIGINT UNSIGNED NOT NULL,
  paket_sira_no INT UNSIGNED NOT NULL,
  paket_adi VARCHAR(150) NOT NULL,
  haftalik_katilim_sayisi TINYINT UNSIGNED NOT NULL,
  toplam_normal_hak INT UNSIGNED NOT NULL,
  toplam_telafi_hak INT UNSIGNED NOT NULL,
  kullanilan_normal_hak INT UNSIGNED NOT NULL DEFAULT 0,
  kullanilan_telafi_hak INT UNSIGNED NOT NULL DEFAULT 0,
  kalan_normal_hak INT UNSIGNED NOT NULL,
  kalan_telafi_hak INT UNSIGNED NOT NULL,
  baslangic_tarihi DATE NOT NULL,
  tahmini_son_ders_tarihi DATE NULL,
  liste_fiyati DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  indirim_turu VARCHAR(80) NULL,
  indirim_tutari DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  indirim_aciklama TEXT NULL,
  net_paket_tutari DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  paket_durumu ENUM('aktif','tamamlandi','iptal') NOT NULL DEFAULT 'aktif',
  yenileme_durumu ENUM('belirsiz','yenilenecek','yenilenmeyecek') NOT NULL DEFAULT 'belirsiz',
  yonetici_notu TEXT NULL,
  olusturan_kullanici_id BIGINT UNSIGNED NOT NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY ogrenci_paket_sira_unique (ogrenci_id, paket_sira_no),
  CONSTRAINT fk_paketler_ogrenci FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id) ON DELETE RESTRICT,
  CONSTRAINT fk_paketler_kullanici FOREIGN KEY (olusturan_kullanici_id) REFERENCES kullanicilar(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE kasalar (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ad VARCHAR(150) NOT NULL,
  tur ENUM('nakit','banka','altin','diger') NOT NULL DEFAULT 'nakit',
  para_birimi VARCHAR(10) NOT NULL DEFAULT 'TRY',
  acilis_bakiyesi DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  aciklama VARCHAR(255) NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  olusturan_kullanici_id BIGINT UNSIGNED NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_kasalar_aktif (aktif),
  INDEX idx_kasalar_tur (tur),
  CONSTRAINT fk_kasalar_kullanici FOREIGN KEY (olusturan_kullanici_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE kasa_hareketleri (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kasa_id BIGINT UNSIGNED NOT NULL,
  tarih DATE NOT NULL,
  tur ENUM('giris','cikis') NOT NULL DEFAULT 'giris',
  tutar DECIMAL(14,2) NOT NULL,
  aciklama VARCHAR(255) NULL,
  olusturan_kullanici_id BIGINT UNSIGNED NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_kasa_hareketleri_kasa (kasa_id),
  INDEX idx_kasa_hareketleri_tarih (tarih),
  CONSTRAINT fk_kasa_hareketleri_kasa FOREIGN KEY (kasa_id) REFERENCES kasalar(id) ON DELETE CASCADE,
  CONSTRAINT fk_kasa_hareketleri_kullanici FOREIGN KEY (olusturan_kullanici_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE odemeler (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ogrenci_id BIGINT UNSIGNED NOT NULL,
  veli_id BIGINT UNSIGNED NULL,
  paket_id BIGINT UNSIGNED NOT NULL,
  tarih DATE NOT NULL,
  tutar DECIMAL(12,2) NOT NULL,
  yontem ENUM('nakit','kredi_karti','havale_eft','odeme_baglantisi','diger') NOT NULL,
  kasa_id BIGINT UNSIGNED NULL,
  makbuz_numarasi VARCHAR(100) NULL,
  aciklama TEXT NULL,
  alan_kullanici_id BIGINT UNSIGNED NOT NULL,
  iptal TINYINT(1) NOT NULL DEFAULT 0,
  iptal_nedeni TEXT NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_odemeler_ogrenci FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id) ON DELETE RESTRICT,
  CONSTRAINT fk_odemeler_veli FOREIGN KEY (veli_id) REFERENCES veliler(id) ON DELETE SET NULL,
  CONSTRAINT fk_odemeler_paket FOREIGN KEY (paket_id) REFERENCES paketler(id) ON DELETE RESTRICT,
  CONSTRAINT fk_odemeler_kasa FOREIGN KEY (kasa_id) REFERENCES kasalar(id) ON DELETE SET NULL,
  CONSTRAINT fk_odemeler_kullanici FOREIGN KEY (alan_kullanici_id) REFERENCES kullanicilar(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE odeme_sozleri (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ogrenci_id BIGINT UNSIGNED NOT NULL,
  veli_id BIGINT UNSIGNED NULL,
  paket_id BIGINT UNSIGNED NOT NULL,
  soz_verilen_tutar DECIMAL(12,2) NOT NULL,
  soz_verilen_tarih DATE NOT NULL,
  hatirlatma_tarihi DATE NULL,
  durum ENUM('bekleniyor','bugun_odenecek','odendi','gecikti','yeni_tarih_verildi','iptal_edildi') NOT NULL DEFAULT 'bekleniyor',
  aciklama TEXT NULL,
  onceki_tarih DATE NULL,
  yeni_tarih DATE NULL,
  odeme_id BIGINT UNSIGNED NULL,
  olusturan_kullanici_id BIGINT UNSIGNED NOT NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_odeme_sozleri_ogrenci FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id) ON DELETE RESTRICT,
  CONSTRAINT fk_odeme_sozleri_veli FOREIGN KEY (veli_id) REFERENCES veliler(id) ON DELETE SET NULL,
  CONSTRAINT fk_odeme_sozleri_paket FOREIGN KEY (paket_id) REFERENCES paketler(id) ON DELETE RESTRICT,
  CONSTRAINT fk_odeme_sozleri_odeme FOREIGN KEY (odeme_id) REFERENCES odemeler(id) ON DELETE SET NULL,
  CONSTRAINT fk_odeme_sozleri_kullanici FOREIGN KEY (olusturan_kullanici_id) REFERENCES kullanicilar(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE giderler (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tarih DATE NOT NULL,
  tedarikci VARCHAR(150) NOT NULL,
  kategori VARCHAR(120) NULL,
  aciklama VARCHAR(255) NULL,
  tutar DECIMAL(12,2) NOT NULL,
  odeme_turu ENUM('nakit','kredi_karti','banka_havalesi','otomatik_odeme','diger') NOT NULL DEFAULT 'nakit',
  kasa_id BIGINT UNSIGNED NULL,
  durum ENUM('planlandi','odendi','iptal') NOT NULL DEFAULT 'planlandi',
  odeme_tarihi DATE NULL,
  olusturan_kullanici_id BIGINT UNSIGNED NOT NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_giderler_kasa FOREIGN KEY (kasa_id) REFERENCES kasalar(id) ON DELETE SET NULL,
  CONSTRAINT fk_giderler_kullanici FOREIGN KEY (olusturan_kullanici_id) REFERENCES kullanicilar(id) ON DELETE RESTRICT,
  INDEX idx_giderler_tarih (tarih),
  INDEX idx_giderler_durum (durum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE paket_disi_haklar (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ogrenci_id BIGINT UNSIGNED NOT NULL,
  hak_turu VARCHAR(80) NOT NULL,
  toplam_hak INT UNSIGNED NOT NULL,
  kullanilan_hak INT UNSIGNED NOT NULL DEFAULT 0,
  kalan_hak INT UNSIGNED NOT NULL,
  baslangic_tarihi DATE NOT NULL,
  son_kullanim_tarihi DATE NULL,
  grup_id BIGINT UNSIGNED NULL,
  ogretmen_id BIGINT UNSIGNED NULL,
  ucretli_mi TINYINT(1) NOT NULL DEFAULT 0,
  tutar DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  aciklama TEXT NULL,
  durum ENUM('aktif','tamamlandi','iptal') NOT NULL DEFAULT 'aktif',
  CONSTRAINT fk_paket_disi_haklar_ogrenci FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id) ON DELETE RESTRICT,
  CONSTRAINT fk_paket_disi_haklar_grup FOREIGN KEY (grup_id) REFERENCES gruplar(id) ON DELETE SET NULL,
  CONSTRAINT fk_paket_disi_haklar_ogretmen FOREIGN KEY (ogretmen_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE telafi_haklari (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ogrenci_id BIGINT UNSIGNED NOT NULL,
  paket_id BIGINT UNSIGNED NULL,
  kaynak_randevu_id BIGINT UNSIGNED NULL,
  durum ENUM('planlanmayi_bekliyor','planlandi','kullanildi','iptal') NOT NULL DEFAULT 'planlanmayi_bekliyor',
  son_kullanim_tarihi DATE NULL,
  aciklama TEXT NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_telafi_haklari_ogrenci FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id) ON DELETE RESTRICT,
  CONSTRAINT fk_telafi_haklari_paket FOREIGN KEY (paket_id) REFERENCES paketler(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE randevular (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ogrenci_id BIGINT UNSIGNED NOT NULL,
  veli_id BIGINT UNSIGNED NULL,
  grup_id BIGINT UNSIGNED NULL,
  paket_id BIGINT UNSIGNED NULL,
  paket_disi_hak_id BIGINT UNSIGNED NULL,
  telafi_hakki_id BIGINT UNSIGNED NULL,
  ogretmen_id BIGINT UNSIGNED NULL,
  tarih DATE NOT NULL,
  baslangic_saati TIME NOT NULL,
  bitis_saati TIME NOT NULL,
  tur VARCHAR(80) NOT NULL,
  hak_kaynagi VARCHAR(80) NOT NULL,
  durum ENUM('planlandi','geldi','gelmedi','mazeretli_gelmedi','gec_iptal','kurum_iptali','ertelendi','tamamlandi') NOT NULL DEFAULT 'planlandi',
  otomatik_gelmedi_islendi TINYINT(1) NOT NULL DEFAULT 0,
  aciklama TEXT NULL,
  olusturan_kullanici_id BIGINT UNSIGNED NOT NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_randevular_ogrenci FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id) ON DELETE RESTRICT,
  CONSTRAINT fk_randevular_veli FOREIGN KEY (veli_id) REFERENCES veliler(id) ON DELETE SET NULL,
  CONSTRAINT fk_randevular_grup FOREIGN KEY (grup_id) REFERENCES gruplar(id) ON DELETE SET NULL,
  CONSTRAINT fk_randevular_paket FOREIGN KEY (paket_id) REFERENCES paketler(id) ON DELETE RESTRICT,
  CONSTRAINT fk_randevular_paket_disi FOREIGN KEY (paket_disi_hak_id) REFERENCES paket_disi_haklar(id) ON DELETE RESTRICT,
  CONSTRAINT fk_randevular_telafi FOREIGN KEY (telafi_hakki_id) REFERENCES telafi_haklari(id) ON DELETE RESTRICT,
  CONSTRAINT fk_randevular_ogretmen FOREIGN KEY (ogretmen_id) REFERENCES kullanicilar(id) ON DELETE SET NULL,
  CONSTRAINT fk_randevular_kullanici FOREIGN KEY (olusturan_kullanici_id) REFERENCES kullanicilar(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE telafi_haklari
  ADD CONSTRAINT fk_telafi_haklari_kaynak_randevu FOREIGN KEY (kaynak_randevu_id) REFERENCES randevular(id) ON DELETE SET NULL;

CREATE TABLE yoklamalar (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  randevu_id BIGINT UNSIGNED NOT NULL,
  ogrenci_id BIGINT UNSIGNED NOT NULL,
  durum VARCHAR(80) NOT NULL,
  notlar TEXT NULL,
  kaydeden_kullanici_id BIGINT UNSIGNED NOT NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_yoklamalar_randevu FOREIGN KEY (randevu_id) REFERENCES randevular(id) ON DELETE RESTRICT,
  CONSTRAINT fk_yoklamalar_ogrenci FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id) ON DELETE RESTRICT,
  CONSTRAINT fk_yoklamalar_kullanici FOREIGN KEY (kaydeden_kullanici_id) REFERENCES kullanicilar(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hak_hareketleri (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ogrenci_id BIGINT UNSIGNED NOT NULL,
  paket_id BIGINT UNSIGNED NULL,
  randevu_id BIGINT UNSIGNED NULL,
  hareket_turu VARCHAR(80) NOT NULL,
  hak_turu VARCHAR(80) NOT NULL,
  miktar INT NOT NULL,
  onceki_kalan INT NOT NULL,
  sonraki_kalan INT NOT NULL,
  aciklama TEXT NULL,
  olusturan_kullanici_id BIGINT UNSIGNED NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_hak_hareketleri_ogrenci FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id) ON DELETE RESTRICT,
  CONSTRAINT fk_hak_hareketleri_paket FOREIGN KEY (paket_id) REFERENCES paketler(id) ON DELETE RESTRICT,
  CONSTRAINT fk_hak_hareketleri_randevu FOREIGN KEY (randevu_id) REFERENCES randevular(id) ON DELETE SET NULL,
  CONSTRAINT fk_hak_hareketleri_kullanici FOREIGN KEY (olusturan_kullanici_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE telafi_onerileri (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  telafi_hakki_id BIGINT UNSIGNED NOT NULL,
  onerilen_tarih DATE NOT NULL,
  baslangic_saati TIME NOT NULL,
  bitis_saati TIME NOT NULL,
  grup_id BIGINT UNSIGNED NULL,
  ogretmen_id BIGINT UNSIGNED NULL,
  durum ENUM('onerildi','kabul_edildi','reddedildi','iptal') NOT NULL DEFAULT 'onerildi',
  aciklama TEXT NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_telafi_onerileri_telafi FOREIGN KEY (telafi_hakki_id) REFERENCES telafi_haklari(id) ON DELETE RESTRICT,
  CONSTRAINT fk_telafi_onerileri_grup FOREIGN KEY (grup_id) REFERENCES gruplar(id) ON DELETE SET NULL,
  CONSTRAINT fk_telafi_onerileri_ogretmen FOREIGN KEY (ogretmen_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bildirimler (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kullanici_id BIGINT UNSIGNED NULL,
  baslik VARCHAR(190) NOT NULL,
  mesaj TEXT NOT NULL,
  okundu TINYINT(1) NOT NULL DEFAULT 0,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_bildirimler_kullanici FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE islem_kayitlari (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kullanici_id BIGINT UNSIGNED NULL,
  islem VARCHAR(120) NOT NULL,
  aciklama TEXT NOT NULL,
  veri JSON NULL,
  ip_adresi VARCHAR(45) NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_islem_kayitlari_kullanici FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ayarlar (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  anahtar VARCHAR(120) NOT NULL UNIQUE,
  deger TEXT NULL,
  aciklama TEXT NULL,
  guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE gunluk_notlar (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ogrenci_id BIGINT UNSIGNED NOT NULL,
  randevu_id BIGINT UNSIGNED NULL,
  tarih DATE NOT NULL,
  kategori VARCHAR(80) NOT NULL DEFAULT 'Genel',
  not_metni TEXT NOT NULL,
  olusturan_kullanici_id BIGINT UNSIGNED NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_gunluk_notlar_tarih (tarih),
  INDEX idx_gunluk_notlar_ogrenci (ogrenci_id, tarih),
  INDEX idx_gunluk_notlar_randevu (randevu_id),
  CONSTRAINT fk_gunluk_notlar_ogrenci FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id) ON DELETE CASCADE,
  CONSTRAINT fk_gunluk_notlar_randevu FOREIGN KEY (randevu_id) REFERENCES randevular(id) ON DELETE SET NULL,
  CONSTRAINT fk_gunluk_notlar_kullanici FOREIGN KEY (olusturan_kullanici_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roller (kod, ad) VALUES
('kurucu', 'Kurucu'),
('yonetici', 'Yonetici'),
('ogretmen', 'Ogretmen'),
('muhasebe', 'Muhasebe'),
('resepsiyon', 'Resepsiyon');

INSERT INTO rol_yetkileri (rol_id, yetki)
SELECT r.id, y.yetki
FROM roller r
JOIN (
  SELECT 'ogrenci_listele' AS yetki UNION ALL
  SELECT 'ogrenci_ekle' UNION ALL
  SELECT 'bekleyen_veli_listele' UNION ALL
  SELECT 'bekleyen_veli_ekle' UNION ALL
  SELECT 'veli_listele' UNION ALL
  SELECT 'veli_ekle' UNION ALL
  SELECT 'grup_listele' UNION ALL
  SELECT 'grup_ekle' UNION ALL
  SELECT 'paket_listele' UNION ALL
  SELECT 'odeme_listele' UNION ALL
  SELECT 'randevu_listele' UNION ALL
  SELECT 'randevu_durum_degistir' UNION ALL
  SELECT 'yoklama_listele' UNION ALL
  SELECT 'rapor_ozet' UNION ALL
  SELECT 'sms_goruntule' UNION ALL
  SELECT 'sms_gonder' UNION ALL
  SELECT 'sms_toplu_gonder' UNION ALL
  SELECT 'sms_sablon_yonet' UNION ALL
  SELECT 'sms_tekrar_gonder' UNION ALL
  SELECT 'sms_ayar_yonet' UNION ALL
  SELECT 'sms_rapor_goruntule' UNION ALL
  SELECT 'tema_yonet' UNION ALL
  SELECT 'kullanici_yonet'
) y
WHERE r.kod = 'kurucu';

INSERT INTO rol_yetkileri (rol_id, yetki)
SELECT r.id, y.yetki FROM roller r JOIN (
  SELECT 'ogrenci_listele' AS yetki UNION ALL SELECT 'ogrenci_ekle' UNION ALL SELECT 'bekleyen_veli_listele' UNION ALL SELECT 'bekleyen_veli_ekle' UNION ALL SELECT 'veli_listele' UNION ALL SELECT 'veli_ekle' UNION ALL SELECT 'grup_listele' UNION ALL SELECT 'grup_ekle' UNION ALL SELECT 'paket_listele' UNION ALL SELECT 'odeme_listele' UNION ALL SELECT 'randevu_listele' UNION ALL SELECT 'randevu_durum_degistir' UNION ALL SELECT 'yoklama_listele' UNION ALL SELECT 'rapor_ozet' UNION ALL SELECT 'sms_goruntule' UNION ALL SELECT 'sms_gonder' UNION ALL SELECT 'sms_toplu_gonder' UNION ALL SELECT 'tema_yonet'
) y WHERE r.kod = 'yonetici';

INSERT INTO rol_yetkileri (rol_id, yetki)
SELECT r.id, y.yetki FROM roller r JOIN (
  SELECT 'ogrenci_listele' AS yetki UNION ALL SELECT 'grup_listele' UNION ALL SELECT 'randevu_listele' UNION ALL SELECT 'randevu_durum_degistir' UNION ALL SELECT 'yoklama_listele' UNION ALL SELECT 'tema_yonet'
) y WHERE r.kod = 'ogretmen';

INSERT INTO rol_yetkileri (rol_id, yetki)
SELECT r.id, y.yetki FROM roller r JOIN (
  SELECT 'paket_listele' AS yetki UNION ALL SELECT 'odeme_listele' UNION ALL SELECT 'rapor_ozet' UNION ALL SELECT 'sms_goruntule' UNION ALL SELECT 'sms_gonder'
) y WHERE r.kod = 'muhasebe';

INSERT INTO rol_yetkileri (rol_id, yetki)
SELECT r.id, y.yetki FROM roller r JOIN (
  SELECT 'ogrenci_listele' AS yetki UNION ALL SELECT 'ogrenci_ekle' UNION ALL SELECT 'bekleyen_veli_listele' UNION ALL SELECT 'bekleyen_veli_ekle' UNION ALL SELECT 'veli_listele' UNION ALL SELECT 'veli_ekle' UNION ALL SELECT 'grup_listele' UNION ALL SELECT 'randevu_listele' UNION ALL SELECT 'randevu_durum_degistir' UNION ALL SELECT 'sms_goruntule' UNION ALL SELECT 'sms_gonder'
) y WHERE r.kod = 'resepsiyon';

INSERT INTO kullanicilar (rol_id, ad, soyad, eposta, telefon, sifre, aktif)
SELECT id, 'Talya', 'Kurucu', 'admin', '05510000000', '$2y$12$4.pd6ePXI8Hrtvl352ddqej3YMl2Qk3FzLqbPoyPGv1cFGhNcbKke', 1
FROM roller
WHERE kod = 'kurucu';

INSERT INTO ayarlar (anahtar, deger, aciklama) VALUES
('otomatik_gelmedi_bekleme_dakika', '60', 'Planli randevular icin otomatik gelmedi bekleme suresi.'),
('kurum_adi', 'Oyun Evleri Yönetim Sistemi', 'Panelde kullanilan kurum adi.'),
('sms_appointment_reminder_enabled', '1', 'Randevu hatirlatma SMS otomasyonu aktiflik bilgisi.'),
('sms_appointment_reminder_days_before', '1', 'Randevudan kac gun once hatirlatma SMS kuyruga alinacak.'),
('sms_appointment_reminder_time', '14:00', 'Hatirlatma SMS kuyruga alma saati.'),
('sms_birthday_message_enabled', '1', 'Dogum gunu SMS otomasyonu aktiflik bilgisi.'),
('sms_birthday_message_time', '09:00', 'Dogum gunu SMS kuyruga alma saati.');
CREATE TABLE IF NOT EXISTS age_groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_age_groups_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS weekly_themes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    week_start DATE NOT NULL,
    week_end DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_weekly_themes_week_start (week_start),
    KEY idx_weekly_themes_week_end (week_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS weekly_theme_age_groups (
    theme_id BIGINT UNSIGNED NOT NULL,
    age_group_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (theme_id, age_group_id),
    CONSTRAINT fk_weekly_theme_age_groups_theme
        FOREIGN KEY (theme_id) REFERENCES weekly_themes (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_weekly_theme_age_groups_age
        FOREIGN KEY (age_group_id) REFERENCES age_groups (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_activity_templates_active (is_active),
    UNIQUE KEY uq_activity_templates_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS theme_activities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    theme_id BIGINT UNSIGNED NOT NULL,
    activity_template_id BIGINT UNSIGNED NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_theme_activities_theme (theme_id),
    KEY idx_theme_activities_template (activity_template_id),
    CONSTRAINT fk_theme_activities_theme
        FOREIGN KEY (theme_id) REFERENCES weekly_themes (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_theme_activities_template
        FOREIGN KEY (activity_template_id) REFERENCES activity_templates (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS theme_activity_groups (
    activity_id BIGINT UNSIGNED NOT NULL,
    group_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (activity_id, group_id),
    CONSTRAINT fk_theme_activity_groups_activity
        FOREIGN KEY (activity_id) REFERENCES theme_activities (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_theme_activity_groups_group
        FOREIGN KEY (group_id) REFERENCES gruplar (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS theme_group_presets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_theme_group_presets_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS theme_group_preset_groups (
    preset_id BIGINT UNSIGNED NOT NULL,
    group_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (preset_id, group_id),
    CONSTRAINT fk_theme_group_preset_groups_preset
        FOREIGN KEY (preset_id) REFERENCES theme_group_presets (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_theme_group_preset_groups_group
        FOREIGN KEY (group_id) REFERENCES gruplar (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_activity_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    activity_id BIGINT UNSIGNED NOT NULL,
    completed_at DATE NOT NULL,
    source_type VARCHAR(30) NOT NULL DEFAULT 'manual',
    randevu_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_student_activity_records_student_activity (student_id, activity_id),
    KEY idx_student_activity_records_completed (completed_at),
    KEY idx_student_activity_records_activity (activity_id),
    KEY idx_student_activity_records_randevu (randevu_id),
    KEY idx_student_activity_records_source (source_type),
    CONSTRAINT fk_student_activity_records_student
        FOREIGN KEY (student_id) REFERENCES ogrenciler (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_student_activity_records_activity
        FOREIGN KEY (activity_id) REFERENCES theme_activities (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_student_activity_records_randevu
        FOREIGN KEY (randevu_id) REFERENCES randevular (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO age_groups (name, sort_order, created_at, updated_at) VALUES
('18-24 Ay', 10, NOW(), NOW()),
('25-36 Ay', 20, NOW(), NOW()),
('37-48 Ay', 30, NOW(), NOW())
ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order), updated_at = NOW();

INSERT INTO activity_templates (title, description, is_active, created_at, updated_at) VALUES
('Parmak Boyasi', NULL, 1, NOW(), NOW()),
('Kesme ve Yapistirma', NULL, 1, NOW(), NOW()),
('Duyusal Oyun', NULL, 1, NOW(), NOW()),
('Renk Eslestirme', NULL, 1, NOW(), NOW()),
('Nesne Eslestirme', NULL, 1, NOW(), NOW()),
('Puzzle Calismasi', NULL, 1, NOW(), NOW()),
('Muzik ve Ritim', NULL, 1, NOW(), NOW()),
('Hikaye Zamani', NULL, 1, NOW(), NOW()),
('Blok Calismasi', NULL, 1, NOW(), NOW()),
('Serbest Boyama', NULL, 1, NOW(), NOW()),
('Renk Avi', NULL, 1, NOW(), NOW()),
('Doku Kesfi', NULL, 1, NOW(), NOW()),
('Buyuk-Kucuk Eslestirme', NULL, 1, NOW(), NOW()),
('Dolu-Bos Kavrami', NULL, 1, NOW(), NOW()),
('Ince Motor Calismasi', NULL, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE is_active = VALUES(is_active), updated_at = NOW();
