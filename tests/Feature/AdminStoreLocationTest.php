<?php

use App\Models\StoreLocation;
use App\Models\User;
use App\Services\OrderPricingService;

function adminLocationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Pasay Branch',
        'address' => '161 Vallhalla Extension',
        'city' => 'Pasay City, Metro Manila',
        'phone' => '+639123456789',
        'lat' => '14.5378',
        'lng' => '121.0022',
        'sort_order' => 0,
        'is_active' => 1,
        'is_pickup' => 0,
    ], $overrides);
}

test('admin can view store locations index', function () {
    $admin = User::factory()->admin()->create();
    StoreLocation::create(adminLocationPayload(['name' => 'Pasay Branch', 'is_pickup' => 1]));

    $this->actingAs($admin)
        ->get(route('admin.store-locations.index'))
        ->assertOk()
        ->assertSee('Pasay Branch');
});

test('store locations routes are protected', function () {
    $this->get(route('admin.store-locations.index'))->assertStatus(302);

    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('admin.store-locations.index'))
        ->assertStatus(401);
});

test('admin can create a store location and set it as pickup', function () {
    $admin = User::factory()->admin()->create();
    StoreLocation::create(adminLocationPayload());

    $this->actingAs($admin)
        ->post(route('admin.store-locations.store'), adminLocationPayload(['name' => 'Bacoor Branch', 'is_pickup' => 1]))
        ->assertRedirect(route('admin.store-locations.index'));

    expect(StoreLocation::count())->toBe(2);
    expect(StoreLocation::where('name', 'Bacoor Branch')->first()->is_pickup)->toBeTrue();
    expect(StoreLocation::where('name', 'Pasay Branch')->first()->is_pickup)->toBeFalse();
});

test('store location validation rejects invalid coordinates', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('admin.store-locations.create'))
        ->post(route('admin.store-locations.store'), adminLocationPayload(['lat' => '50', 'lng' => '10']))
        ->assertSessionHasErrors(['lat', 'lng']);

    expect(StoreLocation::count())->toBe(0);
});

test('admin can update a store location and reassign pickup', function () {
    $admin = User::factory()->admin()->create();
    $pasay = StoreLocation::create(adminLocationPayload(['is_pickup' => 1]));
    $gtrias = StoreLocation::create(adminLocationPayload(['name' => 'G.Trias']));

    $this->actingAs($admin)
        ->patch(route('admin.store-locations.update', $gtrias), adminLocationPayload([
            'name' => 'General Trias',
            'is_pickup' => 1,
        ]))
        ->assertRedirect(route('admin.store-locations.index'));

    expect($gtrias->fresh()->name)->toBe('General Trias');
    expect($gtrias->fresh()->is_pickup)->toBeTrue();
    expect($pasay->fresh()->is_pickup)->toBeFalse();
});

test('deleting the pickup location promotes the next active one', function () {
    $admin = User::factory()->admin()->create();
    $pasay = StoreLocation::create(adminLocationPayload(['is_pickup' => 1, 'sort_order' => 0]));
    $gtrias = StoreLocation::create(adminLocationPayload(['name' => 'G.Trias', 'sort_order' => 1]));

    $this->actingAs($admin)
        ->delete(route('admin.store-locations.destroy', $pasay))
        ->assertRedirect(route('admin.store-locations.index'));

    expect(StoreLocation::find($pasay->id))->toBeNull();
    expect($gtrias->fresh()->is_pickup)->toBeTrue();
});

test('order pricing warehouse uses the admin-managed pickup location', function () {
    $pickup = StoreLocation::create(adminLocationPayload(['is_pickup' => 1]));

    $warehouse = app(OrderPricingService::class)->warehouse();

    expect($warehouse['name'])->toBe($pickup->name);
    expect($warehouse['address'])->toBe($pickup->fullAddress());
    expect((float) $warehouse['lat'])->toBe((float) $pickup->lat);
    expect((float) $warehouse['lng'])->toBe((float) $pickup->lng);
});

test('order pricing warehouse falls back to config when no pickup exists', function () {
    $warehouse = app(OrderPricingService::class)->warehouse();

    expect($warehouse['address'])->toBe(config('shop.store.warehouse.address'));
});

test('store locations page lists active locations only', function () {
    StoreLocation::create(adminLocationPayload(['name' => 'Pasay Branch', 'is_active' => 1]));
    StoreLocation::create(adminLocationPayload(['name' => 'Hidden Branch', 'is_active' => 0]));

    $this->get(route('store.locations'))
        ->assertOk()
        ->assertSee('Pasay Branch')
        ->assertDontSee('Hidden Branch');
});
