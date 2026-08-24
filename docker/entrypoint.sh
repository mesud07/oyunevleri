#!/bin/sh
set -e

mkdir -p /var/www/html/storage/sessions \
    /var/www/html/storage/logs \
    /var/www/html/storage/cache

chmod 0777 /var/www/html/storage \
    /var/www/html/storage/sessions \
    /var/www/html/storage/logs \
    /var/www/html/storage/cache 2>/dev/null || true

chmod 0666 /var/www/html/storage/sessions/sess_* 2>/dev/null || true

exec "$@"
