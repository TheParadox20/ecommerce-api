<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Blog;
use App\Models\Testimonial;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Support\Str;

class SampleContentSeeder extends Seeder
{
    public function run()
    {
        // 1. Sample Testimonials (Reviews)
        $reviews = [
            [
                'name' => 'Jennifer A.',
                'role' => 'Mother of Two',
                'comment' => 'The Nanacare storage bags are absolute lifesavers. They never leak and the space-saving design is perfect for my small freezer.',
                'rating' => 5
            ],
            [
                'name' => 'Mark O.',
                'role' => 'Fitness Coach',
                'comment' => "I recommend Grainmill's steel-cut oats to all my clients. The nutritional profile is excellent and they keep you full for hours.",
                'rating' => 5
            ],
            [
                'name' => 'Alice W.',
                'role' => 'Working Mom',
                'comment' => 'The multipurpose cooler bag is so stylish! I use it for work and for baby milk. Nobody even knows it is a cooler bag.',
                'rating' => 5
            ],
            [
                'name' => 'Kevin T.',
                'role' => 'Breakfast Enthusiast',
                'comment' => "Quick breakfast oats that actually taste like real food. I'm impressed by the quality of Grainmill products.",
                'rating' => 4
            ]
        ];

        foreach ($reviews as $review) {
            Testimonial::create($review);
        }

        // 2. Sample Blog Articles
        $blogs = [
            [
                'title' => '5 Nutritious Ways to Use Oat Flour in Baking',
                'slug' => '5-nutritious-ways-to-use-oat-flour',
                'content' => '<p>Oat flour is more than just a gluten-free alternative; it\'s a nutritional powerhouse. Here are five ways to incorporate it into your daily baking routine...</p><h3>1. Perfect Pancakes</h3><p>Swap half your regular flour for Grainmill oat flour for fluffier, fiber-rich pancakes.</p><h3>2. Thickening Sauces</h3><p>Oat flour acts as a great thickener for soups and stews without the heavy taste of wheat flour.</p>',
                'featured_image' => 'https://images.unsplash.com/photo-1590113331908-f1f33f6a6ca7?q=80&w=1000',
                'excerpt' => 'Discover the versatility of oat flour and how it can transform your healthy baking journey.',
                'brands' => [1], // Grainmill
                'products' => [1] // Oat flour
            ],
            [
                'title' => 'The Ultimate Guide to Storing Breastmilk Safely',
                'slug' => 'ultimate-guide-breastmilk-storage',
                'content' => '<p>For breastfeeding mothers returning to work, storage is the biggest concern. Following CDC guidelines, we\'ve compiled this essential guide...</p><h3>Temperature Matters</h3><p>Always store milk in the back of the freezer where the temperature is most stable.</p>',
                'featured_image' => 'https://images.unsplash.com/photo-1622323869826-384485ae90e5?q=80&w=1000',
                'excerpt' => 'Everything you need to know about keeping your liquid gold safe and nutritious for your baby.',
                'brands' => [2], // Babycare
                'products' => [4, 5] // Storage Bags, Cups
            ],
            [
                'title' => 'Why Jumbo Oats are the King of Breakfast',
                'slug' => 'jumbo-oats-king-of-breakfast',
                'content' => '<p>When it comes to texture and satiety, nothing beats jumbo oats. These large, rolled oats maintain their shape and provide a steady release of energy...</p>',
                'featured_image' => 'https://images.unsplash.com/photo-1517673132405-a56a62b18acc?q=80&w=1000',
                'excerpt' => 'Learn why jumbo oats should be your go-to choice for a productive and energetic morning.',
                'brands' => [1, 3], // Grainmill, Nutmill
                'products' => [2] // Jumbo oats
            ]
        ];

        foreach ($blogs as $blogData) {
            $brands = $blogData['brands'];
            $products = $blogData['products'];
            unset($blogData['brands'], $blogData['products']);

            $blogData['status'] = 'published'; // Ensure sample blogs are visible immediately

            $blog = Blog::create($blogData);
            $blog->brands()->sync($brands);
            $blog->products()->sync($products);
        }
    }
}
