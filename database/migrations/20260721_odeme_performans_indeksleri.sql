SET NAMES utf8mb4;

CREATE INDEX idx_paketler_durum_olusturma ON paketler (paket_durumu, olusturulma_tarihi);
CREATE INDEX idx_odemeler_paket_iptal ON odemeler (paket_id, iptal);
