<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Veritabani;
use App\Models\Ayar;
use App\Models\SmsKaydi;
use App\Models\Randevu;
use PDO;
use Throwable;

final class SmsServisi
{
    private array $config;
    private NetgsmServisi $netgsm;
    private PDO $db;

    public function __construct(?array $config = null, ?NetgsmServisi $netgsm = null)
    {
        $this->config = $config ?? require BASE_PATH . '/config/sms.php';
        $this->config = $this->ayarlarIleBirlesikConfig($this->config);
        $this->netgsm = $netgsm ?? new NetgsmServisi($this->config);
        $this->db = Veritabani::baglan();
    }

    public function hatirlatmaAyarlari(): array
    {
        return [
            'appointment_reminder_enabled' => (bool) ($this->config['appointment_reminder_enabled'] ?? true),
            'appointment_reminder_days_before' => (int) ($this->config['appointment_reminder_days_before'] ?? 1),
            'appointment_reminder_time' => (string) ($this->config['appointment_reminder_time'] ?? '14:00'),
            'birthday_message_enabled' => (bool) ($this->config['birthday_message_enabled'] ?? true),
            'birthday_message_time' => (string) ($this->config['birthday_message_time'] ?? '09:00'),
        ];
    }

    public function config(): array
    {
        return $this->config;
    }

    public function telefonNormalize(string $telefon): ?string
    {
        $rakam = preg_replace('/\D+/', '', $telefon) ?? '';
        if (str_starts_with($rakam, '0090')) {
            $rakam = substr($rakam, 4);
        }
        if (str_starts_with($rakam, '90')) {
            $rakam = substr($rakam, 2);
        }
        if (str_starts_with($rakam, '0')) {
            $rakam = substr($rakam, 1);
        }
        if (!preg_match('/^5\d{9}$/', $rakam)) {
            return null;
        }
        return $rakam;
    }

    public function parcaSayisi(string $mesaj): int
    {
        $uzunluk = mb_strlen($mesaj);
        if ($uzunluk <= 155) {
            return 1;
        }
        return (int) ceil($uzunluk / 149);
    }

    public function sablonHazirla(string $anahtar, array $degiskenler): string
    {
        $sablon = SmsKaydi::sablonBul($anahtar);
        if (!$sablon || (int) $sablon['aktif'] !== 1) {
            throw new \RuntimeException('SMS sablonu aktif degil veya bulunamadi: ' . $anahtar);
        }
        $mesaj = (string) $sablon['mesaj'];
        if ($anahtar === 'manuel_sms' && isset($degiskenler['mesaj'])) {
            $mesaj = (string) $degiskenler['mesaj'];
        }
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $mesaj, $matches);
        foreach ($matches[1] ?? [] as $anahtarDegisken) {
            if (!array_key_exists($anahtarDegisken, $degiskenler)) {
                throw new \RuntimeException('SMS sablon degiskeni eksik: {' . $anahtarDegisken . '}');
            }
            $mesaj = str_replace('{' . $anahtarDegisken . '}', (string) $degiskenler[$anahtarDegisken], $mesaj);
        }
        return trim($mesaj);
    }

    public function kuyrugaEkle(array $alicilar, string $mesaj, array $meta = []): array
    {
        $ids = [];
        $hatalar = [];
        $eklenenTelefonlar = [];
        $zorunluAlici = $this->zorunluTestAlicisi();
        foreach ($alicilar as $alici) {
            $orijinal = (string) ($alici['telefon'] ?? '');
            $telefon = $zorunluAlici ?: $this->telefonNormalize($orijinal);
            $mukerrerTelefon = $telefon ?: preg_replace('/\D+/', '', $orijinal);
            $mukerrer = $meta['mukerrer_anahtari'] ?? null;
            if ($mukerrer) {
                $mukerrer .= ':' . $mukerrerTelefon;
            }
            if (!$telefon) {
                $ids[] = SmsKaydi::olustur($this->smsVerisi($alici, $orijinal, '000000000000', $mesaj, $meta, 'basarisiz', 'Gecersiz telefon numarasi.', $mukerrer));
                $hatalar[] = $orijinal . ' gecersiz telefon.';
                continue;
            }
            $tekilAnahtar = $zorunluAlici
                ? $telefon . ':' . (preg_replace('/\D+/', '', $orijinal) ?: 'manuel') . ':' . md5($mesaj)
                : $telefon;
            if (isset($eklenenTelefonlar[$tekilAnahtar])) {
                continue;
            }
            $eklenenTelefonlar[$tekilAnahtar] = true;
            $ids[] = SmsKaydi::olustur($this->smsVerisi($alici, $orijinal, $telefon, $mesaj, $meta, 'bekliyor', null, $mukerrer));
        }
        return ['ids' => $ids, 'hatalar' => $hatalar, 'adet' => count($ids)];
    }

    private function zorunluTestAlicisi(): ?string
    {
        $telefon = trim((string) ($this->config['force_to'] ?? ''));
        return $telefon !== '' ? $this->telefonNormalize($telefon) : null;
    }

    public function manuelTekli(string $telefon, string $mesaj, int $kullaniciId = 0): array
    {
        return $this->kuyrugaEkle([['telefon' => $telefon]], $mesaj, [
            'sablon_anahtari' => 'manuel_sms',
            'olay_tipi' => 'manuel_sms',
            'olusturan_kullanici_id' => $kullaniciId ?: null,
        ]);
    }

    public function manuelOgrenci(int $ogrenciId, int $veliId, string $telefon, string $mesaj, string $sablonAnahtari, int $kullaniciId = 0): array
    {
        return $this->kuyrugaEkle([[
            'telefon' => $telefon,
            'ogrenci_id' => $ogrenciId,
            'veli_id' => $veliId ?: null,
            'alici_tipi' => 'veli',
        ]], $mesaj, [
            'sablon_anahtari' => $sablonAnahtari ?: 'manuel_sms',
            'olay_tipi' => 'manuel_sms',
            'ogrenci_id' => $ogrenciId,
            'veli_id' => $veliId ?: null,
            'olusturan_kullanici_id' => $kullaniciId ?: null,
        ]);
    }

    public function manuelToplu(array $telefonlar, string $mesaj, int $kullaniciId = 0): array
    {
        $alicilar = array_map(static fn(string $telefon): array => ['telefon' => $telefon], $telefonlar);
        return $this->kuyrugaEkle($alicilar, $mesaj, [
            'sablon_anahtari' => 'manuel_sms',
            'olay_tipi' => 'manuel_sms',
            'olusturan_kullanici_id' => $kullaniciId ?: null,
        ]);
    }

    public function randevuOlusturuldu(int $randevuId): void
    {
        $this->randevularOlusturuldu([$randevuId]);
    }

    public function randevularOlusturuldu(array $randevuIdleri): int
    {
        $randevuIdleri = array_values(array_unique(array_filter(array_map('intval', $randevuIdleri))));
        if ($randevuIdleri === []) {
            return 0;
        }

        $randevular = $this->randevuDetaylari($randevuIdleri);
        if ($randevular === []) {
            return 0;
        }

        $ogrenciRandevulari = [];
        foreach ($randevular as $randevu) {
            $ogrenciRandevulari[(int) $randevu['ogrenci_id']][] = $randevu;
        }

        $adet = 0;
        foreach ($ogrenciRandevulari as $ogrenciId => $ogrenciyeAitRandevular) {
            $ilkRandevu = $ogrenciyeAitRandevular[0];
            $alicilar = $this->ogrenciVelileri($ogrenciId);
            if ($alicilar === []) {
                continue;
            }

            $ilkRandevuId = (int) $ilkRandevu['id'];
            $degiskenler = array_replace($this->varsayilanDegiskenler($ilkRandevu), [
                'tarih' => $this->tarihGunTr((string) $ilkRandevu['tarih']),
                'saat' => substr((string) $ilkRandevu['baslangic_saati'], 0, 5),
                'bitis_saati' => substr((string) $ilkRandevu['bitis_saati'], 0, 5),
                'paket_adi' => (string) ($ilkRandevu['paket_adi'] ?? $ilkRandevu['tur']),
                'ogretmen_adi' => (string) ($ilkRandevu['uzman'] ?? ''),
                'katilim_linki' => $this->katilimLinki($ilkRandevuId),
                'randevu_listesi' => $this->randevuListesiMetni($ogrenciyeAitRandevular),
            ]);

            foreach ($alicilar as $alici) {
                $mesaj = $this->randevuOlusturmaMesaji($degiskenler + ['veli_adi' => (string) ($alici['veli_adi'] ?? 'Velimiz')]);
                $sonuc = $this->kuyrugaEkle([$alici], $mesaj, [
                    'sablon_anahtari' => 'randevu_olusturuldu',
                    'olay_tipi' => 'randevu_olusturuldu',
                    'ogrenci_id' => $ogrenciId,
                    'grup_id' => $ilkRandevu['grup_id'] ?? null,
                    'randevu_id' => $ilkRandevuId,
                    'mukerrer_anahtari' => 'randevular_olusturuldu:' . implode('-', array_column($ogrenciyeAitRandevular, 'id')),
                    'olusturan_kullanici_id' => $ilkRandevu['olusturan_kullanici_id'] ?? null,
                ]);
                $adet += (int) $sonuc['adet'];
            }
        }

        return $adet;
    }

    public function paketRandevulariOlusturuldu(int $paketId): int
    {
        $stmt = $this->db->prepare('SELECT id FROM randevular WHERE paket_id = :paket_id ORDER BY tarih ASC, baslangic_saati ASC');
        $stmt->execute(['paket_id' => $paketId]);

        return $this->randevularOlusturuldu(array_column($stmt->fetchAll(), 'id'));
    }

    public function telafiDersiOlusturuldu(int $randevuId, int $kullaniciId = 0): int
    {
        $randevu = $this->randevuDetay($randevuId);
        if (!$randevu) {
            return 0;
        }

        $ogrenciId = (int) $randevu['ogrenci_id'];
        $alicilar = $this->ogrenciVelileri($ogrenciId);
        if ($alicilar === []) {
            return 0;
        }

        $kaynakTarih = (string) ($randevu['telafi_kaynak_tarih'] ?? '');
        $kaynakSaat = (string) ($randevu['telafi_kaynak_saat'] ?? '');
        $degiskenler = array_replace($this->varsayilanDegiskenler($randevu), [
            'tarih' => $this->tarihGunTr((string) $randevu['tarih']),
            'saat' => substr((string) $randevu['baslangic_saati'], 0, 5),
            'bitis_saati' => substr((string) $randevu['bitis_saati'], 0, 5),
            'paket_adi' => (string) ($randevu['paket_adi'] ?? $randevu['tur']),
            'ogretmen_adi' => (string) ($randevu['uzman'] ?? ''),
            'kaynak_tarih' => $kaynakTarih !== '' ? $this->tarihGunTr($kaynakTarih) : '-',
            'kaynak_saat' => $kaynakSaat !== '' ? substr($kaynakSaat, 0, 5) : '-',
            'katilim_linki' => $this->katilimLinki($randevuId),
        ]);

        $adet = 0;
        foreach ($alicilar as $alici) {
            $mesaj = $this->telafiDersiOlusturmaMesaji($degiskenler + [
                'veli_adi' => (string) ($alici['veli_adi'] ?? 'Velimiz'),
            ]);
            $sonuc = $this->kuyrugaEkle([$alici], $mesaj, [
                'sablon_anahtari' => 'telafi_dersi_olusturuldu',
                'olay_tipi' => 'telafi_dersi_olusturuldu',
                'ogrenci_id' => $ogrenciId,
                'grup_id' => $randevu['grup_id'] ?? null,
                'randevu_id' => $randevuId,
                'mukerrer_anahtari' => 'telafi_dersi_olusturuldu:' . $randevuId,
                'olusturan_kullanici_id' => $kullaniciId ?: ($randevu['olusturan_kullanici_id'] ?? null),
            ]);
            $adet += (int) $sonuc['adet'];
        }

        return $adet;
    }

    public function randevuGuncellendi(int $randevuId, array $eskiRandevu = []): int
    {
        $randevu = $this->randevuDetay($randevuId);
        if (!$randevu) {
            return 0;
        }

        $ogrenciId = (int) $randevu['ogrenci_id'];
        $alicilar = $this->ogrenciVelileri($ogrenciId);
        if ($alicilar === []) {
            return 0;
        }

        $eskiTarih = (string) ($eskiRandevu['tarih'] ?? '');
        $eskiSaat = (string) ($eskiRandevu['baslangic_saati'] ?? '');
        $degiskenler = array_replace($this->varsayilanDegiskenler($randevu), [
            'eski_tarih' => $eskiTarih !== '' ? $this->tarihGunTr($eskiTarih) : '-',
            'eski_saat' => $eskiSaat !== '' ? substr($eskiSaat, 0, 5) : '-',
            'tarih' => $this->tarihGunTr((string) $randevu['tarih']),
            'saat' => substr((string) $randevu['baslangic_saati'], 0, 5),
            'bitis_saati' => substr((string) $randevu['bitis_saati'], 0, 5),
            'paket_adi' => (string) ($randevu['paket_adi'] ?? $randevu['tur']),
            'ogretmen_adi' => (string) ($randevu['uzman'] ?? ''),
            'katilim_linki' => $this->katilimLinki($randevuId),
        ]);

        $adet = 0;
        foreach ($alicilar as $alici) {
            $degiskenler['veli_adi'] = (string) ($alici['veli_adi'] ?? 'Velimiz');
            $mesaj = $this->randevuGuncellemeMesaji($degiskenler);
            $sonuc = $this->kuyrugaEkle([$alici], $mesaj, [
                'sablon_anahtari' => 'randevu_guncellendi',
                'olay_tipi' => 'randevu_guncellendi',
                'ogrenci_id' => $ogrenciId,
                'grup_id' => $randevu['grup_id'] ?? null,
                'randevu_id' => $randevuId,
                'mukerrer_anahtari' => 'randevu_guncellendi:' . $randevuId . ':' . md5(
                    implode('|', [
                        $eskiTarih,
                        $eskiSaat,
                        (string) $randevu['tarih'],
                        (string) $randevu['baslangic_saati'],
                        (string) $randevu['durum'],
                    ])
                ),
                'olusturan_kullanici_id' => $randevu['isleyen_kullanici_id'] ?? null,
            ]);
            $adet += (int) $sonuc['adet'];
        }

        return $adet;
    }

    public function odemeAlindi(int $odemeId): void
    {
        $stmt = $this->db->prepare(
            'SELECT od.*, CONCAT(o.ad, " ", o.soyad) AS ogrenci_adi, p.paket_adi, p.net_paket_tutari,
                    COALESCE(SUM(CASE WHEN od2.iptal = 0 THEN od2.tutar ELSE 0 END), 0) AS tahsilat
             FROM odemeler od
             INNER JOIN ogrenciler o ON o.id = od.ogrenci_id
             INNER JOIN paketler p ON p.id = od.paket_id
             LEFT JOIN odemeler od2 ON od2.paket_id = p.id
             WHERE od.id = :id
             GROUP BY od.id'
        );
        $stmt->execute(['id' => $odemeId]);
        $odeme = $stmt->fetch();
        if (!$odeme) {
            return;
        }
        $alicilar = $this->ogrenciVelileri((int) $odeme['ogrenci_id']);
        if ($alicilar === []) {
            return;
        }
        $degiskenler = $this->varsayilanDegiskenler($odeme) + [
            'paket_adi' => (string) $odeme['paket_adi'],
            'odeme_tutari' => $this->para((float) $odeme['tutar']),
            'kalan_borc' => $this->para(max(0, (float) $odeme['net_paket_tutari'] - (float) $odeme['tahsilat'])),
        ];
        foreach ($alicilar as $alici) {
            $mesaj = $this->sablonHazirla('odeme_alindi', $degiskenler + ['veli_adi' => (string) ($alici['veli_adi'] ?? 'Velimiz')]);
            $this->kuyrugaEkle([$alici], $mesaj, [
                'sablon_anahtari' => 'odeme_alindi',
                'olay_tipi' => 'odeme_alindi',
                'ogrenci_id' => (int) $odeme['ogrenci_id'],
                'odeme_id' => $odemeId,
                'mukerrer_anahtari' => 'odeme_alindi:' . $odemeId,
                'olusturan_kullanici_id' => $odeme['alan_kullanici_id'] ?? null,
            ]);
        }
    }

    public function randevuHatirlatmalariOlustur(): int
    {
        if (!$this->config['appointment_reminder_enabled']) {
            return 0;
        }

        $gunOnce = max(0, (int) ($this->config['appointment_reminder_days_before'] ?? 1));
        $gonderimSaati = $this->saatNormalize((string) ($this->config['appointment_reminder_time'] ?? '14:00'));
        if (!$this->hatirlatmaSaatiGeldiMi($gonderimSaati)) {
            return 0;
        }

        $hedefTarih = date('Y-m-d', strtotime('+' . $gunOnce . ' days'));
        $stmt = $this->db->prepare(
            'SELECT r.*, CONCAT(o.ad, " ", o.soyad) AS ogrenci_adi, COALESCE(p.paket_adi, r.tur) AS paket_adi,
                    COALESCE(g.ad, "") AS grup_adi, COALESCE(CONCAT(k.ad, " ", k.soyad), "") AS uzman
             FROM randevular r
             INNER JOIN ogrenciler o ON o.id = r.ogrenci_id
             LEFT JOIN paketler p ON p.id = r.paket_id
             LEFT JOIN gruplar g ON g.id = r.grup_id
             LEFT JOIN kullanicilar k ON k.id = r.ogretmen_id
             WHERE r.durum = "planlandi"
               AND r.tarih = :hedef_tarih'
        );
        $stmt->execute(['hedef_tarih' => $hedefTarih]);
        $adet = 0;
        foreach ($stmt->fetchAll() as $randevu) {
            $anahtar = $this->randevuSablonAnahtari((string) $randevu['tur']);
            $alicilar = $this->ogrenciVelileri((int) $randevu['ogrenci_id']);
            if ($alicilar === []) {
                continue;
            }
            $degiskenler = $this->varsayilanDegiskenler($randevu) + [
                'tarih' => $this->tarihTr((string) $randevu['tarih']),
                'saat' => substr((string) $randevu['baslangic_saati'], 0, 5),
                'bitis_saati' => substr((string) $randevu['bitis_saati'], 0, 5),
                'paket_adi' => (string) $randevu['paket_adi'],
                'ogretmen_adi' => (string) $randevu['uzman'],
                'katilim_linki' => $this->katilimLinki((int) $randevu['id']),
            ];
            foreach ($alicilar as $alici) {
                $mesaj = $this->sablonHazirla($anahtar, $degiskenler + ['veli_adi' => (string) ($alici['veli_adi'] ?? 'Velimiz')]);
                $sonuc = $this->kuyrugaEkle([$alici], $mesaj, [
                    'sablon_anahtari' => $anahtar,
                    'olay_tipi' => 'randevu_hatirlatma',
                    'ogrenci_id' => (int) $randevu['ogrenci_id'],
                    'grup_id' => $randevu['grup_id'] ?? null,
                    'randevu_id' => (int) $randevu['id'],
                    'mukerrer_anahtari' => 'randevu_hatirlatma:' . $hedefTarih . ':' . $randevu['id'],
                ]);
                $adet += (int) $sonuc['adet'];
            }
        }
        return $adet;
    }

    public function dogumGunuMesajlariOlustur(): int
    {
        if (empty($this->config['birthday_message_enabled'])) {
            return 0;
        }

        $gonderimSaati = $this->saatNormalize((string) ($this->config['birthday_message_time'] ?? '09:00'), '09:00');
        if (!$this->hatirlatmaSaatiGeldiMi($gonderimSaati)) {
            return 0;
        }

        $stmt = $this->db->query(
            'SELECT id, CONCAT(ad, " ", soyad) AS ogrenci_adi, dogum_tarihi
             FROM ogrenciler
             WHERE dogum_tarihi IS NOT NULL
               AND durum = "aktif"
               AND DATE_FORMAT(dogum_tarihi, "%m-%d") = DATE_FORMAT(CURDATE(), "%m-%d")
             ORDER BY ad ASC, soyad ASC'
        );

        $adet = 0;
        foreach ($stmt->fetchAll() as $ogrenci) {
            $ogrenciId = (int) $ogrenci['id'];
            $alicilar = $this->ogrenciVelileri($ogrenciId);
            if ($alicilar === []) {
                continue;
            }

            foreach ($alicilar as $alici) {
                $degiskenler = [
                    'veli_adi' => (string) ($alici['veli_adi'] ?? 'Velimiz'),
                    'ogrenci_adi' => (string) $ogrenci['ogrenci_adi'],
                    'kurum_adi' => (string) Ayar::deger('kurum_adi', 'Oyun Evleri Yönetim Sistemi'),
                    'klinik_adi' => (string) Ayar::deger('kurum_adi', 'Oyun Evleri Yönetim Sistemi'),
                ];
                $mesaj = $this->dogumGunuMesaji($degiskenler);
                $sonuc = $this->kuyrugaEkle([$alici], $mesaj, [
                    'sablon_anahtari' => 'dogum_gunu',
                    'olay_tipi' => 'dogum_gunu',
                    'ogrenci_id' => $ogrenciId,
                    'mukerrer_anahtari' => 'dogum_gunu:' . date('Y-m-d') . ':' . $ogrenciId,
                ]);
                $adet += (int) $sonuc['adet'];
            }
        }

        return $adet;
    }

    private function ayarlarIleBirlesikConfig(array $config): array
    {
        $varsayilanGun = (int) ($config['appointment_reminder_days_before'] ?? 0);
        if ($varsayilanGun <= 0 && isset($config['appointment_reminder_hours'])) {
            $varsayilanGun = max(1, (int) ceil(((int) $config['appointment_reminder_hours']) / 24));
        }
        $ayarlar = Ayar::coklu([
            'sms_enabled' => !empty($config['enabled']) ? '1' : '0',
            'sms_test_mode' => !empty($config['test_mode']) ? '1' : '0',
            'sms_test_phone' => (string) ($config['test_phone'] ?? ''),
            'sms_force_to' => (string) ($config['force_to'] ?? ''),
            'sms_max_recipients_per_request' => (string) ($config['max_recipients_per_request'] ?? 1000),
            'sms_max_retry_count' => (string) ($config['max_retry_count'] ?? 3),
            'sms_retry_delay_minutes' => (string) ($config['retry_delay_minutes'] ?? 10),
            'sms_appointment_reminder_enabled' => !empty($config['appointment_reminder_enabled']) ? '1' : '0',
            'sms_appointment_reminder_days_before' => (string) max(0, $varsayilanGun ?: 1),
            'sms_appointment_reminder_time' => (string) ($config['appointment_reminder_time'] ?? '14:00'),
            'sms_birthday_message_enabled' => !empty($config['birthday_message_enabled']) ? '1' : '0',
            'sms_birthday_message_time' => (string) ($config['birthday_message_time'] ?? '09:00'),
            'sms_payment_promise_reminder_enabled' => !empty($config['payment_promise_reminder_enabled']) ? '1' : '0',
            'sms_payment_promise_reminder_hours' => (string) ($config['payment_promise_reminder_hours'] ?? 24),
            'sms_netgsm_usercode' => (string) ($config['netgsm']['usercode'] ?? ''),
            'sms_netgsm_password' => (string) ($config['netgsm']['password'] ?? ''),
            'sms_netgsm_header' => (string) ($config['netgsm']['header'] ?? ''),
            'sms_netgsm_encoding' => (string) ($config['netgsm']['encoding'] ?? 'TR'),
            'sms_netgsm_filter' => (string) ($config['netgsm']['filter'] ?? '0'),
            'sms_netgsm_base_url' => (string) ($config['netgsm']['base_url'] ?? 'https://api.netgsm.com.tr'),
            'sms_netgsm_send_path' => (string) ($config['netgsm']['send_path'] ?? '/sms/rest/v2/send'),
            'sms_netgsm_report_path' => (string) ($config['netgsm']['report_path'] ?? '/sms/report'),
            'sms_netgsm_connect_timeout' => (string) ($config['netgsm']['connect_timeout'] ?? 10),
            'sms_netgsm_timeout' => (string) ($config['netgsm']['timeout'] ?? 30),
        ]);

        $config['enabled'] = (string) $ayarlar['sms_enabled'] === '1';
        $config['test_mode'] = (string) $ayarlar['sms_test_mode'] === '1';
        $config['test_phone'] = trim((string) $ayarlar['sms_test_phone']);
        $config['force_to'] = trim((string) $ayarlar['sms_force_to']);
        $config['max_recipients_per_request'] = max(1, min(10000, (int) $ayarlar['sms_max_recipients_per_request']));
        $config['max_retry_count'] = max(0, min(20, (int) $ayarlar['sms_max_retry_count']));
        $config['retry_delay_minutes'] = max(1, min(1440, (int) $ayarlar['sms_retry_delay_minutes']));
        $config['appointment_reminder_enabled'] = (string) $ayarlar['sms_appointment_reminder_enabled'] === '1';
        $config['appointment_reminder_days_before'] = max(0, min(30, (int) $ayarlar['sms_appointment_reminder_days_before']));
        $config['appointment_reminder_time'] = $this->saatNormalize((string) $ayarlar['sms_appointment_reminder_time']);
        $config['birthday_message_enabled'] = (string) $ayarlar['sms_birthday_message_enabled'] === '1';
        $config['birthday_message_time'] = $this->saatNormalize((string) $ayarlar['sms_birthday_message_time'], '09:00');
        $config['payment_promise_reminder_enabled'] = (string) $ayarlar['sms_payment_promise_reminder_enabled'] === '1';
        $config['payment_promise_reminder_hours'] = max(1, min(720, (int) $ayarlar['sms_payment_promise_reminder_hours']));
        $config['netgsm']['usercode'] = trim((string) $ayarlar['sms_netgsm_usercode']);
        $config['netgsm']['password'] = (string) $ayarlar['sms_netgsm_password'];
        $config['netgsm']['header'] = trim((string) $ayarlar['sms_netgsm_header']);
        $config['netgsm']['encoding'] = trim((string) $ayarlar['sms_netgsm_encoding']) ?: 'TR';
        $config['netgsm']['filter'] = trim((string) $ayarlar['sms_netgsm_filter']) ?: '0';
        $config['netgsm']['base_url'] = rtrim(trim((string) $ayarlar['sms_netgsm_base_url']) ?: 'https://api.netgsm.com.tr', '/');
        $config['netgsm']['send_path'] = trim((string) $ayarlar['sms_netgsm_send_path']) ?: '/sms/rest/v2/send';
        $config['netgsm']['report_path'] = trim((string) $ayarlar['sms_netgsm_report_path']) ?: '/sms/report';
        $config['netgsm']['connect_timeout'] = max(1, min(120, (int) $ayarlar['sms_netgsm_connect_timeout']));
        $config['netgsm']['timeout'] = max(1, min(300, (int) $ayarlar['sms_netgsm_timeout']));

        return $config;
    }

    private function saatNormalize(string $saat, string $varsayilan = '14:00'): string
    {
        if (!preg_match('/^(\d{1,2}):(\d{2})$/', trim($saat), $eslesme)) {
            return $varsayilan;
        }
        $saatDegeri = (int) $eslesme[1];
        $dakika = (int) $eslesme[2];
        if ($saatDegeri < 0 || $saatDegeri > 23 || $dakika < 0 || $dakika > 59) {
            return $varsayilan;
        }

        return sprintf('%02d:%02d', $saatDegeri, $dakika);
    }

    private function hatirlatmaSaatiGeldiMi(string $gonderimSaati): bool
    {
        return date('H:i') >= $gonderimSaati;
    }

    public function odemeSozuHatirlatmalariOlustur(): int
    {
        if (!$this->config['payment_promise_reminder_enabled']) {
            return 0;
        }
        $stmt = $this->db->query(
            'SELECT os.*, CONCAT(o.ad, " ", o.soyad) AS ogrenci_adi, p.paket_adi
             FROM odeme_sozleri os
             INNER JOIN ogrenciler o ON o.id = os.ogrenci_id
             INNER JOIN paketler p ON p.id = os.paket_id
             WHERE os.durum IN ("bekleniyor", "bugun_odenecek")
               AND os.soz_verilen_tarih BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)'
        );
        $adet = 0;
        foreach ($stmt->fetchAll() as $soz) {
            $alicilar = $this->ogrenciVelileri((int) $soz['ogrenci_id']);
            if ($alicilar === []) {
                continue;
            }
            $degiskenler = $this->varsayilanDegiskenler($soz) + [
                'paket_adi' => (string) $soz['paket_adi'],
                'odeme_tutari' => $this->para((float) $soz['soz_verilen_tutar']),
                'odeme_sozu_tarihi' => $this->tarihTr((string) $soz['soz_verilen_tarih']),
            ];
            foreach ($alicilar as $alici) {
                $mesaj = $this->sablonHazirla('odeme_sozu_hatirlatma', $degiskenler + ['veli_adi' => (string) ($alici['veli_adi'] ?? 'Velimiz')]);
                $sonuc = $this->kuyrugaEkle([$alici], $mesaj, [
                    'sablon_anahtari' => 'odeme_sozu_hatirlatma',
                    'olay_tipi' => 'odeme_sozu_hatirlatma',
                    'ogrenci_id' => (int) $soz['ogrenci_id'],
                    'odeme_sozu_id' => (int) $soz['id'],
                    'mukerrer_anahtari' => 'odeme_sozu_hatirlatma:' . $soz['id'] . ':' . date('Y-m-d'),
                ]);
                $adet += (int) $sonuc['adet'];
            }
        }
        return $adet;
    }

    public function kuyrukIsle(int $limit = 50): array
    {
        $kayitlar = SmsKaydi::sahiplen($limit);
        $sonuc = ['islenen' => 0, 'gonderilen' => 0, 'basarisiz' => 0, 'tekrar' => 0];
        foreach ($kayitlar as $kayit) {
            $sonuc['islenen']++;
            try {
                $yanit = $this->netgsm->smsGonder([[
                    'telefon' => $kayit['telefon'],
                    'mesaj' => $kayit['mesaj'],
                ]]);
                if ($yanit['basarili']) {
                    SmsKaydi::gonderildi((int) $kayit['id'], $yanit['islem_no'] ?? null, (string) $yanit['cevap']);
                    $sonuc['gonderilen']++;
                    continue;
                }
                if (($yanit['gecici_hata'] ?? false) && (int) $kayit['deneme_sayisi'] < (int) $this->config['max_retry_count']) {
                    SmsKaydi::tekrarBekliyor((int) $kayit['id'], (string) $yanit['cevap'], (int) $this->config['retry_delay_minutes'], (string) $yanit['cevap']);
                    $sonuc['tekrar']++;
                    continue;
                }
                SmsKaydi::basarisiz((int) $kayit['id'], (string) $yanit['cevap'], (string) $yanit['cevap']);
                $sonuc['basarisiz']++;
            } catch (Throwable $e) {
                if ((int) $kayit['deneme_sayisi'] < (int) $this->config['max_retry_count']) {
                    SmsKaydi::tekrarBekliyor((int) $kayit['id'], $e->getMessage(), (int) $this->config['retry_delay_minutes']);
                    $sonuc['tekrar']++;
                } else {
                    SmsKaydi::basarisiz((int) $kayit['id'], $e->getMessage());
                    $sonuc['basarisiz']++;
                }
            }
        }
        return $sonuc;
    }

    public function durumlariSorgula(int $limit = 100): int
    {
        $stmt = $this->db->prepare(
            "SELECT id, provider_islem_no FROM sms_kayitlari
             WHERE durum = 'gonderildi' AND provider_islem_no IS NOT NULL
             ORDER BY gonderilme_tarihi ASC LIMIT :limit"
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $adet = 0;
        foreach ($stmt->fetchAll() as $kayit) {
            $yanit = $this->netgsm->durumSorgula((string) $kayit['provider_islem_no']);
            if ($yanit['durum'] === 'teslim_edildi') {
                SmsKaydi::teslimEdildi((int) $kayit['id'], (string) $yanit['cevap']);
                $adet++;
            } elseif ($yanit['durum'] === 'basarisiz') {
                SmsKaydi::basarisiz((int) $kayit['id'], 'Provider teslim hatasi.', (string) $yanit['cevap']);
                $adet++;
            }
        }
        return $adet;
    }

    private function smsVerisi(array $alici, string $orijinal, string $telefon, string $mesaj, array $meta, string $durum, ?string $hata, ?string $mukerrer): array
    {
        return [
            'sablon_anahtari' => $meta['sablon_anahtari'] ?? 'manuel_sms',
            'olay_tipi' => $meta['olay_tipi'] ?? 'manuel_sms',
            'alici_tipi' => $alici['alici_tipi'] ?? 'veli',
            'alici_id' => $alici['alici_id'] ?? $alici['veli_id'] ?? null,
            'ogrenci_id' => $meta['ogrenci_id'] ?? $alici['ogrenci_id'] ?? null,
            'veli_id' => $meta['veli_id'] ?? $alici['veli_id'] ?? null,
            'grup_id' => $meta['grup_id'] ?? null,
            'randevu_id' => $meta['randevu_id'] ?? null,
            'odeme_id' => $meta['odeme_id'] ?? null,
            'odeme_sozu_id' => $meta['odeme_sozu_id'] ?? null,
            'telefon_orijinal' => $orijinal,
            'telefon' => $telefon,
            'mesaj' => $mesaj,
            'parca_sayisi' => $this->parcaSayisi($mesaj),
            'durum' => $durum,
            'hata_mesaji' => $hata,
            'mukerrer_anahtari' => $mukerrer,
            'olusturan_kullanici_id' => $meta['olusturan_kullanici_id'] ?? null,
        ];
    }

    private function ogrenciVelileri(int $ogrenciId): array
    {
        $stmt = $this->db->prepare(
            'SELECT v.id AS veli_id, v.telefon, CONCAT(v.ad, " ", v.soyad) AS veli_adi, ov.birincil_mi, ov.ogrenci_id
             FROM ogrenci_velileri ov
             INNER JOIN veliler v ON v.id = ov.veli_id
             WHERE ov.ogrenci_id = :ogrenci_id
             ORDER BY ov.birincil_mi DESC, v.id ASC'
        );
        $stmt->execute(['ogrenci_id' => $ogrenciId]);
        return $stmt->fetchAll();
    }

    private function randevuDetay(int $randevuId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, CONCAT(o.ad, " ", o.soyad) AS ogrenci_adi, COALESCE(g.ad, "") AS grup_adi,
                    COALESCE(p.paket_adi, r.tur) AS paket_adi, COALESCE(CONCAT(k.ad, " ", k.soyad), "") AS uzman,
                    kr.tarih AS telafi_kaynak_tarih, kr.baslangic_saati AS telafi_kaynak_saat
             FROM randevular r
             INNER JOIN ogrenciler o ON o.id = r.ogrenci_id
             LEFT JOIN gruplar g ON g.id = r.grup_id
             LEFT JOIN paketler p ON p.id = r.paket_id
             LEFT JOIN kullanicilar k ON k.id = r.ogretmen_id
             LEFT JOIN telafi_haklari th ON th.id = r.telafi_hakki_id
             LEFT JOIN randevular kr ON kr.id = th.kaynak_randevu_id
             WHERE r.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $randevuId]);
        $randevu = $stmt->fetch();
        return $randevu ?: null;
    }

    private function randevuDetaylari(array $randevuIdleri): array
    {
        $yerTutucular = implode(',', array_fill(0, count($randevuIdleri), '?'));
        $stmt = $this->db->prepare(
            'SELECT r.*, CONCAT(o.ad, " ", o.soyad) AS ogrenci_adi, COALESCE(g.ad, "") AS grup_adi,
                    COALESCE(p.paket_adi, r.tur) AS paket_adi, COALESCE(CONCAT(k.ad, " ", k.soyad), "") AS uzman
             FROM randevular r
             INNER JOIN ogrenciler o ON o.id = r.ogrenci_id
             LEFT JOIN gruplar g ON g.id = r.grup_id
             LEFT JOIN paketler p ON p.id = r.paket_id
             LEFT JOIN kullanicilar k ON k.id = r.ogretmen_id
             WHERE r.id IN (' . $yerTutucular . ')
             ORDER BY r.ogrenci_id ASC, r.tarih ASC, r.baslangic_saati ASC'
        );
        $stmt->execute($randevuIdleri);
        return $stmt->fetchAll();
    }

    private function varsayilanDegiskenler(array $veri): array
    {
        $ogrenciAdi = (string) ($veri['ogrenci_adi'] ?? $veri['ogrenci'] ?? '');
        return [
            'veli_adi' => (string) ($veri['veli_adi'] ?? 'Velimiz'),
            'ogrenci_adi' => $ogrenciAdi,
            'grup_adi' => (string) ($veri['grup_adi'] ?? $veri['grup'] ?? ''),
            'kurum_adi' => 'Oyun Evleri Yönetim Sistemi',
            'klinik_adi' => 'Oyun Evleri Yönetim Sistemi',
            'kurum_telefonu' => '',
            'paket_adi' => (string) ($veri['paket_adi'] ?? ''),
            'tarih' => isset($veri['tarih']) ? $this->tarihTr((string) $veri['tarih']) : '',
            'saat' => isset($veri['baslangic_saati']) ? substr((string) $veri['baslangic_saati'], 0, 5) : '',
            'bitis_saati' => isset($veri['bitis_saati']) ? substr((string) $veri['bitis_saati'], 0, 5) : '',
            'ogretmen_adi' => (string) ($veri['uzman'] ?? ''),
            'odeme_tutari' => '',
            'kalan_borc' => '',
            'odeme_sozu_tarihi' => '',
        ];
    }

    private function randevuSablonAnahtari(string $tur): string
    {
        $tur = mb_strtolower($tur);
        if (str_contains($tur, 'tanisma') || str_contains($tur, 'tanışma')) {
            return 'tanisma_dersi_hatirlatma';
        }
        if (str_contains($tur, 'veli')) {
            return 'veli_gorusmesi_hatirlatma';
        }
        if (str_contains($tur, 'workshop')) {
            return 'workshop_hatirlatma';
        }
        return 'randevu_hatirlatma';
    }

    private function katilimLinki(int $randevuId): string
    {
        $token = Randevu::katilimTokeni($randevuId);
        if (!$token) {
            return '';
        }

        $baseUrl = rtrim((string) Config::get('APP_URL', ''), '/');
        if ($baseUrl === '') {
            $baseUrl = 'http://localhost:8080';
        }
        return $baseUrl . '/randevu-katilim?t=' . rawurlencode($token);
    }

    private function randevuOlusturmaMesaji(array $degiskenler): string
    {
        $sablon = SmsKaydi::sablonBul('randevu_olusturuldu');
        if ($sablon && (int) $sablon['aktif'] === 1 && str_contains((string) $sablon['mesaj'], '{randevu_listesi}')) {
            return $this->sablonHazirla('randevu_olusturuldu', $degiskenler);
        }

        return trim(sprintf(
            'Sayin %s, %s icin %s randevulariniz olusturuldu: %s. %s',
            (string) $degiskenler['veli_adi'],
            (string) $degiskenler['ogrenci_adi'],
            (string) $degiskenler['paket_adi'],
            (string) $degiskenler['randevu_listesi'],
            (string) $degiskenler['kurum_adi']
        ));
    }

    private function randevuGuncellemeMesaji(array $degiskenler): string
    {
        $sablon = SmsKaydi::sablonBul('randevu_guncellendi');
        if ($sablon && (int) $sablon['aktif'] === 1) {
            return $this->sablonHazirla('randevu_guncellendi', $degiskenler);
        }

        return trim(sprintf(
            'Sayin %s, %s randevusu guncellendi. Eski: %s %s. Yeni: %s %s. %s. %s',
            (string) $degiskenler['veli_adi'],
            (string) $degiskenler['ogrenci_adi'],
            (string) $degiskenler['eski_tarih'],
            (string) $degiskenler['eski_saat'],
            (string) $degiskenler['tarih'],
            (string) $degiskenler['saat'],
            (string) $degiskenler['paket_adi'],
            (string) $degiskenler['kurum_adi']
        ));
    }

    private function telafiDersiOlusturmaMesaji(array $degiskenler): string
    {
        $sablon = SmsKaydi::sablonBul('telafi_dersi_olusturuldu');
        if ($sablon && (int) $sablon['aktif'] === 1) {
            return $this->sablonHazirla('telafi_dersi_olusturuldu', $degiskenler);
        }

        return trim(sprintf(
            'Sayin %s, %s icin telafi dersiniz %s saat %s olarak planlanmistir. Kaynak ders: %s %s. %s',
            (string) $degiskenler['veli_adi'],
            (string) $degiskenler['ogrenci_adi'],
            (string) $degiskenler['tarih'],
            (string) $degiskenler['saat'],
            (string) $degiskenler['kaynak_tarih'],
            (string) $degiskenler['kaynak_saat'],
            (string) $degiskenler['kurum_adi']
        ));
    }

    private function dogumGunuMesaji(array $degiskenler): string
    {
        try {
            return $this->sablonHazirla('dogum_gunu', $degiskenler);
        } catch (Throwable $e) {
            return trim(sprintf(
                'Sayin %s, %s icin mutlu yaslar dileriz. Yeni yasi saglik, oyun ve mutlulukla dolsun. %s',
                (string) $degiskenler['veli_adi'],
                (string) $degiskenler['ogrenci_adi'],
                (string) $degiskenler['kurum_adi']
            ));
        }
    }

    private function randevuListesiMetni(array $randevular): string
    {
        $satirlar = [];
        foreach ($randevular as $randevu) {
            $satirlar[] = $this->tarihGunTr((string) $randevu['tarih']) . ' ' . substr((string) $randevu['baslangic_saati'], 0, 5);
        }
        return implode(', ', $satirlar);
    }

    private function tarihGunTr(string $date): string
    {
        $ts = strtotime($date);
        if (!$ts) {
            return $date;
        }

        $gunler = [
            1 => 'Pazartesi',
            2 => 'Sali',
            3 => 'Carsamba',
            4 => 'Persembe',
            5 => 'Cuma',
            6 => 'Cumartesi',
            7 => 'Pazar',
        ];

        return date('d.m.Y', $ts) . ' ' . $gunler[(int) date('N', $ts)];
    }

    private function tarihTr(string $date): string
    {
        $ts = strtotime($date);
        return $ts ? date('d.m.Y', $ts) : $date;
    }

    private function para(float $tutar): string
    {
        return number_format($tutar, 2, ',', '.') . ' TL';
    }
}
