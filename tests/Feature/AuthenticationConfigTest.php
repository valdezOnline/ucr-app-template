<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AuthenticationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthenticationConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_default_authentication_type_is_laravel()
    {
        Config::set('auth.type', 'laravel');

        // Default should be laravel
        $this->assertEquals('laravel', AuthenticationService::getType());
        $this->assertTrue(AuthenticationService::isLaravelAuth());
        $this->assertFalse(AuthenticationService::isCasAuth());
    }

    public function test_can_detect_cas_authentication_type()
    {
        Config::set('auth.type', 'cas');

        $this->assertEquals('cas', AuthenticationService::getType());
        $this->assertFalse(AuthenticationService::isLaravelAuth());
        $this->assertTrue(AuthenticationService::isCasAuth());
    }

    public function test_registration_enabled_only_for_laravel_auth()
    {
        // Laravel auth - registration should be enabled
        Config::set('auth.type', 'laravel');
        $this->assertTrue(AuthenticationService::registrationEnabled());

        // CAS auth - registration should be disabled
        Config::set('auth.type', 'cas');
        $this->assertFalse(AuthenticationService::registrationEnabled());
    }

    public function test_password_reset_enabled_only_for_laravel_auth()
    {
        // Laravel auth - password reset should be enabled
        Config::set('auth.type', 'laravel');
        $this->assertTrue(AuthenticationService::passwordResetEnabled());

        // CAS auth - password reset should be disabled
        Config::set('auth.type', 'cas');
        $this->assertFalse(AuthenticationService::passwordResetEnabled());
    }

    public function test_cas_availability_check()
    {
        Config::set('auth.type', 'cas');
        Config::set('cas.cas_hostname', 'https://cas.example.com');

        // CAS should be considered configured when hostname is set to non-default
        $this->assertTrue(AuthenticationService::isCasAuth());

        // Reset to default
        Config::set('cas.cas_hostname', 'cas.myuniv.edu');
        $this->assertFalse(AuthenticationService::isCasAvailable());
    }

    public function test_authentication_type_display_names()
    {
        Config::set('auth.type', 'laravel');
        $this->assertEquals('Local Authentication', AuthenticationService::getTypeDisplayName());

        Config::set('auth.type', 'cas');
        $this->assertEquals('CAS (Single Sign-On)', AuthenticationService::getTypeDisplayName());
    }

    public function test_cas_masquerade_requires_existing_active_user()
    {
        Config::set('cas.cas_masquerade', 'missing-user');
        $this->assertFalse(AuthenticationService::hasValidCasMasqueradeUser());

        $inactive = User::factory()->create([
            'net_id' => 'inactive-user',
            'is_active' => false,
        ]);
        Config::set('cas.cas_masquerade', $inactive->net_id);
        $this->assertFalse(AuthenticationService::hasValidCasMasqueradeUser());

        $active = User::factory()->create([
            'net_id' => 'active-user',
            'is_active' => true,
        ]);
        Config::set('cas.cas_masquerade', $active->net_id);
        $this->assertTrue(AuthenticationService::hasValidCasMasqueradeUser());
    }

    public function test_login_route_name_falls_back_to_laravel_login_when_cas_route_missing()
    {
        Config::set('auth.type', 'cas');
        Config::set('cas.cas_hostname', 'sso.ucr.edu');

        Route::shouldReceive('has')
            ->once()
            ->with('cas.login')
            ->andReturn(false);

        $this->assertEquals('login', AuthenticationService::getLoginRouteName());
    }

    public function test_login_route_name_falls_back_to_laravel_login_when_cas_is_not_usable()
    {
        Config::set('auth.type', 'cas');
        Config::set('cas.cas_hostname', 'cas.myuniv.edu');
        Config::set('cas.cas_masquerade', null);

        Route::shouldReceive('has')
            ->once()
            ->with('cas.login')
            ->andReturn(true);

        $this->assertEquals('login', AuthenticationService::getLoginRouteName());
    }

    public function test_logout_route_name_falls_back_to_laravel_logout_when_cas_route_missing()
    {
        Config::set('auth.type', 'cas');

        Route::shouldReceive('has')
            ->once()
            ->with('cas.logout')
            ->andReturn(false);

        $this->assertEquals('logout', AuthenticationService::getLogoutRouteName());
    }

    public function test_login_page_is_rendered_in_cas_mode_without_forced_redirect()
    {
        Config::set('auth.type', 'cas');

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Sign In with CAS');
    }

    public function test_posting_local_login_in_cas_mode_redirects_back_with_message()
    {
        Config::set('auth.type', 'cas');

        $response = $this->withSession(['_token' => 'test-token'])
            ->post('/login', [
                '_token' => 'test-token',
                'email' => 'admin@example.com',
                'password' => 'invalid-password',
            ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('info');
    }

    public function test_admin_can_switch_auth_type_from_laravel_to_cas()
    {
        Config::set('auth.type', 'laravel');

        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $envPath = base_path('.env');
        $envContent = "APP_NAME=\"App Template\"\nAUTH_TYPE=laravel\n";

        File::shouldReceive('get')
            ->once()
            ->with($envPath)
            ->andReturn($envContent);

        File::shouldReceive('put')
            ->once()
            ->withArgs(function (string $path, string $content) use ($envPath) {
                return $path === $envPath && str_contains($content, 'AUTH_TYPE=cas');
            })
            ->andReturn(1);

        Artisan::shouldReceive('call')
            ->once()
            ->with('config:clear')
            ->andReturn(0);
        Artisan::shouldReceive('call')
            ->with('route:clear')
            ->zeroOrMoreTimes()
            ->andReturn(0);

        $response = $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->put(route('auth-config.update'), [
                '_token' => 'test-token',
                'auth_type' => 'cas',
            ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');
        $this->assertGuest();
    }

    public function test_admin_can_switch_auth_type_from_cas_to_laravel()
    {
        Config::set('auth.type', 'cas');

        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $envPath = base_path('.env');
        $envContent = "APP_NAME=\"App Template\"\nAUTH_TYPE=cas\n";

        File::shouldReceive('get')
            ->once()
            ->with($envPath)
            ->andReturn($envContent);

        File::shouldReceive('put')
            ->once()
            ->withArgs(function (string $path, string $content) use ($envPath) {
                return $path === $envPath && str_contains($content, 'AUTH_TYPE=laravel');
            })
            ->andReturn(1);

        Artisan::shouldReceive('call')
            ->once()
            ->with('config:clear')
            ->andReturn(0);
        Artisan::shouldReceive('call')
            ->with('route:clear')
            ->zeroOrMoreTimes()
            ->andReturn(0);

        $response = $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->put(route('auth-config.update'), [
                '_token' => 'test-token',
                'auth_type' => 'laravel',
            ]);

        $response->assertRedirect(route('auth-config.index'));
        $response->assertSessionHas('success');
        $this->assertAuthenticatedAs($admin);
    }
}