#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/antiqscan"
cd "$APP_DIR"

git pull --ff-only
/home/hermes/.local/bin/composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
sudo systemctl restart antiqscan.service
curl -fsS https://antiqscan.vatinel.fr >/dev/null
printf 'AntiQScan deployed OK\n'
