# -----------------------------
# 1) Frontend build (Vite) - PNPM
# -----------------------------
FROM node:22-alpine AS assets
WORKDIR /app
RUN corepack enable

# Cacheamos instalación de dependencias de frontend
COPY package.json pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile

# Construimos los assets
COPY . .
RUN pnpm run build


# -----------------------------
# 2) PHP Base (System Deps + Exts + Composer)
# -----------------------------
# Esta etapa contiene TODAS las dependencias del sistema y extensiones.
# Se cacheará y no se volverá a ejecutar a menos que cambies esta definición.
FROM php:8.4-fpm-alpine AS php_base

WORKDIR /var/www

# Instalamos paquetes del sistema, extensiones PHP y Redis en una sola capa
# Incluimos postgresql-dev y git/unzip para composer
RUN apk add --no-cache \
    bash curl icu-dev oniguruma-dev libzip-dev zip unzip git postgresql-dev $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring intl zip pcntl opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# Copiamos Composer globalmente disponible
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


# -----------------------------
# 3) Composer Deps
# -----------------------------
# Usamos la base (que ya tiene git/unzip/php) para instalar dependencias de backend
FROM php_base AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
# --no-scripts evita errores de artisan si no está copiado el código aún
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts


# -----------------------------
# 4) Final Runtime
# -----------------------------
# Extendemos de php_base. Esta es la imagen final.
FROM php_base AS runtime

# Copiamos el código de la aplicación (esto es lo que más cambia)
COPY . /var/www

# Copiamos dependencias de backend desde la etapa vendor
COPY --from=vendor /app/vendor /var/www/vendor

# Copiamos assets compilados desde la etapa assets
COPY --from=assets /app/public/build /var/www/public/build

# Permisos de carpetas de escritura
RUN mkdir -p /var/www/storage /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R ug+rwX /var/www/storage /var/www/bootstrap/cache

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
