<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ExternalAuthorizationCode;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ExternalAuthBrokerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_redirects_with_code_and_state_for_valid_masquerade_user(): void
    {
        Log::spy();

        config()->set('auth.external_broker.enabled', true);
        config()->set('auth.external_broker.require_https_redirects', false);

        $user = User::factory()->create([
            'net_id' => 'ext-user-1',
            'is_active' => true,
        ]);

        config()->set('cas.cas_masquerade', $user->net_id);

        $role = Role::create([
            'name' => 'external_access',
            'display_name' => 'External Access',
            'is_active' => true,
        ]);

        $user->roles()->attach($role->id, [
            'assigned_at' => now(),
        ]);

        $application = Application::create([
            'name' => 'External Client',
            'url_type' => 'external',
            'url' => 'https://external.app-template.com',
            'is_active' => true,
            'supports_api_auth' => true,
            'api_key' => 'app_test_key_123',
            'api_secret' => hash('sha256', 'test-secret'),
            'api_active' => true,
            'external_auth_enabled' => true,
            'external_auth_callback_url' => 'https://external.app-template.com/callback',
        ]);

        $application->roles()->attach($role->id);

        $response = $this->get('/api/auth/authorize?' . http_build_query([
            'api_key' => $application->api_key,
            'redirect_uri' => $application->external_auth_callback_url,
            'state' => 'abc123',
        ]));

        $response->assertStatus(302);

        $location = $response->headers->get('Location');
        $this->assertNotNull($location);

        $query = [];
        parse_str(parse_url($location, PHP_URL_QUERY) ?? '', $query);

        $this->assertArrayHasKey('code', $query);
        $this->assertEquals('abc123', $query['state'] ?? null);
        $this->assertGreaterThanOrEqual(64, strlen($query['code']));

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context = []) use ($application) {
            return $message === 'External auth authorize requested'
                && ($context['api_key_prefix'] ?? null) === substr($application->api_key, 0, 12);
        })->atLeast()->once();

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context = []) use ($application, $user) {
            return $message === 'External auth code issued'
                && ($context['application_id'] ?? null) === $application->id
                && ($context['user_id'] ?? null) === $user->id;
        })->atLeast()->once();
    }

    public function test_callback_path_logs_audit_event_and_issues_code(): void
    {
        Log::spy();

        config()->set('auth.external_broker.enabled', true);
        config()->set('auth.external_broker.require_https_redirects', false);

        $user = User::factory()->create([
            'net_id' => 'ext-user-callback',
            'is_active' => true,
        ]);

        config()->set('cas.cas_masquerade', $user->net_id);

        $role = Role::create([
            'name' => 'external_access_callback',
            'display_name' => 'External Access Callback',
            'is_active' => true,
        ]);

        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        $application = Application::create([
            'name' => 'External Callback Client',
            'url_type' => 'external',
            'url' => 'https://external.app-template.com',
            'is_active' => true,
            'supports_api_auth' => true,
            'api_key' => 'app_test_key_callback',
            'api_secret' => hash('sha256', 'test-secret-callback'),
            'api_active' => true,
            'external_auth_enabled' => true,
            'external_auth_callback_url' => 'https://external.app-template.com/callback-route',
        ]);

        $application->roles()->attach($role->id);

        $requestId = 'req-callback-123';

        $response = $this->withSession([
            'external_auth.pending' => [
                'request_id' => $requestId,
                'application_id' => $application->id,
                'redirect_uri' => $application->external_auth_callback_url,
                'state' => 'callback-state',
                'expires_at' => now()->addMinutes(10)->timestamp,
            ],
        ])->get('/api/auth/callback?rid=' . $requestId);

        $response->assertStatus(302);

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context = []) use ($requestId) {
            return $message === 'External auth callback received'
                && ($context['request_id'] ?? null) === $requestId;
        })->atLeast()->once();

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context = []) use ($application, $user) {
            return $message === 'External auth code issued'
                && ($context['application_id'] ?? null) === $application->id
                && ($context['user_id'] ?? null) === $user->id;
        })->atLeast()->once();
    }

    public function test_token_exchange_returns_bearer_token_and_rejects_replay(): void
    {
        Log::spy();

        config()->set('auth.external_broker.enabled', true);
        config()->set('auth.external_broker.require_https_redirects', false);

        $user = User::factory()->create([
            'net_id' => 'ext-user-2',
            'is_active' => true,
        ]);

        config()->set('cas.cas_masquerade', $user->net_id);

        $role = Role::create([
            'name' => 'external_access_2',
            'display_name' => 'External Access 2',
            'is_active' => true,
        ]);

        $user->roles()->attach($role->id, [
            'assigned_at' => now(),
        ]);

        $application = Application::create([
            'name' => 'External Client 2',
            'url_type' => 'external',
            'url' => 'https://external.app-template.com',
            'is_active' => true,
            'supports_api_auth' => true,
            'api_key' => 'app_test_key_456',
            'api_secret' => hash('sha256', 'test-secret-2'),
            'api_active' => true,
            'external_auth_enabled' => true,
            'external_auth_callback_url' => 'https://external.app-template.com/callback-2',
        ]);

        $application->roles()->attach($role->id);

        $authorize = $this->get('/api/auth/authorize?' . http_build_query([
            'api_key' => $application->api_key,
            'redirect_uri' => $application->external_auth_callback_url,
            'state' => 'state-two',
        ]));

        $authorize->assertStatus(302);
        $location = $authorize->headers->get('Location');
        $query = [];
        parse_str(parse_url($location, PHP_URL_QUERY) ?? '', $query);
        $code = $query['code'] ?? null;

        $this->assertNotNull($code);

        $exchange = $this->postJson('/api/auth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'api_key' => $application->api_key,
            'api_secret' => 'test-secret-2',
            'redirect_uri' => $application->external_auth_callback_url,
        ]);

        $exchange->assertOk();
        $exchange->assertJsonPath('success', true);
        $exchange->assertJsonPath('token_type', 'Bearer');
        $this->assertNotEmpty($exchange->json('access_token'));

        $replay = $this->postJson('/api/auth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'api_key' => $application->api_key,
            'api_secret' => 'test-secret-2',
            'redirect_uri' => $application->external_auth_callback_url,
        ]);

        $replay->assertStatus(400);
        $replay->assertJsonPath('error', 'invalid_grant');

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context = []) use ($application) {
            return $message === 'External auth token exchange requested'
                && ($context['api_key_prefix'] ?? null) === substr($application->api_key, 0, 12);
        })->atLeast()->once();

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context = []) use ($application, $user) {
            return $message === 'External auth token issued'
                && ($context['application_id'] ?? null) === $application->id
                && ($context['user_id'] ?? null) === $user->id;
        })->atLeast()->once();

        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context = []) use ($application) {
            return $message === 'External auth token exchange rejected: invalid_grant'
                && ($context['application_id'] ?? null) === $application->id;
        })->atLeast()->once();
    }

    public function test_authorize_rejects_invalid_redirect_uri(): void
    {
        config()->set('auth.external_broker.enabled', true);
        config()->set('auth.external_broker.require_https_redirects', false);

        $application = Application::create([
            'name' => 'External Client 3',
            'url_type' => 'external',
            'url' => 'https://external.app-template.com',
            'is_active' => true,
            'supports_api_auth' => true,
            'api_key' => 'app_test_key_789',
            'api_secret' => hash('sha256', 'test-secret-3'),
            'api_active' => true,
            'external_auth_enabled' => true,
            'external_auth_callback_url' => 'https://external.app-template.com/allowed-callback',
        ]);

        $response = $this->get('/api/auth/authorize?' . http_build_query([
            'api_key' => $application->api_key,
            'redirect_uri' => 'https://evil.example.com/callback',
            'state' => 'bad-state',
        ]));

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_token_exchange_rejects_expired_authorization_code(): void
    {
        config()->set('auth.external_broker.enabled', true);

        $user = User::factory()->create([
            'net_id' => 'expired-user',
            'is_active' => true,
        ]);

        $role = Role::create([
            'name' => 'external_access_expired',
            'display_name' => 'External Access Expired',
            'is_active' => true,
        ]);

        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        $application = Application::create([
            'name' => 'Expired Code Client',
            'url_type' => 'external',
            'url' => 'https://external.app-template.com',
            'is_active' => true,
            'supports_api_auth' => true,
            'api_key' => 'app_test_key_expired',
            'api_secret' => hash('sha256', 'expired-secret'),
            'api_active' => true,
            'external_auth_enabled' => true,
            'external_auth_callback_url' => 'https://external.app-template.com/callback-expired',
        ]);

        $application->roles()->attach($role->id);

        $plainCode = bin2hex(random_bytes(32));
        ExternalAuthorizationCode::create([
            'application_id' => $application->id,
            'user_id' => $user->id,
            'code_hash' => hash('sha256', $plainCode),
            'redirect_uri' => $application->external_auth_callback_url,
            'state' => 'expired-state',
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->postJson('/api/auth/token', [
            'grant_type' => 'authorization_code',
            'code' => $plainCode,
            'api_key' => $application->api_key,
            'api_secret' => 'expired-secret',
            'redirect_uri' => $application->external_auth_callback_url,
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('error', 'invalid_grant');
    }

    public function test_token_exchange_rejects_inactive_user(): void
    {
        config()->set('auth.external_broker.enabled', true);

        $user = User::factory()->create([
            'net_id' => 'inactive-user',
            'is_active' => false,
        ]);

        $role = Role::create([
            'name' => 'external_access_inactive',
            'display_name' => 'External Access Inactive',
            'is_active' => true,
        ]);

        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        $application = Application::create([
            'name' => 'Inactive User Client',
            'url_type' => 'external',
            'url' => 'https://external.app-template.com',
            'is_active' => true,
            'supports_api_auth' => true,
            'api_key' => 'app_test_key_inactive',
            'api_secret' => hash('sha256', 'inactive-secret'),
            'api_active' => true,
            'external_auth_enabled' => true,
            'external_auth_callback_url' => 'https://external.app-template.com/callback-inactive',
        ]);

        $application->roles()->attach($role->id);

        $plainCode = bin2hex(random_bytes(32));
        ExternalAuthorizationCode::create([
            'application_id' => $application->id,
            'user_id' => $user->id,
            'code_hash' => hash('sha256', $plainCode),
            'redirect_uri' => $application->external_auth_callback_url,
            'state' => 'inactive-state',
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/auth/token', [
            'grant_type' => 'authorization_code',
            'code' => $plainCode,
            'api_key' => $application->api_key,
            'api_secret' => 'inactive-secret',
            'redirect_uri' => $application->external_auth_callback_url,
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('error', 'unauthorized_user');
    }
}
