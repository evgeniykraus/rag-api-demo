# syntax=docker/dockerfile:1

FROM php:8.4-fpm-alpine AS base

# System deps
RUN set -ex \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS postgresql-dev \
    && apk add --no-cache postgresql-libs git bash curl unzip \
    && docker-php-ext-install pdo_mysql pdo_pgsql pgsql \
    && apk del .build-deps

# Opcache recommended settings
RUN docker-php-ext-enable opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy app sources (can be overridden by volume mounts in compose)
COPY . /var/www/html

# Install PHP deps (prod by default; use --no-dev, can override at runtime)
RUN set -ex \
    && composer install --no-interaction --prefer-dist --optimize-autoloader || true

EXPOSE 9000
CMD ["php-fpm"]


