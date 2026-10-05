SET NAMES utf8mb4;

-- Eski kayitlarda varsayilan kurum 1'e dusmus kurum bilgisini guvenilir
-- ust kayitlardan yeniden kur.
UPDATE islem_kayitlari ik
INNER JOIN kullanicilar k ON k.id = ik.kullanici_id
SET ik.kurum_id = k.kurum_id
WHERE ik.kurum_id <> k.kurum_id;

UPDATE odeme_sozleri os
INNER JOIN paketler p ON p.id = os.paket_id
SET os.kurum_id = p.kurum_id
WHERE os.kurum_id <> p.kurum_id;

UPDATE hak_hareketleri hh
INNER JOIN paketler p ON p.id = hh.paket_id
SET hh.kurum_id = p.kurum_id
WHERE hh.kurum_id <> p.kurum_id;

UPDATE sms_kayitlari sk
LEFT JOIN randevular r ON r.id = sk.randevu_id
LEFT JOIN odemeler od ON od.id = sk.odeme_id
LEFT JOIN odeme_sozleri os ON os.id = sk.odeme_sozu_id
LEFT JOIN ogrenciler o ON o.id = sk.ogrenci_id
LEFT JOIN veliler v ON v.id = sk.veli_id
LEFT JOIN gruplar g ON g.id = sk.grup_id
SET sk.kurum_id = COALESCE(r.kurum_id, od.kurum_id, os.kurum_id, o.kurum_id, v.kurum_id, g.kurum_id, sk.kurum_id)
WHERE sk.kurum_id <> COALESCE(r.kurum_id, od.kurum_id, os.kurum_id, o.kurum_id, v.kurum_id, g.kurum_id, sk.kurum_id);

UPDATE sms_olay_kayitlari so
INNER JOIN sms_kayitlari sk ON sk.id = so.sms_kaydi_id
SET so.kurum_id = sk.kurum_id
WHERE so.kurum_id <> sk.kurum_id;

-- Gecici test kurumlarindan kalmis ve gercek bir kuruma baglanamayan ayarlar.
DELETE a
FROM ayarlar a
LEFT JOIN kurumlar k ON k.id = a.kurum_id
WHERE k.id IS NULL;

-- SMS mukerrerligi kurum icinde uygulanmali; farkli kurumlar ayni anahtari
-- kullanabilmelidir.
SET @sms_eski_tekil := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'sms_kayitlari'
    AND INDEX_NAME = 'sms_mukerrer_unique'
);
SET @sql := IF(
  @sms_eski_tekil > 0,
  'ALTER TABLE sms_kayitlari DROP INDEX sms_mukerrer_unique',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sms_kurum_tekil := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'sms_kayitlari'
    AND INDEX_NAME = 'uq_sms_kayitlari_kurum_mukerrer'
);
SET @sql := IF(
  @sms_kurum_tekil = 0,
  'ALTER TABLE sms_kayitlari ADD UNIQUE KEY uq_sms_kayitlari_kurum_mukerrer (kurum_id, mukerrer_anahtari)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

DELIMITER //

DROP PROCEDURE IF EXISTS talya_kurum_kolonunu_sikilastir//
CREATE PROCEDURE talya_kurum_kolonunu_sikilastir(IN tablo_adi VARCHAR(64))
BEGIN
  DECLARE fk_sayisi INT DEFAULT 0;
  DECLARE yetim_sayisi INT DEFAULT 0;
  DECLARE fk_adi VARCHAR(64);

  SET @sql := CONCAT(
    'SELECT COUNT(*) INTO @talya_yetim FROM `', tablo_adi,
    '` t LEFT JOIN kurumlar k ON k.id=t.kurum_id WHERE k.id IS NULL'
  );
  PREPARE stmt FROM @sql;
  EXECUTE stmt;
  DEALLOCATE PREPARE stmt;
  SET yetim_sayisi = COALESCE(@talya_yetim, 0);

  IF yetim_sayisi > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Kurum izolasyonu uygulanamadi: kurumsuz kayit bulundu.';
  END IF;

  SET @sql := CONCAT(
    'ALTER TABLE `', tablo_adi,
    '` MODIFY COLUMN kurum_id BIGINT UNSIGNED NOT NULL'
  );
  PREPARE stmt FROM @sql;
  EXECUTE stmt;
  DEALLOCATE PREPARE stmt;

  SELECT COUNT(*) INTO fk_sayisi
  FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = tablo_adi
    AND COLUMN_NAME = 'kurum_id'
    AND REFERENCED_TABLE_NAME = 'kurumlar';

  IF fk_sayisi = 0 THEN
    SET fk_adi = CONCAT('fk_kurum_', LEFT(MD5(tablo_adi), 20));
    SET @sql := CONCAT(
      'ALTER TABLE `', tablo_adi, '` ADD CONSTRAINT `', fk_adi,
      '` FOREIGN KEY (kurum_id) REFERENCES kurumlar(id) ON DELETE RESTRICT'
    );
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END//

DROP PROCEDURE IF EXISTS talya_bilesik_kurum_fk_ekle//
CREATE PROCEDURE talya_bilesik_kurum_fk_ekle()
BEGIN
  DECLARE bitti INT DEFAULT 0;
  DECLARE alt_tablo VARCHAR(64);
  DECLARE alt_kolon VARCHAR(64);
  DECLARE ust_tablo VARCHAR(64);
  DECLARE ust_kolon VARCHAR(64);
  DECLARE silme_kurali VARCHAR(20);
  DECLARE indeks_sayisi INT DEFAULT 0;
  DECLARE fk_sayisi INT DEFAULT 0;
  DECLARE indeks_adi VARCHAR(64);
  DECLARE fk_adi VARCHAR(64);

  DECLARE iliskiler CURSOR FOR
    SELECT k.TABLE_NAME, k.COLUMN_NAME, k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME, rc.DELETE_RULE
    FROM information_schema.KEY_COLUMN_USAGE k
    INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
      ON rc.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
     AND rc.CONSTRAINT_NAME = k.CONSTRAINT_NAME
     AND rc.TABLE_NAME = k.TABLE_NAME
    INNER JOIN information_schema.COLUMNS alt_kurum
      ON alt_kurum.TABLE_SCHEMA = k.TABLE_SCHEMA
     AND alt_kurum.TABLE_NAME = k.TABLE_NAME
     AND alt_kurum.COLUMN_NAME = 'kurum_id'
    INNER JOIN information_schema.COLUMNS ust_kurum
      ON ust_kurum.TABLE_SCHEMA = k.TABLE_SCHEMA
     AND ust_kurum.TABLE_NAME = k.REFERENCED_TABLE_NAME
     AND ust_kurum.COLUMN_NAME = 'kurum_id'
    WHERE k.TABLE_SCHEMA = DATABASE()
      AND k.REFERENCED_TABLE_NAME IS NOT NULL
      AND k.COLUMN_NAME <> 'kurum_id'
      AND k.REFERENCED_TABLE_NAME <> 'kullanicilar'
      AND rc.DELETE_RULE <> 'SET NULL'
      AND (
        SELECT COUNT(*)
        FROM information_schema.KEY_COLUMN_USAGE kc
        WHERE kc.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
          AND kc.CONSTRAINT_NAME = k.CONSTRAINT_NAME
          AND kc.TABLE_NAME = k.TABLE_NAME
      ) = 1;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET bitti = 1;

  OPEN iliskiler;
  iliski_dongusu: LOOP
    FETCH iliskiler INTO alt_tablo, alt_kolon, ust_tablo, ust_kolon, silme_kurali;
    IF bitti = 1 THEN
      LEAVE iliski_dongusu;
    END IF;

    SET indeks_adi = CONCAT('uq_tenant_', LEFT(MD5(CONCAT(ust_tablo, ':', ust_kolon)), 20));
    SELECT COUNT(*) INTO indeks_sayisi
    FROM (
      SELECT INDEX_NAME,
             GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS kolonlar,
             MIN(NON_UNIQUE) AS tekil_degil
      FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ust_tablo
      GROUP BY INDEX_NAME
    ) indeksler
    WHERE indeksler.kolonlar = CONCAT('kurum_id,', ust_kolon)
      AND indeksler.tekil_degil = 0;

    IF indeks_sayisi = 0 THEN
      SET @sql := CONCAT(
        'ALTER TABLE `', ust_tablo, '` ADD UNIQUE KEY `', indeks_adi,
        '` (kurum_id, `', ust_kolon, '`)'
      );
      PREPARE stmt FROM @sql;
      EXECUTE stmt;
      DEALLOCATE PREPARE stmt;
    END IF;

    SET fk_adi = CONCAT('fk_tenant_', LEFT(MD5(CONCAT(alt_tablo, ':', alt_kolon, ':', ust_tablo)), 20));
    SELECT COUNT(*) INTO fk_sayisi
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = fk_adi;

    IF fk_sayisi = 0 THEN
      SET @sql := CONCAT(
        'ALTER TABLE `', alt_tablo, '` ADD CONSTRAINT `', fk_adi,
        '` FOREIGN KEY (kurum_id, `', alt_kolon, '`) REFERENCES `', ust_tablo,
        '` (kurum_id, `', ust_kolon, '`) ON DELETE ', silme_kurali
      );
      PREPARE stmt FROM @sql;
      EXECUTE stmt;
      DEALLOCATE PREPARE stmt;
    END IF;
  END LOOP;
  CLOSE iliskiler;
END//

DELIMITER ;

CALL talya_kurum_kolonunu_sikilastir('activity_templates');
CALL talya_kurum_kolonunu_sikilastir('ayarlar');
CALL talya_kurum_kolonunu_sikilastir('bekleyen_veliler');
CALL talya_kurum_kolonunu_sikilastir('bildirimler');
CALL talya_kurum_kolonunu_sikilastir('ders_programlari');
CALL talya_kurum_kolonunu_sikilastir('giderler');
CALL talya_kurum_kolonunu_sikilastir('gruplar');
CALL talya_kurum_kolonunu_sikilastir('grup_ogrencileri');
CALL talya_kurum_kolonunu_sikilastir('gunluk_notlar');
CALL talya_kurum_kolonunu_sikilastir('hak_hareketleri');
CALL talya_kurum_kolonunu_sikilastir('hizmetler');
CALL talya_kurum_kolonunu_sikilastir('islem_kayitlari');
CALL talya_kurum_kolonunu_sikilastir('kasalar');
CALL talya_kurum_kolonunu_sikilastir('kasa_hareketleri');
CALL talya_kurum_kolonunu_sikilastir('kullanicilar');
CALL talya_kurum_kolonunu_sikilastir('odemeler');
CALL talya_kurum_kolonunu_sikilastir('odeme_sozleri');
CALL talya_kurum_kolonunu_sikilastir('ogrenciler');
CALL talya_kurum_kolonunu_sikilastir('ogrenci_kara_liste');
CALL talya_kurum_kolonunu_sikilastir('ogrenci_velileri');
CALL talya_kurum_kolonunu_sikilastir('paketler');
CALL talya_kurum_kolonunu_sikilastir('paket_disi_haklar');
CALL talya_kurum_kolonunu_sikilastir('randevular');
CALL talya_kurum_kolonunu_sikilastir('sms_kayitlari');
CALL talya_kurum_kolonunu_sikilastir('sms_olay_kayitlari');
CALL talya_kurum_kolonunu_sikilastir('sms_sablonlari');
CALL talya_kurum_kolonunu_sikilastir('student_activity_records');
CALL talya_kurum_kolonunu_sikilastir('telafi_haklari');
CALL talya_kurum_kolonunu_sikilastir('telafi_onerileri');
CALL talya_kurum_kolonunu_sikilastir('theme_activities');
CALL talya_kurum_kolonunu_sikilastir('theme_activity_groups');
CALL talya_kurum_kolonunu_sikilastir('theme_group_presets');
CALL talya_kurum_kolonunu_sikilastir('theme_group_preset_groups');
CALL talya_kurum_kolonunu_sikilastir('veliler');
CALL talya_kurum_kolonunu_sikilastir('weekly_themes');
CALL talya_kurum_kolonunu_sikilastir('weekly_theme_age_groups');
CALL talya_kurum_kolonunu_sikilastir('yoklamalar');

CALL talya_bilesik_kurum_fk_ekle();

DROP PROCEDURE IF EXISTS talya_bilesik_kurum_fk_ekle;
DROP PROCEDURE IF EXISTS talya_kurum_kolonunu_sikilastir;
