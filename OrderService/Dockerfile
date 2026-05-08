FROM php:8.2-fpm

# Instal dependensi sistem
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip

# Instal ekstensi PHP (Wajib ada 'sockets' untuk RabbitMQ)
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd sockets

# Ambil Composer terbaru
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

# Beri izin akses folder Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]