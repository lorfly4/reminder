# Deployment Checklist

## Server

- Ubuntu 24.04 LTS
- PHP 8.3 with php-fpm
- MySQL 8 with utf8mb4_unicode_ci
- Redis
- Nginx
- Supervisor
- Node.js LTS for build only

## Nginx

- Redirect HTTP to HTTPS.
- Set document root to `public`.
- Forward PHP files to PHP-FPM.
- Set `APP_URL` to the HTTPS domain.
- Set `SANCTUM_STATEFUL_DOMAINS` and `SESSION_DOMAIN` to production domains.
- Set security headers in Nginx or Laravel middleware.

## Supervisor

```ini
[program:remindly-worker]
command=php artisan queue:work redis --sleep=3 --tries=5
directory=/var/www/remindly
autostart=true
autorestart=true
stopasgroup=true
```

## Cron

```text
* * * * * cd /var/www/remindly && php artisan schedule:run
```
