<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WebsiteSetting;

class ContactSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'contact_headline',
                'value' => 'Get in touch',
                'type' => 'text',
                'group' => 'contact',
            ],
            [
                'key' => 'contact_description',
                'value' => "Have a question about our products, deliveries, or anything else? We'd love to hear from you. Fill out the form below and our team will get back to you as soon as possible.",
                'type' => 'text',
                'group' => 'contact',
            ],
            [
                'key' => 'contact_phones',
                'value' => "0718156421\n0795666840\n0113748906",
                'type' => 'text',
                'group' => 'contact',
            ],
            [
                'key' => 'contact_emails',
                'value' => "sales@ngwindsongk.com\ninfo@ngwindsongk.com",
                'type' => 'text',
                'group' => 'contact',
            ],
            [
                'key' => 'contact_address',
                'value' => "Rashali GoDown, No. 2 <br /> Maasai Road, off Mombasa road",
                'type' => 'richtext',
                'group' => 'contact',
            ],
            [
                'key' => 'contact_maps_url',
                'value' => "https://maps.app.goo.gl/yGuJeUQddbhW9wcF7",
                'type' => 'text',
                'group' => 'contact',
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
