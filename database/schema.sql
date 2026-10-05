-- Canlı şemadan üretilen temiz kurulum dosyası.
-- Müşteri, kullanıcı, ödeme ve entegrasyon sırrı içermez.

SET NAMES utf8mb4;
SET time_zone = '+03:00';
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `kurumlar` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ad` varchar(190) NOT NULL,
  `kod` varchar(80) NOT NULL,
  `logo_yolu` varchar(255) DEFAULT NULL,
  `veli_portal_anahtari` char(32) DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kurumlar_kod` (`kod`),
  UNIQUE KEY `uq_kurumlar_veli_portal_anahtari` (`veli_portal_anahtari`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `roller` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kod` varchar(50) NOT NULL,
  `ad` varchar(100) NOT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kod` (`kod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rol_yetkileri` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rol_id` bigint(20) unsigned NOT NULL,
  `yetki` varchar(100) NOT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `rol_yetki_unique` (`rol_id`,`yetki`),
  CONSTRAINT `fk_rol_yetkileri_rol` FOREIGN KEY (`rol_id`) REFERENCES `roller` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `age_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_age_groups_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `activity_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_activity_templates_kurum_title` (`kurum_id`,`title`),
  KEY `idx_activity_templates_active` (`is_active`),
  KEY `idx_activity_templates_kurum` (`kurum_id`),
  CONSTRAINT `fk_kurum_a94689c0867a1c852cde` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `adres_konum_onbellegi` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kurum_id` bigint(20) unsigned NOT NULL,
  `adres_anahtari` char(64) NOT NULL,
  `il` varchar(100) DEFAULT NULL,
  `ilce` varchar(100) DEFAULT NULL,
  `adres` text DEFAULT NULL,
  `enlem` decimal(10,7) NOT NULL,
  `boylam` decimal(10,7) NOT NULL,
  `saglayici` varchar(30) NOT NULL DEFAULT 'google',
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `son_kullanilma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_adres_konum_onbellegi` (`kurum_id`,`adres_anahtari`),
  KEY `idx_adres_konum_onbellegi_kurum` (`kurum_id`),
  CONSTRAINT `fk_adres_konum_onbellegi_kurum` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ayarlar` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `anahtar` varchar(120) NOT NULL,
  `deger` text DEFAULT NULL,
  `aciklama` text DEFAULT NULL,
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ayarlar_kurum_anahtar` (`kurum_id`,`anahtar`),
  KEY `idx_ayarlar_kurum` (`kurum_id`),
  CONSTRAINT `fk_kurum_0d467bdcad2fd865cf02` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bekleyen_veli_gorusmeleri` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kurum_id` bigint(20) unsigned NOT NULL,
  `bekleyen_veli_id` bigint(20) unsigned NOT NULL,
  `gorusme_tarihi` datetime NOT NULL,
  `kanal` enum('telefon','whatsapp','yuz_yuze','sms','diger') NOT NULL DEFAULT 'telefon',
  `ozet` text NOT NULL,
  `sonuc` enum('goruldu','bilgi_verildi','veli_donecek','tekrar_aranacak','kayit_istiyor','uygun_grup_yok','randevu_planlandi','kararsiz','ulasilamadi','katilmadi','olumsuz','diger') NOT NULL DEFAULT 'bilgi_verildi',
  `sonraki_takip_tarihi` date DEFAULT NULL,
  `olusturan_kullanici_id` bigint(20) unsigned DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_bekleyen_veli_gorusme_tarihce` (`kurum_id`,`bekleyen_veli_id`,`gorusme_tarihi`),
  KEY `idx_bekleyen_veli_gorusme_takip` (`kurum_id`,`sonraki_takip_tarihi`),
  KEY `fk_bekleyen_veli_gorusme_veli` (`bekleyen_veli_id`),
  KEY `fk_bekleyen_veli_gorusme_kullanici` (`olusturan_kullanici_id`),
  CONSTRAINT `fk_bekleyen_veli_gorusme_kullanici` FOREIGN KEY (`olusturan_kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_bekleyen_veli_gorusme_kurum` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bekleyen_veli_gorusme_veli` FOREIGN KEY (`bekleyen_veli_id`) REFERENCES `bekleyen_veliler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tenant_ecb46bfe937c9ca1915a` FOREIGN KEY (`kurum_id`, `bekleyen_veli_id`) REFERENCES `bekleyen_veliler` (`kurum_id`, `id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bekleyen_veli_gruplari` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kurum_id` bigint(20) unsigned NOT NULL,
  `bekleyen_veli_id` bigint(20) unsigned NOT NULL,
  `grup_id` bigint(20) unsigned NOT NULL,
  `olusturan_kullanici_id` bigint(20) unsigned DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bekleyen_veli_grubu` (`kurum_id`,`bekleyen_veli_id`,`grup_id`),
  KEY `idx_bekleyen_veli_grubu_grup` (`kurum_id`,`grup_id`),
  KEY `fk_bekleyen_veli_grubu_veli` (`bekleyen_veli_id`),
  KEY `fk_bekleyen_veli_grubu_grup` (`grup_id`),
  KEY `fk_bekleyen_veli_grubu_kullanici` (`olusturan_kullanici_id`),
  CONSTRAINT `fk_bekleyen_veli_grubu_grup` FOREIGN KEY (`grup_id`) REFERENCES `gruplar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bekleyen_veli_grubu_kullanici` FOREIGN KEY (`olusturan_kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_bekleyen_veli_grubu_kurum` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bekleyen_veli_grubu_veli` FOREIGN KEY (`bekleyen_veli_id`) REFERENCES `bekleyen_veliler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tenant_095d74f59953ee6e0661` FOREIGN KEY (`kurum_id`, `grup_id`) REFERENCES `gruplar` (`kurum_id`, `id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tenant_6a17a732e82b0cebd832` FOREIGN KEY (`kurum_id`, `bekleyen_veli_id`) REFERENCES `bekleyen_veliler` (`kurum_id`, `id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bekleyen_veliler` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ogrenci_id` bigint(20) unsigned DEFAULT NULL,
  `ogrenci_ad_soyad` varchar(160) NOT NULL,
  `ogrenci_dogum_tarihi` date DEFAULT NULL,
  `veli_ad_soyad` varchar(160) NOT NULL,
  `veli_telefon` varchar(32) NOT NULL,
  `veli_eposta` varchar(160) DEFAULT NULL,
  `beklenen_gun` varchar(20) DEFAULT NULL,
  `ay_grubu` varchar(80) DEFAULT NULL,
  `zaman_tercihi` enum('hafta_ici','hafta_sonu','farketmez') NOT NULL DEFAULT 'farketmez',
  `durum` enum('yeni_talep','ilk_gorusme_yapilacak','bilgi_verildi','uygun_grup_bekliyor','veli_donusu_bekleniyor','tekrar_aranacak','kayit_olmaya_hazir','kayit_oldu','vazgecti','ulasilamadi','yas_uygun_degil','saatler_uymadi','diger','bekliyor','iletisime_gecildi','katilmadi','kayda_donustu','iptal') NOT NULL DEFAULT 'yeni_talep',
  `next_follow_up_at` datetime DEFAULT NULL,
  `next_action_type` enum('telefonla_ara','whatsapp_gonder','veli_donusunu_bekle','grup_kontrol_et','kayit_icin_ara','diger') DEFAULT NULL,
  `next_action_note` varchar(500) DEFAULT NULL,
  `notlar` text DEFAULT NULL,
  `olusturan_kullanici_id` bigint(20) unsigned DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL,
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tenant_a69a2e69137a18184cf5` (`kurum_id`,`id`),
  KEY `idx_bekleyen_veliler_durum` (`durum`),
  KEY `idx_bekleyen_veliler_telefon` (`veli_telefon`),
  KEY `idx_bekleyen_veliler_gun` (`beklenen_gun`),
  KEY `fk_bekleyen_veliler_kullanici` (`olusturan_kullanici_id`),
  KEY `idx_bekleyen_veliler_kurum` (`kurum_id`),
  KEY `idx_bekleyen_veliler_crm_takip` (`kurum_id`,`durum`,`next_follow_up_at`),
  CONSTRAINT `fk_bekleyen_veliler_kullanici` FOREIGN KEY (`olusturan_kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_kurum_4bcd06e91f3a0f492f26` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bildirimler` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kullanici_id` bigint(20) unsigned DEFAULT NULL,
  `baslik` varchar(190) NOT NULL,
  `mesaj` text NOT NULL,
  `okundu` tinyint(1) NOT NULL DEFAULT 0,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_bildirimler_kullanici` (`kullanici_id`),
  KEY `idx_bildirimler_kurum` (`kurum_id`),
  CONSTRAINT `fk_bildirimler_kullanici` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_kurum_b53bb888dfff63c65b68` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cariler` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kurum_id` bigint(20) unsigned NOT NULL,
  `veli_id` bigint(20) unsigned DEFAULT NULL,
  `profil_turu` enum('bireysel','kurumsal') NOT NULL DEFAULT 'bireysel',
  `ad` varchar(120) DEFAULT NULL,
  `soyad` varchar(120) DEFAULT NULL,
  `unvan` varchar(500) DEFAULT NULL,
  `vkn_tckn` varchar(20) NOT NULL,
  `vergi_dairesi` varchar(190) DEFAULT NULL,
  `adres` text NOT NULL,
  `il` varchar(100) NOT NULL,
  `ilce` varchar(100) NOT NULL,
  `ulke` varchar(100) NOT NULL DEFAULT 'TÜRKİYE',
  `eposta` varchar(190) DEFAULT NULL,
  `telefon` varchar(40) DEFAULT NULL,
  `notlar` text DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cari_vkn` (`kurum_id`,`vkn_tckn`),
  UNIQUE KEY `uq_cari_veli` (`kurum_id`,`veli_id`),
  KEY `idx_cari_unvan` (`kurum_id`,`unvan`),
  KEY `fk_cari_veli` (`veli_id`),
  CONSTRAINT `fk_cari_kurum` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cari_veli` FOREIGN KEY (`veli_id`) REFERENCES `veliler` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cocuk_isletme_aktarimlari` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kaynak` varchar(30) NOT NULL DEFAULT 'openstreetmap',
  `kayit_sayisi` int(10) unsigned NOT NULL DEFAULT 0,
  `aktarim_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cocuk_isletmeleri` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `osm_turu` enum('node','way','relation') NOT NULL,
  `osm_id` bigint(20) unsigned NOT NULL,
  `kaynak_kimligi` varchar(255) DEFAULT NULL,
  `ad` varchar(255) NOT NULL,
  `kategori` enum('okul','anaokulu','kres','oyun_evi','cocuk_parki','diger') NOT NULL DEFAULT 'diger',
  `adres` varchar(500) DEFAULT NULL,
  `ilce` varchar(100) DEFAULT NULL,
  `telefon` varchar(100) DEFAULT NULL,
  `web_sitesi` varchar(500) DEFAULT NULL,
  `enlem` decimal(10,7) NOT NULL,
  `boylam` decimal(10,7) NOT NULL,
  `kaynak` varchar(30) NOT NULL DEFAULT 'openstreetmap',
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `kaynak_guncellenme_tarihi` datetime DEFAULT NULL,
  `onbellek_son_tarihi` datetime DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cocuk_isletmeleri_osm` (`osm_turu`,`osm_id`),
  UNIQUE KEY `uq_cocuk_isletmeleri_kaynak` (`kaynak`,`kaynak_kimligi`),
  KEY `idx_cocuk_isletmeleri_kategori` (`aktif`,`kategori`),
  KEY `idx_cocuk_isletmeleri_konum` (`enlem`,`boylam`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ders_programlari` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `grup_id` bigint(20) unsigned NOT NULL,
  `gun` tinyint(3) unsigned NOT NULL,
  `baslangic_saati` time NOT NULL,
  `bitis_saati` time NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_ders_programlari_grup` (`grup_id`),
  KEY `idx_ders_programlari_kurum` (`kurum_id`),
  KEY `fk_tenant_25a7d028153e1661b5a4` (`kurum_id`,`grup_id`),
  CONSTRAINT `fk_ders_programlari_grup` FOREIGN KEY (`grup_id`) REFERENCES `gruplar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_kurum_db8d77c26442c730cd65` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`),
  CONSTRAINT `fk_tenant_25a7d028153e1661b5a4` FOREIGN KEY (`kurum_id`, `grup_id`) REFERENCES `gruplar` (`kurum_id`, `id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `fatura_arsiv_dosyalari` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kurum_id` bigint(20) unsigned NOT NULL,
  `fatura_id` bigint(20) unsigned NOT NULL,
  `format` enum('pdf','xml') NOT NULL,
  `goreli_yol` varchar(500) NOT NULL,
  `sha256` char(64) NOT NULL,
  `dosya_boyutu` bigint(20) unsigned NOT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fatura_arsiv_format` (`kurum_id`,`fatura_id`,`format`),
  KEY `idx_fatura_arsiv_fatura` (`fatura_id`),
  CONSTRAINT `fk_fatura_arsiv_fatura` FOREIGN KEY (`fatura_id`) REFERENCES `faturalar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fatura_arsiv_kurum` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tenant_d1936f589b5c243993c7` FOREIGN KEY (`kurum_id`, `fatura_id`) REFERENCES `faturalar` (`kurum_id`, `id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `fatura_entegrasyon_denemeleri` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kurum_id` bigint(20) unsigned NOT NULL,
  `odeme_id` bigint(20) unsigned NOT NULL,
  `provider` varchar(40) NOT NULL,
  `basarili` tinyint(1) NOT NULL DEFAULT 0,
  `http_durumu` smallint(6) DEFAULT NULL,
  `hata_kodu` varchar(100) DEFAULT NULL,
  `hata_mesaji` text DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fatura_deneme` (`kurum_id`,`odeme_id`,`id`),
  KEY `fk_fatura_deneme_odeme` (`odeme_id`),
  CONSTRAINT `fk_fatura_deneme_kurum` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fatura_deneme_odeme` FOREIGN KEY (`odeme_id`) REFERENCES `odemeler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tenant_96a057173ab3a8e5596b` FOREIGN KEY (`kurum_id`, `odeme_id`) REFERENCES `odemeler` (`kurum_id`, `id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `fatura_kalemleri` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kurum_id` bigint(20) unsigned NOT NULL,
  `fatura_id` bigint(20) unsigned NOT NULL,
  `aciklama` varchar(500) NOT NULL,
  `miktar` decimal(14,4) NOT NULL DEFAULT 1.0000,
  `birim_kodu` varchar(20) NOT NULL DEFAULT 'C62',
  `birim_fiyat` decimal(14,4) NOT NULL,
  `kdv_orani` decimal(7,4) NOT NULL DEFAULT 0.0000,
  `kdv_tutari` decimal(14,2) NOT NULL DEFAULT 0.00,
  `satir_toplami` decimal(14,2) NOT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fatura_kalemleri_fatura` (`kurum_id`,`fatura_id`),
  KEY `fk_fatura_kalemi_fatura` (`fatura_id`),
  CONSTRAINT `fk_fatura_kalemi_fatura` FOREIGN KEY (`fatura_id`) REFERENCES `faturalar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fatura_kalemi_kurum` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tenant_9267d40101946123c73a` FOREIGN KEY (`kurum_id`, `fatura_id`) REFERENCES `faturalar` (`kurum_id`, `id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `fatura_profilleri` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kurum_id` bigint(20) unsigned NOT NULL,
  `veli_id` bigint(20) unsigned NOT NULL,
  `profil_turu` enum('bireysel','kurumsal') NOT NULL DEFAULT 'bireysel',
  `ad` varchar(120) DEFAULT NULL,
  `soyad` varchar(120) DEFAULT NULL,
  `unvan` varchar(500) DEFAULT NULL,
  `vkn_tckn` varchar(20) NOT NULL,
  `vergi_dairesi` varchar(190) DEFAULT NULL,
  `adres` text NOT NULL,
  `il` varchar(100) NOT NULL,
  `ilce` varchar(100) NOT NULL,
  `ulke` varchar(100) NOT NULL DEFAULT 'TÜRKİYE',
  `eposta` varchar(190) DEFAULT NULL,
  `telefon` varchar(40) DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fatura_profili_veli` (`kurum_id`,`veli_id`),
  KEY `idx_fatura_profili_vkn` (`kurum_id`,`vkn_tckn`),
  KEY `fk_fatura_profili_veli` (`veli_id`),
  CONSTRAINT `fk_fatura_profili_kurum` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fatura_profili_veli` FOREIGN KEY (`veli_id`) REFERENCES `veliler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tenant_5cebc42f493f4d77ad3b` FOREIGN KEY (`kurum_id`, `veli_id`) REFERENCES `veliler` (`kurum_id`, `id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `faturalar` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kurum_id` bigint(20) unsigned NOT NULL,
  `entegrasyon_id` bigint(20) unsigned DEFAULT NULL,
  `provider` varchar(40) DEFAULT NULL,
  `odeme_id` bigint(20) unsigned DEFAULT NULL,
  `veli_id` bigint(20) unsigned DEFAULT NULL,
  `ogrenci_id` bigint(20) unsigned DEFAULT NULL,
  `cari_id` bigint(20) unsigned DEFAULT NULL,
  `kaynak` enum('application','mysoft','nes') NOT NULL DEFAULT 'application',
  `belge_turu` varchar(30) NOT NULL,
  `profil` varchar(40) NOT NULL,
  `fatura_tipi` varchar(40) NOT NULL DEFAULT 'SATIS',
  `ettn` char(36) NOT NULL,
  `referans_anahtari` varchar(100) DEFAULT NULL,
  `mysoft_invoice_id` bigint(20) DEFAULT NULL,
  `provider_invoice_id` varchar(190) DEFAULT NULL,
  `fatura_no` varchar(50) DEFAULT NULL,
  `fatura_tarihi` date NOT NULL,
  `fatura_saati` time DEFAULT NULL,
  `para_birimi` varchar(10) NOT NULL DEFAULT 'TRY',
  `ara_toplam` decimal(14,2) NOT NULL DEFAULT 0.00,
  `kdv_toplami` decimal(14,2) NOT NULL DEFAULT 0.00,
  `genel_toplam` decimal(14,2) NOT NULL DEFAULT 0.00,
  `alici_adi` varchar(500) NOT NULL,
  `alici_vkn_tckn` varchar(20) NOT NULL,
  `yerel_durum` varchar(40) NOT NULL DEFAULT 'olusturuluyor',
  `mysoft_durum` varchar(100) DEFAULT NULL,
  `provider_durum` varchar(100) DEFAULT NULL,
  `fallback_kullanildi` tinyint(1) NOT NULL DEFAULT 0,
  `hata_kodu` varchar(100) DEFAULT NULL,
  `hata_mesaji` text DEFAULT NULL,
  `son_http_durumu` smallint(6) DEFAULT NULL,
  `son_cevap` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`son_cevap`)),
  `gonderilme_tarihi` datetime DEFAULT NULL,
  `durum_sorgulama_tarihi` datetime DEFAULT NULL,
  `iptal_tarihi` datetime DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fatura_ettn` (`kurum_id`,`ettn`),
  UNIQUE KEY `uq_tenant_499367b7358a130b68a9` (`kurum_id`,`id`),
  UNIQUE KEY `uq_fatura_odeme` (`kurum_id`,`odeme_id`),
  KEY `idx_fatura_liste` (`kurum_id`,`fatura_tarihi`,`id`),
  KEY `idx_fatura_durum` (`kurum_id`,`yerel_durum`),
  KEY `fk_fatura_entegrasyon` (`entegrasyon_id`),
  KEY `fk_fatura_odeme` (`odeme_id`),
  KEY `fk_fatura_veli` (`veli_id`),
  KEY `fk_fatura_ogrenci` (`ogrenci_id`),
  KEY `idx_fatura_cari` (`kurum_id`,`cari_id`),
  KEY `fk_fatura_cari` (`cari_id`),
  CONSTRAINT `fk_fatura_cari` FOREIGN KEY (`cari_id`) REFERENCES `cariler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_fatura_entegrasyon` FOREIGN KEY (`entegrasyon_id`) REFERENCES `kurum_entegrasyonlari` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_fatura_kurum` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fatura_odeme` FOREIGN KEY (`odeme_id`) REFERENCES `odemeler` (`id`),
  CONSTRAINT `fk_fatura_ogrenci` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_fatura_veli` FOREIGN KEY (`veli_id`) REFERENCES `veliler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tenant_c84be63b229671a260c7` FOREIGN KEY (`kurum_id`, `odeme_id`) REFERENCES `odemeler` (`kurum_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `giderler` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tarih` date NOT NULL,
  `tedarikci` varchar(150) NOT NULL,
  `kategori` varchar(120) DEFAULT NULL,
  `aciklama` varchar(255) DEFAULT NULL,
  `tutar` decimal(12,2) NOT NULL,
  `odeme_turu` enum('nakit','kredi_karti','banka_havalesi','otomatik_odeme','diger') NOT NULL DEFAULT 'nakit',
  `kasa_id` bigint(20) unsigned DEFAULT NULL,
  `durum` enum('planlandi','odendi','iptal') NOT NULL DEFAULT 'planlandi',
  `odeme_tarihi` date DEFAULT NULL,
  `olusturan_kullanici_id` bigint(20) unsigned NOT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_giderler_kullanici` (`olusturan_kullanici_id`),
  KEY `idx_giderler_tarih` (`tarih`),
  KEY `idx_giderler_durum` (`durum`),
  KEY `idx_giderler_kasa` (`kasa_id`),
  KEY `idx_giderler_kurum` (`kurum_id`),
  CONSTRAINT `fk_giderler_kasa` FOREIGN KEY (`kasa_id`) REFERENCES `kasalar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_giderler_kullanici` FOREIGN KEY (`olusturan_kullanici_id`) REFERENCES `kullanicilar` (`id`),
  CONSTRAINT `fk_kurum_e94f0b488b99df2c7c14` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `grup_ogrencileri` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `grup_id` bigint(20) unsigned NOT NULL,
  `ogrenci_id` bigint(20) unsigned NOT NULL,
  `baslangic_tarihi` date NOT NULL,
  `bitis_tarihi` date DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grup_ogrenci_aktif_unique` (`grup_id`,`ogrenci_id`,`aktif`),
  KEY `fk_grup_ogrencileri_ogrenci` (`ogrenci_id`),
  KEY `idx_grup_ogrencileri_kurum` (`kurum_id`),
  KEY `fk_tenant_c51f7c768b29343c06c3` (`kurum_id`,`ogrenci_id`),
  KEY `fk_tenant_5048a0983d3809c09328` (`kurum_id`,`grup_id`),
  CONSTRAINT `fk_grup_ogrencileri_grup` FOREIGN KEY (`grup_id`) REFERENCES `gruplar` (`id`),
  CONSTRAINT `fk_grup_ogrencileri_ogrenci` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`),
  CONSTRAINT `fk_kurum_c9b5b6aae16cb51faaf8` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`),
  CONSTRAINT `fk_tenant_5048a0983d3809c09328` FOREIGN KEY (`kurum_id`, `grup_id`) REFERENCES `gruplar` (`kurum_id`, `id`),
  CONSTRAINT `fk_tenant_c51f7c768b29343c06c3` FOREIGN KEY (`kurum_id`, `ogrenci_id`) REFERENCES `ogrenciler` (`kurum_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gruplar` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ad` varchar(150) NOT NULL,
  `yas_araligi` varchar(100) DEFAULT NULL,
  `kontenjan` int(10) unsigned NOT NULL DEFAULT 8,
  `ogretmen_id` bigint(20) unsigned DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `durum` varchar(50) DEFAULT 'durum_yok',
  `aciklama` text DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tenant_49bef013cc7cfc411579` (`kurum_id`,`id`),
  KEY `fk_gruplar_ogretmen` (`ogretmen_id`),
  KEY `idx_gruplar_kurum` (`kurum_id`),
  CONSTRAINT `fk_gruplar_ogretmen` FOREIGN KEY (`ogretmen_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_kurum_aa2bea866ef7fe4c317e` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gunluk_notlar` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ogrenci_id` bigint(20) unsigned NOT NULL,
  `randevu_id` bigint(20) unsigned DEFAULT NULL,
  `tarih` date NOT NULL,
  `kategori` varchar(80) NOT NULL DEFAULT 'Genel',
  `not_metni` text NOT NULL,
  `olusturan_kullanici_id` bigint(20) unsigned DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_gunluk_notlar_tarih` (`tarih`),
  KEY `idx_gunluk_notlar_ogrenci` (`ogrenci_id`,`tarih`),
  KEY `idx_gunluk_notlar_randevu` (`randevu_id`),
  KEY `idx_gunluk_notlar_kurum` (`kurum_id`),
  CONSTRAINT `fk_kurum_a90fc42dfc688c0bad77` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hak_hareketleri` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ogrenci_id` bigint(20) unsigned NOT NULL,
  `paket_id` bigint(20) unsigned DEFAULT NULL,
  `randevu_id` bigint(20) unsigned DEFAULT NULL,
  `hareket_turu` varchar(80) NOT NULL,
  `hak_turu` varchar(80) NOT NULL,
  `miktar` int(11) NOT NULL,
  `onceki_kalan` int(11) NOT NULL,
  `sonraki_kalan` int(11) NOT NULL,
  `aciklama` text DEFAULT NULL,
  `olusturan_kullanici_id` bigint(20) unsigned DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_hak_hareketleri_ogrenci` (`ogrenci_id`),
  KEY `fk_hak_hareketleri_paket` (`paket_id`),
  KEY `fk_hak_hareketleri_randevu` (`randevu_id`),
  KEY `fk_hak_hareketleri_kullanici` (`olusturan_kullanici_id`),
  KEY `idx_hak_hareketleri_kurum` (`kurum_id`),
  KEY `fk_tenant_f819f8315bdbf7a954c6` (`kurum_id`,`ogrenci_id`),
  KEY `fk_tenant_34862d2dedf8b21c788a` (`kurum_id`,`paket_id`),
  CONSTRAINT `fk_hak_hareketleri_kullanici` FOREIGN KEY (`olusturan_kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_hak_hareketleri_ogrenci` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`),
  CONSTRAINT `fk_hak_hareketleri_paket` FOREIGN KEY (`paket_id`) REFERENCES `paketler` (`id`),
  CONSTRAINT `fk_hak_hareketleri_randevu` FOREIGN KEY (`randevu_id`) REFERENCES `randevular` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_kurum_039556d7727b61fc0f1f` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`),
  CONSTRAINT `fk_tenant_34862d2dedf8b21c788a` FOREIGN KEY (`kurum_id`, `paket_id`) REFERENCES `paketler` (`kurum_id`, `id`),
  CONSTRAINT `fk_tenant_f819f8315bdbf7a954c6` FOREIGN KEY (`kurum_id`, `ogrenci_id`) REFERENCES `ogrenciler` (`kurum_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hiz_sinirlari` (
  `scope_hash` char(64) NOT NULL,
  `attempts` int(10) unsigned NOT NULL DEFAULT 0,
  `window_started_at` datetime NOT NULL,
  `blocked_until` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`scope_hash`),
  KEY `idx_hiz_sinirlari_temizlik` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hizmetler` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hizmet_adi` varchar(190) NOT NULL,
  `ucret` decimal(12,2) NOT NULL DEFAULT 0.00,
  `kdv_orani` decimal(5,2) DEFAULT NULL,
  `haftalik_katilim_sayisi` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `toplam_normal_hak` int(10) unsigned NOT NULL DEFAULT 4,
  `toplam_telafi_hak` int(10) unsigned NOT NULL DEFAULT 1,
  `hak_hesaplama_turu` enum('sabit','aylik_takvim') NOT NULL DEFAULT 'sabit',
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hizmetler_kurum` (`kurum_id`),
  CONSTRAINT `fk_kurum_854315216e13e0af4f05` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `islem_kayitlari` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kullanici_id` bigint(20) unsigned DEFAULT NULL,
  `islem` varchar(120) NOT NULL,
  `aciklama` text NOT NULL,
  `veri` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`veri`)),
  `ip_adresi` varchar(45) DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_islem_kayitlari_kullanici` (`kullanici_id`),
  KEY `idx_islem_kayitlari_kurum` (`kurum_id`),
  CONSTRAINT `fk_islem_kayitlari_kullanici` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_kurum_d0646b135611d39e9d13` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kalici_oturumlar` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kurum_id` bigint(20) unsigned NOT NULL,
  `kullanici_id` bigint(20) unsigned NOT NULL,
  `secici` char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dogrulayici_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `oturum_surumu` int(10) unsigned NOT NULL,
  `sona_erme_tarihi` datetime NOT NULL,
  `son_kullanim_tarihi` datetime DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kalici_oturum_secici` (`secici`),
  KEY `idx_kalici_oturum_kullanici` (`kullanici_id`,`sona_erme_tarihi`),
  KEY `idx_kalici_oturum_sona_erme` (`sona_erme_tarihi`),
  KEY `fk_kalici_oturum_kurum` (`kurum_id`),
  CONSTRAINT `fk_kalici_oturum_kullanici` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_kalici_oturum_kurum` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kasa_hareketleri` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kasa_id` bigint(20) unsigned NOT NULL,
  `tarih` date NOT NULL,
  `tur` enum('giris','cikis') NOT NULL DEFAULT 'giris',
  `tutar` decimal(14,2) NOT NULL,
  `aciklama` varchar(255) DEFAULT NULL,
  `olusturan_kullanici_id` bigint(20) unsigned DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_kasa_hareketleri_kasa` (`kasa_id`),
  KEY `idx_kasa_hareketleri_tarih` (`tarih`),
  KEY `fk_kasa_hareketleri_kullanici` (`olusturan_kullanici_id`),
  KEY `idx_kasa_hareketleri_kurum` (`kurum_id`),
  KEY `fk_tenant_948a8b63c327f30d52b8` (`kurum_id`,`kasa_id`),
  CONSTRAINT `fk_kasa_hareketleri_kasa` FOREIGN KEY (`kasa_id`) REFERENCES `kasalar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_kasa_hareketleri_kullanici` FOREIGN KEY (`olusturan_kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_kurum_c2496dd67bee4da51de4` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`),
  CONSTRAINT `fk_tenant_948a8b63c327f30d52b8` FOREIGN KEY (`kurum_id`, `kasa_id`) REFERENCES `kasalar` (`kurum_id`, `id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kasalar` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ad` varchar(150) NOT NULL,
  `tur` enum('nakit','banka','altin','diger') NOT NULL DEFAULT 'nakit',
  `para_birimi` varchar(10) NOT NULL DEFAULT 'TRY',
  `acilis_bakiyesi` decimal(14,2) NOT NULL DEFAULT 0.00,
  `aciklama` varchar(255) DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `olusturan_kullanici_id` bigint(20) unsigned DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tenant_c866291ab17f3bb752f0` (`kurum_id`,`id`),
  KEY `idx_kasalar_aktif` (`aktif`),
  KEY `idx_kasalar_tur` (`tur`),
  KEY `fk_kasalar_kullanici` (`olusturan_kullanici_id`),
  KEY `idx_kasalar_kurum` (`kurum_id`),
  CONSTRAINT `fk_kasalar_kullanici` FOREIGN KEY (`olusturan_kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_kurum_dc3f8014b8ba64819bb0` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kullanicilar` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rol_id` bigint(20) unsigned NOT NULL,
  `ad` varchar(100) NOT NULL,
  `soyad` varchar(100) NOT NULL,
  `eposta` varchar(190) NOT NULL,
  `telefon` varchar(40) DEFAULT NULL,
  `sifre` varchar(255) NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `sistem_yoneticisi` tinyint(1) NOT NULL DEFAULT 0,
  `mfa_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `mfa_secret_sifreli` text DEFAULT NULL,
  `mfa_kurtarma_kodlari_sifreli` text DEFAULT NULL,
  `oturum_surumu` int(10) unsigned NOT NULL DEFAULT 1,
  `son_giris_tarihi` datetime DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kullanicilar_kurum_eposta` (`kurum_id`,`eposta`),
  KEY `fk_kullanicilar_rol` (`rol_id`),
  KEY `idx_kullanicilar_kurum` (`kurum_id`),
  KEY `idx_kullanicilar_sistem_yoneticisi` (`sistem_yoneticisi`,`aktif`),
  CONSTRAINT `fk_kullanicilar_rol` FOREIGN KEY (`rol_id`) REFERENCES `roller` (`id`),
  CONSTRAINT `fk_kurum_b52332518761b3165c9b` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kullanici_ek_yetkileri` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kurum_id` bigint(20) unsigned NOT NULL,
  `kullanici_id` bigint(20) unsigned NOT NULL,
  `yetki` varchar(100) NOT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kullanici_ek_yetki` (`kurum_id`,`kullanici_id`,`yetki`),
  KEY `idx_kullanici_ek_yetkileri_kullanici` (`kullanici_id`),
  CONSTRAINT `fk_kullanici_ek_yetkileri_kurum` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_kullanici_ek_yetkileri_kullanici` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kurum_entegrasyonlari` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kurum_id` bigint(20) unsigned NOT NULL,
  `provider` varchar(40) NOT NULL DEFAULT 'mysoft',
  `base_url` varchar(255) NOT NULL,
  `ortam` varchar(20) NOT NULL DEFAULT 'test',
  `tenant_identifier_number` varchar(20) NOT NULL,
  `firma_unvani` varchar(500) DEFAULT NULL,
  `vergi_dairesi` varchar(190) DEFAULT NULL,
  `adres_basligi` varchar(100) DEFAULT NULL,
  `adres` text DEFAULT NULL,
  `il` varchar(100) DEFAULT NULL,
  `ilce` varchar(100) DEFAULT NULL,
  `posta_kodu` varchar(20) DEFAULT NULL,
  `ulke` varchar(100) DEFAULT NULL,
  `eposta` varchar(190) DEFAULT NULL,
  `telefon` varchar(40) DEFAULT NULL,
  `web_sitesi` varchar(255) DEFAULT NULL,
  `client_id` varchar(255) DEFAULT NULL,
  `client_secret_sifreli` text DEFAULT NULL,
  `efatura_prefix` varchar(20) DEFAULT NULL,
  `earsiv_prefix` varchar(20) DEFAULT NULL,
  `numerator_set_code` varchar(100) DEFAULT NULL,
  `gb_alias` varchar(255) DEFAULT NULL,
  `varsayilan_efatura_profili` varchar(40) NOT NULL DEFAULT 'TEMELFATURA',
  `aktif` tinyint(1) NOT NULL DEFAULT 0,
  `otomatik_fatura` tinyint(1) NOT NULL DEFAULT 0,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kurum_entegrasyon_provider` (`kurum_id`,`provider`),
  KEY `idx_kurum_entegrasyon_aktif` (`kurum_id`,`aktif`),
  CONSTRAINT `fk_kurum_entegrasyon_kurum` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `odeme_sozleri` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ogrenci_id` bigint(20) unsigned NOT NULL,
  `veli_id` bigint(20) unsigned DEFAULT NULL,
  `paket_id` bigint(20) unsigned NOT NULL,
  `soz_verilen_tutar` decimal(12,2) NOT NULL,
  `soz_verilen_tarih` date NOT NULL,
  `hatirlatma_tarihi` date DEFAULT NULL,
  `durum` enum('bekleniyor','bugun_odenecek','odendi','gecikti','yeni_tarih_verildi','iptal_edildi') NOT NULL DEFAULT 'bekleniyor',
  `aciklama` text DEFAULT NULL,
  `onceki_tarih` date DEFAULT NULL,
  `yeni_tarih` date DEFAULT NULL,
  `odeme_id` bigint(20) unsigned DEFAULT NULL,
  `olusturan_kullanici_id` bigint(20) unsigned NOT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_odeme_sozleri_ogrenci` (`ogrenci_id`),
  KEY `fk_odeme_sozleri_veli` (`veli_id`),
  KEY `fk_odeme_sozleri_paket` (`paket_id`),
  KEY `fk_odeme_sozleri_odeme` (`odeme_id`),
  KEY `fk_odeme_sozleri_kullanici` (`olusturan_kullanici_id`),
  KEY `idx_odeme_sozleri_kurum` (`kurum_id`),
  KEY `fk_tenant_e593fe5bbb38e2098865` (`kurum_id`,`ogrenci_id`),
  KEY `fk_tenant_145790803027ac8a497f` (`kurum_id`,`paket_id`),
  CONSTRAINT `fk_kurum_21e5687b31fa2ab713f1` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`),
  CONSTRAINT `fk_odeme_sozleri_kullanici` FOREIGN KEY (`olusturan_kullanici_id`) REFERENCES `kullanicilar` (`id`),
  CONSTRAINT `fk_odeme_sozleri_odeme` FOREIGN KEY (`odeme_id`) REFERENCES `odemeler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_odeme_sozleri_ogrenci` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`),
  CONSTRAINT `fk_odeme_sozleri_paket` FOREIGN KEY (`paket_id`) REFERENCES `paketler` (`id`),
  CONSTRAINT `fk_odeme_sozleri_veli` FOREIGN KEY (`veli_id`) REFERENCES `veliler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tenant_145790803027ac8a497f` FOREIGN KEY (`kurum_id`, `paket_id`) REFERENCES `paketler` (`kurum_id`, `id`),
  CONSTRAINT `fk_tenant_e593fe5bbb38e2098865` FOREIGN KEY (`kurum_id`, `ogrenci_id`) REFERENCES `ogrenciler` (`kurum_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `odemeler` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ogrenci_id` bigint(20) unsigned NOT NULL,
  `veli_id` bigint(20) unsigned DEFAULT NULL,
  `paket_id` bigint(20) unsigned NOT NULL,
  `tarih` date NOT NULL,
  `tutar` decimal(12,2) NOT NULL,
  `yontem` enum('nakit','kredi_karti','havale_eft','odeme_baglantisi','diger') NOT NULL,
  `kasa_id` bigint(20) unsigned DEFAULT NULL,
  `makbuz_numarasi` varchar(100) DEFAULT NULL,
  `aciklama` text DEFAULT NULL,
  `alan_kullanici_id` bigint(20) unsigned NOT NULL,
  `iptal` tinyint(1) NOT NULL DEFAULT 0,
  `iptal_nedeni` text DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tenant_712b2fafa19616a2c339` (`kurum_id`,`id`),
  KEY `fk_odemeler_ogrenci` (`ogrenci_id`),
  KEY `fk_odemeler_veli` (`veli_id`),
  KEY `fk_odemeler_kullanici` (`alan_kullanici_id`),
  KEY `idx_odemeler_paket_iptal` (`paket_id`,`iptal`),
  KEY `idx_odemeler_kasa` (`kasa_id`),
  KEY `idx_odemeler_kurum` (`kurum_id`),
  KEY `fk_tenant_75b9e050cbbe8fdd3c88` (`kurum_id`,`ogrenci_id`),
  KEY `fk_tenant_c9c64380e125315157de` (`kurum_id`,`paket_id`),
  CONSTRAINT `fk_kurum_224c9e0c2a346fc4b37e` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`),
  CONSTRAINT `fk_odemeler_kasa` FOREIGN KEY (`kasa_id`) REFERENCES `kasalar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_odemeler_kullanici` FOREIGN KEY (`alan_kullanici_id`) REFERENCES `kullanicilar` (`id`),
  CONSTRAINT `fk_odemeler_ogrenci` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`),
  CONSTRAINT `fk_odemeler_paket` FOREIGN KEY (`paket_id`) REFERENCES `paketler` (`id`),
  CONSTRAINT `fk_odemeler_veli` FOREIGN KEY (`veli_id`) REFERENCES `veliler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tenant_75b9e050cbbe8fdd3c88` FOREIGN KEY (`kurum_id`, `ogrenci_id`) REFERENCES `ogrenciler` (`kurum_id`, `id`),
  CONSTRAINT `fk_tenant_c9c64380e125315157de` FOREIGN KEY (`kurum_id`, `paket_id`) REFERENCES `paketler` (`kurum_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ogrenci_gelisim_testleri` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kurum_id` bigint(20) unsigned NOT NULL,
  `ogrenci_id` bigint(20) unsigned NOT NULL,
  `uygulandi` tinyint(1) NOT NULL DEFAULT 0,
  `uygulama_tarihi` date DEFAULT NULL,
  `notlar` text DEFAULT NULL,
  `guncelleyen_kullanici_id` bigint(20) unsigned DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ogrenci_gelisim_testi` (`kurum_id`,`ogrenci_id`),
  KEY `idx_ogrenci_gelisim_testi_durum` (`kurum_id`,`uygulandi`),
  KEY `fk_ogrenci_gelisim_testi_ogrenci` (`ogrenci_id`),
  KEY `fk_ogrenci_gelisim_testi_kullanici` (`guncelleyen_kullanici_id`),
  CONSTRAINT `fk_ogrenci_gelisim_testi_kullanici` FOREIGN KEY (`guncelleyen_kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ogrenci_gelisim_testi_kurum` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ogrenci_gelisim_testi_ogrenci` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tenant_c2f5191351fb8a8b156c` FOREIGN KEY (`kurum_id`, `ogrenci_id`) REFERENCES `ogrenciler` (`kurum_id`, `id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ogrenci_kara_liste` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ogrenci_id` bigint(20) unsigned NOT NULL,
  `kategori` varchar(80) NOT NULL,
  `sebep` text NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `olusturan_kullanici_id` bigint(20) unsigned DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `kaldirilma_tarihi` datetime DEFAULT NULL,
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ogrenci_kara_liste_ogrenci` (`ogrenci_id`),
  KEY `idx_ogrenci_kara_liste_aktif` (`aktif`),
  KEY `idx_ogrenci_kara_liste_kategori` (`kategori`),
  KEY `fk_ogrenci_kara_liste_kullanici` (`olusturan_kullanici_id`),
  KEY `idx_ogrenci_kara_liste_kurum` (`kurum_id`),
  KEY `fk_tenant_21a237fd73258cf7e847` (`kurum_id`,`ogrenci_id`),
  CONSTRAINT `fk_kurum_2a13f22463c17e87047d` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`),
  CONSTRAINT `fk_ogrenci_kara_liste_kullanici` FOREIGN KEY (`olusturan_kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ogrenci_kara_liste_ogrenci` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tenant_21a237fd73258cf7e847` FOREIGN KEY (`kurum_id`, `ogrenci_id`) REFERENCES `ogrenciler` (`kurum_id`, `id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ogrenci_velileri` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ogrenci_id` bigint(20) unsigned NOT NULL,
  `veli_id` bigint(20) unsigned NOT NULL,
  `birincil_mi` tinyint(1) NOT NULL DEFAULT 0,
  `acil_durum_mu` tinyint(1) NOT NULL DEFAULT 0,
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ogrenci_veli_unique` (`ogrenci_id`,`veli_id`),
  KEY `fk_ogrenci_velileri_veli` (`veli_id`),
  KEY `idx_ogrenci_velileri_kurum` (`kurum_id`),
  KEY `fk_tenant_cdd150c18581e508c471` (`kurum_id`,`ogrenci_id`),
  KEY `fk_tenant_296ca317689ed0eb6255` (`kurum_id`,`veli_id`),
  CONSTRAINT `fk_kurum_7e73895985805b9ca053` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`),
  CONSTRAINT `fk_ogrenci_velileri_ogrenci` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ogrenci_velileri_veli` FOREIGN KEY (`veli_id`) REFERENCES `veliler` (`id`),
  CONSTRAINT `fk_tenant_296ca317689ed0eb6255` FOREIGN KEY (`kurum_id`, `veli_id`) REFERENCES `veliler` (`kurum_id`, `id`),
  CONSTRAINT `fk_tenant_cdd150c18581e508c471` FOREIGN KEY (`kurum_id`, `ogrenci_id`) REFERENCES `ogrenciler` (`kurum_id`, `id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ogrenciler` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ad` varchar(100) NOT NULL,
  `soyad` varchar(100) NOT NULL,
  `tc_kimlik_no` varchar(20) DEFAULT NULL,
  `dogum_tarihi` date DEFAULT NULL,
  `cinsiyet` enum('kiz','erkek','belirtilmedi') NOT NULL DEFAULT 'belirtilmedi',
  `fotograf` varchar(255) DEFAULT NULL,
  `kayit_tarihi` date NOT NULL,
  `durum` enum('aktif','pasif') NOT NULL DEFAULT 'aktif',
  `acil_durum_kisi` varchar(190) DEFAULT NULL,
  `acil_durum_telefon` varchar(40) DEFAULT NULL,
  `saglik_bilgisi` text DEFAULT NULL,
  `alerji_bilgisi` text DEFAULT NULL,
  `ozel_durum_notu` text DEFAULT NULL,
  `profil_ozel_notu` varchar(500) DEFAULT NULL,
  `vasi_ad_soyad` varchar(190) DEFAULT NULL,
  `vasi_tc_kimlik_no` varchar(20) DEFAULT NULL,
  `vasi_telefon` varchar(40) DEFAULT NULL,
  `yonetici_notu` text DEFAULT NULL,
  `ogretmen_notu` text DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  `il` varchar(100) DEFAULT NULL,
  `ilce` varchar(100) DEFAULT NULL,
  `adres` text DEFAULT NULL,
  `adres_enlem` decimal(10,7) DEFAULT NULL,
  `adres_boylam` decimal(10,7) DEFAULT NULL,
  `adres_konum_dogrulandi` tinyint(1) NOT NULL DEFAULT 0,
  `adres_konum_guncellenme_tarihi` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tenant_dff93fae3bdf80bcf235` (`kurum_id`,`id`),
  KEY `idx_ogrenciler_kurum` (`kurum_id`),
  KEY `idx_ogrenciler_adres_konum` (`kurum_id`,`adres_konum_dogrulandi`),
  CONSTRAINT `fk_kurum_304648acea9731f8607f` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `onam_form_ayarlari` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kurum_id` bigint(20) unsigned NOT NULL,
  `public_token` char(48) NOT NULL,
  `baslik` varchar(190) NOT NULL DEFAULT 'Oyun Grubu Katılımcı Bilgi ve Veli Onam Formu',
  `aciklama` text DEFAULT NULL,
  `onam_metni` longtext DEFAULT NULL,
  `form_surumu` int(10) unsigned NOT NULL DEFAULT 1,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_onam_form_ayari_kurum` (`kurum_id`),
  UNIQUE KEY `uq_onam_form_public_token` (`public_token`),
  UNIQUE KEY `uq_tenant_50557bf085e3713e40c3` (`kurum_id`,`id`),
  CONSTRAINT `fk_onam_form_ayari_kurum` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `paket_disi_haklar` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ogrenci_id` bigint(20) unsigned NOT NULL,
  `hak_turu` varchar(80) NOT NULL,
  `toplam_hak` int(10) unsigned NOT NULL,
  `kullanilan_hak` int(10) unsigned NOT NULL DEFAULT 0,
  `kalan_hak` int(10) unsigned NOT NULL,
  `baslangic_tarihi` date NOT NULL,
  `son_kullanim_tarihi` date DEFAULT NULL,
  `grup_id` bigint(20) unsigned DEFAULT NULL,
  `ogretmen_id` bigint(20) unsigned DEFAULT NULL,
  `ucretli_mi` tinyint(1) NOT NULL DEFAULT 0,
  `tutar` decimal(12,2) NOT NULL DEFAULT 0.00,
  `aciklama` text DEFAULT NULL,
  `durum` enum('aktif','tamamlandi','iptal') NOT NULL DEFAULT 'aktif',
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tenant_ed5ae90c19c743c4806a` (`kurum_id`,`id`),
  KEY `fk_paket_disi_haklar_ogrenci` (`ogrenci_id`),
  KEY `fk_paket_disi_haklar_grup` (`grup_id`),
  KEY `fk_paket_disi_haklar_ogretmen` (`ogretmen_id`),
  KEY `idx_paket_disi_haklar_kurum` (`kurum_id`),
  KEY `fk_tenant_a30d633e0cadfab77370` (`kurum_id`,`ogrenci_id`),
  CONSTRAINT `fk_kurum_39ef258e1dc59cb24434` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`),
  CONSTRAINT `fk_paket_disi_haklar_grup` FOREIGN KEY (`grup_id`) REFERENCES `gruplar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_paket_disi_haklar_ogrenci` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`),
  CONSTRAINT `fk_paket_disi_haklar_ogretmen` FOREIGN KEY (`ogretmen_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tenant_a30d633e0cadfab77370` FOREIGN KEY (`kurum_id`, `ogrenci_id`) REFERENCES `ogrenciler` (`kurum_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `paketler` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ogrenci_id` bigint(20) unsigned NOT NULL,
  `paket_sira_no` int(10) unsigned NOT NULL,
  `paket_adi` varchar(150) NOT NULL,
  `haftalik_katilim_sayisi` tinyint(3) unsigned NOT NULL,
  `toplam_normal_hak` int(10) unsigned NOT NULL,
  `toplam_telafi_hak` int(10) unsigned NOT NULL,
  `kullanilan_normal_hak` int(10) unsigned NOT NULL DEFAULT 0,
  `kullanilan_telafi_hak` int(10) unsigned NOT NULL DEFAULT 0,
  `kalan_normal_hak` int(10) unsigned NOT NULL,
  `kalan_telafi_hak` int(10) unsigned NOT NULL,
  `baslangic_tarihi` date NOT NULL,
  `tahmini_son_ders_tarihi` date DEFAULT NULL,
  `liste_fiyati` decimal(12,2) NOT NULL DEFAULT 0.00,
  `indirim_turu` varchar(80) DEFAULT NULL,
  `indirim_tutari` decimal(12,2) NOT NULL DEFAULT 0.00,
  `indirim_aciklama` text DEFAULT NULL,
  `net_paket_tutari` decimal(12,2) NOT NULL DEFAULT 0.00,
  `kdv_orani` decimal(5,2) DEFAULT NULL,
  `tahsilat_notu` text DEFAULT NULL,
  `beklenen_odeme_tarihi` date DEFAULT NULL,
  `paket_durumu` enum('aktif','tamamlandi','iptal') NOT NULL DEFAULT 'aktif',
  `yenileme_durumu` enum('belirsiz','yenilenecek','yenilenmeyecek') NOT NULL DEFAULT 'belirsiz',
  `yonetici_notu` text DEFAULT NULL,
  `olusturan_kullanici_id` bigint(20) unsigned NOT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ogrenci_paket_sira_unique` (`ogrenci_id`,`paket_sira_no`),
  UNIQUE KEY `uq_tenant_f8f78536f13f0646d66a` (`kurum_id`,`id`),
  KEY `fk_paketler_kullanici` (`olusturan_kullanici_id`),
  KEY `idx_paketler_durum_olusturma` (`paket_durumu`,`olusturulma_tarihi`),
  KEY `idx_paketler_kurum` (`kurum_id`),
  KEY `fk_tenant_78fdf391287255b41555` (`kurum_id`,`ogrenci_id`),
  CONSTRAINT `fk_kurum_619c324ecf7cbeb4d486` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`),
  CONSTRAINT `fk_paketler_kullanici` FOREIGN KEY (`olusturan_kullanici_id`) REFERENCES `kullanicilar` (`id`),
  CONSTRAINT `fk_paketler_ogrenci` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`),
  CONSTRAINT `fk_tenant_78fdf391287255b41555` FOREIGN KEY (`kurum_id`, `ogrenci_id`) REFERENCES `ogrenciler` (`kurum_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `personel_puantajlari` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kurum_id` bigint(20) unsigned NOT NULL,
  `personel_id` bigint(20) unsigned NOT NULL,
  `tarih` date NOT NULL,
  `durum` varchar(30) NOT NULL DEFAULT 'calisti',
  `giris_saati` time DEFAULT NULL,
  `cikis_saati` time DEFAULT NULL,
  `mola_dakika` smallint(5) unsigned NOT NULL DEFAULT 0,
  `aciklama` varchar(500) DEFAULT NULL,
  `kaydeden_kullanici_id` bigint(20) unsigned DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_personel_puantaj` (`kurum_id`,`personel_id`,`tarih`),
  KEY `idx_puantaj_tarih` (`kurum_id`,`tarih`,`durum`),
  KEY `fk_puantaj_personel` (`personel_id`),
  KEY `fk_puantaj_kaydeden` (`kaydeden_kullanici_id`),
  CONSTRAINT `fk_puantaj_kaydeden` FOREIGN KEY (`kaydeden_kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_puantaj_kurum` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_puantaj_personel` FOREIGN KEY (`personel_id`) REFERENCES `personeller` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tenant_cace05417a820cd718ed` FOREIGN KEY (`kurum_id`, `personel_id`) REFERENCES `personeller` (`kurum_id`, `id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `personeller` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kurum_id` bigint(20) unsigned NOT NULL,
  `kullanici_id` bigint(20) unsigned DEFAULT NULL,
  `tc_kimlik_no` varchar(11) DEFAULT NULL,
  `ad` varchar(120) NOT NULL,
  `soyad` varchar(120) NOT NULL,
  `pozisyon` varchar(160) DEFAULT NULL,
  `aylik_brut_ucret` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sgk_tesvik_turu` varchar(30) NOT NULL DEFAULT 'diger_2_puan',
  `ise_giris_tarihi` date DEFAULT NULL,
  `isten_cikis_tarihi` date DEFAULT NULL,
  `varsayilan_giris_saati` time DEFAULT NULL,
  `varsayilan_cikis_saati` time DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `notlar` text DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tenant_a43ca9fa32acb5f2a914` (`kurum_id`,`id`),
  UNIQUE KEY `uq_personel_tc` (`kurum_id`,`tc_kimlik_no`),
  UNIQUE KEY `uq_personel_kullanici` (`kurum_id`,`kullanici_id`),
  KEY `idx_personel_aktif` (`kurum_id`,`aktif`,`ad`,`soyad`),
  KEY `fk_personel_kullanici` (`kullanici_id`),
  CONSTRAINT `fk_personel_kullanici` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_personel_kurum` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `randevular` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ogrenci_id` bigint(20) unsigned NOT NULL,
  `veli_id` bigint(20) unsigned DEFAULT NULL,
  `grup_id` bigint(20) unsigned DEFAULT NULL,
  `paket_id` bigint(20) unsigned DEFAULT NULL,
  `paket_disi_hak_id` bigint(20) unsigned DEFAULT NULL,
  `telafi_hakki_id` bigint(20) unsigned DEFAULT NULL,
  `ogretmen_id` bigint(20) unsigned DEFAULT NULL,
  `tarih` date NOT NULL,
  `baslangic_saati` time NOT NULL,
  `bitis_saati` time NOT NULL,
  `tur` varchar(80) NOT NULL,
  `hak_kaynagi` varchar(80) NOT NULL,
  `durum` enum('planlandi','geldi','gelmedi','mazeretli_gelmedi','gec_iptal','kurum_iptali','ertelendi','tamamlandi') NOT NULL DEFAULT 'planlandi',
  `otomatik_gelmedi_islendi` tinyint(1) NOT NULL DEFAULT 0,
  `katilim_token` varchar(80) DEFAULT NULL,
  `katilim_token_hash` char(64) DEFAULT NULL,
  `katilim_token_son_kullanim` datetime DEFAULT NULL,
  `katilim_token_iptal_tarihi` datetime DEFAULT NULL,
  `katilim_yaniti` enum('katilacagim','katilamayacagim') DEFAULT NULL,
  `katilim_yanit_tarihi` datetime DEFAULT NULL,
  `aciklama` text DEFAULT NULL,
  `olusturan_kullanici_id` bigint(20) unsigned NOT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tenant_3a6265c4164765e5471f` (`kurum_id`,`id`),
  UNIQUE KEY `uq_randevu_katilim_token` (`katilim_token`),
  UNIQUE KEY `uq_randevu_katilim_token_hash` (`katilim_token_hash`),
  KEY `fk_randevular_ogrenci` (`ogrenci_id`),
  KEY `fk_randevular_veli` (`veli_id`),
  KEY `fk_randevular_grup` (`grup_id`),
  KEY `fk_randevular_paket` (`paket_id`),
  KEY `fk_randevular_paket_disi` (`paket_disi_hak_id`),
  KEY `fk_randevular_telafi` (`telafi_hakki_id`),
  KEY `fk_randevular_ogretmen` (`ogretmen_id`),
  KEY `fk_randevular_kullanici` (`olusturan_kullanici_id`),
  KEY `idx_randevular_kurum` (`kurum_id`),
  KEY `idx_randevular_kurum_tarih_saat` (`kurum_id`,`tarih`,`baslangic_saati`),
  KEY `idx_randevular_kurum_durum` (`kurum_id`,`durum`),
  KEY `idx_randevular_kurum_ogrenci_tarih_durum` (`kurum_id`,`ogrenci_id`,`tarih`,`durum`),
  KEY `fk_tenant_4f21fef82143b9f86c3d` (`kurum_id`,`paket_id`),
  KEY `fk_tenant_ea874428d1692dc2d4f5` (`kurum_id`,`telafi_hakki_id`),
  KEY `fk_tenant_c95ada6ecf731c4f5708` (`kurum_id`,`paket_disi_hak_id`),
  CONSTRAINT `fk_kurum_0100721ac6f948aad82c` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`),
  CONSTRAINT `fk_randevular_grup` FOREIGN KEY (`grup_id`) REFERENCES `gruplar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_randevular_kullanici` FOREIGN KEY (`olusturan_kullanici_id`) REFERENCES `kullanicilar` (`id`),
  CONSTRAINT `fk_randevular_ogrenci` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`),
  CONSTRAINT `fk_randevular_ogretmen` FOREIGN KEY (`ogretmen_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_randevular_paket` FOREIGN KEY (`paket_id`) REFERENCES `paketler` (`id`),
  CONSTRAINT `fk_randevular_paket_disi` FOREIGN KEY (`paket_disi_hak_id`) REFERENCES `paket_disi_haklar` (`id`),
  CONSTRAINT `fk_randevular_telafi` FOREIGN KEY (`telafi_hakki_id`) REFERENCES `telafi_haklari` (`id`),
  CONSTRAINT `fk_randevular_veli` FOREIGN KEY (`veli_id`) REFERENCES `veliler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tenant_4f21fef82143b9f86c3d` FOREIGN KEY (`kurum_id`, `paket_id`) REFERENCES `paketler` (`kurum_id`, `id`),
  CONSTRAINT `fk_tenant_9e1aff3a7d16def20101` FOREIGN KEY (`kurum_id`, `ogrenci_id`) REFERENCES `ogrenciler` (`kurum_id`, `id`),
  CONSTRAINT `fk_tenant_c95ada6ecf731c4f5708` FOREIGN KEY (`kurum_id`, `paket_disi_hak_id`) REFERENCES `paket_disi_haklar` (`kurum_id`, `id`),
  CONSTRAINT `fk_tenant_ea874428d1692dc2d4f5` FOREIGN KEY (`kurum_id`, `telafi_hakki_id`) REFERENCES `telafi_haklari` (`kurum_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sms_kayitlari` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sablon_anahtari` varchar(100) DEFAULT NULL,
  `olay_tipi` varchar(100) NOT NULL DEFAULT 'manuel_sms',
  `alici_tipi` enum('veli','ogrenci','manuel') NOT NULL DEFAULT 'manuel',
  `alici_id` bigint(20) unsigned DEFAULT NULL,
  `ogrenci_id` bigint(20) unsigned DEFAULT NULL,
  `veli_id` bigint(20) unsigned DEFAULT NULL,
  `grup_id` bigint(20) unsigned DEFAULT NULL,
  `randevu_id` bigint(20) unsigned DEFAULT NULL,
  `odeme_id` bigint(20) unsigned DEFAULT NULL,
  `odeme_sozu_id` bigint(20) unsigned DEFAULT NULL,
  `telefon_orijinal` varchar(60) DEFAULT NULL,
  `telefon` varchar(20) NOT NULL,
  `mesaj` text NOT NULL,
  `parca_sayisi` smallint(5) unsigned NOT NULL DEFAULT 1,
  `durum` varchar(30) NOT NULL DEFAULT 'bekliyor',
  `mukerrer_anahtari` varchar(190) DEFAULT NULL,
  `deneme_sayisi` smallint(5) unsigned NOT NULL DEFAULT 0,
  `sonraki_deneme_tarihi` datetime DEFAULT NULL,
  `provider` varchar(50) NOT NULL DEFAULT 'netgsm',
  `provider_islem_no` varchar(120) DEFAULT NULL,
  `provider_cevabi` text DEFAULT NULL,
  `hata_mesaji` text DEFAULT NULL,
  `gonderilme_tarihi` datetime DEFAULT NULL,
  `teslim_tarihi` datetime DEFAULT NULL,
  `iptal_tarihi` datetime DEFAULT NULL,
  `iptal_eden_kullanici_id` bigint(20) unsigned DEFAULT NULL,
  `olusturan_kullanici_id` bigint(20) unsigned DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tenant_77ff058acf29fbe57f40` (`kurum_id`,`id`),
  UNIQUE KEY `uq_sms_kayitlari_kurum_mukerrer` (`kurum_id`,`mukerrer_anahtari`),
  KEY `idx_sms_durum_deneme` (`durum`,`sonraki_deneme_tarihi`),
  KEY `idx_sms_telefon` (`telefon`),
  KEY `idx_sms_ogrenci` (`ogrenci_id`),
  KEY `idx_sms_randevu` (`randevu_id`),
  KEY `idx_sms_odeme` (`odeme_id`),
  KEY `fk_sms_veli` (`veli_id`),
  KEY `fk_sms_grup` (`grup_id`),
  KEY `fk_sms_odeme_sozu` (`odeme_sozu_id`),
  KEY `fk_sms_iptal_kullanici` (`iptal_eden_kullanici_id`),
  KEY `fk_sms_olusturan_kullanici` (`olusturan_kullanici_id`),
  KEY `idx_sms_kayitlari_kurum` (`kurum_id`),
  CONSTRAINT `fk_kurum_edbd3c79dddfcf465ead` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`),
  CONSTRAINT `fk_sms_grup` FOREIGN KEY (`grup_id`) REFERENCES `gruplar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sms_iptal_kullanici` FOREIGN KEY (`iptal_eden_kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sms_odeme` FOREIGN KEY (`odeme_id`) REFERENCES `odemeler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sms_odeme_sozu` FOREIGN KEY (`odeme_sozu_id`) REFERENCES `odeme_sozleri` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sms_ogrenci` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sms_olusturan_kullanici` FOREIGN KEY (`olusturan_kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sms_randevu` FOREIGN KEY (`randevu_id`) REFERENCES `randevular` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sms_veli` FOREIGN KEY (`veli_id`) REFERENCES `veliler` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sms_olay_kayitlari` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sms_kaydi_id` bigint(20) unsigned NOT NULL,
  `eski_durum` varchar(30) DEFAULT NULL,
  `yeni_durum` varchar(30) NOT NULL,
  `mesaj` text DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sms_olay_sms` (`sms_kaydi_id`),
  KEY `idx_sms_olay_kayitlari_kurum` (`kurum_id`),
  KEY `fk_tenant_51353997b7c1ffa399d5` (`kurum_id`,`sms_kaydi_id`),
  CONSTRAINT `fk_kurum_a1ea0e1b53179f7ab865` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`),
  CONSTRAINT `fk_sms_olay_sms` FOREIGN KEY (`sms_kaydi_id`) REFERENCES `sms_kayitlari` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tenant_51353997b7c1ffa399d5` FOREIGN KEY (`kurum_id`, `sms_kaydi_id`) REFERENCES `sms_kayitlari` (`kurum_id`, `id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sms_sablonlari` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `anahtar` varchar(100) NOT NULL,
  `baslik` varchar(190) NOT NULL,
  `mesaj` text NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `otomatik_gonderim` tinyint(1) NOT NULL DEFAULT 0,
  `onay_durumu` varchar(30) NOT NULL DEFAULT 'kullanilabilir',
  `onay_notu` text DEFAULT NULL,
  `son_onay_tarihi` datetime DEFAULT NULL,
  `netgsm_onay_id` varchar(120) DEFAULT NULL,
  `aciklama` text DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sms_sablonlari_kurum_anahtar` (`kurum_id`,`anahtar`),
  KEY `idx_sms_sablonlari_kurum` (`kurum_id`),
  CONSTRAINT `fk_kurum_c91aa6d0788a7c2298c0` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_activity_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `activity_id` bigint(20) unsigned NOT NULL,
  `completed_at` date NOT NULL,
  `source_type` varchar(30) NOT NULL DEFAULT 'manual',
  `randevu_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_student_activity_records_student_activity` (`student_id`,`activity_id`),
  KEY `idx_student_activity_records_completed` (`completed_at`),
  KEY `idx_student_activity_records_activity` (`activity_id`),
  KEY `idx_student_activity_records_randevu` (`randevu_id`),
  KEY `idx_student_activity_records_source` (`source_type`),
  KEY `idx_student_activity_records_kurum` (`kurum_id`),
  KEY `fk_tenant_ee97d8e94e118acbb1db` (`kurum_id`,`student_id`),
  KEY `fk_tenant_29fc2d38f71641f9e2d3` (`kurum_id`,`activity_id`),
  CONSTRAINT `fk_kurum_b3eb42f3e68ded88f453` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`),
  CONSTRAINT `fk_student_activity_records_activity` FOREIGN KEY (`activity_id`) REFERENCES `theme_activities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_student_activity_records_randevu` FOREIGN KEY (`randevu_id`) REFERENCES `randevular` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_student_activity_records_student` FOREIGN KEY (`student_id`) REFERENCES `ogrenciler` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tenant_29fc2d38f71641f9e2d3` FOREIGN KEY (`kurum_id`, `activity_id`) REFERENCES `theme_activities` (`kurum_id`, `id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tenant_ee97d8e94e118acbb1db` FOREIGN KEY (`kurum_id`, `student_id`) REFERENCES `ogrenciler` (`kurum_id`, `id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `telafi_haklari` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ogrenci_id` bigint(20) unsigned NOT NULL,
  `paket_id` bigint(20) unsigned DEFAULT NULL,
  `kaynak_randevu_id` bigint(20) unsigned DEFAULT NULL,
  `durum` enum('planlanmayi_bekliyor','planlandi','kullanildi','iptal') NOT NULL DEFAULT 'planlanmayi_bekliyor',
  `son_kullanim_tarihi` date DEFAULT NULL,
  `aciklama` text DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tenant_0073638202d00db76b9c` (`kurum_id`,`id`),
  KEY `fk_telafi_haklari_ogrenci` (`ogrenci_id`),
  KEY `fk_telafi_haklari_paket` (`paket_id`),
  KEY `fk_telafi_haklari_kaynak_randevu` (`kaynak_randevu_id`),
  KEY `idx_telafi_haklari_kurum` (`kurum_id`),
  KEY `fk_tenant_dcb786e79aca4cd72494` (`kurum_id`,`ogrenci_id`),
  KEY `fk_tenant_e5629ac9ededec610204` (`kurum_id`,`paket_id`),
  CONSTRAINT `fk_kurum_639dc90f58989b915378` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`),
  CONSTRAINT `fk_telafi_haklari_kaynak_randevu` FOREIGN KEY (`kaynak_randevu_id`) REFERENCES `randevular` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_telafi_haklari_ogrenci` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`),
  CONSTRAINT `fk_telafi_haklari_paket` FOREIGN KEY (`paket_id`) REFERENCES `paketler` (`id`),
  CONSTRAINT `fk_tenant_dcb786e79aca4cd72494` FOREIGN KEY (`kurum_id`, `ogrenci_id`) REFERENCES `ogrenciler` (`kurum_id`, `id`),
  CONSTRAINT `fk_tenant_e5629ac9ededec610204` FOREIGN KEY (`kurum_id`, `paket_id`) REFERENCES `paketler` (`kurum_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `telafi_onerileri` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `telafi_hakki_id` bigint(20) unsigned NOT NULL,
  `onerilen_tarih` date NOT NULL,
  `baslangic_saati` time NOT NULL,
  `bitis_saati` time NOT NULL,
  `grup_id` bigint(20) unsigned DEFAULT NULL,
  `ogretmen_id` bigint(20) unsigned DEFAULT NULL,
  `durum` enum('onerildi','kabul_edildi','reddedildi','iptal') NOT NULL DEFAULT 'onerildi',
  `aciklama` text DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_telafi_onerileri_telafi` (`telafi_hakki_id`),
  KEY `fk_telafi_onerileri_grup` (`grup_id`),
  KEY `fk_telafi_onerileri_ogretmen` (`ogretmen_id`),
  KEY `idx_telafi_onerileri_kurum` (`kurum_id`),
  KEY `fk_tenant_5a827831934521850adc` (`kurum_id`,`telafi_hakki_id`),
  CONSTRAINT `fk_kurum_36134b6279463692ca99` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`),
  CONSTRAINT `fk_telafi_onerileri_grup` FOREIGN KEY (`grup_id`) REFERENCES `gruplar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_telafi_onerileri_ogretmen` FOREIGN KEY (`ogretmen_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_telafi_onerileri_telafi` FOREIGN KEY (`telafi_hakki_id`) REFERENCES `telafi_haklari` (`id`),
  CONSTRAINT `fk_tenant_5a827831934521850adc` FOREIGN KEY (`kurum_id`, `telafi_hakki_id`) REFERENCES `telafi_haklari` (`kurum_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `theme_activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `theme_id` bigint(20) unsigned NOT NULL,
  `activity_template_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tenant_2f83b1ef7d4c9402e2b6` (`kurum_id`,`id`),
  KEY `idx_theme_activities_theme` (`theme_id`),
  KEY `idx_theme_activities_template` (`activity_template_id`),
  KEY `idx_theme_activities_kurum` (`kurum_id`),
  KEY `fk_tenant_4f299d3fa819b210d2ff` (`kurum_id`,`theme_id`),
  CONSTRAINT `fk_kurum_722a44174f3719d1591c` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`),
  CONSTRAINT `fk_tenant_4f299d3fa819b210d2ff` FOREIGN KEY (`kurum_id`, `theme_id`) REFERENCES `weekly_themes` (`kurum_id`, `id`) ON DELETE CASCADE,
  CONSTRAINT `fk_theme_activities_template` FOREIGN KEY (`activity_template_id`) REFERENCES `activity_templates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_theme_activities_theme` FOREIGN KEY (`theme_id`) REFERENCES `weekly_themes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `theme_activity_groups` (
  `activity_id` bigint(20) unsigned NOT NULL,
  `group_id` bigint(20) unsigned NOT NULL,
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`activity_id`,`group_id`),
  KEY `fk_theme_activity_groups_group` (`group_id`),
  KEY `idx_theme_activity_groups_kurum` (`kurum_id`),
  KEY `fk_tenant_f4b4c882f24e2c7eb54f` (`kurum_id`,`activity_id`),
  KEY `fk_tenant_1d31361ceb021c67ec4c` (`kurum_id`,`group_id`),
  CONSTRAINT `fk_kurum_a944b50730272252677e` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`),
  CONSTRAINT `fk_tenant_1d31361ceb021c67ec4c` FOREIGN KEY (`kurum_id`, `group_id`) REFERENCES `gruplar` (`kurum_id`, `id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tenant_f4b4c882f24e2c7eb54f` FOREIGN KEY (`kurum_id`, `activity_id`) REFERENCES `theme_activities` (`kurum_id`, `id`) ON DELETE CASCADE,
  CONSTRAINT `fk_theme_activity_groups_activity` FOREIGN KEY (`activity_id`) REFERENCES `theme_activities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_theme_activity_groups_group` FOREIGN KEY (`group_id`) REFERENCES `gruplar` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `theme_group_preset_groups` (
  `preset_id` bigint(20) unsigned NOT NULL,
  `group_id` bigint(20) unsigned NOT NULL,
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`preset_id`,`group_id`),
  KEY `fk_theme_group_preset_groups_group` (`group_id`),
  KEY `idx_theme_group_preset_groups_kurum` (`kurum_id`),
  KEY `fk_tenant_304419bd7c9319b24e98` (`kurum_id`,`group_id`),
  KEY `fk_tenant_1c72530756ddcdd9749e` (`kurum_id`,`preset_id`),
  CONSTRAINT `fk_kurum_94a0b8b946bedd18c8dc` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`),
  CONSTRAINT `fk_tenant_1c72530756ddcdd9749e` FOREIGN KEY (`kurum_id`, `preset_id`) REFERENCES `theme_group_presets` (`kurum_id`, `id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tenant_304419bd7c9319b24e98` FOREIGN KEY (`kurum_id`, `group_id`) REFERENCES `gruplar` (`kurum_id`, `id`) ON DELETE CASCADE,
  CONSTRAINT `fk_theme_group_preset_groups_group` FOREIGN KEY (`group_id`) REFERENCES `gruplar` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_theme_group_preset_groups_preset` FOREIGN KEY (`preset_id`) REFERENCES `theme_group_presets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `theme_group_presets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(160) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_theme_group_presets_kurum_title` (`kurum_id`,`title`),
  UNIQUE KEY `uq_tenant_2a4cbc8526460495ceec` (`kurum_id`,`id`),
  KEY `idx_theme_group_presets_kurum` (`kurum_id`),
  CONSTRAINT `fk_kurum_111655e5409c999059ce` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `veli_onam_kayitlari` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kurum_id` bigint(20) unsigned NOT NULL,
  `onam_form_ayari_id` bigint(20) unsigned NOT NULL,
  `veli_id` bigint(20) unsigned DEFAULT NULL,
  `ogrenci_id` bigint(20) unsigned DEFAULT NULL,
  `veli_ad_soyad` varchar(190) NOT NULL,
  `veli_telefon` varchar(40) NOT NULL,
  `veli_eposta` varchar(190) DEFAULT NULL,
  `ogrenci_ad_soyad` varchar(190) NOT NULL,
  `ogrenci_dogum_tarihi` date DEFAULT NULL,
  `form_surumu` int(10) unsigned NOT NULL,
  `form_verisi_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`form_verisi_json`)),
  `onam_metni` longtext NOT NULL,
  `dijital_onay` tinyint(1) NOT NULL DEFAULT 1,
  `ip_hash` char(64) DEFAULT NULL,
  `tarayici_bilgisi` varchar(500) DEFAULT NULL,
  `onay_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_veli_onam_kurum_tarih` (`kurum_id`,`onay_tarihi`),
  KEY `idx_veli_onam_telefon` (`kurum_id`,`veli_telefon`),
  KEY `idx_veli_onam_veli` (`veli_id`),
  KEY `idx_veli_onam_ogrenci` (`ogrenci_id`),
  KEY `fk_veli_onam_ayar` (`onam_form_ayari_id`),
  KEY `fk_tenant_d473487082f691bb7c3d` (`kurum_id`,`onam_form_ayari_id`),
  CONSTRAINT `fk_tenant_d473487082f691bb7c3d` FOREIGN KEY (`kurum_id`, `onam_form_ayari_id`) REFERENCES `onam_form_ayarlari` (`kurum_id`, `id`),
  CONSTRAINT `fk_veli_onam_ayar` FOREIGN KEY (`onam_form_ayari_id`) REFERENCES `onam_form_ayarlari` (`id`),
  CONSTRAINT `fk_veli_onam_kurum` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_veli_onam_ogrenci` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_veli_onam_veli` FOREIGN KEY (`veli_id`) REFERENCES `veliler` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `veli_portal_dogrulamalari` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kurum_id` bigint(20) unsigned NOT NULL,
  `telefon_hash` char(64) NOT NULL,
  `kod_hash` varchar(255) NOT NULL,
  `deneme_sayisi` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `son_kullanim_tarihi` datetime NOT NULL,
  `dogrulanma_tarihi` datetime DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_veli_portal_telefon` (`kurum_id`,`telefon_hash`,`olusturulma_tarihi`),
  KEY `idx_veli_portal_temizlik` (`olusturulma_tarihi`),
  CONSTRAINT `fk_veli_portal_dogrulama_kurum` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `veliler` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ad` varchar(100) NOT NULL,
  `soyad` varchar(100) NOT NULL,
  `tc_kimlik_no` varchar(20) DEFAULT NULL,
  `telefon_ulke` varchar(80) DEFAULT NULL,
  `telefon` varchar(40) NOT NULL,
  `yedek_telefon` varchar(40) DEFAULT NULL,
  `eposta` varchar(190) DEFAULT NULL,
  `yakinlik` varchar(50) DEFAULT NULL,
  `il` varchar(100) DEFAULT NULL,
  `ilce` varchar(100) DEFAULT NULL,
  `adres` text DEFAULT NULL,
  `iletisim_referansi` varchar(190) DEFAULT NULL,
  `notlar` text DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tenant_5d6d140771540f090086` (`kurum_id`,`id`),
  KEY `idx_veliler_kurum` (`kurum_id`),
  CONSTRAINT `fk_kurum_1c6dfa2f907c5f96989a` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ogrenci_onam_formlari` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kurum_id` bigint(20) unsigned NOT NULL,
  `ogrenci_id` bigint(20) unsigned NOT NULL,
  `veli_id` bigint(20) unsigned DEFAULT NULL,
  `sablon_kodu` varchar(80) NOT NULL DEFAULT 'gorsel_icerik_kullanim',
  `form_adi` varchar(190) NOT NULL,
  `belge_turu` varchar(30) NOT NULL DEFAULT 'fiziksel',
  `durum` varchar(30) NOT NULL DEFAULT 'olusturuldu',
  `ogrenci_ad_soyad` varchar(190) NOT NULL,
  `ogrenci_tc_kimlik_no` varchar(20) DEFAULT NULL,
  `ogrenci_dogum_tarihi` date DEFAULT NULL,
  `ogrenci_telefon` varchar(40) DEFAULT NULL,
  `veli_ad_soyad` varchar(190) NOT NULL,
  `veli_tc_kimlik_no` varchar(20) DEFAULT NULL,
  `veli_yakinlik` varchar(80) DEFAULT NULL,
  `personel_unvan` varchar(100) NOT NULL,
  `personel_ad_soyad` varchar(190) NOT NULL,
  `form_tarihi` date NOT NULL,
  `olusturan_kullanici_id` bigint(20) unsigned DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_onam_formlari_ogrenci` (`kurum_id`,`ogrenci_id`,`olusturulma_tarihi`),
  KEY `idx_onam_formlari_sablon` (`kurum_id`,`sablon_kodu`),
  KEY `fk_onam_formlari_veli` (`veli_id`),
  KEY `fk_onam_formlari_kullanici` (`olusturan_kullanici_id`),
  CONSTRAINT `fk_onam_formlari_kurum` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_onam_formlari_ogrenci` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_onam_formlari_veli` FOREIGN KEY (`veli_id`) REFERENCES `veliler` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_onam_formlari_kullanici` FOREIGN KEY (`olusturan_kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `weekly_theme_age_groups` (
  `theme_id` bigint(20) unsigned NOT NULL,
  `age_group_id` bigint(20) unsigned NOT NULL,
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`theme_id`,`age_group_id`),
  KEY `fk_weekly_theme_age_groups_age` (`age_group_id`),
  KEY `idx_weekly_theme_age_groups_kurum` (`kurum_id`),
  KEY `fk_tenant_f185267cba5f0bfdc24d` (`kurum_id`,`theme_id`),
  CONSTRAINT `fk_kurum_86d4a288f8b0d5a56d21` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`),
  CONSTRAINT `fk_tenant_f185267cba5f0bfdc24d` FOREIGN KEY (`kurum_id`, `theme_id`) REFERENCES `weekly_themes` (`kurum_id`, `id`) ON DELETE CASCADE,
  CONSTRAINT `fk_weekly_theme_age_groups_age` FOREIGN KEY (`age_group_id`) REFERENCES `age_groups` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_weekly_theme_age_groups_theme` FOREIGN KEY (`theme_id`) REFERENCES `weekly_themes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `weekly_themes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `week_start` date NOT NULL,
  `week_end` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tenant_0efa334ddb4ebe7c1cd8` (`kurum_id`,`id`),
  KEY `idx_weekly_themes_week_start` (`week_start`),
  KEY `idx_weekly_themes_week_end` (`week_end`),
  KEY `idx_weekly_themes_kurum` (`kurum_id`),
  CONSTRAINT `fk_kurum_f344cf9acd230f1b07fa` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `yoklamalar` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `randevu_id` bigint(20) unsigned NOT NULL,
  `ogrenci_id` bigint(20) unsigned NOT NULL,
  `durum` varchar(80) NOT NULL,
  `notlar` text DEFAULT NULL,
  `kaydeden_kullanici_id` bigint(20) unsigned NOT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `kurum_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_yoklamalar_randevu` (`randevu_id`),
  KEY `fk_yoklamalar_ogrenci` (`ogrenci_id`),
  KEY `fk_yoklamalar_kullanici` (`kaydeden_kullanici_id`),
  KEY `idx_yoklamalar_kurum` (`kurum_id`),
  KEY `fk_tenant_14671b630f954a50c7a1` (`kurum_id`,`ogrenci_id`),
  KEY `fk_tenant_7ef65ad0240187ee3e19` (`kurum_id`,`randevu_id`),
  CONSTRAINT `fk_kurum_e9ffb02476f3891a783b` FOREIGN KEY (`kurum_id`) REFERENCES `kurumlar` (`id`),
  CONSTRAINT `fk_tenant_14671b630f954a50c7a1` FOREIGN KEY (`kurum_id`, `ogrenci_id`) REFERENCES `ogrenciler` (`kurum_id`, `id`),
  CONSTRAINT `fk_tenant_7ef65ad0240187ee3e19` FOREIGN KEY (`kurum_id`, `randevu_id`) REFERENCES `randevular` (`kurum_id`, `id`),
  CONSTRAINT `fk_yoklamalar_kullanici` FOREIGN KEY (`kaydeden_kullanici_id`) REFERENCES `kullanicilar` (`id`),
  CONSTRAINT `fk_yoklamalar_ogrenci` FOREIGN KEY (`ogrenci_id`) REFERENCES `ogrenciler` (`id`),
  CONSTRAINT `fk_yoklamalar_randevu` FOREIGN KEY (`randevu_id`) REFERENCES `randevular` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Güvenli başlangıç verileri: yeni kurulumda ilk kurum ve ortak tanımlar.
INSERT INTO kurumlar (ad, kod, aktif, olusturulma_tarihi)
VALUES ('Oyun Evleri', 'TALYA', 1, NOW())
ON DUPLICATE KEY UPDATE ad = VALUES(ad), aktif = VALUES(aktif);

SET @varsayilan_kurum_id := (SELECT id FROM kurumlar WHERE kod = 'TALYA' LIMIT 1);

INSERT INTO roller (kod, ad) VALUES
('kurucu', 'Kurucu'),
('yonetici', 'Yonetici'),
('ogretmen', 'Ogretmen'),
('muhasebe', 'Muhasebe'),
('resepsiyon', 'Resepsiyon')
ON DUPLICATE KEY UPDATE ad = VALUES(ad);

INSERT IGNORE INTO rol_yetkileri (rol_id, yetki)
SELECT r.id, y.yetki FROM roller r JOIN (
  SELECT 'ogrenci_listele' yetki UNION ALL SELECT 'ogrenci_ekle' UNION ALL SELECT 'veli_listele' UNION ALL SELECT 'veli_ekle'
  UNION ALL SELECT 'grup_listele' UNION ALL SELECT 'grup_ekle' UNION ALL SELECT 'paket_listele' UNION ALL SELECT 'paket_ekle'
  UNION ALL SELECT 'odeme_listele' UNION ALL SELECT 'odeme_ekle' UNION ALL SELECT 'randevu_listele' UNION ALL SELECT 'randevu_ekle'
  UNION ALL SELECT 'randevu_durum_degistir' UNION ALL SELECT 'yoklama_listele' UNION ALL SELECT 'rapor_ozet'
  UNION ALL SELECT 'sms_goruntule' UNION ALL SELECT 'sms_gonder' UNION ALL SELECT 'sms_toplu_gonder'
  UNION ALL SELECT 'sms_sablon_yonet' UNION ALL SELECT 'sms_tekrar_gonder' UNION ALL SELECT 'sms_ayar_yonet'
  UNION ALL SELECT 'sms_rapor_goruntule' UNION ALL SELECT 'kullanici_yonet' UNION ALL SELECT 'tema_yonet'
  UNION ALL SELECT 'personel_listele' UNION ALL SELECT 'personel_yonet' UNION ALL SELECT 'fatura_entegrasyon_yonet'
) y WHERE r.kod = 'kurucu';

INSERT IGNORE INTO rol_yetkileri (rol_id, yetki)
SELECT r.id, y.yetki FROM roller r JOIN (
  SELECT 'ogrenci_listele' yetki UNION ALL SELECT 'ogrenci_ekle' UNION ALL SELECT 'veli_listele' UNION ALL SELECT 'veli_ekle'
  UNION ALL SELECT 'grup_listele' UNION ALL SELECT 'grup_ekle' UNION ALL SELECT 'paket_listele' UNION ALL SELECT 'paket_ekle'
  UNION ALL SELECT 'odeme_listele' UNION ALL SELECT 'odeme_ekle' UNION ALL SELECT 'randevu_listele' UNION ALL SELECT 'randevu_ekle'
  UNION ALL SELECT 'randevu_durum_degistir' UNION ALL SELECT 'yoklama_listele' UNION ALL SELECT 'rapor_ozet'
  UNION ALL SELECT 'sms_goruntule' UNION ALL SELECT 'sms_gonder' UNION ALL SELECT 'sms_toplu_gonder'
  UNION ALL SELECT 'sms_sablon_yonet' UNION ALL SELECT 'sms_tekrar_gonder' UNION ALL SELECT 'sms_ayar_yonet'
  UNION ALL SELECT 'sms_rapor_goruntule'
  UNION ALL SELECT 'tema_yonet' UNION ALL SELECT 'personel_listele' UNION ALL SELECT 'personel_yonet'
) y WHERE r.kod = 'yonetici';

INSERT IGNORE INTO rol_yetkileri (rol_id, yetki)
SELECT r.id, y.yetki FROM roller r JOIN (
  SELECT 'ogrenci_listele' yetki UNION ALL SELECT 'veli_listele'
  UNION ALL SELECT 'grup_listele' UNION ALL SELECT 'randevu_listele' UNION ALL SELECT 'randevu_ekle'
  UNION ALL SELECT 'randevu_durum_degistir' UNION ALL SELECT 'yoklama_listele' UNION ALL SELECT 'tema_yonet'
) y WHERE r.kod = 'ogretmen';

INSERT IGNORE INTO rol_yetkileri (rol_id, yetki)
SELECT r.id, y.yetki FROM roller r JOIN (
  SELECT 'paket_listele' yetki UNION ALL SELECT 'odeme_listele' UNION ALL SELECT 'odeme_ekle'
  UNION ALL SELECT 'rapor_ozet' UNION ALL SELECT 'personel_listele'
) y WHERE r.kod = 'muhasebe';

INSERT IGNORE INTO rol_yetkileri (rol_id, yetki)
SELECT r.id, y.yetki FROM roller r JOIN (
  SELECT 'ogrenci_listele' yetki UNION ALL SELECT 'ogrenci_ekle' UNION ALL SELECT 'veli_listele' UNION ALL SELECT 'veli_ekle'
  UNION ALL SELECT 'grup_listele' UNION ALL SELECT 'randevu_listele' UNION ALL SELECT 'randevu_ekle'
  UNION ALL SELECT 'randevu_durum_degistir' UNION ALL SELECT 'sms_goruntule' UNION ALL SELECT 'sms_gonder'
) y WHERE r.kod = 'resepsiyon';

INSERT INTO age_groups (name, sort_order, created_at, updated_at) VALUES
('18-24 Ay', 10, NOW(), NOW()),
('25-36 Ay', 20, NOW(), NOW()),
('37-48 Ay', 30, NOW(), NOW())
ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order), updated_at = NOW();

INSERT INTO ayarlar (kurum_id, anahtar, deger, aciklama) VALUES
(@varsayilan_kurum_id, 'otomatik_gelmedi_bekleme_dakika', '60', 'Planli randevular icin otomatik gelmedi bekleme suresi.'),
(@varsayilan_kurum_id, 'kurum_adi', 'Oyun Evleri', 'Panelde kullanilan kurum adi.'),
(@varsayilan_kurum_id, 'sms_appointment_reminder_enabled', '1', 'Randevu hatirlatma SMS otomasyonu aktiflik bilgisi.'),
(@varsayilan_kurum_id, 'sms_appointment_reminder_days_before', '1', 'Randevudan kac gun once hatirlatma SMS kuyruga alinacak.'),
(@varsayilan_kurum_id, 'sms_appointment_reminder_time', '14:00', 'Hatirlatma SMS kuyruga alma saati.'),
(@varsayilan_kurum_id, 'sms_birthday_message_enabled', '1', 'Dogum gunu SMS otomasyonu aktiflik bilgisi.'),
(@varsayilan_kurum_id, 'sms_birthday_message_time', '09:00', 'Dogum gunu SMS kuyruga alma saati.')
ON DUPLICATE KEY UPDATE deger = VALUES(deger), aciklama = VALUES(aciklama);

INSERT IGNORE INTO sms_sablonlari
  (kurum_id, anahtar, baslik, mesaj, aktif, otomatik_gonderim, onay_durumu, aciklama)
SELECT @varsayilan_kurum_id, s.anahtar, s.baslik, s.mesaj, 1, s.otomatik_gonderim, 'kullanilabilir', s.aciklama
FROM (
  SELECT 'randevu_olusturuldu' anahtar, 'Randevu Olusturuldu' baslik, 'Sayin {veli_adi}, {ogrenci_adi} icin {paket_adi} randevulariniz olusturuldu: {randevu_listesi}. {kurum_adi}' mesaj, 1 otomatik_gonderim, 'Randevu olusturulunca kuyruga eklenir.' aciklama
  UNION ALL SELECT 'randevu_hatirlatma', 'Randevu Hatirlatma', 'Sayin {veli_adi}, {ogrenci_adi} icin {tarih} {saat} tarihinde {paket_adi} randevunuz bulunmaktadir. Katilim durumunuz: {katilim_linki} {kurum_adi}', 1, 'Standart randevu hatirlatmasi.'
  UNION ALL SELECT 'tanisma_dersi_hatirlatma', 'Tanisma Dersi Hatirlatma', 'Sayin {veli_adi}, {ogrenci_adi} icin tanisma dersiniz {tarih} {saat} tarihinde planlanmistir. Katilim durumunuz: {katilim_linki} {kurum_adi}', 1, 'Tek derslik tanisma hatirlatmasi.'
  UNION ALL SELECT 'veli_gorusmesi_hatirlatma', 'Veli Gorusmesi Hatirlatma', 'Sayin {veli_adi}, veli gorusmeniz {tarih} {saat} tarihinde planlanmistir. Katilim durumunuz: {katilim_linki} {kurum_adi}', 1, 'Veli gorusmesi hatirlatmasi.'
  UNION ALL SELECT 'workshop_hatirlatma', 'Workshop Hatirlatma', 'Sayin {veli_adi}, {ogrenci_adi} icin workshop etkinligi {tarih} {saat} tarihinde planlanmistir. Katilim durumunuz: {katilim_linki} {kurum_adi}', 1, 'Workshop hatirlatmasi.'
  UNION ALL SELECT 'odeme_alindi', 'Odeme Alindi', 'Sayin {veli_adi}, {ogrenci_adi} icin {odeme_tutari} tutarindaki odemeniz alinmistir. Kalan borc: {kalan_borc}. {kurum_adi}', 1, 'Tahsilat sonrasi bilgilendirme.'
  UNION ALL SELECT 'odeme_sozu_hatirlatma', 'Odeme Sozu Hatirlatma', 'Sayin {veli_adi}, {ogrenci_adi} icin {odeme_sozu_tarihi} tarihli {odeme_tutari} odeme sozunuz bulunmaktadir. {kurum_adi}', 1, 'Odeme sozu hatirlatmasi.'
  UNION ALL SELECT 'geciken_odeme', 'Geciken Odeme', 'Sayin {veli_adi}, {ogrenci_adi} icin {kalan_borc} tutarinda gecikmis odeme gorunmektedir. {kurum_adi}', 0, 'Manuel veya rapordan gonderilebilir.'
  UNION ALL SELECT 'paket_bitiyor', 'Paket Bitiyor', 'Sayin {veli_adi}, {ogrenci_adi} icin {paket_adi} paketiniz {tarih} tarihinde bitiyor. Kayit yenileme icin bizimle iletisime gecebilirsiniz. {kurum_adi}', 0, 'Paket yenileme bilgilendirmesi.'
  UNION ALL SELECT 'manuel_sms', 'Manuel SMS', '{mesaj}', 0, 'Manuel SMS metni.'
  UNION ALL SELECT 'randevu_guncellendi', 'Randevu Guncellendi', 'Sayin {veli_adi}, {ogrenci_adi} icin {eski_tarih} {eski_saat} randevunuz {tarih} {saat} olarak guncellenmistir. {paket_adi} - {kurum_adi}', 1, 'Randevu guncellendiginde veliye gonderilir.'
  UNION ALL SELECT 'dogum_gunu', 'Dogum Gunu', 'Sayin {veli_adi}, {ogrenci_adi} icin mutlu ve saglikli yaslar dileriz. {kurum_adi}', 1, 'Dogum gunu olan aktif ogrenciler icin otomatik SMS.'
  UNION ALL SELECT 'telafi_dersi_olusturuldu', 'Telafi Dersi Olusturuldu', 'Sayin {veli_adi}, {ogrenci_adi} icin {kaynak_tarih} {kaynak_saat} tarihli dersin telafisi {tarih} {saat} olarak planlanmistir. {kurum_adi}', 1, 'Telafi dersi planlandiginda veliye gonderilir.'
) s;

INSERT INTO activity_templates (kurum_id, title, description, is_active, created_at, updated_at) VALUES
(@varsayilan_kurum_id, 'Parmak Boyasi', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Kesme ve Yapistirma', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Duyusal Oyun', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Renk Eslestirme', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Nesne Eslestirme', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Puzzle Calismasi', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Muzik ve Ritim', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Hikaye Zamani', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Blok Calismasi', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Serbest Boyama', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Renk Avi', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Doku Kesfi', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Buyuk-Kucuk Eslestirme', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Dolu-Bos Kavrami', NULL, 1, NOW(), NOW()),
(@varsayilan_kurum_id, 'Ince Motor Calismasi', NULL, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE is_active = VALUES(is_active), updated_at = NOW();
