<?php

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;

function buildWebhookSignature(string $rawPayload, string $secret = 'test_secret'): array
{
    $timestamp = (string) time();

    return [
        'Paymongo-Signature' => 't='.$timestamp.',te='.hash_hmac('sha256', $timestamp.'.'.$rawPayload, $secret),
    ];
}

function paymentPaidPayload(Order $order, array $overrides = []): array
{
    return array_replace_recursive([
        'data' => [
            'attributes' => [
                'type' => 'payment.paid',
                'data' => [
                    'id' => 'pay_123',
                    'attributes' => [
                        'amount' => (int) round((float) $order->total * 100),
                        'currency' => 'PHP',
                        'metadata' => ['order_id' => $order->id],
                        'source' => ['type' => 'card'],
                    ],
                ],
            ],
        ],
    ], $overrides);
}

test('webhook is rejected when no webhook secret is configured', function () {
    config(['paymongo.webhook_secret' => '']);

    $payload = ['data' => ['attributes' => ['type' => 'payment.paid']]];

    $this->postJson('/paymongo/webhook', $payload, buildWebhookSignature(json_encode($payload)))
        ->assertStatus(400)
        ->assertJson(['status' => 'invalid signature']);
});

test('webhook is rejected without a signature header', function () {
    config(['paymongo.webhook_secret' => 'test_secret']);

    $this->postJson('/paymongo/webhook', ['data' => ['attributes' => ['type' => 'payment.paid']]])
        ->assertStatus(400);
});

test('webhook is rejected with an invalid signature', function () {
    config(['paymongo.webhook_secret' => 'test_secret']);
    $payload = ['data' => ['attributes' => ['type' => 'payment.paid']]];

    $this->postJson('/paymongo/webhook', $payload, [
        'Paymongo-Signature' => 't='.time().',te=forged',
    ])->assertStatus(400)
        ->assertJson(['status' => 'invalid signature']);
});

test('webhook with an unknown event type is ignored', function () {
    config(['paymongo.webhook_secret' => 'test_secret']);
    $payload = ['data' => ['attributes' => ['type' => 'source.chargeable']]];

    $this->postJson('/paymongo/webhook', $payload, buildWebhookSignature(json_encode($payload)))
        ->assertOk()
        ->assertJson(['status' => 'ignored']);
});

test('webhook is ignored when no matching order exists', function () {
    config(['paymongo.webhook_secret' => 'test_secret']);
    $payload = [
        'data' => [
            'attributes' => [
                'type' => 'payment.paid',
                'data' => [
                    'id' => 'pay_999',
                    'attributes' => ['metadata' => ['order_id' => 999999]],
                ],
            ],
        ],
    ];

    $this->postJson('/paymongo/webhook', $payload, buildWebhookSignature(json_encode($payload)))
        ->assertOk()
        ->assertJson(['status' => 'ignored']);
});

test('payment.paid marks the order paid and clears the cart', function () {
    config(['paymongo.webhook_secret' => 'test_secret']);

    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'total' => 1500,
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);
    Cart::factory()->create(['user_id' => $user->id]);

    $payload = paymentPaidPayload($order);

    $this->postJson('/paymongo/webhook', $payload, buildWebhookSignature(json_encode($payload)))
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    $order->refresh();
    expect($order->payment_status)->toBe('paid');
    expect($order->payment_intent_id)->toBe('pay_123');
    expect($order->payment_method)->toBe('card');
    expect(Cart::where('user_id', $user->id)->count())->toBe(0);
});

test('payment.paid is ignored when the paid amount does not match the order total', function () {
    config(['paymongo.webhook_secret' => 'test_secret']);

    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'total' => 1500,
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);

    $payload = paymentPaidPayload($order, [
        'data' => ['attributes' => ['data' => ['attributes' => ['amount' => 1]]]],
    ]);

    $this->postJson('/paymongo/webhook', $payload, buildWebhookSignature(json_encode($payload)))
        ->assertOk();

    expect($order->refresh()->payment_status)->toBe('unpaid');
});

test('payment.paid is ignored when the currency is not PHP', function () {
    config(['paymongo.webhook_secret' => 'test_secret']);

    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'total' => 1500,
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);

    $payload = paymentPaidPayload($order, [
        'data' => ['attributes' => ['data' => ['attributes' => ['currency' => 'USD']]]],
    ]);

    $this->postJson('/paymongo/webhook', $payload, buildWebhookSignature(json_encode($payload)))
        ->assertOk();

    expect($order->refresh()->payment_status)->toBe('unpaid');
});

test('payment.paid is ignored when the checkout session id does not match', function () {
    config(['paymongo.webhook_secret' => 'test_secret']);

    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'total' => 1500,
        'checkout_session_id' => 'cs_test_abc',
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);

    $payload = paymentPaidPayload($order, [
        'data' => ['attributes' => ['data' => ['attributes' => ['checkout_session_id' => 'cs_other']]]],
    ]);

    $this->postJson('/paymongo/webhook', $payload, buildWebhookSignature(json_encode($payload)))
        ->assertOk();

    expect($order->refresh()->payment_status)->toBe('unpaid');
});

test('checkout_session.payment.paid matches by checkout session id', function () {
    config(['paymongo.webhook_secret' => 'test_secret']);

    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'total' => 2000,
        'checkout_session_id' => 'cs_test_abc',
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);

    $payload = [
        'data' => [
            'attributes' => [
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => 'cs_test_abc',
                    'attributes' => [
                        'amount' => 200000,
                        'currency' => 'PHP',
                        'payments' => [
                            ['id' => 'pay_456', 'attributes' => ['source' => ['type' => 'gcash']]],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $this->postJson('/paymongo/webhook', $payload, buildWebhookSignature(json_encode($payload)))
        ->assertOk();

    $order->refresh();
    expect($order->payment_status)->toBe('paid');
    expect($order->payment_intent_id)->toBe('pay_456');
    expect($order->payment_method)->toBe('gcash');
});

test('checkout_session.payment.paid is ignored when the amount does not match', function () {
    config(['paymongo.webhook_secret' => 'test_secret']);

    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'total' => 2000,
        'checkout_session_id' => 'cs_test_abc',
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);

    $payload = [
        'data' => [
            'attributes' => [
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => 'cs_test_abc',
                    'attributes' => ['amount' => 5, 'currency' => 'PHP'],
                ],
            ],
        ],
    ];

    $this->postJson('/paymongo/webhook', $payload, buildWebhookSignature(json_encode($payload)))
        ->assertOk();

    expect($order->refresh()->payment_status)->toBe('unpaid');
});

test('payment.paid webhook is idempotent', function () {
    config(['paymongo.webhook_secret' => 'test_secret']);

    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'total' => 1500,
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);

    $payload = paymentPaidPayload($order);

    $raw = json_encode($payload);

    $this->postJson('/paymongo/webhook', $payload, buildWebhookSignature($raw))->assertOk();
    $this->postJson('/paymongo/webhook', $payload, buildWebhookSignature($raw))->assertOk();

    expect($order->refresh()->payment_status)->toBe('paid');
});

test('payment.paid is ignored for a cancelled order', function () {
    config(['paymongo.webhook_secret' => 'test_secret']);

    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'total' => 1500,
        'status' => 'cancelled',
        'payment_status' => 'unpaid',
    ]);

    $payload = paymentPaidPayload($order);

    $this->postJson('/paymongo/webhook', $payload, buildWebhookSignature(json_encode($payload)))
        ->assertOk();

    expect($order->refresh()->payment_status)->toBe('unpaid');
});

test('payment.failed releases reserved stock and cancels the order', function () {
    config(['paymongo.webhook_secret' => 'test_secret']);

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

    $payload = [
        'data' => [
            'attributes' => [
                'type' => 'payment.failed',
                'data' => [
                    'id' => 'pay_789',
                    'attributes' => ['metadata' => ['order_id' => $order->id]],
                ],
            ],
        ],
    ];

    $raw = json_encode($payload);

    $this->postJson('/paymongo/webhook', $payload, buildWebhookSignature($raw))->assertOk();

    $order->refresh();
    expect($order->payment_status)->toBe('failed');
    expect($order->status)->toBe('cancelled');
    expect($product->fresh()->product_quantity)->toBe(5);
});

test('checkout_session.expired releases reserved stock and cancels the order', function () {
    config(['paymongo.webhook_secret' => 'test_secret']);

    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'checkout_session_id' => 'cs_expired',
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
    $product->decrement('product_quantity', 2);

    $payload = [
        'data' => [
            'attributes' => [
                'type' => 'checkout_session.expired',
                'data' => ['id' => 'cs_expired'],
            ],
        ],
    ];

    $raw = json_encode($payload);

    $this->postJson('/paymongo/webhook', $payload, buildWebhookSignature($raw))->assertOk();

    $order->refresh();
    expect($order->payment_status)->toBe('failed');
    expect($order->status)->toBe('cancelled');
    expect($product->fresh()->product_quantity)->toBe(5);
});

test('payment.failed webhook is idempotent and does not double-restore stock', function () {
    config(['paymongo.webhook_secret' => 'test_secret']);

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

    $payload = [
        'data' => [
            'attributes' => [
                'type' => 'payment.failed',
                'data' => [
                    'id' => 'pay_789',
                    'attributes' => ['metadata' => ['order_id' => $order->id]],
                ],
            ],
        ],
    ];

    $raw = json_encode($payload);

    $this->postJson('/paymongo/webhook', $payload, buildWebhookSignature($raw))->assertOk();
    $this->postJson('/paymongo/webhook', $payload, buildWebhookSignature($raw))->assertOk();

    expect($product->fresh()->product_quantity)->toBe(5);
});

test('payment.failed does not touch an already paid order', function () {
    config(['paymongo.webhook_secret' => 'test_secret']);

    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'processing',
        'payment_status' => 'paid',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
    $product->decrement('product_quantity', 2);

    $payload = [
        'data' => [
            'attributes' => [
                'type' => 'payment.failed',
                'data' => [
                    'id' => 'pay_789',
                    'attributes' => ['metadata' => ['order_id' => $order->id]],
                ],
            ],
        ],
    ];

    $this->postJson('/paymongo/webhook', $payload, buildWebhookSignature(json_encode($payload)))
        ->assertOk();

    $order->refresh();
    expect($order->status)->toBe('processing');
    expect($order->payment_status)->toBe('paid');
    expect($product->fresh()->product_quantity)->toBe(3);
});
