<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('?????-?????'),
            'type' => 'percentage',
            'value' => 20,
            'min_order' => 0,
            'max_discount' => null,
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
            'starts_at' => null,
            'expires_at' => null,
        ];
    }

    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fixed',
            'value' => 100,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
