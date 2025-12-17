FROM php:8.1-apache

# Installation des extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Activation du module rewrite d'Apache
RUN a2enmod rewrite

# Copie des fichiers de l'application
COPY . /var/www/html/

# Configuration Apache
COPY .docker/apache-config.conf /etc/apache2/sites-available/000-default.conf

# Définition des permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]