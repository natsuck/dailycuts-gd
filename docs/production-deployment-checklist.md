# Production Deployment Checklist

Use this checklist before publishing The Daily Cuts by GD.

Domain: `thedailycuts.com`

## 1. VPS Provisioning (one-time)

```bash
# Ubuntu 24.04, minimum 2GB RAM
apt update && apt upgrade -y
apt install -y nginx mysql-server \
  php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath \
  composer git cron ufw

# MySQL
mysql_secure_installation
mysql -u root -p -e "CREATE DATABASE ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'ecommerce'@'localhost' IDENTIFIED BY '<CHANGE_ME>';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON ecommerce.* TO 'ecommerce'@'localhost'; FLUSH PRIVILEGES;"

# Firewall
ufw allow 'Nginx Full'
ufw allow OpenSSH
ufw enable
```

## 2. Environment (.env)

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://thedailycuts.com`
- `APP_KEY=<generated, keep secret>`
- `DB_HOST=127.0.0.1`, `DB_DATABASE=ecommerce`, `DB_USERNAME=ecommerce`, `DB_PASSWORD=<strong>`
- `SESSION_SECURE_COOKIE=true`
- `SESSION_DOMAIN=thedailycuts.com`
- `LOG_LEVEL=warning`
- `MAIL_MAILER=smtp` with production SMTP credentials
- `RESELLER_INQUIRY_EMAIL=<real email>`
- `SHOP_BRANCH_PHONE=<real number>`

### Payment (Maya) — swap when ready

- `MAYA_PUBLIC_KEY=pk_live_...`
- `MAYA_SECRET_KEY=sk_live_...`
- `MAYA_MODE=production`
- `MAYA_WEBHOOK_IPS=` (leave empty to use mode-aware default)
- Register webhook `https://thedailycuts.com/maya/webhook` in Maya Business Portal

### Delivery (Lalamove) — swap when ready

- `LALAMOVE_API_KEY=<production key>`
- `LALAMOVE_API_SECRET=<production secret>`
- `LALAMOVE_SANDBOX=false`
- `LALAMOVE_WEBHOOK_URL=https://thedailycuts.com/lalamove/webhook`

### Social Login — configure when ready

- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI=https://thedailycuts.com/auth/google/callback`
- `FACEBOOK_CLIENT_ID`, `FACEBOOK_CLIENT_SECRET`, `FACEBOOK_REDIRECT_URI=https://thedailycuts.com/auth/facebook/callback`

## 3. Deploy Application

```bash
cd /var/www
git clone <repo-url> thedailycuts.com
cd thedailycuts.com

# .env setup
cp .env.example .env
# Edit .env with production values
php artisan key:generate   # if APP_KEY not set

# Install and build
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Laravel setup
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Permissions
chown -R www-data:www-data /var/www/thedailycuts.com
chmod -R 775 storage bootstrap/cache
```

## 4. Nginx Configuration

`/etc/nginx/sites-available/thedailycuts.com`:

```nginx
server {
    listen 80;
    server_name thedailycuts.com www.thedailycuts.com;
    root /var/www/thedailycuts.com/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realroot$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
ln -s /etc/nginx/sites-available/thedailycuts.com /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default   # remove default
nginx -t && systemctl reload nginx
```

## 5. SSL (Let's Encrypt)

```bash
apt install -y certbot python3-certbot-nginx
certbot --nginx -d thedailycuts.com -d www.thedailycuts.com
# Auto-renewal is configured by certbot
```

## 6. Scheduler (cron)

```bash
crontab -e
# Add:
* * * * * cd /var/www/thedailycuts.com && php artisan schedule:run >> /dev/null 2>&1
```

This runs the scheduled maintenance commands:

| Command | Frequency | Purpose |
|---|---|---|
| `orders:reconcile-maya` | every 5 min | Re-asks Maya about pending unpaid orders so a lost `PAYMENT_SUCCESS` webhook still marks the order paid |
| `orders:expire-unpaid` | hourly | Cancels stale unpaid orders and releases reserved stock (re-checks Maya before cancelling) |
| `orders:retry-lalamove-dispatch` | every 15 min | Creates missing Lalamove deliveries for paid orders (safety net if the queue worker was down) |

## 7. Queue Worker

Laravel queues power the post-payment Lalamove delivery creation
(`QUEUE_CONNECTION=database`). Run a supervised worker:

```bash
apt install -y supervisor

cat > /etc/supervisor/conf.d/thedailycuts-worker.conf <<'EOF'
[program:thedailycuts-worker]
command=php /var/www/thedailycuts.com/artisan queue:work database --tries=3 --max-time=3600
numprocs=1
autostart=true
autorestart=true
user=www-data
stopwaitsecs=3600
EOF

supervisorctl reread && supervisorctl update && supervisorctl start thedailycuts-worker
```

If the worker is down, payments are unaffected — the scheduler's
`orders:retry-lalamove-dispatch` creates any missed deliveries within 15 minutes.

## 8. Trusted Proxies

The Maya webhook IP allowlist compares `$request->ip()` against Maya's webhook
IPs. The app trusts loopback proxies by default (`TRUSTED_PROXIES` unset),
matching the nginx + PHP-FPM setup in section 4. If you later put an external
load balancer or CDN (Cloudflare, AWS ALB, ...) in front of the app, set
`TRUSTED_PROXIES` to its IPs/CIDR ranges — otherwise **every legitimate Maya
webhook will be rejected** with HTTP 400.

## 9. DNS Records

At your domain registrar, create:

| Type | Name | Value |
|------|------|-------|
| A | @ | `<VPS_IP>` |
| A | www | `<VPS_IP>` |

## 10. Post-Launch Verification

- [ ] `https://thedailycuts.com` loads the storefront
- [ ] `https://thedailycuts.com/up` returns `OK`
- [ ] Login / register flow works
- [ ] Product browsing and cart work
- [ ] Checkout with Maya sandbox keys
- [ ] Swap to Maya production keys and test real payment
- [ ] Queue worker running: `supervisorctl status thedailycuts-worker`
- [ ] Scheduler commands fire: `php artisan schedule:list`
- [ ] Email delivery (order confirmation) works
- [ ] `storage/` and `bootstrap/cache/` are writable
