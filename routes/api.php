<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;

Route::middleware('auth:sanctum')->group(function () {});
Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});
// test routes
Route::get('/test/books', [TestController::class, 'books']);
