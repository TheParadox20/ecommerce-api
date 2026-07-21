<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CacheController extends Controller
{
    /**
     * Clear application cache and trigger Next.js frontend revalidation.
     */
    public function clearCache(Request $request)
    {
        $laravelCacheCleared = false;
        $frontendRevalidated = false;
        $frontendError = null;

        // 1. Clear Laravel Internal Cache
        try {
            Cache::flush();
            $laravelCacheCleared = true;
        } catch (\Throwable $e) {
            Log::error('Failed to flush Laravel cache: ' . $e->getMessage());
        }

        // 2. Trigger Next.js Frontend Revalidation
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        $secret = env('REVALIDATE_SECRET', 'super_secure_revalidation_secret_token_2026');

        try {
            $response = Http::timeout(10)->post("{$frontendUrl}/api/revalidate?secret={$secret}");

            if ($response->successful()) {
                $frontendRevalidated = true;
            } else {
                $frontendError = "Frontend returned HTTP {$response->status()}: " . $response->body();
                Log::warning("Frontend revalidation failed: {$frontendError}");
            }
        } catch (\Throwable $e) {
            $frontendError = "Could not connect to frontend at {$frontendUrl}: " . $e->getMessage();
            Log::error("Frontend revalidation connection error: {$frontendError}");
        }

        return response()->json([
            'success' => $laravelCacheCleared,
            'message' => 'Cache clearing process completed.',
            'details' => [
                'laravel_cache_cleared' => $laravelCacheCleared,
                'frontend_revalidated'  => $frontendRevalidated,
                'frontend_error'        => $frontendError,
            ]
        ]);
    }
}
