UPDATE randevular r
INNER JOIN (
  SELECT r2.id AS randevu_id, MIN(dp.grup_id) AS grup_id
  FROM randevular r2
  INNER JOIN ders_programlari dp
    ON dp.aktif = 1
   AND dp.gun = WEEKDAY(r2.tarih) + 1
   AND r2.baslangic_saati >= dp.baslangic_saati
   AND r2.baslangic_saati < dp.bitis_saati
  INNER JOIN gruplar g
    ON g.id = dp.grup_id
   AND g.aktif = 1
  WHERE r2.grup_id IS NULL
  GROUP BY r2.id
) eslesen ON eslesen.randevu_id = r.id
SET r.grup_id = eslesen.grup_id;

INSERT INTO grup_ogrencileri (grup_id, ogrenci_id, baslangic_tarihi, bitis_tarihi, aktif)
SELECT r.grup_id, r.ogrenci_id, MIN(r.tarih), NULL, 1
FROM randevular r
WHERE r.grup_id IS NOT NULL
GROUP BY r.grup_id, r.ogrenci_id
ON DUPLICATE KEY UPDATE bitis_tarihi = NULL, aktif = 1;
