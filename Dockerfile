FROM node:24-alpine AS assets
WORKDIR /app
COPY package.json vite.config.js ./
COPY resources ./resources
RUN npm install
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json ./
RUN apk add --no-cache icu-dev && docker-php-ext-install intl
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

FROM php:8.4-fpm-alpine AS app

RUN apk add --no-cache \
    bash \
    curl \
    freetype-dev \
    icu-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    nginx \
    oniguruma-dev \
    postgresql-dev \
    supervisor \
    unzip \
    zip \
    && docker-php-ext-configure gd --with-jpeg --with-freetype --with-webp \
    && docker-php-ext-install bcmath exif gd intl mbstring opcache pcntl pdo_pgsql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
COPY docker/php/php.ini /usr/local/etc/php/conf.d/marpel.ini
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/marpel.conf /etc/supervisor/conf.d/marpel.conf

RUN chmod +x docker/php/entrypoint.sh \
    && composer dump-autoload --optimize \
    && php artisan filament:assets \
    && mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/testing storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

ENTRYPOINT ["docker/php/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/marpel.conf"]
