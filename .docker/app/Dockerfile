FROM php:8.2-fpm


RUN apt-get update && apt-get install -y \
git \
unzip \
libicu-dev \
libonig-dev \
libzip-dev \
libxml2-dev \
zip \
&& docker-php-ext-install intl mbstring pdo_mysql zip xml


# composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY --link \
    --from=ghcr.io/symfony-cli/symfony-cli:latest \
    /usr/local/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/html


# small entrypoint to set permissions if needed
COPY .docker/app/docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]