# Production image for Warebase (used by Dokploy). Local development uses docker-compose.yml for MySQL only.
# Versions are pinned to releases patched for the October 2026 PHP and Node.js CVEs (see README).

# --- PHP runtime with the extensions Laravel and MySQL need ---
# OPcache is built into PHP 8.5, so it is not in the extension list.
FROM php:8.5.12-apache AS php-base
RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libzip-dev unzip \
    && docker-php-ext-install pdo_mysql intl zip bcmath \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite headers deflate
COPY docker/php/production.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY docker/apache/warebase.conf /etc/apache2/sites-available/000-default.conf
COPY docker/apache/security.conf /etc/apache2/conf-available/zz-security.conf
RUN a2enconf zz-security
WORKDIR /var/www/html

# --- PHP dependencies (no dev packages) ---
FROM php-base AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# --- CSS/JS build (Node is only needed here, not in the final image) ---
FROM node:22.23.4-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
# Tailwind also scans the framework's pagination views.
COPY --from=vendor /var/www/html/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

# --- Final image ---
# Also installs an "artisan" shortcut on PATH, usable from any folder (the Dokploy terminal may not open in /var/www/html).
FROM php-base
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /var/www/html/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && rm /usr/bin/composer \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x docker/entrypoint.sh \
    && printf '#!/bin/sh\nexec php /var/www/html/artisan "$@"\n' > /usr/local/bin/artisan \
    && chmod +x /usr/local/bin/artisan \
    # "artisan" from any folder (e.g. the Dokploy terminal, which may not open in /var/www/html).
    && printf '#!/bin/sh\nexec php /var/www/html/artisan "$@"\n' > /usr/local/bin/artisan \
    && chmod +x /usr/local/bin/artisan
EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s CMD curl -fsS http://localhost/up || exit 1
ENTRYPOINT ["docker/entrypoint.sh"]
