<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\LogisticsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;

Route::post('/signup', [AuthController::class, 'register']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/user/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {return response()->json(['user' => $request->user()]);});
});
// test routes
Route::get('/test/books', [TestController::class, 'books']);
Route::get('/test/session', [TestController::class, 'testSession']);
Route::get('/sms', [MessageController::class, 'sendSMS']);
//product related routes
// API Resource routes for ecommerce models
Route::apiResource('products', ProductController::class);
Route::apiResource('product-variations', App\Http\Controllers\ProductVariationController::class);
Route::apiResource('attributes', App\Http\Controllers\AttributeController::class);
Route::apiResource('attribute-values', App\Http\Controllers\AttributeValueController::class);
Route::apiResource('product-images', App\Http\Controllers\ProductImageController::class);
Route::apiResource('product-faqs', App\Http\Controllers\ProductFAQController::class);
Route::apiResource('categories', App\Http\Controllers\CategoryController::class);
Route::apiResource('brands', App\Http\Controllers\BrandController::class);
Route::apiResource('drafts', App\Http\Controllers\DraftController::class);
Route::apiResource('descriptions', App\Http\Controllers\DescriptionController::class);
Route::apiResource('orders', App\Http\Controllers\OrderController::class);
Route::apiResource('sales', App\Http\Controllers\SalesController::class);
Route::apiResource('shipments', App\Http\Controllers\ShipmentController::class);
Route::apiResource('drafts', App\Http\Controllers\DraftController::class);
Route::apiResource('cart', CartController::class);
Route::post('/cart/merge-guest', [CartController::class, 'mergeGuestCart']);
Route::get('/related/{product_name}', [ProductController::class, 'related']);

// Optionally, comment out or remove old product-related routes for clarity
// Route::get('/home', [ProductsController::class, 'index']);
// Route::get('/listing', [ProductsController::class, 'listing']);
// Route::get('/product', [ProductsController::class, 'product']);
// Route::get('/related', [ProductsController::class, 'related']);
// Route::get('/details', [ProductsController::class, 'details']);
// Route::get('/reviews', [ProductsController::class, 'reviews']);
// Route::post('/product/create', [ProductsController::class, 'create']);
// Route::post('/product/update', [ProductsController::class, 'update']);
// Route::post('/product/update/description', [ProductsController::class, 'updateDescription']);
// Route::post('/product/update/media', [ProductsController::class, 'updateMedia']);
//recipe related routes
Route::get('/recipes', [RecipeController::class, 'index']);
Route::get('/recipe', [RecipeController::class, 'show']);
Route::get('/recipes/popular', [RecipeController::class, 'popular']);
Route::get('/recipes/featured', [RecipeController::class, 'featured']);
Route::get('/recipes/categories', [RecipeController::class, 'categories']);
Route::post('/recipes', [RecipeController::class, 'store']);
Route::put('/recipes/{id}', [RecipeController::class, 'update']);
Route::delete('/recipes/{id}', [RecipeController::class, 'destroy']);
//misc....
Route::apiResource('messages', MessagesController::class);
Route::post('/ask', [MessageController::class, 'ask']);
Route::get('/faqs', [ProductsController::class, 'faqs']);
// admin related routes
Route::get('/logistics', [LogisticsController::class, 'index']);
Route::get('/admin/listing', [ProductsController::class, 'adminListing']);
//payment related routes
Route::post('/delivery-fee', [App\Http\Controllers\DeliveryFeeController::class, 'calculate']);
Route::post('/pay/mpesa', [PaymentController::class, 'mpesaSTK']);
Route::post('/mpesa/mpesaCallback', [PaymentController::class, 'mpesaCallback']);
//system maintenance routes
Route::get('/run-migrations', function () {
    Artisan::call('migrate', ['--force' => true]);
    return Artisan::output();
});

Route::get('/run-seeder', function (Request $request) {
    $class = $request->query('class', 'DatabaseSeeder');
    Artisan::call('db:seed', ['--class' => $class, '--force' => true]);
    return Artisan::output();
});