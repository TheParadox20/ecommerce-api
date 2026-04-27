<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use App\Models\Brand;
use App\Models\Category;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$brands = Brand::withCount('products')->get();
$categories = Category::all();

echo "BRANDS:\n";
foreach ($brands as $brand) {
    echo "ID: {$brand->id} | Name: {$brand->name} | Products Count: {$brand->products_count}\n";
    $brandCategories = $categories->where('brand_id', $brand->id);
    echo "Categories: " . $brandCategories->pluck('name')->implode(', ') . "\n";
    echo "--------------------------------------------------\n";
}

echo "\nALL CATEGORIES:\n";
foreach ($categories as $cat) {
    echo "ID: {$cat->id} | Name: {$cat->name} | Brand ID: {$cat->brand_id}\n";
}
