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
COPY docker/entrypoint.sh /usr/local/bin/talya-entrypoint

RUN chmod +x /usr/local/bin/talya-entrypoint

WORKDIR /var/www/html

ENTRYPOINT ["talya-entrypoint"]
CMD ["apache2-foreground"]
