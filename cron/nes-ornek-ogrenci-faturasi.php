<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Config;
use App\Core\Session;
use App\Core\Veritabani;
use App\Models\Fatura;
use App\Models\KurumEntegrasyonu;
use App\Services\FaturaEntegrasyonServisi;

if (Config::get('APP_ENV', 'production') === 'production' || Config::get('ALLOW_NES_TEST_TOOLS', 'false') !== 'true') {
    throw new RuntimeException('NES test araci canli ortamda veya acik izin olmadan calistirilamaz.');
}
$kurumId = max(0, (int) Config::get('CLI_KURUM_ID', '0'));
if ($kurumId < 1) {
    throw new RuntimeException('CLI_KURUM_ID zorunludur.');
}
Session::set('kurum_id', $kurumId);

$integration = KurumEntegrasyonu::etkinNes() ?? throw new RuntimeException('Aktif NES entegrasyonu bulunamadi.');
if (($integration['ortam'] ?? '') !== 'test' || !str_contains((string) $integration['base_url'], 'apitest.nes.com.tr')) {
    throw new RuntimeException('Bu komut yalnizca NES test ortaminda calisir.');
}

$db = Veritabani::baglan();
$marker = 'NES_TEST_OGRENCI_FATURA_ORNEGI';
$existing = $db->prepare(
    'SELECT o.id AS ogrenci_id, od.id AS odeme_id
     FROM ogrenciler o
     INNER JOIN paketler p ON p.ogrenci_id=o.id AND p.kurum_id=o.kurum_id
     INNER JOIN odemeler od ON od.paket_id=p.id AND od.kurum_id=p.kurum_id
     WHERE o.kurum_id=:kurum_id AND o.yonetici_notu=:student_marker AND od.makbuz_numarasi=:payment_marker
     ORDER BY od.id DESC LIMIT 1'
);
$existing->execute(['kurum_id' => $kurumId, 'student_marker' => $marker, 'payment_marker' => $marker]);
$record = $existing->fetch();

if (!$record) {
    $db->beginTransaction();
    try {
        $userStmt = $db->prepare('SELECT id FROM kullanicilar WHERE kurum_id=:kurum_id AND aktif=1 ORDER BY sistem_yoneticisi DESC,id LIMIT 1');
        $userStmt->execute(['kurum_id' => $kurumId]);
        $userId = (int) $userStmt->fetchColumn();
        if ($userId < 1) {
            throw new RuntimeException('Test kaydini olusturacak aktif kullanici bulunamadi.');
        }

        $parent = $db->prepare(
            'INSERT INTO veliler (kurum_id,ad,soyad,tc_kimlik_no,telefon_ulke,telefon,eposta,yakinlik,il,ilce,adres,notlar)
             VALUES (:kurum_id,"Ayşe","Örnek Test","10000000146","Türkiye","05000000001","nes-test@example.invalid","Anne","ANTALYA","MURATPAŞA","Test Adresi",:marker)'
        );
        $parent->execute(['kurum_id' => $kurumId, 'marker' => $marker]);
        $parentId = (int) $db->lastInsertId();

        $student = $db->prepare(
            'INSERT INTO ogrenciler (kurum_id,ad,soyad,dogum_tarihi,cinsiyet,kayit_tarihi,durum,yonetici_notu)
             VALUES (:kurum_id,"Deniz","Örnek (NES TEST)","2021-01-01","belirtilmedi",CURRENT_DATE,"aktif",:marker)'
        );
        $student->execute(['kurum_id' => $kurumId, 'marker' => $marker]);
        $studentId = (int) $db->lastInsertId();

        $link = $db->prepare('INSERT INTO ogrenci_velileri (kurum_id,ogrenci_id,veli_id,birincil_mi,acil_durum_mu) VALUES (:kurum_id,:student,:parent,1,1)');
        $link->execute(['kurum_id' => $kurumId, 'student' => $studentId, 'parent' => $parentId]);

        $package = $db->prepare(
            'INSERT INTO paketler
             (kurum_id,ogrenci_id,paket_sira_no,paket_adi,haftalik_katilim_sayisi,toplam_normal_hak,toplam_telafi_hak,kalan_normal_hak,kalan_telafi_hak,baslangic_tarihi,liste_fiyati,net_paket_tutari,kdv_orani,tahsilat_notu,paket_durumu,yenileme_durumu,yonetici_notu,olusturan_kullanici_id)
             VALUES (:kurum_id,:student,1,"NES Test Paketi",1,1,0,1,0,CURRENT_DATE,1.00,1.00,10.00,"NES test faturası için örnek tahsilat.","aktif","belirsiz",:marker,:user)'
        );
        $package->execute(['kurum_id' => $kurumId, 'student' => $studentId, 'marker' => $marker, 'user' => $userId]);
        $packageId = (int) $db->lastInsertId();

        $payment = $db->prepare(
            'INSERT INTO odemeler (kurum_id,ogrenci_id,veli_id,paket_id,tarih,tutar,yontem,makbuz_numarasi,aciklama,alan_kullanici_id)
             VALUES (:kurum_id,:student,:parent,:package,CURRENT_DATE,1.00,"nakit",:marker,"NES test ortamı örnek tahsilatı.",:user)'
        );
        $payment->execute(['kurum_id' => $kurumId, 'student' => $studentId, 'parent' => $parentId, 'package' => $packageId, 'marker' => $marker, 'user' => $userId]);
        $paymentId = (int) $db->lastInsertId();
        $db->commit();
        $record = ['ogrenci_id' => $studentId, 'odeme_id' => $paymentId];
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}

$invoice = Fatura::odemeIcin((int) $record['odeme_id']);
if (!$invoice) {
    $invoice = (new FaturaEntegrasyonServisi())->olustur((int) $record['odeme_id'], [
        'profil_turu' => 'bireysel',
        'ad' => 'Ayşe',
        'soyad' => 'Örnek Test',
        'unvan' => '',
        'vkn_tckn' => '10000000146',
        'vergi_dairesi' => '',
        'adres' => 'Test Adresi',
        'il' => 'ANTALYA',
        'ilce' => 'MURATPAŞA',
        'ulke' => 'TÜRKİYE',
        'eposta' => 'nes-test@example.invalid',
        'telefon' => '05000000001',
        'not' => 'Deniz Örnek (NES TEST) öğrencisi için test faturasıdır. Mali değeri yoktur.',
    ], '10');
}

echo json_encode([
    'ogrenci_id' => (int) $record['ogrenci_id'],
    'odeme_id' => (int) $record['odeme_id'],
    'fatura_id' => (int) $invoice['id'],
    'fatura_no' => (string) $invoice['fatura_no'],
    'uuid' => (string) $invoice['ettn'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
