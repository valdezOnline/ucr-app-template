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
    ->withCommands([
        __DIR__ . '/../modules/Core/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \Modules\Core\Presentation\Http\Middleware\SiteStatusMiddleware::class,
        ]);

        $middleware->alias([
            'auth.type' => \Modules\Core\Presentation\Http\Middleware\AuthenticationTypeMiddleware::class,
            'api.app.auth' => \Modules\Core\Presentation\Http\Middleware\ApiApplicationAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
