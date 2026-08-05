<?php

namespace Database\Seeders;

use App\Models\StoreLocation;
use Illuminate\Database\Seeder;

class StoreLocationSeeder extends Seeder
{
    public function run(): void
    {
        $branches = config('shop.store.branches', []);

        foreach ($branches as $index => $branch) {
            StoreLocation::updateOrCreate(
                ['name' => $branch['name']],
                [
                    'address' => $branch['address'],
                    'phone' => config('shop.store.branch_phone', '+639000000000'),
                    'lat' => $branch['lat'],
                    'lng' => $branch['lng'],
                    'is_active' => true,
                    'is_pickup' => $index === 0,
                    'sort_order' => $index,
                ],
            );
        }
    }
}
