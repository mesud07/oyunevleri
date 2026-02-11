FROM --platform=linux/amd64 php:7.1-apache

WORKDIR /var/www/html

# 1. Paketleri Yükle (Arşiv modunda)
# DİKKAT: Zip eklentisi için 'libzip-dev' kütüphanesi buraya eklendi.
RUN echo "deb http://archive.debian.org/debian/ buster main" > /etc/apt/sources.list \
 && echo "deb http://archive.debian.org/debian-security buster/updates main" >> /etc/apt/sources.list \
 && apt-get -o Acquire::Check-Valid-Until=false update \
 && apt-get -o Acquire::Check-Valid-Until=false install -y git zip unzip libzip-dev

# 2. Uzantıları Yükle
# DİKKAT: 'zip' eklentisi buraya eklendi.
RUN docker-php-ext-install mysqli pdo pdo_mysql zip && docker-php-ext-enable mysqli

# 3. Apache Modüllerini Aç
RUN a2enmod rewrite

# 4. .htaccess İZNİ (GARANTİ YÖNTEM)
# Apache konfigürasyonuna yeni bir ayar dosyası ekliyoruz.
RUN echo "<Directory /var/www/html>" > /etc/apache2/conf-available/docker-allow-override.conf \
 && echo "    AllowOverride All" >> /etc/apache2/conf-available/docker-allow-override.conf \
 && echo "</Directory>" >> /etc/apache2/conf-available/docker-allow-override.conf \
 && a2enconf docker-allow-override

# 5. PHP Ayarları
RUN echo "display_errors = On" >> /usr/local/etc/php/conf.d/docker-php-errors.ini
RUN echo "short_open_tag = On" >> /usr/local/etc/php/conf.d/docker-php-short-tags.ini

COPY . /var/www/html

EXPOSE 80