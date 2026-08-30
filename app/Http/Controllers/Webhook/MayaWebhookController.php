<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\DispatchLalamoveDelivery;
use App\Mail\OrderConfirmationMail;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\MayaWebhookEvent;
use App\Models\Order;
use App\Services\InventoryService;
use App\Services\MayaReconciliationService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Handles Maya Checkout webhooks.
 *
 * Maya Checkout webhooks are NOT signed. Requests are only accepted when they
 * come from Maya's official webhook IP ranges (config('maya.webhook_ips')).
 *
 * The payload mirrors one of two documented shapes; both are parsed by
 * MayaPaymentConfirmationService:
 *   - GET Payment response: events in `status` (PAYMENT_SUCCESS, ...)
 *   - Get Checkout response: record state in `status` (COMPLETED/EXPIRED) and
 *     the event in `paymentStatus`
 */
class MayaWebhookController extends Controller
{
    public function handle(Request $request, InventoryService $inventory, MayaReconciliationService $reconciliation)
    {
        if (! $this->hasAllowedIp($request)) {
            Log::warning('Rejected Maya webhook from an unlisted IP.', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['status' => 'invalid source'], 400);
        }

        $payload = $request->input();

        // Both documented payload shapes carry an `id` plus a `status` (GET
        // Payment shape) or a `paymentStatus` (Get Checkout shape).
        if (! is_array($payload) || ! isset($payload['id'])
            || (! isset($payload['status']) && ! isset($payload['paymentStatus']))) {
            return response()->json(['status' => 'invalid payload'], 400);
        }

        // Prefer paymentStatus: the Get Checkout shaped body carries a record
        // state in `status` (COMPLETED/EXPIRED) and the event in `paymentStatus`.
        $status = strtoupper((string) ($payload['paymentStatus'] ?? $payload['status']));

        // Events outside this set (e.g. AUTHORIZED, PAYMENT_PROCESSING, VOIDED,
        // REFUNDED) are acknowledged but not acted upon.
        if (! in_array($status, ['PAYMENT_SUCCESS', 'PAYMENT_FAILED', 'PAYMENT_EXPIRED', 'PAYMENT_CANCELLED'], true)) {
            return response()->json(['status' => 'ignored']);
        }

        $requestReference = (string) ($payload['requestReferenceNumber'] ?? '');

        if ($requestReference === '') {
            Log::info('Maya webhook did not carry a request reference number.');

            return response()->json(['status' => 'ignored']);
        }

        $order = Order::where('payment_request_reference', $requestReference)->first();

        if (! $order) {
            Log::info('Maya webhook did not match an order.', [
                'request_reference' => $requestReference,
            ]);

            return response()->json(['status' => 'ignored']);
        }

        $paymentId = (string) $payload['id'];
        $isDuplicate = false;
        $confirmedOrderId = null;

        DB::transaction(function () use ($order, $inventory, $reconciliation, $payload, $status, $paymentId, $requestReference, &$isDuplicate, &$confirmedOrderId) {
            // Record the event first. The unique (payment_id, event) constraint
            // makes any re-delivery of the same event a no-op regardless of
            // order state; the order-state guards below remain as a second
            // line of defence.
            try {
                MayaWebhookEvent::create([
                    'payment_id' => $paymentId,
                    'event' => $status,
                    'order_id' => $order->id,
                    'request_reference_number' => $requestReference,
                    'payload' => $payload,
                ]);
            } catch (UniqueConstraintViolationException $e) {
                $isDuplicate = true;

                return;
            }

            if ($status === 'PAYMENT_SUCCESS') {
                // The webhook is unsigned. Before marking the order paid,
                // verify server-to-server with Maya's Get Checkout API so a
                // spoofed/forged webhook cannot confirm a payment that never
                // happened. Verification returns null when Maya does not
                // confirm (or the query is inconclusive) -- the scheduled
                // reconciler will retry and pick up any genuine payment.
                $confirmedOrderId = $reconciliation->verifyWebhook($order);

                return;
            }

            $lockedOrder = Order::with('items')->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->payment_status === 'paid' || $lockedOrder->status === 'cancelled') {
                return;
            }

            $inventory->release($lockedOrder);

            $usage = CouponUsage::where('order_id', $lockedOrder->id)->first();
            if ($usage) {
                Coupon::whereKey($usage->coupon_id)->decrement('used_count');
                $usage->delete();
            }

            $lockedOrder->payment_status = 'failed';
            $lockedOrder->status = 'cancelled';
            $lockedOrder->save();
        });

        if ($confirmedOrderId) {
            $this->sendOrderConfirmation($confirmedOrderId);
            $this->dispatchLalamove($confirmedOrderId);
        }

        return response()->json(['status' => 'ok', 'duplicate' => $isDuplicate]);
    }

    protected function sendOrderConfirmation(int $orderId): void
    {
        $order = Order::with(['items', 'user'])->find($orderId);

        if (! $order || ! $order->user) {
            return;
        }

        try {
            Mail::to($order->user->email)->queue(new OrderConfirmationMail($order));
        } catch (\Throwable $e) {
            Log::error('Failed to queue the order confirmation email.', [
                'order_id' => $order->id,
                'recipient' => $order->user->email,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    protected function dispatchLalamove(int $orderId): void
    {
        DispatchLalamoveDelivery::dispatch($orderId);
    }

    protected function hasAllowedIp(Request $request): bool
    {
        $allowed = (array) config('maya.webhook_ips', []);

        return in_array($request->ip(), $allowed, true);
    }
}
