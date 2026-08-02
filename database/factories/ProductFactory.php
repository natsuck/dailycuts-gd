<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'product_title' => fake()->unique()->words(3, true),
            'product_description' => fake()->sentence(12),
            'product_quantity' => fake()->numberBetween(0, 100),
            'reorder_level' => 10,
            'expiry_date' => fake()->dateTimeBetween('+7 days', '+90 days')->format('Y-m-d'),
            'product_price' => fake()->randomFloat(2, 50, 5000),
            'product_image' => null,
            'product_category' => 'Meat',
            'product_type' => fake()->randomElement(['fresh', 'frozen', 'pantry', 'produce']),
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_quantity' => 0,
        ]);
    }
}
