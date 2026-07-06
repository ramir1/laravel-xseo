FROM php:8.5-cli-alpine

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

RUN apk add --no-cache \
    bash \
    curl \
    git

RUN install-php-extensions \
    bcmath \
    dom \
    mbstring \
    pcntl \
    simplexml \
    zip

RUN curl -sS https://getcomposer.org/installer -o composer-setup.php \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm -f composer-setup.php

RUN git config --global --add safe.directory /app

WORKDIR /app
