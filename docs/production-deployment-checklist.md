# Production Deployment Checklist

Use this checklist before publishing The Daily Cuts by GD.

## Environment

- Set `APP_ENV=production`.
- Set `APP_DEBUG=false`.
- Set `APP_URL` to the production HTTPS domain.
- Generate and keep a strong `APP_KEY`.
- Configure production MySQL credentials.
- Configure `PAYMONGO_SECRET` and `PAYMONGO_WEBHOOK_SECRET`.
- Configure mail credentials and `RESELLER_INQUIRY_EMAIL`.
- Use `SESSION_SECURE_COOKIE=true` behind HTTPS.
- Use `LOG_LEVEL=warning` or stricter for production.

## Build And Cache

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## Operations

- Point the web server document root to `public/`.
- Run queues with a process manager if `QUEUE_CONNECTION` is not `sync`.
- Verify the PayMongo webhook endpoint is reachable over HTTPS.
- Confirm `storage/` and `bootstrap/cache/` are writable by the web server user.
- Do not run seeders on production unless they are explicitly production-safe.
- Keep `.env`, logs, and backups outside public web access.
