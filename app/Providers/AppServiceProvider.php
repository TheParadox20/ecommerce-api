<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        Gate::define('viewPulse', function (?User $user) {
            return app()->environment('local') || ($user && $user->hasRole('super_admin'));
        });

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        // Checkout & Payment Rate Limiters
        // 30 requests/min for order placement, keyed by user ID, customer phone, or IP+UserAgent to prevent CGNAT collisions
        RateLimiter::for('checkout-orders', function (Request $request) {
            $identifier = $request->user()?->id
                ?? $request->input('order_details.phone')
                ?? ($request->ip() . '|' . $request->userAgent());

            return Limit::perMinute(30)->by($identifier);
        });

        // 15 requests/min for M-Pesa STK prompts, keyed by recipient phone number
        RateLimiter::for('checkout-mpesa', function (Request $request) {
            $phone = $request->input('phone') ?? $request->ip();
            return Limit::perMinute(15)->by($phone);
        });

        // 60 requests/min for dynamic delivery fee calculations
        RateLimiter::for('delivery-fee', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
