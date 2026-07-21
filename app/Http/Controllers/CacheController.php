<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CacheController extends Controller
{
    /**
     * Clear the Laravel application cache and trigger Next.js page revalidation.
     *
     * Two-step process:
     *   1. Flush the Laravel database/file cache.
     *   2. POST to the Next.js shop's /api/revalidate endpoint (server-to-server).
     *      This works in production because FRONTEND_URL is a real public HTTPS URL.
     */
    public function clearCache(Request $request)
    {
        $laravelCacheCleared = false;
        $frontendRevalidated = false;
        $frontendError       = null;

        // ── Step 1: Clear Laravel cache ──────────────────────────────────────
        try {
            Cache::flush();
            $laravelCacheCleared = true;
            Log::info('Laravel cache flushed by: ' . ($request->user()?->email ?? 'unknown'));
        } catch (\Throwable $e) {
            Log::error('Cache flush failed: ' . $e->getMessage());
        }

        // ── Step 2: Ping Next.js revalidation endpoint ───────────────────────
        $frontendUrl = rtrim(env('FRONTEND_URL', 'https://www.ngwindsongk.com'), '/');
        $secret      = env('REVALIDATE_SECRET', 'super_secure_revalidation_secret_token_2026');

        try {
            $response = Http::timeout(15)
                ->withHeaders(['Accept' => 'application/json'])
                ->post("{$frontendUrl}/api/revalidate?secret={$secret}");

            if ($response->successful()) {
                $frontendRevalidated = true;
            } else {
                $frontendError = "Next.js returned HTTP {$response->status()}: " . $response->body();
                Log::warning("Next.js revalidation failed: {$frontendError}");
            }
        } catch (\Throwable $e) {
            $frontendError = "Could not reach {$frontendUrl}: " . $e->getMessage();
            Log::error("Next.js revalidation error: {$frontendError}");
        }

        $allOk = $laravelCacheCleared && $frontendRevalidated;

        return response()->json([
            'success' => $allOk,
            'message' => $allOk
                ? 'All caches cleared and store revalidated successfully.'
                : 'Partial success — see details below.',
            'details' => [
                'laravel_cache_cleared' => $laravelCacheCleared,
                'frontend_revalidated'  => $frontendRevalidated,
                'frontend_error'        => $frontendError,
            ],
        ]);
    }
}

