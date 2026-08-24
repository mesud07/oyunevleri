CREATE TABLE IF NOT EXISTS ogrenci_onam_formlari (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kurum_id BIGINT UNSIGNED NOT NULL,
    ogrenci_id BIGINT UNSIGNED NOT NULL,
    veli_id BIGINT UNSIGNED NULL,
    sablon_kodu VARCHAR(80) NOT NULL DEFAULT 'gorsel_icerik_kullanim',
    form_adi VARCHAR(190) NOT NULL,
    belge_turu VARCHAR(30) NOT NULL DEFAULT 'fiziksel',
    durum VARCHAR(30) NOT NULL DEFAULT 'olusturuldu',
    ogrenci_ad_soyad VARCHAR(190) NOT NULL,
    ogrenci_tc_kimlik_no VARCHAR(20) NULL,
    ogrenci_dogum_tarihi DATE NULL,
    ogrenci_telefon VARCHAR(40) NULL,
    veli_ad_soyad VARCHAR(190) NOT NULL,
    veli_tc_kimlik_no VARCHAR(20) NULL,
    veli_yakinlik VARCHAR(80) NULL,
    personel_unvan VARCHAR(100) NOT NULL,
    personel_ad_soyad VARCHAR(190) NOT NULL,
    form_tarihi DATE NOT NULL,
    olusturan_kullanici_id BIGINT UNSIGNED NULL,
    olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_onam_formlari_ogrenci (kurum_id, ogrenci_id, olusturulma_tarihi),
    KEY idx_onam_formlari_sablon (kurum_id, sablon_kodu),
    CONSTRAINT fk_onam_formlari_kurum
        FOREIGN KEY (kurum_id) REFERENCES kurumlar (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_onam_formlari_ogrenci
        FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_onam_formlari_veli
        FOREIGN KEY (veli_id) REFERENCES veliler (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_onam_formlari_kullanici
        FOREIGN KEY (olusturan_kullanici_id) REFERENCES kullanicilar (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
