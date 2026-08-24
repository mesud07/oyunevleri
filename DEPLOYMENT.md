# app.talyakids.com Tasima Notlari

Bu dosya mevcut lokal kurulumu `app.talyakids.com` alan adinda calisir hale getirmek icin uygulanacak kisa kontrol listesidir.

## Zorunlu ortam ayarlari

Canli ortamda uygulama linkleri ve SMS katilim linkleri icin:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.talyakids.com
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

1. `app.talyakids.com` DNS kaydini sunucu IP adresine yonlendir.
2. Sunucuda HTTPS sertifikasi kur. Cloudflare, Nginx Proxy Manager, Caddy veya nginx + certbot kullanilabilir.
3. Reverse proxy varsa hedef port mevcut compose ayarina gore `http://127.0.0.1:8080` olmalidir.
4. Uygulama production modunda session cookie'lerini `secure` olarak isaretler; bu nedenle canli domain HTTPS olmadan giris stabil calismaz.

Nginx kullaniyorsan temel reverse proxy hedefi:

```nginx
server {
    listen 80;
    server_name app.talyakids.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name app.talyakids.com;

    ssl_certificate /etc/letsencrypt/live/app.talyakids.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/app.talyakids.com/privkey.pem;

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
mkdir -p /opt/app-talya
```

Lokal makineden sunucuya proje dosyalarini aktar:

```bash
rsync -av --delete \
  --exclude='.git' \
  --exclude='talya_db.sql' \
  ./ kullanici@sunucu-ip:/opt/app-talya/
```

VPS sunucuda:

```bash
cd /opt/app-talya
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
FTP_SERVER=ftp.talyakids.com
FTP_PORT=21
FTP_USERNAME=talyakidsmesud@app.talyakids.com
FTP_SERVER_DIR=/app-talya/
```

`FTP_SERVER_DIR` FTP kullanicisinin kok dizinine gore hesaplanir. cPanel'de giris dizini `/home/talyakid` ise `/app-talya/` sunucuda `/home/talyakid/app-talya` anlamina gelir.

Workflow `.env` dosyasini repodan tasimaz. Hosting uzerinde `/home/talyakid/app-talya/.env` dosyasi manuel bulunmali.

### cPanel document root

`app.talyakids.com` domain veya subdomain document root degeri su klasore bakmali:

```bash
/home/talyakid/app-talya/public
```

Document root `/home/talyakid/app-talya` olursa `app/`, `config/`, `.env` gibi dosyalar webden gorunebilir. Bu nedenle document root mutlaka `public` klasoru olmali.

### Hosting uzerinde .env

cPanel Terminal veya File Manager ile `/home/talyakid/app-talya/.env` dosyasi olustur:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.talyakids.com
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
```

GitHub'da bos ve private bir repo olusturduktan sonra lokal projeyi bagla:

```bash
git remote add origin git@github.com:KULLANICI_ADI/app-talya.git
git push -u origin main
```

HTTPS remote kullanacaksan:

```bash
git remote add origin https://github.com/KULLANICI_ADI/app-talya.git
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
scp talya_db.sql kullanici@sunucu-ip:/opt/app-talya/talya_db.sql
```

Sunucuda veriyi geri yukle:

```bash
cd /opt/app-talya
podman exec -i talya_db mysql -u root -p talya_db < talya_db.sql
```

Geri yukleme bittikten sonra yedek dosyasini sunucuda saklaman gerekmiyorsa sil:

```bash
rm talya_db.sql
```

## Cron

SMS ve otomasyonlar icin sunucuda cron calistir:

```cron
* * * * * podman exec talya_app php /var/www/html/cron/sms-kuyrugu.php 50
*/10 * * * * podman exec talya_app php /var/www/html/cron/randevu-sms-hatirlatma.php
*/15 * * * * podman exec talya_app php /var/www/html/cron/sms-durum-sorgula.php
*/15 * * * * podman exec talya_app php /var/www/html/cron/otomatik-gelmedi.php
*/30 * * * * podman exec talya_app php /var/www/html/cron/otomatik-telafi.php
0 9 * * * podman exec talya_app php /var/www/html/cron/geciken-odemeler.php
```

## Kontrol

```bash
podman exec talya_app php -r 'require "bootstrap.php"; echo App\Core\Config::get("APP_URL") . PHP_EOL;'
podman exec talya_app php -r '$c=require "/var/www/html/config/sms.php"; echo json_encode([$c["enabled"], $c["test_mode"], $c["force_to"]]) . PHP_EOL;'
```

Beklenen sonuc:

```text
https://app.talyakids.com
[true,false,"TEST_TELEFON"]
```

`SMS_FORCE_TO` bosaltilinca ikinci satirdaki son deger `""` olur ve SMS'ler gercek veli numaralarina gitmeye baslar.
