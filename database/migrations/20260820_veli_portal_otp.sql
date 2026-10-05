CREATE TABLE IF NOT EXISTS veli_portal_dogrulamalari (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kurum_id BIGINT UNSIGNED NOT NULL,
    telefon_hash CHAR(64) NOT NULL,
    kod_hash VARCHAR(255) NOT NULL,
    deneme_sayisi TINYINT UNSIGNED NOT NULL DEFAULT 0,
    son_kullanim_tarihi DATETIME NOT NULL,
    dogrulanma_tarihi DATETIME NULL,
    olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_veli_portal_telefon (kurum_id, telefon_hash, olusturulma_tarihi),
    KEY idx_veli_portal_temizlik (olusturulma_tarihi),
    CONSTRAINT fk_veli_portal_dogrulama_kurum FOREIGN KEY (kurum_id) REFERENCES kurumlar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
