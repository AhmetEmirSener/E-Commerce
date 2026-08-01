FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    ca-certificates curl git unzip zip libzip-dev libpng-dev libonig-dev libxml2-dev libicu-dev autoconf gcc g++ make pkg-config \
    && update-ca-certificates \
    && docker-php-ext-install pdo pdo_mysql zip mbstring bcmath exif pcntl intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && curl -sS -o /etc/ssl/certs/cacert.pem https://curl.se/ca/cacert.pem



RUN echo "openssl.cafile=/etc/ssl/certs/cacert.pem" > /usr/local/etc/php/conf.d/ca-certificates.ini

ENV SSL_CERT_FILE=/etc/ssl/certs/cacert.pem
ENV COMPOSER_CAFILE=/etc/ssl/certs/cacert.pem

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --optimize-autoloader --no-dev --no-interaction

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache


EXPOSE 9000
CMD ["php-fpm"]