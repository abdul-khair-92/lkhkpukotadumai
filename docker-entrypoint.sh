#!/bin/sh

echo "Waiting for database..."
while ! mysqladmin ping -h"db" -uroot -p"${DB_PASSWORD}" --skip-ssl --silent; do
    sleep 1
done

echo "Running migrations..."
php artisan migrate --force

echo "Optimizing application..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Linking storage..."
php artisan storage:link || true

# Start PHP-FPM
echo "Starting PHP-FPM..."
exec "$@"
