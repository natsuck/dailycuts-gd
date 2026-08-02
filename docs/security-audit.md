# Security Audit & Remediation Report

Audited by opencode on 2026-08-01. App: The Daily Cuts by GD (Laravel 12 e-commerce).

All items below have been implemented at the code level. Environment secrets (`.env`) were not modified by agreement. A summary of each finding, its severity, and the remediation follows.

## Critical

### Webhook signature verification could be bypassed in local/test mode
**File:** `app/Http/Controllers/Webhook/PaymongoWebhookController.php`

The webhook previously trusted any request when no webhook secret was configured, which would let an attacker spoof `payment.paid` events. Now `hasValidSignature()` returns `false` when the secret is empty, so the endpoint always rejects unsigned or unverifiable requests (HTTP 400). Signed requests are verified with HMAC-SHA256 over the raw body using a replay-proof timestamp.

### TLS certificate verification disabled on outbound HTTP calls
**Files:** `app/Http/Controllers/CheckoutController.php`, `app/Services/LalamoveService.php`

Outbound calls to PayMongo and Lalamove used `Http::withoutVerifying()`, disabling TLS certificate validation. Removed so all outbound traffic is fully verified. Also added a 15-second timeout to Lalamove calls.

## High

### Inventory could oversell (TOCTOU race at checkout)
**Files:** `app/Services/InventoryService.php`, `app/Http/Controllers/CheckoutController.php`, `app/Http/Controllers/Webhook/PaymongoWebhookController.php`

Stock was only decremented when PayMongo confirmed payment, so two concurrent customers could both pass the stock check and oversell. Now stock is reserved (decremented) inside a `DB::transaction` at order creation using `lockForUpdate()` row locks. If the payment gateway call fails, the reservation is released and the order is rolled back; a `payment.failed` webhook or a user checkout cancellation also releases stock. Every reservation writes an `InventoryHistory` row (`type = sale`) referenced to the order. Product-level stock uses `products.product_quantity`; variant-level stock uses `product_variants.quantity`.

### Cross-user data access (IDOR) on cart, review, and order resources
**Files:** `app/Http/Controllers/UserController.php`, `app/Http/Controllers/ReviewController.php`, `app/Http/Controllers/OrderController.php`, `app/Http/Controllers/CheckoutController.php`

Cart remove/update/variant-change, review delete, order show/cancel, and checkout-cancel were all scoped to the authenticated user with ownership checks. Non-owners now receive `404`/`403` instead of operating on someone else's resource.

### Order status could jump through invalid transitions
**File:** `app/Http/Controllers/AdminController.php`

Order status is now constrained to a whitelist (`pending`, `processing`, `shipped`, `delivered`, `cancelled`, `returned`) and an allowed-transition map (e.g. `pending` cannot jump straight to `delivered`).

## Medium

### Cart add/update/change-variant TOCTOU
**File:** `app/Http/Controllers/UserController.php`

Quantity updates were check-then-act without locks. Now wrapped in transactions with `lockForUpdate()` so concurrent updates cannot exceed available stock.

### Missing validation on order report date range
**File:** `app/Http/Controllers/ReportController.php`

`from`/`to` are validated as real dates with `from` before or equal to `to`, and the range is capped at 366 days so a single request cannot scan the full history.

### Unthrottled sensitive endpoints
**Files:** `routes/web.php`, `routes/auth.php`

Added `throttle:5,1` to the password-reset email endpoint and `throttle:30,1` to the shipping-estimate endpoint. Reseller inquiry posts are throttled `10,1`. This limits brute-force and abuse.

### Mass assignment / privilege escalation via `user_type`
**File:** `app/Models/User.php`

`user_type` was in `$fillable`, letting a crafted request escalate a customer to admin. Removed from `$fillable` (and from `UserFactory` defaults where present); admin accounts are set explicitly.

### Stored XSS via reseller and banner input
**Files:** `app/Http/Controllers/UserController.php`, `app/Http/Controllers/AdminSaleBannerController.php`

Reseller name/phone are now validated with strict patterns, banner `button_url` is validated as a URL, and the banner image delete path no longer allows arbitrary path traversal.

## Low

### Coupon value limits
**File:** `app/Http/Controllers/CouponController.php`

Coupon `value` is capped (100 for percentage, 1,000,000 for fixed amount) on both create and update.

### Order-item integrity
**File:** `database/migrations/2026_08_01_000003_add_integrity_constraints_to_store_tables.php`

`order_items` now records the `variant_id` used and has a foreign key to `product_variants` (`nullOnDelete`), plus FKs to `orders` (cascade delete) and `products` (restrict). Duplicate rows are deduplicated, and unique indexes prevent duplicate cart rows and duplicate variant weights.

### Sensitive data in logs
**Files:** `app/Http/Controllers/CheckoutController.php`, `app/Services/LalamoveService.php`

Raw Lalamove bodies (customer addresses) and PayMongo billing details are redacted before being logged.

## Test Coverage

Feature tests added in `tests/Feature/` covering cart CRUD and ownership, stock reservation and release at checkout, webhook signature validation and paid/failed flows with idempotency, order scoping, review ownership, admin authorization, order status transitions, and rate-limited endpoints. `php artisan test` passes (70 tests, 194 assertions).

## Still Recommended (not changed)

- Set `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, and strong `LOG_LEVEL` in production. See `docs/production-deployment-checklist.md`.
- Replace the real PayMongo/Lalamove test keys in `.env` with production keys at launch.
- Configure a real queue worker (`QUEUE_CONNECTION=sync` is used during development).
- Do not run `php artisan config:cache` on a development machine while running the test suite: the cached config bakes in the live DB connection and environment, which causes tests to hit the real database instead of the SQLite test database. Run `php artisan config:clear` before `php artisan test`.
- Back up the MySQL database (`mysqldump`) before any migration or test run that touches the live connection.
