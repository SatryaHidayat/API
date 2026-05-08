# # syntax=docker/dockerfile:1

# # Comments are provided throughout this file to help you get started.
# # If you need more help, visit the Dockerfile reference guide at
# # https://docs.docker.com/go/dockerfile-reference/

# # Want to help us make this template better? Share your feedback here: https://forms.gle/ybq9Krt8jtBL3iCk7

# ################################################################################

# # The example below uses the PHP Apache image as the foundation for running the app.
# # By specifying the "8.2.12-apache" tag, it will also use whatever happens to be the
# # most recent version of that tag when you build your Dockerfile.
# # If reproducibility is important, consider using a specific digest SHA, like
# # php@sha256:99cede493dfd88720b610eb8077c8688d3cca50003d76d1d539b0efc8cca72b4.
# FROM php:8.2.12-apache

# # Your PHP application may require additional PHP extensions to be installed
# # manually. For detailed instructions for installing extensions can be found, see
# # https://github.com/docker-library/docs/tree/master/php#how-to-install-more-php-extensions
# # The following code blocks provide examples that you can edit and use.
# #
# # Add core PHP extensions, see
# # https://github.com/docker-library/docs/tree/master/php#php-core-extensions
# # This example adds the apt packages for the 'gd' extension's dependencies and then
# # installs the 'gd' extension. For additional tips on running apt-get:
# # https://docs.docker.com/go/dockerfile-aptget-best-practices/
# # RUN apt-get update && apt-get install -y \
# #     libfreetype-dev \
# #     libjpeg62-turbo-dev \
# #     libpng-dev \
# # && rm -rf /var/lib/apt/lists/* \
# #     && docker-php-ext-configure gd --with-freetype --with-jpeg \
# #     && docker-php-ext-install -j$(nproc) gd
# #
# # Add PECL extensions, see
# # https://github.com/docker-library/docs/tree/master/php#pecl-extensions
# # This example adds the 'redis' and 'xdebug' extensions.
# # RUN pecl install redis-5.3.7 \
# #    && pecl install xdebug-3.2.1 \
# #    && docker-php-ext-enable redis xdebug

# # Use the default production configuration for PHP runtime arguments, see
# # https://github.com/docker-library/docs/tree/master/php#configuration
# RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# # Copy app files from the app directory.
# COPY ./OrderService/routes /var/www/html

# # Switch to a non-privileged user (defined in the base image) that the app will run under.
# # See https://docs.docker.com/go/dockerfile-user-best-practices/
# USER www-data

# # Instal ekstensi sockets untuk komunikasi RabbitMQ
# RUN docker-php-ext-install sockets

# 1. Gunakan image PHP resmi dengan versi yang sesuai
FROM php:8.2-fpm

# 2. Tentukan direktori kerja di dalam container
WORKDIR /var/www

# 3. Instal dependensi sistem yang dibutuhkan (Termasuk ekstensi 'sockets' untuk RabbitMQ)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# 4. Instal ekstensi PHP (Wajib instal sockets agar bisa konek ke RabbitMQ)
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd sockets

# 5. Instal Composer secara otomatis ke dalam container
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. SALIN semua file dari folder lokal ke dalam container (Inilah proses "unggah"-nya)
COPY . .

# 7. Jalankan composer install di dalam container agar folder 'vendor' tercipta
RUN composer install --no-interaction --optimize-autoloader --no-dev

# 8. Berikan izin akses folder storage agar Laravel bisa menulis log
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]