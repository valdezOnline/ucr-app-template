<?php

namespace App\Providers;

use App\Models\SiteStatusSetting;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Application\Auth\AuthenticationManager;

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
            static $resolvedSiteStatus = false;
            static $activeSiteStatus = null;
            $authenticationManager = app(AuthenticationManager::class);

            if (!$resolvedSiteStatus) {
                if (Schema::hasTable('site_status_settings')) {
                    $siteStatus = SiteStatusSetting::current();
                    $activeSiteStatus = $siteStatus->isActive() ? $siteStatus : null;
                }

                $resolvedSiteStatus = true;
            }

            $view->with([
                'authType' => $authenticationManager->getType(),
                'isCasAuth' => $authenticationManager->isCasAuth(),
                'isLaravelAuth' => $authenticationManager->isLaravelAuth(),
                'casAvailable' => $authenticationManager->isCasAvailable(),
                'hasValidCasMasqueradeUser' => $authenticationManager->hasValidCasMasqueradeUser(),
                'authTypeDisplayName' => $authenticationManager->getTypeDisplayName(),
                'registrationEnabled' => $authenticationManager->registrationEnabled(),
                'passwordResetEnabled' => $authenticationManager->passwordResetEnabled(),
                'activeSiteStatus' => $activeSiteStatus,
            ]);
        });

        // Custom Blade directives for authentication type checking
        \Illuminate\Support\Facades\Blade::if('casauth', function () {
            return app(AuthenticationManager::class)->isCasAuth();
        });

        \Illuminate\Support\Facades\Blade::if('laravelauth', function () {
            return app(AuthenticationManager::class)->isLaravelAuth();
        });

        \Illuminate\Support\Facades\Blade::if('casavailable', function () {
            return app(AuthenticationManager::class)->isCasAvailable();
        });
    }
}
