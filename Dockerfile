FROM php:8.2-fpm-alpine

# Set working directory
ARG workdir=/app

WORKDIR $workdir

# Install system dependencies
RUN apk update
RUN apk add --no-cache \
  libjpeg-turbo-dev \
  libpng-dev \
  libwebp-dev \
  freetype-dev \
  libzip-dev \
  zip \
  bash \
  dos2unix

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
RUN docker-php-ext-install exif
RUN docker-php-ext-install zip
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install -j$(nproc) gd

COPY . /app


# Get latest Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN composer install

RUN php artisan key:generate

# RUN php artisan migrate

CMD ["php", "/app/artisan", "serve", "--host=0.0.0.0", "--port=8000"]

EXPOSE 8000