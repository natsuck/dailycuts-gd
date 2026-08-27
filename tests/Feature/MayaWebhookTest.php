<?php

use App\Jobs\DispatchLalamoveDelivery;
use App\Mail\OrderConfirmationMail;
use App\Models\Cart;
use App\Models\MayaWebhookEvent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

function mayaWebhookIp(): string
{
    return '13.229.160.234';
}

/**
 * Builds a webhook body matching the documented GET Payment response
 * (PaymentResponse schema), which Maya Checkout webhooks mirror.
 *
 * @see https://developers.maya.ph/reference/getpaymentviapaymentid-1
 */
function mayaPaymentPayload(Order $order, string $status, array $overrides = []): array
{
    $isPaid = $status === 'PAYMENT_SUCCESS';

    return array_replace_recursive([
        'id' => 'e732f996-cb87-4120-b712-166d8183c01d',
        'isPaid' => $isPaid,
        'status' => $status,
        'amount' => number_format((float) $order->total, 2, '.', ''),
        'currency' => 'PHP',
        'canVoid' => $isPaid,
        'canRefund' => $isPaid,
        'canCapture' => false,
        'createdAt' => '2026-08-14T08:00:00.000Z',
        'updatedAt' => '2026-08-14T08:01:00.000Z',
        'description' => 'Charge for customer@example.com',
        'requestReferenceNumber' => $order->payment_request_reference,
        'fundSource' => [
            'type' => 'card',
            'id' => 'GFwIun6Avo1kYe0nW7S1jVysqosocS9uMU7XNpMcUEksHx8FZXbE7TQZ3F1YQwZb5xHiAv2b2hpvrpVwYuNH1gPSPdaq8Zr90WXBRMfDVC8KkiHswlRjPz5ToTPEGy76S4S052RkAiwFcC6uAEGKnFyiGZzx7NMcYczrCK8',
            'description' => '**** **** **** 4154',
            'details' => [
                'scheme' => 'master-card',
                'last4' => '4154',
                'first6' => '545301',
                'masked' => '545301******4154',
                'issuer' => 'Others',
            ],
        ],
        'receipt' => [
            'transactionId' => '1ec32b34-eb53-4d7b-bcd4-b846aff1d601',
            'receiptNo' => 'eb1ae1105a61',
            'approvalCode' => '00001234',
        ],
        'approvalCode' => '00001234',
        'receiptNumber' => 'eb1ae1105a61',
    ], $overrides);
}

function mayaPaymentSuccessPayload(Order $order, array $overrides = []): array
{
    return mayaPaymentPayload($order, 'PAYMENT_SUCCESS', $overrides);
}

function mayaPaymentFailedPayload(Order $order, array $overrides = []): array
{
    return mayaPaymentPayload($order, 'PAYMENT_FAILED', array_replace([
        'errorCode' => 'PY0016',
        'errorMessage' => '[PY0016] Payment processor service error',
    ], $overrides));
}

function mayaPaymentExpiredPayload(Order $order, array $overrides = []): array
{
    return mayaPaymentPayload($order, 'PAYMENT_EXPIRED', $overrides);
}

function mayaPaymentCancelledPayload(Order $order, array $overrides = []): array
{
    return mayaPaymentPayload($order, 'PAYMENT_CANCELLED', $overrides);
}

/**
 * Builds a webhook body matching the Get Checkout API response, which the
 * older Checkout webhook reference says webhooks mirror. The record state
 * lives in `status` (COMPLETED/EXPIRED) and the event in `paymentStatus`.
 * There is no isPaid/amount/currency/fundSource; totals live under totalAmount.
 *
 * @see https://s3-us-west-2.amazonaws.com/developers.paymaya.com.pg/checkout/v2/Checkout+API.html
 */
function mayaCheckoutWebhookPayload(Order $order, string $paymentStatus, array $overrides = []): array
{
    return array_replace_recursive([
        'id' => 'cs_test_123',
        'status' => $paymentStatus === 'PAYMENT_SUCCESS' ? 'COMPLETED' : 'EXPIRED',
        'paymentStatus' => $paymentStatus,
        'requestReferenceNumber' => $order->payment_request_reference,
        'totalAmount' => [
            'value' => number_format((float) $order->total, 2, '.', ''),
            'currency' => 'PHP',
            'details' => null,
        ],
        'paymentScheme' => 'master-card',
        'transactionReferenceNumber' => 'txn_test_123',
        'receiptNumber' => 'rcpt_test_123',
    ], $overrides);
}

function mayaOrderWithReference(array $attributes = []): Order
{
    return Order::factory()->create(array_replace([
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'payment_request_reference' => 'rrn_123',
        'checkout_session_id' => 'cs_123',
    ], $attributes));
}

function mayaOrderWithReservedStock(int $stock = 5): array
{
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => $stock]);
    $order = mayaOrderWithReference(['user_id' => $user->id]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
    $product->decrement('product_quantity', 2);

    return [$user, $product, $order];
}

/**
 * Get Checkout API response that the server-to-server verification call
 * receives from Maya. This is the authoritative state used to confirm a
 * PAYMENT_SUCCESS webhook (the webhook body alone is never trusted).
 */
function mayaVerifiedCheckoutResponse(Order $order, string $paymentStatus, array $overrides = []): array
{
    $recordState = $paymentStatus === 'PAYMENT_SUCCESS' ? 'COMPLETED' : 'EXPIRED';

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
    Mail::fake();
    Queue::fake();
    config(['maya.webhook_ips' => [mayaWebhookIp()]]);
    config(['maya.secret_key' => 'sk_test']);
});

test('webhook is rejected when the request does not come from a Maya IP', function () {
    $order = mayaOrderWithReference();

    $this->postJson('/maya/webhook', mayaPaymentSuccessPayload($order), ['REMOTE_ADDR' => '203.0.113.10'])
        ->assertStatus(400)
        ->assertJson(['status' => 'invalid source']);
});

test('webhook is rejected when the payload does not carry an id and status', function () {
    $this->postJson('/maya/webhook', ['foo' => 'bar'], ['REMOTE_ADDR' => mayaWebhookIp()])
        ->assertStatus(400)
        ->assertJson(['status' => 'invalid payload']);
});

test('webhook with an unhandled event status is acknowledged but ignored', function () {
    $order = mayaOrderWithReference();

    $this->postJson('/maya/webhook', mayaPaymentPayload($order, 'AUTHORIZED'), ['REMOTE_ADDR' => mayaWebhookIp()])
        ->assertOk()
        ->assertJson(['status' => 'ignored']);
});

test('webhook is ignored when no matching order exists', function () {
    $order = mayaOrderWithReference();

    $payload = mayaPaymentSuccessPayload($order, ['requestReferenceNumber' => 'rrn_does_not_exist']);

    $this->postJson('/maya/webhook', $payload, ['REMOTE_ADDR' => mayaWebhookIp()])
        ->assertOk()
        ->assertJson(['status' => 'ignored']);
});

test('webhook is ignored when the request reference number is missing', function () {
    $order = mayaOrderWithReference();

    $payload = mayaPaymentSuccessPayload($order, ['requestReferenceNumber' => null]);

    $this->postJson('/maya/webhook', $payload, ['REMOTE_ADDR' => mayaWebhookIp()])
        ->assertOk()
        ->assertJson(['status' => 'ignored']);
});

test('PAYMENT_SUCCESS marks the order paid and clears the cart', function () {
    $user = User::factory()->create();
    $order = mayaOrderWithReference(['user_id' => $user->id, 'total' => 1500]);
    Cart::factory()->create(['user_id' => $user->id]);

    Http::fake([
        'pg-sandbox.paymaya.com/checkout/v1/checkouts/*' => Http::response(
            mayaVerifiedCheckoutResponse($order, 'PAYMENT_SUCCESS'),
            200,
        ),
    ]);

    $this->postJson('/maya/webhook', mayaPaymentSuccessPayload($order), ['REMOTE_ADDR' => mayaWebhookIp()])
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    $order->refresh();
    expect($order->payment_status)->toBe('paid');
    expect($order->payment_intent_id)->toBe($order->checkout_session_id);
    expect($order->payment_method)->toBe('card');
    expect(Cart::where('user_id', $user->id)->count())->toBe(0);
    Queue::assertPushed(DispatchLalamoveDelivery::class, fn (DispatchLalamoveDelivery $job) => $job->orderId === $order->id);
});

test('PAYMENT_SUCCESS queues an order confirmation email to the customer', function () {
    $user = User::factory()->create();
    $order = mayaOrderWithReference(['user_id' => $user->id, 'total' => 1500]);

    Http::fake([
        'pg-sandbox.paymaya.com/checkout/v1/checkouts/*' => Http::response(
            mayaVerifiedCheckoutResponse($order, 'PAYMENT_SUCCESS'),
            200,
        ),
    ]);

    $this->postJson('/maya/webhook', mayaPaymentSuccessPayload($order), ['REMOTE_ADDR' => mayaWebhookIp()])
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    Mail::assertQueued(OrderConfirmationMail::class, function (OrderConfirmationMail $mail) use ($order, $user) {
        return $mail->hasTo($user->email) && $mail->order->id === $order->id;
    });
});

test('order confirmation email is queued only once for re-delivered webhooks', function () {
    $user = User::factory()->create();
    $order = mayaOrderWithReference(['user_id' => $user->id, 'total' => 1500]);

    $payload = mayaPaymentSuccessPayload($order);

    Http::fake([
        'pg-sandbox.paymaya.com/checkout/v1/checkouts/*' => Http::response(
            mayaVerifiedCheckoutResponse($order, 'PAYMENT_SUCCESS'),
            200,
        ),
    ]);

    $this->postJson('/maya/webhook', $payload, ['REMOTE_ADDR' => mayaWebhookIp()])->assertOk();
    $this->postJson('/maya/webhook', $payload, ['REMOTE_ADDR' => mayaWebhookIp()])->assertOk();

    Mail::assertQueued(OrderConfirmationMail::class, 1);
});

test('PAYMENT_SUCCESS maps a maya-wallet fund source to the maya method', function () {
    $order = mayaOrderWithReference(['total' => 1500]);

    Http::fake([
        'pg-sandbox.paymaya.com/checkout/v1/checkouts/*' => Http::response(
            mayaVerifiedCheckoutResponse($order, 'PAYMENT_SUCCESS', ['paymentScheme' => 'maya-wallet']),
            200,
        ),
    ]);

    $this->postJson('/maya/webhook', $payload = mayaPaymentSuccessPayload($order), ['REMOTE_ADDR' => mayaWebhookIp()])->assertOk();

    expect($order->refresh()->payment_method)->toBe('maya');
});

test('PAYMENT_SUCCESS maps a maya-credit fund source to the maya method', function () {
    $order = mayaOrderWithReference(['total' => 1500]);

    Http::fake([
        'pg-sandbox.paymaya.com/checkout/v1/checkouts/*' => Http::response(
            mayaVerifiedCheckoutResponse($order, 'PAYMENT_SUCCESS', ['paymentScheme' => 'maya-credit']),
            200,
        ),
    ]);

    $this->postJson('/maya/webhook', mayaPaymentSuccessPayload($order), ['REMOTE_ADDR' => mayaWebhookIp()])->assertOk();

    expect($order->refresh()->payment_method)->toBe('maya');
});

test('PAYMENT_SUCCESS is ignored when the verified amount does not match the order total', function () {
    $order = mayaOrderWithReference(['total' => 1500]);

    Http::fake([
        'pg-sandbox.paymaya.com/checkout/v1/checkouts/*' => Http::response(
            mayaVerifiedCheckoutResponse($order, 'PAYMENT_SUCCESS', [
                'totalAmount' => ['value' => '1.00', 'currency' => 'PHP'],
            ]),
            200,
        ),
    ]);

    $this->postJson('/maya/webhook', mayaPaymentSuccessPayload($order), ['REMOTE_ADDR' => mayaWebhookIp()])->assertOk();

    expect($order->refresh()->payment_status)->toBe('unpaid');
});

test('PAYMENT_SUCCESS is ignored when the verified currency is not PHP', function () {
    $order = mayaOrderWithReference(['total' => 1500]);

    Http::fake([
        'pg-sandbox.paymaya.com/checkout/v1/checkouts/*' => Http::response(
            mayaVerifiedCheckoutResponse($order, 'PAYMENT_SUCCESS', [
                'totalAmount' => ['value' => '1500.00', 'currency' => 'USD'],
            ]),
            200,
        ),
    ]);

    $this->postJson('/maya/webhook', mayaPaymentSuccessPayload($order), ['REMOTE_ADDR' => mayaWebhookIp()])->assertOk();

    expect($order->refresh()->payment_status)->toBe('unpaid');
});

test('PAYMENT_SUCCESS is not confirmed when Maya does not report a successful payment', function () {
    $order = mayaOrderWithReference(['total' => 1500]);

    Http::fake([
        'pg-sandbox.paymaya.com/checkout/v1/checkouts/*' => Http::response(
            mayaVerifiedCheckoutResponse($order, 'PENDING_PAYMENT'),
            200,
        ),
    ]);

    $this->postJson('/maya/webhook', mayaPaymentSuccessPayload($order), ['REMOTE_ADDR' => mayaWebhookIp()])->assertOk();

    expect($order->refresh()->payment_status)->toBe('unpaid');
});

test('PAYMENT_SUCCESS is not confirmed when the server-to-server verification cannot be reached', function () {
    $order = mayaOrderWithReference(['total' => 1500]);

    Http::fake([
        'pg-sandbox.paymaya.com/checkout/v1/checkouts/*' => Http::response(['errors' => ['message' => 'boom']], 500),
    ]);

    $this->postJson('/maya/webhook', mayaPaymentSuccessPayload($order), ['REMOTE_ADDR' => mayaWebhookIp()])->assertOk();

    expect($order->refresh()->payment_status)->toBe('unpaid');
});

test('PAYMENT_SUCCESS webhook is idempotent', function () {
    $order = mayaOrderWithReference(['total' => 1500]);

    $payload = mayaPaymentSuccessPayload($order);

    Http::fake([
        'pg-sandbox.paymaya.com/checkout/v1/checkouts/*' => Http::response(
            mayaVerifiedCheckoutResponse($order, 'PAYMENT_SUCCESS'),
            200,
        ),
    ]);

    $this->postJson('/maya/webhook', $payload, ['REMOTE_ADDR' => mayaWebhookIp()])->assertOk();
    $this->postJson('/maya/webhook', $payload, ['REMOTE_ADDR' => mayaWebhookIp()])->assertOk();

    expect($order->refresh()->payment_status)->toBe('paid');
});

test('re-delivered webhook events are recorded once and acked as duplicates', function () {
    $order = mayaOrderWithReference(['total' => 1500]);

    $payload = mayaPaymentSuccessPayload($order);

    Http::fake([
        'pg-sandbox.paymaya.com/checkout/v1/checkouts/*' => Http::response(
            mayaVerifiedCheckoutResponse($order, 'PAYMENT_SUCCESS'),
            200,
        ),
    ]);

    $this->postJson('/maya/webhook', $payload, ['REMOTE_ADDR' => mayaWebhookIp()])
        ->assertOk()
        ->assertJson(['status' => 'ok', 'duplicate' => false]);

    $this->postJson('/maya/webhook', $payload, ['REMOTE_ADDR' => mayaWebhookIp()])
        ->assertOk()
        ->assertJson(['status' => 'ok', 'duplicate' => true]);

    expect(MayaWebhookEvent::where('payment_id', $payload['id'])
        ->where('event', 'PAYMENT_SUCCESS')->count())->toBe(1);
    expect($order->refresh()->payment_status)->toBe('paid');
});

test('a different event for the same payment id is not treated as a duplicate', function () {
    $order = mayaOrderWithReference(['total' => 1500]);

    Http::fake([
        'pg-sandbox.paymaya.com/checkout/v1/checkouts/*' => Http::response(
            mayaVerifiedCheckoutResponse($order, 'PAYMENT_SUCCESS'),
            200,
        ),
    ]);

    $this->postJson('/maya/webhook', mayaPaymentSuccessPayload($order), ['REMOTE_ADDR' => mayaWebhookIp()])
        ->assertOk()
        ->assertJson(['duplicate' => false]);

    $this->postJson('/maya/webhook', mayaPaymentExpiredPayload($order), ['REMOTE_ADDR' => mayaWebhookIp()])
        ->assertOk()
        ->assertJson(['duplicate' => false]);

    expect(MayaWebhookEvent::where('payment_id', 'e732f996-cb87-4120-b712-166d8183c01d')->count())->toBe(2);
});

test('PAYMENT_SUCCESS is ignored for a cancelled order', function () {
    $order = mayaOrderWithReference(['status' => 'cancelled', 'total' => 1500]);

    $payload = mayaPaymentSuccessPayload($order);

    $this->postJson('/maya/webhook', $payload, ['REMOTE_ADDR' => mayaWebhookIp()])->assertOk();

    expect($order->refresh()->payment_status)->toBe('unpaid');
});

test('PAYMENT_SUCCESS sent via the paymentStatus field is processed', function () {
    $order = mayaOrderWithReference(['total' => 1500]);

    $payload = mayaPaymentSuccessPayload($order);
    unset($payload['status']);
    $payload['paymentStatus'] = 'PAYMENT_SUCCESS';

    Http::fake([
        'pg-sandbox.paymaya.com/checkout/v1/checkouts/*' => Http::response(
            mayaVerifiedCheckoutResponse($order, 'PAYMENT_SUCCESS'),
            200,
        ),
    ]);

    $this->postJson('/maya/webhook', $payload, ['REMOTE_ADDR' => mayaWebhookIp()])->assertOk();

    expect($order->refresh()->payment_status)->toBe('paid');
});

test('a Get Checkout shaped webhook (status COMPLETED + paymentStatus) is processed', function () {
    $user = User::factory()->create();
    $order = mayaOrderWithReference(['user_id' => $user->id, 'total' => 1500]);
    Cart::factory()->create(['user_id' => $user->id]);

    Http::fake([
        'pg-sandbox.paymaya.com/checkout/v1/checkouts/*' => Http::response(
            mayaVerifiedCheckoutResponse($order, 'PAYMENT_SUCCESS'),
            200,
        ),
    ]);

    $this->postJson('/maya/webhook', mayaCheckoutWebhookPayload($order, 'PAYMENT_SUCCESS'), ['REMOTE_ADDR' => mayaWebhookIp()])
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    $order->refresh();
    expect($order->payment_status)->toBe('paid');
    expect($order->payment_method)->toBe('card');
    expect($order->payment_intent_id)->toBe($order->checkout_session_id);
    expect(Cart::where('user_id', $user->id)->count())->toBe(0);
});

test('a Get Checkout shaped expired webhook releases stock and cancels the order', function () {
    [$user, $product, $order] = mayaOrderWithReservedStock();

    $this->postJson('/maya/webhook', mayaCheckoutWebhookPayload($order, 'PAYMENT_EXPIRED'), ['REMOTE_ADDR' => mayaWebhookIp()])
        ->assertOk();

    $order->refresh();
    expect($order->payment_status)->toBe('failed');
    expect($order->status)->toBe('cancelled');
    expect($product->fresh()->product_quantity)->toBe(5);
});

test('a Get Checkout shaped success webhook is ignored on an amount mismatch', function () {
    $order = mayaOrderWithReference(['total' => 1500]);

    Http::fake([
        'pg-sandbox.paymaya.com/checkout/v1/checkouts/*' => Http::response(
            mayaVerifiedCheckoutResponse($order, 'PAYMENT_SUCCESS', [
                'totalAmount' => ['value' => '1.00', 'currency' => 'PHP'],
            ]),
            200,
        ),
    ]);

    $this->postJson('/maya/webhook', mayaCheckoutWebhookPayload($order, 'PAYMENT_SUCCESS'), ['REMOTE_ADDR' => mayaWebhookIp()])
        ->assertOk();

    expect($order->refresh()->payment_status)->toBe('unpaid');
});

test('PAYMENT_FAILED releases reserved stock and cancels the order', function () {
    [$user, $product, $order] = mayaOrderWithReservedStock();

    $this->postJson('/maya/webhook', mayaPaymentFailedPayload($order), ['REMOTE_ADDR' => mayaWebhookIp()])->assertOk();

    $order->refresh();
    expect($order->payment_status)->toBe('failed');
    expect($order->status)->toBe('cancelled');
    expect($product->fresh()->product_quantity)->toBe(5);
});

test('PAYMENT_EXPIRED releases reserved stock and cancels the order', function () {
    [$user, $product, $order] = mayaOrderWithReservedStock();

    $this->postJson('/maya/webhook', mayaPaymentExpiredPayload($order), ['REMOTE_ADDR' => mayaWebhookIp()])->assertOk();

    $order->refresh();
    expect($order->payment_status)->toBe('failed');
    expect($order->status)->toBe('cancelled');
    expect($product->fresh()->product_quantity)->toBe(5);
});

test('PAYMENT_CANCELLED releases reserved stock and cancels the order', function () {
    [$user, $product, $order] = mayaOrderWithReservedStock();

    $this->postJson('/maya/webhook', mayaPaymentCancelledPayload($order), ['REMOTE_ADDR' => mayaWebhookIp()])->assertOk();

    $order->refresh();
    expect($order->payment_status)->toBe('failed');
    expect($order->status)->toBe('cancelled');
    expect($product->fresh()->product_quantity)->toBe(5);
});

test('failed webhooks are idempotent and do not double-restore stock', function () {
    [$user, $product, $order] = mayaOrderWithReservedStock();

    $payload = mayaPaymentFailedPayload($order);

    $this->postJson('/maya/webhook', $payload, ['REMOTE_ADDR' => mayaWebhookIp()])->assertOk();
    $this->postJson('/maya/webhook', $payload, ['REMOTE_ADDR' => mayaWebhookIp()])->assertOk();

    expect($product->fresh()->product_quantity)->toBe(5);
});

test('PAYMENT_FAILED does not touch an already paid order', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    $order = mayaOrderWithReference([
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

    $this->postJson('/maya/webhook', mayaPaymentFailedPayload($order), ['REMOTE_ADDR' => mayaWebhookIp()])->assertOk();

    $order->refresh();
    expect($order->status)->toBe('processing');
    expect($order->payment_status)->toBe('paid');
    expect($product->fresh()->product_quantity)->toBe(3);
});
