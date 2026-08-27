# 🚀 Production Deployment Checklist

**Project:** The Daily Cuts  
**Date:** 2026-08-27  
**Status:** In progress — VPS + Cloudflare (Full strict) + supervisor; waiting on Maya production approval (BIR 2303).

---

## ✅ Phase 1: Secrets Cleanup - COMPLETED

- [x] `.env` removed from Git history using `git filter-branch`
- [x] Repository force-pushed with cleaned history
- [x] Verified no credentials in version control
- [x] `.env.example` exists for local development reference
- [x] `.env` already in `.gitignore`

**Result:** Repository is now safe for public/team access. `.env` credentials will NOT be exposed to new clones.

---

## ⏳ Phase 2: Production Configuration

### Step 1: Rotate Compromised Secrets
**Status:** 🔴 NOT DONE  
**Reason:** Database password was exposed in Git history  
**Action Required:**

```bash
# ⚠️ CRITICAL: Do this IMMEDIATELY
# 1. Access your MySQL database server
mysql -h 127.0.0.1 -u root -p

# 2. Generate a new password and update the database user:
ALTER USER 'root'@'127.0.0.1' IDENTIFIED BY 'NEW_SECURE_PASSWORD_HERE';
FLUSH PRIVILEGES;

# 3. Update your local .env file:
# DB_PASSWORD=NEW_SECURE_PASSWORD_HERE

# 4. Test connection:
php artisan tinker
# Then: DB::connection()->getPdo()
# Should connect without error
```

**Other Exposed Credentials to Rotate:**
- ✅ `MAIL_PASSWORD` - Generate new App Password in Gmail settings
- ✅ `MAYA_PUBLIC_KEY` - Contact Maya support or regenerate in dashboard
- ✅ `MAYA_SECRET_KEY` - Contact Maya support or regenerate in dashboard  
- ✅ `LALAMOVE_API_KEY` - Regenerate in Lalamove Developer Console
- ✅ `LALAMOVE_API_SECRET` - Regenerate in Lalamove Developer Console
- ✅ `GOOGLE_MAPS_API_KEY` - Create new API key in Google Cloud Console
- ✅ `APP_KEY` - Already unique (generated with `php artisan key:generate`)

---

### Step 2: Verify Production Mode Settings
**File:** `.env`  
**Checklist:**

```bash
# Run this command and verify output:
php artisan tinker

# Check these values:
config('app.env')           # Should be: 'production'
config('app.debug')         # Should be: false
config('mail.from.name')    # Should be: 'The Daily Cuts'
config('queue.default')     # Should be: 'database' (or 'redis' for scale)
config('cache.default')     # Should be: 'database' (or 'redis' for scale)
config('session.secure')    # Should be: true
config('session.encrypt')   # Should be: true
config('auth.passwords.users.expire') # Should be set
```

**Required `.env` values for production (set on the VPS — secret values are
redacted from this repo and never committed):**
- [x] `APP_ENV=production` - Correct
- [x] `APP_DEBUG=false` - Correct  
- [ ] `APP_URL=https://thedailycuts.com` - Set to real domain (currently ngrok)
- [ ] `DB_USERNAME`/`DB_PASSWORD` - Use dedicated `app_user`, rotated password
- [ ] `MAIL_PASSWORD` - Rotated Gmail App Password
- [ ] `MAYA_PUBLIC_KEY` / `MAYA_SECRET_KEY` - sandbox now; production at go-live (BIR)
- [ ] `MAYA_MODE` - keep `sandbox` until Maya production approved
- [ ] `MAYA_WEBHOOK_IPS` - auto-defaults to production IPs at switch-over
- [ ] `LALAMOVE_API_KEY` / `LALAMOVE_API_SECRET` - rotated live keys
- [ ] `GOOGLE_MAPS_API_KEY` - rotated + domain-restricted key
- [ ] `TRUSTED_PROXIES` - Cloudflare IP ranges (see deploy/README.md)

---

### Step 3: Configure Payment Gateway API Keys

#### Maya Payment Gateway
**Portal:** https://dashboard.maya.ph  
**Current Settings:**
```env
# Values redacted from this checklist — secrets never belong in the repo.
MAYA_MODE=sandbox        # switch to production at go-live (BIR 2303)
MAYA_PUBLIC_KEY=<sandbox key on VPS>
MAYA_SECRET_KEY=<sandbox key on VPS>
MAYA_WEBHOOK_IPS=        # auto-defaults to production IPs when MAYA_MODE=production
```
**Action (READ-ONLY until BIR 2303 clears):** The repo's deployment plan is to
deploy now in **sandbox** mode behind maintenance mode, then do a **config-only
flip** to production when Maya business approval arrives. See `deploy/README.md`.

**To update to production:**
1. Log into Maya Dashboard
2. Navigate to API Keys section
3. Copy Production Public Key → `MAYA_PUBLIC_KEY`
4. Copy Production Secret Key → `MAYA_SECRET_KEY`
5. Change `MAYA_MODE=production`
6. Get webhook IP allowlist from Maya Dashboard
7. Set `MAYA_WEBHOOK_IPS=<IP1>,<IP2>`

**Test:**
```bash
php artisan tinker
config('maya.public_key')    # Verify production key loaded
config('maya.mode')          # Should be 'production'
```

---

#### Lalamove Delivery Service  
**Portal:** https://lalamove.com/developer  
**Current Settings:**
```env
# Values redacted from this checklist — secrets never belong in the repo.
LALAMOVE_API_KEY=<live key on VPS>
LALAMOVE_API_SECRET=<live secret on VPS>
LALAMOVE_SANDBOX=false
```

**To update to production:**
1. Log into Lalamove Developer Console
2. Navigate to Production API Keys section
3. Copy Production API Key → `LALAMOVE_API_KEY`
4. Copy Production API Secret → `LALAMOVE_API_SECRET`
5. Change `LALAMOVE_SANDBOX=false`
6. Get webhook IP allowlist if available
7. Set `LALAMOVE_WEBHOOK_IPS=<IP1>,<IP2>` (if different from sandbox)

**Test:**
```bash
php artisan tinker
config('lalamove.api_key')   # Verify production key loaded
config('lalamove.sandbox')   # Should be false
```

---

### Step 4: Configure Trusted Proxies (If Behind Load Balancer/CDN)

**Scenario 1: Behind Cloudflare CDN**
```env
# Set in .env to Cloudflare's published IPv4 ranges (see deploy/README.md).
# Do NOT use "*" — that lets anyone spoof X-Forwarded-For (a webhook forgery
# risk for the Maya IP allowlist).
TRUSTED_PROXIES=<Cloudflare IP ranges from https://www.cloudflare.com/ips/>
```

**Scenario 2: Behind AWS Application Load Balancer**
```env
TRUSTED_PROXIES=10.0.0.0/8,172.16.0.0/12,192.168.0.0/16
```

**Scenario 3: Behind AWS API Gateway / Lambda**
```env
TRUSTED_PROXIES=*
```

**Why this matters:** Maya and Lalamove webhooks validate source IP. If you're behind a proxy and don't configure TRUSTED_PROXIES, Laravel sees the proxy's IP (not the webhook sender's IP), causing validation to fail.

**Test:**
```bash
# After deployment, check what IP Laravel sees:
php artisan tinker
request()->ip()  # Should be webhook sender's IP, not proxy IP
```

---

## ⏳ Phase 3: Dependency & Build Validation

### Step 1: Check for Dependency Vulnerabilities  
**Status:** ✅ DONE (2026-08-27)  
**Command:**

```bash
# Scan composer dependencies for CVEs:
composer audit
```

**Result:** No security vulnerability advisories found (0 CVEs).

**If vulnerabilities found:**
```bash
# Update vulnerable packages:
composer update

# Re-run audit:
composer audit
```

---

### Step 2: Build Frontend Assets
**Status:** 🔴 NOT DONE  
**Commands:**

```bash
# Install Node dependencies:
npm install

# Build production assets:
npm run build

# Verify build output:
ls -la public/build/
# Should contain manifest.json and asset files
```

---

### Step 3: Cache Configuration
**Status:** 🔴 Run at deploy (done now that route closures were converted)  
**Commands:**

```bash
# Cache configuration files (improves performance):
php artisan config:cache

# Cache routes (improves performance). Works now — all route closures were
# converted to controller methods so routes are serializable:
php artisan route:cache

# Optimize autoloader (improves performance):
composer install --optimize-autoloader --no-dev
```

---

## 🧪 Phase 4: Pre-Deployment Testing

### Step 1: Verify Application Startup
```bash
# Run development server to verify:
php artisan serve --host=127.0.0.1 --port=8000

# In browser: http://localhost:8000
# Should load homepage without errors
# Check Laravel logs: tail -f storage/logs/laravel.log
```

---

### Step 2: Test Payment Gateway Webhook
**Maya Payment Webhook Test:**
Maya webhooks are **not signed**. Acceptance requires BOTH:
1. The request IP must be in `config('maya.webhook_ips')` (via `request()->ip()`
   after trusted-proxy resolution — behind Cloudflare this must resolve to
   Maya's IP, not Cloudflare's edge).
2. For `PAYMENT_SUCCESS`, the order is only confirmed after a **server-to-server
   Get Checkout** call to Maya confirms the payment and the amount/currency match.

The best end-to-end test is a real **sandbox** checkout through Cloudflare
(which exercises the full wiring). A raw `curl` `POST /maya/webhook` will
acknowledge the payload but the order will **not** be confirmed unless Maya's
API reports the payment as successful.

**Lalamove Webhook Test:**
```bash
# Lalamove webhooks require a valid HMAC signature (raw JSON body + shared secret).
# Run from Lalamove test console or contact support for a test webhook.
```

---

### Step 3: Test Database Connection
```bash
php artisan tinker

# Run:
DB::select('SELECT 1 as id')

# Expected: Collection with id=>1
```

---

### Step 4: Test Email Sending
```bash
php artisan tinker

# Run:
Mail::raw('Test email', function ($message) {
  $message->to('your-email@example.com');
});

# Check your email inbox (may take 1-2 minutes)
```

---

## 📋 Final Deployment Steps

### Before Going Live:

1. [ ] All Phase 2 steps completed
2. [ ] **Supervisor running both programs:** `laravel-queue` AND `laravel-schedule` (see `deploy/supervisor/`). Without these, queued emails/Lalamove dispatch and the unpaid-order expiry never run.
3. [ ] All Phase 4 tests passed
4. [ ] Database backed up
5. [ ] Rollback plan documented
6. [ ] Uptime monitoring configured
7. [ ] Error monitoring (Sentry/Rollbar) configured — optional, not yet wired
8. [ ] SSL certificate verified (HTTPS working) — Cloudflare Full (strict) + origin cert
9. [ ] Rate limiting tested
10. [ ] Security headers verified (check in browser DevTools) — CSP is Report-Only
11. [ ] `public/hot` removed on deploy (serve from `public/build`)

### Deployment Commands:
```bash
# On production server:

# 1. Pull latest code:
git pull origin main

# 2. Install dependencies (prod only, no dev tools like tinker):
composer install --optimize-autoloader --no-dev

# 3. Run migrations:
php artisan migrate --force

# 4. Build assets + remove Vite dev marker:
rm -f public/hot
npm ci && npm run build

# 5. Cache configuration and routes:
php artisan config:cache
php artisan route:cache
php artisan storage:link

# 6. Restart supervisord programs (queue + scheduler):
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all

# 7. Under-wraps until Maya production is ready:
php artisan down
```

---

## 🔐 Security Verification Checklist

- [ ] `.env` file NOT visible in public repositories
- [ ] Database credentials rotated
- [ ] API keys are production keys (not sandbox)
- [ ] `APP_DEBUG=false` in production
- [ ] HTTPS enforced (redirect HTTP → HTTPS)
- [ ] SSL certificate valid and not expired
- [ ] Security headers present (check with: `curl -I https://thedailycuts.com`)
- [ ] Rate limiting active
- [ ] Webhook IP validation configured
- [ ] Admin accounts use strong passwords
- [ ] Two-factor authentication enabled for admin accounts (recommended)

---

## 📞 Rollback Plan

If issues occur after deployment:

```bash
# View recent commits:
git log --oneline -10

# Revert to previous version:
git revert <commit-hash>
# OR
git reset --hard <commit-hash>

# Restart services:
php artisan cache:clear
php artisan queue:restart
supervisorctl restart all
```

---

## 📞 Emergency Contacts

- **Maya Support:** https://help.paymaya.com
- **Lalamove Support:** https://help.lalamove.com
- **Laravel Docs:** https://laravel.com/docs
- **Security Issues:** Contact your hosting provider immediately

---

**Last Updated:** 2026-08-27  
**Next Review:** At each deployment
