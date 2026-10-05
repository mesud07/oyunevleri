CREATE TABLE IF NOT EXISTS bekleyen_veli_gorusmeleri (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kurum_id BIGINT UNSIGNED NOT NULL,
  bekleyen_veli_id BIGINT UNSIGNED NOT NULL,
  gorusme_tarihi DATETIME NOT NULL,
  kanal ENUM('telefon','whatsapp','yuz_yuze','sms','diger') NOT NULL DEFAULT 'telefon',
  ozet TEXT NOT NULL,
  sonuc ENUM('bilgi_verildi','tekrar_aranacak','randevu_planlandi','kararsiz','ulasilamadi','olumsuz','diger') NOT NULL DEFAULT 'bilgi_verildi',
  sonraki_takip_tarihi DATE NULL,
  olusturan_kullanici_id BIGINT UNSIGNED NULL,
  olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_bekleyen_veli_gorusme_tarihce (kurum_id, bekleyen_veli_id, gorusme_tarihi),
  KEY idx_bekleyen_veli_gorusme_takip (kurum_id, sonraki_takip_tarihi),
  CONSTRAINT fk_bekleyen_veli_gorusme_veli FOREIGN KEY (bekleyen_veli_id) REFERENCES bekleyen_veliler(id) ON DELETE CASCADE,
  CONSTRAINT fk_bekleyen_veli_gorusme_kurum FOREIGN KEY (kurum_id) REFERENCES kurumlar(id) ON DELETE CASCADE,
  CONSTRAINT fk_bekleyen_veli_gorusme_kullanici FOREIGN KEY (olusturan_kullanici_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
