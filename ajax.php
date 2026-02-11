<?php
require_once("includes/config.php");
require_once("includes/functions.php");

$islem = $_POST['islem'] ?? $_POST['action'] ?? $_GET['islem'] ?? $_GET['action'] ?? '';
if ($islem === '') {
    json_yanit(false, 'Islem belirtilmedi.');
}

// AJAX giris kontrolu yapar ve kurum bilgisini dogrular.
$admin_giris = !empty($_SESSION['giris']);
$veli_giris = !empty($_SESSION['veli_giris']);
$veli_izinli = ['hak_kontrol', 'rezervasyon_yap', 'rezervasyon_iptal'];

if (!$admin_giris && !($veli_giris && in_array($islem, $veli_izinli, true))) {
    json_yanit(false, 'Oturum bulunamadi.');
}

$kurum_id = aktif_kurum_id();
if ($kurum_id <= 0) {
    json_yanit(false, 'Kurum bilgisi bulunamadi.');
}

switch ($islem) {
    case 'veli_kaydet':
        $veli_id = (int) ($_POST['veli_id'] ?? 0);
        $sube_id = (int) ($_POST['sube_id'] ?? aktif_sube_id());
        $ad_soyad = trim($_POST['ad_soyad'] ?? '');
        $telefon = trim($_POST['telefon'] ?? '');
        $eposta = trim($_POST['eposta'] ?? '');
        $sifre = $_POST['sifre'] ?? '';

        if ($sube_id <= 0 || $ad_soyad === '') {
            json_yanit(false, 'Veli bilgileri eksik.');
        }

        $data = [
            'sube_id' => $sube_id,
            'ad_soyad' => $ad_soyad,
            'telefon' => $telefon,
            'eposta' => $eposta,
        ];

        if ($sifre !== '') {
            $data['sifre'] = parola_hash($sifre);
        }

        if ($veli_id > 0) {
            $ok = update_data('veliler', $data, ['id' => $veli_id, 'kurum_id' => $kurum_id]);
            json_yanit((bool) $ok, $ok ? 'Veli guncellendi.' : 'Veli guncellenemedi.', ['id' => $veli_id]);
        }

        $data['kurum_id'] = $kurum_id;
        $new_id = insert_into('veliler', $data);
        json_yanit($new_id !== false, $new_id ? 'Veli kaydedildi.' : 'Veli kaydedilemedi.', ['id' => (int) $new_id]);

    case 'ogrenci_kaydet':
        $ogrenci_id = (int) ($_POST['ogrenci_id'] ?? 0);
        $veli_id = (int) ($_POST['veli_id'] ?? 0);
        $ad_soyad = trim($_POST['ad_soyad'] ?? '');
        $dogum_tarihi = trim($_POST['dogum_tarihi'] ?? '');
        $saglik_notlari = trim($_POST['saglik_notlari'] ?? '');

        if ($veli_id <= 0 || $ad_soyad === '' || $dogum_tarihi === '') {
            json_yanit(false, 'Ogrenci bilgileri eksik.');
        }

        $data = [
            'veli_id' => $veli_id,
            'ad_soyad' => $ad_soyad,
            'dogum_tarihi' => $dogum_tarihi,
            'saglik_notlari' => $saglik_notlari,
        ];

        if ($ogrenci_id > 0) {
            $ok = update_data('ogrenciler', $data, ['id' => $ogrenci_id, 'kurum_id' => $kurum_id]);
            json_yanit((bool) $ok, $ok ? 'Ogrenci guncellendi.' : 'Ogrenci guncellenemedi.', ['id' => $ogrenci_id]);
        }

        $data['kurum_id'] = $kurum_id;
        $new_id = insert_into('ogrenciler', $data);
        json_yanit($new_id !== false, $new_id ? 'Ogrenci kaydedildi.' : 'Ogrenci kaydedilemedi.', ['id' => (int) $new_id]);

    case 'ogrenci_liste':
        $veli_id = (int) ($_POST['veli_id'] ?? 0);
        $seans_id = (int) ($_POST['seans_id'] ?? 0);
        if ($veli_id <= 0) {
            json_yanit(false, 'Veli bilgisi eksik.');
        }

        $min_ay = null;
        $max_ay = null;
        if ($seans_id > 0) {
            $stmt = $db->prepare("SELECT g.min_ay, g.max_ay
                FROM seanslar s
                INNER JOIN oyun_gruplari g ON g.id = s.grup_id
                WHERE s.id = :seans_id AND s.kurum_id = :kurum_id
                LIMIT 1");
            $stmt->execute(['seans_id' => $seans_id, 'kurum_id' => $kurum_id]);
            $row = $stmt->fetch();
            if ($row) {
                $min_ay = $row['min_ay'] !== null ? (int) $row['min_ay'] : null;
                $max_ay = $row['max_ay'] !== null ? (int) $row['max_ay'] : null;
            }
        }

        $sql = "SELECT id, ad_soyad, dogum_tarihi
            FROM ogrenciler
            WHERE kurum_id = :kurum_id AND veli_id = :veli_id";
        $params = ['kurum_id' => $kurum_id, 'veli_id' => $veli_id];

        if ($min_ay !== null || $max_ay !== null) {
            $sql .= " AND dogum_tarihi IS NOT NULL";
            if ($min_ay !== null) {
                $sql .= " AND TIMESTAMPDIFF(MONTH, dogum_tarihi, CURDATE()) >= :min_ay";
                $params['min_ay'] = $min_ay;
            }
            if ($max_ay !== null) {
                $sql .= " AND TIMESTAMPDIFF(MONTH, dogum_tarihi, CURDATE()) <= :max_ay";
                $params['max_ay'] = $max_ay;
            }
        }

        $sql .= " ORDER BY ad_soyad";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        json_yanit(true, 'ok', ['ogrenciler' => $rows]);

    case 'veli_ogrenci_kaydet':
        $veli_id = (int) ($_POST['veli_id'] ?? 0);
        $ogrenci_id = (int) ($_POST['ogrenci_id'] ?? 0);
        $sube_id = (int) ($_POST['sube_id'] ?? aktif_sube_id());
        $veli_ad = trim($_POST['veli_ad'] ?? '');
        $veli_telefon = trim($_POST['veli_telefon'] ?? '');
        $veli_eposta = trim($_POST['veli_eposta'] ?? '');
        $ogrenci_ad = trim($_POST['ogrenci_ad'] ?? '');
        $dogum_tarihi = trim($_POST['dogum_tarihi'] ?? '');
        $saglik_notlari = trim($_POST['saglik_notlari'] ?? '');

        if ($veli_ad === '' || $ogrenci_ad === '') {
            json_yanit(false, 'Veli ve ogrenci bilgileri eksik.');
        }

        try {
            $db->beginTransaction();
            if ($veli_id > 0) {
                $ok = update_data('veliler', [
                    'sube_id' => $sube_id > 0 ? $sube_id : null,
                    'ad_soyad' => $veli_ad,
                    'telefon' => $veli_telefon,
                    'eposta' => $veli_eposta,
                ], ['id' => $veli_id, 'kurum_id' => $kurum_id]);
                if (!$ok) {
                    throw new Exception('Veli guncellenemedi.');
                }
            } else {
                $veli_id = (int) insert_into('veliler', [
                    'kurum_id' => $kurum_id,
                    'sube_id' => $sube_id > 0 ? $sube_id : null,
                    'ad_soyad' => $veli_ad,
                    'telefon' => $veli_telefon,
                    'eposta' => $veli_eposta,
                    'sifre' => null,
                ]);
                if ($veli_id <= 0) {
                    throw new Exception('Veli kaydedilemedi.');
                }
            }

            $ogr_data = [
                'veli_id' => $veli_id,
                'ad_soyad' => $ogrenci_ad,
                'dogum_tarihi' => $dogum_tarihi !== '' ? $dogum_tarihi : null,
                'saglik_notlari' => $saglik_notlari,
            ];

            if ($ogrenci_id > 0) {
                $ok = update_data('ogrenciler', $ogr_data, ['id' => $ogrenci_id, 'kurum_id' => $kurum_id]);
                if (!$ok) {
                    throw new Exception('Ogrenci guncellenemedi.');
                }
            } else {
                $ogr_data['kurum_id'] = $kurum_id;
                $new_id = insert_into('ogrenciler', $ogr_data);
                if (!$new_id) {
                    throw new Exception('Ogrenci kaydedilemedi.');
                }
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            error_log('Veli-ogrenci hata: ' . $e->getMessage());
            json_yanit(false, 'Veli/ogrenci kaydedilemedi.');
        }

        json_yanit(true, 'Veli/ogrenci kaydedildi.');

    case 'veli_ogrenci_cikar':
        $ogrenci_id = (int) ($_POST['ogrenci_id'] ?? 0);
        $onay = (int) ($_POST['onay'] ?? 0);
        if ($ogrenci_id <= 0) {
            json_yanit(false, 'Öğrenci bilgisi eksik.');
        }

        $stmt = $db->prepare("SELECT o.id, o.veli_id, v.bakiye_hak
            FROM ogrenciler o
            INNER JOIN veliler v ON v.id = o.veli_id
            WHERE o.id = :ogrenci_id AND o.kurum_id = :kurum_id
            LIMIT 1");
        $stmt->execute(['ogrenci_id' => $ogrenci_id, 'kurum_id' => $kurum_id]);
        $row = $stmt->fetch();
        if (!$row) {
            json_yanit(false, 'Öğrenci bulunamadı.');
        }
        $veli_id = (int) $row['veli_id'];
        $bakiye_hak = (int) ($row['bakiye_hak'] ?? 0);

        $stmt = $db->prepare("SELECT COUNT(*) FROM veli_borclar
            WHERE kurum_id = :kurum_id AND veli_id = :veli_id AND durum = 'beklemede'");
        $stmt->execute(['kurum_id' => $kurum_id, 'veli_id' => $veli_id]);
        $borc_sayisi = (int) $stmt->fetchColumn();

        if (($bakiye_hak > 0 || $borc_sayisi > 0) && $onay !== 1) {
            $msg = 'Bu velinin ';
            $detay = [];
            if ($bakiye_hak > 0) {
                $detay[] = $bakiye_hak . ' hak';
            }
            if ($borc_sayisi > 0) {
                $detay[] = $borc_sayisi . ' adet bekleyen borcu';
            }
            $msg .= implode(' ve ', $detay) . ' var. Yine de kurumdan çıkarmak istiyor musunuz?';
            json_yanit(true, $msg, ['durum' => 'uyari']);
        }

        try {
            $db->beginTransaction();
            $db->prepare("UPDATE ogrenciler SET kurum_id = NULL WHERE id = :id AND kurum_id = :kurum_id")
                ->execute(['id' => $ogrenci_id, 'kurum_id' => $kurum_id]);

            $stmt = $db->prepare("SELECT COUNT(*) FROM ogrenciler WHERE veli_id = :veli_id AND kurum_id = :kurum_id");
            $stmt->execute(['veli_id' => $veli_id, 'kurum_id' => $kurum_id]);
            $kalan = (int) $stmt->fetchColumn();
            if ($kalan === 0) {
                $db->prepare("UPDATE veliler SET kurum_id = NULL, sube_id = NULL WHERE id = :id AND kurum_id = :kurum_id")
                    ->execute(['id' => $veli_id, 'kurum_id' => $kurum_id]);
            }

            $db->commit();
        } catch (PDOException $e) {
            $db->rollBack();
            error_log('Veli/ogrenci cikar hata: ' . $e->getMessage());
            json_yanit(false, 'Kurumdan çıkarma işlemi başarısız.');
        }

        json_yanit(true, 'Kurum bağlantısı kaldırıldı.');

    case 'grup_kaydet':
        $grup_id = (int) ($_POST['grup_id'] ?? 0);
        $sube_id = (int) ($_POST['sube_id'] ?? aktif_sube_id());
        $alan_id = (int) ($_POST['alan_id'] ?? 0);
        $grup_adi = trim($_POST['grup_adi'] ?? '');
        $min_ay = (int) ($_POST['min_ay'] ?? 0);
        $max_ay = (int) ($_POST['max_ay'] ?? 0);
        $kapasite = (int) ($_POST['kapasite'] ?? 0);
        $tekrar_tipi = trim($_POST['tekrar_tipi'] ?? 'tekil');
        $tekrar_gunleri = $_POST['tekrar_gunleri'] ?? null;
        $seans_baslangic_saati = trim($_POST['seans_baslangic_saati'] ?? '');
        $seans_suresi_dk = (int) ($_POST['seans_suresi_dk'] ?? 0);
        $baslangic_tarihi = trim($_POST['baslangic_tarihi'] ?? '');
        $bitis_tarihi = trim($_POST['bitis_tarihi'] ?? '');

        if ($sube_id <= 0 || $grup_adi === '' || $kapasite <= 0) {
            json_yanit(false, 'Grup bilgileri eksik.');
        }

        if (is_array($tekrar_gunleri)) {
            $tekrar_gunleri = implode(',', $tekrar_gunleri);
        }

        $data = [
            'sube_id' => $sube_id,
            'alan_id' => $alan_id > 0 ? $alan_id : null,
            'grup_adi' => $grup_adi,
            'min_ay' => $min_ay,
            'max_ay' => $max_ay,
            'kapasite' => $kapasite,
            'tekrar_tipi' => $tekrar_tipi,
            'tekrar_gunleri' => $tekrar_gunleri,
            'seans_baslangic_saati' => $seans_baslangic_saati !== '' ? $seans_baslangic_saati : null,
            'seans_suresi_dk' => $seans_suresi_dk > 0 ? $seans_suresi_dk : null,
            'baslangic_tarihi' => $baslangic_tarihi !== '' ? $baslangic_tarihi : null,
            'bitis_tarihi' => $bitis_tarihi !== '' ? $bitis_tarihi : null,
        ];

        if ($grup_id > 0) {
            $ok = update_data('oyun_gruplari', $data, ['id' => $grup_id, 'kurum_id' => $kurum_id]);
            json_yanit((bool) $ok, $ok ? 'Grup guncellendi.' : 'Grup guncellenemedi.', ['id' => $grup_id]);
        }

        $data['kurum_id'] = $kurum_id;
        $new_id = insert_into('oyun_gruplari', $data);
        json_yanit($new_id !== false, $new_id ? 'Grup kaydedildi.' : 'Grup kaydedilemedi.', ['id' => (int) $new_id]);

    case 'seans_kaydet':
        $seans_id = (int) ($_POST['seans_id'] ?? 0);
        $grup_id = (int) ($_POST['grup_id'] ?? 0);
        $seans_baslangic = trim($_POST['seans_baslangic'] ?? '');
        $seans_bitis = trim($_POST['seans_bitis'] ?? '');
        $kontenjan = (int) ($_POST['kontenjan'] ?? 0);

        if ($grup_id <= 0 || $seans_baslangic === '' || $seans_bitis === '' || $kontenjan <= 0) {
            json_yanit(false, 'Seans bilgileri eksik.');
        }

        $data = [
            'grup_id' => $grup_id,
            'seans_baslangic' => $seans_baslangic,
            'seans_bitis' => $seans_bitis,
            'kontenjan' => $kontenjan,
        ];

        if ($seans_id > 0) {
            $ok = update_data('seanslar', $data, ['id' => $seans_id, 'kurum_id' => $kurum_id]);
            json_yanit((bool) $ok, $ok ? 'Seans guncellendi.' : 'Seans guncellenemedi.', ['id' => $seans_id]);
        }

        $data['kurum_id'] = $kurum_id;
        $new_id = insert_into('seanslar', $data);
        json_yanit($new_id !== false, $new_id ? 'Seans kaydedildi.' : 'Seans kaydedilemedi.', ['id' => (int) $new_id]);

    case 'seans_toplu_olustur':
        $grup_id = (int) ($_POST['grup_id'] ?? 0);
        $periyot = trim($_POST['periyot'] ?? 'haftalik');
        $hafta_sayisi = (int) ($_POST['hafta_sayisi'] ?? 1);
        $baslangic_tarihi = trim($_POST['baslangic_tarihi'] ?? '');
        $baslangic_saat = trim($_POST['baslangic_saat'] ?? '');
        $seans_suresi_dk = (int) ($_POST['seans_suresi_dk'] ?? 0);
        $kontenjan = (int) ($_POST['kontenjan'] ?? 0);
        $gunler = $_POST['gunler'] ?? [];

        if ($grup_id <= 0 || $baslangic_tarihi === '' || $baslangic_saat === '') {
            json_yanit(false, 'Toplu seans bilgileri eksik.');
        }
        if (!is_array($gunler) || empty($gunler)) {
            json_yanit(false, 'Gun secimi yapilmalidir.');
        }
        if (!in_array($periyot, ['haftalik', 'aylik'], true)) {
            $periyot = 'haftalik';
        }
        if ($periyot === 'haftalik' && $hafta_sayisi <= 0) {
            $hafta_sayisi = 1;
        }

        // grup bilgisi (kontenjan ve sure icin)
        $stmt = $db->prepare("SELECT kapasite, seans_suresi_dk FROM oyun_gruplari WHERE id = :id AND kurum_id = :kurum_id LIMIT 1");
        $stmt->execute(['id' => $grup_id, 'kurum_id' => $kurum_id]);
        $grup = $stmt->fetch();
        if (!$grup) {
            json_yanit(false, 'Grup bulunamadi.');
        }
        if ($seans_suresi_dk <= 0) {
            $seans_suresi_dk = (int) ($grup['seans_suresi_dk'] ?? 0);
        }
        if ($kontenjan <= 0) {
            $kontenjan = (int) ($grup['kapasite'] ?? 0);
        }
        if ($seans_suresi_dk <= 0 || $kontenjan <= 0) {
            json_yanit(false, 'Seans suresi veya kontenjan bilgisi eksik.');
        }

        $gun_map = [
            'Pzt' => 1,
            'Sal' => 2,
            'Car' => 3,
            'Per' => 4,
            'Cum' => 5,
            'Cmt' => 6,
            'Paz' => 7,
        ];
        $gun_numbers = [];
        foreach ($gunler as $gun) {
            if (isset($gun_map[$gun])) {
                $gun_numbers[] = $gun_map[$gun];
            }
        }
        if (empty($gun_numbers)) {
            json_yanit(false, 'Gun secimi gecersiz.');
        }

        try {
            $start = new DateTime($baslangic_tarihi);
        } catch (Exception $e) {
            json_yanit(false, 'Baslangic tarihi gecersiz.');
        }
        $end = clone $start;
        if ($periyot === 'aylik') {
            $end->modify('last day of this month');
        } else {
            $days = ($hafta_sayisi * 7) - 1;
            if ($days < 0) {
                $days = 0;
            }
            $end->modify('+' . $days . ' days');
        }

        $inserted = 0;
        $skipped = 0;
        $current = clone $start;

        try {
            $db->beginTransaction();
            $check_stmt = $db->prepare("SELECT 1 FROM seanslar
                WHERE kurum_id = :kurum_id AND grup_id = :grup_id AND seans_baslangic = :baslangic
                LIMIT 1");
            $ins_stmt = $db->prepare("INSERT INTO seanslar (kurum_id, grup_id, seans_baslangic, seans_bitis, kontenjan)
                VALUES (:kurum_id, :grup_id, :baslangic, :bitis, :kontenjan)");

            while ($current <= $end) {
                $day_num = (int) $current->format('N');
                if (in_array($day_num, $gun_numbers, true)) {
                    $start_dt = new DateTime($current->format('Y-m-d') . ' ' . $baslangic_saat . ':00');
                    $end_dt = clone $start_dt;
                    $end_dt->modify('+' . $seans_suresi_dk . ' minutes');

                    $check_stmt->execute([
                        'kurum_id' => $kurum_id,
                        'grup_id' => $grup_id,
                        'baslangic' => $start_dt->format('Y-m-d H:i:s'),
                    ]);
                    if ($check_stmt->fetchColumn()) {
                        $skipped++;
                    } else {
                        $ins_stmt->execute([
                            'kurum_id' => $kurum_id,
                            'grup_id' => $grup_id,
                            'baslangic' => $start_dt->format('Y-m-d H:i:s'),
                            'bitis' => $end_dt->format('Y-m-d H:i:s'),
                            'kontenjan' => $kontenjan,
                        ]);
                        $inserted++;
                    }
                }
                $current->modify('+1 day');
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            error_log('Toplu seans hata: ' . $e->getMessage());
            json_yanit(false, 'Toplu seanslar olusturulamadi.');
        }

        json_yanit(true, "Toplu seanslar olusturuldu. Eklenen: {$inserted}, Atlanan: {$skipped}.", [
            'inserted' => $inserted,
            'skipped' => $skipped,
        ]);

    case 'kurum_profil_kaydet':
        if (empty($db_master)) {
            json_yanit(false, 'Master veritabani baglantisi bulunamadi.');
        }
        $kurum_adi = trim($_POST['kurum_adi'] ?? '');
        if ($kurum_adi === '') {
            json_yanit(false, 'Kurum adi zorunludur.');
        }
        $data = [
            'kurum_adi' => $kurum_adi,
            'sehir' => trim($_POST['sehir'] ?? ''),
            'ilce' => trim($_POST['ilce'] ?? ''),
            'adres' => trim($_POST['adres'] ?? ''),
            'telefon' => trim($_POST['telefon'] ?? ''),
            'eposta' => trim($_POST['eposta'] ?? ''),
            'hakkimizda' => trim($_POST['hakkimizda'] ?? ''),
            'meb_onay' => (int) ($_POST['meb_onay'] ?? 0),
            'aile_sosyal_onay' => (int) ($_POST['aile_sosyal_onay'] ?? 0),
            'hizmet_bahceli' => (int) ($_POST['hizmet_bahceli'] ?? 0),
            'hizmet_guvenlik_kamerasi' => (int) ($_POST['hizmet_guvenlik_kamerasi'] ?? 0),
            'hizmet_ingilizce' => (int) ($_POST['hizmet_ingilizce'] ?? 0),
            'min_ay' => ($_POST['min_ay'] ?? '') !== '' ? (int) $_POST['min_ay'] : null,
            'max_ay' => ($_POST['max_ay'] ?? '') !== '' ? (int) $_POST['max_ay'] : null,
        ];

        $sql = "UPDATE kurumlar
                SET kurum_adi = :kurum_adi,
                    sehir = :sehir,
                    ilce = :ilce,
                    adres = :adres,
                    telefon = :telefon,
                    eposta = :eposta,
                    hakkimizda = :hakkimizda,
                    meb_onay = :meb_onay,
                    aile_sosyal_onay = :aile_sosyal_onay,
                    hizmet_bahceli = :hizmet_bahceli,
                    hizmet_guvenlik_kamerasi = :hizmet_guvenlik_kamerasi,
                    hizmet_ingilizce = :hizmet_ingilizce,
                    min_ay = :min_ay,
                    max_ay = :max_ay
                WHERE id = :id";
        $data['id'] = $kurum_id;
        $ok = $db_master->prepare($sql)->execute($data);
        json_yanit((bool) $ok, $ok ? 'Kurum profili guncellendi.' : 'Kurum profili guncellenemedi.');

    case 'kurum_galeri_ekle':
        if (empty($db_master)) {
            json_yanit(false, 'Master veritabani baglantisi bulunamadi.');
        }
        $sira = (int) ($_POST['sira'] ?? 0);
        if (empty($_FILES['gorsel']) || !is_array($_FILES['gorsel'])) {
            json_yanit(false, 'Görsel dosyası zorunludur.');
        }
        $files = $_FILES['gorsel'];
        $names = $files['name'] ?? [];
        if (!is_array($names)) {
            $files = [
                'name' => [$files['name']],
                'type' => [$files['type']],
                'tmp_name' => [$files['tmp_name']],
                'error' => [$files['error']],
                'size' => [$files['size']],
            ];
        }

        $upload_base_dir = rtrim($GLOBALS['upload_base_dir'] ?? (__DIR__ . '/uploads'), '/');
        $upload_base_url = rtrim($GLOBALS['upload_base_url'] ?? '/uploads', '/');
        $upload_dir = $GLOBALS['galeri_upload_dir'] ?? ($upload_base_dir . '/kurum_galeri');
        $upload_url_base = $GLOBALS['galeri_upload_url'] ?? ($upload_base_url . '/kurum_galeri');
        if (!is_dir($upload_base_dir)) {
            @mkdir($upload_base_dir, 0755, true);
        }
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0755, true);
        }
        if (!is_dir($upload_dir) || !is_writable($upload_dir)) {
            json_yanit(false, 'Yükleme dizini yazılabilir değil. Sunucu izinlerini kontrol edin: ' . $upload_dir);
        }

        $allowed_ext = ['jpg', 'jpeg', 'png'];
        $allowed_mime = ['image/jpeg', 'image/png'];
        $added = 0;
        $skipped = 0;
        $errors = [];

        $error_map = [
            UPLOAD_ERR_INI_SIZE => 'Dosya boyutu sunucu limitini aşıyor.',
            UPLOAD_ERR_FORM_SIZE => 'Dosya boyutu form limitini aşıyor.',
            UPLOAD_ERR_PARTIAL => 'Dosya kısmen yüklendi.',
            UPLOAD_ERR_NO_FILE => 'Dosya seçilmedi.',
            UPLOAD_ERR_NO_TMP_DIR => 'Geçici klasör bulunamadı.',
            UPLOAD_ERR_CANT_WRITE => 'Dosya diske yazılamadı.',
            UPLOAD_ERR_EXTENSION => 'Yükleme, bir PHP eklentisi tarafından durduruldu.',
        ];

        foreach ($files['name'] as $idx => $name) {
            $file_error = $files['error'][$idx] ?? UPLOAD_ERR_NO_FILE;
            if ($file_error !== UPLOAD_ERR_OK) {
                $skipped++;
                $errors[] = $error_map[$file_error] ?? 'Dosya yüklenemedi.';
                continue;
            }
            $file_size = $files['size'][$idx] ?? 0;
            if ($file_size > 5 * 1024 * 1024) {
                $skipped++;
                $errors[] = 'Dosya boyutu en fazla 5MB olmalıdır.';
                continue;
            }

            $ext = strtolower(pathinfo($name ?? '', PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_ext, true)) {
                $skipped++;
                $errors[] = 'Sadece JPG, JPEG veya PNG dosyaları yüklenebilir.';
                continue;
            }

            $tmp_name = $files['tmp_name'][$idx] ?? '';
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? finfo_file($finfo, $tmp_name) : '';
            if ($finfo) {
                finfo_close($finfo);
            }
            if (!in_array($mime, $allowed_mime, true)) {
                $skipped++;
                $errors[] = 'Dosya tipi geçersiz.';
                continue;
            }

            $filename = 'kurum_' . $kurum_id . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $target_path = $upload_dir . '/' . $filename;
            if (!move_uploaded_file($tmp_name, $target_path)) {
                $skipped++;
                $errors[] = 'Dosya kaydedilemedi.';
                continue;
            }

            $gorsel_yol = rtrim($upload_url_base, '/') . '/' . $filename;
            $stmt = $db_master->prepare("INSERT INTO kurum_galeri (kurum_id, gorsel_yol, sira)
                VALUES (:kurum_id, :gorsel_yol, :sira)");
            $ok = $stmt->execute([
                'kurum_id' => $kurum_id,
                'gorsel_yol' => $gorsel_yol,
                'sira' => $sira,
            ]);
            if (!$ok) {
                @unlink($target_path);
                $skipped++;
                $errors[] = 'Görsel kaydedilemedi.';
                continue;
            }
            $added++;
            $sira++;
        }

        if ($added <= 0) {
            $msg = !empty($errors) ? $errors[0] : 'Görsel eklenemedi.';
            json_yanit(false, $msg);
        }
        $msg = "Görsel eklendi. Eklenen: {$added}";
        if ($skipped > 0) {
            $msg .= ", Atlanan: {$skipped}";
        }
        json_yanit(true, $msg, ['eklenen' => $added, 'atlanan' => $skipped]);

    case 'kurum_galeri_sil':
        if (empty($db_master)) {
            json_yanit(false, 'Master veritabani baglantisi bulunamadi.');
        }
        $galeri_id = (int) ($_POST['galeri_id'] ?? 0);
        if ($galeri_id <= 0) {
            json_yanit(false, 'Görsel bilgisi eksik.');
        }
        $stmt = $db_master->prepare("SELECT gorsel_yol FROM kurum_galeri WHERE id = :id AND kurum_id = :kurum_id LIMIT 1");
        $stmt->execute(['id' => $galeri_id, 'kurum_id' => $kurum_id]);
        $row = $stmt->fetch();

        $stmt = $db_master->prepare("DELETE FROM kurum_galeri WHERE id = :id AND kurum_id = :kurum_id");
        $ok = $stmt->execute(['id' => $galeri_id, 'kurum_id' => $kurum_id]);
        if ($ok && !empty($row['gorsel_yol'])) {
            $upload_base_dir = rtrim($GLOBALS['upload_base_dir'] ?? (__DIR__ . '/uploads'), '/');
            $upload_base_url = rtrim($GLOBALS['upload_base_url'] ?? '/uploads', '/');
            $upload_dir = $GLOBALS['galeri_upload_dir'] ?? ($upload_base_dir . '/kurum_galeri');
            $upload_url_base = $GLOBALS['galeri_upload_url'] ?? ($upload_base_url . '/kurum_galeri');
            $gorsel_path = $row['gorsel_yol'];
            $parsed = parse_url($gorsel_path);
            $gorsel_path = $parsed['path'] ?? $gorsel_path;
            $path = '';
            if (strpos($gorsel_path, $upload_url_base) === 0) {
                $relative = ltrim(substr($gorsel_path, strlen($upload_url_base)), '/');
                $path = rtrim($upload_dir, '/') . '/' . $relative;
            }
            if ($path !== '' && is_file($path)) {
                @unlink($path);
            }
        }
        json_yanit((bool) $ok, $ok ? 'Görsel silindi.' : 'Görsel silinemedi.');

    case 'kurum_galeri_sirala':
        if (empty($db_master)) {
            json_yanit(false, 'Master veritabani baglantisi bulunamadi.');
        }
        $ids = $_POST['galeri_ids'] ?? [];
        if (!is_array($ids) || empty($ids)) {
            json_yanit(false, 'Sıralama bilgisi eksik.');
        }
        try {
            $db_master->beginTransaction();
            $stmt = $db_master->prepare("UPDATE kurum_galeri SET sira = :sira WHERE id = :id AND kurum_id = :kurum_id");
            $sira_val = 0;
            foreach ($ids as $id) {
                $gid = (int) $id;
                if ($gid <= 0) {
                    continue;
                }
                $stmt->execute([
                    'sira' => $sira_val,
                    'id' => $gid,
                    'kurum_id' => $kurum_id,
                ]);
                $sira_val++;
            }
            $db_master->commit();
        } catch (PDOException $e) {
            $db_master->rollBack();
            error_log('Galeri siralama hata: ' . $e->getMessage());
            json_yanit(false, 'Sıralama kaydedilemedi.');
        }
        json_yanit(true, 'Sıralama kaydedildi.');

    case 'sube_kaydet':
        $sube_id = (int) ($_POST['sube_id'] ?? 0);
        $sube_adi = trim($_POST['sube_adi'] ?? '');
        $sehir = trim($_POST['sehir'] ?? '');
        $ilce = trim($_POST['ilce'] ?? '');
        $adres = trim($_POST['adres'] ?? '');

        if ($sube_adi === '') {
            json_yanit(false, 'Sube adi zorunludur.');
        }

        $data = [
            'sube_adi' => $sube_adi,
            'sehir' => $sehir,
            'ilce' => $ilce,
            'adres' => $adres,
        ];

        if ($sube_id > 0) {
            $ok = update_data('subeler', $data, ['id' => $sube_id, 'kurum_id' => $kurum_id]);
            json_yanit((bool) $ok, $ok ? 'Sube guncellendi.' : 'Sube guncellenemedi.');
        }

        $data['kurum_id'] = $kurum_id;
        $new_id = insert_into('subeler', $data);
        json_yanit($new_id !== false, $new_id ? 'Sube kaydedildi.' : 'Sube kaydedilemedi.', ['id' => (int) $new_id]);

    case 'sube_sil':
        $sube_id = (int) ($_POST['sube_id'] ?? 0);
        if ($sube_id <= 0) {
            json_yanit(false, 'Sube bilgisi eksik.');
        }
        $ok = delete_data('subeler', ['id' => $sube_id, 'kurum_id' => $kurum_id]);
        json_yanit((bool) $ok, $ok ? 'Sube silindi.' : 'Sube silinemedi.');

    case 'kurum_alani_kaydet':
        $alan_id = (int) ($_POST['alan_id'] ?? 0);
        $alan_adi = trim($_POST['alan_adi'] ?? '');
        $kapasite = (int) ($_POST['kapasite'] ?? 0);
        $aciklama = trim($_POST['aciklama'] ?? '');
        $durum = (int) ($_POST['durum'] ?? 1) ? 1 : 0;

        if ($alan_adi === '') {
            json_yanit(false, 'Alan adi zorunludur.');
        }

        $data = [
            'alan_adi' => $alan_adi,
            'kapasite' => $kapasite,
            'aciklama' => $aciklama,
            'durum' => $durum,
        ];

        if ($alan_id > 0) {
            $ok = update_data('kurum_alanlari', $data, ['id' => $alan_id, 'kurum_id' => $kurum_id]);
            json_yanit((bool) $ok, $ok ? 'Alan guncellendi.' : 'Alan guncellenemedi.');
        }

        $data['kurum_id'] = $kurum_id;
        $new_id = insert_into('kurum_alanlari', $data);
        json_yanit($new_id !== false, $new_id ? 'Alan kaydedildi.' : 'Alan kaydedilemedi.', ['id' => (int) $new_id]);

    case 'kurum_alani_sil':
        $alan_id = (int) ($_POST['alan_id'] ?? 0);
        if ($alan_id <= 0) {
            json_yanit(false, 'Alan bilgisi eksik.');
        }
        $ok = delete_data('kurum_alanlari', ['id' => $alan_id, 'kurum_id' => $kurum_id]);
        json_yanit((bool) $ok, $ok ? 'Alan silindi.' : 'Alan silinemedi.');

    case 'egitmen_kaydet':
        if (empty($db_master)) {
            json_yanit(false, 'Master veritabani baglantisi bulunamadi.');
        }
        $egitmen_id = (int) ($_POST['egitmen_id'] ?? 0);
        $ad_soyad = trim($_POST['ad_soyad'] ?? '');
        $uzmanlik = trim($_POST['uzmanlik'] ?? '');
        $biyografi = trim($_POST['biyografi'] ?? '');
        $fotograf_yol = trim($_POST['fotograf_yol'] ?? '');

        if ($ad_soyad === '') {
            json_yanit(false, 'Egitmen adi zorunludur.');
        }

        if ($egitmen_id > 0) {
            $sql = "UPDATE kurum_egitmenler
                    SET ad_soyad = :ad_soyad,
                        uzmanlik = :uzmanlik,
                        biyografi = :biyografi,
                        fotograf_yol = :fotograf_yol
                    WHERE id = :id AND kurum_id = :kurum_id";
            $ok = $db_master->prepare($sql)->execute([
                'ad_soyad' => $ad_soyad,
                'uzmanlik' => $uzmanlik,
                'biyografi' => $biyografi,
                'fotograf_yol' => $fotograf_yol,
                'id' => $egitmen_id,
                'kurum_id' => $kurum_id,
            ]);
            json_yanit((bool) $ok, $ok ? 'Egitmen guncellendi.' : 'Egitmen guncellenemedi.');
        }

        $sql = "INSERT INTO kurum_egitmenler (kurum_id, ad_soyad, uzmanlik, biyografi, fotograf_yol)
                VALUES (:kurum_id, :ad_soyad, :uzmanlik, :biyografi, :fotograf_yol)";
        $ok = $db_master->prepare($sql)->execute([
            'kurum_id' => $kurum_id,
            'ad_soyad' => $ad_soyad,
            'uzmanlik' => $uzmanlik,
            'biyografi' => $biyografi,
            'fotograf_yol' => $fotograf_yol,
        ]);
        json_yanit((bool) $ok, $ok ? 'Egitmen kaydedildi.' : 'Egitmen kaydedilemedi.');

    case 'egitmen_sil':
        if (empty($db_master)) {
            json_yanit(false, 'Master veritabani baglantisi bulunamadi.');
        }
        $egitmen_id = (int) ($_POST['egitmen_id'] ?? 0);
        if ($egitmen_id <= 0) {
            json_yanit(false, 'Egitmen bilgisi eksik.');
        }
        $stmt = $db_master->prepare("DELETE FROM kurum_egitmenler WHERE id = :id AND kurum_id = :kurum_id");
        $ok = $stmt->execute(['id' => $egitmen_id, 'kurum_id' => $kurum_id]);
        json_yanit((bool) $ok, $ok ? 'Egitmen silindi.' : 'Egitmen silinemedi.');

    case 'aday_kaydet':
        $aday_id = (int) ($_POST['aday_id'] ?? 0);
        $veli_id = (int) ($_POST['veli_id'] ?? 0);
        $ogrenci_id = (int) ($_POST['ogrenci_id'] ?? 0);
        $sube_id = (int) ($_POST['sube_id'] ?? 0);
        $ad_soyad = trim($_POST['ad_soyad'] ?? '');
        $telefon = trim($_POST['telefon'] ?? '');
        $eposta = trim($_POST['eposta'] ?? '');
        $yas_ay = ($_POST['yas_ay'] ?? '') !== '' ? (int) $_POST['yas_ay'] : null;
        $notlar = trim($_POST['notlar'] ?? '');
        $donustur = (int) ($_POST['donustur'] ?? 0) === 1;
        $veli_ad = trim($_POST['veli_ad'] ?? '');
        $veli_telefon = trim($_POST['veli_telefon'] ?? '');
        $veli_eposta = trim($_POST['veli_eposta'] ?? '');
        $ogrenci_ad = trim($_POST['ogrenci_ad'] ?? '');
        $dogum_tarihi = trim($_POST['dogum_tarihi'] ?? '');
        $saglik_notlari = trim($_POST['saglik_notlari'] ?? '');

        if ($ad_soyad === '' || $telefon === '') {
            json_yanit(false, 'Aday bilgileri eksik.');
        }

        $data = [
            'sube_id' => $sube_id > 0 ? $sube_id : null,
            'ad_soyad' => $ad_soyad,
            'telefon' => $telefon,
            'eposta' => $eposta,
            'yas_ay' => $yas_ay,
            'notlar' => $notlar,
        ];

        if (!$donustur) {
            if ($aday_id > 0) {
                $ok = update_data('adaylar', $data, ['id' => $aday_id, 'kurum_id' => $kurum_id]);
                json_yanit((bool) $ok, $ok ? 'Aday guncellendi.' : 'Aday guncellenemedi.');
            }
            $data['kurum_id'] = $kurum_id;
            $new_id = insert_into('adaylar', $data);
            json_yanit($new_id !== false, $new_id ? 'Aday kaydedildi.' : 'Aday kaydedilemedi.', ['id' => (int) $new_id]);
        }

        if ($sube_id <= 0) {
            json_yanit(false, 'Dönüşüm için şube seçilmelidir.');
        }
        if ($veli_ad === '') {
            $veli_ad = $ad_soyad;
        }
        if ($ogrenci_ad === '') {
            $ogrenci_ad = $ad_soyad;
        }

        try {
            $db->beginTransaction();

            if ($aday_id > 0) {
                $ok = update_data('adaylar', $data, ['id' => $aday_id, 'kurum_id' => $kurum_id]);
                if (!$ok) {
                    throw new Exception('Aday guncellenemedi.');
                }
            } else {
                $data['kurum_id'] = $kurum_id;
                $aday_id = (int) insert_into('adaylar', $data);
                if ($aday_id <= 0) {
                    throw new Exception('Aday kaydedilemedi.');
                }
            }

            if ($veli_id > 0) {
                $ok = update_data('veliler', [
                    'sube_id' => $sube_id,
                    'ad_soyad' => $veli_ad,
                    'telefon' => $veli_telefon,
                    'eposta' => $veli_eposta,
                ], ['id' => $veli_id, 'kurum_id' => $kurum_id]);
                if (!$ok) {
                    throw new Exception('Veli guncellenemedi.');
                }
            } else {
                if ($veli_telefon !== '' || $veli_eposta !== '') {
                    $stmt = $db->prepare("SELECT id FROM veliler
                        WHERE kurum_id = :kurum_id AND (telefon = :telefon OR eposta = :eposta)
                        LIMIT 1");
                    $stmt->execute([
                        'kurum_id' => $kurum_id,
                        'telefon' => $veli_telefon,
                        'eposta' => $veli_eposta,
                    ]);
                    $veli_id = (int) ($stmt->fetchColumn() ?? 0);
                }
                if ($veli_id <= 0) {
                    $stmt = $db->prepare("INSERT INTO veliler (kurum_id, sube_id, ad_soyad, telefon, eposta, sifre)
                        VALUES (:kurum_id, :sube_id, :ad_soyad, :telefon, :eposta, :sifre)");
                    $stmt->execute([
                        'kurum_id' => $kurum_id,
                        'sube_id' => $sube_id,
                        'ad_soyad' => $veli_ad,
                        'telefon' => $veli_telefon,
                        'eposta' => $veli_eposta,
                        'sifre' => null,
                    ]);
                    $veli_id = (int) $db->lastInsertId();
                }
            }

            if ($ogrenci_id > 0) {
                $ok = update_data('ogrenciler', [
                    'veli_id' => $veli_id,
                    'ad_soyad' => $ogrenci_ad,
                    'dogum_tarihi' => $dogum_tarihi !== '' ? $dogum_tarihi : null,
                    'saglik_notlari' => $saglik_notlari,
                ], ['id' => $ogrenci_id, 'kurum_id' => $kurum_id]);
                if (!$ok) {
                    throw new Exception('Ogrenci guncellenemedi.');
                }
            } else {
                $new_id = insert_into('ogrenciler', [
                    'kurum_id' => $kurum_id,
                    'veli_id' => $veli_id,
                    'ad_soyad' => $ogrenci_ad,
                    'dogum_tarihi' => $dogum_tarihi !== '' ? $dogum_tarihi : null,
                    'saglik_notlari' => $saglik_notlari,
                ]);
                if (!$new_id) {
                    throw new Exception('Ogrenci kaydedilemedi.');
                }
                $ogrenci_id = (int) $new_id;
            }

            $stmt = $db->prepare("UPDATE adaylar
                SET durum = 'donustu', veli_id = :veli_id, ogrenci_id = :ogrenci_id, sube_id = :sube_id
                WHERE id = :id AND kurum_id = :kurum_id");
            $stmt->execute([
                'veli_id' => $veli_id,
                'ogrenci_id' => $ogrenci_id,
                'sube_id' => $sube_id,
                'id' => $aday_id,
                'kurum_id' => $kurum_id,
            ]);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            error_log('Aday kaydet/donustur hata: ' . $e->getMessage());
            json_yanit(false, 'Aday kaydi/donusumu yapilamadi.');
        }

        json_yanit(true, 'Aday veli/ogrenciye donusturuldu.');

    case 'aday_donustur':
        $aday_id = (int) ($_POST['aday_id'] ?? 0);
        $sube_id = (int) ($_POST['sube_id'] ?? 0);
        $ogrenci_ad = trim($_POST['ogrenci_ad'] ?? '');
        $dogum_tarihi = trim($_POST['dogum_tarihi'] ?? '');

        if ($aday_id <= 0 || $sube_id <= 0) {
            json_yanit(false, 'Dönüşüm bilgileri eksik.');
        }

        $stmt = $db->prepare("SELECT * FROM adaylar WHERE id = :id AND kurum_id = :kurum_id LIMIT 1");
        $stmt->execute(['id' => $aday_id, 'kurum_id' => $kurum_id]);
        $aday = $stmt->fetch();
        if (!$aday) {
            json_yanit(false, 'Aday bulunamadi.');
        }

        try {
            $db->beginTransaction();

            $veli_id = 0;
            if (!empty($aday['telefon']) || !empty($aday['eposta'])) {
                $stmt = $db->prepare("SELECT id FROM veliler
                    WHERE kurum_id = :kurum_id AND (telefon = :telefon OR eposta = :eposta)
                    LIMIT 1");
                $stmt->execute([
                    'kurum_id' => $kurum_id,
                    'telefon' => $aday['telefon'],
                    'eposta' => $aday['eposta'],
                ]);
                $veli_id = (int) ($stmt->fetchColumn() ?? 0);
            }

            if ($veli_id <= 0) {
                $stmt = $db->prepare("INSERT INTO veliler (kurum_id, sube_id, ad_soyad, telefon, eposta, sifre)
                    VALUES (:kurum_id, :sube_id, :ad_soyad, :telefon, :eposta, :sifre)");
                $stmt->execute([
                    'kurum_id' => $kurum_id,
                    'sube_id' => $sube_id,
                    'ad_soyad' => $aday['ad_soyad'],
                    'telefon' => $aday['telefon'],
                    'eposta' => $aday['eposta'],
                    'sifre' => null,
                ]);
                $veli_id = (int) $db->lastInsertId();
            }

            $ogrenci_ad = $ogrenci_ad !== '' ? $ogrenci_ad : $aday['ad_soyad'];
            $stmt = $db->prepare("INSERT INTO ogrenciler (kurum_id, veli_id, ad_soyad, dogum_tarihi, saglik_notlari)
                VALUES (:kurum_id, :veli_id, :ad_soyad, :dogum_tarihi, :saglik_notlari)");
            $stmt->execute([
                'kurum_id' => $kurum_id,
                'veli_id' => $veli_id,
                'ad_soyad' => $ogrenci_ad,
                'dogum_tarihi' => $dogum_tarihi !== '' ? $dogum_tarihi : null,
                'saglik_notlari' => null,
            ]);
            $ogrenci_id = (int) $db->lastInsertId();

            $stmt = $db->prepare("UPDATE adaylar
                SET durum = 'donustu', veli_id = :veli_id, ogrenci_id = :ogrenci_id, sube_id = :sube_id
                WHERE id = :id AND kurum_id = :kurum_id");
            $stmt->execute([
                'veli_id' => $veli_id,
                'ogrenci_id' => $ogrenci_id,
                'sube_id' => $sube_id,
                'id' => $aday_id,
                'kurum_id' => $kurum_id,
            ]);

            $db->commit();
        } catch (PDOException $e) {
            $db->rollBack();
            error_log('Aday donusum hata: ' . $e->getMessage());
            json_yanit(false, 'Aday donusumu yapilamadi.');
        }

        json_yanit(true, 'Aday veli/ogrenciye donusturuldu.');

    case 'rol_kaydet':
        if (empty($db_master)) {
            json_yanit(false, 'Master veritabani baglantisi bulunamadi.');
        }
        if (!merkez_admin_mi()) {
            json_yanit(false, 'Bu islem icin yetkiniz yok.');
        }
        $rol_id = (int) ($_POST['rol_id'] ?? 0);
        $rol_adi = trim($_POST['rol_adi'] ?? '');
        if ($rol_adi === '') {
            json_yanit(false, 'Rol adi zorunludur.');
        }
        if ($rol_id > 0) {
            $stmt = $db_master->prepare("UPDATE roller SET rol_adi = :rol_adi WHERE id = :id AND kurum_id = :kurum_id");
            $ok = $stmt->execute(['rol_adi' => $rol_adi, 'id' => $rol_id, 'kurum_id' => $kurum_id]);
            json_yanit((bool) $ok, $ok ? 'Rol guncellendi.' : 'Rol guncellenemedi.');
        }
        $stmt = $db_master->prepare("INSERT INTO roller (kurum_id, rol_adi) VALUES (:kurum_id, :rol_adi)");
        $ok = $stmt->execute(['kurum_id' => $kurum_id, 'rol_adi' => $rol_adi]);
        json_yanit((bool) $ok, $ok ? 'Rol kaydedildi.' : 'Rol kaydedilemedi.');

    case 'rol_sil':
        if (empty($db_master)) {
            json_yanit(false, 'Master veritabani baglantisi bulunamadi.');
        }
        if (!merkez_admin_mi()) {
            json_yanit(false, 'Bu islem icin yetkiniz yok.');
        }
        $rol_id = (int) ($_POST['rol_id'] ?? 0);
        if ($rol_id <= 0) {
            json_yanit(false, 'Rol bilgisi eksik.');
        }
        try {
            $db_master->beginTransaction();
            $db_master->prepare("DELETE FROM rol_yetkiler WHERE rol_id = :rol_id")->execute(['rol_id' => $rol_id]);
            $db_master->prepare("DELETE FROM kullanici_roller WHERE rol_id = :rol_id")->execute(['rol_id' => $rol_id]);
            $stmt = $db_master->prepare("DELETE FROM roller WHERE id = :rol_id AND kurum_id = :kurum_id");
            $stmt->execute(['rol_id' => $rol_id, 'kurum_id' => $kurum_id]);
            $db_master->commit();
        } catch (PDOException $e) {
            $db_master->rollBack();
            error_log('Rol sil hata: ' . $e->getMessage());
            json_yanit(false, 'Rol silinemedi.');
        }
        json_yanit(true, 'Rol silindi.');

    case 'rol_yetki_kaydet':
        if (empty($db_master)) {
            json_yanit(false, 'Master veritabani baglantisi bulunamadi.');
        }
        if (!merkez_admin_mi()) {
            json_yanit(false, 'Bu islem icin yetkiniz yok.');
        }
        $rol_id = (int) ($_POST['rol_id'] ?? 0);
        $yetkiler = $_POST['yetkiler'] ?? [];
        if ($rol_id <= 0) {
            json_yanit(false, 'Rol bilgisi eksik.');
        }
        if (!is_array($yetkiler)) {
            $yetkiler = [];
        }
        try {
            $db_master->beginTransaction();
            $db_master->prepare("DELETE FROM rol_yetkiler WHERE rol_id = :rol_id")->execute(['rol_id' => $rol_id]);
            if (!empty($yetkiler)) {
                $stmt = $db_master->prepare("INSERT INTO rol_yetkiler (rol_id, yetki_id) VALUES (:rol_id, :yetki_id)");
                foreach ($yetkiler as $yetki_id) {
                    $stmt->execute(['rol_id' => $rol_id, 'yetki_id' => (int) $yetki_id]);
                }
            }
            $db_master->commit();
        } catch (PDOException $e) {
            $db_master->rollBack();
            error_log('Rol yetki hata: ' . $e->getMessage());
            json_yanit(false, 'Yetkiler guncellenemedi.');
        }
        json_yanit(true, 'Yetkiler guncellendi.');

    case 'yetki_seed':
        if (empty($db_master)) {
            json_yanit(false, 'Master veritabani baglantisi bulunamadi.');
        }
        if (!merkez_admin_mi()) {
            json_yanit(false, 'Bu islem icin yetkiniz yok.');
        }
        $count = (int) $db_master->query("SELECT COUNT(*) FROM yetkiler")->fetchColumn();
        if ($count > 0) {
            json_yanit(true, 'Yetkiler zaten mevcut.');
        }
        $default_yetkiler = [
            ['dashboard_view', 'Dashboard Görüntüleme'],
            ['kurum_sube_manage', 'Kurum & Şube Yönetimi'],
            ['egitmen_manage', 'Eğitmen Yönetimi'],
            ['grup_seans_manage', 'Grup & Seans Yönetimi'],
            ['rezervasyon_manage', 'Rezervasyon Yönetimi'],
            ['hak_manage', 'Hak Yönetimi'],
            ['materyal_manage', 'Materyal Havuzu'],
            ['crm_manage', 'CRM / Ön Kayıt'],
            ['rapor_view', 'Raporlar'],
            ['rol_yetki_manage', 'Rol & Yetki Yönetimi'],
        ];
        $stmt = $db_master->prepare("INSERT INTO yetkiler (yetki_kodu, yetki_adi) VALUES (:kod, :adi)");
        foreach ($default_yetkiler as $yetki) {
            $stmt->execute(['kod' => $yetki[0], 'adi' => $yetki[1]]);
        }
        json_yanit(true, 'Varsayilan yetkiler eklendi.');

    case 'hak_kontrol':
        $veli_id = (int) ($_POST['veli_id'] ?? 0);
        if ($veli_id <= 0) {
            json_yanit(false, 'Veli bilgisi eksik.');
        }
        $bakiye = veli_bakiye_get($veli_id, $kurum_id);
        $donduruldu = veli_hak_dondurulmus_mu($veli_id, $kurum_id);
        $stmt = $db->prepare("SELECT hak_gecerlilik_bitis FROM veliler WHERE id = :id AND kurum_id = :kurum_id LIMIT 1");
        $stmt->execute(['id' => $veli_id, 'kurum_id' => $kurum_id]);
        $row = $stmt->fetch();
        json_yanit(true, 'Hak bilgisi getirildi.', [
            'bakiye_hak' => $bakiye,
            'hak_donduruldu' => $donduruldu ? 1 : 0,
            'hak_gecerlilik_bitis' => $row['hak_gecerlilik_bitis'] ?? null,
        ]);

    case 'rezervasyon_yap':
        $ogrenci_id = (int) ($_POST['ogrenci_id'] ?? 0);
        $seans_raw = $_POST['seans_id'] ?? [];
        $seans_ids = is_array($seans_raw) ? $seans_raw : [$seans_raw];
        $seans_ids = array_values(array_unique(array_filter(array_map('intval', $seans_ids))));
        if ($ogrenci_id <= 0 || empty($seans_ids)) {
            json_yanit(false, 'Rezervasyon bilgileri eksik.');
        }

        $sql = "SELECT o.id, o.dogum_tarihi, v.id AS veli_id, v.bakiye_hak, v.hak_gecerlilik_bitis
                FROM ogrenciler o
                INNER JOIN veliler v ON v.id = o.veli_id
                WHERE o.id = :ogrenci_id AND o.kurum_id = :kurum_id
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['ogrenci_id' => $ogrenci_id, 'kurum_id' => $kurum_id]);
        $ogrenci = $stmt->fetch();
        if (!$ogrenci) {
            json_yanit(false, 'Ogrenci bulunamadi.');
        }

        if (veli_hak_dondurulmus_mu($ogrenci['veli_id'], $kurum_id)) {
            json_yanit(false, 'Veli haklari dondurulmus durumda.');
        }

        if (!empty($ogrenci['hak_gecerlilik_bitis']) && strtotime($ogrenci['hak_gecerlilik_bitis']) < strtotime(date('Y-m-d'))) {
            json_yanit(false, 'Veli hak suresi dolmus.');
        }

        $yas_ay = ogrenci_yas_ay($ogrenci['dogum_tarihi']);
        $gerekli_hak = count($seans_ids);
        if ((int) $ogrenci['bakiye_hak'] < $gerekli_hak) {
            json_yanit(false, 'Yetersiz hak bakiyesi.');
        }

        $seans_detaylari = [];
        $stmt = $db->prepare("SELECT s.id, s.seans_baslangic, s.seans_bitis, s.kontenjan, g.min_ay, g.max_ay, g.grup_adi
                FROM seanslar s
                INNER JOIN oyun_gruplari g ON g.id = s.grup_id
                WHERE s.id = :seans_id AND s.kurum_id = :kurum_id
                LIMIT 1");
        $rez_stmt = $db->prepare("SELECT id FROM rezervasyonlar
            WHERE ogrenci_id = :ogrenci_id AND seans_id = :seans_id AND kurum_id = :kurum_id
            LIMIT 1");
        foreach ($seans_ids as $sid) {
            $rez_stmt->execute([
                'ogrenci_id' => $ogrenci_id,
                'seans_id' => $sid,
                'kurum_id' => $kurum_id,
            ]);
            if ($rez_stmt->fetch()) {
                json_yanit(false, 'Bu seans için zaten rezervasyonunuz var.');
            }

            $stmt->execute(['seans_id' => $sid, 'kurum_id' => $kurum_id]);
            $seans = $stmt->fetch();
            if (!$seans) {
                json_yanit(false, 'Seans bulunamadi.');
            }
            if (($seans['min_ay'] && $yas_ay < (int) $seans['min_ay']) || ($seans['max_ay'] && $yas_ay > (int) $seans['max_ay'])) {
                json_yanit(false, 'Ogrenci yas araligi uygun degil.');
            }
            $dol_stmt = $db->prepare("SELECT COUNT(*) AS toplam FROM rezervasyonlar
                WHERE seans_id = :seans_id AND kurum_id = :kurum_id AND durum = 'onayli'");
            $dol_stmt->execute(['seans_id' => $sid, 'kurum_id' => $kurum_id]);
            $doluluk = (int) ($dol_stmt->fetch()['toplam'] ?? 0);
            if ($doluluk >= (int) $seans['kontenjan']) {
                json_yanit(false, 'Kontenjan dolu.');
            }
            $seans_detaylari[] = $seans;
        }

        try {
            $db->beginTransaction();
            $stmt = $db->prepare("INSERT INTO rezervasyonlar (kurum_id, ogrenci_id, seans_id, durum)
                VALUES (:kurum_id, :ogrenci_id, :seans_id, 'onayli')");
            foreach ($seans_detaylari as $seans) {
                $stmt->execute([
                    'kurum_id' => $kurum_id,
                    'ogrenci_id' => $ogrenci_id,
                    'seans_id' => (int) $seans['id'],
                ]);
                veli_hak_hareket_ekle($ogrenci['veli_id'], 'kullanim', 1, 'Rezervasyon', $kurum_id);
            }
            $db->commit();
        } catch (PDOException $e) {
            $db->rollBack();
            error_log('Rezervasyon hata: ' . $e->getMessage());
            json_yanit(false, 'Rezervasyon kaydedilemedi.');
        }

        $kurum_bilgi = kurum_bilgi_get($kurum_id);
        $alici = $kurum_bilgi['eposta'] ?? '';
        if (!empty($alici)) {
            $stmt = $db->prepare("SELECT o.ad_soyad AS ogrenci_adi, v.ad_soyad AS veli_adi, v.telefon, v.eposta
                FROM ogrenciler o
                INNER JOIN veliler v ON v.id = o.veli_id
                WHERE o.id = :ogrenci_id AND o.kurum_id = :kurum_id
                LIMIT 1");
            $stmt->execute(['ogrenci_id' => $ogrenci_id, 'kurum_id' => $kurum_id]);
            $kisi = $stmt->fetch() ?: [];

            $seans_satirlar = [];
            foreach ($seans_detaylari as $seans) {
                $bas = date('d.m.Y H:i', strtotime($seans['seans_baslangic']));
                $bit = date('H:i', strtotime($seans['seans_bitis']));
                $grup_adi = htmlspecialchars($seans['grup_adi'] ?? 'Grup', ENT_QUOTES, 'UTF-8');
                $seans_satirlar[] = "<li>{$grup_adi} - {$bas} / {$bit}</li>";
            }

            $kurum_adi = htmlspecialchars($kurum_bilgi['kurum_adi'] ?? 'Kurum', ENT_QUOTES, 'UTF-8');
            $veli_adi = htmlspecialchars($kisi['veli_adi'] ?? '-', ENT_QUOTES, 'UTF-8');
            $ogrenci_adi = htmlspecialchars($kisi['ogrenci_adi'] ?? '-', ENT_QUOTES, 'UTF-8');
            $veli_tel = htmlspecialchars($kisi['telefon'] ?? '-', ENT_QUOTES, 'UTF-8');
            $veli_eposta = htmlspecialchars($kisi['eposta'] ?? '-', ENT_QUOTES, 'UTF-8');

            $subject = "Yeni Rezervasyon - {$kurum_adi}";
            $body = "
                <p>Merhaba {$kurum_adi} yetkilisi,</p>
                <p>Bir veli tarafından yeni rezervasyon oluşturuldu.</p>
                <p><strong>Veli:</strong> {$veli_adi} ({$veli_tel}, {$veli_eposta})</p>
                <p><strong>Öğrenci:</strong> {$ogrenci_adi}</p>
                <p><strong>Seanslar:</strong></p>
                <ul>" . implode('', $seans_satirlar) . "</ul>
            ";
            mail_gonder($alici, $subject, $body);
        }

        json_yanit(true, 'Rezervasyon basarili.');

    case 'rezervasyon_iptal':
        $rez_id = (int) ($_POST['rezervasyon_id'] ?? 0);
        $onay = (int) ($_POST['onay'] ?? 0);
        $iptal_sebebi = trim($_POST['iptal_sebebi'] ?? '');
        if ($rez_id <= 0) {
            json_yanit(false, 'Rezervasyon bilgisi eksik.');
        }

        $sql = "SELECT r.id, r.durum, r.ogrenci_id, r.iptal_sebebi, s.seans_baslangic, s.seans_bitis,
                       g.grup_adi, o.ad_soyad AS ogrenci_adi, v.id AS veli_id, v.ad_soyad AS veli_adi,
                       v.telefon, v.eposta
                FROM rezervasyonlar r
                INNER JOIN seanslar s ON s.id = r.seans_id
                INNER JOIN oyun_gruplari g ON g.id = s.grup_id
                INNER JOIN ogrenciler o ON o.id = r.ogrenci_id
                INNER JOIN veliler v ON v.id = o.veli_id
                WHERE r.id = :id AND r.kurum_id = :kurum_id
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $rez_id, 'kurum_id' => $kurum_id]);
        $rez = $stmt->fetch();
        if (!$rez) {
            json_yanit(false, 'Rezervasyon bulunamadi.');
        }
        if ($rez['durum'] !== 'onayli') {
            json_yanit(false, 'Rezervasyon iptal edilemez.');
        }

        $kural_saat = (int) sistem_ayar_get('iptal_kural_saat', $kurum_id, 48);
        $kalan_saat = seans_iptal_kalan_saat($rez['seans_baslangic']);

        if ($kalan_saat < $kural_saat && $onay !== 1) {
            json_yanit(true, 'Hakkınız yanacaktır. Devam etmek istiyor musunuz?', ['durum' => 'uyari']);
        }

        try {
            $db->beginTransaction();
            if ($kalan_saat < $kural_saat) {
                $stmt = $db->prepare("UPDATE rezervasyonlar
                    SET durum = 'hak_yandi', iptal_onay = 1, iptal_sebebi = :iptal_sebebi
                    WHERE id = :id AND kurum_id = :kurum_id");
                $stmt->execute([
                    'id' => $rez_id,
                    'kurum_id' => $kurum_id,
                    'iptal_sebebi' => $iptal_sebebi,
                ]);
            } else {
                $stmt = $db->prepare("UPDATE rezervasyonlar
                    SET durum = 'iptal', iptal_onay = 0, iptal_sebebi = :iptal_sebebi
                    WHERE id = :id AND kurum_id = :kurum_id");
                $stmt->execute([
                    'id' => $rez_id,
                    'kurum_id' => $kurum_id,
                    'iptal_sebebi' => $iptal_sebebi,
                ]);
            }
            $db->commit();
        } catch (PDOException $e) {
            $db->rollBack();
            error_log('Rezervasyon iptal hata: ' . $e->getMessage());
            json_yanit(false, 'Rezervasyon iptal edilemedi.');
        }

        $kurum_bilgi = kurum_bilgi_get($kurum_id);
        $alici = $kurum_bilgi['eposta'] ?? '';
        if (!empty($alici)) {
            $kurum_adi = htmlspecialchars($kurum_bilgi['kurum_adi'] ?? 'Kurum', ENT_QUOTES, 'UTF-8');
            $veli_adi = htmlspecialchars($rez['veli_adi'] ?? '-', ENT_QUOTES, 'UTF-8');
            $ogrenci_adi = htmlspecialchars($rez['ogrenci_adi'] ?? '-', ENT_QUOTES, 'UTF-8');
            $veli_tel = htmlspecialchars($rez['telefon'] ?? '-', ENT_QUOTES, 'UTF-8');
            $veli_eposta = htmlspecialchars($rez['eposta'] ?? '-', ENT_QUOTES, 'UTF-8');
            $grup_adi = htmlspecialchars($rez['grup_adi'] ?? 'Grup', ENT_QUOTES, 'UTF-8');
            $bas = date('d.m.Y H:i', strtotime($rez['seans_baslangic']));
            $bit = date('H:i', strtotime($rez['seans_bitis']));
            $sebep = htmlspecialchars($iptal_sebebi, ENT_QUOTES, 'UTF-8');

            $durum_text = $kalan_saat < $kural_saat
                ? 'Rezervasyon iptal edildi. 48 saat kuralı nedeniyle hak iadesi yapılmayacaktır.'
                : 'Rezervasyon iptal talebi alındı ve onay bekliyor.';

            $subject = "Rezervasyon İptal Talebi - {$kurum_adi}";
            $body = "
                <p>Merhaba {$kurum_adi} yetkilisi,</p>
                <p>{$durum_text}</p>
                <p><strong>Veli:</strong> {$veli_adi} ({$veli_tel}, {$veli_eposta})</p>
                <p><strong>Öğrenci:</strong> {$ogrenci_adi}</p>
                <p><strong>Seans:</strong> {$grup_adi} - {$bas} / {$bit}</p>
                <p><strong>İptal Sebebi:</strong> " . ($sebep !== '' ? $sebep : '-') . "</p>
            ";
            mail_gonder($alici, $subject, $body);
        }

        json_yanit(true, 'Rezervasyon iptal talebi alindi.');

    case 'iptal_onayla':
        $rez_id = (int) ($_POST['rezervasyon_id'] ?? 0);
        if ($rez_id <= 0) {
            json_yanit(false, 'Rezervasyon bilgisi eksik.');
        }
        $sql = "SELECT r.id, r.durum, r.iptal_onay, r.ogrenci_id, r.iptal_sebebi,
                       s.seans_baslangic, s.seans_bitis, g.grup_adi,
                       o.ad_soyad AS ogrenci_adi,
                       v.id AS veli_id, v.ad_soyad AS veli_adi, v.telefon, v.eposta
                FROM rezervasyonlar r
                INNER JOIN seanslar s ON s.id = r.seans_id
                INNER JOIN oyun_gruplari g ON g.id = s.grup_id
                INNER JOIN ogrenciler o ON o.id = r.ogrenci_id
                INNER JOIN veliler v ON v.id = o.veli_id
                WHERE r.id = :id AND r.kurum_id = :kurum_id
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $rez_id, 'kurum_id' => $kurum_id]);
        $rez = $stmt->fetch();
        if (!$rez || $rez['durum'] !== 'iptal' || (int) $rez['iptal_onay'] !== 0) {
            json_yanit(false, 'Onay bekleyen iptal bulunamadi.');
        }

        try {
            $db->beginTransaction();
            $stmt = $db->prepare("UPDATE rezervasyonlar
                SET iptal_onay = 1
                WHERE id = :id AND kurum_id = :kurum_id");
            $stmt->execute(['id' => $rez_id, 'kurum_id' => $kurum_id]);
            veli_hak_hareket_ekle($rez['veli_id'], 'iade', 1, 'Rezervasyon iptal onayi', $kurum_id);
            $db->commit();
        } catch (PDOException $e) {
            $db->rollBack();
            error_log('Iptal onay hata: ' . $e->getMessage());
            json_yanit(false, 'İptal onaylanamadi.');
        }

        $kurum_bilgi = kurum_bilgi_get($kurum_id);
        $alici = $kurum_bilgi['eposta'] ?? '';
        $kurum_adi = htmlspecialchars($kurum_bilgi['kurum_adi'] ?? 'Kurum', ENT_QUOTES, 'UTF-8');
        $veli_adi = htmlspecialchars($rez['veli_adi'] ?? '-', ENT_QUOTES, 'UTF-8');
        $ogrenci_adi = htmlspecialchars($rez['ogrenci_adi'] ?? '-', ENT_QUOTES, 'UTF-8');
        $veli_tel = htmlspecialchars($rez['telefon'] ?? '-', ENT_QUOTES, 'UTF-8');
        $veli_eposta = htmlspecialchars($rez['eposta'] ?? '-', ENT_QUOTES, 'UTF-8');
        $grup_adi = htmlspecialchars($rez['grup_adi'] ?? 'Grup', ENT_QUOTES, 'UTF-8');
        $bas = date('d.m.Y H:i', strtotime($rez['seans_baslangic']));
        $bit = date('H:i', strtotime($rez['seans_bitis']));
        $sebep = htmlspecialchars($rez['iptal_sebebi'] ?? '', ENT_QUOTES, 'UTF-8');

        if (!empty($alici)) {
            $subject = "İptal Onaylandı - {$kurum_adi}";
            $body = "
                <p>Merhaba {$kurum_adi} yetkilisi,</p>
                <p>Rezervasyon iptali <strong>onaylandı</strong>.</p>
                <p><strong>Veli:</strong> {$veli_adi} ({$veli_tel}, {$veli_eposta})</p>
                <p><strong>Öğrenci:</strong> {$ogrenci_adi}</p>
                <p><strong>Seans:</strong> {$grup_adi} - {$bas} / {$bit}</p>
                <p><strong>İptal Sebebi:</strong> " . ($sebep !== '' ? $sebep : '-') . "</p>
            ";
            mail_gonder($alici, $subject, $body);
        }

        if (!empty($rez['eposta'])) {
            $subject = "Rezervasyon İptal Talebiniz Onaylandı";
            $body = "
                <p>Merhaba {$veli_adi},</p>
                <p>Rezervasyon iptal talebiniz <strong>onaylandı</strong>.</p>
                <p><strong>Öğrenci:</strong> {$ogrenci_adi}</p>
                <p><strong>Seans:</strong> {$grup_adi} - {$bas} / {$bit}</p>
                <p><strong>İptal Sebebi:</strong> " . ($sebep !== '' ? $sebep : '-') . "</p>
                <p>Hak iadeniz hesabınıza yansıtılmıştır.</p>
            ";
            mail_gonder($rez['eposta'], $subject, $body);
        }

        json_yanit(true, 'İptal onaylandi ve hak iadesi yapildi.');

    case 'iptal_reddet':
        $rez_id = (int) ($_POST['rezervasyon_id'] ?? 0);
        if ($rez_id <= 0) {
            json_yanit(false, 'Rezervasyon bilgisi eksik.');
        }
        $stmt = $db->prepare("SELECT r.id, r.iptal_sebebi,
                       s.seans_baslangic, s.seans_bitis, g.grup_adi,
                       o.ad_soyad AS ogrenci_adi,
                       v.ad_soyad AS veli_adi, v.telefon, v.eposta
            FROM rezervasyonlar r
            INNER JOIN seanslar s ON s.id = r.seans_id
            INNER JOIN oyun_gruplari g ON g.id = s.grup_id
            INNER JOIN ogrenciler o ON o.id = r.ogrenci_id
            INNER JOIN veliler v ON v.id = o.veli_id
            WHERE r.id = :id AND r.kurum_id = :kurum_id
            LIMIT 1");
        $stmt->execute(['id' => $rez_id, 'kurum_id' => $kurum_id]);
        $rez = $stmt->fetch();

        $stmt = $db->prepare("UPDATE rezervasyonlar
            SET durum = 'onayli', iptal_onay = 0
            WHERE id = :id AND kurum_id = :kurum_id AND durum = 'iptal'");
        $ok = $stmt->execute(['id' => $rez_id, 'kurum_id' => $kurum_id]);

        if ($ok && $rez) {
            $kurum_bilgi = kurum_bilgi_get($kurum_id);
            $alici = $kurum_bilgi['eposta'] ?? '';
            $kurum_adi = htmlspecialchars($kurum_bilgi['kurum_adi'] ?? 'Kurum', ENT_QUOTES, 'UTF-8');
            $veli_adi = htmlspecialchars($rez['veli_adi'] ?? '-', ENT_QUOTES, 'UTF-8');
            $ogrenci_adi = htmlspecialchars($rez['ogrenci_adi'] ?? '-', ENT_QUOTES, 'UTF-8');
            $veli_tel = htmlspecialchars($rez['telefon'] ?? '-', ENT_QUOTES, 'UTF-8');
            $veli_eposta = htmlspecialchars($rez['eposta'] ?? '-', ENT_QUOTES, 'UTF-8');
            $grup_adi = htmlspecialchars($rez['grup_adi'] ?? 'Grup', ENT_QUOTES, 'UTF-8');
            $bas = date('d.m.Y H:i', strtotime($rez['seans_baslangic']));
            $bit = date('H:i', strtotime($rez['seans_bitis']));
            $sebep = htmlspecialchars($rez['iptal_sebebi'] ?? '', ENT_QUOTES, 'UTF-8');

            if (!empty($alici)) {
                $subject = "İptal Reddedildi - {$kurum_adi}";
                $body = "
                    <p>Merhaba {$kurum_adi} yetkilisi,</p>
                    <p>Rezervasyon iptal talebi <strong>reddedildi</strong>.</p>
                    <p><strong>Veli:</strong> {$veli_adi} ({$veli_tel}, {$veli_eposta})</p>
                    <p><strong>Öğrenci:</strong> {$ogrenci_adi}</p>
                    <p><strong>Seans:</strong> {$grup_adi} - {$bas} / {$bit}</p>
                    <p><strong>İptal Sebebi:</strong> " . ($sebep !== '' ? $sebep : '-') . "</p>
                ";
                mail_gonder($alici, $subject, $body);
            }

            if (!empty($rez['eposta'])) {
                $subject = "Rezervasyon İptal Talebiniz Reddedildi";
                $body = "
                    <p>Merhaba {$veli_adi},</p>
                    <p>Rezervasyon iptal talebiniz <strong>reddedildi</strong>.</p>
                    <p><strong>Öğrenci:</strong> {$ogrenci_adi}</p>
                    <p><strong>Seans:</strong> {$grup_adi} - {$bas} / {$bit}</p>
                    <p><strong>İptal Sebebi:</strong> " . ($sebep !== '' ? $sebep : '-') . "</p>
                    <p>Rezervasyonunuz geçerliliğini korumaktadır.</p>
                ";
                mail_gonder($rez['eposta'], $subject, $body);
            }
        }

        json_yanit((bool) $ok, $ok ? 'İptal reddedildi.' : 'İptal reddedilemedi.');

    case 'hak_dondur':
        $veli_id = (int) ($_POST['veli_id'] ?? 0);
        $baslangic = trim($_POST['baslangic_tarihi'] ?? '');
        $bitis = trim($_POST['bitis_tarihi'] ?? '');
        $aciklama = trim($_POST['aciklama'] ?? '');
        if ($veli_id <= 0 || $baslangic === '') {
            json_yanit(false, 'Hak dondurma bilgileri eksik.');
        }

        $stmt = $db->prepare("INSERT INTO veli_hak_dondurma (kurum_id, veli_id, baslangic_tarihi, bitis_tarihi, durum, aciklama, islem_yapan_id)
            VALUES (:kurum_id, :veli_id, :baslangic, :bitis, 'aktif', :aciklama, :islem_yapan_id)");
        $stmt->execute([
            'kurum_id' => $kurum_id,
            'veli_id' => $veli_id,
            'baslangic' => $baslangic,
            'bitis' => $bitis !== '' ? $bitis : null,
            'aciklama' => $aciklama,
            'islem_yapan_id' => aktif_kullanici_id(),
        ]);
        $db->prepare("UPDATE veliler SET hak_donduruldu = 1 WHERE id = :id AND kurum_id = :kurum_id")
            ->execute(['id' => $veli_id, 'kurum_id' => $kurum_id]);
        json_yanit(true, 'Hak dondurma islemi yapildi.');

    case 'hak_dondurma_kaldir':
        $veli_id = (int) ($_POST['veli_id'] ?? 0);
        if ($veli_id <= 0) {
            json_yanit(false, 'Veli bilgisi eksik.');
        }
        $db->prepare("UPDATE veli_hak_dondurma SET durum = 'pasif'
            WHERE veli_id = :veli_id AND kurum_id = :kurum_id AND durum = 'aktif'")
            ->execute(['veli_id' => $veli_id, 'kurum_id' => $kurum_id]);
        $db->prepare("UPDATE veliler SET hak_donduruldu = 0 WHERE id = :id AND kurum_id = :kurum_id")
            ->execute(['id' => $veli_id, 'kurum_id' => $kurum_id]);
        json_yanit(true, 'Hak dondurma kaldirildi.');

    case 'hak_sure_uzat':
        $veli_id = (int) ($_POST['veli_id'] ?? 0);
        $yeni_tarih = trim($_POST['yeni_tarih'] ?? '');
        if ($veli_id <= 0 || $yeni_tarih === '') {
            json_yanit(false, 'Hak sure uzatma bilgileri eksik.');
        }
        $ok = $db->prepare("UPDATE veliler SET hak_gecerlilik_bitis = :tarih WHERE id = :id AND kurum_id = :kurum_id")
            ->execute(['tarih' => $yeni_tarih, 'id' => $veli_id, 'kurum_id' => $kurum_id]);
        json_yanit((bool) $ok, $ok ? 'Hak suresi uzatildi.' : 'Hak suresi uzatilamadi.');

    case 'hak_ekle':
        $veli_id = (int) ($_POST['veli_id'] ?? 0);
        $miktar = (int) ($_POST['miktar'] ?? 0);
        $aciklama = trim($_POST['aciklama'] ?? '');
        $gecerlilik_bitis = trim($_POST['gecerlilik_bitis'] ?? '');
        $ucret_raw = trim($_POST['ucret'] ?? '0');
        $ucret_normal = $ucret_raw;
        if (strpos($ucret_normal, ',') !== false) {
            $ucret_normal = str_replace('.', '', $ucret_normal);
            $ucret_normal = str_replace(',', '.', $ucret_normal);
        }
        $ucret_normal = preg_replace('/[^0-9\\.]/', '', $ucret_normal);
        $ucret = (float) $ucret_normal;
        $son_odeme_tarihi = trim($_POST['son_odeme_tarihi'] ?? '');
        if ($veli_id <= 0 || $miktar <= 0) {
            json_yanit(false, 'Hak ekleme bilgileri eksik.');
        }
        if ($ucret > 0 && $son_odeme_tarihi === '') {
            json_yanit(false, 'Son ödeme tarihi giriniz.');
        }
        try {
            $db->beginTransaction();
            $hareket_id = veli_hak_hareket_ekle($veli_id, 'ekleme', $miktar, $aciklama, $kurum_id);
            if (!$hareket_id) {
                throw new Exception('Hak eklenemedi.');
            }
            if ($gecerlilik_bitis !== '') {
                $db->prepare("UPDATE veliler SET hak_gecerlilik_bitis = :tarih WHERE id = :id AND kurum_id = :kurum_id")
                    ->execute(['tarih' => $gecerlilik_bitis, 'id' => $veli_id, 'kurum_id' => $kurum_id]);
            }
            if ($ucret > 0) {
                $stmt = $db->prepare("INSERT INTO veli_borclar (kurum_id, veli_id, hak_hareket_id, hak_miktar, tutar, son_odeme_tarihi, durum, aciklama)
                    VALUES (:kurum_id, :veli_id, :hak_hareket_id, :hak_miktar, :tutar, :son_odeme, 'beklemede', :aciklama)");
                $stmt->execute([
                    'kurum_id' => $kurum_id,
                    'veli_id' => $veli_id,
                    'hak_hareket_id' => $hareket_id,
                    'hak_miktar' => $miktar,
                    'tutar' => $ucret,
                    'son_odeme' => $son_odeme_tarihi,
                    'aciklama' => $aciklama,
                ]);
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            error_log('Hak ekle hata: ' . $e->getMessage());
            json_yanit(false, 'Hak eklenemedi.');
        }
        json_yanit(true, 'Hak eklendi.');

    case 'tahsilat_ekle':
        $borc_id = (int) ($_POST['borc_id'] ?? 0);
        $odeme_yontemi = trim($_POST['odeme_yontemi'] ?? 'nakit');
        $aciklama = trim($_POST['aciklama'] ?? '');
        if ($borc_id <= 0) {
            json_yanit(false, 'Tahsilat bilgileri eksik.');
        }
        $stmt = $db->prepare("SELECT * FROM veli_borclar WHERE id = :id AND kurum_id = :kurum_id LIMIT 1");
        $stmt->execute(['id' => $borc_id, 'kurum_id' => $kurum_id]);
        $borc = $stmt->fetch();
        if (!$borc || ($borc['durum'] ?? '') !== 'beklemede') {
            json_yanit(false, 'Bekleyen borç bulunamadı.');
        }

        try {
            $db->beginTransaction();
            $tutar = (float) ($borc['tutar'] ?? 0);
            $veli_id = (int) ($borc['veli_id'] ?? 0);
            $db->prepare("UPDATE veli_borclar
                SET durum = 'odendi', odeme_tarihi = NOW(), odeme_yontemi = :odeme_yontemi, tahsil_tutar = :tutar
                WHERE id = :id AND kurum_id = :kurum_id")
                ->execute([
                    'odeme_yontemi' => $odeme_yontemi,
                    'tutar' => $tutar,
                    'id' => $borc_id,
                    'kurum_id' => $kurum_id,
                ]);
            $kategori_var = kasa_kategori_var_mi();
            if ($kategori_var) {
                $stmt = $db->prepare("INSERT INTO kasa_hareketleri (kurum_id, sube_id, veli_id, islem_tipi, kategori, odeme_yontemi, tutar, aciklama)
                    VALUES (:kurum_id, :sube_id, :veli_id, 'gelir', :kategori, :odeme_yontemi, :tutar, :aciklama)");
            } else {
                $stmt = $db->prepare("INSERT INTO kasa_hareketleri (kurum_id, sube_id, veli_id, islem_tipi, odeme_yontemi, tutar, aciklama)
                    VALUES (:kurum_id, :sube_id, :veli_id, 'gelir', :odeme_yontemi, :tutar, :aciklama)");
            }
            $aciklama_final = $aciklama !== '' ? $aciklama : 'Hak tahsilatı';
            if (!$kategori_var) {
                $aciklama_final = 'Kategori: Tahsilat - ' . $aciklama_final;
            }
            $params = [
                'kurum_id' => $kurum_id,
                'sube_id' => aktif_sube_id(),
                'veli_id' => $veli_id > 0 ? $veli_id : null,
                'odeme_yontemi' => $odeme_yontemi,
                'tutar' => $tutar,
                'aciklama' => $aciklama_final,
            ];
            if ($kategori_var) {
                $params['kategori'] = 'Tahsilat';
            }
            $stmt->execute($params);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            error_log('Tahsilat hata: ' . $e->getMessage());
            json_yanit(false, 'Tahsilat kaydedilemedi.');
        }
        json_yanit(true, 'Tahsilat kaydedildi.');

    case 'hak_geri_al':
        $hareket_id = (int) ($_POST['hareket_id'] ?? 0);
        if ($hareket_id <= 0) {
            json_yanit(false, 'Hak hareketi bulunamadı.');
        }
        $stmt = $db->prepare("SELECT h.id, h.veli_id, h.miktar, h.islem_tipi
            FROM veli_hak_hareketleri h
            WHERE h.id = :id AND h.kurum_id = :kurum_id
            LIMIT 1");
        $stmt->execute(['id' => $hareket_id, 'kurum_id' => $kurum_id]);
        $hareket = $stmt->fetch();
        if (!$hareket || $hareket['islem_tipi'] !== 'ekleme') {
            json_yanit(false, 'Geri alınacak hak bulunamadı.');
        }

        $stmt = $db->prepare("SELECT id, durum FROM veli_borclar
            WHERE hak_hareket_id = :hareket_id AND kurum_id = :kurum_id
            LIMIT 1");
        $stmt->execute(['hareket_id' => $hareket_id, 'kurum_id' => $kurum_id]);
        $borc = $stmt->fetch();
        if ($borc && ($borc['durum'] ?? '') === 'odendi') {
            json_yanit(false, 'Bu hak için ödeme alınmış. Geri alınamaz.');
        }

        $veli_id = (int) ($hareket['veli_id'] ?? 0);
        $miktar = (int) ($hareket['miktar'] ?? 0);
        $stmt = $db->prepare("SELECT bakiye_hak FROM veliler WHERE id = :id AND kurum_id = :kurum_id LIMIT 1");
        $stmt->execute(['id' => $veli_id, 'kurum_id' => $kurum_id]);
        $bakiye = (int) ($stmt->fetchColumn() ?? 0);
        if ($bakiye < $miktar) {
            json_yanit(false, 'Bakiye kullanılmış görünüyor. Geri alma yapılamadı.');
        }

        try {
            $db->beginTransaction();
            if ($borc && ($borc['durum'] ?? '') === 'beklemede') {
                $db->prepare("DELETE FROM veli_borclar WHERE id = :id AND kurum_id = :kurum_id")
                    ->execute(['id' => (int) $borc['id'], 'kurum_id' => $kurum_id]);
            }
            $ok = veli_hak_hareket_ekle($veli_id, 'kullanim', $miktar, 'Hak geri alım', $kurum_id);
            if (!$ok) {
                throw new Exception('Hak geri alım kaydedilemedi.');
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            error_log('Hak geri al hata: ' . $e->getMessage());
            json_yanit(false, 'Hak geri alınamadı.');
        }
        json_yanit(true, 'Hak geri alındı.');

    case 'iptal_kural_kaydet':
        $saat = (int) ($_POST['iptal_kural_saat'] ?? 0);
        if ($saat <= 0) {
            json_yanit(false, 'Iptal kural saati gecersiz.');
        }
        $ok = sistem_ayar_set('iptal_kural_saat', (string) $saat, $kurum_id);
        json_yanit((bool) $ok, $ok ? 'Iptal kural saati guncellendi.' : 'Iptal kural saati guncellenemedi.');

    case 'gelir_kaydet':
    case 'gider_kaydet':
        $sube_id = (int) ($_POST['sube_id'] ?? aktif_sube_id());
        $veli_id = (int) ($_POST['veli_id'] ?? 0);
        $kategori = trim($_POST['kategori'] ?? '');
        $odeme_yontemi = trim($_POST['odeme_yontemi'] ?? 'nakit');
        $tutar = (float) ($_POST['tutar'] ?? 0);
        $aciklama = trim($_POST['aciklama'] ?? '');

        if ($sube_id <= 0 || $tutar <= 0) {
            json_yanit(false, 'Muhasebe bilgileri eksik.');
        }

        $islem_tipi = ($islem === 'gelir_kaydet') ? 'gelir' : 'gider';
        if ($islem_tipi === 'gider' && $kategori === '') {
            json_yanit(false, 'Gider kategorisi seçiniz.');
        }
        $kategori_var = kasa_kategori_var_mi();
        if ($kategori_var) {
            $stmt = $db->prepare("INSERT INTO kasa_hareketleri (kurum_id, sube_id, veli_id, islem_tipi, kategori, odeme_yontemi, tutar, aciklama)
                VALUES (:kurum_id, :sube_id, :veli_id, :islem_tipi, :kategori, :odeme_yontemi, :tutar, :aciklama)");
        } else {
            $stmt = $db->prepare("INSERT INTO kasa_hareketleri (kurum_id, sube_id, veli_id, islem_tipi, odeme_yontemi, tutar, aciklama)
                VALUES (:kurum_id, :sube_id, :veli_id, :islem_tipi, :odeme_yontemi, :tutar, :aciklama)");
        }
        $aciklama_final = $aciklama;
        if (!$kategori_var && $kategori !== '') {
            $aciklama_final = 'Kategori: ' . $kategori . ($aciklama_final !== '' ? ' - ' . $aciklama_final : '');
        }
        $params = [
            'kurum_id' => $kurum_id,
            'sube_id' => $sube_id,
            'veli_id' => $veli_id > 0 ? $veli_id : null,
            'islem_tipi' => $islem_tipi,
            'odeme_yontemi' => $odeme_yontemi,
            'tutar' => $tutar,
            'aciklama' => $aciklama_final,
        ];
        if ($kategori_var) {
            $params['kategori'] = $kategori !== '' ? $kategori : null;
        }
        $ok = $stmt->execute($params);
        json_yanit((bool) $ok, $ok ? 'Islem kaydedildi.' : 'Islem kaydedilemedi.');

    case 'materyal_yukle':
        if (!materyal_yukleme_yetkili_mi()) {
            json_yanit(false, 'Bu islem icin yetkiniz yok.');
        }

        $materyal_adi = trim($_POST['materyal_adi'] ?? '');
        $kazanimlar = trim($_POST['kazanimlar'] ?? '');
        if ($materyal_adi === '' || empty($_FILES['dosya'])) {
            json_yanit(false, 'Materyal bilgileri eksik.');
        }

        $dosya = $_FILES['dosya'];
        if ($dosya['error'] !== UPLOAD_ERR_OK) {
            json_yanit(false, 'Dosya yuklenemedi.');
        }

        $ext = strtolower(pathinfo($dosya['name'], PATHINFO_EXTENSION));
        $izinli = ['pdf', 'jpg', 'jpeg', 'png'];
        if (!in_array($ext, $izinli, true)) {
            json_yanit(false, 'Dosya tipi desteklenmiyor.');
        }

        $upload_dir = __DIR__ . '/uploads/materyaller/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $safe_name = dosya_adi_temizle(pathinfo($dosya['name'], PATHINFO_FILENAME));
        $hedef = $upload_dir . time() . '_' . $safe_name . '.' . $ext;
        if (!move_uploaded_file($dosya['tmp_name'], $hedef)) {
            json_yanit(false, 'Dosya kaydedilemedi.');
        }

        $rel_path = 'uploads/materyaller/' . basename($hedef);
        $stmt = $db->prepare("INSERT INTO materyal_havuzu (kurum_id, materyal_adi, kazanimlar, materyal_dosya, yukleyen_kullanici_id)
            VALUES (:kurum_id, :materyal_adi, :kazanimlar, :materyal_dosya, :yukleyen)");
        $ok = $stmt->execute([
            'kurum_id' => $kurum_id,
            'materyal_adi' => $materyal_adi,
            'kazanimlar' => $kazanimlar,
            'materyal_dosya' => $rel_path,
            'yukleyen' => aktif_kullanici_id(),
        ]);
        json_yanit((bool) $ok, $ok ? 'Materyal yuklendi.' : 'Materyal kaydedilemedi.');

    case 'dashboard_ozet':
        $filter = $_POST['filter'] ?? 'today';
        $filter = in_array($filter, ['today', 'week'], true) ? $filter : 'today';
        $start = new DateTime('today');
        $end = new DateTime('today 23:59:59');
        if ($filter === 'week') {
            $start = new DateTime('monday this week');
            $end = new DateTime('sunday this week 23:59:59');
        }
        $start_str = $start->format('Y-m-d H:i:s');
        $end_str = $end->format('Y-m-d H:i:s');

        $sube_id = (int) ($_SESSION['sube_id'] ?? 0);
        $sube_filter = $sube_id > 0 ? " AND g.sube_id = :sube_id" : "";
        $params = [
            'kurum_id' => $kurum_id,
            'start' => $start_str,
            'end' => $end_str,
        ];
        if ($sube_id > 0) {
            $params['sube_id'] = $sube_id;
        }

        $kontenjan_toplam = 0;
        $rezervasyon_sayisi = 0;
        $gunluk_cocuk = 0;
        $bekleyen_iptal = 0;
        $hak_yandi = 0;
        $aylik_ciro = 0;

        try {
            $sql = "SELECT COALESCE(SUM(s.kontenjan), 0) AS toplam
                    FROM seanslar s
                    INNER JOIN oyun_gruplari g ON g.id = s.grup_id
                    WHERE s.kurum_id = :kurum_id
                      AND s.seans_baslangic BETWEEN :start AND :end" . $sube_filter;
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $kontenjan_toplam = (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Dashboard kontenjan hata: ' . $e->getMessage());
        }

        try {
            $sql = "SELECT COUNT(*) AS toplam
                    FROM rezervasyonlar r
                    INNER JOIN seanslar s ON s.id = r.seans_id
                    INNER JOIN oyun_gruplari g ON g.id = s.grup_id
                    WHERE r.kurum_id = :kurum_id
                      AND r.durum = 'onayli'
                      AND s.seans_baslangic BETWEEN :start AND :end" . $sube_filter;
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rezervasyon_sayisi = (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Dashboard rezervasyon hata: ' . $e->getMessage());
        }

        try {
            $sql = "SELECT COUNT(DISTINCT r.ogrenci_id) AS toplam
                    FROM rezervasyonlar r
                    INNER JOIN seanslar s ON s.id = r.seans_id
                    INNER JOIN oyun_gruplari g ON g.id = s.grup_id
                    WHERE r.kurum_id = :kurum_id
                      AND r.durum = 'onayli'
                      AND s.seans_baslangic BETWEEN :start AND :end" . $sube_filter;
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $gunluk_cocuk = (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Dashboard cocuk sayisi hata: ' . $e->getMessage());
        }

        try {
            $sql = "SELECT COUNT(*) AS toplam
                    FROM rezervasyonlar r
                    INNER JOIN seanslar s ON s.id = r.seans_id
                    INNER JOIN oyun_gruplari g ON g.id = s.grup_id
                    WHERE r.kurum_id = :kurum_id
                      AND r.durum = 'iptal'
                      AND r.iptal_onay = 0
                      AND r.islem_tarihi BETWEEN :start AND :end" . $sube_filter;
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $bekleyen_iptal = (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Dashboard iptal hata: ' . $e->getMessage());
        }

        try {
            $sql = "SELECT COUNT(*) AS toplam
                    FROM rezervasyonlar r
                    INNER JOIN seanslar s ON s.id = r.seans_id
                    INNER JOIN oyun_gruplari g ON g.id = s.grup_id
                    WHERE r.kurum_id = :kurum_id
                      AND r.durum = 'hak_yandi'
                      AND r.islem_tarihi BETWEEN :start AND :end" . $sube_filter;
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $hak_yandi = (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Dashboard hak yandi hata: ' . $e->getMessage());
        }

        try {
            $ay_bas = date('Y-m-01 00:00:00');
            $ay_bit = date('Y-m-t 23:59:59');
            $sql = "SELECT COALESCE(SUM(tutar), 0) AS toplam
                    FROM kasa_hareketleri
                    WHERE kurum_id = :kurum_id
                      AND islem_tipi = 'gelir'
                      AND tarih BETWEEN :ay_bas AND :ay_bit";
            if ($sube_id > 0) {
                $sql .= " AND sube_id = :sube_id";
            }
            $stmt = $db->prepare($sql);
            $params_ciro = [
                'kurum_id' => $kurum_id,
                'ay_bas' => $ay_bas,
                'ay_bit' => $ay_bit,
            ];
            if ($sube_id > 0) {
                $params_ciro['sube_id'] = $sube_id;
            }
            $stmt->execute($params_ciro);
            $aylik_ciro = (float) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Dashboard ciro hata: ' . $e->getMessage());
        }

        $doluluk_oran = $kontenjan_toplam > 0 ? round(($rezervasyon_sayisi / $kontenjan_toplam) * 100, 1) : 0;
        $filter_label = $filter === 'week' ? 'Bu Hafta' : 'Bugün';

        json_yanit(true, 'ok', [
            'filter_label' => $filter_label,
            'gunluk_cocuk' => number_format($gunluk_cocuk, 0, ",", "."),
            'doluluk_oran' => $doluluk_oran,
            'aylik_ciro' => number_format($aylik_ciro, 2, ",", ".") . ' TL',
            'bekleyen_iptal' => number_format($bekleyen_iptal, 0, ",", "."),
            'hak_yandi' => number_format($hak_yandi, 0, ",", "."),
        ]);

    default:
        json_yanit(false, 'Gecersiz islem.');
}
