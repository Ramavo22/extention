FROM php:8.2.12-cli

# Installer dépendances système
RUN apt-get update && apt-get install -y \
    git unzip zip libicu-dev libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install intl pdo pdo_mysql zip

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le dossier de travail
WORKDIR /var/www/symfony

# Copier les fichiers Symfony dans le conteneur
COPY . /var/www/symfony

# Droits (selon ton OS)
RUN chown -R www-data:www-data /var/www/symfony \
    && chmod -R 755 /var/www/symfony

# Démarrer le serveur Symfony (serveur PHP intégré)
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
