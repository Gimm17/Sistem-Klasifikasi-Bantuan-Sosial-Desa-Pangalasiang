<?php

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
        // Sanctum SPA (cookie-based): pastikan request dari domain first-party
        // (SANCTUM_STATEFUL_DOMAINS) memperoleh stack session/CSRF. Tanpa ini,
        // Auth::guard('web')->attempt() & session()->regenerate() gagal pada API route.
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Alias middleware 'role' untuk pembatasan akses per peran.
        // Pemakaian di route: ->middleware(['auth:sanctum', 'role:admin,superadmin'])
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);

        // SPA: guest redirect ke /login (Vue route), BUKAN route('login').
        // Tanpa ini, middleware Authenticate memanggil route('login') yang tidak ada
        // dan melempar RouteNotFoundException → 500 bukan 401 pada API request.
        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
