FROM php:8.5.12-apache

RUN apt-get update && apt-get install -y --no-install-recommends unzip libzip-dev \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# El código de app/, storage/ y .env no es servible por HTTP.
RUN sed -ri 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && a2enmod headers

COPY docker/php-security.ini /usr/local/etc/php/conf.d/99-votacion-security.ini
WORKDIR /var/www/html
COPY composer.json /var/www/html/composer.json
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
COPY . /var/www/html/
RUN mkdir -p /var/www/html/storage/uploads \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod 700 /var/www/html/storage/uploads

CMD ["sh", "-c", "php /var/www/html/scripts/create_operator.php && exec apache2-foreground"]
