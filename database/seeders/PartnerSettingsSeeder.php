<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WebsiteSetting;

class PartnerSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'influencer_commission_rate',
                'value' => '5',
                'type' => 'number',
                'group' => 'marketing',
            ],
            [
                'key' => 'admin_notification_emails',
                'value' => 'admin@example.com',
                'type' => 'text',
                'group' => 'notifications',
            ],
            [
                'key' => 'enable_commission_alerts',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notifications',
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
