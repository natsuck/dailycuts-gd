<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reconciles a pending unpaid order against Maya's Get Checkout API.
 *
 * The webhook remains authoritative; this closes the gap when a webhook is
 * lost or delayed (e.g. the customer closed the browser before the success
 * redirect fired). Shared by the checkout success page and the scheduled
 * reconciler / expiry command so every path uses identical logic.
 */
class MayaReconciliationService
{
    /** The order was confirmed as paid (or was already paid). */
    public const CONFIRMED = 'confirmed';

    /** Maya definitively reports the payment is not successful. */
    public const UNPAID = 'unpaid';

    /** The answer from Maya was inconclusive (HTTP/network/malformed error). */
    public const ERROR = 'error';

    public function reconcile(Order $order): string
    {
        if (! $order->checkout_session_id) {
            return self::ERROR;
        }

        $payload = $this->fetchCheckout($order);

        if ($payload === null) {
            return self::ERROR;
        }

        $confirmedOrderId = app(MayaPaymentConfirmationService::class)->confirm($order, $payload);

        return $confirmedOrderId ? self::CONFIRMED : self::UNPAID;
    }

    /**
     * Server-to-server verification of a PAYMENT_SUCCESS webhook.
     *
     * The webhook is unsigned, so a successful webhook alone is not enough to
     * trust that money actually changed hands. This queries Maya's Get Checkout
     * API (authenticated with the secret key) and only confirms the order paid
     * when Maya itself reports the checkout COMPLETED / PAYMENT_SUCCESS and the
     * amount and currency match the order. Returns the newly-confirmed order id,
     * or null when Maya does not confirm the payment (including inconclusive
     * errors, which should not silently confirm an order).
     */
    public function verifyWebhook(Order $order): ?int
    {
        $payload = $this->fetchCheckout($order);

        if ($payload === null) {
            return null;
        }

        return app(MayaPaymentConfirmationService::class)->confirm($order, $payload);
    }

    /**
     * Fetches the authoritative checkout state from Maya, or null on any error.
     */
    protected function fetchCheckout(Order $order): ?array
    {
        if (! $order->checkout_session_id) {
            return null;
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Basic '.base64_encode((string) config('maya.secret_key').':'),
                    'Accept' => 'application/json',
                ])
                ->get(config('maya.base_url').'/checkout/v1/checkouts/'.$order->checkout_session_id);

            if (! $response->successful()) {
                Log::warning('Maya Get Checkout query failed', [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return null;
            }

            $payload = $response->json();

            return is_array($payload) ? $payload : null;
        } catch (\Exception $e) {
            Log::error('Maya Get Checkout query exception', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
