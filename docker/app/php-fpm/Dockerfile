FROM php:8.2-fpm-alpine

# Rozszerzenia potrzebne appce: PDO PostgreSQL + mbstring (uzywane tez przez ovh-ocr).
RUN apk add --no-cache postgresql-dev oniguruma-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring

# Composer (do composer install w kontenerze / buildzie prod).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
