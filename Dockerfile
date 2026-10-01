FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
      libpq-dev libicu-dev libzip-dev unzip git \
 && docker-php-ext-install pdo_pgsql pgsql intl \
 && a2enmod rewrite \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install CodeIgniter 4 + library JWT
RUN rm -rf /var/www/html/* \
 && composer create-project codeigniter4/appstarter /var/www/html --no-interaction \
 && cd /var/www/html && composer require firebase/php-jwt --no-interaction

# Timpa dengan kode custom
COPY app/ /var/www/html/app/

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf /etc/apache2/conf-available/*.conf \
 && sed -ri 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
 && chown -R www-data:www-data /var/www/html/writable

EXPOSE 80
