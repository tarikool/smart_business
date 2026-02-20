# Use PHP 8.3 FPM Alpine as base
FROM php:8.3-fpm-alpine

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and PostgreSQL + PostGIS support
RUN apk add --no-cache \
    bash \
    git \
    unzip \
    curl \
    postgresql-dev \
    libpq \
    postgis \
    oniguruma-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && docker-php-ext-install mbstring \
    && docker-php-ext-install bcmath \
    && docker-php-ext-install pcntl

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application code
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Create storage symlink
RUN php artisan storage:link || true

# Expose port 10000 for Render or custom port
EXPOSE 10000

# Set the CMD to serve Laravel
CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]
