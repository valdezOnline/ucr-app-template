<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make
                            {name : Module name (e.g. Billing or UserNotifications)}
                            {--email-template= : Optional email template name to scaffold (e.g. welcome-user)}
                            {--force : Overwrite existing files where possible}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a module scaffold with optional email template support';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $rawName = (string) $this->argument('name');
        $moduleName = Str::studly($rawName);

        if ($moduleName === '') {
            $this->error('A valid module name is required.');
            return Command::FAILURE;
        }

        $modulePath = base_path("modules/{$moduleName}");
        if (File::isDirectory($modulePath) && !$this->option('force')) {
            $this->error("Module '{$moduleName}' already exists. Use --force to overwrite files.");
            return Command::FAILURE;
        }

        $emailTemplateOption = $this->option('email-template');
        $emailTemplateName = is_string($emailTemplateOption) ? trim($emailTemplateOption) : '';
        $shouldCreateEmailTemplate = $emailTemplateName !== '';

        $this->createBaseScaffold($moduleName, $modulePath);

        if ($shouldCreateEmailTemplate) {
            $this->createEmailTemplateScaffold($moduleName, $modulePath, $emailTemplateName);
        }

        $this->info("Module '{$moduleName}' scaffold created successfully.");
        $this->line("Path: {$modulePath}");

        if ($shouldCreateEmailTemplate) {
            $this->line("Email template scaffolded: {$emailTemplateName}");
        }

        return Command::SUCCESS;
    }

    private function createBaseScaffold(string $moduleName, string $modulePath): void
    {
        $moduleKebab = Str::kebab($moduleName);
        $controllerClass = "{$moduleName}Controller";
        $providerClass = "{$moduleName}ServiceProvider";

        $this->ensureDirectory("{$modulePath}/Application");
        $this->ensureDirectory("{$modulePath}/Domain");
        $this->ensureDirectory("{$modulePath}/Infrastructure");
        $this->ensureDirectory("{$modulePath}/Http/Controllers");
        $this->ensureDirectory("{$modulePath}/Providers");
        $this->ensureDirectory("{$modulePath}/routes");
        $this->ensureDirectory("{$modulePath}/resources/views");
        $this->ensureDirectory("{$modulePath}/Database/Migrations");

        $this->writeFile("{$modulePath}/module.php", <<<PHP
<?php

return [
    'name' => '{$moduleName}',
    'providers' => [
        Modules\\{$moduleName}\\Providers\\{$providerClass}::class,
    ],
];
PHP
        );

        $this->writeFile("{$modulePath}/Providers/{$providerClass}.php", <<<PHP
<?php

namespace Modules\\{$moduleName}\\Providers;

use Illuminate\\Support\\ServiceProvider;

class {$providerClass} extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        \$this->loadViewsFrom(__DIR__ . '/../resources/views', '{$moduleKebab}');
    }
}
PHP
        );

        $this->writeFile("{$modulePath}/Http/Controllers/{$controllerClass}.php", <<<PHP
<?php

namespace Modules\\{$moduleName}\\Http\\Controllers;

use App\\Http\\Controllers\\Controller;
use Illuminate\\Contracts\\View\\View;

class {$controllerClass} extends Controller
{
    public function index(): View
    {
        return view('{$moduleKebab}::index');
    }
}
PHP
        );

        $this->writeFile("{$modulePath}/routes/web.php", <<<PHP
<?php

use Illuminate\\Support\\Facades\\Route;
use Modules\\{$moduleName}\\Http\\Controllers\\{$controllerClass};

Route::prefix('{$moduleKebab}')
    ->name('{$moduleKebab}.')
    ->group(function (): void {
        Route::get('/', [{$controllerClass}::class, 'index'])->name('index');
    });
PHP
        );

        $this->writeFile("{$modulePath}/routes/api.php", <<<PHP
<?php

use Illuminate\\Support\\Facades\\Route;

Route::prefix('{$moduleKebab}')
    ->name('api.{$moduleKebab}.')
    ->group(function (): void {
        Route::get('/health', function () {
            return response()->json([
                'module' => '{$moduleName}',
                'status' => 'ok',
            ]);
        })->name('health');
    });
PHP
        );

        $this->writeFile("{$modulePath}/resources/views/index.blade.php", <<<BLADE
@extends('layouts.app')

@section('title', '{$moduleName}')

@section('content')
    <div class="container">
        <div class="card shadow-sm">
            <div class="card-header">
                <h4 class="mb-0">{$moduleName} module</h4>
            </div>
            <div class="card-body">
                <p class="mb-0">This module was scaffolded by <code>php artisan module:make {$moduleName}</code>.</p>
            </div>
        </div>
    </div>
@endsection
BLADE
        );
    }

    private function createEmailTemplateScaffold(string $moduleName, string $modulePath, string $rawTemplateName): void
    {
        $templateSlug = Str::of($rawTemplateName)->snake('-')->replace('_', '-')->toString();
        $templateStudly = Str::studly(str_replace('-', ' ', $templateSlug));
        $className = "{$templateStudly}EmailTemplate";
        $moduleKebab = Str::kebab($moduleName);

        $this->ensureDirectory("{$modulePath}/Application/EmailTemplates");
        $this->ensureDirectory("{$modulePath}/resources/views/emails");

        $this->writeFile("{$modulePath}/Application/EmailTemplates/{$className}.php", <<<PHP
<?php

namespace Modules\\{$moduleName}\\Application\\EmailTemplates;

use Modules\\Core\\Domain\\Notifications\\Data\\EmailMessage;

class {$className}
{
    /**
     * @param array<int, string> \$to
     * @param array<string, mixed> \$data
     */
    public function build(array \$to, array \$data = []): EmailMessage
    {
        \$subject = \$data['subject'] ?? '{$moduleName} notification';
        \$htmlBody = view('{$moduleKebab}::emails.{$templateSlug}', [
            'data' => \$data,
        ])->render();

        return new EmailMessage(
            to: \$to,
            subject: \$subject,
            htmlBody: \$htmlBody,
            textBody: strip_tags(\$htmlBody),
        );
    }
}
PHP
        );

        $this->writeFile("{$modulePath}/resources/views/emails/{$templateSlug}.blade.php", <<<'BLADE'
<p>Hello,</p>

<p>This is an email template generated for this module.</p>

@if (!empty($data))
    <p><strong>Template data:</strong></p>
    <pre>{{ json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
@endif

<p>Regards,<br>{{ config('app.name') }}</p>
BLADE
        );
    }

    private function ensureDirectory(string $path): void
    {
        File::ensureDirectoryExists($path);
    }

    private function writeFile(string $path, string $content): void
    {
        if (File::exists($path) && !$this->option('force')) {
            return;
        }

        File::put($path, $content . PHP_EOL);
    }
}
