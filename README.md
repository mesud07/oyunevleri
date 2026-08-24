# Oyun Evleri Yönetim Sistemi

Bagimsiz PHP 8.2 + MySQL 8.0 tabanli yonetim paneli.

## Yerel Calistirma

```bash
podman compose up -d --build
```

Adresler:

- Uygulama: http://localhost:8080
- Giris: http://localhost:8080/giris
- Panel: http://localhost:8080/panel
- phpMyAdmin: http://localhost:8081

Ilk kullanici:

- E-posta: `kurucu@talyakids.local`
- Sifre: `Talya2026!`

## Dogrulama

```bash
find app config public resources cron -type f -name '*.php' | sort | xargs -n1 php -l
podman exec talya_app php /var/www/html/cron/otomatik-gelmedi.php
podman exec talya_app php /var/www/html/cron/otomatik-telafi.php
podman exec talya_app php /var/www/html/cron/geciken-odemeler.php
```

## Not

`public/` disindaki uygulama dosyalari Apache tarafindan dogrudan yayinlanmaz. Ortam ayarlari `.env` uzerinden okunur.
# talyakids
