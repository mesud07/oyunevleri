FROM composer:2.8 AS composer-deps

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        unzip \
        git \
        libcurl4-openssl-dev \
        libonig-dev \
        libzip-dev \
        default-mysql-client \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mysqli \
        curl \
        mbstring \
        zip \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php-production.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY docker/entrypoint.sh /usr/local/bin/talya-entrypoint
COPY . /var/www/html
COPY --from=composer-deps /app/vendor /var/www/html/vendor

RUN chmod +x /usr/local/bin/talya-entrypoint \
    && mkdir -p /var/www/html/storage/sessions /var/www/html/storage/logs /var/www/html/storage/cache /var/www/html/storage/faturalar \
    && chown -R root:root /var/www/html \
    && chown -R www-data:www-data /var/www/html/storage \
    && find /var/www/html -path /var/www/html/storage -prune -o -type d -exec chmod 0755 {} + \
    && find /var/www/html -path /var/www/html/storage -prune -o -type f -exec chmod 0644 {} + \
    && chmod 0770 /var/www/html/storage /var/www/html/storage/sessions /var/www/html/storage/logs /var/www/html/storage/cache /var/www/html/storage/faturalar

WORKDIR /var/www/html

ENTRYPOINT ["talya-entrypoint"]
CMD ["apache2-foreground"]
