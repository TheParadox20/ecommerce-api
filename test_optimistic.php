<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$product = App\Models\Product::first();
echo "Initial version: " . $product->version . "\n";

// Update it optimistically
$product->updateOptimistically(['name' => $product->name . ' Updated'], $product->version);
echo "New version: " . $product->fresh()->version . "\n";

// Try to update it again with the OLD version
try {
    $product->updateOptimistically(['name' => $product->name . ' Failed'], $product->version - 1);
    echo "FAIL: Expected exception was not thrown.\n";
} catch (\Exception $e) {
    echo "SUCCESS: Exception thrown -> " . $e->getMessage() . "\n";
}
