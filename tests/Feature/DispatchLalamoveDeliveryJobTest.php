<?php

use App\Jobs\DispatchLalamoveDelivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\DeliveryWindow;
use App\Services\LalamoveDeliveryService;
use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function fakeLalamoveOrderFlowForJob(): void
{
    $handler = function (Request $request) {
        if (str_ends_with($request->url(), '/v3/quotations')) {
            return Http::response([
                'data' => [
                    'quotationId' => 'quotation_test_1',
                    'stops' => [
                        ['stopId' => 'stop_pickup_1'],
                        ['stopId' => 'stop_dropoff_1'],
                    ],
                    'priceBreakdown' => ['total' => 150],
                ],
            ], 201);
        }

        return Http::response([
            'data' => [
                'orderId' => 'order_test_1',
                'status' => 'ASSIGNING_DRIVER',
                'shareLink' => 'https://track.lalamove.test/order_test_1',
            ],
        ], 201);
    };

    Http::fake([
        'rest.lalamove.com/*' => $handler,
        'rest.sandbox.lalamove.com/*' => $handler,
    ]);
}

function paidOrderForJobDispatch(array $attributes = []): Order
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

    // Freeze time inside the dispatch window so every test is deterministic
    // regardless of when the suite runs.
    Carbon::setTestNow(Carbon::parse('2026-08-30 10:00:00'));
});

afterEach(function () {
    Carbon::setTestNow(null);
});

test('the job creates a Lalamove delivery for a paid order', function () {
    fakeLalamoveOrderFlowForJob();
    $order = paidOrderForJobDispatch();

    (new DispatchLalamoveDelivery($order->id))->handle(app(LalamoveDeliveryService::class), app(DeliveryWindow::class));

    expect($order->fresh()->lalamove_order_id)->toBe('order_test_1');
});

test('the job does nothing for an unpaid order', function () {
    Http::fake();

    $order = paidOrderForJobDispatch(['payment_status' => 'unpaid']);

    (new DispatchLalamoveDelivery($order->id))->handle(app(LalamoveDeliveryService::class), app(DeliveryWindow::class));

    expect($order->fresh()->lalamove_order_id)->toBeNull();
    Http::assertNothingSent();
});

test('the job defers a paid order while the dispatch window is closed', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-30 20:00:00'));
    Http::fake();

    $order = paidOrderForJobDispatch();

    (new DispatchLalamoveDelivery($order->id))->handle(app(LalamoveDeliveryService::class), app(DeliveryWindow::class));

    expect($order->fresh()->lalamove_order_id)->toBeNull();
    Http::assertNothingSent();
});

test('the job does nothing when a delivery already exists', function () {
    Http::fake();

    $order = paidOrderForJobDispatch(['lalamove_order_id' => 'ord_existing']);

    (new DispatchLalamoveDelivery($order->id))->handle(app(LalamoveDeliveryService::class), app(DeliveryWindow::class));

    expect($order->fresh()->lalamove_order_id)->toBe('ord_existing');
    Http::assertNothingSent();
});

test('the job fails so it is retried when Lalamove rejects the dispatch', function () {
    Http::fake([
        'rest.lalamove.com/*' => Http::response([
            'errors' => [['id' => 'ERR_INVALID_FIELD', 'message' => 'quotation expired']],
        ], 422),
        'rest.sandbox.lalamove.com/*' => Http::response([
            'errors' => [['id' => 'ERR_INVALID_FIELD', 'message' => 'quotation expired']],
        ], 422),
    ]);

    $order = paidOrderForJobDispatch();

    expect(fn () => (new DispatchLalamoveDelivery($order->id))->handle(app(LalamoveDeliveryService::class), app(DeliveryWindow::class)))
        ->toThrow(RuntimeException::class);

    expect($order->fresh()->lalamove_order_id)->toBeNull();
});

test('the job handles a missing order gracefully', function () {
    Http::fake();

    (new DispatchLalamoveDelivery(999999))->handle(app(LalamoveDeliveryService::class), app(DeliveryWindow::class));

    Http::assertNothingSent();
});
