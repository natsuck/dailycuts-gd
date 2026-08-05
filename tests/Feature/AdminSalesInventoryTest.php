<?php

use App\Models\InventoryHistory;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;

test('reports page loads for admins', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin/reports')
        ->assertOk()
        ->assertSee('Reports')
        ->assertSee('Export CSV');
});

test('reports page loads with a date range filter', function () {
    $admin = User::factory()->admin()->create();
    $from = Carbon::today()->subDays(7)->format('Y-m-d');
    $to = Carbon::today()->format('Y-m-d');

    $this->actingAs($admin)->get('/admin/reports?from='.$from.'&to='.$to)
        ->assertOk()
        ->assertSee('Daily Sales');
});

test('reports csv export returns a csv attachment', function () {
    $admin = User::factory()->admin()->create();
    $from = Carbon::today()->subDays(7)->format('Y-m-d');
    $to = Carbon::today()->format('Y-m-d');

    $response = $this->actingAs($admin)->get('/admin/reports/export/csv?from='.$from.'&to='.$to);

    $response->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename="sales_report_'.$from.'_to_'.$to.'.csv"');

    expect($response->headers->get('content-type'))->toContain('text/csv')
        ->and($response->streamedContent())->toContain('Order ID');
});

test('reports csv export escapes formula injection in customer names', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    Order::factory()->create([
        'user_id' => $user->id,
        'name' => '=HYPERLINK("http://evil.example","click")',
        'payment_status' => 'paid',
    ]);
    $from = Carbon::today()->format('Y-m-d');
    $to = Carbon::today()->format('Y-m-d');

    $response = $this->actingAs($admin)->get('/admin/reports/export/csv?from='.$from.'&to='.$to);

    $response->assertOk();

    expect($response->streamedContent())
        ->toContain('"\'=HYPERLINK(""http://evil.example"",""click"")"')
        ->not->toContain('=HYPERLINK("http://evil.example"');
});

test('inventory page loads and lists products', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create();

    $this->actingAs($admin)->get('/admin/inventory')
        ->assertOk()
        ->assertSee($product->product_title);
});

test('low stock inventory page loads', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin/inventory/low-stock')
        ->assertOk();
});

test('inventory history page renders the product change log', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create();
    InventoryHistory::factory()->create([
        'product_id' => $product->id,
        'type' => 'restock',
        'quantity_change' => 5,
        'quantity_before' => 10,
        'quantity_after' => 15,
        'notes' => 'Weekly delivery',
    ]);

    $this->actingAs($admin)->get('/admin/inventory/history/'.$product->id)
        ->assertOk()
        ->assertSee($product->product_title)
        ->assertSee('Restock')
        ->assertSee('Weekly delivery');
});
