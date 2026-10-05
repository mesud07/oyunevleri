CREATE TABLE IF NOT EXISTS `kurum_modul_sayfalari` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kurum_id` bigint(20) unsigned NOT NULL,
  `sayfa` varchar(80) NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kurum_modul_sayfalari_kurum_sayfa` (`kurum_id`,`sayfa`),
  KEY `idx_kurum_modul_sayfalari_aktif` (`kurum_id`,`aktif`),
  CONSTRAINT `fk_kurum_modul_sayfalari_kurum` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `kurum_modul_sayfalari` (`kurum_id`, `sayfa`, `aktif`)
SELECT k.id, s.sayfa, 1
FROM kurumlar k
CROSS JOIN (
  SELECT 'ogrenciler' AS sayfa UNION ALL SELECT 'devamlilik_raporu'
  UNION ALL SELECT 'adres_haritasi' UNION ALL SELECT 'ozel_notlar'
  UNION ALL SELECT 'tedbir_listesi' UNION ALL SELECT 'bekleyen_veliler'
  UNION ALL SELECT 'veli_onamlari' UNION ALL SELECT 'randevular'
  UNION ALL SELECT 'haftalik_program' UNION ALL SELECT 'paketler'
  UNION ALL SELECT 'borclu_paketler' UNION ALL SELECT 'tahsilatlar'
  UNION ALL SELECT 'giderler' UNION ALL SELECT 'kasalar'
  UNION ALL SELECT 'cariler' UNION ALL SELECT 'faturalar'
  UNION ALL SELECT 'entegrasyon_ayarlari' UNION ALL SELECT 'gelir_gider'
  UNION ALL SELECT 'raporlar' UNION ALL SELECT 'haftalik_temalar'
  UNION ALL SELECT 'gunluk_kayitlar' UNION ALL SELECT 'sms_yonetimi'
  UNION ALL SELECT 'sms_raporlari' UNION ALL SELECT 'kullanicilar'
  UNION ALL SELECT 'personel_puantaj'
) s;
