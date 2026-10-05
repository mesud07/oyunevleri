ALTER TABLE bekleyen_veliler
  MODIFY COLUMN durum ENUM(
    'bekliyor','iletisime_gecildi','bilgi_verildi','ulasilamadi','katilmadi','kayda_donustu','iptal'
  ) NOT NULL DEFAULT 'bekliyor';

ALTER TABLE bekleyen_veli_gorusmeleri
  MODIFY COLUMN sonuc ENUM(
    'bilgi_verildi','tekrar_aranacak','randevu_planlandi','kararsiz','ulasilamadi','katilmadi','olumsuz','diger'
  ) NOT NULL DEFAULT 'bilgi_verildi';

CREATE TABLE IF NOT EXISTS bekleyen_veli_gruplari (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kurum_id BIGINT UNSIGNED NOT NULL,
  bekleyen_veli_id BIGINT UNSIGNED NOT NULL,
  grup_id BIGINT UNSIGNED NOT NULL,
  olusturan_kullanici_id BIGINT UNSIGNED NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_bekleyen_veli_grubu (kurum_id, bekleyen_veli_id, grup_id),
  KEY idx_bekleyen_veli_grubu_grup (kurum_id, grup_id),
  CONSTRAINT fk_bekleyen_veli_grubu_veli FOREIGN KEY (bekleyen_veli_id) REFERENCES bekleyen_veliler(id) ON DELETE CASCADE,
  CONSTRAINT fk_bekleyen_veli_grubu_grup FOREIGN KEY (grup_id) REFERENCES gruplar(id) ON DELETE CASCADE,
  CONSTRAINT fk_bekleyen_veli_grubu_kurum FOREIGN KEY (kurum_id) REFERENCES kurumlar(id) ON DELETE CASCADE,
  CONSTRAINT fk_bekleyen_veli_grubu_kullanici FOREIGN KEY (olusturan_kullanici_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
