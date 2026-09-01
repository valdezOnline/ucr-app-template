<?php

namespace App\Console\Commands;

use App\Models\Application;
use Illuminate\Console\Command;

class GenerateApiCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-api-credentials
                            {application? : The application name or ID}
                            {--list : List all applications}
                            {--revoke : Revoke existing API credentials}
                            {--domains=* : Allowed domains for API access}
                            {--expires= : Expiration date (Y-m-d format)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate or manage API credentials for external application authentication';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('list')) {
            return $this->listApplications();
        }

        $applicationInput = $this->argument('application');

        if (!$applicationInput) {
            $applications = Application::active()->get();

            if ($applications->isEmpty()) {
                $this->error('No applications found. Please create an application first.');
                return Command::FAILURE;
            }

            $choices = $applications->mapWithKeys(function ($app) {
                return [$app->id => "{$app->name} (ID: {$app->id})"];
            })->toArray();

            $applicationId = $this->choice('Select an application:', $choices);
            $application = $applications->find($applicationId);
        } else {
            // Try to find by ID first, then by name
            $application = Application::find($applicationInput) ?:
                Application::where('name', $applicationInput)->first();

            if (!$application) {
                $this->error("Application not found: {$applicationInput}");
                return Command::FAILURE;
            }
        }

        if ($this->option('revoke')) {
            return $this->revokeCredentials($application);
        }

        return $this->generateCredentials($application);
    }

    private function listApplications()
    {
        $applications = Application::with('creator')->get();

        if ($applications->isEmpty()) {
            $this->info('No applications found.');
            return Command::SUCCESS;
        }

        $headers = ['ID', 'Name', 'API Enabled', 'API Key', 'Expires At', 'Created By'];
        $rows = $applications->map(function ($app) {
            return [
                $app->id,
                $app->name,
                $app->supports_api_auth ? 'Yes' : 'No',
                $app->api_key ? substr($app->api_key, 0, 20) . '...' : 'None',
                $app->api_key_expires_at ? $app->api_key_expires_at->format('Y-m-d') : 'Never',
                $app->creator->name ?? 'Unknown',
            ];
        })->toArray();

        $this->table($headers, $rows);
        return Command::SUCCESS;
    }

    private function generateCredentials(Application $application)
    {
        if ($application->supports_api_auth && $application->api_key) {
            if (!$this->confirm("Application '{$application->name}' already has API credentials. Regenerate?")) {
                return Command::SUCCESS;
            }
        }

        // Set allowed domains
        $domains = $this->option('domains');
        if (empty($domains)) {
            $domainInput = $this->ask('Enter allowed domains (comma-separated, leave blank for no restrictions)');
            if ($domainInput) {
                $domains = array_map('trim', explode(',', $domainInput));
            }
        }

        // Set expiration
        $expires = $this->option('expires');
        if ($expires) {
            try {
                $expiresAt = \Carbon\Carbon::createFromFormat('Y-m-d', $expires);
            } catch (\Exception $e) {
                $this->error('Invalid date format. Use Y-m-d format.');
                return Command::FAILURE;
            }
        } else {
            $expiresAt = now()->addYear(); // Default 1 year
        }

        // Generate credentials
        $credentials = $application->generateApiCredentials();

        // Update additional settings
        $application->update([
            'allowed_domains' => !empty($domains) ? $domains : null,
            'api_key_expires_at' => $expiresAt,
        ]);

        $this->info("API credentials generated for application: {$application->name}");
        $this->line('');
        $this->warn('IMPORTANT: Save these credentials securely. The API secret will not be shown again.');
        $this->line('');

        $this->table(
            ['Credential', 'Value'],
            [
                ['API Key', $credentials['api_key']],
                ['API Secret', $credentials['api_secret']],
                ['Expires At', $expiresAt->format('Y-m-d H:i:s')],
                ['Allowed Domains', !empty($domains) ? implode(', ', $domains) : 'No restrictions'],
            ]
        );

        $this->line('');
        $this->info('Example API Usage:');
        $this->line("curl -X POST " . url('/api/auth/authenticate') . " \\");
        $this->line("  -H 'Content-Type: application/json' \\");
        $this->line("  -d '{");
        $this->line("    \"api_key\": \"" . $credentials['api_key'] . "\",");
        $this->line("    \"api_secret\": \"" . $credentials['api_secret'] . "\",");
        $this->line("    \"username\": \"user@example.com\",");
        $this->line("    \"password\": \"password\"");
        $this->line("  }'");

        return Command::SUCCESS;
    }

    private function revokeCredentials(Application $application)
    {
        if (!$application->supports_api_auth || !$application->api_key) {
            $this->info("Application '{$application->name}' does not have active API credentials.");
            return Command::SUCCESS;
        }

        if (!$this->confirm("Are you sure you want to revoke API credentials for '{$application->name}'?")) {
            return Command::SUCCESS;
        }

        $application->revokeApiAccess();

        $this->info("API credentials revoked for application: {$application->name}");
        return Command::SUCCESS;
    }
}
