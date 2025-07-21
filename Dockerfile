FROM php:8.2-fpm

# Cài các dependency cơ bản
RUN apt-get update && apt-get install -y \
    git curl zip unzip libonig-dev libxml2-dev libzip-dev \
    libpq-dev libjpeg-dev libpng-dev libfreetype6-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl zip

# Cài Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy toàn bộ source code
COPY . .

# Cài đặt Laravel
RUN composer install --no-dev --optimize-autoloader

# Laravel permissions
RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www/storage

EXPOSE 8000

CMD php artisan config:cache && php artisan serve --host=0.0.0.0 --port=8000
