Haklısınız; sistem **WordPress tabanlı olmayacak**. `app.talyakids.com` üzerinde çalışacak, **bağımsız PHP 8.2 + MySQL 8.0 tabanlı özel bir web uygulaması** olacak. WordPress, tema, eklenti, `$wpdb`, nonce veya `wp-load.php` kullanılmayacak.

Aşağıdaki promptu kullanın:

# TALYA KIDS YÖNETİM PANELİ – BAĞIMSIZ PHP UYGULAMASI GELİŞTİRME PROMPTU

`app.talyakids.com` alan adında çalışacak, bağımsız bir Talya Kids yönetim paneli geliştir.

Bu proje WordPress tabanlı olmayacaktır.

Aşağıdaki teknolojileri kullan:

* PHP 8.2
* MySQL 8.0
* Apache
* Saf JavaScript
* Fetch API
* HTML5
* CSS3
* PDO
* JSON tabanlı AJAX
* Podman Compose
* ARM64 uyumluluğu

Sistem ilk olarak Apple Silicon Mac üzerinde Podman ile localhost ortamında geliştirilecek, ardından aynı kod tabanı ve veritabanı yapısıyla `app.talyakids.com` alan adına taşınacaktır.

WordPress ile ilgili hiçbir yapı kullanma:

* WordPress fonksiyonları kullanma.
* WordPress eklenti sistemi kullanma.
* WordPress tema sistemi kullanma.
* `$wpdb` kullanma.
* `wp-load.php` kullanma.
* `wp-admin` kullanma.
* WordPress nonce yapısı kullanma.
* WordPress kullanıcı ve rol sistemi kullanma.

Uygulamanın kendi:

* kullanıcı sistemi,
* rol ve yetki sistemi,
* oturum sistemi,
* CSRF güvenliği,
* veritabanı katmanı,
* yönlendirme yapısı,
* AJAX sistemi

bulunmalıdır.

---

# 1. ÇALIŞMA ORTAMI

Yerel geliştirme adresleri:

```text
Uygulama:
http://localhost:8080

Giriş:
http://localhost:8080/giris

Panel:
http://localhost:8080/panel

phpMyAdmin:
http://localhost:8081
```

Canlı ortam:

```text
https://app.talyakids.com/giris
https://app.talyakids.com/panel
```

Kod içinde `localhost:8080` veya `app.talyakids.com` sabit olarak yazılmamalıdır.

Ortam ayarları `.env` dosyasından alınmalıdır.

Örnek:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080
APP_TIMEZONE=Europe/Istanbul

DB_HOST=db
DB_PORT=3306
DB_DATABASE=talya_db
DB_USERNAME=talya_user
DB_PASSWORD=talya_pass

SESSION_NAME=talya_kids_session
SESSION_LIFETIME=7200
CSRF_TOKEN_NAME=talya_csrf
```

Canlı ortamda yalnızca `.env` değerleri değiştirilecektir.

---

# 2. PODMAN COMPOSE YAPISI

Aşağıdaki yapıya uygun çalış:

```yaml
services:
  db:
    image: mysql:8.0
    platform: linux/arm64/v8
    container_name: talya_db
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: root_pass
      MYSQL_DATABASE: talya_db
      MYSQL_USER: talya_user
      MYSQL_PASSWORD: talya_pass
    command:
      - --character-set-server=utf8mb4
      - --collation-server=utf8mb4_unicode_ci
    volumes:
      - talya_db_data:/var/lib/mysql

  app:
    build:
      context: .
      dockerfile: Dockerfile
      platforms:
        - linux/arm64/v8
    container_name: talya_app
    platform: linux/arm64/v8
    restart: unless-stopped
    depends_on:
      - db
    ports:
      - "8080:80"
    environment:
      APP_ENV: local
      APP_DEBUG: "true"
      APP_URL: http://localhost:8080
      APP_TIMEZONE: Europe/Istanbul
      DB_HOST: db
      DB_PORT: 3306
      DB_DATABASE: talya_db
      DB_USERNAME: talya_user
      DB_PASSWORD: talya_pass
    volumes:
      - ./:/var/www/html

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    platform: linux/arm64/v8
    container_name: talya_phpmyadmin
    restart: unless-stopped
    depends_on:
      - db
    ports:
      - "8081:80"
    environment:
      PMA_HOST: db
      PMA_PORT: 3306
      PMA_USER: root
      PMA_PASSWORD: root_pass
      UPLOAD_LIMIT: 256M

volumes:
  talya_db_data:
```

Uygulama container adı:

```text
talya_app
```

Veritabanı container adı:

```text
talya_db
```

---

# 3. DOCKERFILE

PHP 8.2 ve Apache tabanlı Dockerfile oluştur:

```dockerfile
FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        unzip \
        git \
        libzip-dev \
        default-mysql-client \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mysqli \
        zip \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
```

Apache DocumentRoot klasörü:

```text
/var/www/html/public
```

olmalıdır.

Uygulamanın yalnızca `public` klasörü internetten erişilebilir olmalıdır.

---

# 4. PROJE KLASÖR YAPISI

Aşağıdaki modüler yapıyı kullan:

```text
talya-kids/
├── app/
│   ├── Controllers/
│   │   ├── GirisController.php
│   │   ├── PanelController.php
│   │   ├── OgrenciController.php
│   │   ├── VeliController.php
│   │   ├── GrupController.php
│   │   ├── PaketController.php
│   │   ├── OdemeController.php
│   │   ├── OdemeSozuController.php
│   │   ├── RandevuController.php
│   │   ├── YoklamaController.php
│   │   ├── PaketDisiHakController.php
│   │   ├── TelafiController.php
│   │   ├── RaporController.php
│   │   └── AyarlarController.php
│   ├── Models/
│   │   ├── Kullanici.php
│   │   ├── Ogrenci.php
│   │   ├── Veli.php
│   │   ├── Grup.php
│   │   ├── Paket.php
│   │   ├── Odeme.php
│   │   ├── OdemeSozu.php
│   │   ├── Randevu.php
│   │   ├── Yoklama.php
│   │   ├── HakHareketi.php
│   │   ├── PaketDisiHak.php
│   │   ├── TelafiHakki.php
│   │   ├── Bildirim.php
│   │   └── IslemKaydi.php
│   ├── Services/
│   │   ├── KimlikDogrulamaServisi.php
│   │   ├── YetkiServisi.php
│   │   ├── PaketServisi.php
│   │   ├── OdemeServisi.php
│   │   ├── RandevuServisi.php
│   │   ├── YoklamaServisi.php
│   │   ├── HakHareketiServisi.php
│   │   ├── TelafiServisi.php
│   │   ├── BildirimServisi.php
│   │   ├── TahsilatTahminServisi.php
│   │   └── LogServisi.php
│   ├── Core/
│   │   ├── Veritabani.php
│   │   ├── Router.php
│   │   ├── Controller.php
│   │   ├── Model.php
│   │   ├── Session.php
│   │   ├── Csrf.php
│   │   ├── Validator.php
│   │   ├── Response.php
│   │   ├── Auth.php
│   │   └── Config.php
│   ├── Middleware/
│   │   ├── GirisKontrolu.php
│   │   ├── YetkiKontrolu.php
│   │   └── CsrfKontrolu.php
│   └── Helpers/
│       ├── tarih.php
│       ├── para.php
│       ├── metin.php
│       └── genel.php
├── config/
│   ├── app.php
│   ├── database.php
│   ├── routes.php
│   └── permissions.php
├── database/
│   ├── migrations/
│   ├── seeds/
│   └── schema.sql
├── public/
│   ├── index.php
│   ├── ajax.php
│   ├── .htaccess
│   └── assets/
│       ├── css/
│       │   ├── panel.css
│       │   ├── formlar.css
│       │   ├── tablolar.css
│       │   ├── takvim.css
│       │   └── mobil.css
│       ├── js/
│       │   ├── ajax.js
│       │   ├── panel.js
│       │   ├── ogrenciler.js
│       │   ├── veliler.js
│       │   ├── gruplar.js
│       │   ├── paketler.js
│       │   ├── odemeler.js
│       │   ├── randevular.js
│       │   ├── yoklama.js
│       │   ├── paket-disi-haklar.js
│       │   └── telafiler.js
│       └── images/
├── resources/
│   └── views/
│       ├── layouts/
│       │   ├── ana.php
│       │   ├── panel.php
│       │   └── giris.php
│       ├── auth/
│       │   └── giris.php
│       ├── panel/
│       │   ├── genel-bakis.php
│       │   ├── ogrenciler.php
│       │   ├── ogrenci-detay.php
│       │   ├── veliler.php
│       │   ├── gruplar.php
│       │   ├── paketler.php
│       │   ├── odemeler.php
│       │   ├── odeme-sozleri.php
│       │   ├── randevular.php
│       │   ├── yoklama.php
│       │   ├── paket-disi-haklar.php
│       │   ├── telafi-yonetimi.php
│       │   ├── tahsilat-plani.php
│       │   ├── raporlar.php
│       │   └── ayarlar.php
│       └── errors/
│           ├── 403.php
│           ├── 404.php
│           └── 500.php
├── storage/
│   ├── logs/
│   ├── cache/
│   └── uploads/
├── cron/
│   ├── otomatik-gelmedi.php
│   ├── otomatik-telafi.php
│   └── geciken-odemeler.php
├── docker/
│   └── apache.conf
├── bootstrap.php
├── composer.json
├── .env
├── .env.example
├── Dockerfile
└── compose.yaml
```

Dosya, sınıf, metot ve değişken adlarında anlaşılır Türkçe kelimeler kullan ancak Türkçe karakter kullanma.

Örnek:

```php
$ogrenci_id
$odeme_tutari
$kalan_telafi_hakki
$randevu_tarihi
```

Kod açıklamaları Türkçe olmalıdır.

---

# 5. MVC VE KATMANLI MİMARİ

Projeyi katmanlı mimariyle geliştir.

Controller görevleri:

* HTTP isteğini almak.
* Veriyi doğrulamak.
* İlgili servisi çağırmak.
* JSON veya HTML yanıt döndürmek.

Model görevleri:

* Veritabanı kayıtlarını temsil etmek.
* Basit veri erişim metotlarını içermek.

Service görevleri:

* İş kurallarını yürütmek.
* Transaction yönetmek.
* Birden fazla modeli birlikte kullanmak.
* Hak düşme, ödeme, telafi ve randevu işlemlerini yönetmek.

View dosyalarında SQL sorgusu veya iş mantığı bulunmamalıdır.

Controller içinde doğrudan karmaşık SQL kullanılmamalıdır.

---

# 6. VERİTABANI BAĞLANTISI

PDO kullan.

Örnek bağlantı özellikleri:

```php
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
PDO::ATTR_EMULATE_PREPARES => false
```

Bütün SQL sorgularında prepared statement kullan.

Veritabanı bilgilerini yalnızca `.env` dosyasından al.

Bağlantı kodunda sabit şifre veya veritabanı adı bulunmamalıdır.

MySQL bağlantısında:

```text
utf8mb4
```

kullan.

---

# 7. KULLANICI VE ROL SİSTEMİ

Kullanıcı rolleri:

* Kurucu
* Yönetici
* Öğretmen
* Muhasebe
* Resepsiyon

Kendi kullanıcı tablonu oluştur.

Kullanıcı alanları:

* ID
* Ad
* Soyad
* E-posta
* Telefon
* Şifre
* Rol
* Aktiflik durumu
* Son giriş tarihi
* Oluşturulma tarihi
* Güncellenme tarihi

Şifreleri:

```php
password_hash()
password_verify()
```

ile yönet.

Şifreleri hiçbir zaman düz metin saklama.

Oturum güvenliği:

* Başarılı girişte session ID yenile.
* Oturum zaman aşımı uygula.
* HTTP only cookie kullan.
* Canlı ortamda secure cookie kullan.
* SameSite=Lax veya Strict kullan.
* Çıkışta session verilerini tamamen sil.

---

# 8. YETKİLER

Kurucu:

* Bütün verilere erişebilir.
* Finansal raporları görebilir.
* İndirim uygulayabilir.
* Kullanıcı oluşturabilir.
* Sistem ayarlarını değiştirebilir.

Yönetici:

* Öğrenci, veli, grup ve randevuları yönetebilir.
* Paket ve ödeme bilgilerini görebilir.
* Yetkisi varsa ödeme kaydedebilir.
* Telafi planlayabilir.

Öğretmen:

* Kendi gruplarını görebilir.
* Yoklama alabilir.
* Öğrenci notu ekleyebilir.
* Finansal bilgileri göremez.

Muhasebe:

* Paketleri görebilir.
* Ödeme kaydedebilir.
* Ödeme sözü yönetebilir.
* Tahsilat raporlarını görebilir.

Resepsiyon:

* Öğrenci ve veli ekleyebilir.
* Randevu oluşturabilir.
* Yetki verilirse ödeme kaydedebilir.

Her AJAX işleminde rol ve yetki kontrolü yapılmalıdır.

---

# 9. VERİTABANI TABLOLARI

Özel MySQL tabloları oluştur.

Tablo adları:

```text
kullanicilar
roller
rol_yetkileri
ogrenciler
veliler
ogrenci_velileri
gruplar
grup_ogrencileri
ders_programlari
paketler
odemeler
odeme_sozleri
randevular
yoklamalar
hak_hareketleri
paket_disi_haklar
telafi_haklari
telafi_onerileri
bildirimler
islem_kayitlari
ayarlar
```

Bütün tablolar:

```text
ENGINE=InnoDB
CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci
```

olmalıdır.

Birincil anahtarlar:

```sql
BIGINT UNSIGNED AUTO_INCREMENT
```

olmalıdır.

Finans alanları:

```sql
DECIMAL(12,2)
```

olmalıdır.

Foreign key ilişkileri açık şekilde tanımlanmalıdır.

Finansal ve hak hareketleri için doğrudan cascade delete kullanma.

Finansal kayıtlarda soft delete veya iptal durumu kullan.

---

# 10. ÖĞRENCİ YÖNETİMİ

Her öğrenci için:

* Ad
* Soyad
* Doğum tarihi
* Yaş ve ay hesabı
* Cinsiyet
* Fotoğraf
* Kayıt tarihi
* Aktif veya pasif durum
* Veli
* İkinci veli
* Acil durum kişisi
* Sağlık bilgisi
* Alerji bilgisi
* Özel durum notu
* Yönetici notu
* Öğretmen notu
* Kayıtlı gruplar

Öğrenci detayında:

* Aktif paket
* Toplam paket sayısı
* Kalan normal hak
* Kalan telafi hakkı
* Paket dışı haklar
* Sonraki randevu
* Tahmini son ders
* Toplam paket bedeli
* Toplam indirim
* Toplam tahsilat
* Açık borç
* En yakın ödeme sözü
* Paket geçmişi
* Ödeme geçmişi
* Yoklama geçmişi
* Randevu geçmişi
* Telafi geçmişi
* Hak hareketleri

---

# 11. PAKET YÖNETİMİ

Her yeni paket ayrı kayıt olmalıdır.

Eski paketin üzerine yazma.

Paket alanları:

* Öğrenci ID
* Paket sıra numarası
* Paket adı
* Haftalık katılım sayısı
* Toplam normal ders hakkı
* Toplam telafi hakkı
* Kullanılan normal hak
* Kullanılan telafi hakkı
* Kalan normal hak
* Kalan telafi hakkı
* Başlangıç tarihi
* Tahmini son ders tarihi
* Liste fiyatı
* İndirim türü
* İndirim tutarı
* Net paket tutarı
* Paket durumu
* Yenileme durumu
* Yönetici notu
* Oluşturan kullanıcı
* Oluşturulma tarihi

Varsayılan:

Haftada 1 gün:

```text
4 normal ders
1 telafi hakkı
```

Haftada 2 gün:

```text
8 normal ders
2 telafi hakkı
```

Paket sıra numarası öğrenci bazında otomatik oluşturulmalıdır.

---

# 12. İNDİRİM YÖNETİMİ

İndirim türleri:

* Nakit indirimi
* Kardeş indirimi
* Kampanya
* Yönetici indirimi
* Personel indirimi
* Diğer

Hesaplama:

```text
Net paket tutarı = Liste fiyatı - İndirim tutarı
```

İndirim alanları:

* İndirim türü
* İndirim tutarı
* Açıklama
* Uygulayan kullanıcı
* Tarih

İndirim kayıtları doğrudan silinmemelidir.

---

# 13. ÖDEME YÖNETİMİ

Bir paket için birden fazla ödeme alınabilmelidir.

Ödeme yöntemleri:

* Nakit
* Kredi kartı
* Havale/EFT
* Ödeme bağlantısı
* Diğer

Ödeme alanları:

* Öğrenci
* Veli
* Paket
* Tarih
* Tutar
* Yöntem
* Makbuz numarası
* Açıklama
* Ödemeyi alan kullanıcı
* İptal durumu
* İptal nedeni

Hesaplamalar:

```text
Toplam tahsilat = Aktif ödemelerin toplamı

Kalan borç = Net paket tutarı - Toplam tahsilat
```

Fazla ödeme ayrıca gösterilmelidir.

Ödeme işlemlerinde transaction kullanılmalıdır.

---

# 14. ÖDEME SÖZÜ

Veliler farklı tarihlerde ödeme sözü verebilir.

Alanlar:

* Öğrenci
* Veli
* Paket
* Söz verilen tutar
* Söz verilen tarih
* Hatırlatma tarihi
* Durum
* Açıklama
* Önceki tarih
* Yeni tarih
* İlgili ödeme ID
* Oluşturan kullanıcı

Durumlar:

* Bekleniyor
* Bugün ödenecek
* Ödendi
* Gecikti
* Yeni tarih verildi
* İptal edildi

Tarih geldiğinde:

```text
Bugün ödenecek
```

Tarih geçtiğinde:

```text
Gecikti
```

olmalıdır.

---

# 15. RANDEVU SİSTEMİ

Kurucu öğrenciye paket içinden veya paket dışında randevu tanımlayabilmelidir.

Randevu türleri:

* Normal ders
* Telafi dersi
* Paket dışı ders
* Ücretsiz ek ders
* Ücretli tek ders
* Tanışma dersi
* Veli görüşmesi
* Workshop
* Diğer

Hak kaynakları:

* Aktif paket
* Paket telafi hakkı
* Paket dışı hak
* Ücretsiz ek hak
* Ücretli tek ders
* Hak düşülmeyecek

Randevu alanları:

* Öğrenci
* Veli
* Grup
* Paket
* Paket dışı hak
* Telafi hakkı
* Öğretmen
* Tarih
* Başlangıç saati
* Bitiş saati
* Tür
* Hak kaynağı
* Durum
* Açıklama
* Oluşturan kullanıcı

Durumlar:

* Planlandı
* Geldi
* Gelmedi
* Mazeretli gelmedi
* Geç iptal
* Kurum iptali
* Ertelendi
* Tamamlandı

---

# 16. OTOMATİK GELMEDİ

Randevu bitiş zamanı ve bekleme süresi geçtiğinde, durum hâlâ “Planlandı” ise otomatik “Gelmedi” yapılmalıdır.

Varsayılan bekleme süresi:

```text
60 dakika
```

Örnek:

```text
Ders:
15.00–16.00

Bekleme:
60 dakika

17.00 olduğunda hâlâ Planlandı ise:
Gelmedi
```

Manuel seçilmiş durumlar değiştirilmemelidir.

İşlem yalnızca bir kez yapılmalıdır.

Audit log oluşturulmalıdır.

---

# 17. HAK DÜŞME MANTIĞI

Geldi:

```text
Normal paketten 1 hak düş.
```

Gelmedi:

```text
Önce telafi hakkını kontrol et.
Telafi varsa telafiden 1 düş.
Telafi yoksa normal haktan 1 düş.
```

Mazeretli gelmedi:

```text
Önce telafiden düş.
Telafi yoksa normal haktan düş.
```

Geç iptal:

```text
Normal haktan 1 düş.
```

Kurum iptali:

```text
Hak düşme.
```

Ertelendi:

```text
Hak düşme.
```

Paket dışı randevuda seçilen paket dışı haktan düş.

Negatif hak oluşmasına izin verme.

---

# 18. OTOMATİK TELAFİ

Öğrenci gelmediğinde ve telafi hakkı kullanıldığında:

1. Telafi hakkından 1 düş.
2. Planlanmayı bekleyen telafi kaydı oluştur.
3. Bir sonraki haftadaki aynı gün ve saati kontrol et.
4. Uygunsa öneri oluştur veya ayara göre otomatik ekle.
5. Uygun değilse alternatif tarih sun.
6. Yönetici isterse başka tarih seçebilsin.

Kontroller:

* Grup aktif mi?
* Kontenjan uygun mu?
* Öğretmen uygun mu?
* Öğrencinin çakışan randevusu var mı?
* Yaş grubu uygun mu?
* Kurum kapalı mı?
* Aynı telafi için başka randevu var mı?

Manuel belirlenen tarih otomatik sistem tarafından değiştirilmemelidir.

---

# 19. PAKET DIŞI HAKLAR

Kurucu öğrenciye paket dışında hak tanımlayabilmelidir.

Hak türleri:

* Ücretsiz ek ders
* Hediye ders
* Yönetici hakkı
* Tanışma dersi
* Telafi hakkı
* Workshop hakkı
* Ücretli tek ders
* Diğer

Alanlar:

* Öğrenci
* Hak türü
* Toplam hak
* Kullanılan hak
* Kalan hak
* Başlangıç tarihi
* Son kullanım tarihi
* Grup
* Öğretmen
* Ücretli veya ücretsiz
* Tutar
* Açıklama
* Durum

---

# 20. TAHSİLAT TAHMİNİ

Kurucu aşağıdaki gelirleri ayrı ayrı görmelidir:

* Potansiyel yenileme
* Kesinleşmiş yenileme
* Ödeme sözü
* Gecikmiş ödeme
* Mevcut paket borcu
* Gerçekleşen tahsilat

Tarih filtreleri:

* Bugün
* Bu hafta
* Gelecek hafta
* 15 gün
* 30 gün
* Özel tarih aralığı

Aynı tutar iki kez toplam gelire eklenmemelidir.

---

# 21. AJAX.PHP

Bütün panel işlemleri:

```text
/public/ajax.php
```

üzerinden ilerlemelidir.

`ajax.php` yalnızca dağıtıcı olmalıdır.

Görevleri:

* Yalnızca POST kabul et.
* Oturum kontrolü yap.
* CSRF kontrolü yap.
* İşlem adını doğrula.
* Yetki kontrolü yap.
* Controller çağır.
* JSON cevap döndür.

Kullanıcıdan gelen dosya adını doğrudan include etme.

İşlem haritası kullan.

Örnek:

```php
$islem_haritasi = [
    'ogrenci_ekle' => [
        'controller' => OgrenciController::class,
        'metot' => 'ekle',
        'yetki' => 'ogrenci_ekle',
    ],
    'odeme_kaydet' => [
        'controller' => OdemeController::class,
        'metot' => 'kaydet',
        'yetki' => 'odeme_ekle',
    ],
];
```

---

# 22. AJAX CEVAP FORMATI

Başarılı:

```json
{
  "basari": true,
  "mesaj": "İşlem tamamlandı.",
  "veri": {}
}
```

Hatalı:

```json
{
  "basari": false,
  "mesaj": "İşlem tamamlanamadı.",
  "hatalar": {}
}
```

Doğru HTTP durum kodları kullan.

---

# 23. JAVASCRIPT AJAX

Saf JavaScript ve Fetch API kullan.

Ortak fonksiyon:

```javascript
async function talyaAjax(islem, veriler = {}) {
    const yanit = await fetch('/ajax.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.talyaCsrfToken
        },
        body: JSON.stringify({
            islem,
            ...veriler
        })
    });

    const sonuc = await yanit.json();

    if (!yanit.ok || !sonuc.basari) {
        throw new Error(
            sonuc.mesaj || 'İşlem sırasında bir hata oluştu.'
        );
    }

    return sonuc;
}
```

Her modül için ayrı JavaScript dosyası kullan.

---

# 24. GÜVENLİK

Aşağıdaki güvenlik önlemlerini uygula:

* PDO prepared statements
* CSRF token
* Session fixation koruması
* XSS koruması
* Output escaping
* Rol ve yetki kontrolü
* Rate limiting
* Brute force koruması
* Güvenli şifre saklama
* HTTP only cookie
* Secure cookie
* SameSite cookie
* Dosya yükleme kontrolü
* MIME type kontrolü
* Maksimum dosya boyutu
* Audit log
* Hata detaylarını kullanıcıya göstermeme

---

# 25. CRON

Otomatik işlemler AJAX üzerinden çalışmamalıdır.

Komut satırı dosyaları:

```text
cron/otomatik-gelmedi.php
cron/otomatik-telafi.php
cron/geciken-odemeler.php
```

Yerel test:

```bash
podman exec talya_app \
php /var/www/html/cron/otomatik-gelmedi.php
```

```bash
podman exec talya_app \
php /var/www/html/cron/otomatik-telafi.php
```

Canlı sunucu cron örneği:

```cron
*/5 * * * * /usr/bin/php /home/KULLANICI/app.talyakids.com/cron/otomatik-gelmedi.php
*/10 * * * * /usr/bin/php /home/KULLANICI/app.talyakids.com/cron/otomatik-telafi.php
0 * * * * /usr/bin/php /home/KULLANICI/app.talyakids.com/cron/geciken-odemeler.php
```

Cron dosyaları yalnızca CLI üzerinden çalışmalıdır.

Web üzerinden doğrudan erişimi engelle.

---

# 26. GELİŞTİRME AŞAMALARI

Aşama 1:

* Podman Compose
* Dockerfile
* Apache yapılandırması
* MVC çekirdeği
* Router
* PDO bağlantısı
* `.env`
* Kullanıcı ve giriş sistemi
* Rol ve yetkiler
* Panel iskeleti

Aşama 2:

* Öğrenciler
* Veliler
* Gruplar
* Ders programı

Aşama 3:

* Paketler
* İndirimler
* Ödemeler
* Ödeme sözleri
* Paket dışı haklar

Aşama 4:

* Randevular
* Yoklama
* Hak hareketleri
* Transaction sistemi

Aşama 5:

* Otomatik gelmedi
* Otomatik telafi
* Manuel telafi
* Cron işlemleri

Aşama 6:

* Yaklaşan yenilemeler
* Tahsilat tahmini
* Dashboard
* Bildirimler

Aşama 7:

* Raporlar
* CSV dışa aktarma
* Mobil görünüm
* Güvenlik testleri
* Canlıya taşıma

---

# 27. KOD ÜRETİM KURALLARI

Her aşamada:

1. Dosya listesini ver.
2. Dosya yollarını belirt.
3. Her dosyanın tam kodunu yaz.
4. TODO bırakma.
5. Placeholder kullanma.
6. Eksik veya sembolik kod verme.
7. Önceki kodlarla uyumlu ilerle.
8. Değişen dosyanın tamamını yeniden ver.
9. Podman test komutlarını yaz.
10. Beklenen sonucu belirt.
11. Hata durumlarını açıkla.
12. PHP 8.2 uyumluluğunu kontrol et.
13. MySQL 8.0 uyumluluğunu kontrol et.
14. ARM64 uyumluluğunu kontrol et.
15. Güvenlik kontrollerini açıkla.

---

# 28. İLK CEVAPTA İSTENENLER

İlk cevapta kod yazmaya başlama.

Önce aşağıdakileri hazırla:

1. Genel mimari
2. Klasör yapısı
3. Veritabanı şeması
4. Tablo ilişkileri
5. MVC akışı
6. AJAX mimarisi
7. Kullanıcı ve yetki sistemi
8. Randevu sistemi
9. Hak düşme mantığı
10. Otomatik gelmedi akışı
11. Otomatik telafi akışı
12. Manuel telafi akışı
13. Paket dışı hak sistemi
14. Ödeme ve ödeme sözü sistemi
15. Tahsilat tahmini
16. Cron yapısı
17. Localhost’tan `app.talyakids.com` adresine taşıma planı
18. Geliştirme aşamaları
19. Kritik güvenlik riskleri

Mimari tamamlandıktan sonra Aşama 1 kodlarını eksiksiz üret.

Bu sürümde sistem tamamen bağımsızdır; WordPress kurulumu veya WordPress dosyaları gerektirmez.
