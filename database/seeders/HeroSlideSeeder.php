<?php

namespace Database\Seeders;

use App\Models\HeroSlide;
use Illuminate\Database\Seeder;

class HeroSlideSeeder extends Seeder
{
    /**
     * Seed the homepage hero slides.
     */
    public function run(): void
    {
        HeroSlide::query()->delete();

        $slides = [
            [
                'tag' => 'EXCLUSIVE DAILY CUTS',
                'heading' => 'Masterpieces of freshness, cut daily for your table.',
                'subheading' => 'Premium beef, pork, and chicken from trusted local suppliers, packed clean and delivered with care.',
                'cta_text' => 'Shop Now',
                'cta_link' => '/shop',
                'image_path' => 'frontend/images/2222.jpeg',
                'sort_order' => 0,
            ],
            [
                'tag' => 'WEEKLY SPECIALS',
                'heading' => 'Premium cuts at unbeatable prices every week.',
                'subheading' => 'Discover hand-selected deals on our finest meats. Fresh, affordable, and delivered to your door.',
                'cta_text' => 'View Deals',
                'cta_link' => '/shop',
                'image_path' => 'frontend/images/banners.png',
                'sort_order' => 1,
            ],
            [
                'tag' => 'ORDER NOW',
                'heading' => 'Premium Frozen Meats Delivered Fresh Daily',
                'subheading' => 'Samgyupsal Meat • Steak • Wagyu • Wholesale & Retail',
                'cta_text' => 'Order Now',
                'cta_link' => '/shop',
                'image_path' => 'frontend/images/banners.png',
                'sort_order' => 2,
            ],
        ];

        foreach ($slides as $slide) {
            HeroSlide::create($slide);
        }
    }
}
