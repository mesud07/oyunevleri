CREATE TABLE IF NOT EXISTS kalici_oturumlar (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kurum_id BIGINT UNSIGNED NOT NULL,
    kullanici_id BIGINT UNSIGNED NOT NULL,
    secici CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    dogrulayici_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    oturum_surumu INT UNSIGNED NOT NULL,
    sona_erme_tarihi DATETIME NOT NULL,
    son_kullanim_tarihi DATETIME NULL,
    olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_kalici_oturum_secici (secici),
    KEY idx_kalici_oturum_kullanici (kullanici_id, sona_erme_tarihi),
    KEY idx_kalici_oturum_sona_erme (sona_erme_tarihi),
    CONSTRAINT fk_kalici_oturum_kurum FOREIGN KEY (kurum_id) REFERENCES kurumlar(id) ON DELETE CASCADE,
    CONSTRAINT fk_kalici_oturum_kullanici FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
