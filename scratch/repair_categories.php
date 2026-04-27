<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use App\Models\Category;
use App\Models\Product;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Repairing Category-Brand relationships...\n";

$categories = Category::whereNull('brand_id')->get();

foreach ($categories as $category) {
    // Find the most common brand associated with products in this category
    $brandId = Product::where('category_id', $category->id)
        ->whereNotNull('brand_id')
        ->groupBy('brand_id')
        ->select('brand_id', \DB::raw('count(*) as total'))
        ->orderBy('total', 'desc')
        ->first()
        ?->brand_id;

    if ($brandId) {
        $category->update(['brand_id' => $brandId]);
        echo "Linked Category '{$category->name}' to Brand ID {$brandId}\n";
    } else {
        echo "Could not find a brand for Category '{$category->name}' (no products found)\n";
    }
}

echo "Done.\n";
