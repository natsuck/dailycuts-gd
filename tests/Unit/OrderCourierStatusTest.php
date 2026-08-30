<?php

use App\Models\Order;

test('courier status map covers every Lalamove status', function () {
    $map = Order::courierStatusMap();

    foreach (['PENDING', 'PLACED', 'ASSIGNING_DRIVER', 'DRIVER_ASSIGNED', 'ON_GOING', 'PICKED_UP', 'COMPLETED', 'CANCELED', 'CANCELLED', 'EXPIRED', 'REJECTED', 'FAILED'] as $status) {
        expect($map)->toHaveKey($status)
            ->and($map[$status])->toHaveKeys(['short', 'text']);
    }
});

test('courier status helpers normalize casing and fall back gracefully', function () {
    $order = new Order();
    $order->lalamove_status = 'picked_up';

    expect($order->courierStatusKey())->toBe('PICKED_UP')
        ->and($order->courierStatusShort())->toBe('Picked Up')
        ->and($order->courierStatusText())->toBe('Your order has been picked up by the courier.');

    $unknown = new Order();
    $unknown->lalamove_status = 'SOMETHING_UNKNOWN';

    expect($unknown->courierStatusShort())->toBe('SOMETHING_UNKNOWN')
        ->and($unknown->courierStatusText())->toBe('Courier status: SOMETHING_UNKNOWN');

    $none = new Order();

    expect($none->courierStatusShort())->toBe('Pending')
        ->and($none->courierStatusText())->toBe('Courier status: N/A');
});