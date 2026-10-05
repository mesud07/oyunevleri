# app.oyunevleri.com Tasima Notlari

Bu dosya mevcut lokal kurulumu `app.oyunevleri.com` alan adinda calisir hale getirmek icin uygulanacak kisa kontrol listesidir.

## Zorunlu ortam ayarlari

Canli ortamda uygulama linkleri ve SMS katilim linkleri icin:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.oyunevleri.com
SMS_ENABLED=true
SMS_TEST_MODE=false
SMS_FORCE_TO=TEST_TELEFON
```

`SMS_FORCE_TO` dolu oldugu surece tum SMS'ler NetGSM'e gercek olarak gider, fakat alici numarasi gecici olarak bu test numarasi olur. Canli velilere SMS gondermeye hazir oldugunda bu alani bosalt:

```env
SMS_FORCE_TO=
```

Canli sunucuya tasimadan once varsayilan veritabani sifrelerini degistir:

```env
MYSQL_ROOT_PASSWORD=guclu-root-sifresi
DB_DATABASE=talya_db
DB_USERNAME=talya_user
DB_PASSWORD=guclu-uygulama-sifresi
```

## Domain ve SSL

1. `app.oyunevleri.com` DNS kaydini sunucu IP adresine yonlendir.
2. Sunucuda HTTPS sertifikasi kur. Cloudflare, Nginx Proxy Manager, Caddy veya nginx + certbot kullanilabilir.
3. Reverse proxy varsa hedef port mevcut compose ayarina gore `http://127.0.0.1:8080` olmalidir.
4. Uygulama production modunda session cookie'lerini `secure` olarak isaretler; bu nedenle canli domain HTTPS olmadan giris stabil calismaz.

Nginx kullaniyorsan temel reverse proxy hedefi:

```nginx
server {
    listen 80;
    server_name app.oyunevleri.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name app.oyunevleri.com;

    ssl_certificate /etc/letsencrypt/live/app.oyunevleri.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/app.oyunevleri.com/privkey.pem;

    client_max_body_size 32m;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
    }
}
```

## Container

Bu proje lokal gelistirme icin Podman ile calisir. Paylasimli hostingte disaridan SSH olmadigi icin GitHub Actions sunucuda `podman compose` calistiramaz.

VPS veya SSH erisimi olan bir sunucuda platform bagimsiz dosyayi kullan:

```bash
podman compose -f compose.production.yaml up -d --build
```

Mevcut lokal `compose.yaml` Podman Desktop/Mac icin hazirlanmistir. VPS canli sunucuda `compose.production.yaml` kullanmak daha dogru olur.

Lokal container'i yeni domain ayarlariyla tekrar baslatmak istersen:

```bash
podman compose up -d --force-recreate app
```

## Dosyalari tasima

Sunucuda hedef klasor ornegi:

```bash
mkdir -p /opt/oyunevleri
```

Lokal makineden sunucuya proje dosyalarini aktar:

```bash
rsync -av --delete \
  --exclude='.git' \
  --exclude='talya_db.sql' \
  ./ kullanici@sunucu-ip:/opt/oyunevleri/
```

VPS sunucuda:

```bash
cd /opt/oyunevleri
podman compose -f compose.production.yaml up -d --build
```

## GitHub Actions ile otomatik deploy

Paylasimli hostingte SSH kapali oldugu icin otomatik deploy FTP/FTPS ile yapilir. Repo `main` branch'e push aldiginda `.github/workflows/deploy-production.yml` calisir. Bu workflow:

1. Kodu GitHub runner'a alir.
2. `.env` dosyasinin repoya yanlislikla commit edilmedigini kontrol eder.
3. Dosyalari FTPS uzerinden hosting hesabina senkronize eder.

GitHub repo ayarlarinda `Settings > Secrets and variables > Actions` altina su secret'lari ekle:

```text
FTP_PASSWORD=FTP_SIFRESI
```

GitHub `Variables` altina su degerleri ekleyebilirsin. Eklenmezse workflow varsayilanlari kullanir:

```text
FTP_SERVER=ftp.oyunevleri.com
FTP_PORT=21
FTP_USERNAME=FTP_KULLANICI_ADI
FTP_SERVER_DIR=/oyunevleri/
```

`FTP_SERVER_DIR` FTP kullanicisinin kok dizinine gore hesaplanir. cPanel'de giris dizini `/home/KULLANICI` ise `/oyunevleri/` sunucuda `/home/KULLANICI/oyunevleri` anlamina gelir.

Workflow `.env` dosyasini repodan tasimaz. Hosting uzerinde `/home/KULLANICI/oyunevleri/.env` dosyasi manuel bulunmali.

### cPanel document root

`app.oyunevleri.com` domain veya subdomain document root degeri su klasore bakmali:

```bash
/home/KULLANICI/oyunevleri/public
```

Document root `/home/KULLANICI/oyunevleri` olursa `app/`, `config/`, `.env` gibi dosyalar webden gorunebilir. Bu nedenle document root mutlaka `public` klasoru olmali.

### Hosting uzerinde .env

cPanel Terminal veya File Manager ile `/home/KULLANICI/oyunevleri/.env` dosyasi olustur:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.oyunevleri.com
APP_TIMEZONE=Europe/Istanbul

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=CPANEL_MYSQL_DATABASE
DB_USERNAME=CPANEL_MYSQL_USER
DB_PASSWORD=CPANEL_MYSQL_PASSWORD

SESSION_NAME=talya_kids_session
SESSION_LIFETIME=2592000
CSRF_TOKEN_NAME=talya_csrf

SMS_PROVIDER=netgsm
SMS_ENABLED=true
SMS_TEST_MODE=false
SMS_TEST_PHONE=
SMS_FORCE_TO=TEST_TELEFON

NETGSM_USERCODE=8503465468
NETGSM_PASSWORD=NETGSM_SIFRESI
NETGSM_HEADER=TALYAOYUNEV
NETGSM_ENCODING=TR
NETGSM_FILTER=0
NETGSM_API_BASE_URL=https://api.netgsm.com.tr
NETGSM_CONNECT_TIMEOUT=10
NETGSM_TIMEOUT=30

SMS_MAX_RECIPIENTS_PER_REQUEST=1000
SMS_MAX_RETRY_COUNT=3
SMS_RETRY_DELAY_MINUTES=10
SMS_APPOINTMENT_REMINDER_ENABLED=true
SMS_APPOINTMENT_REMINDER_HOURS=24
SMS_PAYMENT_PROMISE_REMINDER_ENABLED=true
SMS_PAYMENT_PROMISE_REMINDER_HOURS=24

BACKUP_ENCRYPTION_KEY=EN_AZ_32_KARAKTER_RASTGELE_YEDEK_ANAHTARI
BACKUP_DIR=/home/CPANEL_KULLANICISI/backups/oyunevleri
BACKUP_RETENTION_DAYS=30
```

GitHub'da bos ve private bir repo olusturduktan sonra lokal projeyi bagla:

```bash
git remote add origin git@github.com:KULLANICI_ADI/oyunevleri.git
git push -u origin main
```

HTTPS remote kullanacaksan:

```bash
git remote add origin https://github.com/KULLANICI_ADI/oyunevleri.git
git push -u origin main
```

Bu ilk push sonrasinda GitHub Actions otomatik deploy'u baslatir. `FTP_PASSWORD` secret'i eklenmeden workflow FTP dogrulama adiminda durur.

## Veritabani tasima

Lokal veritabani yedegi al:

```bash
podman exec talya_db mysqldump -u root -p talya_db > talya_db.sql
```

Komut sifre sorarsa lokal root sifresini gir. Yedegi sunucuya aktar:

```bash
scp talya_db.sql kullanici@sunucu-ip:/opt/oyunevleri/talya_db.sql
```

Sunucuda veriyi geri yukle:

```bash
cd /opt/oyunevleri
podman exec -i talya_db mysql -u root -p talya_db < talya_db.sql
```

Geri yukleme bittikten sonra yedek dosyasini sunucuda saklaman gerekmiyorsa sil:

```bash
rm talya_db.sql
```

## Cron

SMS ve otomasyonlar icin sunucuda cron calistir:

```cron
* * * * * podman exec talya_app php /var/www/html/cron/sms-otomasyon.php 100
*/15 * * * * podman exec talya_app php /var/www/html/cron/otomatik-gelmedi.php
*/30 * * * * podman exec talya_app php /var/www/html/cron/otomatik-telafi.php
0 9 * * * podman exec talya_app php /var/www/html/cron/geciken-odemeler.php
```

`compose.production.yaml` ile kurulumda `scheduler` servisi bu SMS komutunu her dakika
kendiliginden calistirir. Paylasimli hostingde ise proje yolunuza gore su tek cron yeterlidir:

```cron
* * * * * cd /sunucudaki/proje/yolu && /usr/local/bin/php cron/sms-otomasyon.php 100 >> storage/logs/cron-sms-otomasyon.log 2>&1
```

Cron gecici olarak calisamazsa `SMS_WEB_AUTOMATION_ENABLED=true` ayari, gercek web
trafiginde tum aktif kurumlari en fazla 10 dakikada bir isleyen yedek mekanizmayi etkin tutar.

## Günlük şifreli yedekleme

Yedek anahtarını sunucuda üretip `.env` içindeki `BACKUP_ENCRYPTION_KEY` alanına yaz:

```bash
openssl rand -base64 48
```

Anahtarın bir kopyasını sunucu dışında, güvenli bir parola kasasında sakla. Anahtar
kaybolursa şifreli yedekler geri açılamaz. `BACKUP_DIR` web kökünün ve proje klasörünün
dışında mutlak bir dizin olmalıdır.

cPanel Cron Jobs ekranında her gece 03:15 için şu komutu tanımla:

```cron
15 3 * * * /bin/sh /home/CPANEL_KULLANICISI/oyunevleri/bin/daily-backup.sh >> /home/CPANEL_KULLANICISI/oyunevleri/storage/logs/cron-daily-backup.log 2>&1
```

Komut veritabanını, `storage/faturalar` arşivini ve `public/uploads` altındaki kurum
dosyalarını tek arşivde toplar; AES-256 ile şifreler, SHA-256 bütünlük dosyası üretir,
oluşan yedeği otomatik doğrular ve varsayılan olarak 30 günden eski yedekleri temizler.

İlk kurulumu beklemeden sınamak için:

```bash
cd /home/CPANEL_KULLANICISI/oyunevleri
/bin/sh bin/daily-backup.sh
```

Son yedeği ayrıca doğrulamak için:

```bash
set -a; . ./.env; set +a
/bin/sh bin/verify-encrypted-backup.sh /home/CPANEL_KULLANICISI/backups/oyunevleri/talya-YYYYMMDD-HHMMSS.tar.enc
```

`compose.production.yaml` kullanılan VPS kurulumunda scheduler servisi aynı günlük
yedeklemeyi otomatik çalıştırır ve şifreli dosyaları `talya_backups` volume'ünde saklar.

## Kontrol

```bash
podman exec talya_app php -r 'require "bootstrap.php"; echo App\Core\Config::get("APP_URL") . PHP_EOL;'
podman exec talya_app php -r '$c=require "/var/www/html/config/sms.php"; echo json_encode([$c["enabled"], $c["test_mode"], $c["force_to"]]) . PHP_EOL;'
```

Beklenen sonuc:

```text
https://app.oyunevleri.com
[true,false,"TEST_TELEFON"]
```

`SMS_FORCE_TO` bosaltilinca ikinci satirdaki son deger `""` olur ve SMS'ler gercek veli numaralarina gitmeye baslar.
