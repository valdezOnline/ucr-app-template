<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share authentication configuration with all views
        view()->composer('*', function ($view) {
            $view->with([
                'authType' => config('auth.type', 'laravel'),
                'isCasAuth' => config('auth.type') === 'cas',
                'isLaravelAuth' => config('auth.type', 'laravel') === 'laravel',
            ]);
        });

        // Custom Blade directives for authentication type checking
        \Illuminate\Support\Facades\Blade::if('casauth', function () {
            return config('auth.type') === 'cas';
        });

        \Illuminate\Support\Facades\Blade::if('laravelauth', function () {
            return config('auth.type', 'laravel') === 'laravel';
        });

        \Illuminate\Support\Facades\Blade::if('casavailable', function () {
            return config('auth.type') === 'cas' &&
                function_exists('cas') &&
                config('cas.cas_hostname') &&
                config('cas.cas_hostname') !== 'cas.myuniv.edu';
        });
    }
}
