# The Daily Cuts — Production Deployment

Deployment target: **Digital Ocean VPS** behind **Cloudflare** (Full strict SSL),
with **supervisor** running the queue worker and scheduler.

Follow these steps in order. Everything up to step 8 is **not** blocked by the
Maya business approval (BIR 2303). Step 8 (real payments) is the only
Maya-gated step and is a **config-only** flip.

---

## 1. VPS baseline

- Ubuntu LTS (or Debian) server.
- Install: PHP `8.2` + extensions (`mysql`, `gd`, `curl`, `zip`, `mbstring`,
  `xml`, `bcmath`, `intl`), MySQL/MariaDB, nginx, composer, supervisor, git.
- Clone the repo to `/var/www/ecommerce`, ownership `www-data`.

## 2. Database

- Create a **dedicated least-privilege app user** (do not use `root`):

```sql
CREATE DATABASE ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'app_user'@'127.0.0.1' IDENTIFIED BY 'STRONG_APP_PASSWORD';
GRANT ALL PRIVILEGES ON ecommerce.* TO 'app_user'@'127.0.0.1';
FLUSH PRIVILEGES;
```

- Run migrations: `php artisan migrate --force`.

## 3. Rotate all previously-exposed secrets

The DB password (and other credentials) were exposed in git history. Treat
**all** of these as compromised and rotate them:

| Credential | Where to rotate | `.env` var(s) |
|---|---|---|
| DB password | MySQL (DB was previously leaked) | `DB_PASSWORD` |
| Gmail SMTP app password | Google Account > Security > App passwords | `MAIL_PASSWORD` |
| Lalamove key/secret | Lalamove developer console | `LALAMOVE_API_KEY` / `LALAMOVE_API_SECRET` |
| Google Maps key | Google Cloud Console (restrict to your domain) | `GOOGLE_MAPS_API_KEY` |
| Google OAuth secret | Google Cloud Console | `GOOGLE_CLIENT_SECRET` |
| Maya keys | Maya dashboard (when production is approved) | `MAYA_PUBLIC_KEY` / `MAYA_SECRET_KEY` |

Generate a strong DB password with: `openssl rand -base64 24`.

## 4. `.env` (production, on the VPS only — never committed)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://thedailycuts.com          # real domain, NOT the ngrok tunnel

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_USERNAME=app_user
DB_PASSWORD=STRONG_APP_PASSWORD

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true                # required; HTTPS-only cookies
SESSION_ENCRYPT=true

LOG_CHANNEL=daily                          # rotate logs daily (avoid 17MB single file)
LOG_LEVEL=warning

QUEUE_CONNECTION=database                  # jobs stored in DB until the worker runs
CACHE_STORE=database

# === Cloudflare trusted proxies ===
# The app must see the REAL client IP (used by the Maya webhook IP allowlist),
# not Cloudflare's edge IP. Set TRUSTED_PROXIES to Cloudflare's published IPv4
# ranges so Laravel trusts X-Forwarded-For from them. See:
# https://www.cloudflare.com/ips/
# Example (update to the current Cloudflare ranges):
# TRUSTED_PROXIES=173.245.48.0/20,103.21.244.0/22,103.22.200.0/22,103.31.4.0/22,141.101.64.0/18,108.162.192.0/18,190.93.240.0/20,188.114.96.0/20,197.234.240.0/22,198.41.128.0/17,162.158.0.0/15,104.16.0.0/13,104.24.0.0/14,172.64.0.0/13,131.0.72.0/22

# === Maya (sandbox until BIR 2303 clears) ===
MAYA_MODE=sandbox
MAYA_PUBLIC_KEY=SANDBOX_PUBLIC_KEY
MAYA_SECRET_KEY=SANDBOX_SECRET_KEY
# Optional when MAYA_MODE=production: defaults to Maya's production webhook IPs
# automatically if unset.
# MAYA_WEBHOOK_IPS=
```

Then cache the config: `php artisan config:cache`.

## 5. Cloudflare setup

- Add the domain to Cloudflare (DNS). Point `A` + `AAAA` records to the VPS IP.
- **SSL/TLS mode: Full (strict)**
  - The origin (nginx) must present a **valid** cert. Use Let's Encrypt on the
    VPS (`certbot --nginx -d thedailycuts.com -d www.thedailycuts.com`), or
    Cloudflare Origin CA.
- Keep `www`/apex serving HTTPS. The `.env` `APP_URL` should be the canonical
  host.

## 6. Supervisor (queue + scheduler)

Copy `deploy/supervisor/*.conf` to `/etc/supervisor/conf.d/`, adjusting
`user`, `command` paths, and the PHP binary to match the server.

```bash
sudo cp deploy/supervisor/laravel-queue.conf deploy/supervisor/laravel-schedule.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

Verify both are `RUNNING`. This is required — without the worker, queued
emails and Lalamove dispatch never run; without the scheduler, unpaid orders
never expire.

## 7. nginx + deployment

- Copy `deploy/nginx/ecommerce.conf` into nginx, adjust the PHP-FPM socket
  version and paths, then reload.
- Remove the local Vite dev marker so production serves built assets:
  `rm -f public/hot`
- Build assets: `npm ci && npm run build`
- Install prod deps: `composer install --no-dev --classmap-authoritative`
- Cache routes + config: `php artisan config:cache && php artisan route:cache`
- Storage link: `php artisan storage:link`

## 8. Under-wraps (maintenance mode)

While waiting on Maya production approval, keep the site **hidden** behind the
branded maintenance page (`resources/views/errors/503.blade.php`):

```bash
php artisan down
```

- The `/up` health endpoint stays reachable (see nginx config), so Cloudflare
  health checks keep passing.
- Test the full checkout flow with **sandbox** MDaya payments from your own
  device (allow your IP or test via curl with the right headers) to validate
  the Cloudflare → trusted-proxy → Maya webhook IP wiring.

## GO-LIVE (when BIR 2303 / Maya business approval arrives)

1. Get Maya **production** keys from the Maya dashboard.
2. Update `.env`:
   ```env
   MAYA_MODE=production
   MAYA_PUBLIC_KEY=PROD_PUBLIC_KEY
   MAYA_SECRET_KEY=PROD_SECRET_KEY
   ```
   (Webhook IPs auto-default to Maya's production ranges when unset.)
3. Cache config: `php artisan config:cache`
4. Take the site live: `php artisan up`

That's it — a config-only flip, no code changes. **Important:** with
`MAYA_MODE=production` and a blank/missing key, Create Checkout fails; keep the
site under maintenance (step 8) until the real production key is in place.
