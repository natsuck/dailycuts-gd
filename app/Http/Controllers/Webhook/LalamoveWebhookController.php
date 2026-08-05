<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LalamoveWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payloadRaw = $request->getContent();
        $payload = $request->json()->all();

        $this->verifyPayload($request, $payloadRaw, $payload);

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

        $this->syncOrder($order, $data);

        return response()->json(['status' => 'ok']);
    }

    protected function syncOrder(Order $order, array $data): void
    {
        $status = $data['status'] ?? null;

        if ($status) {
            $order->lalamove_status = $status;
            $order->delivery_status = strtolower($status);
            $order->save();
        }

        if ($status === 'COMPLETED' && $order->status !== 'delivered') {
            $order->status = 'delivered';
            $order->save();
        }

        if (in_array($status, ['CANCELED', 'CANCELLED', 'EXPIRED', 'REJECTED'], true)
            && ! in_array($order->status, ['delivered', 'cancelled'], true)) {
            $order->status = 'cancelled';
            $order->save();
        }

        Log::info('Lalamove order status updated.', [
            'order_id' => $order->id,
            'lalamove_order_id' => $order->lalamove_order_id,
            'lalamove_status' => $status,
            'order_status' => $order->status,
        ]);
    }

    protected function verifyPayload(Request $request, string $payloadRaw, array $payload): void
    {
        $apiKey = $payload['apiKey'] ?? null;

        if ($apiKey === null || ! hash_equals((string) config('services.lalamove.key', ''), (string) $apiKey)) {
            Log::warning('Rejected Lalamove webhook due to apiKey mismatch.');

            abort(401, 'Invalid Lalamove webhook signature.');
        }

        $secret = (string) config('services.lalamove.secret', '');

        if ($secret === '') {
            Log::warning('Rejected Lalamove webhook because no API secret is configured.');

            abort(401, 'Invalid Lalamove webhook signature.');
        }

        $providedSignature = $request->header('X-Lalamove-Signature', '');

        if ($providedSignature === '') {
            Log::warning('Rejected Lalamove webhook due to a missing signature header.');

            abort(401, 'Invalid Lalamove webhook signature.');
        }

        $path = $request->getPathInfo();
        $computedSignature = base64_encode(hash_hmac('sha256', $payloadRaw.$path, $secret, true));

        if (! hash_equals($providedSignature, $computedSignature)) {
            Log::warning('Rejected Lalamove webhook due to a signature mismatch.');

            abort(401, 'Invalid Lalamove webhook signature.');
        }
    }
}
