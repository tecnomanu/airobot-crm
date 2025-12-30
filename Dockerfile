# =============================================================================
# Stage 1: Base PHP 8.4 with all system dependencies and extensions
# =============================================================================
FROM php:8.4-fpm-alpine AS base

ARG INSTALL_PGSQL=true

# Timezone, bash, git, and core system dependencies
RUN apk update && apk upgrade && \
    apk add --no-cache \
    bash git zip unzip curl supervisor nginx sqlite \
    icu-dev oniguruma-dev gmp-dev libzip-dev \
    libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev \
    libxml2-dev libtool openssl-dev linux-headers gettext-dev \
    autoconf g++ make zlib-dev pkgconf re2c

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp && \
    docker-php-ext-install bcmath bz2 exif ftp gd gmp intl mbstring opcache pdo \
    shmop sockets sysvmsg sysvsem sysvshm zip gettext pcntl

# PostgreSQL extension
RUN if [ "$INSTALL_PGSQL" = "true" ]; then \
    apk add --no-cache --virtual .pgsql-deps postgresql-dev libpq-dev && \
    docker-php-ext-install pdo_pgsql && \
    apk del .pgsql-deps && \
    apk add --no-cache libpq; \
    fi

# Redis extension
RUN apk add --no-cache $PHPIZE_DEPS && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    apk del $PHPIZE_DEPS

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer

# Create necessary directories and config for nginx/supervisor
RUN mkdir -p /etc/supervisor.d /run/nginx /var/www /var/log/supervisor /var/log/nginx && \
    touch /run/nginx/nginx.pid && \
    ln -sf /dev/stdout /var/log/nginx/access.log && \
    ln -sf /dev/stderr /var/log/nginx/error.log

# Copy config files
COPY ./docker-compose/php/local.ini /usr/local/etc/php/php.ini
COPY ./docker-compose/nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./docker-compose/nginx/conf.d/app.conf /etc/nginx/http.d/default.conf
COPY ./docker-compose/supervisord/supervisord.ini /etc/supervisor.d/supervisord.ini
COPY ./docker-compose/crontab /etc/crontabs/root

# Copy and configure entrypoint script
COPY ./docker-compose/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www
EXPOSE 80


# =============================================================================
# Stage 2: Build frontend assets with PNPM
# =============================================================================
FROM node:22-alpine AS assets
WORKDIR /var/www

RUN corepack enable

# Install dependencies
COPY package.json pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile

# Build assets
COPY vite.config.js ./
COPY tailwind.config.js ./
COPY postcss.config.js ./
COPY jsconfig.json ./
COPY resources/ ./resources/
COPY public/ ./public/

RUN pnpm run build


# =============================================================================
# Stage 3: Production image
# =============================================================================
FROM base AS production
WORKDIR /var/www

# Copy application code (Dokploy will inject .env at runtime)
COPY . .

# Copy built assets from assets stage
COPY --from=assets /var/www/public/build ./public/build

# Set permissions and create storage link
RUN mkdir -p /var/www/storage/logs /var/www/bootstrap/cache && \
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Create temporary .env for build (real env vars injected at runtime by Dokploy)
RUN echo "APP_NAME=\"AIRobot CRM\"" > .env && \
    echo "APP_ENV=production" >> .env && \
    echo "APP_KEY=base64:temporarykeyforbuildonlyrealkeyinjectedatruntime=" >> .env && \
    echo "APP_DEBUG=false" >> .env && \
    echo "DB_CONNECTION=pgsql" >> .env

# Install PHP dependencies with optimized autoloader
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Remove temporary .env (Dokploy will inject real one at runtime)
RUN rm -f .env

EXPOSE 80
CMD ["supervisord", "-c", "/etc/supervisor.d/supervisord.ini"]
