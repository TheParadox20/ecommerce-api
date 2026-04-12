<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ProductImage;
use App\Models\Description;
use App\Models\ProductFAQ;
use Illuminate\Support\Str;

class CoolerBagSeeder extends Seeder
{
    public function run()
    {
        // 1. Create or Update the Product
        $product = Product::updateOrCreate(
            ['name' => 'Multipurpose cooler bags'],
            [
                'slug' => 'multipurpose-cooler-bags',
                'category_id' => 3, // Nanacare
                'brand_id' => 2,    // Babycare
                'about' => "Your milk, your schedule, your freedom. The Nanacare Insulated Cooler Bag is a sleek, multi-compartment bag designed to keep your expressed breastmilk cold, fresh, and safe — whether you're at the office, on the road, or anywhere life takes you. This isn't just a cooler bag — it's a complete breastfeeding companion built around the real needs of working and on-the-go mums.",
                'price' => 1500,
                'discount' => 0,
                'stock' => 72,
            ]
        );

        // 2. Add/Update Description (Rich Text)
        $descriptionContent = "
            <p>Your milk, your schedule, your freedom. The Nanacare Insulated Cooler Bag is a sleek, multi-compartment bag designed to keep your expressed breastmilk cold, fresh, and safe — whether you're at the office, on the road, or anywhere life takes you.</p>
            <p>This isn't just a cooler bag — it's a complete breastfeeding companion built around the real needs of working and on-the-go mums. Available in six stunning colours — Black, Red, Blue, Green, Orange, and Pink — there's a shade for every mum, every outfit, and every occasion.</p>
            <h3>Features & Perks</h3>
            <ul>
                <li><strong>Superior insulation</strong> — maintains the required cold temperatures to keep breastmilk fresh and safe during transport</li>
                <li><strong>Top compartment</strong> — perfectly sized to carry your breast pump accessories, keeping everything organised and within easy reach</li>
                <li><strong>Bottom compartment</strong> — generously designed to hold up to 15 Nanacare Breastmilk Storage Bags and 2 ice packs</li>
                <li><strong>Portable fridge</strong> — used with ice packs, the bag acts as a mobile cold storage unit for your expressed milk</li>
                <li><strong>Dual carry options</strong> — carry it by hand or sling it over your shoulder, whatever suits your day</li>
                <li><strong>Multi-purpose</strong> — beyond breastmilk, use it to carry your baby's food, drinks, and snacks on the go</li>
            </ul>
        ";

        Description::updateOrCreate(
            ['product_id' => $product->id],
            ['description' => $descriptionContent]
        );

        // 3. Add Variations
        $variationData = [
            'Black' => null,
            'Red' => null,
            'Green' => null,
            'Pink' => 'https://api.ngwindsongk.com/storage/products/Multipurpose_cooler_bags/1773647151_nanacare_026.webp',
            'Orange' => null,
            'Blue' => 'https://api.ngwindsongk.com/storage/products/Multipurpose_cooler_bags/1773647151_nanacare_022.webp',
        ];

        foreach ($variationData as $color => $imageUrl) {
            ProductVariation::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'sku' => strtoupper(Str::slug($product->name . '-' . $color))
                ],
                [
                    'attribute_name' => 'Colour',
                    'attribute_value' => $color,
                    'price' => 1500,
                    'stock' => 12,
                    'status' => 'active',
                    'image' => $imageUrl
                ]
            );
        }

        // 4. Add Images
        $images = [
            [
                'url' => 'https://api.ngwindsongk.com/storage/products/Multipurpose_cooler_bags/1773647151_cooler_bags.jpeg',
                'is_primary' => true
            ],
            [
                'url' => 'https://api.ngwindsongk.com/storage/products/Multipurpose_cooler_bags/1773647151_nanacare_022.webp',
                'is_primary' => false
            ],
            [
                'url' => 'https://api.ngwindsongk.com/storage/products/Multipurpose_cooler_bags/1773647151_nanacare_026.webp',
                'is_primary' => false
            ]
        ];

        // Clear existing images to avoid duplicates if re-running
        $product->productImages()->delete();
        foreach ($images as $img) {
            ProductImage::create([
                'product_id' => $product->id,
                'url' => $img['url'],
                'is_primary' => $img['is_primary']
            ]);
        }

        // 5. Add Perks (if appropriate table existed, but we added to description above)
        
        $this->command->info('Cooler Bag product and variations seeded successfully!');
    }
}
