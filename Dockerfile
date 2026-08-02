FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    ca-certificates curl git unzip zip libzip-dev libpng-dev libonig-dev libxml2-dev libicu-dev autoconf gcc g++ make pkg-config \
    && update-ca-certificates \
    && docker-php-ext-install pdo pdo_mysql zip mbstring bcmath exif pcntl intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && curl -sS -o /etc/ssl/certs/cacert.pem https://curl.se/ca/cacert.pem \
    && apt-get purge -y autoconf gcc g++ make pkg-config \
    && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/*

RUN echo "openssl.cafile=/etc/ssl/certs/cacert.pem" > /usr/local/etc/php/conf.d/ca-certificates.ini \
    && echo "upload_max_filesize=20M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=20M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time=120" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "opcache.enable=1" > /usr/local/etc/php/conf.d/opcache-custom.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache-custom.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache-custom.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache-custom.ini

ENV SSL_CERT_FILE=/etc/ssl/certs/cacert.pem
ENV COMPOSER_CAFILE=/etc/ssl/certs/cacert.pem

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --optimize-autoloader --no-dev --no-interaction --no-scripts

COPY . .

RUN composer dump-autoload --optimize

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]