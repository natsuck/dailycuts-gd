<?php

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\InventoryHistory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\GeocodingService;
use Illuminate\Support\Facades\Http;

function validCheckoutPayload(): array
{
    return [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address' => '123 Main St',
        'address2' => 'Unit 4',
        'barangay' => 'San Antonio',
        'city' => 'Springfield',
        'region' => 'NCR',
        'postal' => '1234',
        'phone' => '09171234567',
        'email' => 'john@example.com',
        'notes' => '',
    ];
}

test('guests are redirected to login at checkout', function () {
    $this->get('/checkout')->assertRedirect('/login');
});

test('checkout with an empty cart redirects to the shop', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/checkout/place-order', validCheckoutPayload())
        ->assertRedirect('/shop')
        ->assertSessionHas('cartMessage');

    expect(Order::count())->toBe(0);
});

test('placing an order reserves stock and records inventory history', function () {
    Http::fake([
        'api.paymongo.com/*' => Http::response([
            'data' => [
                'id' => 'cs_test_123',
                'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_test_123'],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $this->actingAs($user)
        ->post('/checkout/place-order', validCheckoutPayload())
        ->assertRedirect('https://checkout.paymongo.com/cs_test_123');

    $order = Order::where('user_id', $user->id)->first();

    expect($order)->not->toBeNull();
    expect($order->status)->toBe('pending');
    expect($order->payment_status)->toBe('unpaid');
    expect($order->checkout_session_id)->toBe('cs_test_123');
    expect($product->fresh()->product_quantity)->toBe(3);
    expect(InventoryHistory::where('reference_id', $order->id)->where('type', 'sale')->count())->toBe(1);
});

test('resubmitting the same checkout form does not create a second order', function () {
    Http::fake([
        'api.paymongo.com/*' => Http::response([
            'data' => [
                'id' => 'cs_dup_1',
                'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_dup_1'],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $payload = validCheckoutPayload();
    $payload['idempotency_key'] = 'key-checkout-1';

    $this->actingAs($user)
        ->post('/checkout/place-order', $payload)
        ->assertRedirect('https://checkout.paymongo.com/cs_dup_1');

    $this->actingAs($user)
        ->post('/checkout/place-order', $payload)
        ->assertRedirect('https://checkout.paymongo.com/cs_dup_1');

    expect(Order::count())->toBe(1);
    expect(Order::first()->checkout_session_url)->toBe('https://checkout.paymongo.com/cs_dup_1');
    expect($product->fresh()->product_quantity)->toBe(3);
});

test('a distinct idempotency key issues a new order', function () {
    $count = 0;

    Http::fake([
        'api.paymongo.com/*' => function (\Illuminate\Http\Client\Request $request) use (&$count) {
            $count++;
            $id = 'cs_key_fresh_'.$count;

            return Http::response([
                'data' => [
                    'id' => $id,
                    'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/'.$id],
                ],
            ], 200);
        },
    ]);

    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $this->actingAs($user)
        ->post('/checkout/place-order', [...validCheckoutPayload(), 'idempotency_key' => 'key-checkout-1'])
        ->assertRedirect('https://checkout.paymongo.com/cs_key_fresh_1');

    $this->actingAs($user)
        ->post('/checkout/place-order', [...validCheckoutPayload(), 'idempotency_key' => 'key-checkout-2'])
        ->assertRedirect('https://checkout.paymongo.com/cs_key_fresh_2');

    expect(Order::count())->toBe(2);
    expect($product->fresh()->product_quantity)->toBe(1);
});

test('resubmitting a form for an already paid order does not create a duplicate', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'delivered',
        'payment_status' => 'paid',
        'idempotency_key' => 'key-paid-1',
    ]);
    $product->decrement('product_quantity', 2);

    $this->actingAs($user)
        ->post('/checkout/place-order', [...validCheckoutPayload(), 'idempotency_key' => 'key-paid-1'])
        ->assertRedirect(route('orders.show', $order->id));

    expect(Order::count())->toBe(1);
    expect($product->fresh()->product_quantity)->toBe(3);
});

test('order items record the variant used at checkout', function () {
    Http::fake([
        'api.paymongo.com/*' => Http::response([
            'data' => [
                'id' => 'cs_test_variant',
                'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_test_variant'],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    $variant = App\Models\ProductVariant::factory()->create([
        'product_id' => $product->id,
        'quantity' => 5,
    ]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    $this->actingAs($user)
        ->post('/checkout/place-order', validCheckoutPayload());

    $item = OrderItem::first();

    expect($item->variant_id)->toBe($variant->id);
    expect($variant->fresh()->quantity)->toBe(3);
});

test('cannot place an order exceeding available stock', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 2]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 5,
    ]);

    $this->actingAs($user)
        ->from('/checkout')
        ->post('/checkout/place-order', validCheckoutPayload())
        ->assertSessionHasErrors('cart');

    expect(Order::count())->toBe(0);
    expect($product->fresh()->product_quantity)->toBe(2);
});

test('checkout validates the order payload', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $payload = validCheckoutPayload();
    $payload['phone'] = 'not-a-phone';
    $payload['postal'] = 'abc';

    $this->actingAs($user)
        ->from('/checkout')
        ->post('/checkout/place-order', $payload)
        ->assertSessionHasErrors(['phone', 'postal']);

    expect(Order::count())->toBe(0);
});

test('paymongo failure releases reserved stock and deletes the order', function () {
    Http::fake([
        'api.paymongo.com/*' => Http::response(['errors' => ['message' => 'gateway error']], 400),
    ]);

    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $this->actingAs($user)
        ->from('/checkout')
        ->post('/checkout/place-order', validCheckoutPayload())
        ->assertRedirect('/checkout')
        ->assertSessionHasErrors('checkout');

    expect(Order::count())->toBe(0);
    expect(OrderItem::count())->toBe(0);
    expect($product->fresh()->product_quantity)->toBe(5);
});

test('cancelling checkout redirects without mutating the order', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
    $product->decrement('product_quantity', 2);

    $this->actingAs($user)
        ->get('/checkout/cancel?order_id='.$order->id)
        ->assertRedirect('/shop');

    // Stock is released via the payment.failed / checkout_session.expired webhook,
    // not through the (unauthenticated, GET) cancel redirect.
    expect($order->fresh()->status)->toBe('pending');
    expect($product->fresh()->product_quantity)->toBe(3);
});

test('user cannot cancel another user\'s order', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    $order = Order::factory()->create([
        'user_id' => $owner->id,
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $this->actingAs($other)
        ->get('/checkout/cancel?order_id='.$order->id)
        ->assertRedirect('/shop');

    expect($order->fresh()->status)->toBe('pending');
    expect($product->fresh()->product_quantity)->toBe(5);
});

test('applying a valid percentage coupon returns the discount', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_price' => 100, 'product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    Coupon::factory()->create(['code' => 'SAVE20', 'type' => 'percentage', 'value' => 20]);

    $this->actingAs($user)
        ->postJson('/checkout/coupon', ['code' => 'save20'])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'couponCode' => 'SAVE20',
            'discount' => 40,
            'freeShipping' => false,
        ]);

    expect(session('coupon_code'))->toBe('SAVE20');
});

test('applying an unknown coupon returns an error', function () {
    $user = User::factory()->create();
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => Product::factory()->create(['product_price' => 100, 'product_quantity' => 5])->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->postJson('/checkout/coupon', ['code' => 'NOPE'])
        ->assertStatus(422)
        ->assertJson(['success' => false]);
});

test('applying a coupon below its minimum order returns an error', function () {
    $user = User::factory()->create();
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => Product::factory()->create(['product_price' => 100, 'product_quantity' => 5])->id,
        'quantity' => 1,
    ]);

    Coupon::factory()->create(['code' => 'MIN500', 'type' => 'fixed', 'value' => 50, 'min_order' => 500]);

    $this->actingAs($user)
        ->postJson('/checkout/coupon', ['code' => 'MIN500'])
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    expect(session('coupon_code'))->toBeNull();
});

test('removing an applied coupon clears it from the session', function () {
    $user = User::factory()->create();
    session(['coupon_code' => 'SAVE20']);

    $this->actingAs($user)
        ->deleteJson('/checkout/coupon')
        ->assertOk()
        ->assertJson(['success' => true, 'discount' => 0, 'freeShipping' => false]);

    expect(session('coupon_code'))->toBeNull();
});

test('placing an order with a coupon stores the discount and records usage', function () {
    Http::fake([
        'api.paymongo.com/*' => Http::response([
            'data' => [
                'id' => 'cs_coupon_1',
                'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_coupon_1'],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $product = Product::factory()->create(['product_price' => 100, 'product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $coupon = Coupon::factory()->create(['code' => 'SAVE20', 'type' => 'percentage', 'value' => 20]);

    session(['coupon_code' => 'SAVE20']);

    $this->actingAs($user)
        ->post('/checkout/place-order', validCheckoutPayload())
        ->assertRedirect('https://checkout.paymongo.com/cs_coupon_1');

    $order = Order::where('user_id', $user->id)->first();

    expect((float) $order->discount)->toBe(40.0);
    expect($order->coupon_code)->toBe('SAVE20');
    expect(CouponUsage::where('order_id', $order->id)->where('coupon_id', $coupon->id)->exists())->toBeTrue();
    expect((float) CouponUsage::where('order_id', $order->id)->first()->discount_amount)->toBe(40.0);
    expect($coupon->fresh()->used_count)->toBe(1);
    expect(session('coupon_code'))->toBeNull();
});

test('free shipping coupon waives the delivery fee estimate', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_price' => 300, 'product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    Coupon::factory()->create(['code' => 'FREESHIP', 'type' => 'free_shipping', 'value' => 0, 'min_order' => 500]);

    session(['coupon_code' => 'FREESHIP']);

    $this->actingAs($user)
        ->getJson('/checkout/estimate-shipping?city=Makati')
        ->assertOk()
        ->assertJson(['shippingFee' => 0, 'source' => 'free_shipping']);
});

test('free shipping coupon makes the order shipping free', function () {
    Http::fake([
        'api.paymongo.com/*' => Http::response([
            'data' => [
                'id' => 'cs_freeship',
                'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_freeship'],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $product = Product::factory()->create(['product_price' => 300, 'product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    Coupon::factory()->create(['code' => 'FREESHIP', 'type' => 'free_shipping', 'value' => 0, 'min_order' => 500]);

    session(['coupon_code' => 'FREESHIP']);

    $this->actingAs($user)
        ->post('/checkout/place-order', validCheckoutPayload())
        ->assertRedirect('https://checkout.paymongo.com/cs_freeship');

    $order = Order::where('user_id', $user->id)->first();

    expect((float) $order->shipping_fee)->toBe(0.0);
    expect((float) $order->discount)->toBe(0.0);
    expect($order->coupon_code)->toBe('FREESHIP');
    expect(CouponUsage::where('order_id', $order->id)->exists())->toBeTrue();
});

test('placing an order stores provided delivery coordinates without geocoding', function () {
    Http::fake([
        'api.paymongo.com/*' => Http::response([
            'data' => [
                'id' => 'cs_coords',
                'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_coords'],
            ],
        ], 200),
    ]);

    $this->mock(GeocodingService::class, function ($mock) {
        $mock->shouldReceive('geocode')->never();
        $mock->shouldReceive('reverseGeocode')->andReturn([
            'address' => 'San Antonio, Springfield, NCR, Philippines',
            'locality' => 'Springfield',
            'region' => 'NCR',
        ]);
        $mock->shouldReceive('fullAddress')->andReturn('123 Main St, San Antonio, Springfield, NCR 1234');
    });

    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $payload = validCheckoutPayload();
    $payload['delivery_lat'] = '14.5460616';
    $payload['delivery_lng'] = '120.9977219';

    $this->actingAs($user)
        ->post('/checkout/place-order', $payload)
        ->assertRedirect('https://checkout.paymongo.com/cs_coords');

    $order = Order::where('user_id', $user->id)->first();

    expect((float) $order->delivery_lat)->toBe(14.5460616);
    expect((float) $order->delivery_lng)->toBe(120.9977219);
});

test('placing an order ignores coordinates that do not match the address and falls back to geocoding', function () {
    Http::fake([
        'api.paymongo.com/*' => Http::response([
            'data' => [
                'id' => 'cs_coords_spoof',
                'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_coords_spoof'],
            ],
        ], 200),
    ]);

    $this->mock(GeocodingService::class, function ($mock) {
        $mock->shouldReceive('reverseGeocode')->andReturn([
            'address' => 'Somewhere, Manila, Philippines',
            'locality' => 'Manila',
            'region' => 'NCR',
        ]);
        $mock->shouldReceive('geocode')->once()->andReturn(['lat' => 14.55, 'lng' => 121.01]);
        $mock->shouldReceive('fullAddress')->andReturn('123 Main St, San Antonio, Springfield, NCR 1234');
    });

    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $payload = validCheckoutPayload();
    $payload['delivery_lat'] = '14.5460616';
    $payload['delivery_lng'] = '120.9977219';

    $this->actingAs($user)
        ->post('/checkout/place-order', $payload)
        ->assertRedirect('https://checkout.paymongo.com/cs_coords_spoof');

    $order = Order::where('user_id', $user->id)->first();

    expect((float) $order->delivery_lat)->toBe(14.55);
    expect((float) $order->delivery_lng)->toBe(121.01);
});

test('placing an order falls back to geocoding for out-of-bounds coordinates', function () {
    Http::fake([
        'api.paymongo.com/*' => Http::response([
            'data' => [
                'id' => 'cs_coords_fallback',
                'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_coords_fallback'],
            ],
        ], 200),
    ]);

    $this->mock(GeocodingService::class, function ($mock) {
        $mock->shouldReceive('geocode')->once()->andReturn(['lat' => 14.55, 'lng' => 121.01]);
        $mock->shouldReceive('fullAddress')->andReturn('123 Main St, San Antonio, Springfield, NCR 1234');
    });

    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $payload = validCheckoutPayload();
    $payload['delivery_lat'] = '999';
    $payload['delivery_lng'] = '999';

    $this->actingAs($user)
        ->post('/checkout/place-order', $payload)
        ->assertRedirect('https://checkout.paymongo.com/cs_coords_fallback');

    $order = Order::where('user_id', $user->id)->first();

    expect((float) $order->delivery_lat)->toBe(14.55);
    expect((float) $order->delivery_lng)->toBe(121.01);
});

test('invalid delivery coordinates are rejected at checkout', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $payload = validCheckoutPayload();
    $payload['delivery_lat'] = 'not-a-number';

    $this->actingAs($user)
        ->from('/checkout')
        ->post('/checkout/place-order', $payload)
        ->assertSessionHasErrors('delivery_lat');

    expect(Order::count())->toBe(0);
});

test('shipping estimate uses provided coordinates without geocoding', function () {
    Http::fake();

    config(['services.lalamove.key' => '', 'services.lalamove.secret' => '']);

    $this->mock(GeocodingService::class, function ($mock) {
        $mock->shouldReceive('geocode')->never();
        $mock->shouldReceive('reverseGeocode')->andReturn([
            'address' => 'Makati, Philippines',
            'locality' => 'Makati',
            'region' => 'NCR',
        ]);
        $mock->shouldReceive('fullAddress')->andReturn('123 Main St, Springfield, NCR 1234');
    });

    $user = User::factory()->create();
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => Product::factory()->create(['product_price' => 100, 'product_quantity' => 5])->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->getJson('/checkout/estimate-shipping?city=Makati&delivery_lat=14.55&delivery_lng=121.01')
        ->assertOk()
        ->assertJsonStructure(['shippingFee', 'source'])
        ->assertJson(['source' => 'flat_rate']);
});
