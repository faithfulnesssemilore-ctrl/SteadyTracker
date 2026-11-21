FROM node:22-bookworm-slim AS assets

ARG VITE_APP_NAME=SteadyTracker
ARG VITE_API_URL=
ARG VITE_REVERB_APP_KEY=
ARG VITE_REVERB_HOST=
ARG VITE_REVERB_PORT=8080
ARG VITE_REVERB_SCHEME=https

ENV VITE_APP_NAME=$VITE_APP_NAME \
    VITE_API_URL=$VITE_API_URL \
    VITE_REVERB_APP_KEY=$VITE_REVERB_APP_KEY \
    VITE_REVERB_HOST=$VITE_REVERB_HOST \
    VITE_REVERB_PORT=$VITE_REVERB_PORT \
    VITE_REVERB_SCHEME=$VITE_REVERB_SCHEME

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

FROM php:8.4-fpm-bookworm AS php

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libicu-dev \
    libonig-dev \
    libzip-dev \
    nginx \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php.ini /usr/local/etc/php/conf.d/steady.ini

RUN echo 'upload_max_filesize=100M' > /usr/local/etc/php/conf.d/uploads.ini \
    && echo 'post_max_size=100M' >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo 'memory_limit=256M' >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo 'max_execution_time=300' >> /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/certs/aiven-ca.pem /var/www/html/certs/aiven-ca.pem
COPY docker/start-web.sh /usr/local/bin/start-web.sh

RUN chmod +x /usr/local/bin/start-web.sh \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=5 \
    CMD php -r '$c=stream_context_create(["http"=>["timeout"=>2]]); $r=@file_get_contents("http://127.0.0.1/up",false,$c); exit($r!==false?0:1);'

CMD ["/usr/local/bin/start-web.sh"]
