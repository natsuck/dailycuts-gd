<?php

namespace Database\Factories;

use App\Models\SaleBanner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SaleBanner>
 */
class SaleBannerFactory extends Factory
{
    protected $model = SaleBanner::class;

    public function definition(): array
    {
        return [
            'badge_text' => fake()->randomElement(['NEW', 'SALE', 'LIMITED']),
            'title' => fake()->words(4, true),
            'subtitle' => fake()->sentence(8),
            'button_text' => 'Shop Now',
            'button_url' => fake()->url(),
            'image_path' => null,
            'background_color' => '#7a1118',
            'text_color' => '#ffffff',
            'sort_order' => 0,
            'starts_at' => null,
            'ends_at' => null,
            'is_active' => true,
        ];
    }
}
