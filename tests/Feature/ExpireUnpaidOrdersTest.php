<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;

test('a stale unpaid order is cancelled and its product stock is released', function () {
    $product = Product::factory()->create(['product_quantity' => 5]);
    $order = Order::factory()->create([
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
    $product->decrement('product_quantity', 2);
    $order->created_at = now()->subHours(3);
    $order->save();

    $this->artisan('orders:expire-unpaid')->assertSuccessful();

    expect($order->fresh()->status)->toBe('cancelled');
    expect($order->fresh()->payment_status)->toBe('failed');
    expect($product->fresh()->product_quantity)->toBe(5);
});

test('a stale unpaid order releases reserved variant stock', function () {
    $product = Product::factory()->create(['product_quantity' => 5]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'quantity' => 4,
    ]);
    $order = Order::factory()->create([
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'quantity' => 3,
    ]);
    $variant->decrement('quantity', 3);
    $order->created_at = now()->subHours(3);
    $order->save();

    $this->artisan('orders:expire-unpaid')->assertSuccessful();

    expect($order->fresh()->status)->toBe('cancelled');
    expect($variant->fresh()->quantity)->toBe(4);
});

test('a recent unpaid order is left alone', function () {
    $product = Product::factory()->create(['product_quantity' => 5]);
    $order = Order::factory()->create([
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
    $product->decrement('product_quantity', 2);

    $this->artisan('orders:expire-unpaid')->assertSuccessful();

    expect($order->fresh()->status)->toBe('pending');
    expect($order->fresh()->payment_status)->toBe('unpaid');
    expect($product->fresh()->product_quantity)->toBe(3);
});

test('a stale paid order is left alone', function () {
    $product = Product::factory()->create(['product_quantity' => 5]);
    $order = Order::factory()->paid()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
    $order->created_at = now()->subHours(3);
    $order->save();

    $this->artisan('orders:expire-unpaid')->assertSuccessful();

    expect($order->fresh()->payment_status)->toBe('paid');
    expect($order->fresh()->status)->toBe('pending');
    expect($product->fresh()->product_quantity)->toBe(5);
});

test('an already cancelled order is not expired again', function () {
    $product = Product::factory()->create(['product_quantity' => 5]);
    $order = Order::factory()->create([
        'status' => 'cancelled',
        'payment_status' => 'failed',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
    $order->created_at = now()->subHours(3);
    $order->save();

    $this->artisan('orders:expire-unpaid')->assertSuccessful();

    expect($order->fresh()->status)->toBe('cancelled');
    expect($product->fresh()->product_quantity)->toBe(5);
});

test('running the expirer twice does not double release stock', function () {
    $product = Product::factory()->create(['product_quantity' => 5]);
    $order = Order::factory()->create([
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
    $product->decrement('product_quantity', 2);
    $order->created_at = now()->subHours(3);
    $order->save();

    $this->artisan('orders:expire-unpaid')->assertSuccessful();
    $this->artisan('orders:expire-unpaid')->assertSuccessful();

    expect($order->fresh()->status)->toBe('cancelled');
    expect($product->fresh()->product_quantity)->toBe(5);
});
