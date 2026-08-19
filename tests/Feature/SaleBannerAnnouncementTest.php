<?php

use App\Models\SaleBanner;
use App\Models\User;

test('the announcement ticker renders active banners flagged for the ticker', function () {
    SaleBanner::factory()->create([
        'title' => 'FREE DELIVERY OVER P500',
        'show_in_ticker' => true,
        'is_active' => true,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('FREE DELIVERY OVER P500', false)
        ->assertSee('aria-label="Announcements"', false);
});

test('the announcement ticker is hidden when no banner is flagged for it', function () {
    SaleBanner::factory()->create([
        'title' => 'Weekend Sale',
        'show_in_ticker' => false,
        'is_active' => true,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('aria-label="Announcements"', false)
        ->assertDontSee('Weekend Sale', false);
});

test('the announcement ticker hides inactive banners', function () {
    SaleBanner::factory()->create([
        'title' => 'Not Running',
        'show_in_ticker' => true,
        'is_active' => false,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('Not Running', false)
        ->assertDontSee('aria-label="Announcements"', false);
});

test('the announcement ticker hides banners outside their schedule', function () {
    SaleBanner::factory()->create([
        'title' => 'Past Promo',
        'show_in_ticker' => true,
        'is_active' => true,
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->subDays(5),
    ]);

    SaleBanner::factory()->create([
        'title' => 'Future Promo',
        'show_in_ticker' => true,
        'is_active' => true,
        'starts_at' => now()->addDays(5),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('Past Promo', false)
        ->assertDontSee('Future Promo', false)
        ->assertDontSee('aria-label="Announcements"', false);
});

test('admin can flag a sale banner for the announcement ticker', function () {
    $admin = User::factory()->create(['user_type' => 'admin']);

    $this->actingAs($admin)
        ->post('/sale-banners', [
            'title' => 'Buy One Get One',
            'badge_text' => 'BOGO',
            'background_color' => '#7a1118',
            'text_color' => '#ffffff',
            'is_active' => '1',
            'show_in_ticker' => '1',
            'sort_order' => 1,
        ])
        ->assertRedirect(route('admin.sale-banners.index'));

    expect(SaleBanner::where('title', 'Buy One Get One')->first()->show_in_ticker)->toBeTrue();
});

test('admin can unflag a sale banner from the announcement ticker', function () {
    $admin = User::factory()->create(['user_type' => 'admin']);
    $banner = SaleBanner::factory()->create([
        'title' => 'Flash Sale',
        'show_in_ticker' => true,
    ]);

    $this->actingAs($admin)
        ->patch('/sale-banners/'.$banner->id, [
            'title' => 'Flash Sale',
            'badge_text' => 'SALE',
            'background_color' => '#7a1118',
            'text_color' => '#ffffff',
            'is_active' => '1',
            'show_in_ticker' => null,
            'sort_order' => 0,
        ])
        ->assertRedirect(route('admin.sale-banners.index'));

    expect($banner->fresh()->show_in_ticker)->toBeFalse();
});
