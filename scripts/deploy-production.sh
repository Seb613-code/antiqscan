#!/usr/bin/env bash
set -euo pipefail

SRC_DIR="/home/hermes/projects/antiqscan"
APP_DIR="/var/www/antiqscan"

cd "$SRC_DIR"
git fetch origin main
git checkout main
git pull --ff-only

# Copy only tracked project files. This keeps production .env, storage, vendor and SQLite data safe.
git archive HEAD | tar -x -C "$APP_DIR"

cd "$APP_DIR"
/home/hermes/.local/bin/composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader
php artisan package:discover --ansi
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
sudo systemctl restart antiqscan.service

for i in {1..20}; do
    if curl -fsS http://127.0.0.1:8090 >/dev/null; then
        break
    fi
    sleep 1
done

curl -fsS https://antiqscan.vatinel.fr >/dev/null
printf 'AntiQScan deployed OK\n'
