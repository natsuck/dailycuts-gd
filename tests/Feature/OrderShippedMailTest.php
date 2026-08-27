<?php

use App\Mail\OrderDeliveredMail;
use App\Mail\OrderShippedMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('shipped email is queued to the customer when order is marked shipped', function () {
    Mail::fake();
    Mail::assertNothingQueued();

    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create([
        'status' => 'processing',
        'payment_status' => 'paid',
        'tracking_url' => 'https://track.lalamove.test/order_123',
    ]);

    $this->actingAs($admin)
        ->from('/view_orders')
        ->patch('/view_orders/'.$order->id.'/status', ['status' => 'shipped'])
        ->assertRedirect('/view_orders')
        ->assertSessionHas('success');

    expect($order->fresh()->status)->toBe('shipped');

    Mail::assertQueued(OrderShippedMail::class, function (OrderShippedMail $mail) use ($order) {
        return $mail->hasTo($order->user->email) && $mail->order->id === $order->id;
    });
});

test('delivered email is queued to the customer when order is marked delivered', function () {
    Mail::fake();
    Mail::assertNothingQueued();

    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create([
        'status' => 'shipped',
        'payment_status' => 'paid',
    ]);

    $this->actingAs($admin)
        ->from('/view_orders')
        ->patch('/view_orders/'.$order->id.'/status', ['status' => 'delivered'])
        ->assertRedirect('/view_orders')
        ->assertSessionHas('success');

    expect($order->fresh()->status)->toBe('delivered');

    Mail::assertQueued(OrderDeliveredMail::class, function (OrderDeliveredMail $mail) use ($order) {
        return $mail->hasTo($order->user->email) && $mail->order->id === $order->id;
    });
});

test('shipped email renders the lalamove tracking link', function () {
    $order = Order::factory()->create([
        'tracking_url' => 'https://track.lalamove.test/order_123',
    ]);

    $mail = new OrderShippedMail($order);

    $html = $mail->render();

    expect($html)->toContain('Track on Lalamove');
    expect($html)->toContain('https://track.lalamove.test/order_123');
});

test('shipped email shows a fallback note when no tracking link exists', function () {
    $order = Order::factory()->create(['tracking_url' => null]);

    $html = (new OrderShippedMail($order))->render();

    expect($html)->toContain('Tracking will be available once your courier has been assigned');
});
