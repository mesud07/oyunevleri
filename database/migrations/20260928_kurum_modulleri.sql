CREATE TABLE IF NOT EXISTS `kurum_modulleri` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kurum_id` bigint(20) unsigned NOT NULL,
  `modul` varchar(50) NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kurum_modulleri_kurum_modul` (`kurum_id`,`modul`),
  KEY `idx_kurum_modulleri_aktif` (`kurum_id`,`aktif`),
  CONSTRAINT `fk_kurum_modulleri_kurum` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `kurum_modulleri` (`kurum_id`, `modul`, `aktif`)
SELECT k.id, m.modul, 1
FROM kurumlar k
CROSS JOIN (
  SELECT 'ogrenci_islemleri' AS modul
  UNION ALL SELECT 'program'
  UNION ALL SELECT 'finans'
  UNION ALL SELECT 'icerik_takip'
  UNION ALL SELECT 'sms'
  UNION ALL SELECT 'yonetim'
) m;
