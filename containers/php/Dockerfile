FROM php:8.4-fpm-alpine AS base

RUN docker-php-ext-install pdo pdo_mysql

# Imagick for image processing (uploads → thumbnails + per-section variants).
# Runtime: just `imagemagick`. Build-time deps go into a virtual package so
# we can `apk del` them and not bloat the image.
RUN apk add --no-cache imagemagick imagemagick-libs imagemagick-webp libwebp libwebp-tools \
    && apk add --no-cache --virtual .build-deps autoconf g++ make pkgconfig imagemagick-dev libwebp-dev \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && apk del .build-deps

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

COPY containers/php/php.ini /usr/local/etc/php/conf.d/the-saiged.ini

WORKDIR /var/www/the-saiged

FROM base AS prod

COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --no-progress

COPY public               public
COPY src                  src
COPY migrations           migrations
COPY scripts/migrate.php  scripts/migrate.php

COPY containers/php/prod.ini      /usr/local/etc/php/conf.d/the-saiged-prod.ini
COPY containers/php/entrypoint.sh /entrypoint.sh
RUN  chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
CMD  ["php-fpm"]
