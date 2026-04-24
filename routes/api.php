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
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminManagementController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\RoleManagementController;
use App\Http\Controllers\PartnerManagementController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BlogCommentController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\NavMenuController;
use App\Http\Controllers\DistributorInviteController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\CommissionController;
use App\Http\Controllers\DistributorController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\InfluencerController;

Route::get('/nav-menus', [NavMenuController::class, 'index']);

Route::post('/signup', [AuthController::class, 'register']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'user' => array_merge($user->toArray(), [
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ])
        ]);
    });
});

// Onboarding: "Show Interest" Registration (Public)
Route::post('/register/distributor', [RegistrationController::class, 'distributorSignup']);
Route::post('/register/influencer', [RegistrationController::class, 'influencerSignup']);

// Voucher validation (public)
Route::post('/vouchers/validate', [VoucherController::class, 'validateCode']);
Route::get('/menu', function () {
    return response()->json([
        'success' => true,
        'menu' => []
    ]);
});
// test routes
Route::post('/test-idempotency', function (Illuminate\Http\Request $request) {
    return response()->json(['message' => 'Processed', 'timestamp' => now()->timestamp]);
})->middleware('idempotent');
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
Route::post('/categories/{id}/restore', [App\Http\Controllers\CategoryController::class, 'restore']);
Route::apiResource('brands', App\Http\Controllers\BrandController::class);
Route::apiResource('drafts', App\Http\Controllers\DraftController::class);
Route::apiResource('descriptions', App\Http\Controllers\DescriptionController::class);
Route::apiResource('orders', App\Http\Controllers\OrderController::class)->middleware('idempotent');
Route::apiResource('sales', App\Http\Controllers\SalesController::class);
Route::apiResource('shipments', App\Http\Controllers\ShipmentController::class);
Route::apiResource('recipes', RecipeController::class);
Route::apiResource('cart', CartController::class);
Route::post('/cart/merge-guest', [CartController::class, 'mergeGuestCart']);

// Blog Public Routes
Route::get('/blogs', [BlogController::class, 'index']);
Route::get('/blogs/{slug}', [BlogController::class, 'show']);
Route::post('/blogs/{id}/comments', [BlogController::class, 'storeComment']);

// Settings, Testimonials, Banners Public
Route::get('/settings', [SettingController::class, 'index']);
Route::get('/testimonials', [TestimonialController::class, 'index']);
Route::get('/reviews', [ReviewController::class, 'index']);
Route::post('/reviews', [ReviewController::class, 'store']);
Route::get('/banners', [BannerController::class, 'index']);
//misc....
Route::apiResource('messages', MessagesController::class);
Route::post('/ask', [MessageController::class, 'ask']);
Route::get('/faqs', [ProductsController::class, 'faqs']);

Route::middleware(['auth:sanctum', 'role:super_admin|admin'])->group(function () {
    // Super Admin Only: Admin & User management
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/admins', [AdminManagementController::class, 'index']);
        Route::get('/admin/admins', [AdminManagementController::class, 'index']);
        Route::post('/admins', [AdminManagementController::class, 'store']);
        Route::put('/admin/admins/{id}/password', [AdminManagementController::class, 'updatePassword']);
        Route::delete('/admin/admins/{id}', [AdminManagementController::class, 'destroy']);
        
        Route::get('/admin/users', [UserManagementController::class, 'index']);
        Route::post('/admin/users/{id}/deactivate', [UserManagementController::class, 'deactivate']);
        Route::post('/admin/users/{id}/reactivate', [UserManagementController::class, 'reactivate']);
        Route::post('/admin/users/{id}/assign-role', [UserManagementController::class, 'assignRole']);

        // Access Control (Roles & Permissions)
        Route::get('/admin/roles', [RoleManagementController::class, 'index']);
        Route::post('/admin/roles', [RoleManagementController::class, 'store']);
        Route::post('/admin/roles/{id}/sync-permissions', [RoleManagementController::class, 'syncPermissions']);
        Route::delete('/admin/roles/{id}', [RoleManagementController::class, 'destroy']);

        // Partner Management (Hub)
        Route::get('/admin/partners', [PartnerManagementController::class, 'index']);
        Route::get('/admin/partners/{id}/performance', [PartnerManagementController::class, 'performance']);
        Route::put('/admin/partners/{id}/status', [PartnerManagementController::class, 'updateStatus']);

        // Application Management (Onboarding)
        Route::get('/admin/applications', [ApplicationController::class, 'index']);
        Route::get('/admin/applications/{id}', [ApplicationController::class, 'show']);
        Route::post('/admin/applications/{id}/approve', [ApplicationController::class, 'approve']);
        Route::post('/admin/applications/{id}/reject', [ApplicationController::class, 'reject']);

        // Commission & Payouts (Admin Only)
        Route::get('/admin/commissions', [CommissionController::class, 'index']);
        Route::get('/admin/commissions/summary', [CommissionController::class, 'summary']);
        Route::post('/admin/commissions/payout', [CommissionController::class, 'processPayout']);

        // Voucher Management
        Route::apiResource('/admin/vouchers', VoucherController::class);
    });

    // General Admin Routes (Content Management)
    // Admin Recipe Routes
    Route::get('/admin/recipes', [RecipeController::class, 'index']);
    Route::post('/admin/recipes', [RecipeController::class, 'store']);
    Route::put('/admin/recipes/{id}', [RecipeController::class, 'update']);
    Route::delete('/admin/recipes/{id}', [RecipeController::class, 'destroy']);
    Route::get('/admin/recipes/{id}', [RecipeController::class, 'show']);

    // Admin Blog Routes
    Route::get('/admin/blogs', [BlogController::class, 'adminIndex']);
    Route::apiResource('/admin/blogs', BlogController::class)->except(['index']);

    // Admin Testimonial Routes
    Route::get('/admin/testimonials', [TestimonialController::class, 'adminIndex']);
    Route::apiResource('/admin/testimonials', TestimonialController::class)->except(['index']);

    // Admin Review Routes
    Route::get('/admin/reviews', [ReviewController::class, 'adminIndex']);
    Route::put('/admin/reviews/{id}/approve', [ReviewController::class, 'approve']);
    Route::delete('/admin/reviews/{id}', [ReviewController::class, 'destroy']);
    Route::patch('/admin/reviews/{id}', [ReviewController::class, 'update']);

    // Admin Setting Routes
    Route::apiResource('/admin/settings', SettingController::class)->only(['update']);

    // Admin Comment Moderation
    Route::get('/admin/comments', [BlogCommentController::class, 'index']);
    Route::put('/admin/comments/{id}/approve', [BlogCommentController::class, 'approve']);
    Route::delete('/admin/comments/{id}', [BlogCommentController::class, 'destroy']);

    // Admin Settings, Testimonials, Banners
    Route::post('/admin/settings', [SettingController::class, 'store']);
    Route::post('/admin/testimonials', [TestimonialController::class, 'store']);
    Route::put('/admin/testimonials/{id}', [TestimonialController::class, 'update']);
    Route::delete('/admin/testimonials/{id}', [TestimonialController::class, 'destroy']);
    
    Route::get('/admin/banners', [BannerController::class, 'adminIndex']);
    Route::post('/admin/banners', [BannerController::class, 'store']);
    Route::put('/admin/banners/{id}', [BannerController::class, 'update']);
    Route::delete('/admin/banners/{id}', [BannerController::class, 'destroy']);
    
    // Admin NavMenu Routes
    Route::get('/admin/nav-menus', [NavMenuController::class, 'adminIndex']);
    Route::post('/admin/nav-menus', [NavMenuController::class, 'store']);
    Route::put('/admin/nav-menus/{id}', [NavMenuController::class, 'update']);
    Route::delete('/admin/nav-menus/{id}', [NavMenuController::class, 'destroy']);
    Route::post('/admin/nav-menus/reorder', [NavMenuController::class, 'reorder']);

    // Global Media Hub
    Route::post('/admin/media/upload', [App\Http\Controllers\MediaController::class, 'upload']);

    // Reporting & PDF Routes
    Route::get('/export/sales', [App\Http\Controllers\ExportController::class, 'sales']);
    Route::get('/export/deliveries', [App\Http\Controllers\ExportController::class, 'deliveries']);
    Route::get('/orders/{slug}/invoice', [App\Http\Controllers\PDFController::class, 'downloadInvoice']);
});

// Distributor Specific Routes
Route::middleware(['auth:sanctum', 'role:distributor'])->group(function () {
    Route::get('/distributor/stock', [DistributorController::class, 'stockOverview']);
    Route::put('/distributor/stock/{id}', [DistributorController::class, 'updateStock']);
    Route::get('/distributor/orders', [DistributorController::class, 'orders']);
});

// Influencer Specific Routes
Route::middleware(['auth:sanctum', 'role:influencer'])->group(function () {
    Route::get('/influencer/stats', [InfluencerController::class, 'stats']);
    Route::get('/influencer/conversions', [InfluencerController::class, 'conversions']);
});

Route::get('/logistics', [LogisticsController::class, 'index']);
Route::get('/admin/listing', [ProductsController::class, 'adminListing']);
//payment related routes
Route::post('/delivery-fee', [App\Http\Controllers\DeliveryFeeController::class, 'calculate']);
Route::post('/pay/mpesa', [PaymentController::class, 'mpesaSTK'])->middleware('idempotent');
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