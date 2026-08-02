<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'weight' => fake()->randomElement(['250g', '500g', '1kg']),
            'price' => fake()->randomFloat(2, 50, 5000),
            'quantity' => fake()->numberBetween(0, 50),
        ];
    }
}
