FROM php:8.2.12-cli

RUN apt-get update && apt-get install -y \
    git unzip zip libicu-dev libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install intl pdo pdo_mysql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/symfony

COPY . /var/www/symfony

RUN chown -R www-data:www-data /var/www/symfony \
    && chmod -R 755 /var/www/symfony

# Forcer le mode dev
ENV APP_ENV=dev

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
