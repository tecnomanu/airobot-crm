# -----------------------------
# 1) Frontend build (Vite) - PNPM
# -----------------------------
FROM node:22-alpine AS assets
WORKDIR /app

RUN corepack enable

# Copiamos manifests primero para cache
COPY package.json pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile

# Copiamos el resto del código y build
COPY . .
RUN pnpm run build


# -----------------------------
# 2) Composer deps (con ext-pcntl para Horizon)
# -----------------------------
FROM php:8.4-cli-alpine AS vendor
WORKDIR /app

# Dependencias y extensiones necesarias para validar requirements en composer
RUN apk add --no-cache git unzip libzip-dev icu-dev oniguruma-dev \
    && docker-php-ext-install pcntl intl zip mbstring

# Copiamos composer desde la imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader


# -----------------------------
# 3) Runtime (php-fpm)
# -----------------------------
FROM php:8.4-fpm-alpine AS runtime

# Paquetes y extensiones típicas Laravel + Horizon
RUN apk add --no-cache \
    bash curl icu-dev oniguruma-dev libzip-dev zip unzip git postgresql-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring intl zip pcntl opcache

# Redis extension (pecl)
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

WORKDIR /var/www

# App code
COPY . /var/www

# Vendor + assets build
COPY --from=vendor /app/vendor /var/www/vendor
COPY --from=assets /app/public/build /var/www/public/build

# Permisos Laravel
RUN mkdir -p /var/www/storage /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R ug+rwX /var/www/storage /var/www/bootstrap/cache

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
