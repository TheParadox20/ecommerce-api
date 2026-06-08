<?php

use App\Models\WebsiteSetting;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$settings = [
    'consultant_name' => 'Lactation Expert Jane',
    'consultant_profile' => 'A dedicated maternal and childcare consultant...',
    'consultant_phone' => '0700000000',
    'consultant_email' => 'jane@example.com',
    'consultant_whatsapp' => '254700000000'
];

foreach ($settings as $key => $value) {
    WebsiteSetting::updateOrCreate(
        ['key' => $key],
        ['value' => $value, 'group' => 'footer']
    );
}

echo "Successfully updated consultant settings\n";
