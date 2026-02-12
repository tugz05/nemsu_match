<?php

use App\Http\Middleware\EnsureProfileCompleted;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureSuperadmin;
use App\Http\Middleware\EnsureAccountNotDisabled;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\UpdateLastSeen;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\CheckPreRegistrationMode;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            UpdateLastSeen::class,
            CheckMaintenanceMode::class,
            CheckPreRegistrationMode::class,
        ]);

        $middleware->alias([
            'profile.completed' => EnsureProfileCompleted::class,
            'admin' => EnsureAdmin::class,
            'superadmin' => EnsureSuperadmin::class,
            'account.active' => EnsureAccountNotDisabled::class,
        ]);

        // Redirect unauthenticated users to home page instead of /login
        $middleware->redirectGuestsTo('/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
