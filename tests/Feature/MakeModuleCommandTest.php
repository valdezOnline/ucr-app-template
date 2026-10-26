<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class MakeModuleCommandTest extends TestCase
{
    /**
     * @var array<int, string>
     */
    private array $createdModulePaths = [];

    protected function tearDown(): void
    {
        foreach ($this->createdModulePaths as $path) {
            if (File::isDirectory($path)) {
                File::deleteDirectory($path);
            }
        }

        parent::tearDown();
    }

    public function test_it_scaffolds_a_module_structure(): void
    {
        $moduleName = 'TestModule' . Str::studly(Str::random(8));
        $modulePath = base_path("modules/{$moduleName}");
        $this->createdModulePaths[] = $modulePath;

        $this->artisan('module:make', [
            'name' => $moduleName,
        ])->assertExitCode(0);

        $this->assertTrue(File::isDirectory($modulePath));
        $this->assertTrue(File::exists("{$modulePath}/module.php"));
        $this->assertTrue(File::exists("{$modulePath}/Providers/{$moduleName}ServiceProvider.php"));
        $this->assertTrue(File::exists("{$modulePath}/Presentation/Http/Controllers/{$moduleName}Controller.php"));
        $this->assertTrue(File::exists("{$modulePath}/routes/web.php"));
        $this->assertTrue(File::exists("{$modulePath}/routes/api.php"));
        $this->assertTrue(File::exists("{$modulePath}/resources/views/index.blade.php"));
    }

    public function test_it_can_scaffold_module_with_email_template(): void
    {
        $moduleName = 'TestEmailModule' . Str::studly(Str::random(8));
        $modulePath = base_path("modules/{$moduleName}");
        $this->createdModulePaths[] = $modulePath;

        $this->artisan('module:make', [
            'name' => $moduleName,
            '--email-template' => 'welcome-user',
        ])->assertExitCode(0);

        $this->assertTrue(File::exists("{$modulePath}/Application/EmailTemplates/WelcomeUserEmailTemplate.php"));
        $this->assertTrue(File::exists("{$modulePath}/resources/views/emails/welcome-user.blade.php"));
    }
}
