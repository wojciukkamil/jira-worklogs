FROM php:8.5-fpm-alpine

# Instalacja zależności systemowych i rozszerzeń PHP (wymaganych przez Symfony)
RUN apk update && apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip pdo_mysql

# Instalacja Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY ./app/composer.json ./app/composer.lock ./

RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist;

WORKDIR /var/www/html

# Kopiowanie plików aplikacji i instalacja zależności
COPY . .
# RUN composer install --no-scripts --optimize-autoloader
COPY docker/php/php.ini /usr/local/etc/php/

EXPOSE 9000
CMD ["php-fpm"]