<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WebsiteSetting;

class AboutSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'about_story',
                'value' => 'Ng Windsong was founded from a deep understanding of two critical needs: the importance of healthy nutrition and the challenges faced by new parents. As a passionate advocate for healthy living, the founder recognized that many families struggle to maintain nutritious eating habits in today\'s fast-paced world. This led to the creation of a premium product line of oats (<i>Avena Sativa</i>), edible nuts & seeds offering wholesome, convenient nutrition for everyone.',
                'type' => 'richtext',
                'group' => 'about',
            ],
            [
                'key' => 'about_mission',
                'value' => 'To empower healthy living through nutritious food choices and support new mothers with practical, thoughtful products that make their journey easier and more enjoyable.',
                'type' => 'richtext',
                'group' => 'about',
            ],
            [
                'key' => 'about_oats_desc',
                'value' => 'Our premium oats line offers a variety of nutritious options including jumbo oats, steel-cut oats, quick breakfast oats, and oat flour.',
                'type' => 'richtext',
                'group' => 'about',
            ],
            [
                'key' => 'about_nanacare_desc',
                'value' => 'Our Nanacare line was specifically designed to address the real challenges new mothers face, from cooler bags to storage cups.',
                'type' => 'richtext',
                'group' => 'about',
            ],
            [
                'key' => 'about_founder_message',
                'value' => 'When I founded NG windsong Kenya LTD, I wanted to create more than just products – I wanted to create solutions that make a real difference in people\'s lives.',
                'type' => 'richtext',
                'group' => 'about',
            ],
            [
                'key' => 'about_founder_name',
                'value' => 'Jennifer',
                'type' => 'text',
                'group' => 'about',
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
