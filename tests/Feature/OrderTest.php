<?php

use App\Models\Order;
use App\Models\User;

test('guests are redirected to login on the orders page', function () {
    $this->get('/orders')->assertRedirect('/login');
});

test('a user can view their own order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get('/orders/'.$order->id)->assertOk();
});

test('a user cannot view another user\'s order', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($other)->get('/orders/'.$order->id)->assertNotFound();
});

test('the orders index only lists the current user\'s orders', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Order::factory()->count(2)->create(['user_id' => $user->id]);
    Order::factory()->create(['user_id' => $other->id]);

    $response = $this->actingAs($user)->get('/orders');

    $response->assertOk();
    expect(Order::where('user_id', $user->id)->count())->toBe(2);
});
