#!/bin/sh
# Runs on every container start: cache config for speed, then apply new migrations, then serve.
set -e

php artisan config:cache
php artisan route:cache
php artisan view:cache

# The database container may still be starting; retry for about a minute.
attempt=1
until php artisan migrate --force; do
    if [ "$attempt" -ge 20 ]; then
        echo "Database not reachable, giving up." >&2
        exit 1
    fi
    attempt=$((attempt + 1))
    sleep 3
done

# Cache files and logs above were written as root; Apache runs as www-data.
chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground
