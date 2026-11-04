
<?php

use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SystemAvailabilityMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // Sanctum API Authentication
        $middleware->statefulApi();

        // System Availability
        $middleware->append(
            SystemAvailabilityMiddleware::class
        );

        // Middleware Aliases
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        // API Authentication Redirect
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*')) {
                return null;
            }

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Render API Exceptions as JSON
        $exceptions->shouldRenderJsonWhen(function (
            Request $request,
            Throwable $exception
        ): bool {
            return $request->is('api/*')
                || $request->expectsJson();
        });

    })
    ->create();
