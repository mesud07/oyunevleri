CREATE TABLE IF NOT EXISTS ogrenci_gelisim_testleri (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kurum_id BIGINT UNSIGNED NOT NULL,
    ogrenci_id BIGINT UNSIGNED NOT NULL,
    uygulandi TINYINT(1) NOT NULL DEFAULT 0,
    uygulama_tarihi DATE NULL,
    notlar TEXT NULL,
    guncelleyen_kullanici_id BIGINT UNSIGNED NULL,
    olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    guncellenme_tarihi DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ogrenci_gelisim_testi (kurum_id, ogrenci_id),
    KEY idx_ogrenci_gelisim_testi_durum (kurum_id, uygulandi),
    CONSTRAINT fk_ogrenci_gelisim_testi_kurum
        FOREIGN KEY (kurum_id) REFERENCES kurumlar(id) ON DELETE CASCADE,
    CONSTRAINT fk_ogrenci_gelisim_testi_ogrenci
        FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id) ON DELETE CASCADE,
    CONSTRAINT fk_ogrenci_gelisim_testi_kullanici
        FOREIGN KEY (guncelleyen_kullanici_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
