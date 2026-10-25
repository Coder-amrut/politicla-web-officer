<?php

use App\Http\Middleware\EnsureAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Return null instead of redirecting to non-existent route('login')
        $middleware->redirectGuestsTo(fn () => null);

        // Register the EnsureAdmin middleware alias so it can be referenced
        // by class name directly in routes (Laravel 13 style).
        $middleware->alias([
            'admin' => EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => true,
        );
    })->create();
