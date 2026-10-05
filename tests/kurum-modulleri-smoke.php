<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Veritabani;
use App\Services\KurumModuluServisi;

$assert = static function (bool $kosul, string $mesaj): void {
    if (!$kosul) {
        fwrite(STDERR, "[FAIL] {$mesaj}\n");
        exit(1);
    }
    fwrite(STDOUT, "[OK] {$mesaj}\n");
};

$db = Veritabani::baglan();
$tabloVar = (bool) $db->query(
    'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "kurum_modulleri"'
)->fetchColumn();
$assert($tabloVar, 'Kurum modülleri tablosu mevcut');
$sayfaTablosuVar = (bool) $db->query(
    'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "kurum_modul_sayfalari"'
)->fetchColumn();
$assert($sayfaTablosuVar, 'Kurum modül sayfaları tablosu mevcut');

$beklenenSira = ['ogrenci_islemleri', 'program', 'finans', 'icerik_takip', 'sms', 'yonetim'];
$assert(array_keys(KurumModuluServisi::TANIMLAR) === $beklenenSira, 'Modüller sidebar sırasıyla tanımlı');
$assert(isset(KurumModuluServisi::TANIMLAR['finans']['sayfalar']['faturalar']), 'Faturalar finans altında ayrı sayfa olarak tanımlı');

$kurumId = (int) $db->query('SELECT id FROM kurumlar ORDER BY id LIMIT 1')->fetchColumn();
$assert($kurumId > 0, 'Modül testi için kurum bulundu');

$db->beginTransaction();
try {
    $servis = new KurumModuluServisi();
    $servis->kaydet($kurumId, ['program', 'finans', 'sms']);
    $aktifSayfalar = array_keys(KurumModuluServisi::sayfaTanimlari());
    $aktifSayfalar = array_values(array_diff($aktifSayfalar, ['faturalar']));
    $servis->sayfalariKaydet($kurumId, $aktifSayfalar);
    $durumlar = $servis->kurumIcin($kurumId);
    $assert($durumlar['program'] && $durumlar['finans'] && $durumlar['sms'], 'Seçilen kurum modülleri açılıyor');
    $assert(!$durumlar['ogrenci_islemleri'], 'Seçilmeyen kurum modülleri kapanıyor');
    $assert($servis->yetkiIcinAktifMi('randevu_listele', $kurumId), 'Açık modülün sayfa yetkisi kullanılabiliyor');
    $assert(!$servis->yetkiIcinAktifMi('ogrenci_listele', $kurumId), 'Kapalı modülün sayfa yetkisi engelleniyor');
    $assert(!$servis->sayfaAktifMi('faturalar', $kurumId), 'Finans açıkken Faturalar sayfası kapatılabiliyor');
    $assert($servis->sayfaAktifMi('tahsilatlar', $kurumId), 'Aynı modüldeki açık sayfa kullanılabiliyor');
    $assert(!$servis->yolIcinAktifMi('/panel/faturalar', $kurumId), 'Kapalı Faturalar doğrudan URL erişiminde engelleniyor');
    $assert($servis->yolIcinAktifMi('/panel/odemeler/tahsilatlar', $kurumId), 'Açık Tahsilatlar URL erişiminde kullanılabiliyor');
    $assert(!$servis->islemIcinAktifMi('fatura_olustur', $kurumId), 'Kapalı Faturalar AJAX işlemleri engelleniyor');
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}

$controller = file_get_contents(dirname(__DIR__) . '/app/Controllers/KurumController.php');
$model = file_get_contents(dirname(__DIR__) . '/app/Models/Kurum.php');
$view = file_get_contents(dirname(__DIR__) . '/resources/views/panel/kurumlar.php');
$script = file_get_contents(dirname(__DIR__) . '/public/assets/js/kurumlar.js');
$assert(is_string($controller) && str_contains($controller, 'mudur_sifre'), 'Müdür şifresi sunucu tarafında işleniyor');
$assert(is_string($model) && str_contains($model, 'password_hash($mudur'), 'Müdür şifresi güvenli hash ile saklanıyor');
$assert(is_string($view) && str_contains($view, 'Kurum Müdürü Hesabı'), 'Kurum düzenleme ekranında müdür hesabı bulunuyor');
$assert(str_contains($view, 'name="sayfalar[]"'), 'Kurum düzenleme ekranında alt sayfa anahtarları bulunuyor');
$assert(str_contains($model, 'AS kurucu_eposta'), 'Kurum listesi mevcut kurucu hesabı bilgilerini getiriyor');
$assert(str_contains($view, 'data-institution-founder-status'), 'Kurum düzenleme ekranında kurucu hesap durumu bulunuyor');
$assert(is_string($script) && str_contains($script, 'row?.kurucu_eposta'), 'Mevcut kurucu hesabı düzenleme ekranına aktarılıyor');

fwrite(STDOUT, "Kurum modülleri smoke testleri tamamlandı.\n");
