FROM composer:2 AS composer


# ============================================================
# COMMON
# ============================================================

FROM php:8.5-fpm AS base

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libmemcached-dev \
    libz-dev \
    libssl-dev \
    libzip-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_pgsql zip sockets

RUN pecl install redis \
    && docker-php-ext-enable redis

RUN pecl install memcached \
    && docker-php-ext-enable memcached

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY www.conf /usr/local/etc/php-fpm.d/www.conf

# ============================================================
# DEVELOPMENT
# ============================================================

FROM base AS dev

# SSH
RUN apt-get update \
    && apt-get install -y --no-install-recommends openssh-server \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /run/sshd \
    && echo 'root:dev' | chpasswd \
    && printf '%s\n' \
        'PermitRootLogin yes' \
        'PasswordAuthentication yes' \
        'PubkeyAuthentication yes' \
        > /etc/ssh/sshd_config.d/99-dev.conf

# Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN printf '%s\n' \
    'xdebug.mode=debug,develop' \
    'xdebug.start_with_request=trigger' \
    'xdebug.client_port=9003' \
    > /usr/local/etc/php/conf.d/99-xdebug.ini


# ============================================================
# PRODUCTION
# ============================================================

FROM base AS prod

WORKDIR /var/www/html

# Код приложения в "app"
COPY --from=app composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY --from=app src/ ./src/
COPY --from=app public/ ./public/
COPY --from=app bin/ ./bin/
RUN composer dump-autoload --no-dev --optimize

# Dev-зависимости и тесты остаются вне production-образа.
FROM prod AS test
RUN composer install --no-interaction --prefer-dist
COPY --from=app tests/ ./tests/
COPY --from=app phpunit.xml ./phpunit.xml
CMD ["vendor/bin/phpunit"]

# Сборка без --target по-прежнему даёт production с php-fpm.
FROM prod AS production
