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
