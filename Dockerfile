# ── Stage 1: build ────────────────────────────────────────────────────────────
FROM php:8.4-cli-alpine AS builder

RUN apk add --no-cache \
    git unzip curl gcompat \
    sqlite-dev libzip-dev libxml2-dev oniguruma-dev icu-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install \
    pdo pdo_sqlite \
    zip mbstring xml xmlwriter xmlreader simplexml \
    intl gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./
RUN APP_ENV=prod composer install \
    --no-dev \
    --no-scripts \
    --optimize-autoloader \
    --no-interaction

COPY . .

# Install bundle assets and vendor JS, then build Tailwind and compile asset map
RUN APP_ENV=prod php bin/console assets:install public --no-debug
RUN APP_ENV=prod php bin/console importmap:install --no-debug
RUN APP_ENV=prod php bin/console tailwind:build --minify --no-debug
RUN APP_ENV=prod php bin/console asset-map:compile --no-debug

# ── Stage 2: PHP-FPM runtime ──────────────────────────────────────────────────
FROM php:8.4-fpm-alpine AS runtime

RUN apk add --no-cache \
    sqlite-dev libzip-dev libxml2-dev oniguruma-dev icu-dev su-exec \
    libpng-dev libjpeg-turbo-dev freetype-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install \
    pdo pdo_sqlite opcache \
    zip mbstring xml xmlwriter xmlreader simplexml \
    intl gd

COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html

COPY --chown=www-data:www-data --from=builder /app .

COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]

# ── Stage 3: nginx with baked-in static assets ────────────────────────────────
FROM nginx:alpine AS web

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=builder /app/public /var/www/html/public
