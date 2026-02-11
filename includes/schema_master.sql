-- Oyunevleri.com - Master Veritabani Semasi
-- Charset/Collation: utf8mb4 / utf8mb4_turkish_ci

CREATE DATABASE IF NOT EXISTS oyunev_master
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_turkish_ci;

USE oyunev_master;

CREATE TABLE IF NOT EXISTS kurumlar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_kodu VARCHAR(20) UNIQUE,
    kurum_adi VARCHAR(255),
    kurum_type VARCHAR(50),
    kurum_db_adi VARCHAR(100) DEFAULT 'oyunev_kurum',
    sehir VARCHAR(100),
    ilce VARCHAR(100),
    adres TEXT,
    hakkimizda TEXT,
    telefon VARCHAR(20),
    eposta VARCHAR(100),
    meb_onay TINYINT DEFAULT 0,
    aile_sosyal_onay TINYINT DEFAULT 0,
    hizmet_bahceli TINYINT DEFAULT 0,
    hizmet_guvenlik_kamerasi TINYINT DEFAULT 0,
    hizmet_ingilizce TINYINT DEFAULT 0,
    min_ay INT DEFAULT NULL,
    max_ay INT DEFAULT NULL,
    durum TINYINT DEFAULT 1,
    kayit_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS kullanicilar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    sube_id INT DEFAULT 0,
    kullanici_adi VARCHAR(50),
    sifre VARCHAR(255),
    yetki_seviyesi ENUM('merkez_admin', 'sube_admin', 'egitmen'),
    FOREIGN KEY (kurum_id) REFERENCES kurumlar(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS yetkiler (
    id INT PRIMARY KEY AUTO_INCREMENT,
    yetki_kodu VARCHAR(100) UNIQUE,
    yetki_adi VARCHAR(255),
    aciklama TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS roller (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    rol_adi VARCHAR(100),
    varsayilan TINYINT DEFAULT 0,
    aktif TINYINT DEFAULT 1,
    FOREIGN KEY (kurum_id) REFERENCES kurumlar(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS rol_yetkiler (
    rol_id INT,
    yetki_id INT,
    PRIMARY KEY (rol_id, yetki_id),
    FOREIGN KEY (rol_id) REFERENCES roller(id),
    FOREIGN KEY (yetki_id) REFERENCES yetkiler(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS kullanici_roller (
    kullanici_id INT,
    rol_id INT,
    PRIMARY KEY (kullanici_id, rol_id),
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id),
    FOREIGN KEY (rol_id) REFERENCES roller(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS kurum_galeri (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    gorsel_yol VARCHAR(255),
    sira INT DEFAULT 0,
    FOREIGN KEY (kurum_id) REFERENCES kurumlar(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS kurum_egitmenler (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    ad_soyad VARCHAR(255),
    uzmanlik VARCHAR(255),
    biyografi TEXT,
    fotograf_yol VARCHAR(255),
    FOREIGN KEY (kurum_id) REFERENCES kurumlar(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS kurum_yorumlar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    veli_adi VARCHAR(255),
    puan TINYINT,
    yorum TEXT,
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kurum_id) REFERENCES kurumlar(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS kurum_fiyatlar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    paket_adi VARCHAR(255),
    aciklama TEXT,
    fiyat DECIMAL(10,2),
    birim ENUM('seans','aylik','paket') DEFAULT 'seans',
    FOREIGN KEY (kurum_id) REFERENCES kurumlar(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS kurum_iletisim_talepleri (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    ad_soyad VARCHAR(255),
    telefon VARCHAR(20),
    mesaj TEXT,
    kaynak VARCHAR(50) DEFAULT 'web',
    ip_adresi VARCHAR(45),
    sayfa_url VARCHAR(255),
    durum ENUM('yeni','okundu','kapali') DEFAULT 'yeni',
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kurum_id) REFERENCES kurumlar(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
