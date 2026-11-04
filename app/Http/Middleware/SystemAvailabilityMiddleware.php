<?php

namespace App\Http\Middleware;

use App\Services\SystemAvailabilityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SystemAvailabilityMiddleware
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        // Allow only the availability-check endpoint.
        if ($request->is('api/system-availability')) {
            return $next($request);
        }

        if (app(SystemAvailabilityService::class)->isAvailable()) {
            return $next($request);
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Service temporarily unavailable.',
            ], 503);
        }

        return response()->view(
            'errors.system-unavailable',
            [],
            503
        );
    }
}
