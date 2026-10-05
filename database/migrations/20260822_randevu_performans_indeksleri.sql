ALTER TABLE randevular
  ADD INDEX idx_randevular_kurum_tarih_saat (kurum_id, tarih, baslangic_saati),
  ADD INDEX idx_randevular_kurum_durum (kurum_id, durum),
  ADD INDEX idx_randevular_kurum_ogrenci_tarih_durum (kurum_id, ogrenci_id, tarih, durum);
