<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\LogisticsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RecipeController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {return $request->user();});
});
// test routes
Route::get('/test/books', [TestController::class, 'books']);
Route::get('/test/session', [TestController::class, 'testSession']);
Route::get('/sms', [MessageController::class, 'sendSMS']);
//product related routes
// API Resource routes for ecommerce models
Route::apiResource('products', App\Http\Controllers\ProductController::class);
Route::apiResource('product-variations', App\Http\Controllers\ProductVariationController::class);
Route::apiResource('attributes', App\Http\Controllers\AttributeController::class);
Route::apiResource('attribute-values', App\Http\Controllers\AttributeValueController::class);
Route::apiResource('product-images', App\Http\Controllers\ProductImageController::class);
Route::apiResource('categories', App\Http\Controllers\CategoryController::class);
Route::apiResource('brands', App\Http\Controllers\BrandController::class);
Route::apiResource('cart', CartController::class);
Route::post('/cart/merge-guest', [CartController::class, 'mergeGuestCart']);

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
Route::post('/contact', [MessageController::class, 'contact']);
Route::post('/ask', [MessageController::class, 'ask']);
Route::get('/faqs', [ProductsController::class, 'faqs']);
// admin related routes
Route::get('/logistics', [LogisticsController::class, 'index']);
Route::get('/admin/listing', [ProductsController::class, 'adminListing']);
//payment related routes
Route::post('/pay/mpesa', [PaymentController::class, 'mpesaSTK']);