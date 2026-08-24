<?php

declare(strict_types=1);

use App\Core\Veritabani;

require dirname(__DIR__) . '/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Bu komut yalnizca CLI uzerinden calistirilabilir.\n");
    exit(1);
}

if (!in_array('--apply', $argv, true)) {
    fwrite(STDOUT, "Zengin demo verisini olusturmak icin --apply parametresini kullanin.\n");
    exit(0);
}

$db = Veritabani::baglan();
$kurumStmt = $db->prepare('SELECT id FROM kurumlar WHERE kod = :kod AND aktif = 1 LIMIT 1');
$kurumStmt->execute(['kod' => 'DEMO']);
$kurumId = (int) ($kurumStmt->fetchColumn() ?: 0);
if ($kurumId < 1) {
    fwrite(STDERR, "Aktif DEMO kurumu bulunamadi. Once seed-demo-kurum.php komutunu calistirin.\n");
    exit(1);
}

$kullaniciStmt = $db->prepare('SELECT id FROM kullanicilar WHERE kurum_id = :kurum_id AND aktif = 1 ORDER BY id LIMIT 1');
$kullaniciStmt->execute(['kurum_id' => $kurumId]);
$kullaniciId = (int) ($kullaniciStmt->fetchColumn() ?: 0);
if ($kullaniciId < 1) {
    fwrite(STDERR, "DEMO kurumu icin aktif kullanici bulunamadi.\n");
    exit(1);
}

$markerStmt = $db->prepare('SELECT COUNT(*) FROM odemeler WHERE kurum_id = :kurum_id AND aciklama LIKE "Zengin demo verisi:%"');
$markerStmt->execute(['kurum_id' => $kurumId]);
if ((int) $markerStmt->fetchColumn() > 0) {
    fwrite(STDOUT, "Zengin DEMO verisi zaten mevcut; yeni veri eklenmedi.\n");
    exit(0);
}

$bugun = new DateTimeImmutable('today');
$db->beginTransaction();

try {
    $paketTanimlari = [
        ['Oyun Grubu Haftalik 1 Ders', 5000, 1, 4, 1],
        ['Oyun Grubu Haftalik 2 Ders', 8750, 2, 8, 2],
        ['Tanisma Dersi (Tek Ders)', 1250, 1, 1, 0],
        ['Birebir Seans', 2600, 1, 1, 0],
        ['Yaz Grubu', 13500, 3, 12, 0],
        ['Yarim Gun Grubu', 12000, 2, 8, 0],
    ];
    $hizmetBul = $db->prepare('SELECT id FROM hizmetler WHERE kurum_id = :kurum_id AND hizmet_adi = :ad LIMIT 1');
    $hizmetEkle = $db->prepare(
        'INSERT INTO hizmetler
            (kurum_id, hizmet_adi, ucret, haftalik_katilim_sayisi, toplam_normal_hak, toplam_telafi_hak, aktif, olusturulma_tarihi)
         VALUES (:kurum_id, :ad, :ucret, :haftalik, :normal, :telafi, 1, NOW())'
    );
    $hizmetler = [];
    foreach ($paketTanimlari as [$ad, $ucret, $haftalik, $normal, $telafi]) {
        $hizmetBul->execute(['kurum_id' => $kurumId, 'ad' => $ad]);
        $id = (int) ($hizmetBul->fetchColumn() ?: 0);
        if ($id < 1) {
            $hizmetEkle->execute([
                'kurum_id' => $kurumId,
                'ad' => $ad,
                'ucret' => $ucret,
                'haftalik' => $haftalik,
                'normal' => $normal,
                'telafi' => $telafi,
            ]);
            $id = (int) $db->lastInsertId();
        }
        $hizmetler[] = [
            'id' => $id,
            'ad' => $ad,
            'ucret' => (float) $ucret,
            'haftalik' => (int) $haftalik,
            'normal' => (int) $normal,
            'telafi' => (int) $telafi,
        ];
    }

    $yeniCocuklar = [
        ['Alin', 'Yildirim', 'kiz', '2023-03-08', 'Seda', 'Yildirim'],
        ['Baran', 'Ozkan', 'erkek', '2023-08-17', 'Merve', 'Ozkan'],
        ['Ceylin', 'Acar', 'kiz', '2024-02-11', 'Pelin', 'Acar'],
        ['Deniz', 'Kilic', 'erkek', '2022-11-23', 'Serkan', 'Kilic'],
        ['Ela', 'Yuce', 'kiz', '2023-12-04', 'Nihan', 'Yuce'],
        ['Emir', 'Tas', 'erkek', '2024-04-19', 'Gokce', 'Tas'],
        ['Eylul', 'Bulut', 'kiz', '2022-09-14', 'Duygu', 'Bulut'],
        ['Kaan', 'Uslu', 'erkek', '2023-05-28', 'Burcu', 'Uslu'],
        ['Lara', 'Gunes', 'kiz', '2024-01-07', 'Sibel', 'Gunes'],
        ['Mete', 'Kara', 'erkek', '2022-07-16', 'Hande', 'Kara'],
        ['Nehir', 'Keskin', 'kiz', '2023-10-25', 'Ebru', 'Keskin'],
        ['Poyraz', 'Ergun', 'erkek', '2024-03-13', 'Melis', 'Ergun'],
        ['Ruzgar', 'Akay', 'erkek', '2022-12-02', 'Asli', 'Akay'],
        ['Sare', 'Onal', 'kiz', '2023-07-21', 'Buse', 'Onal'],
        ['Toprak', 'Duman', 'erkek', '2024-05-06', 'Esra', 'Duman'],
        ['Yagmur', 'Erdem', 'kiz', '2022-10-18', 'Ceren', 'Erdem'],
        ['Zeynep', 'Korkmaz', 'kiz', '2023-02-26', 'Aylin', 'Korkmaz'],
        ['Arda', 'Sari', 'erkek', '2024-06-09', 'Ipek', 'Sari'],
        ['Asya', 'Kaplan', 'kiz', '2022-08-30', 'Neslihan', 'Kaplan'],
        ['Bora', 'Tan', 'erkek', '2023-09-12', 'Funda', 'Tan'],
        ['Duru', 'Cetin', 'kiz', '2024-02-29', 'Leyla', 'Cetin'],
        ['Efe', 'Sonmez', 'erkek', '2022-06-15', 'Mine', 'Sonmez'],
        ['Mira', 'Kocak', 'kiz', '2023-11-01', 'Ozge', 'Kocak'],
        ['Uras', 'Bayram', 'erkek', '2024-04-07', 'Selma', 'Bayram'],
    ];
    $ogrenciBul = $db->prepare('SELECT id FROM ogrenciler WHERE kurum_id = :kurum_id AND ad = :ad AND soyad = :soyad LIMIT 1');
    $ogrenciEkle = $db->prepare(
        'INSERT INTO ogrenciler
            (kurum_id, ad, soyad, dogum_tarihi, cinsiyet, kayit_tarihi, durum,
             acil_durum_kisi, acil_durum_telefon, ozel_durum_notu, yonetici_notu, olusturulma_tarihi)
         VALUES
            (:kurum_id, :ad, :soyad, :dogum, :cinsiyet, :kayit, "aktif",
             :acil_kisi, :telefon, "Demo ogrenci kaydi", "Zengin demo verisi", NOW())'
    );
    $veliEkle = $db->prepare(
        'INSERT INTO veliler
            (kurum_id, ad, soyad, telefon_ulke, telefon, eposta, yakinlik, il, ilce, adres,
             iletisim_referansi, notlar, olusturulma_tarihi)
         VALUES
            (:kurum_id, :ad, :soyad, "Turkiye", :telefon, :eposta, "Anne", "Antalya", "Muratpasa",
             "Demo Mahallesi, Antalya", :referans, "Zengin demo verisi", NOW())'
    );
    $veliBagla = $db->prepare(
        'INSERT INTO ogrenci_velileri (kurum_id, ogrenci_id, veli_id, birincil_mi, acil_durum_mu)
         VALUES (:kurum_id, :ogrenci_id, :veli_id, 1, 1)'
    );
    $referanslar = ['Instagram', 'Google aramasi', 'Mevcut veli tavsiyesi', 'Arkadas tavsiyesi', 'Mahalle etkinligi'];
    foreach ($yeniCocuklar as $index => [$ad, $soyad, $cinsiyet, $dogum, $veliAd, $veliSoyad]) {
        $ogrenciBul->execute(['kurum_id' => $kurumId, 'ad' => $ad, 'soyad' => $soyad]);
        if ((int) ($ogrenciBul->fetchColumn() ?: 0) > 0) {
            continue;
        }
        $telefon = sprintf('0(555) 2%02d %02d %02d', intdiv($index + 1, 100), ($index + 21) % 100, ($index + 41) % 100);
        $veliEkle->execute([
            'kurum_id' => $kurumId,
            'ad' => $veliAd,
            'soyad' => $veliSoyad,
            'telefon' => $telefon,
            'eposta' => 'zengin.demo' . ($index + 1) . '@example.com',
            'referans' => $referanslar[$index % count($referanslar)],
        ]);
        $veliId = (int) $db->lastInsertId();
        $ogrenciEkle->execute([
            'kurum_id' => $kurumId,
            'ad' => $ad,
            'soyad' => $soyad,
            'dogum' => $dogum,
            'cinsiyet' => $cinsiyet,
            'kayit' => $bugun->modify('-' . (($index % 80) + 20) . ' days')->format('Y-m-d'),
            'acil_kisi' => $veliAd . ' ' . $veliSoyad,
            'telefon' => $telefon,
        ]);
        $ogrenciId = (int) $db->lastInsertId();
        $veliBagla->execute(['kurum_id' => $kurumId, 'ogrenci_id' => $ogrenciId, 'veli_id' => $veliId]);
    }

    $ogrencilerStmt = $db->prepare(
        'SELECT o.id,
                (SELECT ov.veli_id FROM ogrenci_velileri ov
                 WHERE ov.kurum_id = o.kurum_id AND ov.ogrenci_id = o.id
                 ORDER BY ov.birincil_mi DESC, ov.id ASC LIMIT 1) AS veli_id
         FROM ogrenciler o
         WHERE o.kurum_id = :kurum_id AND o.durum = "aktif"
         ORDER BY o.id'
    );
    $ogrencilerStmt->execute(['kurum_id' => $kurumId]);
    $ogrenciler = $ogrencilerStmt->fetchAll();

    $programlar = [
        [1, '10:00:00', '13:00:00', '30-50 Ay', 'Pazartesi Yarim Gun', 8, 7],
        [1, '15:30:00', '16:30:00', '18-24 Ay', 'Pazartesi 18-24 1.Grup', 6, 4],
        [1, '17:00:00', '18:00:00', '18-24 Ay', 'Pazartesi 18-24', 6, 4],
        [2, '16:00:00', '17:00:00', '25-36 Ay', 'Sali 25-36', 7, 6],
        [3, '10:00:00', '13:00:00', '30-50 Ay', 'Carsamba Yarim Gun', 8, 7],
        [3, '15:00:00', '16:00:00', '25-36 Ay', 'Carsamba 25-36', 7, 5],
        [3, '17:00:00', '18:00:00', '18-24 Ay', 'Carsamba 18-24', 6, 5],
        [4, '11:00:00', '12:00:00', '25-36 Ay', 'Persembe 25-36', 7, 6],
        [4, '15:00:00', '16:00:00', '37-48 Ay', 'Persembe 37-48', 7, 5],
        [4, '17:00:00', '18:00:00', '18-24 Ay', 'Persembe 18-24', 6, 4],
        [5, '10:00:00', '13:00:00', '30-50 Ay', 'Cuma Yarim Gun', 8, 7],
        [5, '16:00:00', '17:00:00', '25-36 Ay', 'Cuma 25-36', 7, 6],
        [6, '10:00:00', '11:00:00', '18-30 Ay', 'Cumartesi Minikler', 8, 6],
        [6, '12:00:00', '13:00:00', '31-48 Ay', 'Cumartesi Kasifler', 8, 7],
    ];
    $grupBul = $db->prepare('SELECT id FROM gruplar WHERE kurum_id = :kurum_id AND ad = :ad LIMIT 1');
    $grupEkle = $db->prepare(
        'INSERT INTO gruplar
            (kurum_id, ad, yas_araligi, kontenjan, ogretmen_id, aktif, durum, aciklama, olusturulma_tarihi)
         VALUES (:kurum_id, :ad, :yas, :kontenjan, :ogretmen_id, 1, "aktif", "Zengin demo haftalik programi", NOW())'
    );
    $dersBul = $db->prepare('SELECT id FROM ders_programlari WHERE kurum_id = :kurum_id AND grup_id = :grup_id AND gun = :gun LIMIT 1');
    $dersEkle = $db->prepare(
        'INSERT INTO ders_programlari (kurum_id, grup_id, gun, baslangic_saati, bitis_saati, aktif)
         VALUES (:kurum_id, :grup_id, :gun, :baslangic, :bitis, 1)'
    );
    $grupOgrenciBul = $db->prepare(
        'SELECT id FROM grup_ogrencileri
         WHERE kurum_id = :kurum_id AND grup_id = :grup_id AND ogrenci_id = :ogrenci_id AND aktif = 1 LIMIT 1'
    );
    $grupOgrenciEkle = $db->prepare(
        'INSERT INTO grup_ogrencileri (kurum_id, grup_id, ogrenci_id, baslangic_tarihi, aktif)
         VALUES (:kurum_id, :grup_id, :ogrenci_id, :baslangic, 1)'
    );
    $demoGruplari = [];
    foreach ($programlar as $programIndex => [$gun, $baslangic, $bitis, $yas, $ad, $kontenjan, $ogrenciSayisi]) {
        $grupBul->execute(['kurum_id' => $kurumId, 'ad' => $ad]);
        $grupId = (int) ($grupBul->fetchColumn() ?: 0);
        if ($grupId < 1) {
            $grupEkle->execute([
                'kurum_id' => $kurumId,
                'ad' => $ad,
                'yas' => $yas,
                'kontenjan' => $kontenjan,
                'ogretmen_id' => $kullaniciId,
            ]);
            $grupId = (int) $db->lastInsertId();
        }
        $dersBul->execute(['kurum_id' => $kurumId, 'grup_id' => $grupId, 'gun' => $gun]);
        if ((int) ($dersBul->fetchColumn() ?: 0) < 1) {
            $dersEkle->execute([
                'kurum_id' => $kurumId,
                'grup_id' => $grupId,
                'gun' => $gun,
                'baslangic' => $baslangic,
                'bitis' => $bitis,
            ]);
        }
        $atananlar = [];
        for ($slot = 0; $slot < $ogrenciSayisi; $slot++) {
            $ogrenci = $ogrenciler[($programIndex * 3 + $slot) % count($ogrenciler)];
            $ogrenciId = (int) $ogrenci['id'];
            $grupOgrenciBul->execute(['kurum_id' => $kurumId, 'grup_id' => $grupId, 'ogrenci_id' => $ogrenciId]);
            if ((int) ($grupOgrenciBul->fetchColumn() ?: 0) < 1) {
                $grupOgrenciEkle->execute([
                    'kurum_id' => $kurumId,
                    'grup_id' => $grupId,
                    'ogrenci_id' => $ogrenciId,
                    'baslangic' => $bugun->modify('-60 days')->format('Y-m-d'),
                ]);
            }
            $atananlar[] = $ogrenci;
        }
        $demoGruplari[] = [
            'id' => $grupId,
            'gun' => (int) $gun,
            'baslangic' => $baslangic,
            'bitis' => $bitis,
            'ogrenciler' => $atananlar,
        ];
    }

    $paketBul = $db->prepare(
        'SELECT id FROM paketler WHERE kurum_id = :kurum_id AND ogrenci_id = :ogrenci_id AND paket_durumu = "aktif" ORDER BY id DESC LIMIT 1'
    );
    $paketSira = $db->prepare('SELECT COALESCE(MAX(paket_sira_no), 0) + 1 FROM paketler WHERE kurum_id = :kurum_id AND ogrenci_id = :ogrenci_id');
    $paketEkle = $db->prepare(
        'INSERT INTO paketler
            (kurum_id, ogrenci_id, paket_sira_no, paket_adi, haftalik_katilim_sayisi,
             toplam_normal_hak, toplam_telafi_hak, kullanilan_normal_hak, kullanilan_telafi_hak,
             kalan_normal_hak, kalan_telafi_hak, baslangic_tarihi, tahmini_son_ders_tarihi,
             liste_fiyati, indirim_turu, indirim_tutari, net_paket_tutari, paket_durumu,
             yenileme_durumu, yonetici_notu, olusturan_kullanici_id, olusturulma_tarihi)
         VALUES
            (:kurum_id, :ogrenci_id, :sira, :ad, :haftalik,
             :normal, :telafi, :kullanilan_normal, 0,
             :kalan_normal, :kalan_telafi, :baslangic, :bitis,
             :liste_fiyati, NULL, 0, :net_tutar, :durum,
             :yenileme, :not, :kullanici_id, NOW())'
    );
    $aktifPaketler = [];
    foreach ($ogrenciler as $index => $ogrenci) {
        $ogrenciId = (int) $ogrenci['id'];
        $paketBul->execute(['kurum_id' => $kurumId, 'ogrenci_id' => $ogrenciId]);
        $aktifPaketId = (int) ($paketBul->fetchColumn() ?: 0);
        if ($aktifPaketId < 1) {
            $hizmet = $hizmetler[$index % count($hizmetler)];
            $paketSira->execute(['kurum_id' => $kurumId, 'ogrenci_id' => $ogrenciId]);
            $sira = (int) $paketSira->fetchColumn();
            $kullanilan = min(2, $hizmet['normal']);
            $paketEkle->execute([
                'kurum_id' => $kurumId,
                'ogrenci_id' => $ogrenciId,
                'sira' => $sira,
                'ad' => $hizmet['ad'],
                'haftalik' => $hizmet['haftalik'],
                'normal' => $hizmet['normal'],
                'telafi' => $hizmet['telafi'],
                'kullanilan_normal' => $kullanilan,
                'kalan_normal' => $hizmet['normal'] - $kullanilan,
                'kalan_telafi' => $hizmet['telafi'],
                'baslangic' => $bugun->modify('-21 days')->format('Y-m-d'),
                'bitis' => $bugun->modify('+90 days')->format('Y-m-d'),
                'liste_fiyati' => $hizmet['ucret'],
                'net_tutar' => $hizmet['ucret'],
                'durum' => 'aktif',
                'yenileme' => $index % 4 === 0 ? 'yenilenecek' : 'belirsiz',
                'not' => 'Zengin demo aktif paketi',
                'kullanici_id' => $kullaniciId,
            ]);
            $aktifPaketId = (int) $db->lastInsertId();
        }
        $aktifPaketler[$ogrenciId] = $aktifPaketId;

        $gecmisBul = $db->prepare(
            'SELECT id FROM paketler WHERE kurum_id = :kurum_id AND ogrenci_id = :ogrenci_id AND yonetici_notu = "Zengin demo gecmis paketi" LIMIT 1'
        );
        $gecmisBul->execute(['kurum_id' => $kurumId, 'ogrenci_id' => $ogrenciId]);
        if ((int) ($gecmisBul->fetchColumn() ?: 0) < 1) {
            $hizmet = $hizmetler[($index + 1) % count($hizmetler)];
            $paketSira->execute(['kurum_id' => $kurumId, 'ogrenci_id' => $ogrenciId]);
            $paketEkle->execute([
                'kurum_id' => $kurumId,
                'ogrenci_id' => $ogrenciId,
                'sira' => (int) $paketSira->fetchColumn(),
                'ad' => $hizmet['ad'],
                'haftalik' => $hizmet['haftalik'],
                'normal' => $hizmet['normal'],
                'telafi' => $hizmet['telafi'],
                'kullanilan_normal' => $hizmet['normal'],
                'kalan_normal' => 0,
                'kalan_telafi' => $hizmet['telafi'],
                'baslangic' => $bugun->modify('-150 days')->format('Y-m-d'),
                'bitis' => $bugun->modify('-45 days')->format('Y-m-d'),
                'liste_fiyati' => $hizmet['ucret'],
                'net_tutar' => $hizmet['ucret'],
                'durum' => 'tamamlandi',
                'yenileme' => 'yenilenecek',
                'not' => 'Zengin demo gecmis paketi',
                'kullanici_id' => $kullaniciId,
            ]);
        }
    }

    $randevuEkle = $db->prepare(
        'INSERT INTO randevular
            (kurum_id, ogrenci_id, veli_id, grup_id, paket_id, ogretmen_id, tarih,
             baslangic_saati, bitis_saati, tur, hak_kaynagi, durum, aciklama,
             olusturan_kullanici_id, olusturulma_tarihi)
         VALUES
            (:kurum_id, :ogrenci_id, :veli_id, :grup_id, :paket_id, :ogretmen_id, :tarih,
             :baslangic, :bitis, "Normal ders", "Aktif paket", :durum, "Zengin demo takvim randevusu",
             :kullanici_id, NOW())'
    );
    $randevuSayisi = 0;
    for ($tarih = $bugun->modify('-14 days'); $tarih <= $bugun->modify('+28 days'); $tarih = $tarih->modify('+1 day')) {
        $gun = (int) $tarih->format('N');
        foreach ($demoGruplari as $grup) {
            if ($grup['gun'] !== $gun) {
                continue;
            }
            foreach ($grup['ogrenciler'] as $ogrenci) {
                $ogrenciId = (int) $ogrenci['id'];
                $randevuEkle->execute([
                    'kurum_id' => $kurumId,
                    'ogrenci_id' => $ogrenciId,
                    'veli_id' => (int) ($ogrenci['veli_id'] ?? 0) ?: null,
                    'grup_id' => $grup['id'],
                    'paket_id' => $aktifPaketler[$ogrenciId] ?? null,
                    'ogretmen_id' => $kullaniciId,
                    'tarih' => $tarih->format('Y-m-d'),
                    'baslangic' => $grup['baslangic'],
                    'bitis' => $grup['bitis'],
                    'durum' => $tarih < $bugun ? 'geldi' : 'planlandi',
                    'kullanici_id' => $kullaniciId,
                ]);
                $randevuSayisi++;
            }
        }
    }

    $kasaTanimlari = [
        ['Nakit Kasa', 'nakit', 12500, 'Gunluk nakit tahsilatlar'],
        ['Banka Hesabi', 'banka', 28000, 'Havale ve EFT tahsilatlari'],
        ['Sanal POS', 'diger', 7500, 'Kart ve odeme baglantisi tahsilatlari'],
    ];
    $kasaBul = $db->prepare('SELECT id FROM kasalar WHERE kurum_id = :kurum_id AND ad = :ad LIMIT 1');
    $kasaEkle = $db->prepare(
        'INSERT INTO kasalar
            (kurum_id, ad, tur, para_birimi, acilis_bakiyesi, aciklama, aktif, olusturan_kullanici_id, olusturulma_tarihi)
         VALUES (:kurum_id, :ad, :tur, "TRY", :bakiye, :aciklama, 1, :kullanici_id, NOW())'
    );
    $kasalar = [];
    foreach ($kasaTanimlari as [$ad, $tur, $bakiye, $aciklama]) {
        $kasaBul->execute(['kurum_id' => $kurumId, 'ad' => $ad]);
        $id = (int) ($kasaBul->fetchColumn() ?: 0);
        if ($id < 1) {
            $kasaEkle->execute([
                'kurum_id' => $kurumId,
                'ad' => $ad,
                'tur' => $tur,
                'bakiye' => $bakiye,
                'aciklama' => $aciklama,
                'kullanici_id' => $kullaniciId,
            ]);
            $id = (int) $db->lastInsertId();
        }
        $kasalar[] = $id;
    }

    $tumPaketler = $db->prepare(
        'SELECT p.id, p.ogrenci_id, p.net_paket_tutari,
                (SELECT ov.veli_id FROM ogrenci_velileri ov
                 WHERE ov.kurum_id = p.kurum_id AND ov.ogrenci_id = p.ogrenci_id
                 ORDER BY ov.birincil_mi DESC, ov.id ASC LIMIT 1) AS veli_id
         FROM paketler p
         WHERE p.kurum_id = :kurum_id
         ORDER BY p.id'
    );
    $tumPaketler->execute(['kurum_id' => $kurumId]);
    $paketRows = $tumPaketler->fetchAll();
    $odemeEkle = $db->prepare(
        'INSERT INTO odemeler
            (kurum_id, ogrenci_id, veli_id, paket_id, tarih, tutar, yontem, kasa_id,
             makbuz_numarasi, aciklama, alan_kullanici_id, iptal, olusturulma_tarihi)
         VALUES
            (:kurum_id, :ogrenci_id, :veli_id, :paket_id, :tarih, :tutar, :yontem, :kasa_id,
             :makbuz, :aciklama, :kullanici_id, 0, NOW())'
    );
    $yontemler = ['nakit', 'kredi_karti', 'havale_eft', 'odeme_baglantisi'];
    foreach ($paketRows as $index => $paket) {
        $parcaSayisi = $index < 12 ? 2 : 1;
        $toplam = max(500, (float) $paket['net_paket_tutari']);
        for ($parca = 1; $parca <= $parcaSayisi; $parca++) {
            $yontem = $yontemler[($index + $parca) % count($yontemler)];
            $kasaId = match ($yontem) {
                'nakit' => $kasalar[0],
                'havale_eft' => $kasalar[1],
                default => $kasalar[2],
            };
            $odemeEkle->execute([
                'kurum_id' => $kurumId,
                'ogrenci_id' => (int) $paket['ogrenci_id'],
                'veli_id' => (int) ($paket['veli_id'] ?? 0) ?: null,
                'paket_id' => (int) $paket['id'],
                'tarih' => $bugun->modify('-' . (($index * 3 + $parca * 5) % 58) . ' days')->format('Y-m-d'),
                'tutar' => round($toplam / $parcaSayisi, 2),
                'yontem' => $yontem,
                'kasa_id' => $kasaId,
                'makbuz' => 'DEMO-' . $paket['id'] . '-' . $parca,
                'aciklama' => 'Zengin demo verisi: paket tahsilati',
                'kullanici_id' => $kullaniciId,
            ]);
        }
    }

    $giderler = [
        ['Kurum Kirasi', 'Kira', 42000, 'banka_havalesi'],
        ['Personel Maaslari', 'Personel', 68500, 'banka_havalesi'],
        ['SGK ve Yan Haklar', 'Personel', 18200, 'banka_havalesi'],
        ['Etkinlik Malzemeleri', 'Malzeme', 9800, 'kredi_karti'],
        ['Elektrik ve Su', 'Fatura', 6300, 'otomatik_odeme'],
        ['Temizlik Hizmeti', 'Temizlik', 7200, 'banka_havalesi'],
        ['Mutfak ve Ikram', 'Mutfak', 4850, 'kredi_karti'],
        ['Reklam ve Tanitim', 'Pazarlama', 11500, 'kredi_karti'],
        ['Oyuncak ve Ekipman', 'Demirbas', 14750, 'kredi_karti'],
        ['Internet ve Telefon', 'Fatura', 2250, 'otomatik_odeme'],
        ['Bakim Onarim', 'Bakim', 5600, 'nakit'],
        ['Kirtasiye', 'Ofis', 3150, 'nakit'],
    ];
    $giderEkle = $db->prepare(
        'INSERT INTO giderler
            (kurum_id, tarih, tedarikci, kategori, aciklama, tutar, odeme_turu, kasa_id,
             durum, odeme_tarihi, olusturan_kullanici_id, olusturulma_tarihi)
         VALUES
            (:kurum_id, :tarih, :tedarikci, :kategori, :aciklama, :tutar, :odeme_turu, :kasa_id,
             "odendi", :odeme_tarihi, :kullanici_id, NOW())'
    );
    foreach ([1, 2] as $ayDongusu) {
        foreach ($giderler as $index => [$tedarikci, $kategori, $tutar, $odemeTuru]) {
            $tarih = $bugun->modify('-' . (($ayDongusu - 1) * 30 + ($index * 2 + 1)) . ' days')->format('Y-m-d');
            $giderEkle->execute([
                'kurum_id' => $kurumId,
                'tarih' => $tarih,
                'tedarikci' => $tedarikci,
                'kategori' => $kategori,
                'aciklama' => 'Zengin demo verisi: ' . $kategori,
                'tutar' => $tutar,
                'odeme_turu' => $odemeTuru,
                'kasa_id' => $odemeTuru === 'nakit' ? $kasalar[0] : $kasalar[1],
                'odeme_tarihi' => $tarih,
                'kullanici_id' => $kullaniciId,
            ]);
        }
    }

    $bekleyenler = [
        ['Marsel Gorguc', '2023-01-04', 'Nur Gorguc', '0(536) 693 55 39', 'Persembe', '37-48 Ay', 'farketmez', 'Iletisimi iyi'],
        ['Firuze Agca', '2024-03-04', 'Firuze Agca', '0(543) 414 70 56', 'Cumartesi', '25-36 Ay', 'farketmez', ''],
        ['Dogan Alp Dalman', '2023-07-11', 'Adile Dalman', '0(542) 283 87 98', 'Persembe', '37-48 Ay', 'farketmez', ''],
        ['Maya Gedikoglu', '2023-11-14', 'Reyhan Guven', '0(555) 669 89 58', 'Persembe', '25-36 Ay', 'farketmez', 'Aksam 5'],
        ['Erva Gunal', '2024-03-25', 'Emine Gunal', '0(543) 253 47 97', 'Sali', '25-36 Ay', 'hafta_ici', 'Sabah'],
        ['Lavin Aslan', '2023-06-18', 'Ece Aslan', '0(532) 144 22 10', 'Carsamba', '31-42 Ay', 'hafta_ici', 'Ogle grubu'],
        ['Mert Ali Ekin', '2024-01-09', 'Dilan Ekin', '0(505) 278 31 44', 'Cumartesi', '18-30 Ay', 'hafta_sonu', 'Tanisma bekliyor'],
        ['Nila Karaca', '2022-12-27', 'Sinem Karaca', '0(535) 811 09 16', 'Cuma', '37-48 Ay', 'farketmez', ''],
        ['Omer Ege Yalcin', '2023-09-02', 'Ahu Yalcin', '0(541) 390 24 65', 'Pazartesi', '25-36 Ay', 'hafta_ici', 'Saat 16 sonrasi'],
        ['Sare Mina Ak', '2024-05-12', 'Gul Ak', '0(507) 229 80 18', 'Cumartesi', '18-24 Ay', 'hafta_sonu', ''],
        ['Tuna Cakir', '2023-04-21', 'Merve Cakir', '0(533) 671 43 20', 'Persembe', '31-42 Ay', 'farketmez', 'Arkadas tavsiyesi'],
        ['Yagiz Can Er', '2022-10-05', 'Pinar Er', '0(545) 182 76 33', 'Cuma', '43-60 Ay', 'hafta_ici', ''],
    ];
    $bekleyenEkle = $db->prepare(
        'INSERT INTO bekleyen_veliler
            (kurum_id, ogrenci_ad_soyad, ogrenci_dogum_tarihi, veli_ad_soyad, veli_telefon,
             veli_eposta, beklenen_gun, ay_grubu, zaman_tercihi, durum, notlar,
             olusturan_kullanici_id, olusturulma_tarihi)
         VALUES
            (:kurum_id, :ogrenci, :dogum, :veli, :telefon, :eposta, :gun, :ay_grubu,
             :tercih, "bekliyor", :notlar, :kullanici_id, :olusturulma)'
    );
    foreach ($bekleyenler as $index => [$ogrenci, $dogum, $veli, $telefon, $gun, $ayGrubu, $tercih, $notlar]) {
        $bekleyenEkle->execute([
            'kurum_id' => $kurumId,
            'ogrenci' => $ogrenci,
            'dogum' => $dogum,
            'veli' => $veli,
            'telefon' => $telefon,
            'eposta' => 'bekleyen' . ($index + 1) . '@example.com',
            'gun' => $gun,
            'ay_grubu' => $ayGrubu,
            'tercih' => $tercih,
            'notlar' => $notlar !== '' ? $notlar : 'Zengin demo verisi',
            'kullanici_id' => $kullaniciId,
            'olusturulma' => $bugun->modify('-' . ($index + 3) . ' days')->format('Y-m-d H:i:s'),
        ]);
    }

    $db->commit();

    fwrite(STDOUT, sprintf(
        "Zengin DEMO verisi olusturuldu: ogrenci=%d, grup=%d, paket_tanimi=%d, paket=%d, randevu=%d, tahsilat=%d, gider=%d, bekleyen_veli=%d\n",
        count($ogrenciler),
        count($demoGruplari),
        count($hizmetler),
        count($paketRows),
        $randevuSayisi,
        count($paketRows) + min(12, count($paketRows)),
        count($giderler) * 2,
        count($bekleyenler)
    ));
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, "Zengin DEMO verisi olusturulamadi: " . $e->getMessage() . "\n");
    exit(1);
}
