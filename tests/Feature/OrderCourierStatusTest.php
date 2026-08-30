<?php

use App\Models\Order;
use App\Models\User;

test('the order page shows friendly courier status labels for the customer', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'shipped',
        'lalamove_order_id' => 'ord_tracking_test',
        'lalamove_status' => 'PICKED_UP',
        'delivery_status' => 'picked_up',
        'tracking_url' => 'https://track.lalamove.test/track123',
    ]);

    $this->actingAs($user)
        ->get('/orders/'.$order->id)
        ->assertOk()
        ->assertSee('Picked Up')
        ->assertSee('Your order has been picked up by the courier.')
        ->assertSee('Track on Lalamove');
});

test('the order page renders friendly status copy instead of raw fallback text', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'shipped',
        'lalamove_order_id' => 'ord_tracking_raw',
        'lalamove_status' => 'DRIVER_ASSIGNED',
        'delivery_status' => 'driver_assigned',
    ]);

    $this->actingAs($user)
        ->get('/orders/'.$order->id)
        ->assertOk()
        ->assertSee('Courier Assigned')
        ->assertSee('A courier has been assigned.')
        ->assertDontSee('Status: DRIVER_ASSIGNED');
});