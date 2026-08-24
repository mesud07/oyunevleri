<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Ayar;
use App\Models\Grup;
use App\Models\SmsKaydi;
use App\Services\NetgsmServisi;
use App\Services\SmsServisi;

final class SmsController extends Controller
{
    private function smsConfig(): array
    {
        $config = require BASE_PATH . '/config/sms.php';
        return (new SmsServisi($config))->config();
    }

    private function baglantiDurumu(): array
    {
        $ayarlar = Ayar::coklu([
            'sms_netgsm_last_check_at',
            'sms_netgsm_last_check_status',
            'sms_netgsm_last_check_message',
        ]);

        return [
            'son_test_tarihi' => trim((string) ($ayarlar['sms_netgsm_last_check_at'] ?? '')),
            'son_test_durumu' => trim((string) ($ayarlar['sms_netgsm_last_check_status'] ?? '')),
            'son_test_mesaji' => trim((string) ($ayarlar['sms_netgsm_last_check_message'] ?? '')),
        ];
    }

    private function baglantiAyarlariVerisi(array $data): array
    {
        $enabled = !empty($data['sms_enabled']) && (string) $data['sms_enabled'] !== '0';
        $testMode = !empty($data['sms_test_mode']) && (string) $data['sms_test_mode'] !== '0';
        $testPhone = trim((string) ($data['sms_test_phone'] ?? ''));
        $forceTo = trim((string) ($data['sms_force_to'] ?? ''));
        $baseUrl = rtrim(trim((string) ($data['sms_netgsm_base_url'] ?? 'https://api.netgsm.com.tr')), '/');
        $sendPath = trim((string) ($data['sms_netgsm_send_path'] ?? '/sms/rest/v2/send'));
        $reportPath = trim((string) ($data['sms_netgsm_report_path'] ?? '/sms/report'));
        $encoding = trim((string) ($data['sms_netgsm_encoding'] ?? 'TR')) ?: 'TR';
        $filter = trim((string) ($data['sms_netgsm_filter'] ?? '0')) ?: '0';
        $maxRecipients = max(1, min(10000, (int) ($data['sms_max_recipients_per_request'] ?? 1000)));
        $maxRetryCount = max(0, min(20, (int) ($data['sms_max_retry_count'] ?? 3)));
        $retryDelay = max(1, min(1440, (int) ($data['sms_retry_delay_minutes'] ?? 10)));
        $connectTimeout = max(1, min(120, (int) ($data['sms_netgsm_connect_timeout'] ?? 10)));
        $timeout = max(1, min(300, (int) ($data['sms_netgsm_timeout'] ?? 30)));

        return compact(
            'enabled',
            'testMode',
            'testPhone',
            'forceTo',
            'baseUrl',
            'sendPath',
            'reportPath',
            'encoding',
            'filter',
            'maxRecipients',
            'maxRetryCount',
            'retryDelay',
            'connectTimeout',
            'timeout'
        ) + [
            'usercode' => trim((string) ($data['sms_netgsm_usercode'] ?? '')),
            'password' => (string) ($data['sms_netgsm_password'] ?? ''),
            'header' => trim((string) ($data['sms_netgsm_header'] ?? '')),
        ];
    }

    private function baglantiAyarlariDogrula(array $ayarlar): ?array
    {
        if ($ayarlar['baseUrl'] === '' || !filter_var($ayarlar['baseUrl'], FILTER_VALIDATE_URL)) {
            return ['mesaj' => 'NetGSM base URL gecerli bir adres olmalidir.', 'alan' => 'sms_netgsm_base_url', 'ipucu' => 'Gecerli URL girin.'];
        }

        if ($ayarlar['sendPath'] === '' || $ayarlar['sendPath'][0] !== '/') {
            return ['mesaj' => 'Gonderim yolu "/" ile baslamalidir.', 'alan' => 'sms_netgsm_send_path', 'ipucu' => 'Ornek: /sms/rest/v2/send'];
        }

        if ($ayarlar['reportPath'] === '' || $ayarlar['reportPath'][0] !== '/') {
            return ['mesaj' => 'Rapor yolu "/" ile baslamalidir.', 'alan' => 'sms_netgsm_report_path', 'ipucu' => 'Ornek: /sms/report'];
        }

        return null;
    }

    private function netgsmConfigOlustur(array $ayarlar): array
    {
        $config = $this->smsConfig();
        $config['enabled'] = $ayarlar['enabled'];
        $config['test_mode'] = $ayarlar['testMode'];
        $config['test_phone'] = $ayarlar['testPhone'];
        $config['force_to'] = $ayarlar['forceTo'];
        $config['max_recipients_per_request'] = $ayarlar['maxRecipients'];
        $config['max_retry_count'] = $ayarlar['maxRetryCount'];
        $config['retry_delay_minutes'] = $ayarlar['retryDelay'];
        $config['netgsm']['usercode'] = $ayarlar['usercode'];
        $config['netgsm']['password'] = $ayarlar['password'];
        $config['netgsm']['header'] = $ayarlar['header'];
        $config['netgsm']['encoding'] = $ayarlar['encoding'];
        $config['netgsm']['filter'] = $ayarlar['filter'];
        $config['netgsm']['base_url'] = $ayarlar['baseUrl'];
        $config['netgsm']['send_path'] = $ayarlar['sendPath'];
        $config['netgsm']['report_path'] = $ayarlar['reportPath'];
        $config['netgsm']['connect_timeout'] = $ayarlar['connectTimeout'];
        $config['netgsm']['timeout'] = $ayarlar['timeout'];

        return $config;
    }

    private function baglantiTestSonucuKaydet(bool $basarili, string $mesaj): array
    {
        $tarih = date('Y-m-d H:i:s');
        $durum = $basarili ? 'basarili' : 'basarisiz';
        Ayar::kaydetCoklu([
            'sms_netgsm_last_check_at' => $tarih,
            'sms_netgsm_last_check_status' => $durum,
            'sms_netgsm_last_check_message' => $mesaj,
        ], [
            'sms_netgsm_last_check_at' => 'NetGSM baglanti testi son calisma tarihi.',
            'sms_netgsm_last_check_status' => 'NetGSM baglanti testi son durum bilgisi.',
            'sms_netgsm_last_check_message' => 'NetGSM baglanti testi son sonuc mesaji.',
        ]);

        return [
            'son_test_tarihi' => $tarih,
            'son_test_durumu' => $durum,
            'son_test_mesaji' => $mesaj,
        ];
    }

    public function sayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $config = $this->smsConfig();
        $smsServisi = new SmsServisi($config);
        $this->view('panel/sms-yonetimi', [
            'baslik' => 'SMS Yonetimi',
            'aktif' => 'sms',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
            'smsConfig' => $config,
            'smsConnectionStatus' => $this->baglantiDurumu(),
            'smsReminderSettings' => $smsServisi->hatirlatmaAyarlari(),
            'sablonlar' => SmsKaydi::sablonlar(),
            'gruplar' => Grup::liste(),
        ], 'panel');
    }

    public function raporlarSayfa(): void
    {
        if (!Auth::check()) {
            Response::redirect('/giris');
        }

        $this->view('panel/sms-raporlari', [
            'baslik' => 'SMS Raporlari',
            'aktif' => 'sms-raporlar',
            'kullanici' => Auth::user(),
            'csrf' => Csrf::token(),
        ], 'panel');
    }

    public function kayitlariListele(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        Response::json([
            'basari' => true,
            'mesaj' => 'SMS kayitlari listelendi.',
            'veri' => SmsKaydi::liste([
                'durum' => trim((string) ($data['durum'] ?? '')),
                'q' => trim((string) ($data['q'] ?? '')),
                'sayfa' => max(1, (int) ($data['sayfa'] ?? 1)),
                'limit' => min(100, max(10, (int) ($data['limit'] ?? 20))),
            ]),
        ]);
    }

    public function raporlariListele(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $filtre = [
            'durum' => trim((string) ($data['durum'] ?? '')),
            'olay_tipi' => trim((string) ($data['olay_tipi'] ?? '')),
            'ogrenci_id' => (int) ($data['ogrenci_id'] ?? 0),
            'q' => trim((string) ($data['q'] ?? '')),
            'baslangic' => trim((string) ($data['baslangic'] ?? '')),
            'bitis' => trim((string) ($data['bitis'] ?? '')),
            'sayfa' => max(1, (int) ($data['sayfa'] ?? 1)),
            'limit' => min(100, max(10, (int) ($data['limit'] ?? 20))),
        ];

        Response::json([
            'basari' => true,
            'mesaj' => 'SMS raporlari listelendi.',
            'veri' => SmsKaydi::raporListe($filtre),
        ]);
    }

    public function ogrenciRaporlari(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $ogrenciId = (int) ($data['ogrenci_id'] ?? 0);
        if ($ogrenciId <= 0) {
            Response::json(['basari' => false, 'mesaj' => 'Ogrenci secimi zorunludur.', 'hatalar' => []], 422);
            return;
        }

        Response::json([
            'basari' => true,
            'mesaj' => 'Ogrenci SMS raporlari listelendi.',
            'veri' => SmsKaydi::raporListe([
                'ogrenci_id' => $ogrenciId,
                'sayfa' => 1,
                'limit' => 100,
            ]),
        ]);
    }

    public function hatirlatmaAyarlariKaydet(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $aktif = isset($data['appointment_reminder_enabled'])
            && (string) $data['appointment_reminder_enabled'] !== ''
            && (string) $data['appointment_reminder_enabled'] !== '0';
        $gunOnce = max(0, min(30, (int) ($data['appointment_reminder_days_before'] ?? 1)));
        $saat = trim((string) ($data['appointment_reminder_time'] ?? '14:00'));
        if (!preg_match('/^(\d{1,2}):(\d{2})$/', $saat, $eslesme)) {
            Response::json(['basari' => false, 'mesaj' => 'Gonderim saati HH:MM formatinda olmalidir.', 'hatalar' => []], 422);
            return;
        }
        $saatDegeri = (int) $eslesme[1];
        $dakika = (int) $eslesme[2];
        if ($saatDegeri < 0 || $saatDegeri > 23 || $dakika < 0 || $dakika > 59) {
            Response::json(['basari' => false, 'mesaj' => 'Gecerli bir gonderim saati girin.', 'hatalar' => []], 422);
            return;
        }

        $gonderimSaati = sprintf('%02d:%02d', $saatDegeri, $dakika);
        Ayar::kaydetCoklu([
            'sms_appointment_reminder_enabled' => $aktif ? '1' : '0',
            'sms_appointment_reminder_days_before' => (string) $gunOnce,
            'sms_appointment_reminder_time' => $gonderimSaati,
        ], [
            'sms_appointment_reminder_enabled' => 'Randevu hatirlatma SMS otomasyonu aktiflik bilgisi.',
            'sms_appointment_reminder_days_before' => 'Randevudan kac gun once hatirlatma SMS kuyruga alinacak.',
            'sms_appointment_reminder_time' => 'Hatirlatma SMS kuyruga alma saati.',
        ]);

        Response::json([
            'basari' => true,
            'mesaj' => 'Hatirlatma ayarlari kaydedildi.',
            'veri' => [
                'appointment_reminder_enabled' => $aktif,
                'appointment_reminder_days_before' => $gunOnce,
                'appointment_reminder_time' => $gonderimSaati,
            ],
        ]);
    }

    public function baglantiAyarlariKaydet(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $ayarlar = $this->baglantiAyarlariVerisi($data);
        $hata = $this->baglantiAyarlariDogrula($ayarlar);
        if ($hata !== null) {
            Response::json(['basari' => false, 'mesaj' => $hata['mesaj'], 'hatalar' => [$hata['alan'] => $hata['ipucu']]], 422);
            return;
        }

        Ayar::kaydetCoklu([
            'sms_enabled' => $ayarlar['enabled'] ? '1' : '0',
            'sms_test_mode' => $ayarlar['testMode'] ? '1' : '0',
            'sms_test_phone' => $ayarlar['testPhone'],
            'sms_force_to' => $ayarlar['forceTo'],
            'sms_max_recipients_per_request' => (string) $ayarlar['maxRecipients'],
            'sms_max_retry_count' => (string) $ayarlar['maxRetryCount'],
            'sms_retry_delay_minutes' => (string) $ayarlar['retryDelay'],
            'sms_netgsm_usercode' => $ayarlar['usercode'],
            'sms_netgsm_password' => $ayarlar['password'],
            'sms_netgsm_header' => $ayarlar['header'],
            'sms_netgsm_encoding' => $ayarlar['encoding'],
            'sms_netgsm_filter' => $ayarlar['filter'],
            'sms_netgsm_base_url' => $ayarlar['baseUrl'],
            'sms_netgsm_send_path' => $ayarlar['sendPath'],
            'sms_netgsm_report_path' => $ayarlar['reportPath'],
            'sms_netgsm_connect_timeout' => (string) $ayarlar['connectTimeout'],
            'sms_netgsm_timeout' => (string) $ayarlar['timeout'],
        ], [
            'sms_enabled' => 'SMS servisi kurum bazli aktiflik bilgisi.',
            'sms_test_mode' => 'SMS test modu kurum bazli aktiflik bilgisi.',
            'sms_test_phone' => 'Varsayilan test telefonu.',
            'sms_force_to' => 'Tum SMSleri zorunlu yonlendirme telefonu.',
            'sms_max_recipients_per_request' => 'Tek API cagrisi basina maksimum alici adedi.',
            'sms_max_retry_count' => 'Maksimum otomatik tekrar deneme sayisi.',
            'sms_retry_delay_minutes' => 'Tekrar deneme bekleme suresi (dakika).',
            'sms_netgsm_usercode' => 'NetGSM kullanici kodu.',
            'sms_netgsm_password' => 'NetGSM sifresi.',
            'sms_netgsm_header' => 'NetGSM onayli mesaj basligi.',
            'sms_netgsm_encoding' => 'NetGSM encoding parametresi.',
            'sms_netgsm_filter' => 'NetGSM iysfilter parametresi.',
            'sms_netgsm_base_url' => 'NetGSM API base URL.',
            'sms_netgsm_send_path' => 'NetGSM gonderim endpoint yolu.',
            'sms_netgsm_report_path' => 'NetGSM rapor endpoint yolu.',
            'sms_netgsm_connect_timeout' => 'NetGSM baglanti timeout saniyesi.',
            'sms_netgsm_timeout' => 'NetGSM istek timeout saniyesi.',
        ]);

        Response::json([
            'basari' => true,
            'mesaj' => 'NetGSM baglanti ayarlari kaydedildi.',
            'veri' => $this->smsConfig(),
        ]);
    }

    public function baglantiDogrula(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $ayarlar = $this->baglantiAyarlariVerisi($data);
        $hata = $this->baglantiAyarlariDogrula($ayarlar);
        if ($hata !== null) {
            Response::json(['basari' => false, 'mesaj' => $hata['mesaj'], 'hatalar' => [$hata['alan'] => $hata['ipucu']]], 422);
            return;
        }

        $netgsm = new NetgsmServisi($this->netgsmConfigOlustur($ayarlar));
        $sonuc = $netgsm->mesajBasliklari();
        $basliklar = array_values(array_filter(array_map('strval', (array) ($sonuc['basliklar'] ?? []))));
        $mesaj = $sonuc['basarili']
            ? ($basliklar === [] ? 'NetGSM baglantisi dogrulandi. Onayli baslik bulunmuyor.' : 'NetGSM baglantisi dogrulandi. Onayli basliklar alindi.')
            : trim((string) ($sonuc['cevap'] ?? 'NetGSM baglantisi dogrulanamadi.'));

        $durum = $this->baglantiTestSonucuKaydet((bool) $sonuc['basarili'], $mesaj);

        Response::json([
            'basari' => (bool) $sonuc['basarili'],
            'mesaj' => $mesaj,
            'veri' => [
                'basliklar' => $basliklar,
                'ham_cevap' => (string) ($sonuc['cevap'] ?? ''),
                'durum' => $durum,
            ],
        ], $sonuc['basarili'] ? 200 : 422);
    }

    public function raporKontrolEt(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $id = (int) ($data['id'] ?? 0);
        $kayit = SmsKaydi::idIleBul($id);
        if (!$kayit) {
            Response::json(['basari' => false, 'mesaj' => 'SMS kaydi bulunamadi.', 'hatalar' => []], 404);
            return;
        }
        if (trim((string) ($kayit['provider_islem_no'] ?? '')) === '') {
            Response::json(['basari' => false, 'mesaj' => 'Bu SMS icin servis islem numarasi yok.', 'hatalar' => []], 422);
            return;
        }

        $yanit = (new NetgsmServisi())->durumSorgula((string) $kayit['provider_islem_no']);
        if ($yanit['durum'] === 'teslim_edildi') {
            SmsKaydi::teslimEdildi($id, (string) $yanit['cevap']);
        } elseif ($yanit['durum'] === 'basarisiz') {
            SmsKaydi::basarisiz($id, 'Provider teslim hatasi.', (string) $yanit['cevap']);
        }

        Response::json([
            'basari' => true,
            'mesaj' => 'SMS servis durumu kontrol edildi.',
            'veri' => [
                'kayit' => SmsKaydi::idIleBul($id),
                'servis_yaniti' => $yanit,
            ],
        ]);
    }

    public function detayGetir(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $kayit = SmsKaydi::idIleBul((int) ($data['id'] ?? 0));
        if (!$kayit) {
            Response::json(['basari' => false, 'mesaj' => 'SMS kaydi bulunamadi.', 'hatalar' => []], 404);
            return;
        }
        Response::json(['basari' => true, 'mesaj' => 'SMS kaydi getirildi.', 'veri' => $kayit]);
    }

    public function tekliGonder(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['telefon', 'mesaj']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Telefon ve mesaj zorunludur.', 'hatalar' => $hatalar], 422);
            return;
        }
        $smsServisi = new SmsServisi();
        $sonuc = $smsServisi->manuelTekli(
            trim((string) $data['telefon']),
            trim((string) $data['mesaj']),
            (int) (Auth::user()['id'] ?? 0)
        );
        $sonuc['gonderim'] = $smsServisi->kuyrukIsle(20);
        Response::json(['basari' => true, 'mesaj' => 'SMS gonderim kuyruguna alindi ve islendi.', 'veri' => $sonuc], 201);
    }

    public function ogrenciyeGonder(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['ogrenci_id', 'telefon', 'mesaj']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Ogrenci, telefon ve mesaj zorunludur.', 'hatalar' => $hatalar], 422);
            return;
        }

        $smsServisi = new SmsServisi();
        $sonuc = $smsServisi->manuelOgrenci(
            (int) $data['ogrenci_id'],
            (int) ($data['veli_id'] ?? 0),
            trim((string) $data['telefon']),
            trim((string) $data['mesaj']),
            trim((string) ($data['sablon_anahtari'] ?? 'manuel_sms')),
            (int) (Auth::user()['id'] ?? 0)
        );
        $sonuc['gonderim'] = $smsServisi->kuyrukIsle(20);
        Response::json(['basari' => true, 'mesaj' => 'SMS gonderim kuyruguna alindi ve islendi.', 'veri' => $sonuc], 201);
    }

    public function topluGonder(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['telefonlar', 'mesaj']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Alicilar ve mesaj zorunludur.', 'hatalar' => $hatalar], 422);
            return;
        }
        $telefonlar = preg_split('/[\s,;]+/', trim((string) $data['telefonlar'])) ?: [];
        $telefonlar = array_values(array_filter($telefonlar));
        $smsServisi = new SmsServisi();
        $sonuc = $smsServisi->manuelToplu($telefonlar, trim((string) $data['mesaj']), (int) (Auth::user()['id'] ?? 0));
        $sonuc['gonderim'] = $smsServisi->kuyrukIsle(100);
        Response::json(['basari' => true, 'mesaj' => 'Toplu SMS gonderim kuyruguna alindi ve islendi.', 'veri' => $sonuc], 201);
    }

    public function kuyrugaEkle(): void
    {
        $this->tekliGonder();
    }

    public function tekrarGonder(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $basarili = SmsKaydi::tekrarGonder((int) ($data['id'] ?? 0));
        Response::json([
            'basari' => $basarili,
            'mesaj' => $basarili ? 'SMS tekrar kuyruga alindi.' : 'SMS kaydi tekrar gonderime uygun degil.',
            'veri' => ['id' => (int) ($data['id'] ?? 0)],
        ], $basarili ? 200 : 422);
    }

    public function iptalEt(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $basarili = SmsKaydi::iptal((int) ($data['id'] ?? 0), (int) (Auth::user()['id'] ?? 0));
        Response::json([
            'basari' => $basarili,
            'mesaj' => $basarili ? 'SMS kuyrugu iptal edildi.' : 'SMS kaydi iptal edilemez.',
            'veri' => ['id' => (int) ($data['id'] ?? 0)],
        ], $basarili ? 200 : 422);
    }

    public function sablonlariniListele(): void
    {
        Response::json(['basari' => true, 'mesaj' => 'Sablonlar listelendi.', 'veri' => SmsKaydi::sablonlar()]);
    }

    public function sablonSecimleri(): void
    {
        Response::json(['basari' => true, 'mesaj' => 'Aktif SMS sablonlari listelendi.', 'veri' => SmsKaydi::sablonlar(true)]);
    }

    public function sablonKaydet(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $hatalar = Validator::gerekli($data, ['anahtar', 'baslik', 'mesaj']);
        if ($hatalar) {
            Response::json(['basari' => false, 'mesaj' => 'Sablon alanlari eksik.', 'hatalar' => $hatalar], 422);
            return;
        }
        if (!str_contains((string) $data['mesaj'], '{klinik_adi}')) {
            Response::json(['basari' => false, 'mesaj' => 'SMS iceriginde {klinik_adi} etiketi zorunludur.', 'hatalar' => ['mesaj' => '{klinik_adi} zorunlu.']], 422);
            return;
        }
        $id = SmsKaydi::sablonKaydet([
            'anahtar' => preg_replace('/[^a-z0-9_]/', '', mb_strtolower((string) $data['anahtar'])),
            'baslik' => trim((string) $data['baslik']),
            'mesaj' => trim((string) $data['mesaj']),
            'aktif' => (int) ($data['aktif'] ?? 1),
            'otomatik_gonderim' => (int) ($data['otomatik_gonderim'] ?? 0),
            'onay_durumu' => 'incelemede',
            'onay_notu' => 'Sablon incelemeye alindi.',
            'aciklama' => trim((string) ($data['aciklama'] ?? '')),
        ]);
        Response::json(['basari' => true, 'mesaj' => 'SMS sablonu kaydedildi.', 'veri' => ['id' => $id]]);
    }

    public function sablonDurumDegistir(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $basarili = SmsKaydi::sablonDurumDegistir((string) ($data['anahtar'] ?? ''), (bool) ($data['aktif'] ?? false));
        Response::json([
            'basari' => $basarili,
            'mesaj' => $basarili ? 'Sablon durumu guncellendi.' : 'Sablon bulunamadi.',
            'veri' => ['anahtar' => (string) ($data['anahtar'] ?? '')],
        ], $basarili ? 200 : 404);
    }

    public function sablonOnayla(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $basarili = SmsKaydi::sablonOnayla((string) ($data['anahtar'] ?? ''));
        Response::json([
            'basari' => $basarili,
            'mesaj' => $basarili ? 'SMS sablonu kullanilabilir yapildi.' : 'Sablon bulunamadi.',
            'veri' => ['anahtar' => (string) ($data['anahtar'] ?? '')],
        ], $basarili ? 200 : 404);
    }

    public function sablonReddet(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $basarili = SmsKaydi::sablonReddet((string) ($data['anahtar'] ?? ''), trim((string) ($data['onay_notu'] ?? '')));
        Response::json([
            'basari' => $basarili,
            'mesaj' => $basarili ? 'SMS sablonu reddedildi.' : 'Sablon bulunamadi.',
            'veri' => ['anahtar' => (string) ($data['anahtar'] ?? '')],
        ], $basarili ? 200 : 404);
    }

    public function netgsmBasliklariListele(): void
    {
        $sonuc = (new NetgsmServisi($this->smsConfig()))->mesajBasliklari();
        Response::json([
            'basari' => (bool) $sonuc['basarili'],
            'mesaj' => $sonuc['basarili'] ? 'NetGSM basliklari getirildi.' : 'NetGSM basliklari getirilemedi.',
            'veri' => $sonuc,
        ], $sonuc['basarili'] ? 200 : 422);
    }

    public function testGonder(): void
    {
        $data = $GLOBALS['talya_ajax_data'] ?? [];
        $config = $this->smsConfig();
        $telefon = trim((string) ($data['telefon'] ?? $config['test_phone'] ?? ''));
        if ($telefon === '') {
            Response::json(['basari' => false, 'mesaj' => 'Test telefonu girilmelidir.', 'hatalar' => []], 422);
            return;
        }
        $mesaj = trim((string) ($data['mesaj'] ?? 'Oyun Evleri Yönetim Sistemi test SMS.'));
        $smsServisi = new SmsServisi();
        $sonuc = $smsServisi->manuelTekli($telefon, $mesaj, (int) (Auth::user()['id'] ?? 0));
        $sonuc['gonderim'] = $smsServisi->kuyrukIsle(20);
        Response::json(['basari' => true, 'mesaj' => 'Test SMS gonderim kuyruguna alindi ve islendi.', 'veri' => $sonuc], 201);
    }
}
