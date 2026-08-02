<?php

use App\Models\User;

test('admin coupons page loads for admin', function () {
    $admin = User::factory()->create(['user_type' => 'admin']);

    $this->actingAs($admin)
        ->get('/admin/coupons')
        ->assertOk();
});

test('unauthenticated admin coupons status', function () {
    $this->get('/admin/coupons')->dump()->assertStatus(302);
});

test('unauthenticated view_orders status', function () {
    $this->get('/view_orders')->dump()->assertStatus(302);
});
