<?php

namespace Tests\Feature;

use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCredentialsValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_credentials_and_domain_validation_work_for_active_application(): void
    {
        $apiKey = 'app_test_new_key';
        $apiSecret = 'plain-test-secret';

        $application = Application::create([
            'name' => 'Credentials Test App',
            'url' => 'https://example.test',
            'is_active' => true,
            'supports_api_auth' => true,
            'api_key' => $apiKey,
            'api_secret' => hash('sha256', $apiSecret),
            'allowed_domains' => ['localhost'],
            'api_key_expires_at' => now()->addDay(),
            'api_active' => true,
        ]);

        $this->assertTrue($application->verifyApiCredentials($apiKey, $apiSecret));
        $this->assertFalse($application->verifyApiCredentials($apiKey, 'wrong-secret'));

        $this->assertTrue($application->isDomainAllowed('localhost'));
        $this->assertFalse($application->isDomainAllowed(null));
    }

    public function test_old_api_key_is_not_present(): void
    {
        $oldApiKey = 'app_bcd2113f6014766964c3296a375fbb97f3ed7ad400c329df';

        $this->assertNull(Application::where('api_key', $oldApiKey)->first());
    }
}