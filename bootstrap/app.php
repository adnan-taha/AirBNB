<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // Middleware aliases
        $middleware->alias([
            'approved' => \App\Http\Middleware\ApprovedMiddleware::class,
            'role'     => \App\Http\Middleware\RoleMiddleware::class,
            'admin'    => \App\Http\Middleware\AdminApprovalMiddleware::class,
            'trust'    => \App\Http\Middleware\TrustProxies::class,
            'auth'     => \App\Http\Middleware\Authenticate::class,
        ]);

        // Global middleware (order matters)
        $middleware->use([
            \App\Http\Middleware\ForceJson::class,
        ]);

        // CORS MUST be global to handle OPTIONS preflight
        $middleware->append(HandleCors::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
