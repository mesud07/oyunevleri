SET NAMES utf8mb4;

ALTER TABLE kurum_entegrasyonlari
  ADD COLUMN firma_unvani VARCHAR(500) NULL AFTER tenant_identifier_number,
  ADD COLUMN vergi_dairesi VARCHAR(190) NULL AFTER firma_unvani,
  ADD COLUMN adres TEXT NULL AFTER vergi_dairesi,
  ADD COLUMN il VARCHAR(100) NULL AFTER adres,
  ADD COLUMN ilce VARCHAR(100) NULL AFTER il;

ALTER TABLE faturalar
  MODIFY kaynak ENUM('application','mysoft','nes') NOT NULL DEFAULT 'application',
  ADD COLUMN provider VARCHAR(40) NULL AFTER entegrasyon_id,
  ADD COLUMN provider_invoice_id VARCHAR(190) NULL AFTER mysoft_invoice_id,
  ADD COLUMN provider_durum VARCHAR(100) NULL AFTER mysoft_durum,
  ADD COLUMN fallback_kullanildi TINYINT(1) NOT NULL DEFAULT 0 AFTER provider_durum;

UPDATE faturalar f
LEFT JOIN kurum_entegrasyonlari k ON k.id=f.entegrasyon_id
SET f.provider=COALESCE(k.provider, IF(f.kaynak='nes','nes','mysoft'))
WHERE f.provider IS NULL;

CREATE TABLE IF NOT EXISTS fatura_entegrasyon_denemeleri (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kurum_id BIGINT UNSIGNED NOT NULL,
  odeme_id BIGINT UNSIGNED NOT NULL,
  provider VARCHAR(40) NOT NULL,
  basarili TINYINT(1) NOT NULL DEFAULT 0,
  http_durumu SMALLINT NULL,
  hata_kodu VARCHAR(100) NULL,
  hata_mesaji TEXT NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_fatura_deneme (kurum_id, odeme_id, id),
  CONSTRAINT fk_fatura_deneme_kurum FOREIGN KEY (kurum_id) REFERENCES kurumlar(id) ON DELETE CASCADE,
  CONSTRAINT fk_fatura_deneme_odeme FOREIGN KEY (odeme_id) REFERENCES odemeler(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
