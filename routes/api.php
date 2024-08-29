<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\LogisticsController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cart', [CartController::class, 'add']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::get('/user', function (Request $request) {return $request->user();});
});
// test routes
Route::get('/test/books', [TestController::class, 'books']);
Route::get('/sms', [MessageController::class, 'sendSMS']);
//product related routes
Route::get('/home', [ProductsController::class, 'index']);
Route::get('/listing', [ProductsController::class, 'listing']);
Route::get('/product', [ProductsController::class, 'product']);
Route::get('/related', [ProductsController::class, 'related']);
Route::get('/details', [ProductsController::class, 'details']);
Route::get('/reviews', [ProductsController::class, 'reviews']);
//misc....
Route::post('/contact', [MessageController::class, 'contact']);
Route::post('/ask', [MessageController::class, 'ask']);
Route::get('/faqs', [ProductsController::class, 'faqs']);
// admin related routes
Route::get('/logistics', [LogisticsController::class, 'index']);
