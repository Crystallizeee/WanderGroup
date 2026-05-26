#!/bin/sh
set -e

# Wait for database if needed
# sleep 5

echo "Running migrations..."
php artisan migrate --force

echo "Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
