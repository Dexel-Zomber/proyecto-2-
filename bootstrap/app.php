<?php

use App\Console\Commands\RecalculateAcademicAlerts;
use App\Http\Middleware\EnsureRole;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // En Laravel 12 el scheduler ya no se define en app/Console/Kernel.php;
        // antes esta tarea nunca se ejecutaba automáticamente.
        $schedule->command(RecalculateAcademicAlerts::class)->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
