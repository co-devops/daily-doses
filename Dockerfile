FROM php:7.4.5-apache

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN apt-get update

RUN apt-get install libzip-dev zip -y
#RUN apt-get install libicu-dev -y

RUN docker-php-ext-install zip
#RUN docker-php-ext-configure intl && docker-php-ext-install intl

RUN a2enmod rewrite

COPY composer.json .

RUN composer install -n --prefer-dist

COPY . .

ARG BUILD
ENV BUILD=${BUILD}

## Disabled following when running locally (keep it enabled for GCP Cloud Run)
RUN if [ "$BUILD" = "local" ] ; then ls -al ; else sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.    conf ; fi
