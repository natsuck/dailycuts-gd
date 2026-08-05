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
