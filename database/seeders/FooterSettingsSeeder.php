<?php

namespace Database\Seeders;

use App\Models\WebsiteSetting;
use Illuminate\Database\Seeder;

class FooterSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Contact Info
            [
                'key' => 'footer_phone',
                'value' => '+254 718 156 421',
                'group' => 'footer',
                'type' => 'text'
            ],
            [
                'key' => 'footer_email',
                'value' => 'info@ngwindsongk.com',
                'group' => 'footer',
                'type' => 'text'
            ],
            [
                'key' => 'footer_address',
                'value' => 'Nairobi, Kenya',
                'group' => 'footer',
                'type' => 'text'
            ],
            [
                'key' => 'footer_whatsapp',
                'value' => '254718156421',
                'group' => 'footer',
                'type' => 'text'
            ],

            // Menus (JSON encoded)
            [
                'key' => 'footer_menu_about',
                'value' => json_encode([
                    ['label' => 'Our Story', 'link' => '/about'],
                    ['label' => 'Contact', 'link' => '/contact'],
                    ['label' => 'Recipes', 'link' => '/recipes'],
                    ['label' => 'Blog', 'link' => '/blog'],
                    ['label' => 'Privacy Policy', 'link' => '/policy'],
                    ['label' => 'Terms & Conditions', 'link' => '/terms'],
                ]),
                'group' => 'footer',
                'type' => 'text'
            ],
            [
                'key' => 'footer_menu_shop',
                'value' => json_encode([
                    ['label' => 'All Products', 'link' => '/products'],
                    ['label' => 'Grainmill', 'link' => '/products/oats'],
                    ['label' => 'Nutmill', 'link' => '/products/nutmill'],
                    ['label' => 'Nanacare', 'link' => '/products/nanacare'],
                    ['label' => 'Featured', 'link' => '/recipes'],
                ]),
                'group' => 'footer',
                'type' => 'text'
            ],

            // Socials
            [
                'key' => 'social_facebook',
                'value' => 'https://facebook.com/ngwindsongk',
                'group' => 'footer',
                'type' => 'text'
            ],
            [
                'key' => 'social_instagram',
                'value' => 'https://instagram.com/ng_windsong_kenya',
                'group' => 'footer',
                'type' => 'text'
            ],
        ];

        foreach ($settings as $setting) {
            WebsiteSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
