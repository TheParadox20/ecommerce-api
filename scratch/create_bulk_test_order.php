<?php

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Sale;
use Illuminate\Support\Str;

// Helper to add items
$items = [];
$totalAmount = 0;

function addItem($productName, $attributeValue, $qty, &$items, &$totalAmount) {
    $product = Product::where('name', $productName)->first();
    if (!$product) {
        // Create if missing (for Breastmilk Storage Bags)
        $product = Product::firstOrCreate(['name' => $productName], [
            'slug' => Str::slug($productName),
            'price' => 620,
            'category_id' => 1,
            'brand_id' => 1,
        ]);
    }
    
    $variation = ProductVariation::where('product_id', $product->id)
        ->where('attribute_value', $attributeValue)
        ->first();
        
    if (!$variation) {
        $variation = ProductVariation::create([
            'product_id' => $product->id,
            'attribute_name' => 'Size',
            'attribute_value' => $attributeValue,
            'sku' => strtoupper(Str::slug($productName . '-' . $attributeValue . '-' . rand(100,999))),
            'price' => $product->price ?? 620,
            'stock' => 100,
            'status' => 'active'
        ]);
    }

    $price = $variation->price;
    $itemTotal = $price * $qty;
    
    $items[] = [
        'product_id' => $product->id,
        'variation_id' => $variation->id,
        'quantity' => $qty,
        'price' => $price,
        'total' => $itemTotal
    ];
    
    $totalAmount += $itemTotal;
}

addItem('Jumbo oats', '250g', 2, $items, $totalAmount);
addItem('Jumbo oats', '1KG', 1, $items, $totalAmount);
addItem('Steel Cut Oats', '500g', 3, $items, $totalAmount);
addItem('Quick breakfast oats', '250g', 1, $items, $totalAmount);
addItem('Breastmilk Storage Bags', 'Standard Size', 5, $items, $totalAmount);

// Create the order
$order = Order::create([
    'slug' => 'BULK-' . strtoupper(Str::random(6)),
    'total' => $totalAmount + 350, // Including shipping
    'shipping' => 350,
    'delivery_method' => 'delivery',
    'delivery_zone' => 'Nairobi West',
    'status' => 'pending',
    'payment_status' => 'completed',
    'order_type' => 'b2c',
]);

OrderDetail::create([
    'order_id' => $order->id,
    'full_name' => 'Premium Bulk Tester',
    'phone' => '0799887766',
    'email' => 'bulktest@example.com',
    'address' => 'Langata Road, Nairobi',
]);

foreach ($items as $item) {
    Sale::create([
        'order_id' => $order->id,
        'product_id' => $item['product_id'],
        'product_variation_id' => $item['variation_id'],
        'quantity' => $item['quantity'],
        'price' => $item['price'],
        'total' => $item['total'],
    ]);
}

echo "Premium Bulk Test Order created: " . $order->slug . "\n";
echo "Total Items: " . count($items) . "\n";
echo "Total Order Value: " . ($totalAmount + 350) . " KES\n";
