<?php

use App\Http\Middleware\EnsureAntiqscanAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Public Caddy forwards to FrankenPHP with a local Host header. Trust the
        // proxy-provided public scheme and host so signed verification URLs remain valid.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'antiqscan.access' => EnsureAntiqscanAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
