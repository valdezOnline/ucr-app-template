<?php

namespace Modules\Core\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Console\Commands\GenerateApiCredentials;
use Modules\Core\Console\Commands\ListModulesCommand;
use Modules\Core\Console\Commands\MakeModuleCommand;
use Modules\Core\Console\Commands\PurgeExpiredExternalAuthorizationCodes;
use Modules\Core\Domain\Auth\Contracts\AuthenticationGateway;
use Modules\Core\Domain\Impersonation\Contracts\ImpersonationGateway;
use Modules\Core\Infrastructure\Auth\LegacyAuthenticationGateway;
use Modules\Core\Infrastructure\Impersonation\LegacyImpersonationGateway;
use Modules\Core\Services\UserActivityTracker;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register any core module services.
     */
    public function register(): void
    {
        $this->app->bind(AuthenticationGateway::class, LegacyAuthenticationGateway::class);
        $this->app->bind(ImpersonationGateway::class, LegacyImpersonationGateway::class);
        $this->app->singleton(UserActivityTracker::class);
    }

    public function boot(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            app(UserActivityTracker::class)->recordLogin($event->user);
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateApiCredentials::class,
                ListModulesCommand::class,
                MakeModuleCommand::class,
                PurgeExpiredExternalAuthorizationCodes::class,
            ]);
        }
    }
}
