# Use the official PHP image with Apache
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy the project files
WORKDIR /var/www/html
COPY . .

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-dev

# Set permissions for Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Enable Apache rewrite module
RUN a2enmod rewrite
