<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') || !$request->header('Idempotency-Key')) {
            return $next($request);
        }

        $key = 'idempotency:' . $request->header('Idempotency-Key');

        if (Cache::has($key)) {
            $cachedResponse = Cache::get($key);
            return response($cachedResponse['content'], $cachedResponse['status'], $cachedResponse['headers']);
        }

        $response = $next($request);

        if ($response->isSuccessful() || $response->isRedirection()) {
            Cache::put($key, [
                'content' => $response->getContent(),
                'status' => $response->getStatusCode(),
                'headers' => $response->headers->all(),
            ], now()->addHours(24));
        }

        return $response;
    }
}
