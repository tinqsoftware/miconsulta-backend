#!/usr/bin/env sh
set -eu

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
