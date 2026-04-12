FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
        icu-libs \
        libpq \
        libzip \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        postgresql-dev \
    && docker-php-ext-install \
        intl \
        opcache \
        pdo_pgsql \
        zip \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY docker/php/php.ini "$PHP_INI_DIR/conf.d/app.ini"

# ---- Dependencies ----
FROM base AS deps

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-progress

# ---- Production ----
FROM base AS prod

ENV APP_ENV=prod

COPY . .
COPY --from=deps /app/vendor vendor/

RUN composer dump-autoload --classmap-authoritative \
    && php bin/console cache:clear --env=prod \
    && chown -R www-data:www-data var/

USER www-data

EXPOSE 9000
