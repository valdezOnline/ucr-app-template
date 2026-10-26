<?php

namespace Modules\Core\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register any module services.
     */
    public function register(): void
    {
        foreach ($this->discoverModuleManifestFiles() as $manifestPath) {
            $manifest = require $manifestPath;
            $providers = $manifest['providers'] ?? [];

            foreach ($providers as $provider) {
                if (is_string($provider) && class_exists($provider)) {
                    $this->app->register($provider);
                }
            }
        }
    }

    /**
     * Bootstrap any module services.
     */
    public function boot(): void
    {
        $routeFiles = config('modules.route_files', []);
        $webRouteFile = $routeFiles['web'] ?? 'routes/web.php';
        $apiRouteFile = $routeFiles['api'] ?? 'routes/api.php';

        foreach ($this->discoverModuleDirectories() as $modulePath) {
            $this->loadWebRoutes($modulePath . DIRECTORY_SEPARATOR . $webRouteFile);
            $this->loadApiRoutes($modulePath . DIRECTORY_SEPARATOR . $apiRouteFile);
        }
    }

    /**
     * @return array<int, string>
     */
    private function discoverModuleDirectories(): array
    {
        $directories = [];
        $paths = config('modules.paths', []);

        foreach ($paths as $path) {
            if (!is_string($path) || $path === '' || !File::isDirectory($path)) {
                continue;
            }

            foreach (File::directories($path) as $modulePath) {
                $directories[] = $modulePath;
            }
        }

        return $directories;
    }

    /**
     * @return array<int, string>
     */
    private function discoverModuleManifestFiles(): array
    {
        $manifestFiles = [];

        foreach ($this->discoverModuleDirectories() as $modulePath) {
            $manifestPath = $modulePath . DIRECTORY_SEPARATOR . 'module.php';
            if (File::exists($manifestPath)) {
                $manifestFiles[] = $manifestPath;
            }
        }

        return $manifestFiles;
    }

    private function loadWebRoutes(string $routePath): void
    {
        if (!File::exists($routePath)) {
            return;
        }

        Route::middleware('web')->group($routePath);
    }

    private function loadApiRoutes(string $routePath): void
    {
        if (!File::exists($routePath)) {
            return;
        }

        Route::prefix('api')->middleware('api')->group($routePath);
    }
}
