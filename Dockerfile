FROM node:22-alpine AS node
FROM php:8.4-fpm-alpine

RUN apk add --no-cache icu-libs libzip libpng libjpeg-turbo freetype libxml2 oniguruma curl libpq

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev libxml2-dev oniguruma-dev postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_pgsql pgsql intl mbstring gd zip opcache \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node /usr/local/bin/node /usr/local/bin/node
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

WORKDIR /app
COPY . .

RUN sed -i '/->withMiddleware(function (Middleware $middleware): void {/a\        $middleware->trustProxies(at: "*");' bootstrap/app.php \
    && composer install --no-dev --no-interaction --no-progress \
    && npm ci --no-audit --no-fund \
    && npm run build \
    && rm -rf node_modules \
    && mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache

EXPOSE 8080
CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=8080"]
