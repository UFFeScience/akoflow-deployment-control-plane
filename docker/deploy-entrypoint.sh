#!/bin/bash
set -e

# Cloud Run provides the PORT env variable. Apache must listen on it.
PORT="${PORT:-8080}"

sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-enabled/000-default.conf

# Run Laravel bootstrap tasks
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
