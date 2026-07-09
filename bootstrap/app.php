<?php

use App\Console\Commands\RecalculateAcademicAlerts;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureRole;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => EnsurePermission::class,
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
