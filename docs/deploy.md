# AntiQScan deployment notes

Target: `antiqscan.vatinel.fr` on the same VPS/Caddy pattern as `cv.vatinel.fr`.

## Caddy skeleton

```caddyfile
antiqscan.vatinel.fr {
    root * /var/www/antiqscan/current/public
    encode zstd gzip
    php_fastcgi unix//run/php/php8.3-fpm.sock
    file_server
}
```

## Server checklist

1. Create MariaDB database/user.
2. Clone repo into `/var/www/antiqscan/releases/<timestamp>`.
3. Write production `.env` with `APP_ENV=production`, `APP_DEBUG=false`, local storage, queue database.
4. Run `composer install --no-dev --optimize-autoloader`.
5. Run `php artisan key:generate --force` once if no key exists.
6. Run `php artisan migrate --force`.
7. Run `npm ci && npm run build`.
8. Symlink `current` to latest release.
9. Configure queue worker via systemd/supervisor.
10. Reload Caddy.

No AI provider key is required for the initial upload/edit/catalogue skeleton.
