# Maya Production Compliance Pass

Goal: make the Maya Checkout integration fully conform to Maya's documented
contract before the user swaps the `.env` sandbox keys for live production keys.

Status: approved (all changes). Implementation pending (plan mode active).

## Background

The Maya sandbox was returning 504s (Maya-side outage). Retry logic + clearer
error messages were already added and merged (CheckoutController retry loop,
config checkout_retries/checkout_retry_delay_ms, 3 new CheckoutTest tests, full
suite green). The user then asked to verify the integration against the official
Maya docs and make it compliant for production.

Every load-bearing part was already verified correct against the docs:
- Create Checkout authenticates with the public key (Basic auth).
- totalAmount.value, items[].amount.value are numeric; currency PHP.
- requestReferenceNumber = UUID (36 chars, <= 36 max, unique per attempt).
- Webhook success payload: paymentStatus PAYMENT_SUCCESS, totalAmount.value
  (string), currency under totalAmount.currency, no isPaid -> service defaults
  handle it. The service keys off `paymentStatus ?? status`, which is required
  because the CHECKOUT_FAILURE sample has `status: COMPLETED`.
- Failure/expired/cancel payloads (top-level amount, isPaid:false) are handled
  by MayaWebhookController (cancel + stock release).
- Get Checkout reconciliation uses the secret key (matches docs; deprecated but
  functional).

## Changes to implement

### 1. config/maya.php

- Make `base_url` overridable: add `MAYA_BASE_URL` env override, keeping the
  per-mode default (`pg.paymaya.com` prod / `pg-sandbox.paymaya.com` sandbox).
- Make `webhook_ips` mode-aware: when `MAYA_WEBHOOK_IPS` is unset, use the
  sandbox IPs (13.229.160.234, 3.1.199.75) in sandbox mode and the production
  IPs (18.138.50.235, 3.1.207.200) in production mode. (Current default is the
  sandbox set, which would silently reject every real webhook in production.)
- Update the NOTE comment about the pg.maya.ph / pg.paymaya.com discrepancy to
  reference MAYA_BASE_URL.

### 2. .env.example

- Set MAYA_WEBHOOK_IPS to empty with a comment explaining the per-mode default
  and the production IPs (18.138.50.235, 3.1.207.200). (Leaving the sandbox
  values set would override the mode-aware default with sandbox IPs in prod.)
- Add commented MAYA_BASE_URL override with the host note.

### 3. app/Http/Controllers/CheckoutController.php (lines ~403-407)

- Send totalAmount.details (subtotal, discount, shippingFee) as two-decimal
  numeric strings (e.g. "200.00") to match the OpenAPI spec and the webhook
  response format. Keep totalAmount.value and items values numeric (the sandbox
  validator rejected string values there).

### 4. app/Services/MayaPaymentConfirmationService.php (amountFrom, lines ~89-98)

- Add a fallback to `totalAmount.amount` when `totalAmount.value` is absent, to
  cover the failure/expired webhook samples that use the `amount` key (defensive
  only; those payloads never reach the PAYMENT_SUCCESS confirm path today).

### 5. tests/Feature/CheckoutTest.php (lines ~800-801)

- Update the request-body assertions so details subtotal/shippingFee expect
  strings ("200.00", "150.00") instead of floats. totalAmount.value and item
  amount assertions stay numeric.

### 6. Verify

- Run `php vendor/bin/pint --test`.
- Run the full suite (`php artisan test`).

## Go-live checklist (user, after switching keys)

- Set MAYA_PUBLIC_KEY / MAYA_SECRET_KEY to the live keys.
- Set MAYA_MODE=production.
- Set MAYA_WEBHOOK_IPS=18.138.50.235,3.1.207.200 (or leave unset and rely on the
  mode-aware default).
- Set APP_URL to the real production domain (currently a ngrok URL).
- Register the webhook URL https://<domain>/maya/webhook in the Maya Business
  Portal.
- Set MAYA_BASE_URL only if the first live Create Checkout call fails on
  pg.paymaya.com and succeeds on pg.maya.ph.
- Run one real checkout to confirm host + details-as-strings are accepted and
  the webhook arrives.
