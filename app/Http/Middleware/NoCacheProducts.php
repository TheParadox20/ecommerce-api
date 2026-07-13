<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevent browsers and proxies from caching product/pricing responses.
 *
 * Applied to public product endpoints so that any price or discount update
 * made in the admin panel is immediately visible to shoppers without a
 * hard refresh or cache-bust.
 */
class NoCacheProducts
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
