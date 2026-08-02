<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('guests are redirected to login before admin routes', function () {
    $this->get('/view_orders')->assertRedirect('/login');
});

test('regular users cannot access admin routes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/view_orders')->assertStatus(401);
});

test('admins can access admin routes', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/view_orders')->assertOk();
});

test('admin can update an order status with a valid transition', function () {
    Mail::fake();
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create(['status' => 'pending']);

    $this->actingAs($admin)
        ->from('/view_orders')
        ->patch('/view_orders/'.$order->id.'/status', ['status' => 'processing'])
        ->assertRedirect('/view_orders')
        ->assertSessionHas('success');

    expect($order->fresh()->status)->toBe('processing');
});

test('admin cannot update an order status with an invalid transition', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create(['status' => 'pending']);

    $this->actingAs($admin)
        ->from('/view_orders')
        ->patch('/view_orders/'.$order->id.'/status', ['status' => 'delivered'])
        ->assertRedirect('/view_orders')
        ->assertSessionHasErrors('status');

    expect($order->fresh()->status)->toBe('pending');
});

test('admin cannot update an order status to an invalid value', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create(['status' => 'pending']);

    $this->actingAs($admin)
        ->from('/view_orders')
        ->patch('/view_orders/'.$order->id.'/status', ['status' => 'exploded'])
        ->assertSessionHasErrors('status');

    expect($order->fresh()->status)->toBe('pending');
});
