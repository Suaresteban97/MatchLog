# --- Etapa 1: Composer (Build Stage) ---
FROM composer:2.7 as vendor
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install \
    --ignore-platform-reqs \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist

# --- Etapa 2: Imagen Final PHP 8.2 ---
FROM php:8.2-fpm-alpine

# Argumentos para permisos en WSL2
ARG user=www-data
ARG uid=1000

# 1. Instalamos dependencias del sistema
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    libzip-dev \
    icu-dev \
    shadow

# 2. Ajustamos el usuario www-data
RUN usermod -u ${uid} ${user} && groupmod -g ${uid} ${user}

# 3. Instalamos extensiones de PHP (AQUÍ AGREGAMOS opcache)
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache

# 4. Instalamos Redis mediante PECL
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Traemos configuraciones y herramientas
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

# (AQUÍ COPIAMOS LA CONFIGURACIÓN DE OPCACHE)
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiamos dependencias de la Etapa 1
COPY --from=vendor /var/www/html/vendor ./vendor

# Copiamos el proyecto y asignamos dueño
COPY --chown=${user}:${user} . .

# Permisos para Laravel
RUN chmod -R 775 storage bootstrap/cache

# Script de inicio
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]