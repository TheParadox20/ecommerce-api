<?php

use App\Models\WebsiteSetting;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$key = 'consultant_image';
$value = 'settings/maternal_consultant.jpg';
$group = 'footer';

WebsiteSetting::updateOrCreate(
    ['key' => $key, 'group' => $group],
    ['value' => $value]
);

echo "Successfully updated consultant_image to $value\n";
