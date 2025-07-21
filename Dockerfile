FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip libonig-dev libxml2-dev libzip-dev \
    libpq-dev libjpeg-dev libpng-dev libfreetype6-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy project files
COPY . .

# Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www/storage /var/www/bootstrap/cache

# Expose the port Laravel will run on
EXPOSE 8000

# Run migrations and start the Laravel app
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000
