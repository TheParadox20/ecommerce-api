<?php

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Sale;
use Illuminate\Support\Str;

// Fetch Jumbo Oats
$product = Product::where('name', 'Jumbo oats')->first();
$variation500g = ProductVariation::where('product_id', $product->id)->where('attribute_value', '500g')->first();
$variation1kg = ProductVariation::where('product_id', $product->id)->where('attribute_value', '1KG')->first();

// Create the order
$order = Order::create([
    'slug' => 'OATS-' . strtoupper(Str::random(6)),
    'total' => 704, // 255 + 449
    'shipping' => 350,
    'delivery_method' => 'delivery',
    'delivery_zone' => 'Nairobi Central',
    'status' => 'pending',
    'payment_status' => 'completed',
    'order_type' => 'b2c',
]);

// Add order details
OrderDetail::create([
    'order_id' => $order->id,
    'full_name' => 'Oats Tester',
    'phone' => '0788123456',
    'email' => 'oatstest@example.com',
    'address' => 'Upper Hill, Nairobi',
]);

// Add sales for 500g
Sale::create([
    'order_id' => $order->id,
    'product_id' => $product->id,
    'product_variation_id' => $variation500g->id,
    'quantity' => 1,
    'price' => 255.00,
    'total' => 255.00,
]);

// Add sales for 1KG
Sale::create([
    'order_id' => $order->id,
    'product_id' => $product->id,
    'product_variation_id' => $variation1kg->id,
    'quantity' => 1,
    'price' => 449.00,
    'total' => 449.00,
]);

echo "Test order for oats created: " . $order->slug . "\n";
