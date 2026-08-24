<?php

declare(strict_types=1);

use App\Core\Veritabani;

require dirname(__DIR__) . '/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Bu komut yalnizca CLI uzerinden calistirilabilir.\n");
    exit(1);
}

if (!in_array('--apply', $argv, true)) {
    fwrite(STDOUT, "Demo kurum olusturmak icin --apply parametresini kullanin.\n");
    exit(0);
}

$demoPassword = (string) getenv('DEMO_PASSWORD');
if (strlen($demoPassword) < 12) {
    fwrite(STDERR, "DEMO_PASSWORD en az 12 karakter olmalidir.\n");
    exit(1);
}

$db = Veritabani::baglan();
$kurumKodu = 'DEMO';
$kurumAdi = 'Renkli Adimlar Demo Oyun Evi';
$demoEposta = 'demo@oyunevleri.local';

$mevcut = $db->prepare('SELECT id FROM kurumlar WHERE kod = :kod LIMIT 1');
$mevcut->execute(['kod' => $kurumKodu]);
if ((int) ($mevcut->fetchColumn() ?: 0) > 0) {
    fwrite(STDOUT, "DEMO kurumu zaten mevcut; yeni veri eklenmedi.\n");
    exit(0);
}

$kolon = $db->query(
    'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "veliler" AND COLUMN_NAME = "iletisim_referansi"'
);
if ((int) $kolon->fetchColumn() === 0) {
    $db->exec('ALTER TABLE veliler ADD COLUMN iletisim_referansi VARCHAR(190) NULL AFTER adres');
}

$db->beginTransaction();

try {
    $kurum = $db->prepare(
        'INSERT INTO kurumlar (ad, kod, veli_portal_anahtari, aktif, olusturulma_tarihi)
         VALUES (:ad, :kod, :anahtar, 1, NOW())'
    );
    $kurum->execute([
        'ad' => $kurumAdi,
        'kod' => $kurumKodu,
        'anahtar' => bin2hex(random_bytes(16)),
    ]);
    $kurumId = (int) $db->lastInsertId();

    $rol = $db->prepare('SELECT id FROM roller WHERE kod = "kurucu" LIMIT 1');
    $rol->execute();
    $rolId = (int) ($rol->fetchColumn() ?: 0);
    if ($rolId < 1) {
        throw new RuntimeException('Kurucu rolu bulunamadi.');
    }

    $kullanici = $db->prepare(
        'INSERT INTO kullanicilar
            (kurum_id, rol_id, ad, soyad, eposta, telefon, sifre, aktif, sistem_yoneticisi, olusturulma_tarihi)
         VALUES
            (:kurum_id, :rol_id, :ad, :soyad, :eposta, :telefon, :sifre, 1, 0, NOW())'
    );
    $kullanici->execute([
        'kurum_id' => $kurumId,
        'rol_id' => $rolId,
        'ad' => 'Demo',
        'soyad' => 'Yoneticisi',
        'eposta' => $demoEposta,
        'telefon' => '0(555) 000 00 00',
        'sifre' => password_hash($demoPassword, PASSWORD_DEFAULT),
    ]);
    $kullaniciId = (int) $db->lastInsertId();

    $hizmet = $db->prepare(
        'INSERT INTO hizmetler
            (kurum_id, hizmet_adi, ucret, haftalik_katilim_sayisi, toplam_normal_hak, toplam_telafi_hak, aktif, olusturulma_tarihi)
         VALUES (:kurum_id, :ad, :ucret, 2, 24, 3, 1, NOW())'
    );
    foreach ([['Oyun Grubu Programi', 8400], ['Duyu ve Sanat Atolyesi', 6200]] as [$ad, $ucret]) {
        $hizmet->execute(['kurum_id' => $kurumId, 'ad' => $ad, 'ucret' => $ucret]);
    }

    $grupEkle = $db->prepare(
        'INSERT INTO gruplar
            (kurum_id, ad, yas_araligi, kontenjan, ogretmen_id, aktif, durum, aciklama, olusturulma_tarihi)
         VALUES (:kurum_id, :ad, :yas, 8, :ogretmen_id, 1, "aktif", :aciklama, NOW())'
    );
    $gruplar = [
        ['Minik Adimlar', '18-30 Ay', 'Pazartesi ve Carsamba sabah grubu'],
        ['Merakli Kasifler', '31-42 Ay', 'Sali ve Persembe ogle grubu'],
        ['Renkli Yildizlar', '43-60 Ay', 'Cuma ve Cumartesi etkinlik grubu'],
    ];
    $grupIdleri = [];
    foreach ($gruplar as [$ad, $yas, $aciklama]) {
        $grupEkle->execute([
            'kurum_id' => $kurumId,
            'ad' => $ad,
            'yas' => $yas,
            'ogretmen_id' => $kullaniciId,
            'aciklama' => $aciklama,
        ]);
        $grupIdleri[] = (int) $db->lastInsertId();
    }

    $veliler = [
        ['Ayse', 'Yilmaz', '0(555) 100 00 01', 'Anne', 'Zeynep Hanım tavsiyesi'],
        ['Mehmet', 'Kaya', '0(555) 100 00 02', 'Baba', 'Instagram'],
        ['Selin', 'Demir', '0(555) 100 00 03', 'Anne', 'Google araması'],
        ['Burak', 'Celik', '0(555) 100 00 04', 'Baba', 'Ayşe Hanım tavsiyesi'],
        ['Elif', 'Sahin', '0(555) 100 00 05', 'Anne', 'Mahalle etkinliği'],
        ['Can', 'Aydin', '0(555) 100 00 06', 'Baba', 'Instagram'],
        ['Derya', 'Arslan', '0(555) 100 00 07', 'Anne', 'Mevcut veli tavsiyesi'],
        ['Emre', 'Koc', '0(555) 100 00 08', 'Baba', 'Google araması'],
        ['Gizem', 'Polat', '0(555) 100 00 09', 'Anne', 'Çocuk doktoru tavsiyesi'],
        ['Hakan', 'Kurt', '0(555) 100 00 10', 'Baba', 'Instagram'],
        ['Irem', 'Aksoy', '0(555) 100 00 11', 'Anne', 'Arkadaş tavsiyesi'],
        ['Kerem', 'Eren', '0(555) 100 00 12', 'Baba', 'Açık kapı etkinliği'],
    ];
    $ogrenciler = [
        ['Ada', 'Yilmaz', '2024-06-12', 'kiz'], ['Ege', 'Kaya', '2024-04-03', 'erkek'],
        ['Lina', 'Demir', '2024-01-18', 'kiz'], ['Mert', 'Celik', '2023-12-08', 'erkek'],
        ['Defne', 'Sahin', '2023-06-21', 'kiz'], ['Aras', 'Aydin', '2023-04-09', 'erkek'],
        ['Mina', 'Arslan', '2023-02-14', 'kiz'], ['Kuzey', 'Koc', '2022-12-30', 'erkek'],
        ['Nehir', 'Polat', '2022-08-17', 'kiz'], ['Atlas', 'Kurt', '2022-05-25', 'erkek'],
        ['Duru', 'Aksoy', '2022-02-11', 'kiz'], ['Bora', 'Eren', '2021-11-06', 'erkek'],
    ];

    $veliEkle = $db->prepare(
        'INSERT INTO veliler
            (kurum_id, ad, soyad, telefon_ulke, telefon, eposta, yakinlik, il, ilce, adres, iletisim_referansi, notlar, olusturulma_tarihi)
         VALUES
            (:kurum_id, :ad, :soyad, "Turkiye", :telefon, :eposta, :yakinlik, "Antalya", "Muratpasa", :adres, :referans, "Demo veli kaydi", NOW())'
    );
    $ogrenciEkle = $db->prepare(
        'INSERT INTO ogrenciler
            (kurum_id, ad, soyad, dogum_tarihi, cinsiyet, kayit_tarihi, durum, acil_durum_kisi, acil_durum_telefon, ozel_durum_notu, olusturulma_tarihi)
         VALUES
            (:kurum_id, :ad, :soyad, :dogum, :cinsiyet, :kayit_tarihi, "aktif", :acil_kisi, :acil_telefon, "Demo ogrenci kaydi", NOW())'
    );
    $bagla = $db->prepare(
        'INSERT INTO ogrenci_velileri (kurum_id, ogrenci_id, veli_id, birincil_mi, acil_durum_mu)
         VALUES (:kurum_id, :ogrenci_id, :veli_id, 1, 1)'
    );
    $grubaAta = $db->prepare(
        'INSERT INTO grup_ogrencileri (kurum_id, grup_id, ogrenci_id, baslangic_tarihi, aktif)
         VALUES (:kurum_id, :grup_id, :ogrenci_id, :baslangic, 1)'
    );
    $paketEkle = $db->prepare(
        'INSERT INTO paketler
            (kurum_id, ogrenci_id, paket_sira_no, paket_adi, haftalik_katilim_sayisi,
             toplam_normal_hak, toplam_telafi_hak, kullanilan_normal_hak, kullanilan_telafi_hak,
             kalan_normal_hak, kalan_telafi_hak, baslangic_tarihi, tahmini_son_ders_tarihi,
             liste_fiyati, indirim_turu, indirim_tutari, net_paket_tutari, paket_durumu,
             yenileme_durumu, yonetici_notu, olusturan_kullanici_id, olusturulma_tarihi)
         VALUES
            (:kurum_id, :ogrenci_id, 1, "24 Derslik Oyun Grubu", 2,
             24, 3, 2, 0, 22, 3, :baslangic, :bitis,
             8400, NULL, 0, 8400, "aktif", "belirsiz", "Demo paket", :kullanici_id, NOW())'
    );

    $veliIdleri = [];
    $ogrenciIdleri = [];
    $paketIdleri = [];
    $baslangic = (new DateTimeImmutable('today'))->modify('-14 days')->format('Y-m-d');
    $bitis = (new DateTimeImmutable('today'))->modify('+90 days')->format('Y-m-d');
    foreach ($ogrenciler as $i => [$ogrenciAd, $ogrenciSoyad, $dogum, $cinsiyet]) {
        [$veliAd, $veliSoyad, $telefon, $yakinlik, $referans] = $veliler[$i];
        $veliEkle->execute([
            'kurum_id' => $kurumId,
            'ad' => $veliAd,
            'soyad' => $veliSoyad,
            'telefon' => $telefon,
            'eposta' => 'demo.veli' . ($i + 1) . '@example.com',
            'yakinlik' => $yakinlik,
            'adres' => 'Demo Mahallesi, Antalya',
            'referans' => $referans,
        ]);
        $veliId = (int) $db->lastInsertId();
        $veliIdleri[] = $veliId;

        $ogrenciEkle->execute([
            'kurum_id' => $kurumId,
            'ad' => $ogrenciAd,
            'soyad' => $ogrenciSoyad,
            'dogum' => $dogum,
            'cinsiyet' => $cinsiyet,
            'kayit_tarihi' => $baslangic,
            'acil_kisi' => $veliAd . ' ' . $veliSoyad,
            'acil_telefon' => $telefon,
        ]);
        $ogrenciId = (int) $db->lastInsertId();
        $ogrenciIdleri[] = $ogrenciId;

        $grupId = $grupIdleri[intdiv($i, 4)];
        $bagla->execute(['kurum_id' => $kurumId, 'ogrenci_id' => $ogrenciId, 'veli_id' => $veliId]);
        $grubaAta->execute(['kurum_id' => $kurumId, 'grup_id' => $grupId, 'ogrenci_id' => $ogrenciId, 'baslangic' => $baslangic]);
        $paketEkle->execute([
            'kurum_id' => $kurumId,
            'ogrenci_id' => $ogrenciId,
            'baslangic' => $baslangic,
            'bitis' => $bitis,
            'kullanici_id' => $kullaniciId,
        ]);
        $paketIdleri[] = (int) $db->lastInsertId();
    }

    $randevuEkle = $db->prepare(
        'INSERT INTO randevular
            (kurum_id, ogrenci_id, veli_id, grup_id, paket_id, ogretmen_id, tarih,
             baslangic_saati, bitis_saati, tur, hak_kaynagi, durum, aciklama,
             olusturan_kullanici_id, olusturulma_tarihi)
         VALUES
            (:kurum_id, :ogrenci_id, :veli_id, :grup_id, :paket_id, :ogretmen_id, :tarih,
             :baslangic, :bitis, "Normal ders", "Aktif paket", :durum, "Demo takvim randevusu",
             :kullanici_id, NOW())'
    );
    $programlar = [
        1 => [0, '09:00:00', '10:15:00'],
        3 => [0, '09:00:00', '10:15:00'],
        2 => [1, '11:00:00', '12:15:00'],
        4 => [1, '11:00:00', '12:15:00'],
        5 => [2, '14:00:00', '15:15:00'],
        6 => [2, '14:00:00', '15:15:00'],
    ];
    $bugun = new DateTimeImmutable('today');
    $takvimBaslangic = $bugun->modify('-14 days');
    $takvimBitis = $bugun->modify('+42 days');
    $randevuSayisi = 0;
    for ($gun = $takvimBaslangic; $gun <= $takvimBitis; $gun = $gun->modify('+1 day')) {
        $haftaGunu = (int) $gun->format('N');
        if (!isset($programlar[$haftaGunu])) {
            continue;
        }
        [$grupIndex, $saatBaslangic, $saatBitis] = $programlar[$haftaGunu];
        $ilkOgrenci = $grupIndex * 4;
        for ($i = $ilkOgrenci; $i < $ilkOgrenci + 4; $i++) {
            $durum = $gun < $bugun ? 'geldi' : 'planlandi';
            $randevuEkle->execute([
                'kurum_id' => $kurumId,
                'ogrenci_id' => $ogrenciIdleri[$i],
                'veli_id' => $veliIdleri[$i],
                'grup_id' => $grupIdleri[$grupIndex],
                'paket_id' => $paketIdleri[$i],
                'ogretmen_id' => $kullaniciId,
                'tarih' => $gun->format('Y-m-d'),
                'baslangic' => $saatBaslangic,
                'bitis' => $saatBitis,
                'durum' => $durum,
                'kullanici_id' => $kullaniciId,
            ]);
            $randevuSayisi++;
        }
    }

    $db->commit();
    fwrite(STDOUT, sprintf(
        "Demo kurum olusturuldu: kurum_id=%d, kullanici=%s, ogrenci=%d, veli=%d, grup=%d, randevu=%d\n",
        $kurumId,
        $demoEposta,
        count($ogrenciIdleri),
        count($veliIdleri),
        count($grupIdleri),
        $randevuSayisi
    ));
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, "Demo kurum olusturulamadi: " . $e->getMessage() . "\n");
    exit(1);
}
