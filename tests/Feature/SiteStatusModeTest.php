<?php

namespace Tests\Feature;

use App\Models\SiteStatusSetting;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SiteStatusModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_admin_can_update_site_status_mode(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->put(route('site-status-config.update'), [
                '_token' => 'test-token',
                'mode' => SiteStatusSetting::MODE_MAINTENANCE,
                'message' => 'Scheduled maintenance from 10 PM to 11 PM.',
            ]);

        $response->assertRedirect(route('site-status-config.edit'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('site_status_settings', [
            'mode' => SiteStatusSetting::MODE_MAINTENANCE,
            'message' => 'Scheduled maintenance from 10 PM to 11 PM.',
            'updated_by' => $admin->id,
        ]);
    }

    public function test_custom_message_mode_requires_message(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->from(route('site-status-config.edit'))
            ->withSession(['_token' => 'test-token'])
            ->put(route('site-status-config.update'), [
                '_token' => 'test-token',
                'mode' => SiteStatusSetting::MODE_CUSTOM_MESSAGE,
                'message' => '',
            ]);

        $response->assertRedirect(route('site-status-config.edit'));
        $response->assertSessionHasErrors('message');
    }

    public function test_non_admin_users_are_redirected_to_status_page_when_mode_is_enabled(): void
    {
        SiteStatusSetting::current()->update([
            'mode' => SiteStatusSetting::MODE_TECHNICAL_ISSUE,
            'message' => null,
        ]);
        $this->assertEquals(
            SiteStatusSetting::MODE_TECHNICAL_ISSUE,
            SiteStatusSetting::current()->mode
        );

        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('site-status.show'));
    }

    public function test_admin_can_access_admin_routes_while_mode_is_enabled(): void
    {
        SiteStatusSetting::current()->update([
            'mode' => SiteStatusSetting::MODE_MAINTENANCE,
            'message' => null,
        ]);

        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('site-status-config.edit'));

        $response->assertOk();
    }

    public function test_status_page_displays_expected_message(): void
    {
        SiteStatusSetting::current()->update([
            'mode' => SiteStatusSetting::MODE_TECHNICAL_ISSUE,
            'message' => null,
        ]);
        $this->assertEquals(
            SiteStatusSetting::MODE_TECHNICAL_ISSUE,
            SiteStatusSetting::current()->mode
        );

        $technicalResponse = $this->get(route('site-status.show'));
        $technicalResponse->assertOk();
        $technicalResponse->assertViewIs('site-status.show');
        $technicalResponse->assertViewHas('siteStatus', function ($status) {
            return $status->mode === SiteStatusSetting::MODE_TECHNICAL_ISSUE
                && $status->displayTitle() === 'Experiencing Technical Issue'
                && str_contains($status->displayMessage(), 'technical issues');
        });

        SiteStatusSetting::current()->update([
            'mode' => SiteStatusSetting::MODE_CUSTOM_MESSAGE,
            'message' => 'Unexpected outage in reporting services.',
        ]);

        $customResponse = $this->get(route('site-status.show'));
        $customResponse->assertOk();
        $customResponse->assertViewHas('siteStatus', function ($status) {
            return $status->mode === SiteStatusSetting::MODE_CUSTOM_MESSAGE
                && $status->displayMessage() === 'Unexpected outage in reporting services.';
        });
    }

    public function test_admin_sees_active_site_status_banner_in_app_layout(): void
    {
        SiteStatusSetting::current()->update([
            'mode' => SiteStatusSetting::MODE_MAINTENANCE,
            'message' => 'Planned maintenance window is in progress.',
        ]);

        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/home');

        $response->assertOk();
        $response->assertSee('Site Status Active:');
        $response->assertSee('Maintenance Mode');
        $response->assertSee('Planned maintenance window is in progress.');
        $response->assertSee('Manage Site Status');
    }

    public function test_enabling_site_status_switches_authentication_from_cas_to_laravel(): void
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
            ->put(route('site-status-config.update'), [
                '_token' => 'test-token',
                'mode' => SiteStatusSetting::MODE_MAINTENANCE,
                'message' => 'Maintenance in progress.',
            ]);

        $response->assertRedirect(route('site-status-config.edit'));
        $response->assertSessionHas('success');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_disabling_site_status_switches_authentication_from_laravel_to_cas_and_logs_out(): void
    {
        Config::set('auth.type', 'laravel');

        SiteStatusSetting::current()->update([
            'mode' => SiteStatusSetting::MODE_MAINTENANCE,
            'message' => 'Maintenance in progress.',
        ]);

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
            ->put(route('site-status-config.update'), [
                '_token' => 'test-token',
                'mode' => SiteStatusSetting::MODE_NONE,
                'message' => null,
            ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');
        $this->assertGuest();
    }
}
