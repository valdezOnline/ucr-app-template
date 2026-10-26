<?php

namespace Modules\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Console\Commands\GenerateApiCredentials;
use Modules\Core\Console\Commands\ListModulesCommand;
use Modules\Core\Console\Commands\MakeModuleCommand;
use Modules\Core\Console\Commands\PurgeExpiredExternalAuthorizationCodes;
use Modules\Core\Domain\Auth\Contracts\AuthenticationGateway;
use Modules\Core\Domain\Impersonation\Contracts\ImpersonationGateway;
use Modules\Core\Infrastructure\Auth\LegacyAuthenticationGateway;
use Modules\Core\Infrastructure\Impersonation\LegacyImpersonationGateway;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register any core module services.
     */
    public function register(): void
    {
        $this->app->bind(AuthenticationGateway::class, LegacyAuthenticationGateway::class);
        $this->app->bind(ImpersonationGateway::class, LegacyImpersonationGateway::class);
    }

    public function boot(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            GenerateApiCredentials::class,
            ListModulesCommand::class,
            MakeModuleCommand::class,
            PurgeExpiredExternalAuthorizationCodes::class,
        ]);
    }
}
