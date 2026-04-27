<?php

use App\Models\User;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Brand;
use App\Models\Order;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// 1. Create/Get Distributor User
$user = User::firstOrCreate(
    ['email' => 'distributor_test@example.com'],
    [
        'name' => 'Test Distributor',
        'phone' => '0712345678',
        'password' => bcrypt('password'),
        'status' => 'active'
    ]
);
if (!$user->hasRole('distributor')) {
    $user->assignRole('distributor');
}
echo "User: {$user->name} (ID: {$user->id}) is a Distributor.\n";

// 2. Setup Test Data
$brand = Brand::where('name', 'Nanacare')->first();
$originalMaxAmount = $brand->max_order_amount;
$brand->update(['max_order_amount' => 5000]); // Set a low limit for testing

$variation = ProductVariation::where('id', 52)->first(); // Breastmilk Storage Bags 25pcs
$originalMOQ = $variation->min_order_quantity;
$originalIsBulk = $variation->is_bulk;
$variation->update(['min_order_quantity' => 10, 'is_bulk' => true]); // Set MOQ for testing

echo "Brand 'Nanacare' max limit set to 5000.\n";
echo "Variation ID 52 MOQ set to 10 (Bulk).\n";

$controller = new OrderController();

// 3. Test Failure: MOQ Violation
echo "\n--- Testing MOQ Violation ---\n";
$requestMOQ = new Request([
    'user_id' => $user->id,
    'total' => 620 * 5,
    'payment_method' => 'mpesa',
    'sales' => [
        [
            'id' => $variation->product_id,
            'variation' => $variation->id,
            'quantity' => 5, // Below MOQ of 10
            'price' => 620
        ]
    ],
    'order_details' => [
        'full_name' => 'Test Order',
        'phone' => '0712345678',
        'address' => 'Test Address',
        'notes' => 'MOQ Test'
    ],
    'delivery_method' => 'pickup'
]);

$responseMOQ = $controller->store($requestMOQ);
echo "Response: " . $responseMOQ->getContent() . "\n";

// 4. Test Failure: Brand Max Limit Violation
echo "\n--- Testing Brand Max Limit Violation ---\n";
$requestBrand = new Request([
    'user_id' => $user->id,
    'total' => 620 * 15,
    'payment_method' => 'mpesa',
    'sales' => [
        [
            'id' => $variation->product_id,
            'variation' => $variation->id,
            'quantity' => 15, // Total = 9300 > 5000 limit
            'price' => 620
        ]
    ],
    'order_details' => [
        'full_name' => 'Test Order',
        'phone' => '0712345678',
        'address' => 'Test Address',
        'notes' => 'Brand Limit Test'
    ],
    'delivery_method' => 'pickup'
]);

$responseBrand = $controller->store($requestBrand);
echo "Response: " . $responseBrand->getContent() . "\n";

// 5. Create Successful Sale
echo "\n--- Creating Successful Sale ---\n";
$brand->update(['max_order_amount' => 20000]); // Increase limit
$requestSuccess = new Request([
    'user_id' => $user->id,
    'total' => 620 * 12, // Above MOQ (10) and below Brand Limit (20000)
    'payment_method' => 'mpesa',
    'sales' => [
        [
            'id' => $variation->product_id,
            'variation' => $variation->id,
            'quantity' => 12,
            'price' => 620
        ]
    ],
    'order_details' => [
        'full_name' => 'Test Success Order',
        'phone' => '0712345678',
        'address' => 'Test Address',
        'notes' => 'Success Test'
    ],
    'delivery_method' => 'pickup'
]);

$responseSuccess = $controller->store($requestSuccess);
echo "Response: " . $responseSuccess->getContent() . "\n";

// 6. Cleanup
$brand->update(['max_order_amount' => $originalMaxAmount]);
$variation->update(['min_order_quantity' => $originalMOQ, 'is_bulk' => $originalIsBulk]);
echo "\nCleanup completed. Reset Brand limits and MOQ.\n";
