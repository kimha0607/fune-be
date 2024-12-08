FROM php:8.2-fpm-alpine

# Set working directory
ARG workdir=/var/www
WORKDIR $workdir

# Install system dependencies
RUN apk add --no-cache \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    freetype-dev \
    libzip-dev \
    zip \
    bash \
    dos2unix \
    oniguruma-dev \
    curl-dev \
    tzdata

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mysqli exif zip

# Copy application code
COPY . .

# Set correct permissions for storage and cache folders
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Get latest Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Install dependencies using Composer
RUN composer install --optimize-autoloader --no-dev

# Generate Laravel application key
RUN php artisan key:generate

# Set default command
CMD ["php-fpm"]
