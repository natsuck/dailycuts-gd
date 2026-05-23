<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymongoWebhookController extends Controller
{
    public function handle(Request $request)
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

        if (! in_array($eventType, ['checkout_session.payment.paid', 'payment.paid', 'payment.failed'], true)) {
            return response()->json(['status' => 'ignored']);
        }

        [$order, $paymentId, $paymentMethod] = $this->resolveOrderContext($payload, $eventType);

        if (! $order) {
            Log::info('PayMongo webhook did not match an order.');

            return response()->json(['status' => 'ignored']);
        }

        if ($order->payment_status === 'paid' && $eventType !== 'payment.failed') {
            return response()->json(['status' => 'already processed']);
        }

        if ($eventType === 'checkout_session.payment.paid' || $eventType === 'payment.paid') {
            DB::transaction(function () use ($order, $paymentId, $paymentMethod) {
                $lockedOrder = Order::with('items')->lockForUpdate()->findOrFail($order->id);

                if ($lockedOrder->payment_status === 'paid') {
                    return;
                }

                foreach ($lockedOrder->items as $item) {
                    $product = Product::lockForUpdate()->find($item->product_id);

                    if (! $product || $product->product_quantity < $item->quantity) {
                        throw new \RuntimeException("Insufficient stock for order {$lockedOrder->id}.");
                    }
                }

                foreach ($lockedOrder->items as $item) {
                    Product::whereKey($item->product_id)->decrement('product_quantity', $item->quantity);
                }

                $lockedOrder->payment_status = 'paid';
                $lockedOrder->payment_method = $paymentMethod;
                $lockedOrder->payment_intent_id = $paymentId;
                $lockedOrder->save();

                Cart::where('user_id', $lockedOrder->user_id)->delete();
            });
        }

        if ($eventType === 'payment.failed' && $order->payment_status !== 'paid') {
            $order->payment_status = 'failed';
            $order->status = 'cancelled';
            $order->save();
        }

        return response()->json(['status' => 'ok']);
    }

    protected function hasValidSignature(Request $request, string $payloadRaw, array $payload): bool
    {
        $secret = (string) env('PAYMONGO_WEBHOOK_SECRET', '');

        if ($secret === '') {
            return app()->environment('local');
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

        $computedSignature = hash_hmac('sha256', $timestamp.'.'.$payloadRaw, $secret);

        return hash_equals($expectedSignature, $computedSignature);
    }

    protected function resolveOrderContext(array $payload, string $eventType): array
    {
        $resource = $payload['data']['attributes']['data'] ?? [];
        $attributes = $resource['attributes'] ?? [];

        if ($eventType === 'checkout_session.payment.paid') {
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
