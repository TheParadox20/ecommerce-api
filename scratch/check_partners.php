<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use App\Models\User;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = User::role(['distributor', 'influencer'])->latest()->limit(5)->get();

foreach ($users as $user) {
    echo "ID: {$user->id} | Name: {$user->name} | Role: {$user->role}\n";
    echo "Profile Details: " . json_encode($user->profile_details, JSON_PRETTY_PRINT) . "\n";
    echo "--------------------------------------------------\n";
}
