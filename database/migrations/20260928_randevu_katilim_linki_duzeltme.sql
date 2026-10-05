-- Katilim yaniti sadece ders baslangicina kadar degistirilebilir.
-- Link, ders sonrasinda randevu detayini salt okunur gostermeye devam eder.
UPDATE randevular
SET katilim_token_son_kullanim = TIMESTAMP(tarih, baslangic_saati)
WHERE katilim_token_hash IS NOT NULL;
