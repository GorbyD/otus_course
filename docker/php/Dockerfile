FROM composer:2 AS composer

FROM php:8.5-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libmemcached-dev \
    libz-dev \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_pgsql

RUN pecl install redis && docker-php-ext-enable redis

RUN pecl install memcached && docker-php-ext-enable memcached

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY www.conf /usr/local/etc/php-fpm.d/www.conf
