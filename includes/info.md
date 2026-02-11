 🚀 1. PROJE GENEL TANIMI

**Proje Adı:** Oyunevleri.com Ekosistemi
**Yapı:** 1. **[www.oyunevleri.com](https://www.google.com/search?q=https://www.oyunevleri.com):** Pazaryeri (Oyun evi arama, listeleme, detay görme).
2. **app.oyunevleri.com:** SaaS Paneli (İşletme yönetimi, veli paneli, rezervasyon ve muhasebe).

---

## 🛠 2. SUNUCU VE KONFİGÜRASYON (config.php)

* **Sunucu/IP:** `89.252.183.194` (Güzel Hosting)
* **Veritabanı Kullanıcısı:** `oyunev_mesud` / **Şifre:** `Balkanlar07.`
* **Master DB:** `oyunev_master`
* **Kurum DB (Ortak):** `oyunev_kurum` (tüm kurumlar `kurum_id` ile ayrışır)
* **Bağlantı:** PDO, `utf8mb4_turkish_ci`.

---

## 🗄 3. VERİTABANI ŞEMASI (FULL SQL SCHEMA)

Tüm tablolar `InnoDB` motoru ve `utf8mb4_turkish_ci` karakter setiyle oluşturulmalıdır.

### **A. Master Veritabanı (oyunev_master)**

Bu veritabanı tüm sistemi koordine eder ve girişleri doğrular.

```sql
CREATE TABLE kurumlar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_kodu VARCHAR(20) UNIQUE, -- Giriş anahtarı
    kurum_adi VARCHAR(255),
    kurum_type VARCHAR(50),
    kurum_db_adi VARCHAR(100), -- Tek DB kullaniminda sabit: oyunev_kurum
    sehir VARCHAR(100),
    ilce VARCHAR(100),
    adres TEXT,
    hakkimizda TEXT,
    telefon VARCHAR(20),
    eposta VARCHAR(100),
    meb_onay TINYINT DEFAULT 0,
    aile_sosyal_onay TINYINT DEFAULT 0,
    hizmet_bahceli TINYINT DEFAULT 0,
    hizmet_guvenlik_kamerasi TINYINT DEFAULT 0,
    hizmet_ingilizce TINYINT DEFAULT 0,
    min_ay INT DEFAULT NULL, -- Pazaryeri hızlı filtre için
    max_ay INT DEFAULT NULL, -- Pazaryeri hızlı filtre için
    durum TINYINT DEFAULT 1,
    kayit_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE kullanicilar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    sube_id INT DEFAULT 0, -- 0 ise Merkez Admin
    kullanici_adi VARCHAR(50),
    sifre VARCHAR(255), -- password_hash ile saklanir
    yetki_seviyesi ENUM('merkez_admin', 'sube_admin', 'egitmen'),
    FOREIGN KEY (kurum_id) REFERENCES kurumlar(id)
);

-- Rol & Yetki Yönetimi (Merkez Admin sayfasindan yönetilir)
CREATE TABLE yetkiler (
    id INT PRIMARY KEY AUTO_INCREMENT,
    yetki_kodu VARCHAR(100) UNIQUE,
    yetki_adi VARCHAR(255),
    aciklama TEXT
);

CREATE TABLE roller (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    rol_adi VARCHAR(100),
    varsayilan TINYINT DEFAULT 0,
    aktif TINYINT DEFAULT 1,
    FOREIGN KEY (kurum_id) REFERENCES kurumlar(id)
);

CREATE TABLE rol_yetkiler (
    rol_id INT,
    yetki_id INT,
    PRIMARY KEY (rol_id, yetki_id),
    FOREIGN KEY (rol_id) REFERENCES roller(id),
    FOREIGN KEY (yetki_id) REFERENCES yetkiler(id)
);

CREATE TABLE kullanici_roller (
    kullanici_id INT,
    rol_id INT,
    PRIMARY KEY (kullanici_id, rol_id),
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id),
    FOREIGN KEY (rol_id) REFERENCES roller(id)
);

-- Kurum Profili (Pazaryeri içerikleri) - kurumlar tablosuna bağlı
CREATE TABLE kurum_galeri (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    gorsel_yol VARCHAR(255),
    sira INT DEFAULT 0,
    FOREIGN KEY (kurum_id) REFERENCES kurumlar(id)
);

CREATE TABLE kurum_egitmenler (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    ad_soyad VARCHAR(255),
    uzmanlik VARCHAR(255),
    biyografi TEXT,
    fotograf_yol VARCHAR(255),
    FOREIGN KEY (kurum_id) REFERENCES kurumlar(id)
);

CREATE TABLE kurum_yorumlar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    veli_adi VARCHAR(255),
    puan TINYINT, -- 1-5
    yorum TEXT,
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kurum_id) REFERENCES kurumlar(id)
);

CREATE TABLE kurum_fiyatlar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    paket_adi VARCHAR(255),
    aciklama TEXT,
    fiyat DECIMAL(10,2),
    birim ENUM('seans','aylik','paket') DEFAULT 'seans',
    FOREIGN KEY (kurum_id) REFERENCES kurumlar(id)
);

CREATE TABLE kurum_iletisim_talepleri (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    ad_soyad VARCHAR(255),
    telefon VARCHAR(20),
    mesaj TEXT,
    kaynak VARCHAR(50) DEFAULT 'web',
    ip_adresi VARCHAR(45),
    sayfa_url VARCHAR(255),
    durum ENUM('yeni','okundu','kapali') DEFAULT 'yeni',
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kurum_id) REFERENCES kurumlar(id)
);

```

### **B. Kurum Veritabanı (Tek Ortak DB: oyunev_kurum)**

Tüm kurumların operasyonel verileri tek DB'de tutulur. Ayrışım `kurum_id` kolonu ile yapılır.
Tum sorgular `kurum_id` ile filtrelenecek sekilde tasarlanmalidir.

```sql
CREATE TABLE subeler (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    sube_adi VARCHAR(255),
    sehir VARCHAR(100),
    ilce VARCHAR(100),
    adres TEXT
);

CREATE TABLE kurum_alanlari (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    alan_adi VARCHAR(255),
    kapasite INT,
    aciklama TEXT,
    durum TINYINT DEFAULT 1
);

CREATE TABLE adaylar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    sube_id INT NULL,
    ad_soyad VARCHAR(255),
    telefon VARCHAR(20),
    eposta VARCHAR(100),
    yas_ay INT NULL,
    notlar TEXT,
    durum ENUM('aday','donustu','kayip') DEFAULT 'aday',
    veli_id INT NULL,
    ogrenci_id INT NULL,
    kayit_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sube_id) REFERENCES subeler(id)
);

CREATE TABLE veliler (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    sube_id INT,
    ad_soyad VARCHAR(255),
    telefon VARCHAR(20),
    eposta VARCHAR(100),
    sifre VARCHAR(255),
    google_sub VARCHAR(100) NULL,
    google_email VARCHAR(100) NULL,
    bakiye_hak INT DEFAULT 0,
    hak_gecerlilik_bitis DATE NULL,
    hak_donduruldu TINYINT DEFAULT 0,
    FOREIGN KEY (sube_id) REFERENCES subeler(id)
);

CREATE TABLE veli_hak_hareketleri (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    veli_id INT,
    islem_tipi ENUM('ekleme','kullanim','iade'),
    miktar INT,
    aciklama TEXT,
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (veli_id) REFERENCES veliler(id)
);

CREATE TABLE veli_hak_dondurma (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    veli_id INT,
    baslangic_tarihi DATE,
    bitis_tarihi DATE,
    durum ENUM('aktif','pasif') DEFAULT 'aktif',
    aciklama TEXT,
    islem_yapan_id INT, -- kullanicilar.id (master)
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (veli_id) REFERENCES veliler(id)
);

CREATE TABLE veli_borclar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    veli_id INT,
    hak_hareket_id INT NULL,
    hak_miktar INT,
    tutar DECIMAL(10,2),
    son_odeme_tarihi DATE,
    durum ENUM('beklemede','odendi','iptal') DEFAULT 'beklemede',
    aciklama TEXT,
    olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    odeme_tarihi DATETIME NULL,
    odeme_yontemi ENUM('nakit','kredi_karti','havale') NULL,
    tahsil_tutar DECIMAL(10,2) NULL,
    FOREIGN KEY (veli_id) REFERENCES veliler(id)
);

CREATE TABLE sistem_ayarlar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    anahtar VARCHAR(100),
    deger VARCHAR(255),
    aciklama TEXT,
    guncelleme DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_kurum_anahtar (kurum_id, anahtar)
);

CREATE TABLE ogrenciler (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    veli_id INT,
    ad_soyad VARCHAR(255),
    dogum_tarihi DATE, -- Yaş/Ay kontrolü için
    saglik_notlari TEXT,
    FOREIGN KEY (veli_id) REFERENCES veliler(id)
);

CREATE TABLE oyun_gruplari (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    sube_id INT,
    alan_id INT NULL,
    grup_adi VARCHAR(255),
    min_ay INT,
    max_ay INT,
    kapasite INT,
    tekrar_tipi ENUM('tekil','haftalik') DEFAULT 'tekil',
    tekrar_gunleri SET('Pzt','Sal','Car','Per','Cum','Cmt','Paz') NULL,
    seans_baslangic_saati TIME NULL,
    seans_suresi_dk INT NULL,
    baslangic_tarihi DATE NULL,
    bitis_tarihi DATE NULL,
    FOREIGN KEY (sube_id) REFERENCES subeler(id),
    FOREIGN KEY (alan_id) REFERENCES kurum_alanlari(id)
);

CREATE TABLE seanslar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    grup_id INT,
    seans_baslangic DATETIME,
    seans_bitis DATETIME,
    kontenjan INT,
    durum ENUM('aktif','iptal') DEFAULT 'aktif',
    FOREIGN KEY (grup_id) REFERENCES oyun_gruplari(id)
);

CREATE TABLE rezervasyonlar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    ogrenci_id INT,
    seans_id INT,
    islem_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    durum ENUM('onayli', 'iptal', 'hak_yandi'),
    iptal_onay TINYINT DEFAULT 0,
    FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler(id),
    FOREIGN KEY (seans_id) REFERENCES seanslar(id)
);

CREATE TABLE kasa_hareketleri (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    sube_id INT,
    veli_id INT NULL,
    islem_tipi ENUM('gelir', 'gider'),
    kategori VARCHAR(100) NULL,
    odeme_yontemi ENUM('nakit', 'kredi_karti', 'havale'),
    tutar DECIMAL(10,2),
    aciklama TEXT,
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE materyal_havuzu (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurum_id INT,
    materyal_adi VARCHAR(255),
    kazanimlar TEXT, -- JSON Formatı
    materyal_dosya VARCHAR(255), -- PDF/Görsel yolu
    yukleyen_kullanici_id INT,
    yukleme_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP
);

```

---

## 💻 4. SAYFA YAPILARI VE USER FLOW

### **Pazaryeri (www)**

1. **Listeleme:** Kullanıcı şehir/ilçe seçer. `oyunev_master` üzerinden kurumlar listelenir.
2. **Filtreleme:** Yaş aralığı ve bakanlık onayı (MEB vb.) filtreleri uygulanır.
3. **Detay:** Seçilen kurumun galerisi, eğitmenleri ve fiyatları gösterilir.

### **SaaS Paneli (app)**

1. **Dashboard:** Günlük doluluk oranı ve finansal özet.
2. **Kayıt Modülü:** Veli/Öğrenci ekleme işlemleri **Bootstrap Modal** üzerinden **jQuery.ajax()** ile yapılır.
3. **Takvim:** Seans dolulukları görselleştirilir.

---


WWW.OYUNEVLERI.COM (PAZARYERİ) SAYFALARI
A. Ana Sayfa & Arama (Landing Page)
UI/UX: Modern, çocuk dostu pastel tonlar. Hero section'da "Şehir seç, yaş seç, eğlenceyi bul" arama barı.

İşlemler: Şehir, ilçe ve yaş grubuna göre hızlı arama.

B. Listeleme Sayfası (Search Results)
UI/UX: Sol tarafta daraltılabilir filtreleme paneli (Bakanlık onayı, yaş grubu, fiyat aralığı). Sağ tarafta ise kart tasarımlı (Card UI) oyun evleri.

Filtreler: * Kurumsal: MEB Bağlı, Aile Sosyal Pol. Bağlı.

Hizmet: Bahçeli, Güvenlik Kamerası, İngilizce Oyun Grubu.

İşlem: "Detayı Gör" butonu ile profil sayfasına yönlendirme.

C. Oyun Evi Detay Sayfası (Storefront)
UI/UX: Üstte galeri (slider), sağda fiyat ve hızlı iletişim kutusu. Alt sekmelerde (tabs) "Hakkımızda", "Gruplarımız", "Eğitmenler", "Yorumlar".

İşlem: "Kayıt Ol / Giriş Yap" butonu ile direkt app.oyunevleri.com üzerindeki kayıt akışına yönlendirme.


-------------------------------

APP.OYUNEVLERI.COM (SAAS PANELI) SAYFALARI
A. Giriş ve Kurum Seçim Sayfası
User Flow: Kurum Kodu + K_Adi + Şifre -> Başarılı -> `kurum_id` session'a yazılır -> (Eğer Merkez Adminse) Şube Seçim Ekranı -> Dashboard.

B. Yönetici Dashboard (İstatistik)
UI/UX: Kartlar halinde (Widgets); Günlük toplam çocuk sayısı, Aylık Ciro, Bekleyen İptaller, 48 Saat Kuralına takılanlar.

İşlem: AJAX ile anlık veri filtreleme (Bugün/Bu Hafta).

C. Grup & Takvim Yönetimi
UI/UX: Haftalık takvim görünümü. Seanslara tıklanınca modal açılır.

İşlem: Yeni grup oluşturma (Ay aralığı belirleme), seans bazlı kontenjan takibi.

D. Veli & Öğrenci Yönetimi (CRM)
UI/UX: Liste görünümü, her satırda "Hak Tanımla", "Karne Gör", "Ödeme Al" aksiyonları.

Modal Kullanımı: Yeni veli/öğrenci kaydı modal üzerinden jQuery.ajax() ile yapılır.
Ek Aksiyonlar: "Hak Dondur", "Hak Dondurma Kaldir", "Süre Uzat".

E. Rol & Yetki Yönetimi (Sadece Merkez Admin)
UI/UX: Roller listesi + yetki checklist. Rol ekle/sil/guncelle.
Islem: Merkez admin rol-yetki map'lerini ve kullanici rol atamalarini düzenler.
Ek Aksiyonlar: "Hak Dondur", "Hak Dondurma Kaldir", "Süre Uzat".

E. Rol & Yetki Yönetimi (Sadece Merkez Admin)
UI/UX: Roller listesi + yetki checklist. Rol ekle/sil/guncelle.
Islem: Merkez admin rol-yetki map'lerini ve kullanici rol atamalarini düzenler.

🧠 6. USER FLOW (KRİTİK AKIŞLAR)
Akış 1: Rezervasyon Yapma (Veli)
Veli app'e giriş yapar -> "Grup Seç" ekranına gelir.

Sistem çocuğun ayını hesaplar; yaş sınırına uymayan grupları pasifize eder.

Veli uygun grubu seçer -> Modal onay ekranı açılır -> AJAX ile hak_kontrol yapılır -> Onaylanırsa bakiye düşer.

Akış 2: İptal ve 48 Saat Kuralı
Veli "Rezervasyonlarım" sayfasına girer -> "İptal Et" butonuna tıklar.

AJAX İstek: PHP, seans saatine olan farkı kontrol eder.

Karar: * Fark > iptal_kural_saat: Durum = 'iptal', iptal_onay = 0 (bekleyen onay).

Fark < iptal_kural_saat: Ekrana "Hakkınız Yanacaktır" uyarısı çıkar -> Onaylanırsa hak iade edilmeden durum = 'hak_yandi' yapılır.

Yönetici onayı: "Bekleyen iptaller" ekranında onayla/iade veya reddet aksiyonu uygulanır.


## 🧠 5. KRİTİK İŞ MANTIĞI (AJAX STANDARTLARI)

* **Kısıtlama:** Saf JavaScript yerine tamamen **jQuery** kullanılacaktır.
* **Modal Kullanımı:** Tüm ekleme ve güncelleme işlemleri modal üzerinden yapılacaktır.
* **Kurum Filtre:** Ortak DB kullanildigi icin tum SELECT/INSERT/UPDATE islemlerinde `kurum_id` zorunludur.
* **Oturum & Parola:** PHP session kullanılacak. Parola doğrulama `password_verify`, kayıt/güncelleme `password_hash` ile yapılacak.
* **48 Saat Kuralı (Dinamik):** İptal butonuna tıklandığında AJAX ile PHP'ye sorgu gider; `sistem_ayarlar` tablosundaki `iptal_kural_saat` değeri kullanılır. Varsayılan 48.
* **Hak Dondurma / Süre Uzatma:** `veli_hak_dondurma` tablosu ile takip edilir. Aktif dondurma varsa rezervasyon bloklanır. Süre uzatma `veliler.hak_gecerlilik_bitis` alanı güncellenerek yapılır.
* **Dosya Yukleme:** Sadece `materyal_havuzu` için dosya yüklenir. Yetkili roller: `egitmen`, `sube_admin`, `merkez_admin`. Yukleme dizini: `/uploads/materyaller/` (proje root). Izinli tipler: PDF, JPG, PNG.

**Örnek jQuery AJAX Kullanımı:**

```javascript
// Yeni bir ödeme (Gelir) ekleme işlemi
function gelir_ekle_ajax() {
    let veri = $('#kasa_formu').serialize();
    $.ajax({
        url: 'ajax/muhasebe_islemleri.php',
        type: 'POST',
        data: veri + '&islem=gelir_kaydet',
        success: function(yanit) {
            let json = JSON.parse(yanit);
            if(json.durum == 'ok') {
                $('#gelirModal').modal('hide');
                location.reload(); // Veya tabloyu dinamik yenile
            }
        }
    });
}

```

---

## 🎨 6. TEMA VE UI/UX

* **Tema:** Tüm sayfalar `/theme/` dizinindeki ana şablona bağlı kalmalıdır.
* **UX:** Kullanıcı bir işlem yaptığında (örn. hatalı yaş grubu seçimi) sayfa yenilenmeden kırmızı alert ile bilgilendirilmelidir.

---
APP.OYUNEVLERI.COM (SAAS PANELI) SAYFALARI
A. Giriş ve Kurum Seçim Sayfası
User Flow: Kurum Kodu + K_Adi + Şifre -> Başarılı -> `kurum_id` session'a yazılır -> (Eğer Merkez Adminse) Şube Seçim Ekranı -> Dashboard.

B. Yönetici Dashboard (İstatistik)
UI/UX: Kartlar halinde (Widgets); Günlük toplam çocuk sayısı, Aylık Ciro, Bekleyen İptaller, 48 Saat Kuralına takılanlar.

İşlem: AJAX ile anlık veri filtreleme (Bugün/Bu Hafta).

C. Grup & Takvim Yönetimi
UI/UX: Haftalık takvim görünümü. Seanslara tıklanınca modal açılır.

İşlem: Yeni grup oluşturma (Ay aralığı belirleme), seans bazlı kontenjan takibi.

D. Veli & Öğrenci Yönetimi (CRM)
UI/UX: Liste görünümü, her satırda "Hak Tanımla", "Karne Gör", "Ödeme Al" aksiyonları.

Modal Kullanımı: Yeni veli/öğrenci kaydı modal üzerinden jQuery.ajax() ile yapılır.

🧠 6. USER FLOW (KRİTİK AKIŞLAR)
Akış 1: Rezervasyon Yapma (Veli)
Veli app'e giriş yapar -> "Grup Seç" ekranına gelir.

Sistem çocuğun ayını hesaplar; yaş sınırına uymayan grupları pasifize eder.

Veli uygun grubu seçer -> Modal onay ekranı açılır -> AJAX ile hak_kontrol yapılır -> Onaylanırsa bakiye düşer.

Akış 2: İptal ve 48 Saat Kuralı
Veli "Rezervasyonlarım" sayfasına girer -> "İptal Et" butonuna tıklar.

AJAX İstek: PHP, seans saatine olan farkı kontrol eder.

Karar: * Fark > iptal_kural_saat: Bakiye iade, rezervasyon sil.

Fark < iptal_kural_saat: Ekrana "Hakkınız Yanacaktır" uyarısı çıkar -> Onaylanırsa hak iade edilmeden durum = 'hak_yandi' yapılır.



FRONTEND STANDARTLARI
Tema: Tüm sayfalar /theme/ klasöründeki template dosyalarına (header.php, footer.php, sidebar.php) bağlıdır.

jQuery & AJAX: Saf JavaScript (Vanilla) yerine jQuery tercih edilecektir.

Örnek Form Yapısı:

JavaScript

// Modal içindeki kaydet butonuna tıklandığında
$('#btn_kaydet').on('click', function() {
    var formData = $('#form_data').serialize();
    $.ajax({
        type: 'POST',
        url: 'ajax/islem_merkezi.php',
        data: formData + '&islem=tahsilat_ekle',
        success: function(response) {
            var res = JSON.parse(response);
            if(res.status == 'ok') {
                $('#myModal').modal('hide');
                $('.kasa-tablo').load(location.href + ' .kasa-tablo'); // Tabloyu yenile
            }
        }
    });
});
