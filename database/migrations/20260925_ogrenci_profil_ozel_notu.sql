DELIMITER //

DROP PROCEDURE IF EXISTS talya_profil_ozel_notu_kolonu_ekle//
CREATE PROCEDURE talya_profil_ozel_notu_kolonu_ekle()
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'ogrenciler'
          AND COLUMN_NAME = 'profil_ozel_notu'
    ) THEN
        ALTER TABLE ogrenciler
            ADD COLUMN profil_ozel_notu VARCHAR(500) NULL AFTER ozel_durum_notu;

        /* İlk örnek notu korur; diğer mevcut açıklamalar özel not sayılmaz. */
        UPDATE ogrenciler
        SET profil_ozel_notu = TRIM(ozel_durum_notu)
        WHERE ozel_durum_notu IS NOT NULL
          AND LOWER(TRIM(TRAILING '.' FROM TRIM(ozel_durum_notu))) IN (
              'haftada 2 ye cikmak istiyor',
              'haftada 2 ye çıkmak istiyor',
              'haftada 2ye cikmak istiyor',
              'haftada 2ye çıkmak istiyor',
              'haftada 2''ye cikmak istiyor',
              'haftada 2''ye çıkmak istiyor'
          );
    END IF;
END//

DELIMITER ;

CALL talya_profil_ozel_notu_kolonu_ekle();
DROP PROCEDURE IF EXISTS talya_profil_ozel_notu_kolonu_ekle;
