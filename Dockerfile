# Node.js build stage
FROM node:23 AS node-builder

WORKDIR /app

# Copy package files
COPY package*.json ./
RUN npm install

# Copy config files
COPY vite.config.js ./
COPY postcss.config.js ./
COPY tailwind.config.js ./

# Copy source files
COPY resources ./resources
COPY public ./public

# Build assets
RUN npm run build

# PHP Stage
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    && docker-php-ext-install pdo pdo_mysql bcmath \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN a2enmod rewrite
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# Copy application files
COPY . .

# Copy built Vite assets from node-builder
COPY --from=node-builder /app/public/build ./public/build

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-dev

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Create .env if missing
RUN cp .env.example .env || true

# Generate application key
RUN php artisan key:generate --force

# Expose port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]