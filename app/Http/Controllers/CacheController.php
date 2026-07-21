<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheController extends Controller
{
    /**
     * Clear the Laravel application cache.
     *
     * Note: Next.js front-end revalidation is handled directly by the admin
     * browser client to avoid server-to-server connectivity issues.
     */
    public function clearCache(Request $request)
    {
        $laravelCacheCleared = false;

        try {
            Cache::flush();
            $laravelCacheCleared = true;
            Log::info('Laravel application cache flushed by admin: ' . $request->user()?->email);
        } catch (\Throwable $e) {
            Log::error('Failed to flush Laravel cache: ' . $e->getMessage());
        }

        return response()->json([
            'success' => $laravelCacheCleared,
            'message' => $laravelCacheCleared
                ? 'Laravel cache cleared successfully.'
                : 'Failed to clear Laravel cache.',
        ]);
    }
}

