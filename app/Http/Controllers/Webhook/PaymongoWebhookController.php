<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Services\InventoryService;
use App\Services\LalamoveDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymongoWebhookController extends Controller
{
    public function handle(Request $request, InventoryService $inventory)
    {
        $payloadRaw = $request->getContent();
        $payload = json_decode($payloadRaw, true);

        if (! is_array($payload)) {
            return response()->json(['status' => 'invalid payload'], 400);
        }

        if (! $this->hasValidSignature($request, $payloadRaw, $payload)) {
            Log::warning('Rejected PayMongo webhook due to invalid signature.');

            return response()->json(['status' => 'invalid signature'], 400);
        }

        $eventType = $payload['data']['attributes']['type'] ?? null;

        if (! in_array($eventType, ['checkout_session.payment.paid', 'payment.paid', 'payment.failed', 'checkout_session.expired'], true)) {
            return response()->json(['status' => 'ignored']);
        }

        [$order, $paymentId, $paymentMethod] = $this->resolveOrderContext($payload, $eventType);

        if (! $order) {
            Log::info('PayMongo webhook did not match an order.');

            return response()->json(['status' => 'ignored']);
        }

        $dispatchedOrderId = null;

        if ($eventType === 'checkout_session.payment.paid' || $eventType === 'payment.paid') {
            $resource = $payload['data']['attributes']['data'] ?? [];
            $resourceAttributes = $resource['attributes'] ?? [];

            DB::transaction(function () use ($order, $paymentId, $paymentMethod, $eventType, $resourceAttributes, &$dispatchedOrderId) {
                $lockedOrder = Order::with('items')->lockForUpdate()->findOrFail($order->id);

                if ($lockedOrder->payment_status === 'paid') {
                    return;
                }

                if ($lockedOrder->status === 'cancelled') {
                    Log::warning('Ignored payment confirmation for a cancelled order.', [
                        'order_id' => $lockedOrder->id,
                    ]);

                    return;
                }

                $expectedAmount = (int) round($lockedOrder->total * 100);
                $paidAmount = (int) ($resourceAttributes['amount'] ?? -1);
                $paidCurrency = strtoupper((string) ($resourceAttributes['currency'] ?? ''));

                if ($paidAmount !== $expectedAmount || $paidCurrency !== 'PHP') {
                    Log::warning('Rejected payment confirmation due to an amount/currency mismatch.', [
                        'order_id' => $lockedOrder->id,
                        'order_total' => $lockedOrder->total,
                        'paid_amount' => $paidAmount,
                        'paid_currency' => $paidCurrency,
                    ]);

                    return;
                }

                if ($eventType === 'payment.paid' && $lockedOrder->checkout_session_id) {
                    $paidSessionId = $resourceAttributes['checkout_session_id'] ?? null;

                    if ($paidSessionId !== null && $paidSessionId !== $lockedOrder->checkout_session_id) {
                        Log::warning('Rejected payment confirmation due to a checkout session mismatch.', [
                            'order_id' => $lockedOrder->id,
                        ]);

                        return;
                    }
                }

                $lockedOrder->payment_status = 'paid';
                $lockedOrder->payment_method = $paymentMethod;
                $lockedOrder->payment_intent_id = $paymentId;
                $lockedOrder->save();

                Cart::where('user_id', $lockedOrder->user_id)->delete();

                $dispatchedOrderId = $lockedOrder->id;
            });
        }

        if ($dispatchedOrderId) {
            $this->dispatchLalamove($dispatchedOrderId);
        }

        if ($eventType === 'payment.failed' || $eventType === 'checkout_session.expired') {
            DB::transaction(function () use ($order, $inventory) {
                $lockedOrder = Order::with('items')->lockForUpdate()->findOrFail($order->id);

                if ($lockedOrder->payment_status === 'paid' || $lockedOrder->status === 'cancelled') {
                    return;
                }

                $inventory->release($lockedOrder);
                $lockedOrder->payment_status = 'failed';
                $lockedOrder->status = 'cancelled';
                $lockedOrder->save();
            });
        }

        return response()->json(['status' => 'ok']);
    }

    protected function dispatchLalamove(int $orderId): void
    {
        try {
            app(LalamoveDeliveryService::class)->dispatch(Order::findOrFail($orderId));
        } catch (\Exception $e) {
            Log::error('Lalamove dispatch exception', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected function hasValidSignature(Request $request, string $payloadRaw, array $payload): bool
    {
        $secret = (string) config('paymongo.webhook_secret', '');

        if ($secret === '') {
            return false;
        }

        $signatureHeader = $request->header('Paymongo-Signature', '');

        if ($signatureHeader === '') {
            return false;
        }

        $parts = [];

        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);

            if ($key !== null) {
                $parts[$key] = $value;
            }
        }

        $timestamp = $parts['t'] ?? null;
        $expectedSignature = ($payload['data']['attributes']['livemode'] ?? false)
            ? ($parts['li'] ?? null)
            : ($parts['te'] ?? null);

        if (! $timestamp || ! $expectedSignature) {
            return false;
        }

        if (abs(now()->getTimestamp() - (int) $timestamp) > 300) {
            return false;
        }

        $computedSignature = hash_hmac('sha256', $timestamp.'.'.$payloadRaw, $secret);

        return hash_equals($expectedSignature, $computedSignature);
    }

    protected function resolveOrderContext(array $payload, string $eventType): array
    {
        $resource = $payload['data']['attributes']['data'] ?? [];
        $attributes = $resource['attributes'] ?? [];

        if ($eventType === 'checkout_session.payment.paid' || $eventType === 'checkout_session.expired') {
            $checkoutSessionId = $resource['id'] ?? null;
            $order = $checkoutSessionId
                ? Order::where('checkout_session_id', $checkoutSessionId)->first()
                : null;

            return [
                $order,
                data_get($attributes, 'payments.0.id', $checkoutSessionId),
                data_get($attributes, 'payments.0.attributes.source.type', 'checkout'),
            ];
        }

        $orderId = $attributes['metadata']['order_id'] ?? null;
        $order = $orderId ? Order::find($orderId) : null;

        return [
            $order,
            $resource['id'] ?? null,
            data_get($attributes, 'source.type', 'unknown'),
        ];
    }
}
