# syntax=docker/dockerfile:1.6
FROM php:8.3-fpm-alpine

ARG USER_ID=1000
ARG GROUP_ID=1000

RUN apk add --no-cache \
        git \
        curl \
        libzip \
        zip \
        unzip \
        oniguruma-dev \
        libxml2-dev \
        icu-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        mariadb-connector-c-dev

RUN docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" intl pdo pdo_mysql opcache

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

RUN addgroup -g ${GROUP_ID} app \
    && adduser -D -G app -u ${USER_ID} app

WORKDIR /var/www/html

COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

USER app

CMD ["php-fpm"]
