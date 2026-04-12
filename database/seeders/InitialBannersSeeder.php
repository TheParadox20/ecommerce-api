<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InitialBannersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\HomepageBanner::truncate();

        $banners = [
            // Homepage
            ['image' => 'carousel/OatsPoster.webp', 'page' => 'homepage', 'title' => 'Hearty Oats', 'description' => 'Hearty oats & nourishing grain essentials for everyday meals.', 'order' => 1],
            ['image' => 'carousel/nanacare.jpeg',  'page' => 'homepage', 'title' => 'Comfort Care', 'description' => 'Comfort-first baby care for feeding, storage, and daily ease.', 'order' => 2],
            ['image' => 'carousel/nutmill.jpeg',   'page' => 'homepage', 'title' => 'Roasted Nuts', 'description' => 'Bold nuts, seeds & snackable pantry picks roasted to perfection.', 'order' => 3],

            // About Us
            ['image' => 'about_hero/about-hero-3.jpeg', 'page' => 'about', 'title' => 'Our Story', 'description' => 'Discover the story behind ngwindsongk and our commitment to healthy living.', 'order' => 1],
            ['image' => 'about_hero/about-hero-2.jpeg', 'page' => 'about', 'title' => 'Our Mission', 'description' => 'To empower healthy living through nutritious food choices.', 'order' => 2],
            ['image' => 'about_hero/about-hero-4.jpeg', 'page' => 'about', 'title' => 'Our Values', 'description' => 'We never compromise on quality. Every product is carefully selected.', 'order' => 3],
            ['image' => 'about_hero/about-hero.jpg',    'page' => 'about', 'title' => 'Founder Message', 'description' => 'A message from our founder on healthy living and care.', 'order' => 4],

            // Categories
            ['image' => 'carousel/OatsPoster.webp', 'page' => 'oats',     'title' => 'Oats Collection', 'description' => 'Hearty oats & nourishing grain essentials.', 'order' => 1],
            ['image' => 'carousel/nanacare.jpeg',  'page' => 'nanacare', 'title' => 'Nanacare Products', 'description' => 'Comfort-first baby care for daily ease.', 'order' => 1],
            ['image' => 'carousel/nutmill.jpeg',   'page' => 'nutmill',  'title' => 'Nutmill Snacks', 'description' => 'Bold nuts, seeds & roasted picks.', 'order' => 1],
        ];

        foreach ($banners as $data) {
            \App\Models\HomepageBanner::create($data);
        }
    }
}
