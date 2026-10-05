#!/bin/sh
set -e
umask 0027

mkdir -p /var/www/html/storage/sessions \
    /var/www/html/storage/logs \
    /var/www/html/storage/cache \
    /var/www/html/storage/faturalar

chown -R www-data:www-data /var/www/html/storage 2>/dev/null || true
chmod 0770 /var/www/html/storage \
    /var/www/html/storage/sessions \
    /var/www/html/storage/logs \
    /var/www/html/storage/cache 2>/dev/null || true

chown -R www-data:www-data /var/www/html/storage/faturalar 2>/dev/null || true
chmod 2770 /var/www/html/storage/faturalar 2>/dev/null || true
find /var/www/html/storage/faturalar -type d -exec chmod 2770 {} + 2>/dev/null || true
find /var/www/html/storage/faturalar -type f -exec chmod 0660 {} + 2>/dev/null || true

find /var/www/html/storage/sessions -type f -exec chmod 0600 {} + 2>/dev/null || true
find /var/www/html/storage/logs -type f -exec chmod 0640 {} + 2>/dev/null || true
find /var/www/html/storage/cache -type f -exec chmod 0640 {} + 2>/dev/null || true

exec "$@"
