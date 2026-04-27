<?php

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Sale;
use Illuminate\Support\Str;

// Create a test product
$product = Product::firstOrCreate(['name' => 'Breastmilk Storage Bags'], [
    'slug' => Str::slug('Breastmilk Storage Bags'),
    'price' => 620,
    'category_id' => 1,
    'brand_id' => 1,
]);

// Create a variation
$variation = ProductVariation::updateOrCreate(
    ['product_id' => $product->id, 'attribute_name' => 'Size'],
    [
        'attribute_value' => 'Standard Size',
        'sku' => 'TEST-BAGS-STD',
        'price' => 620,
        'stock' => 100,
        'status' => 'active'
    ]
);

// Create the order
$order = Order::create([
    'slug' => 'TEST-' . strtoupper(Str::random(6)),
    'total' => 7440,
    'shipping' => 0,
    'delivery_method' => 'delivery',
    'status' => 'pending',
    'payment_status' => 'completed',
    'order_type' => 'b2c',
]);

// Add order details
OrderDetail::create([
    'order_id' => $order->id,
    'full_name' => 'Test Customer',
    'phone' => '0712345678',
    'email' => 'test@example.com',
    'address' => 'Nairobi, Kenya',
]);

// Add sales
Sale::create([
    'order_id' => $order->id,
    'product_id' => $product->id,
    'product_variation_id' => $variation->id,
    'quantity' => 12,
    'price' => 620,
    'total' => 7440,
]);

echo "Test order created: " . $order->slug . "\n";
echo "You can now download the invoice for this order.\n";
