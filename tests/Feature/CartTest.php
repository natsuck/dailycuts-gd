<?php

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

test('guests cannot add items to the cart', function () {
    $product = Product::factory()->create(['product_quantity' => 5]);

    $this->post('/cart/'.$product->id)->assertRedirect('/login');
});

test('authenticated user can add a product to the cart', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);

    $this->actingAs($user)
        ->from('/shop')
        ->post('/cart/'.$product->id, ['quantity' => 2])
        ->assertRedirect('/shop')
        ->assertSessionHas('cartMessage');

    $this->assertDatabaseHas('carts', [
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
});

test('adding the same product merges quantities into one cart row', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 10]);

    $this->actingAs($user)->post('/cart/'.$product->id, ['quantity' => 2]);
    $this->actingAs($user)->post('/cart/'.$product->id, ['quantity' => 3]);

    $rows = Cart::where('user_id', $user->id)->where('product_id', $product->id)->get();

    expect($rows)->toHaveCount(1);
    expect($rows->first()->quantity)->toBe(5);
});

test('cannot add more items than available stock', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 3]);

    $this->actingAs($user)
        ->from('/shop')
        ->post('/cart/'.$product->id, ['quantity' => 4])
        ->assertRedirect('/shop')
        ->assertSessionHasErrors('quantity');

    expect(Cart::where('user_id', $user->id)->where('product_id', $product->id)->exists())->toBeFalse();
});

test('user can update their own cart item quantity', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 10]);
    $cart = Cart::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 1]);

    $response = $this->actingAs($user)->patchJson('/cart/update/'.$cart->id, ['quantity' => 4]);

    $response->assertOk()->assertJson(['success' => true, 'quantity' => 4]);
    expect($cart->fresh()->quantity)->toBe(4);
});

test('user cannot update another user\'s cart item', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 10]);
    $cart = Cart::factory()->create(['user_id' => $owner->id, 'product_id' => $product->id, 'quantity' => 1]);

    $this->actingAs($other)
        ->patchJson('/cart/update/'.$cart->id, ['quantity' => 4])
        ->assertForbidden();

    expect($cart->fresh()->quantity)->toBe(1);
});

test('user can remove their own cart item', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 10]);
    $cart = Cart::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

    $this->actingAs($user)
        ->from('/viewcart')
        ->delete('/cart/'.$cart->id)
        ->assertRedirect('/viewcart');

    expect(Cart::find($cart->id))->toBeNull();
});

test('user cannot remove another user\'s cart item', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 10]);
    $cart = Cart::factory()->create(['user_id' => $owner->id, 'product_id' => $product->id]);

    $this->actingAs($other)->delete('/cart/'.$cart->id)->assertNotFound();

    expect(Cart::find($cart->id))->not->toBeNull();
});

test('user can change the variant of their own cart item', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 10]);
    $variantA = ProductVariant::factory()->create(['product_id' => $product->id, 'weight' => '250g', 'quantity' => 5]);
    $variantB = ProductVariant::factory()->create(['product_id' => $product->id, 'weight' => '500g', 'quantity' => 5]);
    $cart = Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'variant_id' => $variantA->id,
        'quantity' => 2,
    ]);

    $response = $this->actingAs($user)->patchJson('/cart/'.$cart->id.'/variant', [
        'variant_id' => $variantB->id,
    ]);

    $response->assertOk()->assertJson(['success' => true]);
    expect($cart->fresh()->variant_id)->toBe($variantB->id);
});

test('user cannot change variant on another user\'s cart item', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 10]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'quantity' => 5]);
    $cart = Cart::factory()->create([
        'user_id' => $owner->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
    ]);

    $this->actingAs($other)
        ->patchJson('/cart/'.$cart->id.'/variant', ['variant_id' => $variant->id])
        ->assertForbidden();
});
