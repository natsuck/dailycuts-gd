<?php

use App\Models\Order;
use App\Models\User;

function buildLalamoveSignature(string $rawPayload, string $secret = 'test_lalamove_secret'): array
{
    return [
        'X-Lalamove-Signature' => base64_encode(hash_hmac('sha256', $rawPayload.'/lalamove/webhook', $secret, true)),
    ];
}

beforeEach(function () {
    config(['services.lalamove.key' => 'test_lalamove_key']);
    config(['services.lalamove.secret' => 'test_lalamove_secret']);
});

afterEach(function () {
    config(['services.lalamove.key' => env('LALAMOVE_API_KEY', '')]);
    config(['services.lalamove.secret' => env('LALAMOVE_API_SECRET', '')]);
});

test('webhook is rejected when apiKey is missing', function () {
    $payload = [
        'eventName' => 'ORDER_STATUS_CHANGED',
        'data' => ['orderId' => 'ord_1', 'status' => 'COMPLETED'],
    ];

    $this->postJson('/lalamove/webhook', $payload, buildLalamoveSignature(json_encode($payload)))
        ->assertStatus(401);
});

test('webhook is rejected when apiKey does not match', function () {
    $payload = [
        'apiKey' => 'forged_key',
        'eventName' => 'ORDER_STATUS_CHANGED',
        'data' => ['orderId' => 'ord_1', 'status' => 'COMPLETED'],
    ];

    $this->postJson('/lalamove/webhook', $payload, buildLalamoveSignature(json_encode($payload)))
        ->assertStatus(401);
});

test('webhook is rejected without a signature header', function () {
    $this->postJson('/lalamove/webhook', [
        'apiKey' => 'test_lalamove_key',
        'eventName' => 'ORDER_STATUS_CHANGED',
        'data' => ['orderId' => 'ord_1', 'status' => 'COMPLETED'],
    ])->assertStatus(401);
});

test('webhook is rejected with a tampered body', function () {
    $payload = [
        'apiKey' => 'test_lalamove_key',
        'eventName' => 'ORDER_STATUS_CHANGED',
        'data' => ['orderId' => 'ord_1', 'status' => 'COMPLETED'],
    ];

    $forged = $payload;
    $forged['data']['status'] = 'CANCELED';

    $this->postJson('/lalamove/webhook', $forged, buildLalamoveSignature(json_encode($payload)))
        ->assertStatus(401);
});

test('webhook is rejected with a forged signature', function () {
    $payload = [
        'apiKey' => 'test_lalamove_key',
        'eventName' => 'ORDER_STATUS_CHANGED',
        'data' => ['orderId' => 'ord_1', 'status' => 'COMPLETED'],
    ];

    $this->postJson('/lalamove/webhook', $payload, ['X-Lalamove-Signature' => base64_encode('forged')])
        ->assertStatus(401);
});

test('webhook with a valid signature and apiKey updates the order status', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'lalamove_order_id' => 'ord_1',
        'status' => 'processing',
        'delivery_status' => 'ASSIGNING_DRIVER',
        'payment_status' => 'paid',
    ]);

    $payload = [
        'apiKey' => 'test_lalamove_key',
        'eventName' => 'ORDER_STATUS_CHANGED',
        'data' => ['orderId' => 'ord_1', 'status' => 'COMPLETED'],
    ];

    $this->postJson('/lalamove/webhook', $payload, buildLalamoveSignature(json_encode($payload)))
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    $order->refresh();

    expect($order->status)->toBe('delivered');
    expect($order->delivery_status)->toBe('completed');
});

test('webhook with a valid signature is ignored for an unknown order', function () {
    $payload = [
        'apiKey' => 'test_lalamove_key',
        'eventName' => 'ORDER_STATUS_CHANGED',
        'data' => ['orderId' => 'no_such_order', 'status' => 'COMPLETED'],
    ];

    $this->postJson('/lalamove/webhook', $payload, buildLalamoveSignature(json_encode($payload)))
        ->assertOk()
        ->assertJson(['status' => 'ignored']);
});

test('a stale webhook is ignored and does not regress a newer status', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'lalamove_order_id' => 'ord_stale',
        'status' => 'delivered',
        'delivery_status' => 'completed',
        'payment_status' => 'paid',
        'last_lalamove_webhook_at' => now()->subMinutes(5),
    ]);

    $payload = [
        'apiKey' => 'test_lalamove_key',
        'eventName' => 'ORDER_STATUS_CHANGED',
        'data' => [
            'orderId' => 'ord_stale',
            'status' => 'ON_GOING',
            'updatedAt' => now()->subHour()->toIso8601String(),
        ],
    ];

    $this->postJson('/lalamove/webhook', $payload, buildLalamoveSignature(json_encode($payload)))
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    $order->refresh();

    expect($order->status)->toBe('delivered');
    expect($order->delivery_status)->toBe('completed');
});

test('a duplicate webhook with the same timestamp is ignored', function () {
    $user = User::factory()->create();
    $at = now()->subMinutes(5);
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'lalamove_order_id' => 'ord_dup',
        'status' => 'delivered',
        'delivery_status' => 'completed',
        'payment_status' => 'paid',
        'last_lalamove_webhook_at' => $at,
    ]);

    $payload = [
        'apiKey' => 'test_lalamove_key',
        'eventName' => 'ORDER_STATUS_CHANGED',
        'data' => [
            'orderId' => 'ord_dup',
            'status' => 'COMPLETED',
            'updatedAt' => $at->toIso8601String(),
        ],
    ];

    $this->postJson('/lalamove/webhook', $payload, buildLalamoveSignature(json_encode($payload)))
        ->assertOk();

    $order->refresh();

    expect($order->status)->toBe('delivered');
});

test('a newer webhook updates the order status', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'lalamove_order_id' => 'ord_new',
        'status' => 'processing',
        'delivery_status' => 'assigning_driver',
        'payment_status' => 'paid',
        'last_lalamove_webhook_at' => now()->subHours(2),
    ]);

    $payload = [
        'apiKey' => 'test_lalamove_key',
        'eventName' => 'ORDER_STATUS_CHANGED',
        'data' => [
            'orderId' => 'ord_new',
            'status' => 'COMPLETED',
            'updatedAt' => now()->subMinutes(5)->toIso8601String(),
        ],
    ];

    $this->postJson('/lalamove/webhook', $payload, buildLalamoveSignature(json_encode($payload)))
        ->assertOk();

    $order->refresh();

    expect($order->status)->toBe('delivered');
    expect($order->delivery_status)->toBe('completed');
    expect($order->last_lalamove_webhook_at->timestamp)->toBeGreaterThan(now()->subMinutes(10)->timestamp);
});

test('a FAILED status marks the order as failed', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'lalamove_order_id' => 'ord_failed',
        'status' => 'processing',
        'delivery_status' => 'assigning_driver',
        'payment_status' => 'paid',
    ]);

    $payload = [
        'apiKey' => 'test_lalamove_key',
        'eventName' => 'ORDER_STATUS_CHANGED',
        'data' => [
            'orderId' => 'ord_failed',
            'status' => 'FAILED',
            'updatedAt' => now()->toIso8601String(),
        ],
    ];

    $this->postJson('/lalamove/webhook', $payload, buildLalamoveSignature(json_encode($payload)))
        ->assertOk();

    $order->refresh();

    expect($order->status)->toBe('failed');
    expect($order->delivery_status)->toBe('failed');
    expect($order->lalamove_status)->toBe('FAILED');
});

test('a FAILED status does not override an already delivered order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'lalamove_order_id' => 'ord_failed_late',
        'status' => 'delivered',
        'delivery_status' => 'completed',
        'payment_status' => 'paid',
        'last_lalamove_webhook_at' => now()->subHour(),
    ]);

    $payload = [
        'apiKey' => 'test_lalamove_key',
        'eventName' => 'ORDER_STATUS_CHANGED',
        'data' => [
            'orderId' => 'ord_failed_late',
            'status' => 'FAILED',
            'updatedAt' => now()->toIso8601String(),
        ],
    ];

    $this->postJson('/lalamove/webhook', $payload, buildLalamoveSignature(json_encode($payload)))
        ->assertOk();

    $order->refresh();

    expect($order->status)->toBe('delivered');
});

test('a DRIVER_ASSIGNED webhook stores the driver details', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'lalamove_order_id' => 'ord_driver',
        'status' => 'processing',
        'delivery_status' => 'assigning_driver',
        'payment_status' => 'paid',
    ]);

    $payload = [
        'apiKey' => 'test_lalamove_key',
        'eventName' => 'DRIVER_ASSIGNED',
        'data' => [
            'orderId' => 'ord_driver',
            'driver' => [
                'name' => 'Juan Dela Cruz',
                'phone' => '+639171234567',
            ],
            'updatedAt' => now()->toIso8601String(),
        ],
    ];

    $this->postJson('/lalamove/webhook', $payload, buildLalamoveSignature(json_encode($payload)))
        ->assertOk();

    $order->refresh();

    expect($order->lalamove_driver_name)->toBe('Juan Dela Cruz');
    expect($order->lalamove_driver_phone)->toBe('+639171234567');
    expect($order->last_lalamove_webhook_at)->not->toBeNull();
});
