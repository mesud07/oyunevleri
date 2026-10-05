<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Models\OgrenciAdresHaritasi;
use App\Services\CocukIsletmesiAktarimServisi;

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "[FAIL] {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "[OK] {$message}\n");
};

$assert(OgrenciAdresHaritasi::turkiyeSinirlariIcindeMi(36.8841, 30.7056), 'Antalya koordinatı kabul ediliyor');
$assert(!OgrenciAdresHaritasi::turkiyeSinirlariIcindeMi(51.5072, -0.1276), 'Türkiye dışındaki koordinat reddediliyor');
$assert(
    OgrenciAdresHaritasi::adresAnahtari(['adres' => ' Atatürk  Cd.  10 ', 'ilce' => 'MURATPAŞA', 'il' => 'ANTALYA'])
    === OgrenciAdresHaritasi::adresAnahtari(['adres' => 'atatürk cd. 10', 'ilce' => 'Muratpaşa', 'il' => 'Antalya']),
    'Aynı adres yazım farklarından bağımsız tek önbellek anahtarı üretiyor'
);

$migration = file_get_contents(dirname(__DIR__) . '/database/migrations/20260924_ogrenci_adres_haritasi.sql') ?: '';
$view = file_get_contents(dirname(__DIR__) . '/resources/views/panel/ogrenci-adres-haritasi.php') ?: '';
$newStudentView = file_get_contents(dirname(__DIR__) . '/resources/views/panel/ogrenci-yeni.php') ?: '';
$profileView = file_get_contents(dirname(__DIR__) . '/resources/views/panel/ogrenci-profil.php') ?: '';
$panelCss = file_get_contents(dirname(__DIR__) . '/public/assets/css/panel.css') ?: '';
$tableCss = file_get_contents(dirname(__DIR__) . '/public/assets/css/tablolar.css') ?: '';
$js = file_get_contents(dirname(__DIR__) . '/public/assets/js/ogrenci-adres-haritasi.js') ?: '';
$cacheMigration = file_get_contents(dirname(__DIR__) . '/database/migrations/20260924_adres_konum_onbellegi.sql') ?: '';
$businessMigration = file_get_contents(dirname(__DIR__) . '/database/migrations/20260924_cocuk_isletmeleri.sql') ?: '';
$businessImporter = file_get_contents(dirname(__DIR__) . '/app/Services/CocukIsletmesiAktarimServisi.php') ?: '';
$googlePlacesImporter = file_get_contents(dirname(__DIR__) . '/app/Services/GooglePlacesCocukIsletmesiAktarimServisi.php') ?: '';
$googlePlacesMigration = file_get_contents(dirname(__DIR__) . '/database/migrations/20260924_google_places_cocuk_isletmeleri.sql') ?: '';
$headers = file_get_contents(dirname(__DIR__) . '/app/Core/SecurityHeaders.php') ?: '';
$layout = file_get_contents(dirname(__DIR__) . '/resources/views/layouts/panel.php') ?: '';
$model = file_get_contents(dirname(__DIR__) . '/app/Models/OgrenciAdresHaritasi.php') ?: '';
$assert(str_contains($migration, 'adres_enlem') && str_contains($migration, 'adres_boylam'), 'Adres koordinat kolonları migration içinde');
$assert(str_contains($view, 'Veli Adres Yoğunluk Haritası'), 'Adres yoğunluk ekranı oluşturuldu');
$assert(str_contains($view, 'Google\'da Kontrol Et'), 'Adres doğruluğu için kullanıcı kontrollü Google Maps bağlantısı var');
$assert(str_contains($js, 'tile.openstreetmap.org'), 'OpenStreetMap karo katmanı tanımlı');
$assert(str_contains($js, 'districtCenters'), 'İlçe merkezi yedek koordinatları tanımlı');
$assert(str_contains($js, "'muratpaşa': [36.8888637, 30.7208911]"), 'Muratpaşa idari merkez koordinatı doğrulandı');
$assert(str_contains($js, 'districtCenter'), 'Konum seçici öğrencinin ilçesinden açılıyor');
$assert(str_contains($js, 'İlçe bazlı yaklaşık konum'), 'Doğrulanmamış adresler ilçe bazlı işaretleniyor');
$assert(str_contains($js, 'google.maps.Geocoder'), 'Google Maps ile otomatik sokak/cadde geocoding tanımlı');
$assert(str_contains($js, 'geoClusters') && str_contains($js, 'zoom_changed'), 'Google haritasında uzaklaştıkça kişi kümeleri oluşturuluyor');
$assert(str_contains($js, 'leafletClusters') && str_contains($js, "map.on('zoomend'"), 'OpenStreetMap görünümünde yakınlaştırmaya duyarlı kümeler oluşturuluyor');
$assert(str_contains($js, 'label: isCluster') && str_contains($js, 'String(count)'), 'Google küme işaretlerinde kişi sayısı gösteriliyor');
$assert(str_contains($view, 'Numaralı işaretler') && str_contains($view, 'Kişi kümesi'), 'Haritadaki küme davranışı kullanıcıya açıklanıyor');
$assert(str_contains($tableCss, '.student-map-cluster'), 'Kişi kümeleri okunabilir işaretlerle biçimlendiriliyor');
$assert(str_contains($js, 'ogrenci_adres_konumu_guncelle'), 'Otomatik bulunan koordinatlar öğrenci kaydına yazılıyor');
$assert(str_contains($js, 'adres_anahtari'), 'Adres değişikliği sırasında eski koordinatın kaydedilmesi engelleniyor');
$assert(str_contains($cacheMigration, 'UNIQUE KEY uq_adres_konum_onbellegi'), 'Adres koordinatları kurum bazında tekil önbelleğe alınıyor');
$assert(str_contains($businessMigration, 'UNIQUE KEY uq_cocuk_isletmeleri_osm'), 'Çocuk işletmeleri kaynak kimliğiyle tekilleştiriliyor');
$assert(str_contains($businessImporter, 'overpass-api.de/api/interpreter') && str_contains($businessImporter, 'overpass.kumi.systems/api/interpreter'), 'Tek seferlik OpenStreetMap işletme aktarımı ve yedek sunucu tanımlı');
$assert(str_contains($view, 'data-businesses='), 'Kayıtlı çocuk işletmeleri harita verisine bağlanıyor');
$assert(str_contains($view, '© OpenStreetMap'), 'OpenStreetMap veri atfı gösteriliyor');
$assert(str_contains($js, 'activeBusinessCategories'), 'Çocuk işletmesi kategori filtreleri çalışıyor');
$assert(str_contains($view, 'data-active-students-only') && str_contains($view, 'checked'), 'Harita varsayılan olarak yalnız aktif öğrencileri gösteriyor');
$assert(str_contains($model, 'AS aktif_randevusu_var') && str_contains($model, 'TIMESTAMP(r.tarih, r.baslangic_saati) >= NOW()'), 'Aktif öğrenci gelecek planlı randevusuna göre belirleniyor');
$assert(str_contains($view, 'data-heatmap-toggle'), 'Isı dağılımı katmanı kullanıcı tarafından açılıp kapatılabiliyor');
$assert(str_contains($js, 'visibleGoogleLocations') && str_contains($js, 'visibleLeafletStudents'), 'Aktif öğrenci filtresi iki harita sağlayıcısına uygulanıyor');
$assert(str_contains($js, 'class GoogleStudentHeatmap') && str_contains($js, 'paintHeatmap'), 'Google Maps üzerinde bağımsız ve görünür ısı katmanı çiziliyor');
$assert(str_contains($js, "map.getPane('studentHeatPane').appendChild(leafletHeatmap)"), 'OpenStreetMap üzerinde bağımsız ve görünür ısı katmanı çiziliyor');
$assert(CocukIsletmesiAktarimServisi::kategoriBelirle(['amenity' => 'kindergarten'], 'Örnek') === 'anaokulu', 'Anaokulu kategorisi belirleniyor');
$assert(CocukIsletmesiAktarimServisi::kategoriBelirle(['amenity' => 'school'], 'Örnek İlkokulu') === 'okul', 'Okul kategorisi belirleniyor');
$assert(CocukIsletmesiAktarimServisi::kategoriBelirle(['leisure' => 'playground'], 'Masal Oyun Evi') === 'oyun_evi', 'Oyun evi kategorisi belirleniyor');
$assert(CocukIsletmesiAktarimServisi::kategoriBelirle(['leisure' => 'playground'], '') === 'cocuk_parki', 'İsimsiz oyun alanları çocuk parkı kategorisine alınıyor');
$assert(str_contains($businessImporter, 'nwr["amenity"~"^(school|kindergarten|childcare)$"]'), 'Okul, anaokulu ve kreşler birlikte sorgulanıyor');
$assert(str_contains($businessImporter, 'nwr["leisure"="playground"]('), 'İsim şartı olmadan OpenStreetMap oyun alanları sorgulanıyor');
$assert(str_contains($businessImporter, 'foreach ($sorgular as $sorgu)'), 'Yoğun işletme sorgusu daha güvenilir küçük paketlere bölünüyor');
$assert(str_contains($googlePlacesImporter, "private const ILCELER = ['Muratpaşa', 'Kepez', 'Konyaaltı', 'Döşemealtı', 'Aksu']"), 'Google Places taraması beş hedef ilçeyi kapsıyor');
$assert(str_contains($googlePlacesImporter, "'pageSize' => 20"), 'Her ilçe ve kategori için geniş sonuç kümesi isteniyor');
$assert(str_contains($googlePlacesImporter, 'DATE_ADD(NOW(), INTERVAL 30 DAY)'), 'Google Places içeriği en fazla 30 gün önbellekte tutuluyor');
$assert(str_contains($googlePlacesMigration, 'uq_cocuk_isletmeleri_kaynak'), 'Google Place ID kaynakla birlikte tekilleştiriliyor');
$assert(str_contains($js, 'businessSourceLabel'), 'Harita işaretlerinde işletme veri kaynağı gösteriliyor');
$assert(str_contains($headers, 'https://tile.openstreetmap.org'), 'Harita karoları içerik güvenliği politikasında izinli');
$assert(str_contains($headers, 'https://maps.googleapis.com'), 'Google Maps içerikleri güvenlik politikasında izinli');
$assert(str_contains($newStudentView, '<option value="Antalya" selected>Antalya</option>'), 'Yeni öğrenci adresinde Antalya varsayılan seçiliyor');
foreach (['Muratpaşa', 'Kepez', 'Konyaaltı', 'Döşemealtı', 'Aksu'] as $ilce) {
    $assert(str_contains($newStudentView, 'value="' . $ilce . '"'), $ilce . ' yeni kayıt ilçe seçeneklerinde');
    $assert(str_contains($profileView, "'" . $ilce . "'"), $ilce . ' profil ilçe seçeneklerinde');
}
$assert(str_contains($profileView, 'Adresi Kaydet'), 'Profil adres bölümünde erişilebilir kaydet düğmesi var');
$assert(str_contains($panelCss, 'grid-template-rows: auto minmax(0, 1fr) auto'), 'Profil düzenleme başlığı ve kapatma düğmesi kaydırma dışında sabit');

fwrite(STDOUT, "Adres haritası smoke testleri tamamlandı.\n");
