<?php

namespace Database\Factories;

use App\Models\InventoryHistory;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryHistory>
 */
class InventoryHistoryFactory extends Factory
{
    protected $model = InventoryHistory::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'type' => fake()->randomElement(['sale', 'restock', 'adjustment']),
            'quantity_change' => fake()->numberBetween(-20, 20),
            'quantity_before' => fake()->numberBetween(0, 100),
            'quantity_after' => fake()->numberBetween(0, 100),
            'reference_type' => null,
            'reference_id' => null,
            'notes' => null,
        ];
    }
}
