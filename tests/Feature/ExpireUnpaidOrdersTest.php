<?php

use App\Jobs\DispatchLalamoveDelivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

function staleOrderWithSession(array $attributes = []): Order
{
    $order = Order::factory()->create(array_replace([
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'total' => 350.00,
        'checkout_session_id' => 'cs_expire_1',
        'payment_request_reference' => 'rrn_expire_1',
    ], $attributes));
    $order->created_at = now()->subHours(3);
    $order->save();

    return $order;
}

test('a stale unpaid order is cancelled and its product stock is released', function () {
    $product = Product::factory()->create(['product_quantity' => 5]);
    $order = Order::factory()->create([
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
    $product->decrement('product_quantity', 2);
    $order->created_at = now()->subHours(3);
    $order->save();

    $this->artisan('orders:expire-unpaid')->assertSuccessful();

    expect($order->fresh()->status)->toBe('cancelled');
    expect($order->fresh()->payment_status)->toBe('failed');
    expect($product->fresh()->product_quantity)->toBe(5);
});

test('a stale unpaid order releases reserved variant stock', function () {
    $product = Product::factory()->create(['product_quantity' => 5]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'quantity' => 4,
    ]);
    $order = Order::factory()->create([
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'quantity' => 3,
    ]);
    $variant->decrement('quantity', 3);
    $order->created_at = now()->subHours(3);
    $order->save();

    $this->artisan('orders:expire-unpaid')->assertSuccessful();

    expect($order->fresh()->status)->toBe('cancelled');
    expect($variant->fresh()->quantity)->toBe(4);
});

test('a recent unpaid order is left alone', function () {
    $product = Product::factory()->create(['product_quantity' => 5]);
    $order = Order::factory()->create([
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
    $product->decrement('product_quantity', 2);

    $this->artisan('orders:expire-unpaid')->assertSuccessful();

    expect($order->fresh()->status)->toBe('pending');
    expect($order->fresh()->payment_status)->toBe('unpaid');
    expect($product->fresh()->product_quantity)->toBe(3);
});

test('a stale paid order is left alone', function () {
    $product = Product::factory()->create(['product_quantity' => 5]);
    $order = Order::factory()->paid()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
    $order->created_at = now()->subHours(3);
    $order->save();

    $this->artisan('orders:expire-unpaid')->assertSuccessful();

    expect($order->fresh()->payment_status)->toBe('paid');
    expect($order->fresh()->status)->toBe('pending');
    expect($product->fresh()->product_quantity)->toBe(5);
});

test('an already cancelled order is not expired again', function () {
    $product = Product::factory()->create(['product_quantity' => 5]);
    $order = Order::factory()->create([
        'status' => 'cancelled',
        'payment_status' => 'failed',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
    $order->created_at = now()->subHours(3);
    $order->save();

    $this->artisan('orders:expire-unpaid')->assertSuccessful();

    expect($order->fresh()->status)->toBe('cancelled');
    expect($product->fresh()->product_quantity)->toBe(5);
});

test('running the expirer twice does not double release stock', function () {
    $product = Product::factory()->create(['product_quantity' => 5]);
    $order = Order::factory()->create([
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
    $product->decrement('product_quantity', 2);
    $order->created_at = now()->subHours(3);
    $order->save();

    $this->artisan('orders:expire-unpaid')->assertSuccessful();
    $this->artisan('orders:expire-unpaid')->assertSuccessful();

    expect($order->fresh()->status)->toBe('cancelled');
    expect($product->fresh()->product_quantity)->toBe(5);
});

test('a stale order that Maya reports as paid is confirmed instead of cancelled', function () {
    Queue::fake();

    $product = Product::factory()->create(['product_quantity' => 5]);
    $order = staleOrderWithSession(['total' => 350.00]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
    $product->decrement('product_quantity', 2);

    Http::fake([
        'pg-sandbox.paymaya.com/checkout/v1/checkouts/cs_expire_1' => Http::response(
            mayaGetCheckoutResponse($order, 'PAYMENT_SUCCESS'),
            200,
        ),
    ]);

    $this->artisan('orders:expire-unpaid')->assertSuccessful();

    expect($order->refresh()->payment_status)->toBe('paid');
    expect($order->status)->toBe('pending');
    expect($product->fresh()->product_quantity)->toBe(3);
    Queue::assertPushed(DispatchLalamoveDelivery::class, fn (DispatchLalamoveDelivery $job) => $job->orderId === $order->id);
});

test('an inconclusive Maya answer defers cancellation to a later run', function () {
    $order = staleOrderWithSession();

    Http::fake([
        'pg-sandbox.paymaya.com/*' => Http::response(['errors' => ['message' => 'boom']], 500),
    ]);

    $this->artisan('orders:expire-unpaid')->assertSuccessful();

    expect($order->refresh()->status)->toBe('pending');
    expect($order->payment_status)->toBe('unpaid');
});

test('a definitive unpaid answer from Maya lets the expiry proceed', function () {
    $product = Product::factory()->create(['product_quantity' => 5]);
    $order = staleOrderWithSession();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
    $product->decrement('product_quantity', 2);

    Http::fake([
        'pg-sandbox.paymaya.com/*' => Http::response(mayaGetCheckoutResponse($order, 'PENDING_PAYMENT'), 200),
    ]);

    $this->artisan('orders:expire-unpaid')->assertSuccessful();

    expect($order->fresh()->status)->toBe('cancelled');
    expect($product->fresh()->product_quantity)->toBe(5);
});

test('the 24 hour hard cap cancels an order even when Maya keeps erroring', function () {
    $product = Product::factory()->create(['product_quantity' => 5]);
    $order = staleOrderWithSession();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
    $product->decrement('product_quantity', 2);
    $order->created_at = now()->subHours(30);
    $order->save();

    Http::fake([
        'pg-sandbox.paymaya.com/*' => Http::response(['errors' => ['message' => 'boom']], 500),
    ]);

    $this->artisan('orders:expire-unpaid')->assertSuccessful();

    expect($order->fresh()->status)->toBe('cancelled');
    expect($product->fresh()->product_quantity)->toBe(5);
});

test('stale orders without a checkout session are cancelled without contacting Maya', function () {
    $order = staleOrderWithSession(['checkout_session_id' => null]);

    Http::fake([
        'pg-sandbox.paymaya.com/*' => Http::response([], 200),
    ]);

    $this->artisan('orders:expire-unpaid')->assertSuccessful();

    expect($order->fresh()->status)->toBe('cancelled');
    Http::assertNothingSent();
});
