-- Oyunevleri.com - Ortak Kurum Veritabani Semasi (tek DB, kurum_id ile ayrisim)
-- Charset/Collation: utf8mb4 / utf8mb4_turkish_ci

CREATE DATABASE IF NOT EXISTS oyunev_kurum
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_turkish_ci;

USE oyunev_kurum;

CREATE TABLE IF NOT EXISTS subeler (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    sube_adi VARCHAR(255),
    sehir VARCHAR(100),
    ilce VARCHAR(100),
    adres TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS kurum_alanlari (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    alan_adi VARCHAR(255),
    kapasite INT,
    aciklama TEXT,
    durum TINYINT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS adaylar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    sube_id INT NULL,
    ad_soyad VARCHAR(255),
    telefon VARCHAR(20),
    eposta VARCHAR(100),
    yas_ay INT NULL,
    notlar TEXT,
    durum ENUM('aday','donustu','kayip') DEFAULT 'aday',
    veli_id INT NULL,
    ogrenci_id INT NULL,
    kayit_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sube_id) REFERENCES subeler(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS veliler (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    sube_id INT,
    ad_soyad VARCHAR(255),
    telefon VARCHAR(20),
    eposta VARCHAR(100),
    sifre VARCHAR(255),
    google_sub VARCHAR(100) NULL,
    google_email VARCHAR(100) NULL,
    bakiye_hak INT DEFAULT 0,
    hak_gecerlilik_bitis DATE NULL,
    hak_donduruldu TINYINT DEFAULT 0,
    FOREIGN KEY (sube_id) REFERENCES subeler(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS veli_hak_hareketleri (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    veli_id INT,
    islem_tipi ENUM('ekleme','kullanim','iade'),
    miktar INT,
    aciklama TEXT,
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (veli_id) REFERENCES veliler(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS veli_hak_dondurma (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    veli_id INT,
    baslangic_tarihi DATE,
    bitis_tarihi DATE,
    durum ENUM('aktif','pasif') DEFAULT 'aktif',
    aciklama TEXT,
    islem_yapan_id INT,
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (veli_id) REFERENCES veliler(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS veli_borclar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    veli_id INT,
    hak_hareket_id INT NULL,
    hak_miktar INT,
    tutar DECIMAL(10,2),
    son_odeme_tarihi DATE,
    durum ENUM('beklemede','odendi','iptal') DEFAULT 'beklemede',
    aciklama TEXT,
    olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    odeme_tarihi DATETIME NULL,
    odeme_yontemi ENUM('nakit','kredi_karti','havale') NULL,
    tahsil_tutar DECIMAL(10,2) NULL,
    FOREIGN KEY (veli_id) REFERENCES veliler(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS sistem_ayarlar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    anahtar VARCHAR(100),
    deger VARCHAR(255),
    aciklama TEXT,
    guncelleme DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_kurum_anahtar (kurum_id, anahtar)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS ogrenciler (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    veli_id INT,
    ad_soyad VARCHAR(255),
    dogum_tarihi DATE,
    saglik_notlari TEXT,
    FOREIGN KEY (veli_id) REFERENCES veliler(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS oyun_gruplari (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    sube_id INT,
    alan_id INT NULL,
    grup_adi VARCHAR(255),
    min_ay INT,
    max_ay INT,
    kapasite INT,
    tekrar_tipi ENUM('tekil','haftalik') DEFAULT 'tekil',
    tekrar_gunleri SET('Pzt','Sal','Car','Per','Cum','Cmt','Paz') NULL,
    seans_baslangic_saati TIME NULL,
    seans_suresi_dk INT NULL,
    baslangic_tarihi DATE NULL,
    bitis_tarihi DATE NULL,
    FOREIGN KEY (sube_id) REFERENCES subeler(id),
    FOREIGN KEY (alan_id) REFERENCES kurum_alanlari(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS seanslar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    grup_id INT,
    seans_baslangic DATETIME,
    seans_bitis DATETIME,
    kontenjan INT,
    durum ENUM('aktif','iptal') DEFAULT 'aktif',
    FOREIGN KEY (grup_id) REFERENCES oyun_gruplari(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS rezervasyonlar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    ogrenci_id INT,
    seans_id INT,
    islem_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    durum ENUM('onayli', 'iptal', 'hak_yandi'),
    iptal_onay TINYINT DEFAULT 0,
    iptal_sebebi TEXT NULL,
    FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id),
    FOREIGN KEY (seans_id) REFERENCES seanslar(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS kasa_hareketleri (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    sube_id INT,
    veli_id INT NULL,
    islem_tipi ENUM('gelir', 'gider'),
    kategori VARCHAR(100) NULL,
    odeme_yontemi ENUM('nakit', 'kredi_karti', 'havale'),
    tutar DECIMAL(10,2),
    aciklama TEXT,
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS materyal_havuzu (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    materyal_adi VARCHAR(255),
    kazanimlar TEXT,
    materyal_dosya VARCHAR(255),
    yukleyen_kullanici_id INT,
    yukleme_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    veli_id INT,
    token_hash VARCHAR(64),
    expires_at DATETIME,
    used_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    FOREIGN KEY (veli_id) REFERENCES veliler(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
