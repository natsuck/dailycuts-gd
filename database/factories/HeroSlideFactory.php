<?php

namespace Database\Factories;

use App\Models\HeroSlide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HeroSlide>
 */
class HeroSlideFactory extends Factory
{
    protected $model = HeroSlide::class;

    public function definition(): array
    {
        return [
            'tag' => fake()->randomElement(['EXCLUSIVE DAILY CUTS', 'WEEKLY SPECIALS', 'ORDER NOW']),
            'heading' => fake()->sentence(6),
            'subheading' => fake()->sentence(12),
            'cta_text' => 'Shop Now',
            'cta_link' => '/shop',
            'image_path' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
