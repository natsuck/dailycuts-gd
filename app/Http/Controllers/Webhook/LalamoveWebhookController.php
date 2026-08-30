<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Mail\OrderDeliveredMail;
use App\Mail\OrderShippedMail;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LalamoveWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payloadRaw = $request->getContent();
        $payload = $request->json()->all();

        if (! $this->verifyPayload($request, $payloadRaw, $payload)) {
            // Unverified request. In strict mode we already aborted with 401.
            // In permissive (capture) mode we return a 200 so Lalamove accepts
            // the registration / validation ping while we learn its real format.
            return response()->json(['status' => 'ignored']);
        }

        $eventName = $payload['eventName'] ?? null;
        $data = $payload['data'] ?? [];

        if (! in_array($eventName, ['ORDER_STATUS_CHANGED', 'DRIVER_ASSIGNED'], true)) {
            return response()->json(['status' => 'ignored']);
        }

        $lalamoveOrderId = $data['orderId'] ?? null;

        if (! $lalamoveOrderId) {
            return response()->json(['status' => 'ignored']);
        }

        $order = Order::where('lalamove_order_id', $lalamoveOrderId)->first();

        if (! $order) {
            Log::info('Lalamove webhook did not match an order.', [
                'lalamove_order_id' => $lalamoveOrderId,
                'event' => $eventName,
            ]);

            return response()->json(['status' => 'ignored']);
        }

        $this->syncOrder($order, $data, $eventName);

        return response()->json(['status' => 'ok']);
    }

    protected function syncOrder(Order $order, array $data, ?string $eventName = null): void
    {
        DB::transaction(function () use ($order, $data, $eventName) {
            $locked = Order::lockForUpdate()->findOrFail($order->id);

            $incoming = ! empty($data['updatedAt']) ? Carbon::parse($data['updatedAt']) : now();

            $last = $locked->last_lalamove_webhook_at;

            if ($last && $incoming->lte($last)) {
                Log::info('Lalamove webhook ignored as out of order.', [
                    'order_id' => $locked->id,
                    'lalamove_order_id' => $locked->lalamove_order_id,
                    'event' => $eventName,
                    'incoming_updated_at' => $incoming->toIso8601String(),
                    'last_webhook_at' => $last->toIso8601String(),
                ]);

                return;
            }

            $locked->last_lalamove_webhook_at = $incoming;

            $wasDelivered = $locked->status === 'delivered';
            $wasDriverAssigned = $locked->lalamove_driver_name !== null;
            $status = $data['status'] ?? null;

            if ($eventName === 'DRIVER_ASSIGNED') {
                $driver = $data['driver'] ?? [];

                if (is_array($driver)) {
                    $locked->lalamove_driver_name = $driver['name'] ?? $locked->lalamove_driver_name;
                    $locked->lalamove_driver_phone = $driver['phone'] ?? $locked->lalamove_driver_phone;
                }
            }

            if ($status) {
                $locked->lalamove_status = $status;
                $locked->delivery_status = strtolower($status);
            }

            if ($status === 'COMPLETED' && $locked->status !== 'delivered') {
                $locked->status = 'delivered';
            }

            if ($status === 'FAILED' && ! in_array($locked->status, ['delivered', 'cancelled', 'failed'], true)) {
                $locked->status = 'failed';
            }

            if (in_array($status, ['CANCELED', 'CANCELLED', 'EXPIRED', 'REJECTED'], true)
                && ! in_array($locked->status, ['delivered', 'cancelled', 'failed'], true)) {
                $locked->status = 'cancelled';
            }

            $locked->save();

            $nowDelivered = $locked->status === 'delivered' && ! $wasDelivered;
            $nowShipped = $eventName === 'DRIVER_ASSIGNED' && ! $wasDriverAssigned && ! $nowDelivered;

            if ($nowShipped) {
                $this->notifyOrder($locked, OrderShippedMail::class);
            }

            if ($nowDelivered) {
                $this->notifyOrder($locked, OrderDeliveredMail::class);
            }

            Log::info('Lalamove order status updated.', [
                'order_id' => $locked->id,
                'lalamove_order_id' => $locked->lalamove_order_id,
                'lalamove_status' => $status,
                'order_status' => $locked->status,
                'now_shipped' => $nowShipped,
                'now_delivered' => $nowDelivered,
            ]);
        });
    }

    /**
     * Queue a customer-facing order email, guarded so orders without a linked
     * user never break the webhook.
     */
    protected function notifyOrder(Order $order, string $mailableClass): void
    {
        $user = $order->user;

        if (! $user || ! $user->email) {
            return;
        }

        try {
            Mail::to($user->email)->queue(new $mailableClass($order));
        } catch (\Throwable $e) {
            Log::error('Failed to queue the order status email.', [
                'order_id' => $order->id,
                'mailable' => $mailableClass,
                'recipient' => $user->email,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    protected function verifyPayload(Request $request, string $payloadRaw, array $payload): bool
    {
        $failureReason = null;
        $apiKey = $payload['apiKey'] ?? null;

        if ($apiKey === null || ! hash_equals((string) config('services.lalamove.key', ''), (string) $apiKey)) {
            $failureReason = 'apiKey mismatch';
        }

        $secret = (string) config('services.lalamove.secret', '');

        if ($secret === '') {
            $failureReason = 'no API secret configured';
        }

        $providedSignature = $request->header('X-Lalamove-Signature', '');

        if ($providedSignature === '') {
            $failureReason ??= 'missing signature header';
        }

        if ($failureReason === null) {
            $path = $request->getPathInfo();
            $computedSignature = base64_encode(hash_hmac('sha256', $payloadRaw.$path, $secret, true));

            if (! hash_equals($providedSignature, $computedSignature)) {
                $failureReason = 'signature mismatch';
            }
        }

        if ($failureReason === null) {
            return true;
        }

        if (config('services.lalamove.webhook_permissive', false)) {
            Log::warning('Lalamove webhook UNVERIFIED (permissive mode) - capturing request.', [
                'reason' => $failureReason,
                'method' => $request->method(),
                'path' => $request->getPathInfo(),
                'headers' => $request->headers->all(),
                'raw_body' => $payloadRaw,
                'payload' => $payload,
                'remote_ip' => $request->ip(),
            ]);

            return false;
        }

        Log::warning('Rejected Lalamove webhook due to '.$failureReason.'.');

        abort(401, 'Invalid Lalamove webhook signature.');
    }
}
