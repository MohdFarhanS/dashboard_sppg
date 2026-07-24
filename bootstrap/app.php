<?php

use App\Http\Middleware\RoleMiddleware;
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
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        // Dipanggil external cron tanpa session Laravel — proteksi pakai
        // token header sendiri (hash_equals), bukan CSRF berbasis session.
        $middleware->validateCsrfTokens(except: [
            'system/demo-reset',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
