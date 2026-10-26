<?php

namespace Modules\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ListModulesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:list {--json : Output module metadata as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List discovered modules with providers, route files, and email template assets';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modules = $this->discoverModules();

        if ($this->option('json')) {
            $this->line(json_encode($modules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return Command::SUCCESS;
        }

        if ($modules === []) {
            $this->info('No modules found.');
            return Command::SUCCESS;
        }

        $rows = array_map(function (array $module): array {
            return [
                $module['name'],
                $module['path'],
                (string) $module['provider_count'],
                $module['has_web_routes'] ? 'yes' : 'no',
                $module['has_api_routes'] ? 'yes' : 'no',
                (string) $module['email_template_count'],
            ];
        }, $modules);

        $this->table(
            ['Module', 'Path', 'Providers', 'Web routes', 'API routes', 'Email templates'],
            $rows
        );

        return Command::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function discoverModules(): array
    {
        $discovered = [];
        $modulePaths = config('modules.paths', []);
        $routeFiles = config('modules.route_files', []);
        $webRouteFile = $routeFiles['web'] ?? 'routes/web.php';
        $apiRouteFile = $routeFiles['api'] ?? 'routes/api.php';

        foreach ($modulePaths as $moduleRootPath) {
            if (!is_string($moduleRootPath) || !File::isDirectory($moduleRootPath)) {
                continue;
            }

            foreach (File::directories($moduleRootPath) as $modulePath) {
                $manifestPath = $modulePath . DIRECTORY_SEPARATOR . 'module.php';
                $manifest = File::exists($manifestPath) ? require $manifestPath : [];
                $providers = is_array($manifest) ? ($manifest['providers'] ?? []) : [];
                $moduleName = is_array($manifest) && isset($manifest['name']) && is_string($manifest['name'])
                    ? $manifest['name']
                    : basename($modulePath);

                $emailTemplateCount = 0;
                $emailTemplatePath = $modulePath . DIRECTORY_SEPARATOR . 'resources/views/emails';
                if (File::isDirectory($emailTemplatePath)) {
                    $emailTemplateCount = count(File::allFiles($emailTemplatePath));
                }

                $discovered[] = [
                    'name' => $moduleName,
                    'path' => $modulePath,
                    'provider_count' => is_array($providers) ? count($providers) : 0,
                    'providers' => is_array($providers) ? array_values($providers) : [],
                    'has_web_routes' => File::exists($modulePath . DIRECTORY_SEPARATOR . $webRouteFile),
                    'has_api_routes' => File::exists($modulePath . DIRECTORY_SEPARATOR . $apiRouteFile),
                    'email_template_count' => $emailTemplateCount,
                ];
            }
        }

        usort($discovered, static fn (array $a, array $b) => strcmp((string) $a['name'], (string) $b['name']));

        return $discovered;
    }
}
