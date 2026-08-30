<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

function fakePendingDispatchFlow(): void
{
    $handler = function (Request $request) {
        if (str_ends_with($request->url(), '/v3/quotations')) {
            return Http::response([
                'data' => [
                    'quotationId' => 'quotation_test_pending',
                    'stops' => [
                        ['stopId' => 'stop_pickup_pending'],
                        ['stopId' => 'stop_dropoff_pending'],
                    ],
                    'priceBreakdown' => ['total' => 150],
                ],
            ], 201);
        }

        return Http::response([
            'data' => [
                'orderId' => 'order_test_pending',
                'status' => 'ASSIGNING_DRIVER',
                'shareLink' => 'https://track.lalamove.test/order_test_pending',
            ],
        ], 201);
    };

    Http::fake([
        'rest.lalamove.com/*' => $handler,
        'rest.sandbox.lalamove.com/*' => $handler,
    ]);
}

function paidOrderPendingDispatch(array $attributes = []): Order
{
    $product = Product::factory()->create(['product_price' => 100, 'product_quantity' => 10]);
    $order = Order::factory()->create(array_replace([
        'city' => 'Makati',
        'delivery_lat' => 14.5547,
        'delivery_lng' => 121.0500,
        'status' => 'pending',
        'payment_status' => 'paid',
    ], $attributes));
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 100,
    ]);

    return $order;
}

beforeEach(function () {
    config([
        'services.lalamove.key' => 'test_api_key',
        'services.lalamove.secret' => 'test_api_secret',
        'shop.store.warehouse.phone' => '+63 962 796 1415',
    ]);
});

afterEach(function () {
    Carbon::setTestNow(null);
    config([
        'services.lalamove.key' => env('LALAMOVE_API_KEY', ''),
        'services.lalamove.secret' => env('LALAMOVE_API_SECRET', ''),
        'shop.store.warehouse.phone' => env('SHOP_WAREHOUSE_PHONE', '+639000000000'),
    ]);
});

test('the pending dispatch command books all paid orders inside the window', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-30 10:00:00'));
    fakePendingDispatchFlow();

    $first = paidOrderPendingDispatch();
    $second = paidOrderPendingDispatch();

    Artisan::call('orders:dispatch-pending');

    expect($first->fresh()->lalamove_order_id)->toBe('order_test_pending');
    expect($second->fresh()->lalamove_order_id)->toBe('order_test_pending');
});

test('the pending dispatch command skips unpaid, non-pending, and already dispatched orders', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-30 10:00:00'));
    Http::fake();

    $unpaid = paidOrderPendingDispatch(['payment_status' => 'unpaid']);
    $processing = paidOrderPendingDispatch(['status' => 'processing']);
    $already = paidOrderPendingDispatch(['lalamove_order_id' => 'ord_existing']);

    Artisan::call('orders:dispatch-pending');

    expect($unpaid->fresh()->lalamove_order_id)->toBeNull();
    expect($processing->fresh()->lalamove_order_id)->toBeNull();
    expect($already->fresh()->lalamove_order_id)->toBe('ord_existing');
    Http::assertNothingSent();
});

test('the pending dispatch command holds orders while the window is closed', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-30 20:00:00'));
    Http::fake();

    $order = paidOrderPendingDispatch();

    Artisan::call('orders:dispatch-pending');

    expect($order->fresh()->lalamove_order_id)->toBeNull();
    Http::assertNothingSent();
});