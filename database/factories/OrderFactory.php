<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'address' => fake()->streetAddress().', San Antonio, '.fake()->city(),
            'barangay' => 'San Antonio',
            'region' => 'Metro Manila',
            'city' => 'Pasay',
            'phone' => '09'.fake()->numerify('#########'),
            'total' => fake()->randomFloat(2, 100, 20000),
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'shipping_fee' => 0,
            'notes' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'payment_status' => 'paid',
        ]);
    }
}
