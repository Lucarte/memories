<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi(); // Ensures Sanctum works for stateful SPAs

        // Define middleware for the 'api' group
        $middleware->group('api', [
            Illuminate\Routing\Middleware\ThrottleRequests::class, // Handles rate limiting
            Illuminate\Routing\Middleware\SubstituteBindings::class, // Handles route bindings
            App\Http\Middleware\CsrfDebugMiddleware::class, // Custom middleware for CSRF debugging
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
