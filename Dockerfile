FROM node:22-alpine AS node
FROM php:8.4-fpm-alpine

RUN apk add --no-cache icu-libs libzip libpng libjpeg-turbo freetype libxml2 oniguruma curl libpq supervisor

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev libxml2-dev oniguruma-dev postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_pgsql pgsql intl mbstring gd zip opcache \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node /usr/local/bin/node /usr/local/bin/node
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

# Let `php artisan serve` handle concurrent requests instead of one at a time.
ENV PHP_CLI_SERVER_WORKERS=4

WORKDIR /app

# Copy dependency manifests first so composer install is cached
# and only re-runs when composer.lock changes. Skip post-install
# scripts (they need the full app source which isn't here yet).
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts \
    && mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views storage/app/private/livewire-tmp storage/app/public bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache

# Copy the full application source. This layer changes on every commit
# but the composer install layer above stays cached.
COPY . .
RUN npm ci --no-audit --no-fund \
    && npm run build \
    && rm -rf node_modules \
    && composer dump-autoload --optimize --no-dev \
    && chmod +x docker/entrypoint.sh

# Uploaded attachments live under storage/app (local disk root: storage/app/private).
# Declare it as a volume so a Pier persistent volume can be mounted here and
# files survive container rebuilds.
VOLUME ["/app/storage/app"]

EXPOSE 8080

# Liveness probe. /login is public (200); /up is IP-restricted and would 404
# from the loopback interface, so it is not suitable as a container healthcheck.
HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8080/login >/dev/null || exit 1

# entrypoint runs migrations + cache warming, then execs supervisor, which
# supervises the web server, queue worker, and scheduler in one container.
ENTRYPOINT ["/app/docker/entrypoint.sh"]
CMD ["supervisord", "-c", "/app/docker/supervisord.conf"]
