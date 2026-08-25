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

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Basic '.base64_encode((string) config('maya.secret_key').':'),
                    'Accept' => 'application/json',
                ])
                ->get(config('maya.base_url').'/checkout/v1/checkouts/'.$order->checkout_session_id);

            if (! $response->successful()) {
                Log::warning('Maya Get Checkout reconciliation failed', [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return self::ERROR;
            }

            $payload = $response->json();

            if (! is_array($payload)) {
                return self::ERROR;
            }

            $confirmedOrderId = app(MayaPaymentConfirmationService::class)->confirm($order, $payload);

            return $confirmedOrderId ? self::CONFIRMED : self::UNPAID;
        } catch (\Exception $e) {
            Log::error('Maya Get Checkout reconciliation exception', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            return self::ERROR;
        }
    }
}
