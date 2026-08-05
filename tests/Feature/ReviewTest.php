<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;

test('a user who purchased the product can submit a review', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $order = Order::factory()->paid()->create(['user_id' => $user->id]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->from('/product_details/'.$product->id)
        ->post('/products/'.$product->id.'/reviews', [
            'rating' => 5,
            'title' => 'Great cuts',
            'body' => 'Really fresh and tasty.',
        ])
        ->assertRedirect('/product_details/'.$product->id);

    $this->assertDatabaseHas('reviews', [
        'user_id' => $user->id,
        'product_id' => $product->id,
        'rating' => 5,
    ]);
});

test('a user cannot review a product they never purchased', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->from('/product_details/'.$product->id)
        ->post('/products/'.$product->id.'/reviews', [
            'rating' => 5,
            'title' => 'Not a customer',
            'body' => 'Should be blocked.',
        ])
        ->assertRedirect('/product_details/'.$product->id)
        ->assertSessionHasErrors('review');

    expect(Review::count())->toBe(0);
});

test('a cancelled purchase does not grant review rights', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $order = Order::factory()->paid()->create([
        'user_id' => $user->id,
        'status' => 'cancelled',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->from('/product_details/'.$product->id)
        ->post('/products/'.$product->id.'/reviews', ['rating' => 4])
        ->assertSessionHasErrors('review');

    expect(Review::count())->toBe(0);
});

test('a review requires a valid rating', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->from('/product_details/'.$product->id)
        ->post('/products/'.$product->id.'/reviews', ['rating' => 6])
        ->assertSessionHasErrors('rating');

    expect(Review::count())->toBe(0);
});

test('a user can delete their own review', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $review = Review::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

    $this->actingAs($user)
        ->delete('/reviews/'.$review->id)
        ->assertRedirect();

    expect(Review::find($review->id))->toBeNull();
});

test('a user cannot delete another user\'s review', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $product = Product::factory()->create();
    $review = Review::factory()->create(['user_id' => $owner->id, 'product_id' => $product->id]);

    $this->actingAs($other)->delete('/reviews/'.$review->id)->assertNotFound();

    expect(Review::find($review->id))->not->toBeNull();
});
