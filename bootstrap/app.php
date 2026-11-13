<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\EnsureOnboarded;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\EnsurePasswordChanged::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
        $middleware->alias([
            'onboarded' => EnsureOnboarded::class,
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);
        // Diperlukan untuk "keluar dari semua perangkat" (Auth::logoutOtherDevices).
        $middleware->authenticateSessions();
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('beranda'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
