<?php

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\InventoryHistory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
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

test('cancelling checkout releases reserved stock', function () {
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

    expect($order->fresh()->status)->toBe('cancelled');
    expect($product->fresh()->product_quantity)->toBe(5);
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
