#!/bin/sh
set -e

# Wait for database if needed
# sleep 5

echo "Running migrations..."
php artisan migrate --force

echo "Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
