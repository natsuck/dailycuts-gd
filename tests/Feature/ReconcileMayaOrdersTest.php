<?php

use App\Jobs\DispatchLalamoveDelivery;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

function pendingOrderWithSession(array $attributes = []): Order
{
    $suffix = (string) Str::uuid();

    return Order::factory()->create(array_replace([
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'total' => 350.00,
        'checkout_session_id' => 'cs_sweep_'.$suffix,
        'payment_request_reference' => 'rrn_sweep_'.$suffix,
    ], $attributes));
}

/**
 * Get Checkout API response body, the same shape the reconciliation
 * service receives when polling Maya for an order's payment state.
 */
function mayaGetCheckoutResponse(Order $order, string $paymentStatus, array $overrides = []): array
{
    $recordState = match ($paymentStatus) {
        'PAYMENT_SUCCESS' => 'COMPLETED',
        'PENDING_PAYMENT' => 'PENDING_PAYMENT',
        default => 'EXPIRED',
    };

    return array_replace_recursive([
        'id' => $order->checkout_session_id,
        'status' => $recordState,
        'paymentStatus' => $paymentStatus,
        'requestReferenceNumber' => $order->payment_request_reference,
        'totalAmount' => [
            'value' => number_format((float) $order->total, 2, '.', ''),
            'currency' => 'PHP',
        ],
        'paymentScheme' => 'master-card',
    ], $overrides);
}

beforeEach(function () {
    Queue::fake();
    config(['maya.secret_key' => 'sk_test']);
});

test('sweeper confirms a paid order whose webhook was lost', function () {
    $order = pendingOrderWithSession();
    $order->created_at = now()->subMinutes(10);
    $order->save();

    Http::fake([
        'pg-sandbox.paymaya.com/checkout/v1/checkouts/*' => Http::response(
            mayaGetCheckoutResponse($order, 'PAYMENT_SUCCESS'),
            200,
        ),
    ]);

    $this->artisan('orders:reconcile-maya')->assertSuccessful();

    expect($order->refresh()->payment_status)->toBe('paid');
    Queue::assertPushed(DispatchLalamoveDelivery::class, fn (DispatchLalamoveDelivery $job) => $job->orderId === $order->id);
});

test('sweeper leaves orders alone when Maya reports no successful payment', function () {
    $order = pendingOrderWithSession();
    $order->created_at = now()->subMinutes(10);
    $order->save();

    Http::fake([
        'pg-sandbox.paymaya.com/*' => Http::response(mayaGetCheckoutResponse($order, 'PENDING_PAYMENT'), 200),
    ]);

    $this->artisan('orders:reconcile-maya')->assertSuccessful();

    expect($order->refresh()->payment_status)->toBe('unpaid');
    expect($order->status)->toBe('pending');
    Queue::assertNothingPushed();
});

test('sweeper tolerates a Maya API error and keeps the order pending', function () {
    $order = pendingOrderWithSession();
    $order->created_at = now()->subMinutes(10);
    $order->save();

    Http::fake([
        'pg-sandbox.paymaya.com/*' => Http::response(['errors' => ['message' => 'boom']], 500),
    ]);

    $this->artisan('orders:reconcile-maya')->assertSuccessful();

    expect($order->refresh()->payment_status)->toBe('unpaid');
    expect($order->status)->toBe('pending');
    Queue::assertNothingPushed();
});

test('sweeper ignores recent orders and orders without a checkout session', function () {
    $recent = pendingOrderWithSession();
    $noSession = pendingOrderWithSession(['checkout_session_id' => null]);

    Http::fake([
        'pg-sandbox.paymaya.com/*' => Http::response(
            mayaGetCheckoutResponse($recent, 'PAYMENT_SUCCESS'),
            200,
        ),
    ]);

    $this->artisan('orders:reconcile-maya')->assertSuccessful();

    expect($recent->refresh()->payment_status)->toBe('unpaid');
    expect($noSession->refresh()->payment_status)->toBe('unpaid');
    Queue::assertNothingPushed();
    Http::assertNothingSent();
});

test('sweeper skips paid and cancelled orders', function () {
    $paid = pendingOrderWithSession(['payment_status' => 'paid']);
    $cancelled = pendingOrderWithSession(['status' => 'cancelled', 'payment_status' => 'failed']);

    Http::fake([
        'pg-sandbox.paymaya.com/*' => Http::response([], 200),
    ]);

    $this->artisan('orders:reconcile-maya')->assertSuccessful();

    expect($paid->refresh()->payment_status)->toBe('paid');
    expect($cancelled->refresh()->status)->toBe('cancelled');
    Queue::assertNothingPushed();
    Http::assertNothingSent();
});
