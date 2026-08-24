<?php

declare(strict_types=1);

use App\Core\Veritabani;

require dirname(__DIR__) . '/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Bu komut yalnizca CLI uzerinden calistirilabilir.\n");
    exit(1);
}

if (!in_array('--apply', $argv, true)) {
    fwrite(STDOUT, "Demo randevu planini uygulamak icin --apply parametresini kullanin.\n");
    exit(0);
}

$db = Veritabani::baglan();
$kurumStmt = $db->prepare('SELECT id FROM kurumlar WHERE kod = "DEMO" AND aktif = 1 LIMIT 1');
$kurumStmt->execute();
$kurumId = (int) ($kurumStmt->fetchColumn() ?: 0);
if ($kurumId < 1) {
    throw new RuntimeException('Aktif DEMO kurumu bulunamadi.');
}

$kullaniciStmt = $db->prepare(
    'SELECT id FROM kullanicilar WHERE kurum_id = :kurum_id AND aktif = 1 ORDER BY id LIMIT 1'
);
$kullaniciStmt->execute(['kurum_id' => $kurumId]);
$kullaniciId = (int) ($kullaniciStmt->fetchColumn() ?: 0);
if ($kullaniciId < 1) {
    throw new RuntimeException('DEMO kurumu icin aktif kullanici bulunamadi.');
}

$paketStmt = $db->prepare(
    'SELECT p.id AS paket_id, p.ogrenci_id, p.paket_adi, p.haftalik_katilim_sayisi,
            p.toplam_normal_hak, p.toplam_telafi_hak,
            (SELECT ov.veli_id
             FROM ogrenci_velileri ov
             WHERE ov.kurum_id = p.kurum_id AND ov.ogrenci_id = p.ogrenci_id
             ORDER BY ov.birincil_mi DESC, ov.id ASC LIMIT 1) AS veli_id
     FROM paketler p
     INNER JOIN ogrenciler o ON o.id = p.ogrenci_id AND o.kurum_id = p.kurum_id
     INNER JOIN (
        SELECT ogrenci_id, MAX(id) AS paket_id
        FROM paketler
        WHERE kurum_id = :kurum_id_alt AND paket_durumu = "aktif"
        GROUP BY ogrenci_id
     ) son ON son.paket_id = p.id
     WHERE p.kurum_id = :kurum_id AND o.durum = "aktif"
     ORDER BY p.ogrenci_id ASC'
);
$paketStmt->execute(['kurum_id' => $kurumId, 'kurum_id_alt' => $kurumId]);
$paketler = $paketStmt->fetchAll();
$grupPaketleri = array_values(array_filter(
    $paketler,
    static fn(array $paket): bool => (int) $paket['toplam_normal_hak'] > 1
));
$tekDersPaketleri = array_values(array_filter(
    $paketler,
    static fn(array $paket): bool => (int) $paket['toplam_normal_hak'] <= 1
));

$programStmt = $db->prepare(
    'SELECT g.id AS grup_id, g.ad, g.kontenjan, g.ogretmen_id,
            dp.gun, dp.baslangic_saati, dp.bitis_saati
     FROM gruplar g
     INNER JOIN ders_programlari dp ON dp.grup_id = g.id AND dp.kurum_id = g.kurum_id AND dp.aktif = 1
     WHERE g.kurum_id = :kurum_id AND g.aktif = 1
     ORDER BY dp.gun ASC, dp.baslangic_saati ASC, g.id ASC'
);
$programStmt->execute(['kurum_id' => $kurumId]);
$programlar = $programStmt->fetchAll();
if (count($programlar) < 8 || $grupPaketleri === []) {
    throw new RuntimeException('Planlama icin yeterli grup veya aktif paket bulunamadi.');
}

$toplamHaftalikHak = array_sum(array_map(
    static fn(array $paket): int => max(1, min(3, (int) $paket['haftalik_katilim_sayisi'])),
    $grupPaketleri
));
$hedefler = [];
$kalanSlot = $toplamHaftalikHak;
foreach ($programlar as $index => $program) {
    $kontenjan = (int) $program['kontenjan'];
    $tamDolacak = in_array($index, [0, 1, 3, 6, 8], true);
    $hedef = $tamDolacak ? $kontenjan : max(1, min($kontenjan - 2, 2 + ($index % 3)));
    $hedefler[$index] = min($hedef, $kalanSlot);
    $kalanSlot -= $hedefler[$index];
}
for ($index = 0; $kalanSlot > 0; $index = ($index + 1) % count($programlar)) {
    $kapasite = (int) $programlar[$index]['kontenjan'];
    if ($hedefler[$index] >= $kapasite) {
        continue;
    }
    $hedefler[$index]++;
    $kalanSlot--;
}

$kalanAtama = [];
$atamalar = [];
foreach ($grupPaketleri as $paket) {
    $ogrenciId = (int) $paket['ogrenci_id'];
    $kalanAtama[$ogrenciId] = max(1, min(3, (int) $paket['haftalik_katilim_sayisi']));
    $atamalar[$ogrenciId] = [];
}
$ogrenciImleci = 0;
foreach ($programlar as $programIndex => $program) {
    for ($slot = 0; $slot < $hedefler[$programIndex]; $slot++) {
        $atandi = false;
        for ($deneme = 0; $deneme < count($grupPaketleri) * 2; $deneme++) {
            $paket = $grupPaketleri[$ogrenciImleci % count($grupPaketleri)];
            $ogrenciImleci++;
            $ogrenciId = (int) $paket['ogrenci_id'];
            if ($kalanAtama[$ogrenciId] < 1 || in_array($programIndex, $atamalar[$ogrenciId], true)) {
                continue;
            }
            $atamalar[$ogrenciId][] = $programIndex;
            $kalanAtama[$ogrenciId]--;
            $atandi = true;
            break;
        }
        if (!$atandi) {
            throw new RuntimeException('Grup kontenjan atamalari tamamlanamadi.');
        }
    }
}

$bugun = new DateTimeImmutable('today');
$ilkPlanTarihi = $bugun->modify('+1 day');
$sonPlanTarihi = $bugun->modify('+35 days');

$sonrakiTarihler = static function (array $atananProgramlar, int $adet) use ($programlar, $ilkPlanTarihi, $sonPlanTarihi): array {
    $tarihler = [];
    for ($tarih = $ilkPlanTarihi; $tarih <= $sonPlanTarihi; $tarih = $tarih->modify('+1 day')) {
        $gun = (int) $tarih->format('N');
        foreach ($atananProgramlar as $programIndex) {
            $program = $programlar[$programIndex];
            if ((int) $program['gun'] !== $gun) {
                continue;
            }
            $tarihler[] = [
                'tarih' => $tarih->format('Y-m-d'),
                'program' => $program,
            ];
        }
    }
    usort($tarihler, static fn(array $a, array $b): int => ($a['tarih'] . $a['program']['baslangic_saati']) <=> ($b['tarih'] . $b['program']['baslangic_saati']));
    return array_slice($tarihler, 0, $adet);
};

$db->beginTransaction();
try {
    $programIdleri = array_map(static fn(array $program): int => (int) $program['grup_id'], $programlar);
    $yerTutucular = implode(',', array_fill(0, count($programIdleri), '?'));

    $eskiRandevuSil = $db->prepare(
        'DELETE FROM randevular
         WHERE kurum_id = ? AND tarih >= ?
           AND (aciklama = "Zengin demo takvim randevusu"
                OR aciklama = "Demo takvim randevusu"
                OR aciklama = "Demo yenileme takvimi randevusu")'
    );
    $eskiRandevuSil->execute([$kurumId, $bugun->format('Y-m-d')]);
    $silinenRandevu = $eskiRandevuSil->rowCount();

    $uyelikSil = $db->prepare("DELETE FROM grup_ogrencileri WHERE kurum_id = ? AND grup_id IN ($yerTutucular)");
    $uyelikSil->execute(array_merge([$kurumId], $programIdleri));

    $uyelikEkle = $db->prepare(
        'INSERT INTO grup_ogrencileri (kurum_id, grup_id, ogrenci_id, baslangic_tarihi, aktif)
         VALUES (:kurum_id, :grup_id, :ogrenci_id, :baslangic, 1)'
    );
    $grupDurum = $db->prepare('UPDATE gruplar SET durum = :durum WHERE id = :id AND kurum_id = :kurum_id');
    foreach ($programlar as $index => $program) {
        $grupDurum->execute([
            'id' => $program['grup_id'],
            'kurum_id' => $kurumId,
            'durum' => $hedefler[$index] >= (int) $program['kontenjan'] ? 'doldu' : 'aktif',
        ]);
    }
    foreach ($atamalar as $ogrenciId => $programIndexleri) {
        foreach ($programIndexleri as $programIndex) {
            $uyelikEkle->execute([
                'kurum_id' => $kurumId,
                'grup_id' => $programlar[$programIndex]['grup_id'],
                'ogrenci_id' => $ogrenciId,
                'baslangic' => $bugun->format('Y-m-d'),
            ]);
        }
    }

    $paketGuncelle = $db->prepare(
        'UPDATE paketler
         SET kullanilan_normal_hak = :kullanilan_normal,
             kalan_normal_hak = :kalan_normal,
             kalan_telafi_hak = :kalan_telafi,
             tahmini_son_ders_tarihi = :bitis,
             yenileme_durumu = :yenileme
         WHERE id = :id AND kurum_id = :kurum_id'
    );
    $randevuEkle = $db->prepare(
        'INSERT INTO randevular
            (kurum_id, ogrenci_id, veli_id, grup_id, paket_id, ogretmen_id, tarih,
             baslangic_saati, bitis_saati, tur, hak_kaynagi, durum, aciklama,
             olusturan_kullanici_id, olusturulma_tarihi)
         VALUES
            (:kurum_id, :ogrenci_id, :veli_id, :grup_id, :paket_id, :ogretmen_id, :tarih,
             :baslangic, :bitis, "Normal ders", "Aktif paket", "planlandi", "Demo yenileme takvimi randevusu",
             :kullanici_id, NOW())'
    );

    $eklenenRandevu = 0;
    foreach ($grupPaketleri as $index => $paket) {
        $toplamHak = (int) $paket['toplam_normal_hak'];
        $kalanHak = min($toplamHak, 1 + ($index % 5));
        $tarihler = $sonrakiTarihler($atamalar[(int) $paket['ogrenci_id']], $kalanHak);
        if (count($tarihler) !== $kalanHak) {
            throw new RuntimeException('Paket icin yeterli gelecek randevu tarihi uretilemedi.');
        }
        foreach ($tarihler as $plan) {
            $program = $plan['program'];
            $randevuEkle->execute([
                'kurum_id' => $kurumId,
                'ogrenci_id' => $paket['ogrenci_id'],
                'veli_id' => (int) ($paket['veli_id'] ?? 0) ?: null,
                'grup_id' => $program['grup_id'],
                'paket_id' => $paket['paket_id'],
                'ogretmen_id' => (int) ($program['ogretmen_id'] ?? 0) ?: $kullaniciId,
                'tarih' => $plan['tarih'],
                'baslangic' => $program['baslangic_saati'],
                'bitis' => $program['bitis_saati'],
                'kullanici_id' => $kullaniciId,
            ]);
            $eklenenRandevu++;
        }
        $sonTarih = $tarihler[array_key_last($tarihler)]['tarih'];
        $toplamTelafi = (int) $paket['toplam_telafi_hak'];
        $paketGuncelle->execute([
            'id' => $paket['paket_id'],
            'kurum_id' => $kurumId,
            'kullanilan_normal' => $toplamHak - $kalanHak,
            'kalan_normal' => $kalanHak,
            'kalan_telafi' => $toplamTelafi > 0 ? $index % ($toplamTelafi + 1) : 0,
            'bitis' => $sonTarih,
            'yenileme' => $index % 3 === 0 ? 'yenilenecek' : 'belirsiz',
        ]);
    }

    $tekDersKontrol = $db->prepare(
        'SELECT MAX(tarih) FROM randevular
         WHERE kurum_id = :kurum_id AND paket_id = :paket_id AND tarih >= :bugun AND durum <> "kurum_iptali"'
    );
    foreach ($tekDersPaketleri as $index => $paket) {
        $tekDersKontrol->execute([
            'kurum_id' => $kurumId,
            'paket_id' => $paket['paket_id'],
            'bugun' => $bugun->format('Y-m-d'),
        ]);
        $randevuTarihi = (string) ($tekDersKontrol->fetchColumn() ?: '');
        if ($randevuTarihi === '') {
            $randevuTarihi = $bugun->modify('+' . (1 + ($index % 10)) . ' days')->format('Y-m-d');
            $randevuEkle->execute([
                'kurum_id' => $kurumId,
                'ogrenci_id' => $paket['ogrenci_id'],
                'veli_id' => (int) ($paket['veli_id'] ?? 0) ?: null,
                'grup_id' => null,
                'paket_id' => $paket['paket_id'],
                'ogretmen_id' => $kullaniciId,
                'tarih' => $randevuTarihi,
                'baslangic' => sprintf('%02d:00:00', 10 + ($index % 7)),
                'bitis' => sprintf('%02d:00:00', 11 + ($index % 7)),
                'kullanici_id' => $kullaniciId,
            ]);
            $eklenenRandevu++;
        }
        $paketGuncelle->execute([
            'id' => $paket['paket_id'],
            'kurum_id' => $kurumId,
            'kullanilan_normal' => 0,
            'kalan_normal' => 1,
            'kalan_telafi' => 0,
            'bitis' => $randevuTarihi,
            'yenileme' => 'belirsiz',
        ]);
    }

    $db->commit();
    fwrite(STDOUT, sprintf(
        "Demo planlama tamamlandi: %d eski gelecek randevu silindi, %d yeni randevu olusturuldu, %d grup programlandi.\n",
        $silinenRandevu,
        $eklenenRandevu,
        count($programlar)
    ));
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    throw $e;
}
