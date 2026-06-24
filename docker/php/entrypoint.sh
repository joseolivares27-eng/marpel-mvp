#!/bin/sh
set -e

mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/testing storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

php artisan storage:link >/dev/null 2>&1 || true

if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force
    php artisan db:seed --force
fi

exec "$@"
