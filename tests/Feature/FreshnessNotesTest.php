<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;

test('freshnessNotes falls back to generic defaults when fields are empty', function () {
    $product = Product::factory()->create([
        'fresh_storage' => null,
        'fresh_prep' => null,
        'fresh_delivery' => null,
        'fresh_best_for' => null,
    ]);

    expect($product->freshnessNotes())->toBe([
        'Storage' => 'Keep chilled',
        'Prep' => 'Cook thoroughly',
        'Delivery' => 'Temperature-controlled',
        'Best For' => 'Daily meals',
    ]);
});

test('freshnessNotes returns per-product values when set', function () {
    $product = Product::factory()->create([
        'fresh_storage' => 'Freeze for longer storage',
        'fresh_prep' => 'Best served cooked',
        'fresh_delivery' => 'Frozen delivery',
        'fresh_best_for' => 'Stews & slow cooking',
    ]);

    expect($product->freshnessNotes())->toBe([
        'Storage' => 'Freeze for longer storage',
        'Prep' => 'Best served cooked',
        'Delivery' => 'Frozen delivery',
        'Best For' => 'Stews & slow cooking',
    ]);
});

test('freshnessNotes falls back per-field when only some values are set', function () {
    $product = Product::factory()->create([
        'fresh_storage' => 'Keep chilled',
        'fresh_prep' => null,
        'fresh_delivery' => null,
        'fresh_best_for' => 'Grilling',
    ]);

    expect($product->freshnessNotes())->toBe([
        'Storage' => 'Keep chilled',
        'Prep' => 'Cook thoroughly',
        'Delivery' => 'Temperature-controlled',
        'Best For' => 'Grilling',
    ]);
});

test('product details page renders per-product freshness notes', function () {
    $product = Product::factory()->create([
        'fresh_storage' => 'Freeze immediately',
        'fresh_prep' => 'Cook from frozen',
        'fresh_delivery' => 'Frozen delivery',
        'fresh_best_for' => 'Grilling',
    ]);

    $this->get('/product_details/'.$product->id)->assertOk()
        ->assertSee('Freeze immediately')
        ->assertSee('Cook from frozen')
        ->assertSee('Frozen delivery')
        ->assertSee('Grilling');
});

test('product details page renders generic defaults when no notes are set', function () {
    $product = Product::factory()->create();

    $this->get('/product_details/'.$product->id)->assertOk()
        ->assertSee('Keep chilled')
        ->assertSee('Cook thoroughly')
        ->assertSee('Temperature-controlled')
        ->assertSee('Daily meals');
});

test('admin can save freshness notes when adding a product', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/add_product', [
        'product_title' => 'Test Cut',
        'product_description' => 'A test product',
        'product_category' => 'Beef',
        'product_type' => 'fresh',
        'product_quantity' => 10,
        'product_price' => 100,
        'reorder_level' => 10,
        'product_image' => UploadedFile::fake()->create('cut.jpg', 10, 'image/jpeg'),
        'fresh_storage' => 'Keep chilled',
        'fresh_prep' => 'Cook thoroughly',
        'fresh_delivery' => 'Chilled delivery',
        'fresh_best_for' => 'Everyday meals',
    ])->assertRedirect();

    $product = Product::where('product_title', 'Test Cut')->first();

    expect($product)->not->toBeNull();
    expect($product->fresh_storage)->toBe('Keep chilled');
    expect($product->fresh_prep)->toBe('Cook thoroughly');
    expect($product->fresh_delivery)->toBe('Chilled delivery');
    expect($product->fresh_best_for)->toBe('Everyday meals');
});

test('admin can update freshness notes on a product', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create([
        'fresh_storage' => 'Old storage',
        'fresh_prep' => 'Old prep',
        'fresh_delivery' => 'Old delivery',
        'fresh_best_for' => 'Old best for',
    ]);

    $this->actingAs($admin)->post('/update_product/'.$product->id, [
        'product_title' => $product->product_title,
        'product_description' => $product->product_description,
        'product_category' => $product->product_category,
        'product_type' => $product->product_type,
        'product_quantity' => $product->product_quantity,
        'product_price' => $product->product_price,
        'fresh_storage' => 'Freeze for storage',
        'fresh_prep' => 'Thaw before cooking',
        'fresh_delivery' => 'Frozen delivery',
        'fresh_best_for' => 'Grilling',
    ])->assertRedirect();

    $product->refresh();

    expect($product->fresh_storage)->toBe('Freeze for storage');
    expect($product->fresh_prep)->toBe('Thaw before cooking');
    expect($product->fresh_delivery)->toBe('Frozen delivery');
    expect($product->fresh_best_for)->toBe('Grilling');
});
