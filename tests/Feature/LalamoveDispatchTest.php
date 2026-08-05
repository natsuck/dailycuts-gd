<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\LalamoveDeliveryService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function fakeLalamoveOrderFlow(): void
{
    Http::fake([
        'rest.sandbox.lalamove.com/*' => function (Request $request) {
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
        },
    ]);
}

function paidOrderWithDelivery(): Order
{
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_price' => 100, 'product_quantity' => 10]);
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'city' => 'Makati',
        'delivery_lat' => 14.5547,
        'delivery_lng' => 121.0500,
        'status' => 'pending',
        'payment_status' => 'paid',
    ]);
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
    config([
        'services.lalamove.key' => env('LALAMOVE_API_KEY', ''),
        'services.lalamove.secret' => env('LALAMOVE_API_SECRET', ''),
        'shop.store.warehouse.phone' => env('SHOP_WAREHOUSE_PHONE', '+639000000000'),
    ]);
});

test('dispatch creates the Lalamove order with a normalized sender phone', function () {
    fakeLalamoveOrderFlow();

    $order = paidOrderWithDelivery();
    $delivery = app(LalamoveDeliveryService::class);

    expect($delivery->dispatch($order))->toBeTrue();
    expect($order->fresh()->lalamove_order_id)->toBe('order_test_1');

    Http::assertSent(function (Request $request) {
        if (! str_ends_with($request->url(), '/v3/orders')) {
            return false;
        }

        $data = $request->data()['data'];

        expect($data['sender']['phone'])->toBe('+639627961415');
        expect($data['quotationId'])->toBe('quotation_test_1');

        return true;
    });
});

test('dispatch does not send item or specialRequests in the order payload', function () {
    fakeLalamoveOrderFlow();

    $delivery = app(LalamoveDeliveryService::class);

    expect($delivery->dispatch(paidOrderWithDelivery()))->toBeTrue();

    Http::assertSent(function (Request $request) {
        if (! str_ends_with($request->url(), '/v3/orders')) {
            return false;
        }

        $data = $request->data()['data'];

        expect($data)->not->toHaveKey('item');
        expect($data)->not->toHaveKey('specialRequests');

        return true;
    });
});

test('dispatch surfaces the real Lalamove error message on a 422', function () {
    Http::fake([
        'rest.sandbox.lalamove.com/*' => Http::response([
            'errors' => [
                ['id' => 'ERR_INVALID_FIELD', 'message' => "'+63 962 796 1415' is not valid 'phone'."],
                ['id' => 'ERR_UNKNOWN_FIELD', 'message' => "additionalProperties 'item', 'specialRequests' not allowed"],
            ],
        ], 422),
    ]);

    $delivery = app(LalamoveDeliveryService::class);

    expect($delivery->dispatch(paidOrderWithDelivery()))->toBeFalse();
    expect($delivery->getLastError())->toContain('ERR_INVALID_FIELD');
    expect($delivery->getLastError())->toContain('ERR_UNKNOWN_FIELD');
});
