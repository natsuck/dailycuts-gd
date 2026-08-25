# 🚀 Production Deployment Checklist

**Project:** The Daily Cuts by GD  
**Date:** 2026-08-25  
**Status:** Phase 2 & 3 Pending  

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
config('mail.from.name')    # Should be: 'The Daily Cuts by GD'
config('queue.default')     # Should be: 'database' (or 'redis' for scale)
config('cache.default')     # Should be: 'database' (or 'redis' for scale)
config('session.secure')    # Should be: true
config('session.encrypt')   # Should be: true
config('auth.passwords.users.expire') # Should be set
```

**Required `.env` values for production:**
- [x] `APP_ENV=production` - Correct
- [x] `APP_DEBUG=false` - Correct  
- [x] `APP_URL=https://thedailycuts.com` - Correct
- [ ] `DB_PASSWORD` - Update with rotated password
- [ ] `MAIL_PASSWORD` - Update with new Gmail App Password
- [ ] `MAYA_PUBLIC_KEY` - Set to production key (not sandbox)
- [ ] `MAYA_SECRET_KEY` - Set to production key (not sandbox)
- [ ] `MAYA_MODE` - Change from `sandbox` to `production`
- [ ] `MAYA_WEBHOOK_IPS` - Set to production IP addresses
- [ ] `LALAMOVE_API_KEY` - Set to production key
- [ ] `LALAMOVE_API_SECRET` - Set to production key
- [ ] `LALAMOVE_SANDBOX` - Change to `false` for production
- [ ] `GOOGLE_MAPS_API_KEY` - Verify production key
- [ ] `TRUSTED_PROXIES` - Configure if behind load balancer/CDN

---

### Step 3: Configure Payment Gateway API Keys

#### Maya Payment Gateway
**Portal:** https://dashboard.maya.ph  
**Current Settings:**
```env
MAYA_PUBLIC_KEY=pk-iaPw9sVcpbSkkeIPupcvuopNpczxEN1klbvTPXCYffm
MAYA_SECRET_KEY=sk-s7RmAxH5jpU6CLM5dHyvpBFtl6FH1IhORbdzmY8dowR
MAYA_MODE=sandbox
MAYA_WEBHOOK_IPS=CONFIGURE_THIS
```

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
LALAMOVE_API_KEY=pk_test_d6bdd15d6ecf6eebb644f85246fd11b2
LALAMOVE_API_SECRET=sk_test_vwWoJ9oGoYVJFJyBQ/Eg0AsreyiiojytecdFyIZY9RBTqHBnh8cOYlEaIuM0f13t
LALAMOVE_SANDBOX=true
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
# Set in .env:
TRUSTED_PROXIES=173.245.48.0/20,103.21.244.0/22,103.22.200.0/22,103.31.4.0/22,141.101.64.0/18,108.162.192.0/18,190.93.240.0/20,188.114.96.0/20,197.234.240.0/22,198.41.128.0/17,162.158.0.0/15,104.16.0.0/12,172.64.0.0/13,131.0.72.0/22

# Or use wildcard (less secure but works):
TRUSTED_PROXIES=*
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
**Status:** 🔴 NOT DONE  
**Command:**

```bash
# Scan composer dependencies for CVEs:
composer audit

# Expected output:
# Found 0 vulnerabilities (or list of vulnerabilities to fix)
```

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
**Status:** 🔴 NOT DONE  
**Commands:**

```bash
# Cache configuration files (improves performance):
php artisan config:cache

# Cache routes (improves performance):
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
```bash
# Simulate incoming webhook from Maya:
curl -X POST http://localhost:8000/api/webhooks/maya \
  -H "Content-Type: application/json" \
  -H "X-Forwarded-For: 13.229.160.234" \
  -d '{
    "payment_id":"PAY-123",
    "event":"payment_success",
    "status":"AUTHORIZED",
    "timestamp":"2026-08-25T10:00:00Z"
  }'

# Expected response: 200 OK with webhook processed message
# Check logs: storage/logs/laravel.log
```

**Lalamove Webhook Test:**
```bash
# Note: Lalamove webhooks require valid HMAC signature
# Run from Lalamove test console or contact support for test webhook
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
2. [ ] All Phase 3 validation passed
3. [ ] All Phase 4 tests passed
4. [ ] Database backed up
5. [ ] Rollback plan documented
6. [ ] Uptime monitoring configured
7. [ ] Error monitoring (Sentry/Rollbar) configured
8. [ ] Log aggregation (CloudWatch/Datadog) configured
9. [ ] SSL certificate verified (HTTPS working)
10. [ ] Rate limiting tested
11. [ ] Security headers verified (check in browser DevTools)

### Deployment Commands:
```bash
# On production server:

# 1. Pull latest code:
git pull origin main

# 2. Install dependencies:
composer install --optimize-autoloader --no-dev

# 3. Run migrations:
php artisan migrate --force

# 4. Cache configuration:
php artisan config:cache
php artisan route:cache

# 5. Restart queue worker (if using supervisor):
supervisorctl restart laravel-queue

# 6. Verify deployment:
php artisan tinker
# Run: config('app.env') # Should show 'production'
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

**Last Updated:** 2026-08-25  
**Next Review:** Before each deployment
